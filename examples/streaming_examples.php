<?php

/**
 * Streaming Examples for Mindwave Laravel Package
 *
 * This file demonstrates the enhanced streaming capabilities including
 * error handling, transformation utilities, metadata tracking, and event hooks.
 */

use Mindwave\Mindwave\Facades\Mindwave;
use Mindwave\Mindwave\LLM\Responses\StreamedChatResponse;
use Mindwave\Mindwave\LLM\Streaming\StreamedTextResponse;
use Mindwave\Mindwave\LLM\Streaming\StreamTransformer;

// =============================================================================
// Example 1: Basic Streaming with Error Handling
// =============================================================================

function basicStreamingWithErrorHandling()
{
    $stream = Mindwave::llm()->streamText('Tell me a story about a brave knight');

    $response = new StreamedTextResponse($stream);

    // Add error handling
    $response->onError(function (\Throwable $error) {
        logger()->error("Streaming error occurred: {$error->getMessage()}");

        return true; // Retry up to maxRetries times
    });

    // Add completion callback
    $response->onComplete(function () {
        logger()->info('Streaming completed successfully!');
    });

    // Set max retries
    $response->withRetries(3);

    // Return as Server-Sent Events response
    return $response->toStreamedResponse();
}

// =============================================================================
// Example 2: Stream Transformation Pipeline
// =============================================================================

function streamTransformationPipeline()
{
    $stream = Mindwave::llm()->streamText('Write a long essay about artificial intelligence');

    // Create a complex transformation pipeline
    $transformed = StreamTransformer::from($stream)
        // Skip the first chunk (might be empty)
        ->skip(1)
        // Filter out empty chunks
        ->filter(fn ($chunk) => trim($chunk) !== '')
        // Normalize whitespace
        ->map(fn ($chunk) => preg_replace('/\s+/', ' ', $chunk))
        // Log each chunk for debugging
        ->tap(function ($chunk) {
            logger()->debug('Received chunk: '.substr($chunk, 0, 50).'...');
        })
        // Buffer 5 chunks together to reduce update frequency
        ->buffer(5)
        // Take only first 20 buffered chunks
        ->take(20)
        // Get the generator
        ->getGenerator();

    return (new StreamedTextResponse($transformed))->toStreamedResponse();
}

// =============================================================================
// Example 3: Cancellable Streaming with Timeout
// =============================================================================

function cancellableStreamingWithTimeout()
{
    $stream = Mindwave::llm()->streamText('Generate a very long document');

    $response = new StreamedTextResponse($stream);

    // Set up timeout cancellation
    $timeoutSeconds = 30;
    $startTime = time();

    $response->onChunk(function ($chunk) use (&$response, $startTime, $timeoutSeconds) {
        if (time() - $startTime > $timeoutSeconds) {
            $response->cancel();
            logger()->warning('Stream cancelled due to timeout');
        }
    });

    return $response->toStreamedResponse();
}

// =============================================================================
// Example 4: Streaming with Metadata Tracking
// =============================================================================

function streamingWithMetadataTracking()
{
    $llm = Mindwave::llm();

    // For this example, you'd need to implement a method that returns StreamChunk objects
    // This is a conceptual example showing how you would use StreamedChatResponse

    $stream = (function () use ($llm) {
        // Your streaming implementation that yields StreamChunk objects
        // For example, you might wrap OpenAI streaming responses
        $rawStream = $llm->streamText('Explain quantum computing');

        foreach ($rawStream as $content) {
            yield new \Mindwave\Mindwave\LLM\Responses\StreamChunk(
                content: $content
            );
        }
    })();

    $response = new StreamedChatResponse($stream);

    // Process chunks in real-time
    $buffer = '';
    foreach ($response->chunks() as $chunk) {
        $buffer .= $chunk->content;

        // You can check for completion
        if ($chunk->isComplete()) {
            logger()->info("Stream completed with reason: {$chunk->finishReason}");
        }

        // You can handle tool calls
        if ($chunk->hasToolCalls()) {
            foreach ($chunk->toolCalls as $toolCall) {
                logger()->info("Tool call requested: {$toolCall['name']}");
            }
        }
    }

    // Get final metadata after streaming
    $metadata = $response->getMetadata();

    return response()->json([
        'content' => $metadata->content,
        'tokens' => [
            'input' => $metadata->inputTokens,
            'output' => $metadata->outputTokens,
            'total' => $metadata->totalTokens,
        ],
        'finish_reason' => $metadata->finishReason,
        'model' => $metadata->model,
    ]);
}

// =============================================================================
// Example 5: Progressive UI Updates
// =============================================================================

function progressiveUIUpdates()
{
    $stream = Mindwave::llm()->streamText('Write a detailed article about climate change');

    // Use chunk-based updates with character limits
    $transformed = StreamTransformer::from($stream)
        // Combine chunks into 100-character segments
        ->chunk(100)
        // Log progress
        ->tap(function ($chunk) {
            $chunkLength = strlen($chunk);
            logger()->debug("Sending {$chunkLength} characters to client");
        })
        ->getGenerator();

    return (new StreamedTextResponse($transformed))->toStreamedResponse();
}

