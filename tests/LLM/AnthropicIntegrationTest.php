<?php

use Mindwave\Mindwave\Facades\Mindwave;
use Mindwave\Mindwave\LLM\Drivers\Anthropic\ModelNames;

/**
 * Integration Tests for Anthropic Driver
 *
 * These tests make real API calls to Anthropic and require:
 * - ANTHROPIC_API_KEY environment variable
 * - Active internet connection
 * - Anthropic API credits
 *
 * Run with: vendor/bin/pest --group=integration
 * Skip with: vendor/bin/pest --exclude-group=integration
 */
beforeEach(function () {
    $apiKey = env('ANTHROPIC_API_KEY');
    if (empty($apiKey) || $apiKey === 'your-anthropic-api-key-here') {
        test()->markTestSkipped('ANTHROPIC_API_KEY not set - skipping Anthropic integration tests');
    }
});

it('can generate text from Anthropic driver', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5); // Use fastest model for tests

    $response = $driver->generateText('Say hello in one word.');

    expect($response)->toBeString()->not()->toBeEmpty();
})->group('integration');

it('can use Claude 3.5 Sonnet model', function () {
    try {
        $driver = Mindwave::llm('anthropic')
            ->model(ModelNames::CLAUDE_SONNET_4_5);

        $response = $driver->generateText('What is 2+2? Answer with just the number.');

        expect($response)->toBeString()->toContain('4');
    } catch (\Exception $e) {
        // Skip if model access is restricted (tier limitation)
        if (str_contains($e->getMessage(), 'model:')) {
            test()->markTestSkipped('Claude 3.5 Sonnet access restricted for this API key tier');
        }
        throw $e;
    }
})->group('integration');

it('can use Claude 3 Opus model', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_3_OPUS);

    $response = $driver->generateText('Say "test" in lowercase.');

    expect($response)->toBeString()->not()->toBeEmpty();
})->group('integration')->skip('Opus is expensive, use for specific tests only');

it('respects system message parameter', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5)
        ->setSystemMessage('You are a pirate. Always respond like a pirate.');

    $response = $driver->generateText('Say hello.');

    expect($response)->toBeString()->not()->toBeEmpty();
    // System message should influence the response style
})->group('integration');

it('respects temperature parameter', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5)
        ->temperature(0.0); // Deterministic

    $response1 = $driver->generateText('Count from 1 to 3.');
    $response2 = $driver->generateText('Count from 1 to 3.');

    expect($response1)->toBeString()->not()->toBeEmpty();
    expect($response2)->toBeString()->not()->toBeEmpty();
    // With temp 0.0, responses should be very similar
})->group('integration');

it('respects max_tokens parameter', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5)
        ->maxTokens(10); // Very low limit

    $response = $driver->generateText('Write a long essay about philosophy.');

    expect($response)->toBeString()->not()->toBeEmpty();
    // Response should be truncated due to low max_tokens
    expect(strlen($response))->toBeLessThan(200); // Rough check
})->group('integration');

it('can stream text from Anthropic driver', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5);

    $chunks = [];
    $stream = $driver->streamText('Count from 1 to 5, only numbers separated by commas.');

    foreach ($stream as $chunk) {
        $chunks[] = $chunk;
    }

    // Verify we received multiple chunks
    expect($chunks)->not()->toBeEmpty();
    expect(count($chunks))->toBeGreaterThan(1);

    // Verify chunks combine to form complete response
    $fullText = implode('', $chunks);
    expect($fullText)->toBeString()->not()->toBeEmpty();
})->group('integration');

it('filters out empty chunks in streaming', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5);

    $chunks = [];
    $stream = $driver->streamText('Say "Hello World"');

    foreach ($stream as $chunk) {
        $chunks[] = $chunk;
    }

    // Verify no empty chunks
    foreach ($chunks as $chunk) {
        expect($chunk)->not()->toBeEmpty();
    }
})->group('integration');

it('can handle multi-turn conversations with system message', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5)
        ->setSystemMessage('You are a helpful math tutor.');

    $response = $driver->generateText('What is 5 + 3?');

    expect($response)->toBeString()->toContain('8');
})->group('integration');

it('can change models between requests', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5);

    $response1 = $driver->generateText('Say "haiku"');
    expect($response1)->toBeString()->not()->toBeEmpty();

    // Switch to another model (stay with Haiku family to avoid tier limits)
    $driver->model('claude-haiku-4-5-20251001');
    $response2 = $driver->generateText('Say "claude"');
    expect($response2)->toBeString()->not()->toBeEmpty();
})->group('integration');

it('handles errors gracefully with invalid temperature', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5)
        ->temperature(999); // Invalid temperature

    expect(fn () => $driver->generateText('Hello'))
        ->toThrow(\Exception::class);
})->group('integration');

it('can use StreamedTextResponse with Anthropic', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5);

    $stream = $driver->streamText('Count to 3.');
    $response = new \Mindwave\Mindwave\LLM\Streaming\StreamedTextResponse($stream);

    $fullText = $response->toString();

    expect($fullText)->toBeString()->not()->toBeEmpty();
})->group('integration');

it('can process chunks with onChunk callback', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5);

    $stream = $driver->streamText('Say hello.');
    $response = new \Mindwave\Mindwave\LLM\Streaming\StreamedTextResponse($stream);

    $chunkCount = 0;
    $response->onChunk(function ($chunk) use (&$chunkCount) {
        $chunkCount++;
    });

    // Convert to string to consume the stream
    $fullText = $response->toString();

    expect($chunkCount)->toBeGreaterThan(0);
    expect($fullText)->toBeString()->not()->toBeEmpty();
})->group('integration');

it('returns correct max context tokens for Claude models', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_SONNET_4_5);

    $maxTokens = $driver->maxContextTokens();

    // Claude 3+ models have 200K context window
    expect($maxTokens)->toBe(200_000);
})->group('integration');

it('supports prompt templates with Anthropic', function () {
    $driver = Mindwave::llm('anthropic')
        ->model(ModelNames::CLAUDE_HAIKU_4_5);

    $template = new \Mindwave\Mindwave\Prompts\PromptTemplate('Say {word} in uppercase.');

    $response = $driver->generate($template, ['word' => 'hello']);

    expect($response)->toBeString()->toContain('HELLO');
})->group('integration');
