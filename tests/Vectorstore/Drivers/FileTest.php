<?php

declare(strict_types=1);

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Mindwave\Mindwave\Vectorstore\Drivers\File;

require_once __DIR__.'/../Helpers.php';

describe('File Vectorstore', function () {
    beforeEach(function () {
        $this->testPath = sys_get_temp_dir().'/mindwave_test_'.uniqid().'.json';
        $this->vectorstore = new File($this->testPath);
    });

    afterEach(function () {
        if (file_exists($this->testPath)) {
            unlink($this->testPath);
        }
    });

    describe('Construction', function () {
        it('creates new store with zero items', function () {
            expect($this->vectorstore->itemCount())->toBe(0);
        });

        it('creates directory on first insert if it does not exist', function () {
            $nestedPath = sys_get_temp_dir().'/mindwave_nested_'.uniqid().'/subdir/store.json';
            $vectorstore = new File($nestedPath);

            $entry = createTestEntry([0.1, 0.2, 0.3], 'Test content');
            $vectorstore->insert($entry);

            expect(file_exists($nestedPath))->toBeTrue();
            expect(file_exists(dirname($nestedPath)))->toBeTrue();

            // Cleanup
            unlink($nestedPath);
            rmdir(dirname($nestedPath));
            rmdir(dirname(dirname($nestedPath)));
        });

        it('loads existing file on construction', function () {
            $entry = createTestEntry([0.1, 0.2, 0.3], 'Test content');
            $this->vectorstore->insert($entry);

            $newVectorstore = new File($this->testPath);
            expect($newVectorstore->itemCount())->toBe(1);
        });

        it('handles empty JSON file', function () {
            file_put_contents($this->testPath, '[]');
            $vectorstore = new File($this->testPath);

            expect($vectorstore->itemCount())->toBe(0);
        });
    });

    describe('insert()', function () {
        it('inserts single entry', function () {
            $entry = createTestEntry([0.1, 0.2, 0.3], 'Test content');

            $this->vectorstore->insert($entry);

            expect($this->vectorstore->itemCount())->toBe(1);
        });

        it('inserts multiple entries individually', function () {
            for ($i = 0; $i < 5; $i++) {
                $this->vectorstore->insert(createTestEntry([0.1 * $i, 0.2, 0.3], "Content {$i}"));
            }

            expect($this->vectorstore->itemCount())->toBe(5);
        });

        it('persists data to file', function () {
            $entry = createTestEntry([0.1, 0.2, 0.3], 'Test content');
            $this->vectorstore->insert($entry);

            expect(file_exists($this->testPath))->toBeTrue();
            $content = file_get_contents($this->testPath);
            expect($content)->toBeJson();
        });

        it('preserves metadata', function () {
            $entry = createTestEntry([0.1, 0.2, 0.3], 'Content', [
                '_mindwave_doc_source_id' => 'source-123',
                '_mindwave_doc_source_type' => 'test',
                '_mindwave_doc_chunk_index' => 5,
                'custom_field' => 'custom_value',
            ]);

            $this->vectorstore->insert($entry);

            $newVectorstore = new File($this->testPath);
            $results = $newVectorstore->similaritySearch(new EmbeddingVector([0.1, 0.2, 0.3]), 1);

            expect($results[0]->document->metadata()['custom_field'])->toBe('custom_value');
        });
    });

    describe('insertMany()', function () {
        it('inserts array of entries', function () {
            $entries = [
                createTestEntry([0.1, 0.2, 0.3], 'Content 1'),
                createTestEntry([0.4, 0.5, 0.6], 'Content 2'),
                createTestEntry([0.7, 0.8, 0.9], 'Content 3'),
            ];

            $this->vectorstore->insertMany($entries);

            expect($this->vectorstore->itemCount())->toBe(3);
        });

        it('handles empty array', function () {
            $this->vectorstore->insertMany([]);

            expect($this->vectorstore->itemCount())->toBe(0);
        });

        it('persists all entries to file', function () {
            $entries = [
                createTestEntry([0.1, 0.2, 0.3], 'Content 1'),
                createTestEntry([0.4, 0.5, 0.6], 'Content 2'),
            ];

            $this->vectorstore->insertMany($entries);

            $newVectorstore = new File($this->testPath);
            expect($newVectorstore->itemCount())->toBe(2);
        });
    });

    describe('similaritySearch()', function () {
        it('returns empty array for empty store', function () {
            $query = new EmbeddingVector([0.1, 0.2, 0.3]);

            $results = $this->vectorstore->similaritySearch($query);

            expect($results)->toBe([]);
        });

        it('returns single matching entry', function () {
            $entry = createTestEntry([0.5, 0.5, 0.5], 'Single content');
            $this->vectorstore->insert($entry);

            $results = $this->vectorstore->similaritySearch(new EmbeddingVector([0.5, 0.5, 0.5]));

            expect($results)->toHaveCount(1);
            expect($results[0])->toBeInstanceOf(VectorStoreEntry::class);
            expect($results[0]->document->content())->toBe('Single content');
        });

        it('returns results with similarity scores', function () {
            $entry = createTestEntry([1.0, 0.0, 0.0], 'Test content');
            $this->vectorstore->insert($entry);

            $results = $this->vectorstore->similaritySearch(new EmbeddingVector([1.0, 0.0, 0.0]));

            expect($results[0]->score)->toEqualWithDelta(1.0, 0.0001);
        });

        it('respects count parameter', function () {
            for ($i = 0; $i < 10; $i++) {
                $this->vectorstore->insert(createTestEntry([0.1 * ($i + 1), 0.2, 0.3], "Content {$i}"));
            }

            $results = $this->vectorstore->similaritySearch(new EmbeddingVector([0.5, 0.2, 0.3]), 3);

            expect($results)->toHaveCount(3);
        });

        it('returns all items when count exceeds store size', function () {
            $this->vectorstore->insert(createTestEntry([0.1, 0.2, 0.3], 'Content 1'));
            $this->vectorstore->insert(createTestEntry([0.4, 0.5, 0.6], 'Content 2'));

            $results = $this->vectorstore->similaritySearch(new EmbeddingVector([0.1, 0.2, 0.3]), 10);

            expect($results)->toHaveCount(2);
        });

        it('sorts results by similarity descending', function () {
            $this->vectorstore->insert(createTestEntry([1.0, 0.0, 0.0], 'Most similar'));
            $this->vectorstore->insert(createTestEntry([0.0, 1.0, 0.0], 'Orthogonal'));
            $this->vectorstore->insert(createTestEntry([0.7, 0.3, 0.0], 'Somewhat similar'));

            $results = $this->vectorstore->similaritySearch(new EmbeddingVector([1.0, 0.0, 0.0]), 3);

            expect($results[0]->document->content())->toBe('Most similar');
            expect($results[0]->score)->toEqualWithDelta(1.0, 0.0001);
            expect($results[1]->score)->toBeGreaterThan($results[2]->score);
        });

        it('preserves document metadata', function () {
            $entry = createTestEntry([0.1, 0.2, 0.3], 'Content', [
                '_mindwave_doc_source_id' => 'source-abc',
                '_mindwave_doc_source_type' => 'file',
                '_mindwave_doc_chunk_index' => 2,
                'custom_key' => 'custom_value',
            ]);
            $this->vectorstore->insert($entry);

            $results = $this->vectorstore->similaritySearch(new EmbeddingVector([0.1, 0.2, 0.3]));

            $meta = $results[0]->document->metadata();
            expect($meta['_mindwave_doc_source_id'])->toBe('source-abc');
            expect($meta['_mindwave_doc_source_type'])->toBe('file');
            expect($meta['_mindwave_doc_chunk_index'])->toBe(2);
            expect($meta['custom_key'])->toBe('custom_value');
        });

        it('works after loading from file', function () {
            $this->vectorstore->insert(createTestEntry([1.0, 0.0, 0.0], 'Persisted content'));

            $newVectorstore = new File($this->testPath);
            $results = $newVectorstore->similaritySearch(new EmbeddingVector([1.0, 0.0, 0.0]), 1);

            expect($results[0]->document->content())->toBe('Persisted content');
        });
    });

    describe('truncate()', function () {
        it('removes all items', function () {
            $this->vectorstore->insert(createTestEntry([0.1, 0.2, 0.3], 'Content 1'));
            $this->vectorstore->insert(createTestEntry([0.4, 0.5, 0.6], 'Content 2'));

            $this->vectorstore->truncate();

            expect($this->vectorstore->itemCount())->toBe(0);
        });

        it('persists truncation to file', function () {
            $this->vectorstore->insert(createTestEntry([0.1, 0.2, 0.3], 'Content'));
            $this->vectorstore->truncate();

            $newVectorstore = new File($this->testPath);
            expect($newVectorstore->itemCount())->toBe(0);
        });

        it('allows inserting after truncate', function () {
            $this->vectorstore->insert(createTestEntry([0.1, 0.2, 0.3], 'Content'));
            $this->vectorstore->truncate();
            $this->vectorstore->insert(createTestEntry([0.4, 0.5, 0.6], 'New content'));

            expect($this->vectorstore->itemCount())->toBe(1);
        });
    });

    describe('itemCount()', function () {
        it('tracks insertions', function () {
            expect($this->vectorstore->itemCount())->toBe(0);

            $this->vectorstore->insert(createTestEntry([0.1, 0.2, 0.3], 'Content 1'));
            expect($this->vectorstore->itemCount())->toBe(1);

            $this->vectorstore->insert(createTestEntry([0.4, 0.5, 0.6], 'Content 2'));
            expect($this->vectorstore->itemCount())->toBe(2);
        });

        it('persists count across instances', function () {
            $this->vectorstore->insert(createTestEntry([0.1, 0.2, 0.3], 'Content 1'));
            $this->vectorstore->insert(createTestEntry([0.4, 0.5, 0.6], 'Content 2'));

            $newVectorstore = new File($this->testPath);
            expect($newVectorstore->itemCount())->toBe(2);
        });
    });
});
