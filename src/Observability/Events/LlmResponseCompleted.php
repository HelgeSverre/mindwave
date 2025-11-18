<?php

namespace Mindwave\Mindwave\Observability\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an LLM response is completed.
 *
 * This event is dispatched when an LLM call finishes successfully,
 * providing comprehensive information about the response, token usage,
 * performance metrics, and cost estimates.
 */
class LlmResponseCompleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $provider The LLM provider (e.g., 'openai', 'anthropic', 'ollama')
     * @param string $model The model that was used
     * @param string $operation The operation type (e.g., 'chat', 'text_completion', 'embeddings')
     * @param array $response The response data (id, choices, finish_reason, etc.)
     * @param array $tokenUsage Token usage information (input, output, cache, etc.)
     * @param int $duration The request duration in nanoseconds
     * @param float|null $costEstimate Estimated cost in USD (null if not available)
     * @param string $spanId The OpenTelemetry span ID
     * @param string $traceId The OpenTelemetry trace ID
     * @param int $timestamp The completion timestamp in nanoseconds
     * @param array $metadata Additional metadata
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly string $operation,
        public readonly array $response,
        public readonly array $tokenUsage,
        public readonly int $duration,
        public readonly ?float $costEstimate,
        public readonly string $spanId,
        public readonly string $traceId,
        public readonly int $timestamp,
        public readonly array $metadata = [],
    ) {
    }

    /**
     * Get the response ID.
     *
     * @return string|null
     */
    public function getResponseId(): ?string
    {
        return $this->response['id'] ?? null;
    }

    /**
     * Get the finish reason.
     *
     * @return string|null
     */
    public function getFinishReason(): ?string
    {
        return $this->response['finish_reason'] ?? null;
    }

    /**
     * Get all finish reasons (for multiple choices).
     *
     * @return array
     */
    public function getFinishReasons(): array
    {
        return $this->response['finish_reasons'] ?? [];
    }

    /**
     * Get input token count.
     *
     * @return int
     */
    public function getInputTokens(): int
    {
        return $this->tokenUsage['input_tokens'] ?? 0;
    }

    /**
     * Get output token count.
     *
     * @return int
     */
    public function getOutputTokens(): int
    {
        return $this->tokenUsage['output_tokens'] ?? 0;
    }

    /**
     * Get total token count.
     *
     * @return int
     */
    public function getTotalTokens(): int
    {
        return $this->getInputTokens() + $this->getOutputTokens();
    }

    /**
     * Get cache read token count.
     *
     * @return int
     */
    public function getCacheReadTokens(): int
    {
        return $this->tokenUsage['cache_read_tokens'] ?? 0;
    }

    /**
     * Get cache creation token count.
     *
     * @return int
     */
    public function getCacheCreationTokens(): int
    {
        return $this->tokenUsage['cache_creation_tokens'] ?? 0;
    }

    /**
     * Check if caching was used.
     *
     * @return bool
     */
    public function usedCache(): bool
    {
        return $this->getCacheReadTokens() > 0 || $this->getCacheCreationTokens() > 0;
    }

    /**
     * Get the duration in seconds.
     *
     * @return float
     */
    public function getDurationInSeconds(): float
    {
        return $this->duration / 1_000_000_000;
    }

    /**
     * Get the duration in milliseconds.
     *
     * @return float
     */
    public function getDurationInMilliseconds(): float
    {
        return $this->duration / 1_000_000;
    }

    /**
     * Get tokens per second.
     *
     * @return float
     */
    public function getTokensPerSecond(): float
    {
        $durationSeconds = $this->getDurationInSeconds();
        if ($durationSeconds === 0.0) {
            return 0.0;
        }

        return $this->getTotalTokens() / $durationSeconds;
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
     * Get metadata value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if cost estimate is available.
     *
     * @return bool
     */
    public function hasCostEstimate(): bool
    {
        return $this->costEstimate !== null;
    }

    /**
     * Get formatted cost estimate.
     *
     * @param int $decimals Number of decimal places
     * @return string|null
     */
    public function getFormattedCost(int $decimals = 4): ?string
    {
        if ($this->costEstimate === null) {
            return null;
        }

        return '$' . number_format($this->costEstimate, $decimals);
    }

    /**
     * Get event data as array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'operation' => $this->operation,
            'response' => $this->response,
            'token_usage' => $this->tokenUsage,
            'duration' => $this->duration,
            'duration_ms' => $this->getDurationInMilliseconds(),
            'cost_estimate' => $this->costEstimate,
            'span_id' => $this->spanId,
            'trace_id' => $this->traceId,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata,
        ];
    }
}
