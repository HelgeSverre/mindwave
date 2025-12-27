<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Vectorstore\Drivers\File;
use Mindwave\Mindwave\Vectorstore\Drivers\InMemory;
use Mindwave\Mindwave\Vectorstore\Drivers\Pinecone;
use Mindwave\Mindwave\Vectorstore\Drivers\Qdrant;
use Mindwave\Mindwave\Vectorstore\Drivers\Weaviate;
use Mindwave\Mindwave\Vectorstore\VectorstoreManager;

describe('VectorstoreManager', function () {
    beforeEach(function () {
        Config::shouldReceive('get')
            ->with('database.default')
            ->andReturn('testing');

        Config::shouldReceive('get')
            ->with('database.connections.testing')
            ->andReturn(['driver' => 'sqlite', 'database' => ':memory:']);
    });

    describe('getDefaultDriver()', function () {
        it('returns configured default driver', function () {
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.default')
                ->andReturn('file');

            $manager = new VectorstoreManager($this->app);

            expect($manager->getDefaultDriver())->toBe('file');
        });

        it('returns array as default when configured', function () {
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.default')
                ->andReturn('array');

            $manager = new VectorstoreManager($this->app);

            expect($manager->getDefaultDriver())->toBe('array');
        });
    });

    describe('createArrayDriver()', function () {
        it('creates InMemory driver', function () {
            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createArrayDriver();

            expect($driver)->toBeInstanceOf(InMemory::class);
            expect($driver)->toBeInstanceOf(Vectorstore::class);
        });

        it('creates new instance on each call', function () {
            $manager = new VectorstoreManager($this->app);
            $driver1 = $manager->createArrayDriver();
            $driver2 = $manager->createArrayDriver();

            expect($driver1)->not->toBe($driver2);
        });
    });

    describe('createFileDriver()', function () {
        it('creates File driver with configured path', function () {
            $testPath = sys_get_temp_dir().'/test-vectorstore.json';

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.file.path')
                ->andReturn($testPath);

            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createFileDriver();

            expect($driver)->toBeInstanceOf(File::class);
            expect($driver)->toBeInstanceOf(Vectorstore::class);

            // Cleanup
            if (file_exists($testPath)) {
                unlink($testPath);
            }
        });

        it('uses path from configuration', function () {
            $customPath = sys_get_temp_dir().'/custom-path.json';

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.file.path')
                ->andReturn($customPath);

            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createFileDriver();

            expect($driver)->toBeInstanceOf(File::class);

            // Cleanup
            if (file_exists($customPath)) {
                unlink($customPath);
            }
        });
    });

    describe('createPineconeDriver()', function () {
        it('creates Pinecone driver with correct configuration', function () {
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.pinecone.api_key')
                ->andReturn('test-api-key');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.pinecone.index_host')
                ->andReturn('test-host.pinecone.io');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.pinecone.index')
                ->andReturn('test-index');

            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createPineconeDriver();

            expect($driver)->toBeInstanceOf(Pinecone::class);
            expect($driver)->toBeInstanceOf(Vectorstore::class);
        });
    });

    describe('createQdrantDriver()', function () {
        it('creates Qdrant driver with correct configuration', function () {
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.api_key')
                ->andReturn('test-api-key');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.collection')
                ->andReturn('test-collection');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.host')
                ->andReturn('localhost');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.port')
                ->andReturn(6333);

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.dimensions', 1536)
                ->andReturn(1536);

            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createQdrantDriver();

            expect($driver)->toBeInstanceOf(Qdrant::class);
            expect($driver)->toBeInstanceOf(Vectorstore::class);
            expect($driver->getDimensions())->toBe(1536);
        });

        it('uses custom dimensions when configured', function () {
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.api_key')
                ->andReturn('test-api-key');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.collection')
                ->andReturn('test-collection');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.host')
                ->andReturn('localhost');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.port')
                ->andReturn(6333);

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.dimensions', 1536)
                ->andReturn(768);

            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createQdrantDriver();

            expect($driver->getDimensions())->toBe(768);
        });

        it('defaults to 1536 dimensions when not configured', function () {
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.api_key')
                ->andReturn('test-api-key');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.collection')
                ->andReturn('test-collection');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.host')
                ->andReturn('localhost');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.port')
                ->andReturn(6333);

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.qdrant.dimensions', 1536)
                ->andReturn(1536);

            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createQdrantDriver();

            expect($driver->getDimensions())->toBe(1536);
        });
    });

    describe('createWeaviateDriver()', function () {
        it('creates Weaviate driver with correct configuration', function () {
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.api_url')
                ->andReturn('http://localhost:8080/v1');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.api_token')
                ->andReturn('test-token');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.additional_headers', [])
                ->andReturn([]);

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.index')
                ->andReturn('TestClass');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.dimensions', 1536)
                ->andReturn(1536);

            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createWeaviateDriver();

            expect($driver)->toBeInstanceOf(Weaviate::class);
            expect($driver)->toBeInstanceOf(Vectorstore::class);
            expect($driver->getDimensions())->toBe(1536);
        });

        it('uses custom dimensions when configured', function () {
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.api_url')
                ->andReturn('http://localhost:8080/v1');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.api_token')
                ->andReturn('test-token');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.additional_headers', [])
                ->andReturn([]);

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.index')
                ->andReturn('TestClass');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.dimensions', 1536)
                ->andReturn(384);

            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createWeaviateDriver();

            expect($driver->getDimensions())->toBe(384);
        });

        it('supports additional headers', function () {
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.api_url')
                ->andReturn('http://localhost:8080/v1');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.api_token')
                ->andReturn('test-token');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.additional_headers', [])
                ->andReturn(['X-Custom-Header' => 'value']);

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.index')
                ->andReturn('TestClass');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.dimensions', 1536)
                ->andReturn(1536);

            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createWeaviateDriver();

            expect($driver)->toBeInstanceOf(Weaviate::class);
        });

        it('defaults to empty array for additional headers', function () {
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.api_url')
                ->andReturn('http://localhost:8080/v1');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.api_token')
                ->andReturn('test-token');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.additional_headers', [])
                ->andReturn([]);

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.index')
                ->andReturn('TestClass');

            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.weaviate.dimensions', 1536)
                ->andReturn(1536);

            $manager = new VectorstoreManager($this->app);
            $driver = $manager->createWeaviateDriver();

            expect($driver)->toBeInstanceOf(Weaviate::class);
        });
    });

    describe('Driver Factory Methods', function () {
        it('all drivers implement Vectorstore interface', function () {
            $manager = new VectorstoreManager($this->app);

            // Array driver
            $arrayDriver = $manager->createArrayDriver();
            expect($arrayDriver)->toBeInstanceOf(Vectorstore::class);

            // File driver
            Config::shouldReceive('get')
                ->with('mindwave-vectorstore.vectorstores.file.path')
                ->andReturn(sys_get_temp_dir().'/test.json');

            $fileDriver = $manager->createFileDriver();
            expect($fileDriver)->toBeInstanceOf(Vectorstore::class);

            // Cleanup
            $path = sys_get_temp_dir().'/test.json';
            if (file_exists($path)) {
                unlink($path);
            }
        });
    });
});
