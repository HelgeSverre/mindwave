<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing;

use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiAttributes;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ScopeInterface;
use Throwable;

/**
 * Span - Wrapper around OpenTelemetry Span
 *
 * Provides a convenient interface for working with OpenTelemetry spans
 * in the Mindwave ecosystem. Includes GenAI-specific helpers and
 * automatic context management.
 *
 * Features:
 * - Simplified attribute setting (single and batch)
 * - GenAI attribute helpers
 * - Automatic activation/deactivation
 * - Exception recording with proper status codes
 * - Event recording
 *
 * @see https://opentelemetry.io/docs/specs/otel/trace/api/#span
 */
class Span
{
    private SpanInterface $span;
    private ?ScopeInterface $scope = null;

    /**
     * @param  SpanInterface  $span  The underlying OpenTelemetry span
     */
    public function __construct(SpanInterface $span)
    {
        $this->span = $span;
    }

    /**
     * Get the underlying OpenTelemetry span
     */
    public function getOtelSpan(): SpanInterface
    {
        return $this->span;
    }

    /**
     * Set a single attribute on the span
     *
     * Handles null values gracefully by skipping them.
     *
     * @param  string  $key  Attribute key
     * @param  mixed  $value  Attribute value (null values are ignored)
     */
    public function setAttribute(string $key, mixed $value): self
    {
        if ($value !== null) {
            $this->span->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Set multiple attributes on the span
     *
     * @param  array<string, mixed>  $attributes  Attributes to set
     */
    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Set the span status
     *
     * @param  string  $code  Status code (StatusCode::STATUS_OK, StatusCode::STATUS_ERROR, StatusCode::STATUS_UNSET)
     * @param  string|null  $description  Optional status description
     */
    public function setStatus(string $code, ?string $description = null): self
    {
        $this->span->setStatus($code, $description);

        return $this;
    }

    /**
     * Mark the span as OK
     */
    public function markAsOk(): self
    {
        return $this->setStatus(StatusCode::STATUS_OK);
    }

    /**
     * Mark the span as errored
     *
     * @param  string|null  $description  Error description
     */
    public function markAsError(?string $description = null): self
    {
        return $this->setStatus(StatusCode::STATUS_ERROR, $description);
    }

    /**
     * Record an exception on the span
     *
     * This automatically sets the span status to ERROR and records
     * the exception details as span events.
     *
     * @param  Throwable  $exception  Exception to record
     * @param  array<string, mixed>  $attributes  Additional attributes
     */
    public function recordException(Throwable $exception, array $attributes = []): self
    {
        $this->span->recordException($exception, $attributes);
        $this->markAsError($exception->getMessage());

        return $this;
    }

    /**
     * Add an event to the span
     *
     * Events are timestamped occurrences during a span's lifetime.
     *
     * @param  string  $name  Event name
     * @param  array<string, mixed>  $attributes  Event attributes
     * @param  int|null  $timestamp  Event timestamp (nanoseconds since epoch)
     */
    public function addEvent(string $name, array $attributes = [], ?int $timestamp = null): self
    {
        $this->span->addEvent($name, $attributes, $timestamp);

        return $this;
    }

    /**
     * Update the span name
     *
     * Useful when the span name depends on runtime information.
     *
     * @param  string  $name  New span name
     */
    public function updateName(string $name): self
    {
        $this->span->updateName($name);

        return $this;
    }

    /**
     * Activate this span in the current context
     *
     * Makes this span the "active" span, so child spans will
     * automatically use it as their parent. Returns a scope
     * that must be detached when done.
     */
    public function activate(): ScopeInterface
    {
        $this->scope = $this->span->activate();

        return $this->scope;
    }

    /**
     * Detach the previously activated scope
     *
     * Call this after activate() when you're done with the span
     * to restore the previous context.
     */
    public function detach(): self
    {
        if ($this->scope !== null) {
            $this->scope->detach();
            $this->scope = null;
        }

        return $this;
    }

    /**
     * End the span
     *
     * This marks the span as complete and queues it for export.
     * After calling end(), no more modifications to the span are allowed.
     *
     * @param  int|null  $endEpochNanos  Optional end timestamp (nanoseconds since epoch)
     */
    public function end(?int $endEpochNanos = null): void
    {
        $this->span->end($endEpochNanos);
    }

    /**
     * Check if the span is recording
     *
     * A span may not be recording if it was sampled out.
     */
    public function isRecording(): bool
    {
        return $this->span->isRecording();
    }

    /**
     * Get the span context
     */
    public function getContext(): \OpenTelemetry\API\Trace\SpanContextInterface
    {
        return $this->span->getContext();
    }

    /**
     * Execute a callback with this span activated
     *
     * Automatically activates the span, executes the callback,
     * and detaches the scope. Handles exceptions properly.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws Throwable
     */
    public function wrap(callable $callback): mixed
    {
        $scope = $this->activate();

        try {
            return $callback();
        } catch (Throwable $e) {
            $this->recordException($e);
            throw $e;
        } finally {
            $scope->detach();
        }
    }

    /**
     * Set GenAI operation attributes
     *
     * Helper for setting common GenAI operation metadata.
     *
     * @param  string  $operationName  Operation name (e.g., "chat", "embeddings")
     * @param  string  $providerName  Provider name (e.g., "openai", "anthropic")
     * @param  string  $model  Model name
     */
    public function setGenAiOperation(
        string $operationName,
        string $providerName,
        string $model
    ): self {
        return $this->setAttributes([
            GenAiAttributes::GEN_AI_OPERATION_NAME => $operationName,
            GenAiAttributes::GEN_AI_PROVIDER_NAME => $providerName,
            GenAiAttributes::GEN_AI_REQUEST_MODEL => $model,
        ]);
    }

    /**
     * Set GenAI request parameters
     *
     * Helper for setting common request parameters.
     *
     * @param  array<string, mixed>  $params  Request parameters
     */
    public function setGenAiRequestParams(array $params): self
    {
        $attributes = [];

        if (isset($params['temperature'])) {
            $attributes[GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE] = $params['temperature'];
        }

        if (isset($params['max_tokens'])) {
            $attributes[GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS] = $params['max_tokens'];
        }

        if (isset($params['top_p'])) {
            $attributes[GenAiAttributes::GEN_AI_REQUEST_TOP_P] = $params['top_p'];
        }

        if (isset($params['top_k'])) {
            $attributes[GenAiAttributes::GEN_AI_REQUEST_TOP_K] = $params['top_k'];
        }

        if (isset($params['frequency_penalty'])) {
            $attributes[GenAiAttributes::GEN_AI_REQUEST_FREQUENCY_PENALTY] = $params['frequency_penalty'];
        }

        if (isset($params['presence_penalty'])) {
            $attributes[GenAiAttributes::GEN_AI_REQUEST_PRESENCE_PENALTY] = $params['presence_penalty'];
        }

        if (isset($params['stop'])) {
            $attributes[GenAiAttributes::GEN_AI_REQUEST_STOP_SEQUENCES] = $params['stop'];
        }

        return $this->setAttributes($attributes);
    }

    /**
     * Set GenAI response attributes
     *
     * Helper for setting response metadata.
     *
     * @param  array<string, mixed>  $response  Response data
     */
    public function setGenAiResponse(array $response): self
    {
        $attributes = [];

        if (isset($response['id'])) {
            $attributes[GenAiAttributes::GEN_AI_RESPONSE_ID] = $response['id'];
        }

        if (isset($response['model'])) {
            $attributes[GenAiAttributes::GEN_AI_RESPONSE_MODEL] = $response['model'];
        }

        if (isset($response['finish_reasons'])) {
            $attributes[GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS] = $response['finish_reasons'];
        }

        return $this->setAttributes($attributes);
    }

    /**
     * Set GenAI token usage attributes
     *
     * Helper for setting token usage data.
     *
     * @param  int|null  $inputTokens  Number of input tokens
     * @param  int|null  $outputTokens  Number of output tokens
     * @param  int|null  $cacheReadTokens  Cache read tokens (optional)
     * @param  int|null  $cacheCreationTokens  Cache creation tokens (optional)
     */
    public function setGenAiUsage(
        ?int $inputTokens = null,
        ?int $outputTokens = null,
        ?int $cacheReadTokens = null,
        ?int $cacheCreationTokens = null
    ): self {
        $attributes = [];

        if ($inputTokens !== null) {
            $attributes[GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS] = $inputTokens;
        }

        if ($outputTokens !== null) {
            $attributes[GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS] = $outputTokens;
        }

        if ($inputTokens !== null && $outputTokens !== null) {
            $attributes[GenAiAttributes::GEN_AI_USAGE_TOTAL_TOKENS] = $inputTokens + $outputTokens;
        }

        if ($cacheReadTokens !== null) {
            $attributes[GenAiAttributes::GEN_AI_USAGE_CACHE_READ_TOKENS] = $cacheReadTokens;
        }

        if ($cacheCreationTokens !== null) {
            $attributes[GenAiAttributes::GEN_AI_USAGE_CACHE_CREATION_TOKENS] = $cacheCreationTokens;
        }

        return $this->setAttributes($attributes);
    }

    /**
     * Set GenAI input messages (opt-in sensitive data)
     *
     * @param  array<array<string, mixed>>  $messages  Input messages
     */
    public function setGenAiInputMessages(array $messages): self
    {
        return $this->setAttribute(GenAiAttributes::GEN_AI_INPUT_MESSAGES, $messages);
    }

    /**
     * Set GenAI output messages (opt-in sensitive data)
     *
     * @param  array<array<string, mixed>>  $messages  Output messages
     */
    public function setGenAiOutputMessages(array $messages): self
    {
        return $this->setAttribute(GenAiAttributes::GEN_AI_OUTPUT_MESSAGES, $messages);
    }

    /**
     * Set server attributes
     *
     * @param  string  $address  Server address (e.g., "api.openai.com")
     * @param  int  $port  Server port (e.g., 443)
     */
    public function setServerAttributes(string $address, int $port = 443): self
    {
        return $this->setAttributes([
            GenAiAttributes::SERVER_ADDRESS => $address,
            GenAiAttributes::SERVER_PORT => $port,
        ]);
    }

    /**
     * Destructor - ensures span is ended if not already
     *
     * This is a safety mechanism to prevent unclosed spans.
     */
    public function __destruct()
    {
        if ($this->isRecording()) {
            $this->end();
        }
    }
}
