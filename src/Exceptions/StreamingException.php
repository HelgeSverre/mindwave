<?php

namespace Mindwave\Mindwave\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown during streaming operations.
 *
 * This exception is thrown when errors occur during LLM streaming,
 * such as connection failures, timeouts, or invalid stream data.
 */
class StreamingException extends Exception
{
    /**
     * The provider that caused the error.
     */
    protected ?string $provider = null;

    /**
     * The model being used when the error occurred.
     */
    protected ?string $model = null;

    /**
     * Whether this error is retryable.
     */
    protected bool $retryable = false;

    /**
     * Create a new streaming exception.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  Throwable|null  $previous  The previous exception
     * @param  string|null  $provider  The LLM provider
     * @param  string|null  $model  The model identifier
     * @param  bool  $retryable  Whether the operation can be retried
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
        ?string $provider = null,
        ?string $model = null,
        bool $retryable = false
    ) {
        parent::__construct($message, $code, $previous);
        $this->provider = $provider;
        $this->model = $model;
        $this->retryable = $retryable;
    }

    /**
     * Get the provider that caused the error.
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * Get the model being used when the error occurred.
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * Check if this error is retryable.
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * Create an exception for connection failures.
     */
    public static function connectionFailed(
        string $provider,
        string $model,
        ?Throwable $previous = null
    ): self {
        return new self(
            "Failed to establish streaming connection to {$provider} ({$model}).",
            0,
            $previous,
            $provider,
            $model,
            true
        );
    }

    /**
     * Create an exception for stream timeouts.
     */
    public static function timeout(
        string $provider,
        string $model,
        int $seconds
    ): self {
        return new self(
            "Streaming timeout after {$seconds} seconds for {$provider} ({$model}).",
            0,
            null,
            $provider,
            $model,
            true
        );
    }

    /**
     * Create an exception for invalid stream data.
     */
    public static function invalidData(
        string $provider,
        string $model,
        string $reason = 'Invalid stream chunk format'
    ): self {
        return new self(
            "Invalid streaming data from {$provider} ({$model}): {$reason}",
            0,
            null,
            $provider,
            $model,
            false
        );
    }

    /**
     * Create an exception for stream interruptions.
     */
    public static function interrupted(
        string $provider,
        string $model,
        ?Throwable $previous = null
    ): self {
        return new self(
            "Stream interrupted from {$provider} ({$model}).",
            0,
            $previous,
            $provider,
            $model,
            true
        );
    }
}
