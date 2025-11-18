<?php

namespace Mindwave\Mindwave\Observability\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired for each streaming token received from an LLM.
 *
 * This event is dispatched during streaming responses, providing
 * real-time information about token generation progress.
 */
class LlmTokenStreamed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $delta  The token delta (new content chunk)
     * @param  int  $cumulativeTokens  The cumulative number of tokens streamed so far
     * @param  string  $spanId  The OpenTelemetry span ID
     * @param  string  $traceId  The OpenTelemetry trace ID
     * @param  int  $timestamp  The timestamp when this token was received in nanoseconds
     * @param  array  $metadata  Additional metadata (role, finish_reason, etc.)
     */
    public function __construct(
        public readonly string $delta,
        public readonly int $cumulativeTokens,
        public readonly string $spanId,
        public readonly string $traceId,
        public readonly int $timestamp,
        public readonly array $metadata = [],
    ) {}

    /**
     * Get the length of the delta in characters.
     */
    public function getDeltaLength(): int
    {
        return mb_strlen($this->delta);
    }

    /**
     * Get metadata value.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if this is the final token (has finish_reason).
     */
    public function isFinal(): bool
    {
        return isset($this->metadata['finish_reason']) && $this->metadata['finish_reason'] !== null;
    }

    /**
     * Get the finish reason if this is the final token.
     */
    public function getFinishReason(): ?string
    {
        return $this->metadata['finish_reason'] ?? null;
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
            'delta' => $this->delta,
            'cumulative_tokens' => $this->cumulativeTokens,
            'span_id' => $this->spanId,
            'trace_id' => $this->traceId,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata,
        ];
    }
}
