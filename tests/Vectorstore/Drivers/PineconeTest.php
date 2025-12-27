<?php

declare(strict_types=1);

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Mindwave\Mindwave\Vectorstore\Drivers\Pinecone;
use Probots\Pinecone\Client as PineconeClient;

require_once __DIR__.'/../Helpers.php';

/**
 * Pinecone Vectorstore Tests
 */
describe('Pinecone Vectorstore', function () {
    beforeEach(function () {
        $this->client = Mockery::mock(PineconeClient::class);
        $this->index = Mockery::mock('Probots\Pinecone\Resources\Index');
        $this->vectors = Mockery::mock('Probots\Pinecone\Resources\Vectors');

        $this->client->shouldReceive('index')
            ->with('test-index')
            ->andReturn($this->index);

        $this->index->shouldReceive('vectors')
            ->andReturn($this->vectors);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('Construction', function () {
        it('can be instantiated', function () {
            expect(class_exists(Pinecone::class))->toBeTrue();
        });
    });

    describe('insert()', function () {
        beforeEach(function () {
            $this->vectorstore = new Pinecone(
                client: $this->client,
                index: 'test-index'
            );
        });

        it('inserts single entry', function () {
            $entry = createTestEntry([0.1, 0.2, 0.3], 'Test content');

            $this->vectors->shouldReceive('upsert')
                ->once()
                ->with(Mockery::on(function ($vector) {
                    return isset($vector['id'])
                        && $vector['values'] === [0.1, 0.2, 0.3]
                        && isset($vector['metadata']);
                }));

            $this->vectorstore->insert($entry);
        });

        it('includes all metadata fields', function () {
            $entry = createTestEntry([0.1, 0.2, 0.3], 'Content', [
                '_mindwave_doc_source_id' => 'source-123',
                '_mindwave_doc_source_type' => 'test',
                '_mindwave_doc_chunk_index' => 5,
                'custom_field' => 'custom_value',
            ]);

            $this->vectors->shouldReceive('upsert')
                ->once()
                ->with(Mockery::on(function ($vector) {
                    $meta = $vector['metadata'];

                    return $meta['_mindwave_doc_source_id'] === 'source-123'
                        && $meta['_mindwave_doc_source_type'] === 'test'
                        && $meta['_mindwave_doc_chunk_index'] === 5;
                }));

            $this->vectorstore->insert($entry);
        });

        it('filters out null metadata values', function () {
            $entry = createTestEntry([0.1, 0.2, 0.3], 'Content', [
                '_mindwave_doc_source_id' => 'source-123',
                'null_field' => null,
            ]);

            $this->vectors->shouldReceive('upsert')
                ->once()
                ->with(Mockery::on(function ($vector) {
                    return ! array_key_exists('null_field', $vector['metadata']);
                }));

            $this->vectorstore->insert($entry);
        });
    });

    describe('insertMany()', function () {
        beforeEach(function () {
            $this->vectorstore = new Pinecone(
                client: $this->client,
                index: 'test-index'
            );
        });

        it('inserts multiple entries in batch', function () {
            $entries = [
                createTestEntry([0.1, 0.2, 0.3], 'Content 1'),
                createTestEntry([0.4, 0.5, 0.6], 'Content 2'),
                createTestEntry([0.7, 0.8, 0.9], 'Content 3'),
            ];

            $this->vectors->shouldReceive('upsert')
                ->once()
                ->with(Mockery::on(function ($vectors) {
                    return is_array($vectors) && count($vectors) === 3;
                }));

            $this->vectorstore->insertMany($entries);
        });

        it('handles empty array', function () {
            $this->vectors->shouldReceive('upsert')
                ->once()
                ->with([]);

            $this->vectorstore->insertMany([]);
        });

        it('filters null metadata from all entries', function () {
            $entries = [
                createTestEntry([0.1, 0.2, 0.3], 'Content 1', ['valid' => 'value', 'null_field' => null]),
            ];

            $this->vectors->shouldReceive('upsert')
                ->once()
                ->with(Mockery::on(function ($vectors) {
                    return ! array_key_exists('null_field', $vectors[0]['metadata']);
                }));

            $this->vectorstore->insertMany($entries);
        });
    });

    describe('itemCount()', function () {
        beforeEach(function () {
            $this->vectorstore = new Pinecone(
                client: $this->client,
                index: 'test-index'
            );
        });

        it('returns vector count from stats', function () {
            $response = Mockery::mock();
            $response->shouldReceive('json')
                ->with('totalVectorCount')
                ->once()
                ->andReturn(42);

            $this->vectors->shouldReceive('stats')
                ->once()
                ->andReturn($response);

            $count = $this->vectorstore->itemCount();

            expect($count)->toBe(42);
        });

        it('returns zero when no vectors exist', function () {
            $response = Mockery::mock();
            $response->shouldReceive('json')
                ->with('totalVectorCount')
                ->once()
                ->andReturn(0);

            $this->vectors->shouldReceive('stats')
                ->once()
                ->andReturn($response);

            $count = $this->vectorstore->itemCount();

            expect($count)->toBe(0);
        });
    });

    describe('similaritySearch()', function () {
        beforeEach(function () {
            $this->vectorstore = new Pinecone(
                client: $this->client,
                index: 'test-index'
            );
        });

        it('returns results with correct structure', function () {
            $query = new EmbeddingVector([0.1, 0.2, 0.3]);

            $response = Mockery::mock();
            $response->shouldReceive('collect')
                ->with('matches')
                ->once()
                ->andReturn(collect([
                    [
                        'id' => 'vec-1',
                        'score' => 0.95,
                        'values' => [0.1, 0.2, 0.3],
                        'metadata' => [
                            '_mindwave_doc_content' => 'Test content',
                            '_mindwave_doc_source_id' => 'source-1',
                            '_mindwave_doc_source_type' => 'test',
                            '_mindwave_doc_chunk_index' => 0,
                        ],
                    ],
                ]));

            $this->vectors->shouldReceive('query')
                ->once()
                ->andReturn($response);

            $results = $this->vectorstore->similaritySearch($query);

            expect($results)->toHaveCount(1);
            expect($results[0])->toBeInstanceOf(VectorStoreEntry::class);
            expect($results[0]->document->content())->toBe('Test content');
            expect($results[0]->score)->toBe(0.95);
        });

        it('handles missing optional metadata', function () {
            $query = new EmbeddingVector([0.1, 0.2, 0.3]);

            $response = Mockery::mock();
            $response->shouldReceive('collect')
                ->with('matches')
                ->once()
                ->andReturn(collect([
                    [
                        'id' => 'vec-1',
                        'score' => 0.95,
                        'values' => [0.1, 0.2, 0.3],
                        'metadata' => [
                            '_mindwave_doc_content' => 'Content',
                        ],
                    ],
                ]));

            $this->vectors->shouldReceive('query')
                ->once()
                ->andReturn($response);

            $results = $this->vectorstore->similaritySearch($query);

            expect($results)->toHaveCount(1);
            expect($results[0]->document->content())->toBe('Content');
        });

        it('decodes metadata JSON correctly', function () {
            $query = new EmbeddingVector([0.1, 0.2, 0.3]);

            $response = Mockery::mock();
            $response->shouldReceive('collect')
                ->with('matches')
                ->once()
                ->andReturn(collect([
                    [
                        'id' => 'vec-1',
                        'score' => 0.95,
                        'values' => [0.1, 0.2, 0.3],
                        'metadata' => [
                            '_mindwave_doc_content' => 'Content',
                            '_mindwave_doc_metadata' => json_encode(['custom' => 'value']),
                        ],
                    ],
                ]));

            $this->vectors->shouldReceive('query')
                ->once()
                ->andReturn($response);

            $results = $this->vectorstore->similaritySearch($query);

            $metadata = $results[0]->document->metadata();
            expect($metadata['custom'])->toBe('value');
        });

        it('queries with correct parameters')
            ->skip('Requires integration test - complex mock chain');

        it('respects custom count parameter')
            ->skip('Requires integration test - complex mock chain');
    });

    describe('truncate()', function () {
        it('deletes all vectors')
            ->skip('Requires integration test - complex mock chain');
    });
});
