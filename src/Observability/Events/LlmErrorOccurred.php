<?php

namespace Mindwave\Mindwave\Observability\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Event fired when an LLM call fails with an error.
 *
 * This event is dispatched when an LLM request encounters an error,
 * providing information about the exception and the context in which it occurred.
 */
class LlmErrorOccurred
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Throwable $exception The exception that occurred
     * @param string $provider The LLM provider (e.g., 'openai', 'anthropic', 'ollama')
     * @param string $model The model being used
     * @param string $operation The operation type (e.g., 'chat', 'text_completion', 'embeddings')
     * @param string $spanId The OpenTelemetry span ID
     * @param string $traceId The OpenTelemetry trace ID
     * @param int $timestamp The error timestamp in nanoseconds
     * @param array $context Additional context about the error
     */
    public function __construct(
        public readonly Throwable $exception,
        public readonly string $provider,
        public readonly string $model,
        public readonly string $operation,
        public readonly string $spanId,
        public readonly string $traceId,
        public readonly int $timestamp,
        public readonly array $context = [],
    ) {
    }

    /**
     * Get the exception message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->exception->getMessage();
    }

    /**
     * Get the exception code.
     *
     * @return int|string
     */
    public function getCode(): int|string
    {
        return $this->exception->getCode();
    }

    /**
     * Get the exception class name.
     *
     * @return string
     */
    public function getExceptionClass(): string
    {
        return get_class($this->exception);
    }

    /**
     * Get the exception file.
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->exception->getFile();
    }

    /**
     * Get the exception line number.
     *
     * @return int
     */
    public function getLine(): int
    {
        return $this->exception->getLine();
    }

    /**
     * Get the exception stack trace.
     *
     * @return string
     */
    public function getTrace(): string
    {
        return $this->exception->getTraceAsString();
    }

    /**
     * Get the previous exception if available.
     *
     * @return Throwable|null
     */
    public function getPrevious(): ?Throwable
    {
        return $this->exception->getPrevious();
    }

    /**
     * Check if there is a previous exception.
     *
     * @return bool
     */
    public function hasPrevious(): bool
    {
        return $this->exception->getPrevious() !== null;
    }

    /**
     * Get context value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getContext(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Get the timestamp in seconds.
     *
     * @return float
     */
    public function getTimestampInSeconds(): float
    {
        return $this->timestamp / 1_000_000_000;
    }

    /**
     * Get the timestamp in milliseconds.
     *
     * @return float
     */
    public function getTimestampInMilliseconds(): float
    {
        return $this->timestamp / 1_000_000;
    }

    /**
     * Get error information for logging.
     *
     * @return array
     */
    public function getErrorInfo(): array
    {
        return [
            'class' => $this->getExceptionClass(),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }

    /**
     * Get event data as array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'exception' => $this->getErrorInfo(),
            'provider' => $this->provider,
            'model' => $this->model,
            'operation' => $this->operation,
            'span_id' => $this->spanId,
            'trace_id' => $this->traceId,
            'timestamp' => $this->timestamp,
            'context' => $this->context,
        ];
    }
}
