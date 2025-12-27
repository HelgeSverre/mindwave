<?php

declare(strict_types=1);

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Mindwave\Mindwave\Vectorstore\Drivers\Weaviate;
use Weaviate\Collections\ClassCollection;
use Weaviate\Model\ClassModel;
use Weaviate\Weaviate as WeaviateClient;

require_once __DIR__.'/../Helpers.php';

/**
 * Helper to create a fully mocked Weaviate client with class already existing
 */
function createWeaviateMocks(): array
{
    $client = Mockery::mock(WeaviateClient::class);
    $schema = Mockery::mock('Weaviate\Api\Schema');
    $dataObject = Mockery::mock('Weaviate\Api\DataObject');
    $batch = Mockery::mock('Weaviate\Api\Batch');
    $graphql = Mockery::mock('Weaviate\Api\GraphQL');

    $client->shouldReceive('schema')->andReturn($schema);
    $client->shouldReceive('dataObject')->andReturn($dataObject);
    $client->shouldReceive('batch')->andReturn($batch);
    $client->shouldReceive('graphql')->andReturn($graphql);

    // Setup default schema response - class exists
    $schemaModel = Mockery::mock('Weaviate\Model\SchemaModel');
    $classes = Mockery::mock(ClassCollection::class);
    $classModel = Mockery::mock(ClassModel::class);

    $schema->shouldReceive('get')->andReturn($schemaModel)->byDefault();
    $schemaModel->shouldReceive('getClasses')->andReturn($classes)->byDefault();
    $classes->shouldReceive('isEmpty')->andReturn(false)->byDefault();
    $classes->shouldReceive('first')->andReturn($classModel)->byDefault();
    $classModel->shouldReceive('getClass')->andReturn('TestClass')->byDefault();

    return [
        'client' => $client,
        'schema' => $schema,
        'dataObject' => $dataObject,
        'batch' => $batch,
        'graphql' => $graphql,
    ];
}

