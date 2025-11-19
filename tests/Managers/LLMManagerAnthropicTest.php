<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\LLM\Drivers\Anthropic\AnthropicDriver;
use Mindwave\Mindwave\LLM\Drivers\Anthropic\ModelNames;
use Mindwave\Mindwave\LLM\LLMManager;

it('creates the Anthropic driver', function () {
    Config::shouldReceive('get')
        ->with('database.default')
        ->andReturn('testing');

    Config::shouldReceive('get')
        ->with('database.connections.testing')
        ->andReturn(['driver' => 'sqlite', 'database' => ':memory:']);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.anthropic.api_key')
        ->andReturn('test-anthropic-api-key');

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.anthropic.model')
        ->andReturn(ModelNames::CLAUDE_3_5_SONNET);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.anthropic.system_message')
        ->andReturn(null);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.anthropic.max_tokens')
        ->andReturn(4096);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.anthropic.temperature')
        ->andReturn(1.0);

    $manager = new LLMManager($this->app);
    $driver = $manager->createAnthropicDriver();

    expect($driver)->toBeInstanceOf(AnthropicDriver::class);
});

it('uses correct default model for Anthropic', function () {
    Config::shouldReceive('get')
        ->with('database.default')
        ->andReturn('testing');

    Config::shouldReceive('get')
        ->with('database.connections.testing')
        ->andReturn(['driver' => 'sqlite', 'database' => ':memory:']);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.anthropic.api_key')
        ->andReturn('test-key');

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.anthropic.model')
        ->andReturn(ModelNames::CLAUDE_3_5_SONNET);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.anthropic.system_message')
        ->andReturn(null);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.anthropic.max_tokens')
        ->andReturn(4096);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.anthropic.temperature')
        ->andReturn(1.0);

    $manager = new LLMManager($this->app);
    $driver = $manager->createAnthropicDriver();

    // Access the model via reflection since it's protected
    $reflection = new ReflectionClass($driver);
    $modelProperty = $reflection->getProperty('model');
    $modelProperty->setAccessible(true);

    expect($modelProperty->getValue($driver))->toBe(ModelNames::CLAUDE_3_5_SONNET);
});
