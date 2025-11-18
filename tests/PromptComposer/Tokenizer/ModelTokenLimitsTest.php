<?php

use Mindwave\Mindwave\PromptComposer\Tokenizer\ModelTokenLimits;

it('returns correct limit for GPT-4', function () {
    expect(ModelTokenLimits::getContextWindow('gpt-4'))->toBe(8_192);
});

it('returns correct limit for GPT-4 Turbo', function () {
    expect(ModelTokenLimits::getContextWindow('gpt-4-turbo'))->toBe(128_000);
});

it('returns correct limit for GPT-4o', function () {
    expect(ModelTokenLimits::getContextWindow('gpt-4o'))->toBe(128_000);
});

it('returns correct limit for GPT-3.5 Turbo', function () {
    expect(ModelTokenLimits::getContextWindow('gpt-3.5-turbo'))->toBe(16_385);
});

it('returns correct limit for Claude 3.5 Sonnet', function () {
    expect(ModelTokenLimits::getContextWindow('claude-3-5-sonnet'))->toBe(200_000);
});

it('returns correct limit for Claude 3 Opus', function () {
    expect(ModelTokenLimits::getContextWindow('claude-3-opus'))->toBe(200_000);
});

it('returns correct limit for Mistral Large', function () {
    expect(ModelTokenLimits::getContextWindow('mistral-large'))->toBe(128_000);
});

it('returns correct limit for Gemini 1.5 Pro', function () {
    expect(ModelTokenLimits::getContextWindow('gemini-1.5-pro'))->toBe(2_000_000);
});

it('returns default limit for unknown models', function () {
    expect(ModelTokenLimits::getContextWindow('unknown-model'))->toBe(4_096);
});

it('returns correct encoding for GPT-4', function () {
    expect(ModelTokenLimits::getEncoding('gpt-4'))->toBe('cl100k_base');
});

it('returns correct encoding for GPT-3.5', function () {
    expect(ModelTokenLimits::getEncoding('gpt-3.5-turbo'))->toBe('cl100k_base');
});

it('returns correct encoding for O1 models', function () {
    expect(ModelTokenLimits::getEncoding('o1-preview'))->toBe('o200k_base');
    expect(ModelTokenLimits::getEncoding('o1-mini'))->toBe('o200k_base');
});

it('returns default encoding for unknown models', function () {
    expect(ModelTokenLimits::getEncoding('unknown-model'))->toBe('cl100k_base');
});

it('returns all supported models', function () {
    $models = ModelTokenLimits::all();

    expect($models)->toBeArray();
    expect($models)->not()->toBeEmpty();
    expect($models)->toHaveKey('gpt-4');
    expect($models)->toHaveKey('claude-3-opus');
    expect($models)->toHaveKey('mistral-large');
});

it('all models have positive token limits', function () {
    $models = ModelTokenLimits::all();

    foreach ($models as $model => $limit) {
        expect($limit)->toBeGreaterThan(0);
        expect($limit)->toBeInt();
    }
});

it('handles model name variations', function () {
    // Should match partial model names
    expect(ModelTokenLimits::getContextWindow('gpt-4-0613'))->toBe(8_192);
    expect(ModelTokenLimits::getContextWindow('gpt-4-turbo-preview'))->toBe(128_000);
    expect(ModelTokenLimits::getContextWindow('claude-3-opus-20240229'))->toBe(200_000);
});

it('largest context window is Gemini Pro', function () {
    $models = ModelTokenLimits::all();
    $max = max($models);

    expect($max)->toBe(2_000_000); // Gemini 1.5 Pro
});
