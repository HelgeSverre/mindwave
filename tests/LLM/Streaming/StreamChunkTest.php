<?php

use Mindwave\Mindwave\LLM\Responses\StreamChunk;

it('creates chunk with content only', function () {
    $chunk = new StreamChunk(content: 'Hello');

    expect($chunk->content)->toBe('Hello');
    expect($chunk->role)->toBeNull();
    expect($chunk->finishReason)->toBeNull();
    expect($chunk->model)->toBeNull();
});

it('creates chunk with all properties', function () {
    $chunk = new StreamChunk(
        content: 'Hello',
        role: 'assistant',
        finishReason: 'stop',
        model: 'gpt-4',
        inputTokens: 10,
        outputTokens: 5,
        toolCalls: [['name' => 'test']],
        raw: ['key' => 'value']
    );

    expect($chunk->content)->toBe('Hello');
    expect($chunk->role)->toBe('assistant');
    expect($chunk->finishReason)->toBe('stop');
    expect($chunk->model)->toBe('gpt-4');
    expect($chunk->inputTokens)->toBe(10);
    expect($chunk->outputTokens)->toBe(5);
    expect($chunk->toolCalls)->toBe([['name' => 'test']]);
    expect($chunk->raw)->toBe(['key' => 'value']);
});

it('detects if chunk has content', function () {
    $chunkWithContent = new StreamChunk(content: 'Hello');
    $chunkWithoutContent = new StreamChunk(content: null);
    $chunkWithEmptyContent = new StreamChunk(content: '');

    expect($chunkWithContent->hasContent())->toBeTrue();
    expect($chunkWithoutContent->hasContent())->toBeFalse();
    expect($chunkWithEmptyContent->hasContent())->toBeFalse();
});

it('detects if chunk is complete', function () {
    $completeChunk = new StreamChunk(content: 'Hello', finishReason: 'stop');
    $incompleteChunk = new StreamChunk(content: 'Hello');

    expect($completeChunk->isComplete())->toBeTrue();
    expect($incompleteChunk->isComplete())->toBeFalse();
});

it('detects if chunk has tool calls', function () {
    $chunkWithToolCalls = new StreamChunk(toolCalls: [['name' => 'test']]);
    $chunkWithoutToolCalls = new StreamChunk(content: 'Hello');
    $chunkWithEmptyToolCalls = new StreamChunk(toolCalls: []);

    expect($chunkWithToolCalls->hasToolCalls())->toBeTrue();
    expect($chunkWithoutToolCalls->hasToolCalls())->toBeFalse();
    expect($chunkWithEmptyToolCalls->hasToolCalls())->toBeFalse();
});

it('is readonly', function () {
    $chunk = new StreamChunk(content: 'Hello');

    expect(fn () => $chunk->content = 'World')
        ->toThrow(\Error::class);
});
