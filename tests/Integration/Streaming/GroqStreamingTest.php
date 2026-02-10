<?php

use Mindwave\Mindwave\LLM\Drivers\OpenAI\OpenAI as OpenAIDriver;
use Mindwave\Mindwave\LLM\Responses\StreamChunk;
use Mindwave\Mindwave\LLM\Responses\StreamedChatResponse;
use Mindwave\Mindwave\LLM\Streaming\StreamedTextResponse;

beforeEach(function () {
    $apiKey = env('GROQ_API_KEY');
    if (empty($apiKey)) {
        test()->markTestSkipped('GROQ_API_KEY not set');
    }

    $client = \OpenAI::factory()
        ->withApiKey($apiKey)
        ->withBaseUri('https://api.groq.com/openai/v1')
        ->make();

    $this->driver = new OpenAIDriver(
        client: $client,
        model: 'llama-3.3-70b-versatile',
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
})->group('integration', 'groq');

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

    $contentChunks = array_filter($chunks, fn (StreamChunk $c) => $c->hasContent());
    expect($contentChunks)->not->toBeEmpty();
})->group('integration', 'groq');

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
})->group('integration', 'groq');

it('StreamedChatResponse accumulates metadata', function () {
    $stream = $this->driver->streamChat([
        ['role' => 'user', 'content' => 'Say "hi".'],
    ]);

    $chatResponse = new StreamedChatResponse($stream);
    $text = $chatResponse->getText();

    expect(strtolower($text))->toContain('hi');

    $metadata = $chatResponse->getMetadata();
    expect($metadata->finishReason)->toBe('stop');
})->group('integration', 'groq');