// =============================================================================
// Example 6: Streaming with Rate Limiting (Debouncing)
// =============================================================================

function streamingWithRateLimiting()
{
    $stream = Mindwave::llm()->streamText('Generate a comprehensive guide to Laravel');

    // Debounce to reduce update frequency
    $debounced = StreamTransformer::from($stream)
        // Accumulate 10 chunks before emitting
        ->debounce(10)
        // Log when chunks are emitted
        ->tap(function ($chunk) {
            logger()->debug('Emitting debounced chunk: '.strlen($chunk).' chars');
        })
        ->getGenerator();

    return (new StreamedTextResponse($debounced))->toStreamedResponse();
}

// =============================================================================
// Example 7: MistralDriver Streaming
// =============================================================================

function mistralDriverStreaming()
{
    // Use Mistral AI for streaming
    $driver = Mindwave::llm('mistral')
        ->model('mistral-large-latest')
        ->temperature(0.7)
        ->maxTokens(2000);

    $stream = $driver->streamText('Explain the theory of relativity in simple terms');

    $response = new StreamedTextResponse($stream);

    $response->onChunk(function ($chunk) {
        // Track progress
        logger()->debug("Mistral chunk: $chunk");
    });

    return $response->toStreamedResponse();
}

// =============================================================================
// Example 8: Multi-Stage Processing
// =============================================================================

function multiStageProcessing()
{
    $stream = Mindwave::llm()->streamText('Write code examples for a REST API');

    $processed = StreamTransformer::from($stream)
        // Stage 1: Filter
        ->filter(fn ($chunk) => strlen($chunk) > 0)
        // Stage 2: Transform
        ->map(function ($chunk) {
            // Highlight code blocks
            return str_replace('```', '```php', $chunk);
        })
        // Stage 3: Buffer
        ->buffer(3)
        // Stage 4: Side effects
        ->tap(function ($chunk) {
            // Save to cache, database, etc.
            cache()->put('latest_chunk', $chunk, 60);
        })
        ->getGenerator();

    return (new StreamedTextResponse($processed))->toStreamedResponse();
}

// =============================================================================
// Example 9: Error Recovery with Fallback
// =============================================================================

function errorRecoveryWithFallback()
{
    $stream = Mindwave::llm()->streamText('Generate content...');

    $response = new StreamedTextResponse($stream);

    $errorCount = 0;

    $response->onError(function (\Throwable $error) use (&$errorCount) {
        $errorCount++;

        logger()->error("Error #{$errorCount}: {$error->getMessage()}");

        // Retry on transient errors, give up on permanent errors
        if ($error instanceof \RuntimeException) {
            return false; // Don't retry
        }

        return $errorCount < 3; // Retry up to 3 times
    });

    $response->withRetries(3);

    return $response->toStreamedResponse();
}

// =============================================================================
// Example 10: Plain Text Streaming for CLI
// =============================================================================

function plainTextStreamingForCLI()
{
    $stream = Mindwave::llm()->streamText('Write a story');

    // For CLI or non-browser clients
    $response = new StreamedTextResponse($stream);

    // Process with callback
    $response->onChunk(function ($chunk) {
        // You could write to a file, send to websocket, etc.
        echo $chunk;
    });

    // Return as plain text (no SSE formatting)
    return $response->toPlainStreamedResponse();
}

// =============================================================================
// Example 11: Collecting Statistics
// =============================================================================

function collectingStatistics()
{
    $stream = Mindwave::llm()->streamText('Analyze data...');

    $chunkCount = 0;
    $totalChars = 0;
    $startTime = microtime(true);

    $tracked = StreamTransformer::from($stream)
        ->tap(function ($chunk) use (&$chunkCount, &$totalChars) {
            $chunkCount++;
            $totalChars += strlen($chunk);
        })
        ->getGenerator();

    $response = new StreamedTextResponse($tracked);

    $response->onComplete(function () use (&$chunkCount, &$totalChars, $startTime) {
        $duration = microtime(true) - $startTime;

        logger()->info('Streaming complete', [
            'chunks' => $chunkCount,
            'characters' => $totalChars,
            'duration' => $duration,
            'chars_per_second' => $totalChars / $duration,
        ]);
    });

    return $response->toStreamedResponse();
}

// =============================================================================
// Example 12: Converting Stream to String (Testing/Debugging)
// =============================================================================

function convertStreamToString()
{
    $stream = Mindwave::llm()->streamText('Short response');

    // For testing/debugging - collect entire stream into string
    $text = StreamTransformer::from($stream)->collect();

    // Or using StreamedTextResponse
    $response = new StreamedTextResponse($stream);
    $text = $response->toString();

    return response()->json(['text' => $text]);
}
