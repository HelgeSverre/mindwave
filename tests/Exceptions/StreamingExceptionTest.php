<?php

use Mindwave\Mindwave\Exceptions\StreamingException;

it('creates connection failed exception', function () {
    $exception = StreamingException::connectionFailed('openai', 'gpt-4');

    expect($exception)
        ->toBeInstanceOf(StreamingException::class)
        ->and($exception->getMessage())->toContain('Failed to establish streaming connection')
        ->and($exception->getMessage())->toContain('openai')
        ->and($exception->getMessage())->toContain('gpt-4')
        ->and($exception->getProvider())->toBe('openai')
        ->and($exception->getModel())->toBe('gpt-4')
        ->and($exception->isRetryable())->toBeTrue();
});

it('creates timeout exception', function () {
    $exception = StreamingException::timeout('anthropic', 'claude-3', 30);

    expect($exception)
        ->toBeInstanceOf(StreamingException::class)
        ->and($exception->getMessage())->toContain('timeout')
        ->and($exception->getMessage())->toContain('30 seconds')
        ->and($exception->getMessage())->toContain('anthropic')
        ->and($exception->getProvider())->toBe('anthropic')
        ->and($exception->getModel())->toBe('claude-3')
        ->and($exception->isRetryable())->toBeTrue();
});

it('creates invalid data exception', function () {
    $exception = StreamingException::invalidData('mistral', 'mistral-medium', 'Malformed JSON');

    expect($exception)
        ->toBeInstanceOf(StreamingException::class)
        ->and($exception->getMessage())->toContain('Invalid streaming data')
        ->and($exception->getMessage())->toContain('Malformed JSON')
        ->and($exception->getProvider())->toBe('mistral')
        ->and($exception->getModel())->toBe('mistral-medium')
        ->and($exception->isRetryable())->toBeFalse(); // Invalid data is not retryable
});

it('creates interrupted exception', function () {
    $previous = new RuntimeException('Connection reset');
    $exception = StreamingException::interrupted('openai', 'gpt-4', $previous);

    expect($exception)
        ->toBeInstanceOf(StreamingException::class)
        ->and($exception->getMessage())->toContain('Stream interrupted')
        ->and($exception->getPrevious())->toBe($previous)
        ->and($exception->getProvider())->toBe('openai')
        ->and($exception->getModel())->toBe('gpt-4')
        ->and($exception->isRetryable())->toBeTrue();
});

it('allows custom retryable flag', function () {
    $exception = new StreamingException(
        message: 'Custom error',
        provider: 'test',
        model: 'test-model',
        retryable: true
    );

    expect($exception->isRetryable())->toBeTrue();

    $exception2 = new StreamingException(
        message: 'Custom error',
        provider: 'test',
        model: 'test-model',
        retryable: false
    );

    expect($exception2->isRetryable())->toBeFalse();
});

it('stores provider and model information', function () {
    $exception = new StreamingException(
        message: 'Test error',
        provider: 'openai',
        model: 'gpt-4'
    );

    expect($exception->getProvider())->toBe('openai')
        ->and($exception->getModel())->toBe('gpt-4');
});

it('handles null provider and model', function () {
    $exception = new StreamingException('Test error');

    expect($exception->getProvider())->toBeNull()
        ->and($exception->getModel())->toBeNull();
});
