<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\LLM\Drivers\Fake;
use Mindwave\Mindwave\LLM\Drivers\OpenAIChat;
use Mindwave\Mindwave\LLM\Drivers\OpenAICompletion;
use Mindwave\Mindwave\LLM\LLMManager;

it('returns the default driver', function () {
    Config::shouldReceive('get')
        ->with('mindwave-llm.default')
        ->andReturn('fake');

    $manager = new LLMManager($this->app);

    expect($manager->getDefaultDriver())->toBe('fake');
});

it('creates the Fake driver', function () {
    $manager = new LLMManager($this->app);
    $driver = $manager->createFakeDriver();

    expect($driver)->toBeInstanceOf(Fake::class);
});

it('creates the OpenAIChat driver', function () {
    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai_chat.api_key')
        ->andReturn('your_openai_chat_api_key');

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai_chat.org_id')
        ->andReturn('your_openai_chat_org_id');

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai_chat.model')
        ->andReturn('your_openai_chat_model');

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai_chat.max_tokens')
        ->andReturn(100);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai_chat.temperature')
        ->andReturn(0.8);

    $manager = new LLMManager($this->app);
    $driver = $manager->createOpenAIChatDriver();

    expect($driver)->toBeInstanceOf(OpenAIChat::class);
});

it('creates the OpenAICompletion driver', function () {
    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai_completion.api_key')
        ->andReturn('your_openai_completion_api_key');

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai_completion.org_id')
        ->andReturn('your_openai_completion_org_id');

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai_completion.model')
        ->andReturn('your_openai_completion_model');

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai_completion.max_tokens')
        ->andReturn(100);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai_completion.temperature')
        ->andReturn(0.8);

    $manager = new LLMManager($this->app);
    $driver = $manager->createOpenAICompletionDriver();

    expect($driver)->toBeInstanceOf(OpenAICompletion::class);
});
