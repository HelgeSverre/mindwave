<?php

declare(strict_types=1);

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Mindwave\Mindwave\Vectorstore\Drivers\InMemory;

require_once __DIR__.'/../Helpers.php';

describe('InMemory Vectorstore', function () {
    beforeEach(function () {
        $this->vectorstore = new InMemory;
    });

    describe('Construction', function () {
        it('starts with zero items', function () {
            expect($this->vectorstore->itemCount())->toBe(0);
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
    });

    describe('upsertVector()', function () {
        it('inserts entry via upsert', function () {
            $entry = createTestEntry([0.1, 0.2, 0.3], 'Test content');

            $this->vectorstore->upsertVector($entry);

            expect($this->vectorstore->itemCount())->toBe(1);
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

            expect($results[0]->score)->toBe(1.0);
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
            // Insert entries with known similarity relationships
            $this->vectorstore->insert(createTestEntry([1.0, 0.0, 0.0], 'Most similar'));
            $this->vectorstore->insert(createTestEntry([0.0, 1.0, 0.0], 'Orthogonal'));
            $this->vectorstore->insert(createTestEntry([0.5, 0.5, 0.0], 'Somewhat similar'));

            $results = $this->vectorstore->similaritySearch(new EmbeddingVector([1.0, 0.0, 0.0]));

            expect($results[0]->document->content())->toBe('Most similar');
            expect($results[0]->score)->toBe(1.0);
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
    });

    describe('truncate()', function () {
        it('removes all items', function () {
            $this->vectorstore->insert(createTestEntry([0.1, 0.2, 0.3], 'Content 1'));
            $this->vectorstore->insert(createTestEntry([0.4, 0.5, 0.6], 'Content 2'));

            $this->vectorstore->truncate();

            expect($this->vectorstore->itemCount())->toBe(0);
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
    });
});
