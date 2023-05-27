<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Vectorstore\Drivers\InMemory;
use Mindwave\Mindwave\Vectorstore\Drivers\Pinecone;
use Mindwave\Mindwave\Vectorstore\Drivers\Weaviate;
use Mindwave\Mindwave\Vectorstore\VectorstoreManager;

it('returns the default driver', function () {
    Config::shouldReceive('get')
        ->with('mindwave-vectorstore.default')
        ->andReturn('array');

    $manager = new VectorstoreManager($this->app);

    expect($manager->getDefaultDriver())->toBe('array');
});

it('creates the Array driver', function () {
    $manager = new VectorstoreManager($this->app);
    $driver = $manager->createArrayDriver();

    expect($driver)->toBeInstanceOf(InMemory::class);
    expect($driver)->toBeInstanceOf(Vectorstore::class);
});

it('creates the Pinecone driver', function () {

    Config::shouldReceive('get')
        ->with('mindwave-vectorstore.vectorstores.pinecone.api_key')
        ->andReturn('your_pinecone_api_key');

    Config::shouldReceive('get')
        ->with('mindwave-vectorstore.vectorstores.pinecone.environment')
        ->andReturn('your_pinecone_environment');

    Config::shouldReceive('get')
        ->with('mindwave-vectorstore.vectorstores.pinecone.index')
        ->andReturn('dummy_items');

    $manager = new VectorstoreManager($this->app);

    $driver = $manager->createPineconeDriver();

    expect($driver)->toBeInstanceOf(Pinecone::class);
    expect($driver)->toBeInstanceOf(Vectorstore::class);
});

it('creates the Weaviate driver', function () {

    Config::shouldReceive('get')
        ->with('mindwave-vectorstore.vectorstores.weaviate.api_url')
        ->andReturn('your_weaviate_api_url');

    Config::shouldReceive('get')
        ->with('mindwave-vectorstore.vectorstores.weaviate.api_token')
        ->andReturn('your_weaviate_api_token');

    Config::shouldReceive('get')
        ->with('mindwave-vectorstore.vectorstores.weaviate.index')
        ->andReturn('dummy_index');

    Config::shouldReceive('get')
        ->with('mindwave-vectorstore.vectorstores.weaviate.additional_headers', [])
        ->andReturn([]);

    $manager = new VectorstoreManager($this->app);

    $driver = $manager->createWeaviateDriver();

    expect($driver)->toBeInstanceOf(Weaviate::class);
    expect($driver)->toBeInstanceOf(Vectorstore::class);
});
