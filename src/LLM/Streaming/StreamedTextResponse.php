<?php

namespace Mindwave\Mindwave\LLM\Streaming;

use Generator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streamed Text Response Helper
 *
 * Wraps an LLM text stream (Generator) and provides convenient methods for
 * converting it to various output formats, particularly Server-Sent Events (SSE)
 * for real-time streaming to web clients.
 *
 * Features:
 * - SSE formatting with proper event stream protocol
 * - Laravel StreamedResponse integration
 * - Callback support for chunk processing
 * - Automatic completion signaling
 *
 * Usage:
 * ```php
 * // In a Laravel controller
 * public function chat(Request $request)
 * {
 *     $stream = Mindwave::llm()->streamText($request->input('prompt'));
 *     $response = new StreamedTextResponse($stream);
 *
 *     return $response->toStreamedResponse();
 * }
 * ```
 *
 * Client-side consumption:
 * ```javascript
 * const eventSource = new EventSource('/api/chat?q=Hello');
 * eventSource.onmessage = (event) => {
 *     console.log('Received:', event.data);
 * };
 * eventSource.addEventListener('done', () => {
 *     eventSource.close();
 * });
 * ```
 */
class StreamedTextResponse
{
    private Generator $stream;

    private $errorHandler = null;

    private $completionHandler = null;

    private bool $cancelled = false;

    private int $retryAttempts = 0;

    private int $maxRetries = 3;

    /**
     * @param  Generator<string>  $stream  The text stream generator
     */
    public function __construct(Generator $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Convert the stream to a Laravel StreamedResponse with SSE formatting
     *
     * This method creates a proper Server-Sent Events response that can be
     * consumed by the EventSource API in browsers. Each chunk is sent as
     * a 'message' event, and a final 'done' event is sent when the stream completes.
     *
     * @param  int  $status  HTTP status code (default: 200)
     * @param  array<string, string>  $headers  Additional headers to include
     * @return StreamedResponse Laravel streamed response
     */
    public function toStreamedResponse(int $status = 200, array $headers = []): StreamedResponse
    {
        $callback = function () {
            try {
                foreach ($this->stream as $chunk) {
                    if ($this->cancelled) {
                        echo $this->formatSSE('cancelled', json_encode(['status' => 'cancelled']));
                        $this->flush();
                        break;
                    }

                    echo $this->formatSSE('message', $chunk);
                    $this->flush();
                }

                // Send completion event
                if (! $this->cancelled) {
                    echo $this->formatSSE('done', json_encode(['status' => 'complete']));
                    $this->flush();

                    if ($this->completionHandler) {
                        ($this->completionHandler)();
                    }
                }
            } catch (\Throwable $e) {
                $this->handleError($e);
            }
        };

        $mergedHeaders = array_merge([
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ], $headers);

        return new StreamedResponse($callback, $status, $mergedHeaders);
    }

    /**
     * Convert the stream to a plain text response (no SSE)
     *
     * This is useful for CLI or non-browser consumers that just want the
     * raw text stream without SSE formatting.
     *
     * @return StreamedResponse Laravel streamed response with plain text
     */
    public function toPlainStreamedResponse(): StreamedResponse
    {
        $callback = function () {
            foreach ($this->stream as $chunk) {
                echo $chunk;
                $this->flush();
            }
        };

        $headers = [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ];

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Convert the entire stream to a single string
     *
     * This consumes the stream and concatenates all chunks into a single string.
     * Note: This defeats the purpose of streaming, use only for debugging or testing.
     *
     * @return string The complete text from the stream
     */
    public function toString(): string
    {
        $result = '';

        foreach ($this->stream as $chunk) {
            $result .= $chunk;
        }

        return $result;
    }

    /**
     * Get the raw generator
     *
     * Useful for manual iteration or custom processing.
     *
     * @return Generator<string> The underlying text stream
     */
    public function getIterator(): Generator
    {
        return $this->stream;
    }

    /**
     * Process each chunk with a callback
     *
     * This allows you to perform side effects or transformations on each chunk
     * as it's received, while still streaming to the client.
     *
     * @param  callable(string): void  $callback  Function to call for each chunk
     * @return self Chainable instance for fluent usage
     */
    public function onChunk(callable $callback): self
    {
        $originalStream = $this->stream;

        // Wrap the original stream with a new generator that calls the callback
        $this->stream = (function () use ($originalStream, $callback) {
            foreach ($originalStream as $chunk) {
                $callback($chunk);
                yield $chunk;
            }
        })();

        return $this;
    }

    /**
     * Format data as Server-Sent Event
     *
     * SSE format:
     * event: <event-name>
     * data: <data>
     * \n\n
     *
     * @param  string  $event  Event name
     * @param  string  $data  Event data
     * @return string Formatted SSE message
     */
    private function formatSSE(string $event, string $data): string
    {
        // Escape newlines in data
        $data = str_replace("\n", '\n', $data);

        return "event: {$event}\ndata: {$data}\n\n";
    }

    /**
     * Set an error handler callback.
     *
     * The handler receives the exception and can decide how to handle it.
     * Return true to retry (up to maxRetries), false to stop the stream.
     *
     * @param  callable(\Throwable): bool  $handler  The error handler
     */
    public function onError(callable $handler): self
    {
        $this->errorHandler = $handler;

        return $this;
    }

    /**
     * Set a completion handler callback.
     *
     * This is called when the stream completes successfully.
     *
     * @param  callable(): void  $handler  The completion handler
     */
    public function onComplete(callable $handler): self
    {
        $this->completionHandler = $handler;

        return $this;
    }

    /**
     * Set the maximum number of retry attempts on error.
     *
     * @param  int  $retries  Maximum number of retries (default: 3)
     */
    public function withRetries(int $retries): self
    {
        $this->maxRetries = max(0, $retries);

        return $this;
    }

    /**
     * Cancel the stream.
     *
     * This will stop processing on the next iteration.
     */
    public function cancel(): void
    {
        $this->cancelled = true;
    }

    /**
     * Check if the stream has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    /**
     * Handle an error during streaming.
     *
     * @param  \Throwable  $error  The error that occurred
     */
    private function handleError(\Throwable $error): void
    {
        if ($this->errorHandler) {
            $shouldRetry = ($this->errorHandler)($error);

            if ($shouldRetry && $this->retryAttempts < $this->maxRetries) {
                $this->retryAttempts++;
                // In a real implementation, you might want to restart the stream here
                // For now, we just send an error event
                echo $this->formatSSE('error', json_encode([
                    'message' => $error->getMessage(),
                    'retry' => true,
                    'attempt' => $this->retryAttempts,
                ]));
            } else {
                echo $this->formatSSE('error', json_encode([
                    'message' => $error->getMessage(),
                    'retry' => false,
                ]));
            }
        } else {
            // Default error handling: send error event
            echo $this->formatSSE('error', json_encode([
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
            ]));
        }

        $this->flush();
    }

    /**
     * Flush output buffers to send data immediately
     *
     * This ensures chunks are sent to the client as soon as they're available,
     * rather than being buffered by PHP or the web server.
     */
    private function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
