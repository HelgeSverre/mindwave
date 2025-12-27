<?php

use Mindwave\Mindwave\LLM\Responses\ChatResponse;
use Mindwave\Mindwave\LLM\Responses\StreamChunk;
use Mindwave\Mindwave\LLM\Responses\StreamedChatResponse;

beforeEach(function () {
    $this->simpleStream = (function () {
        yield new StreamChunk(content: 'Hello', role: 'assistant', model: 'gpt-4');
        yield new StreamChunk(content: ' ');
        yield new StreamChunk(content: 'World', finishReason: 'stop');
    })();
});

it('accumulates text from chunks', function () {
    $response = new StreamedChatResponse($this->simpleStream);

    $text = $response->getText();

    expect($text)->toBe('Hello World');
});

it('collects metadata from chunks', function () {
    $stream = (function () {
        yield new StreamChunk(content: 'Hello', role: 'assistant', model: 'gpt-4');
        yield new StreamChunk(content: ' ', inputTokens: 10);
        yield new StreamChunk(content: 'World', outputTokens: 5, finishReason: 'stop');
    })();

    $response = new StreamedChatResponse($stream);
    $metadata = $response->getMetadata();

    expect($metadata->content)->toBe('Hello World');
    expect($metadata->role)->toBe('assistant');
    expect($metadata->model)->toBe('gpt-4');
    expect($metadata->inputTokens)->toBe(10);
    expect($metadata->outputTokens)->toBe(5);
    expect($metadata->totalTokens)->toBe(15);
    expect($metadata->finishReason)->toBe('stop');
});

it('tracks tool calls', function () {
    $stream = (function () {
        yield new StreamChunk(content: '', toolCalls: [['name' => 'get_weather', 'arguments' => '{"city": "Paris"}']]);
        yield new StreamChunk(content: 'Result', finishReason: 'tool_calls');
    })();

    $response = new StreamedChatResponse($stream);
    $metadata = $response->getMetadata();

    expect($metadata->toolCalls)->toHaveCount(1);
    expect($metadata->toolCalls[0]['name'])->toBe('get_weather');
});

it('converts to ChatResponse', function () {
    $stream = (function () {
        yield new StreamChunk(content: 'Hello', role: 'assistant', model: 'gpt-4');
        yield new StreamChunk(content: ' World', inputTokens: 10, outputTokens: 5, finishReason: 'stop');
    })();

    $response = new StreamedChatResponse($stream);
    $chatResponse = $response->toChatResponse();

    expect($chatResponse)->toBeInstanceOf(ChatResponse::class);
    expect($chatResponse->content)->toBe('Hello World');
    expect($chatResponse->role)->toBe('assistant');
    expect($chatResponse->model)->toBe('gpt-4');
    expect($chatResponse->inputTokens)->toBe(10);
    expect($chatResponse->outputTokens)->toBe(5);
    expect($chatResponse->finishReason)->toBe('stop');
});

it('yields chunks during iteration', function () {
    $response = new StreamedChatResponse($this->simpleStream);
    $chunks = [];

    foreach ($response->chunks() as $chunk) {
        $chunks[] = $chunk->content;
    }

    expect($chunks)->toBe(['Hello', ' ', 'World']);
});

it('marks stream as consumed', function () {
    $response = new StreamedChatResponse($this->simpleStream);

    expect($response->isConsumed())->toBeFalse();

    $response->getText();

    expect($response->isConsumed())->toBeTrue();
});

it('throws when iterating chunks twice', function () {
    $response = new StreamedChatResponse($this->simpleStream);

    // First consumption
    foreach ($response->chunks() as $chunk) {
        // iterate
    }

    // Second consumption should throw
    expect(fn () => iterator_to_array($response->chunks()))
        ->toThrow(\RuntimeException::class, 'Stream has already been consumed');
});

it('allows getText to be called multiple times after consumption', function () {
    $response = new StreamedChatResponse($this->simpleStream);

    $text1 = $response->getText();
    $text2 = $response->getText();

    expect($text1)->toBe('Hello World');
    expect($text2)->toBe('Hello World');
});

it('handles chunks with null content', function () {
    $stream = (function () {
        yield new StreamChunk(role: 'assistant');
        yield new StreamChunk(content: 'Hello');
        yield new StreamChunk(content: null);
        yield new StreamChunk(content: 'World');
    })();

    $response = new StreamedChatResponse($stream);
    $text = $response->getText();

    expect($text)->toBe('HelloWorld');
});

it('handles chunks with empty string content', function () {
    $stream = (function () {
        yield new StreamChunk(content: 'Hello');
        yield new StreamChunk(content: '');
        yield new StreamChunk(content: 'World');
    })();

    $response = new StreamedChatResponse($stream);
    $text = $response->getText();

    expect($text)->toBe('HelloWorld');
});

it('updates metadata progressively', function () {
    $stream = (function () {
        yield new StreamChunk(role: 'assistant');
        yield new StreamChunk(model: 'gpt-4');
        yield new StreamChunk(content: 'Hello');
        yield new StreamChunk(inputTokens: 10);
        yield new StreamChunk(outputTokens: 5);
        yield new StreamChunk(finishReason: 'stop');
    })();

    $response = new StreamedChatResponse($stream);
    $metadata = $response->getMetadata();

    expect($metadata->role)->toBe('assistant');
    expect($metadata->model)->toBe('gpt-4');
    expect($metadata->inputTokens)->toBe(10);
    expect($metadata->outputTokens)->toBe(5);
    expect($metadata->finishReason)->toBe('stop');
});

it('handles multiple tool calls', function () {
    $stream = (function () {
        yield new StreamChunk(toolCalls: [['name' => 'tool1']]);
        yield new StreamChunk(toolCalls: [['name' => 'tool2']]);
        yield new StreamChunk(content: 'Done', finishReason: 'stop');
    })();

    $response = new StreamedChatResponse($stream);
    $metadata = $response->getMetadata();

    expect($metadata->toolCalls)->toHaveCount(2);
});
