<?php

namespace Mindwave\Mindwave\LLM\Streaming;

use Closure;
use Generator;
use Mindwave\Mindwave\Exceptions\StreamingException;
use Throwable;

/**
 * Stream Retry Handler
 *
 * Provides automatic retry functionality for streaming operations.
 * Wraps a streaming generator and retries on retryable errors.
 *
 * Features:
 * - Configurable retry attempts
 * - Exponential backoff with jitter
 * - Selective retry based on exception type
 * - Custom retry predicates
 * - Progress callbacks
 *
 * Usage:
 * ```php
 * $handler = new StreamRetryHandler(
 *     maxRetries: 3,
 *     initialDelay: 1000, // 1 second
 *     maxDelay: 30000,    // 30 seconds
 * );
 *
 * $stream = $handler->wrap(function () use ($driver) {
 *     return $driver->streamText('Hello');
 * });
 *
 * foreach ($stream as $chunk) {
 *     echo $chunk;
 * }
 * ```
 */
class StreamRetryHandler
{
    /**
     * Maximum number of retry attempts.
     */
    protected int $maxRetries;

    /**
     * Initial delay in milliseconds before first retry.
     */
    protected int $initialDelay;

    /**
     * Maximum delay in milliseconds between retries.
     */
    protected int $maxDelay;

    /**
     * Backoff multiplier for exponential backoff.
     */
    protected float $backoffMultiplier;

    /**
     * Callback to determine if an exception is retryable.
     *
     * @var Closure(Throwable, int): bool|null
     */
    protected ?Closure $retryPredicate = null;

    /**
     * Callback invoked before each retry attempt.
     *
     * @var Closure(Throwable, int): void|null
     */
    protected ?Closure $onRetry = null;

    /**
     * @param  int  $maxRetries  Maximum number of retry attempts (default: 3)
     * @param  int  $initialDelay  Initial delay in milliseconds (default: 1000)
     * @param  int  $maxDelay  Maximum delay in milliseconds (default: 30000)
     * @param  float  $backoffMultiplier  Exponential backoff multiplier (default: 2.0)
     */
    public function __construct(
        int $maxRetries = 3,
        int $initialDelay = 1000,
        int $maxDelay = 30000,
        float $backoffMultiplier = 2.0
    ) {
        $this->maxRetries = max(0, $maxRetries);
        $this->initialDelay = max(0, $initialDelay);
        $this->maxDelay = max($initialDelay, $maxDelay);
        $this->backoffMultiplier = max(1.0, $backoffMultiplier);
    }

    /**
     * Wrap a streaming operation with retry logic.
     *
     * @param  Closure(): Generator  $operation  The streaming operation to execute
     * @return Generator The wrapped stream with retry support
     */
    public function wrap(Closure $operation): Generator
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $this->maxRetries) {
            try {
                // Execute the operation
                $stream = $operation();

                // Yield all chunks from the stream
                foreach ($stream as $chunk) {
                    yield $chunk;
                }

                // If we successfully consumed the entire stream, we're done
                return;
            } catch (Throwable $e) {
                $lastException = $e;

                // Check if we should retry
                if (! $this->shouldRetry($e, $attempt)) {
                    throw $e;
                }

                // Check if we've exhausted retries
                if ($attempt >= $this->maxRetries) {
                    throw $e;
                }

                // Invoke retry callback if set
                if ($this->onRetry !== null) {
                    ($this->onRetry)($e, $attempt + 1);
                }

                // Calculate delay and sleep
                $delay = $this->calculateDelay($attempt);
                usleep($delay * 1000); // Convert to microseconds

                $attempt++;
            }
        }

        // If we get here, all retries failed
        if ($lastException !== null) {
            throw $lastException;
        }
    }

    /**
     * Set a custom retry predicate.
     *
     * The predicate receives the exception and current attempt number.
     * Return true to retry, false to fail immediately.
     *
     * @param  Closure(Throwable, int): bool  $predicate  The retry predicate
     */
    public function setRetryPredicate(Closure $predicate): self
    {
        $this->retryPredicate = $predicate;

        return $this;
    }

    /**
     * Set a callback to be invoked before each retry.
     *
     * @param  Closure(Throwable, int): void  $callback  The retry callback
     */
    public function onRetry(Closure $callback): self
    {
        $this->onRetry = $callback;

        return $this;
    }

    /**
     * Determine if an exception should trigger a retry.
     *
     * @param  Throwable  $exception  The exception that occurred
     * @param  int  $attempt  The current attempt number (0-based)
     */
    protected function shouldRetry(Throwable $exception, int $attempt): bool
    {
        // If we've exhausted retries, don't retry
        if ($attempt >= $this->maxRetries) {
            return false;
        }

        // If a custom predicate is set, use it
        if ($this->retryPredicate !== null) {
            return ($this->retryPredicate)($exception, $attempt);
        }

        // Default behavior: retry on StreamingException if it's retryable
        if ($exception instanceof StreamingException) {
            return $exception->isRetryable();
        }

        // Don't retry other exceptions by default
        return false;
    }

    /**
     * Calculate the delay before the next retry attempt.
     *
     * Uses exponential backoff with jitter to avoid thundering herd.
     *
     * @param  int  $attempt  The current attempt number (0-based)
     * @return int Delay in milliseconds
     */
    protected function calculateDelay(int $attempt): int
    {
        // Calculate exponential backoff
        $exponentialDelay = $this->initialDelay * pow($this->backoffMultiplier, $attempt);

        // Cap at max delay
        $delay = min($exponentialDelay, $this->maxDelay);

        // Add jitter (±25%)
        $jitter = $delay * 0.25;
        $delay += random_int((int) -$jitter, (int) $jitter);

        return max(0, (int) $delay);
    }

    /**
     * Get the maximum number of retry attempts.
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Get the initial delay in milliseconds.
     */
    public function getInitialDelay(): int
    {
        return $this->initialDelay;
    }

    /**
     * Get the maximum delay in milliseconds.
     */
    public function getMaxDelay(): int
    {
        return $this->maxDelay;
    }

    /**
     * Get the backoff multiplier.
     */
    public function getBackoffMultiplier(): float
    {
        return $this->backoffMultiplier;
    }
}
