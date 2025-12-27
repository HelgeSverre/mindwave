<?php

use Mindwave\Mindwave\LLM\Drivers\Fake;
use Mindwave\Mindwave\LLM\Responses\StreamChunk;
use Mindwave\Mindwave\LLM\Responses\StreamedChatResponse;

it('can stream chat with Fake driver', function () {
    $driver = new Fake;
    $driver->respondsWith('Hello world!');

    $messages = [
        ['role' => 'user', 'content' => 'Say hello'],
    ];

    $stream = $driver->streamChat($messages);
    $chunks = iterator_to_array($stream);

    // With default chunk size of 5, "Hello world!" should be split into chunks
    expect($chunks)->toHaveCount(3);
    expect($chunks[0])->toBeInstanceOf(StreamChunk::class);
    expect($chunks[0]->content)->toBe('Hello');
    expect($chunks[0]->role)->toBe('assistant');
    expect($chunks[0]->finishReason)->toBeNull();
});

it('provides metadata in first and last chunks', function () {
    $driver = new Fake;
    $driver->respondsWith('Test message')
        ->streamChunkSize(4);

    $stream = $driver->streamChat([['role' => 'user', 'content' => 'test']]);
    $chunks = iterator_to_array($stream);

    // First chunk should have role and input tokens
    $firstChunk = $chunks[0];
    expect($firstChunk->role)->toBe('assistant');
    expect($firstChunk->inputTokens)->toBe(10);
    expect($firstChunk->finishReason)->toBeNull();

    // Last chunk should have finish reason and output tokens
    $lastChunk = end($chunks);
    expect($lastChunk->finishReason)->toBe('stop');
    expect($lastChunk->outputTokens)->toBe(10);
});

it('can wrap stream in StreamedChatResponse', function () {
    $driver = new Fake;
    $driver->respondsWith('Hello from streaming!')
        ->streamChunkSize(6);

    $stream = $driver->streamChat([['role' => 'user', 'content' => 'test']]);
    $response = new StreamedChatResponse($stream);

    $fullText = $response->getText();
    expect($fullText)->toBe('Hello from streaming!');

    $metadata = $response->getMetadata();
    expect($metadata->role)->toBe('assistant');
    expect($metadata->finishReason)->toBe('stop');
    expect($metadata->inputTokens)->toBe(10);
    expect($metadata->outputTokens)->toBe(10);
});

it('accumulates content from all chunks', function () {
    $driver = new Fake;
    $driver->respondsWith('The quick brown fox')
        ->streamChunkSize(3);

    $stream = $driver->streamChat([['role' => 'user', 'content' => 'test']]);
    $response = new StreamedChatResponse($stream);

    $accumulated = '';
    foreach ($response->chunks() as $chunk) {
        if ($chunk->content) {
            $accumulated .= $chunk->content;
        }
    }

    expect($accumulated)->toBe('The quick brown fox');
});

it('throws exception for drivers that do not support streamChat', function () {
    // Create a mock driver that extends BaseDriver but doesn't override streamChat
    $driver = new class extends \Mindwave\Mindwave\LLM\Drivers\BaseDriver
    {
        public function generateText(string $prompt): ?string
        {
            return 'test';
        }

        public function chat(array $messages, array $options = []): \Mindwave\Mindwave\LLM\Responses\ChatResponse
        {
            return new \Mindwave\Mindwave\LLM\Responses\ChatResponse(content: 'test');
        }
    };

    expect(fn () => $driver->streamChat([]))
        ->toThrow(BadMethodCallException::class, 'Chat streaming is not supported');
});

it('handles empty messages in streamChat', function () {
    $driver = new Fake;
    $driver->respondsWith('');

    $stream = $driver->streamChat([]);
    $chunks = iterator_to_array($stream);

    expect($chunks)->toBe([]);
});

it('streamChat preserves multibyte characters', function () {
    $driver = new Fake;
    $driver->respondsWith('你好世界')
        ->streamChunkSize(2);

    $stream = $driver->streamChat([['role' => 'user', 'content' => 'test']]);
    $chunks = iterator_to_array($stream);

    $accumulated = '';
    foreach ($chunks as $chunk) {
        if ($chunk->content) {
            $accumulated .= $chunk->content;
        }
    }

    expect($accumulated)->toBe('你好世界');
});
