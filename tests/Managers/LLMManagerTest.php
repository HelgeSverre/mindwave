<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\LLM\Drivers\Fake;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\Model;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\OpenAI;
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

it('creates the OpenAI driver', function () {
    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai.api_key')
        ->andReturn('your_openai_api_key');

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai.org_id')
        ->andReturn('your_openai_org_id');

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai.model')
        ->andReturn(Model::turbo16k);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai.max_tokens')
        ->andReturn(100);

    Config::shouldReceive('get')
        ->with('mindwave-llm.llms.openai.temperature')
        ->andReturn(0.8);

    $manager = new LLMManager($this->app);
    $driver = $manager->createOpenAIDriver();

    expect($driver)->toBeInstanceOf(OpenAI::class);
});
