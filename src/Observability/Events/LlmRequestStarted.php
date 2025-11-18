<?php

namespace Mindwave\Mindwave\Observability\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an LLM request begins.
 *
 * This event is dispatched at the start of every LLM call, providing
 * information about the request parameters and tracing identifiers.
 */
class LlmRequestStarted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $provider  The LLM provider (e.g., 'openai', 'anthropic', 'ollama')
     * @param  string  $model  The model being used (e.g., 'gpt-4', 'claude-3-opus')
     * @param  string  $operation  The operation type (e.g., 'chat', 'text_completion', 'embeddings')
     * @param  array|null  $messages  The messages being sent (null if capture disabled)
     * @param  array  $parameters  Additional request parameters (temperature, max_tokens, etc.)
     * @param  string  $spanId  The OpenTelemetry span ID
     * @param  string  $traceId  The OpenTelemetry trace ID
     * @param  int  $timestamp  The request start timestamp in nanoseconds
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly string $operation,
        public readonly ?array $messages,
        public readonly array $parameters,
        public readonly string $spanId,
        public readonly string $traceId,
        public readonly int $timestamp,
    ) {}

    /**
     * Get the request parameters.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get a specific parameter value.
     */
    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Check if messages are captured.
     */
    public function hasMessages(): bool
    {
        return $this->messages !== null;
    }

    /**
     * Get the timestamp in seconds.
     */
    public function getTimestampInSeconds(): float
    {
        return $this->timestamp / 1_000_000_000;
    }

    /**
     * Get the timestamp in milliseconds.
     */
    public function getTimestampInMilliseconds(): float
    {
        return $this->timestamp / 1_000_000;
    }

    /**
     * Get event data as array.
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'operation' => $this->operation,
            'messages' => $this->messages,
            'parameters' => $this->parameters,
            'span_id' => $this->spanId,
            'trace_id' => $this->traceId,
            'timestamp' => $this->timestamp,
        ];
    }
}
