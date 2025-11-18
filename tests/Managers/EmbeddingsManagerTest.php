<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Embeddings\Drivers\OpenAIEmbeddings;
use Mindwave\Mindwave\Embeddings\EmbeddingsManager;

it('returns the default driver', function () {
    Config::shouldReceive('get')
        ->with('database.default')
        ->andReturn('testing');

    Config::shouldReceive('get')
        ->with('database.connections.testing')
        ->andReturn(['driver' => 'sqlite', 'database' => ':memory:']);

    Config::shouldReceive('get')
        ->with('mindwave-embeddings.default')
        ->andReturn('openai');

    $manager = new EmbeddingsManager($this->app);

    expect($manager->getDefaultDriver())->toBe('openai');
});

it('creates the OpenAIEmbeddings driver', function () {
    Config::shouldReceive('get')
        ->with('database.default')
        ->andReturn('testing');

    Config::shouldReceive('get')
        ->with('database.connections.testing')
        ->andReturn(['driver' => 'sqlite', 'database' => ':memory:']);

    Config::shouldReceive('get')
        ->with('mindwave-embeddings.default')
        ->andReturn('openai');

    Config::shouldReceive('get')
        ->with('mindwave-embeddings.embeddings.openai.api_key')
        ->andReturn('your_openai_api_key');

    Config::shouldReceive('get')
        ->with('mindwave-embeddings.embeddings.openai.org_id')
        ->andReturn('your_openai_org_id');

    Config::shouldReceive('get')
        ->with('mindwave-embeddings.embeddings.openai.model')
        ->andReturn('your_openai_model');

    $manager = new EmbeddingsManager($this->app);
    $driver = $manager->createOpenaiDriver();

    expect($driver)->toBeInstanceOf(OpenAIEmbeddings::class);
    expect($driver)->toBeInstanceOf(Embeddings::class);
});
