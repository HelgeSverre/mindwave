<?php

use Mindwave\Mindwave\LLM\Streaming\StreamedTextResponse;

beforeEach(function () {
    $this->simpleStream = (function () {
        yield 'Hello';
        yield ' ';
        yield 'World';
    })();
});

it('converts stream to string', function () {
    $response = new StreamedTextResponse($this->simpleStream);

    expect($response->toString())->toBe('Hello World');
});

it('processes chunks with callback', function () {
    $chunks = [];

    $response = new StreamedTextResponse($this->simpleStream);
    $response->onChunk(function ($chunk) use (&$chunks) {
        $chunks[] = $chunk;
    });

    $response->toString();

    expect($chunks)->toBe(['Hello', ' ', 'World']);
});

it('supports cancellation', function () {
    $stream = (function () {
        yield 'Hello';
        yield ' ';
        yield 'World';
        yield '!';
    })();

    $response = new StreamedTextResponse($stream);
    $chunks = [];

    $iterator = $response->getIterator();
    foreach ($iterator as $chunk) {
        $chunks[] = $chunk;
        if ($chunk === ' ') {
            $response->cancel();
        }
    }

    expect($response->isCancelled())->toBeTrue();
});

it('handles errors with custom handler', function () {
    $stream = (function () {
        yield 'Hello';
        throw new \RuntimeException('Stream error');
    })();

    $response = new StreamedTextResponse($stream);
    $errorHandled = false;

    $response->onError(function ($error) use (&$errorHandled) {
        $errorHandled = true;
        expect($error)->toBeInstanceOf(\RuntimeException::class);
        expect($error->getMessage())->toBe('Stream error');

        return false; // Don't retry
    });

    try {
        $response->toString();
    } catch (\RuntimeException $e) {
        // Expected
    }

    expect($errorHandled)->toBeFalse(); // Error handler is only called in StreamedResponse context
});

it('supports completion callback', function () {
    $response = new StreamedTextResponse($this->simpleStream);
    $completed = false;

    $response->onComplete(function () use (&$completed) {
        $completed = true;
    });

    // Completion handler is only called in StreamedResponse context
    $response->toString();

    expect($completed)->toBeFalse(); // Not called outside StreamedResponse
});

it('sets max retries', function () {
    $response = new StreamedTextResponse($this->simpleStream);

    $response->withRetries(5);

    expect($response)->toBeInstanceOf(StreamedTextResponse::class);
});

it('returns raw generator', function () {
    $response = new StreamedTextResponse($this->simpleStream);

    $generator = $response->getIterator();

    expect($generator)->toBeInstanceOf(\Generator::class);

    $chunks = iterator_to_array($generator);
    expect($chunks)->toBe(['Hello', ' ', 'World']);
});

it('creates streamed response with SSE format', function () {
    $response = new StreamedTextResponse($this->simpleStream);

    $streamedResponse = $response->toStreamedResponse();

    expect($streamedResponse)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
    expect($streamedResponse->headers->get('Content-Type'))->toBe('text/event-stream');
    expect($streamedResponse->headers->get('Cache-Control'))->toContain('no-cache');
    expect($streamedResponse->headers->get('Connection'))->toBe('keep-alive');
});

it('creates plain streamed response', function () {
    $response = new StreamedTextResponse($this->simpleStream);

    $streamedResponse = $response->toPlainStreamedResponse();

    expect($streamedResponse)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
    expect($streamedResponse->headers->get('Content-Type'))->toBe('text/plain; charset=utf-8');
});

it('supports custom headers in streamed response', function () {
    $response = new StreamedTextResponse($this->simpleStream);

    $streamedResponse = $response->toStreamedResponse(200, [
        'X-Custom-Header' => 'custom-value',
    ]);

    expect($streamedResponse->headers->get('X-Custom-Header'))->toBe('custom-value');
});
