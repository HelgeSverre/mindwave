<?php

use Mindwave\Mindwave\Facades\Mindwave;

it('returns correct context window for OpenAI GPT-5 model', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-5');

    expect($driver->maxContextTokens())->toBe(400_000);
});

it('returns correct context window for OpenAI GPT-5-mini model', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-5-mini');

    expect($driver->maxContextTokens())->toBe(400_000);
});

it('returns correct context window for OpenAI GPT-5-nano model', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-5-nano');

    expect($driver->maxContextTokens())->toBe(400_000);
});

it('returns correct context window for OpenAI GPT-4.1 model', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-4.1');

    expect($driver->maxContextTokens())->toBe(1_000_000);
});

it('returns correct context window for OpenAI GPT-4.1-mini model', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-4.1-mini');

    expect($driver->maxContextTokens())->toBe(1_000_000);
});

it('returns correct context window for OpenAI GPT-4.1-nano model', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-4.1-nano');

    expect($driver->maxContextTokens())->toBe(1_000_000);
});

it('returns correct context window for OpenAI GPT-4 Turbo model', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-4-turbo');

    expect($driver->maxContextTokens())->toBe(128_000);
});

it('returns correct context window for OpenAI GPT-4o model', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-4o');

    expect($driver->maxContextTokens())->toBe(128_000);
});

it('returns correct context window for OpenAI GPT-4 model', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-4');

    expect($driver->maxContextTokens())->toBe(8_192);
});

it('returns correct context window for OpenAI GPT-3.5 Turbo model', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-3.5-turbo');

    expect($driver->maxContextTokens())->toBe(16_385);
});

it('returns correct context window for Mistral large model', function () {
    $driver = Mindwave::llm('mistral')
        ->model('mistral-large');

    expect($driver->maxContextTokens())->toBe(128_000);
});

it('returns correct context window for Mistral medium model', function () {
    $driver = Mindwave::llm('mistral')
        ->model('mistral-medium');

    expect($driver->maxContextTokens())->toBe(32_000);
});

it('returns fallback context window for unknown model', function () {
    $driver = Mindwave::llm('openai')
        ->model('unknown-model-xyz');

    expect($driver->maxContextTokens())->toBe(4_096);
});