describe('Weaviate Vectorstore', function () {
    afterEach(function () {
        Mockery::close();
    });

    describe('Construction', function () {
        it('constructs with required parameters', function () {
            $mocks = createWeaviateMocks();

            $vectorstore = new Weaviate(
                client: $mocks['client'],
                className: 'TestClass',
                dimensions: 1536
            );

            expect($vectorstore)->toBeInstanceOf(Weaviate::class);
        });

        it('returns configured dimensions', function () {
            $mocks = createWeaviateMocks();

            $vectorstore = new Weaviate(
                client: $mocks['client'],
                className: 'TestClass',
                dimensions: 768
            );

            expect($vectorstore->getDimensions())->toBe(768);
        });

        it('creates schema class if none exist')
            ->skip('Complex mock setup with multiple schema().get() calls - requires integration test');

        it('throws exception if class cannot be created')
            ->skip('Complex mock setup with multiple schema().get() calls - requires integration test');
    });

    describe('insert()', function () {
        it('inserts single entry with correct dimensions', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $entry = createTestEntry([0.1, 0.2, 0.3], 'Test content');

            $mocks['dataObject']->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['class'] === 'TestClass'
                        && $data['vector'] === [0.1, 0.2, 0.3]
                        && $data['properties']['_mindwave_doc_content'] === 'Test content';
                }));

            $vectorstore->insert($entry);
        });

        it('throws exception when inserting vector with wrong dimensions', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $entry = createTestEntry([0.1, 0.2, 0.3, 0.4], 'Test content');

            expect(fn () => $vectorstore->insert($entry))
                ->toThrow(InvalidArgumentException::class, 'Expected vector dimension 3, got 4');
        });

        it('includes all metadata fields', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $entry = createTestEntry([0.1, 0.2, 0.3], 'Content', [
                '_mindwave_doc_source_id' => 'source-123',
                '_mindwave_doc_source_type' => 'test',
                '_mindwave_doc_chunk_index' => 5,
                'custom_field' => 'custom_value',
            ]);

            $mocks['dataObject']->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    $props = $data['properties'];

                    return $props['_mindwave_doc_source_id'] === 'source-123'
                        && $props['_mindwave_doc_source_type'] === 'test'
                        && $props['_mindwave_doc_chunk_index'] === 5;
                }));

            $vectorstore->insert($entry);
        });
    });

    describe('insertMany()', function () {
        it('inserts multiple entries in batch', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $entries = [
                createTestEntry([0.1, 0.2, 0.3], 'Content 1'),
                createTestEntry([0.4, 0.5, 0.6], 'Content 2'),
                createTestEntry([0.7, 0.8, 0.9], 'Content 3'),
            ];

            $mocks['batch']->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($objects) {
                    return count($objects) === 3;
                }));

            $vectorstore->insertMany($entries);
        });

        it('throws exception when any vector has wrong dimensions', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $entries = [
                createTestEntry([0.1, 0.2, 0.3], 'Content 1'),
                createTestEntry([0.4, 0.5, 0.6, 0.7], 'Content 2'),
            ];

            expect(fn () => $vectorstore->insertMany($entries))
                ->toThrow(InvalidArgumentException::class, 'Expected vector dimension 3, got 4 at index 1');
        });

        it('validates all vectors before inserting any', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $entries = [
                createTestEntry([0.1, 0.2, 0.3], 'Content 1'),
                createTestEntry([0.4, 0.5], 'Content 2'),
            ];

            $mocks['batch']->shouldReceive('create')->never();

            expect(fn () => $vectorstore->insertMany($entries))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('similaritySearch()', function () {
        it('performs search with default count', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $query = new EmbeddingVector([0.1, 0.2, 0.3]);

            $mocks['graphql']->shouldReceive('get')
                ->once()
                ->with(Mockery::on(function ($graphqlQuery) {
                    return str_contains($graphqlQuery, 'limit: 5')
                        && str_contains($graphqlQuery, 'nearVector');
                }))
                ->andReturn(['data' => ['Get' => ['TestClass' => []]]]);

            $results = $vectorstore->similaritySearch($query);

            expect($results)->toBe([]);
        });

        it('performs search with custom count', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $query = new EmbeddingVector([0.1, 0.2, 0.3]);

            $mocks['graphql']->shouldReceive('get')
                ->once()
                ->with(Mockery::on(function ($graphqlQuery) {
                    return str_contains($graphqlQuery, 'limit: 10');
                }))
                ->andReturn(['data' => ['Get' => ['TestClass' => []]]]);

            $results = $vectorstore->similaritySearch($query, 10);

            expect($results)->toBe([]);
        });

        it('returns results with correct structure', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $query = new EmbeddingVector([0.1, 0.2, 0.3]);

            $mocks['graphql']->shouldReceive('get')
                ->once()
                ->andReturn([
                    'data' => [
                        'Get' => [
                            'TestClass' => [
                                [
                                    '_additional' => [
                                        'vector' => [0.1, 0.2, 0.3],
                                        'score' => 0.95,
                                    ],
                                    '_mindwave_doc_content' => 'Test content',
                                    '_mindwave_doc_source_id' => 'source-1',
                                    '_mindwave_doc_source_type' => 'test',
                                    '_mindwave_doc_chunk_index' => 0,
                                    '_mindwave_doc_metadata' => json_encode(['custom' => 'value']),
                                ],
                            ],
                        ],
                    ],
                ]);

            $results = $vectorstore->similaritySearch($query);

            expect($results)->toHaveCount(1);
            expect($results[0])->toBeInstanceOf(VectorStoreEntry::class);
            expect($results[0]->document->content())->toBe('Test content');
            expect($results[0]->score)->toBe(0.95);
            expect($results[0]->vector->values)->toBe([0.1, 0.2, 0.3]);
        });

        it('decodes metadata JSON correctly', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $query = new EmbeddingVector([0.1, 0.2, 0.3]);

            $mocks['graphql']->shouldReceive('get')
                ->once()
                ->andReturn([
                    'data' => [
                        'Get' => [
                            'TestClass' => [
                                [
                                    '_additional' => [
                                        'vector' => [0.1, 0.2, 0.3],
                                        'score' => 0.95,
                                    ],
                                    '_mindwave_doc_content' => 'Content',
                                    '_mindwave_doc_source_id' => 'source-1',
                                    '_mindwave_doc_source_type' => 'test',
                                    '_mindwave_doc_chunk_index' => 0,
                                    '_mindwave_doc_metadata' => json_encode([
                                        'custom_field' => 'custom_value',
                                        'number' => 42,
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ]);

            $results = $vectorstore->similaritySearch($query);

            $metadata = $results[0]->document->metadata();
            expect($metadata['custom_field'])->toBe('custom_value');
            expect($metadata['number'])->toBe(42);
        });
    });

    describe('truncate()', function () {
        it('deletes and recreates class')
            ->skip('Complex mock setup with multiple schema().get() calls - requires integration test');
    });

    describe('itemCount()', function () {
        it('returns count from GraphQL aggregate query', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $mocks['graphql']->shouldReceive('get')
                ->once()
                ->with(Mockery::on(function ($query) {
                    return str_contains($query, 'Aggregate')
                        && str_contains($query, 'TestClass')
                        && str_contains($query, 'count');
                }))
                ->andReturn([
                    'data' => [
                        'Aggregate' => [
                            'TestClass' => [
                                ['meta' => ['count' => 42]],
                            ],
                        ],
                    ],
                ]);

            $count = $vectorstore->itemCount();

            expect($count)->toBe(42);
        });

        it('returns zero when collection is empty', function () {
            $mocks = createWeaviateMocks();
            $vectorstore = new Weaviate($mocks['client'], 'TestClass', 3);

            $mocks['graphql']->shouldReceive('get')
                ->once()
                ->andReturn([
                    'data' => [
                        'Aggregate' => [
                            'TestClass' => [
                                ['meta' => ['count' => 0]],
                            ],
                        ],
                    ],
                ]);

            $count = $vectorstore->itemCount();

            expect($count)->toBe(0);
        });
    });
});
