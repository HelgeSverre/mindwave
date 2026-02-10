<?php

use Mindwave\Mindwave\Exceptions\StreamingException;
use Mindwave\Mindwave\LLM\Streaming\StreamRetryHandler;

it('yields all chunks from a successful stream', function () {
    $handler = new StreamRetryHandler(maxRetries: 3);

    $stream = $handler->wrap(function () {
        yield 'Hello';
        yield ' ';
        yield 'World';
    });

    expect(iterator_to_array($stream))->toBe(['Hello', ' ', 'World']);
});

it('retries on retryable StreamingException', function () {
    $handler = new StreamRetryHandler(maxRetries: 3, initialDelay: 0);

    $attempts = 0;
    $stream = $handler->wrap(function () use (&$attempts) {
        $attempts++;
        if ($attempts < 3) {
            throw StreamingException::connectionFailed('openai', 'gpt-4');
        }
        yield 'Success';
    });

    expect(iterator_to_array($stream))->toBe(['Success']);
    expect($attempts)->toBe(3);
});

it('does not retry non-retryable StreamingException', function () {
    $handler = new StreamRetryHandler(maxRetries: 3, initialDelay: 0);

    $attempts = 0;
    $stream = $handler->wrap(function () use (&$attempts) {
        $attempts++;
        throw StreamingException::invalidData('openai', 'gpt-4');
    });

    expect(fn () => iterator_to_array($stream))
        ->toThrow(StreamingException::class);
    expect($attempts)->toBe(1);
});

it('does not retry generic exceptions by default', function () {
    $handler = new StreamRetryHandler(maxRetries: 3, initialDelay: 0);

    $attempts = 0;
    $stream = $handler->wrap(function () use (&$attempts) {
        $attempts++;
        throw new \RuntimeException('Something broke');
    });

    expect(fn () => iterator_to_array($stream))
        ->toThrow(\RuntimeException::class);
    expect($attempts)->toBe(1);
});

it('throws after exhausting max retries', function () {
    $handler = new StreamRetryHandler(maxRetries: 2, initialDelay: 0);

    $attempts = 0;
    $stream = $handler->wrap(function () use (&$attempts) {
        $attempts++;
        throw StreamingException::connectionFailed('openai', 'gpt-4');
    });

    expect(fn () => iterator_to_array($stream))
        ->toThrow(StreamingException::class);
    expect($attempts)->toBe(3); // initial + 2 retries
});

it('uses custom retry predicate', function () {
    $handler = new StreamRetryHandler(maxRetries: 3, initialDelay: 0);
    $handler->setRetryPredicate(fn (\Throwable $e, int $attempt) => $e instanceof \RuntimeException);

    $attempts = 0;
    $stream = $handler->wrap(function () use (&$attempts) {
        $attempts++;
        if ($attempts < 3) {
            throw new \RuntimeException('Retryable');
        }
        yield 'Done';
    });

    expect(iterator_to_array($stream))->toBe(['Done']);
    expect($attempts)->toBe(3);
});

it('custom predicate can reject retries', function () {
    $handler = new StreamRetryHandler(maxRetries: 3, initialDelay: 0);
    $handler->setRetryPredicate(fn () => false);

    $attempts = 0;
    $stream = $handler->wrap(function () use (&$attempts) {
        $attempts++;
        throw StreamingException::connectionFailed('openai', 'gpt-4');
    });

    expect(fn () => iterator_to_array($stream))
        ->toThrow(StreamingException::class);
    expect($attempts)->toBe(1);
});

it('invokes onRetry callback before each retry', function () {
    $handler = new StreamRetryHandler(maxRetries: 3, initialDelay: 0);

    $retryLog = [];
    $handler->onRetry(function (\Throwable $e, int $attempt) use (&$retryLog) {
        $retryLog[] = ['attempt' => $attempt, 'message' => $e->getMessage()];
    });

    $attempts = 0;
    $stream = $handler->wrap(function () use (&$attempts) {
        $attempts++;
        if ($attempts <= 2) {
            throw StreamingException::connectionFailed('openai', 'gpt-4');
        }
        yield 'OK';
    });

    iterator_to_array($stream);

    expect($retryLog)->toHaveCount(2);
    expect($retryLog[0]['attempt'])->toBe(1);
    expect($retryLog[1]['attempt'])->toBe(2);
});

it('does not retry when maxRetries is zero', function () {
    $handler = new StreamRetryHandler(maxRetries: 0);

    $attempts = 0;
    $stream = $handler->wrap(function () use (&$attempts) {
        $attempts++;
        throw StreamingException::connectionFailed('openai', 'gpt-4');
    });

    expect(fn () => iterator_to_array($stream))
        ->toThrow(StreamingException::class);
    expect($attempts)->toBe(1);
});

it('clamps negative constructor values', function () {
    $handler = new StreamRetryHandler(
        maxRetries: -1,
        initialDelay: -100,
        backoffMultiplier: 0.5,
    );

    expect($handler->getMaxRetries())->toBe(0);
    expect($handler->getInitialDelay())->toBe(0);
    expect($handler->getBackoffMultiplier())->toBe(1.0);
});

it('exposes configuration getters', function () {
    $handler = new StreamRetryHandler(
        maxRetries: 5,
        initialDelay: 500,
        maxDelay: 10000,
        backoffMultiplier: 3.0,
    );

    expect($handler->getMaxRetries())->toBe(5);
    expect($handler->getInitialDelay())->toBe(500);
    expect($handler->getMaxDelay())->toBe(10000);
    expect($handler->getBackoffMultiplier())->toBe(3.0);
});

it('returns fluent interface from setters', function () {
    $handler = new StreamRetryHandler;

    $result1 = $handler->setRetryPredicate(fn () => true);
    $result2 = $handler->onRetry(fn () => null);

    expect($result1)->toBe($handler);
    expect($result2)->toBe($handler);
});

it('handles stream that fails mid-way through', function () {
    $handler = new StreamRetryHandler(maxRetries: 2, initialDelay: 0);

    $attempts = 0;
    $stream = $handler->wrap(function () use (&$attempts) {
        $attempts++;
        yield 'chunk1';
        if ($attempts < 3) {
            throw StreamingException::interrupted('openai', 'gpt-4');
        }
        yield 'chunk2';
    });

    // When a stream fails mid-way and retries, the new stream starts from the beginning
    $chunks = iterator_to_array($stream);

    expect($chunks)->toBe(['chunk1', 'chunk1', 'chunk1', 'chunk2']);
    expect($attempts)->toBe(3);
});
