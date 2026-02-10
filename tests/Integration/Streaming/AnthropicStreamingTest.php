<?php

use Mindwave\Mindwave\LLM\Drivers\Anthropic\AnthropicDriver;
use Mindwave\Mindwave\LLM\Responses\StreamChunk;
use Mindwave\Mindwave\LLM\Responses\StreamedChatResponse;
use Mindwave\Mindwave\LLM\Streaming\StreamedTextResponse;

beforeEach(function () {
    $apiKey = env('ANTHROPIC_API_KEY');
    if (empty($apiKey)) {
        test()->markTestSkipped('ANTHROPIC_API_KEY not set');
    }

    $this->driver = new AnthropicDriver(
        client: \Anthropic::client($apiKey),
        model: 'claude-haiku-4-5-20251001',
        maxTokens: 50,
        temperature: 0.0,
    );
});

it('streams text and receives multiple chunks', function () {
    $chunks = [];

    foreach ($this->driver->streamText('Say "Hello World" and nothing else.') as $chunk) {
        $chunks[] = $chunk;
    }

    expect($chunks)->not->toBeEmpty();

    $fullText = implode('', $chunks);
    expect(strtolower($fullText))->toContain('hello');

    foreach ($chunks as $chunk) {
        expect($chunk)->toBeString()->not->toBeEmpty();
    }
})->group('integration', 'anthropic');

it('streams chat with structured StreamChunk objects', function () {
    $chunks = [];

    foreach ($this->driver->streamChat([
        ['role' => 'user', 'content' => 'Say "Hello" and nothing else.'],
    ]) as $chunk) {
        $chunks[] = $chunk;
    }

    expect($chunks)->not->toBeEmpty();

    foreach ($chunks as $chunk) {
        expect($chunk)->toBeInstanceOf(StreamChunk::class);
    }

    // At least one chunk should have content
    $contentChunks = array_filter($chunks, fn (StreamChunk $c) => $c->hasContent());
    expect($contentChunks)->not->toBeEmpty();
})->group('integration', 'anthropic');

it('StreamedTextResponse works end-to-end with SSE output', function () {
    $stream = $this->driver->streamText('Say "test" and nothing else.');
    $response = new StreamedTextResponse($stream);

    $chunksSeen = 0;
    $response->onChunk(function () use (&$chunksSeen) {
        $chunksSeen++;
    });

    $completed = false;
    $response->onComplete(function () use (&$completed) {
        $completed = true;
    });

    $httpResponse = $response->toStreamedResponse();

    $output = '';
    ob_start(function (string $data) use (&$output) {
        $output .= $data;

        return '';
    });
    $httpResponse->sendContent();
    ob_end_clean();

    expect($output)->toContain('event: message');
    expect($output)->toContain('event: done');
    expect($chunksSeen)->toBeGreaterThan(0);
    expect($completed)->toBeTrue();
})->group('integration', 'anthropic');

it('StreamedChatResponse accumulates metadata', function () {
    $stream = $this->driver->streamChat([
        ['role' => 'user', 'content' => 'Say "hi".'],
    ]);

    $chatResponse = new StreamedChatResponse($stream);
    $text = $chatResponse->getText();

    expect(strtolower($text))->toContain('hi');

    $metadata = $chatResponse->getMetadata();
    expect($metadata->model)->toContain('claude');
})->group('integration', 'anthropic');

it('handles streaming errors from invalid parameters', function () {
    $driver = new AnthropicDriver(
        client: \Anthropic::client(env('ANTHROPIC_API_KEY')),
        model: 'claude-haiku-4-5-20251001',
        maxTokens: 50,
        temperature: -999.0,
    );

    expect(function () use ($driver) {
        foreach ($driver->streamText('Hello') as $chunk) {
            // consume
        }
    })->toThrow(\Exception::class);
})->group('integration', 'anthropic');
