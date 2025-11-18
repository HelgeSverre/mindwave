<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing;

use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiAttributes;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanContextInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\ContextInterface;

/**
 * SpanBuilder - Fluent interface for building OpenTelemetry spans
 *
 * Provides a convenient, chainable API for configuring spans before starting them.
 * This is particularly useful when you need fine-grained control over:
 * - Parent context (for creating child spans)
 * - Start timestamp
 * - Span kind (client, server, internal, etc.)
 * - Initial attributes
 * - Links to other spans
 *
 * @see https://opentelemetry.io/docs/specs/otel/trace/api/#spanbuilder
 */
class SpanBuilder
{
    private SpanBuilderInterface $builder;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * @param  SpanBuilderInterface  $builder  The underlying OpenTelemetry span builder
     */
    public function __construct(SpanBuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Set the span name
     *
     * @param  string  $name  Span name (e.g., "chat gpt-4")
     */
    public function setName(string $name): self
    {
        // Note: SpanBuilderInterface doesn't have setName, it's set in constructor
        // This is here for API consistency but doesn't actually do anything
        // The name is set when creating the builder
        return $this;
    }

    /**
     * Set the span kind
     *
     * Available kinds:
     * - INTERNAL: Internal operation
     * - CLIENT: Client request (default for LLM calls)
     * - SERVER: Server handling request
     * - PRODUCER: Message producer
     * - CONSUMER: Message consumer
     *
     * @param  int  $kind  SpanKind constant
     */
    public function setSpanKind(int $kind): self
    {
        $this->builder->setSpanKind($kind);

        return $this;
    }

    /**
     * Set span kind to CLIENT
     */
    public function asClient(): self
    {
        return $this->setSpanKind(SpanKind::KIND_CLIENT);
    }

    /**
     * Set span kind to SERVER
     */
    public function asServer(): self
    {
        return $this->setSpanKind(SpanKind::KIND_SERVER);
    }

    /**
     * Set span kind to INTERNAL
     */
    public function asInternal(): self
    {
        return $this->setSpanKind(SpanKind::KIND_INTERNAL);
    }

    /**
     * Set span kind to PRODUCER
     */
    public function asProducer(): self
    {
        return $this->setSpanKind(SpanKind::KIND_PRODUCER);
    }

    /**
     * Set span kind to CONSUMER
     */
    public function asConsumer(): self
    {
        return $this->setSpanKind(SpanKind::KIND_CONSUMER);
    }

    /**
     * Set the parent context
     *
     * If not set, the current active context is used.
     *
     * @param  ContextInterface  $context  Parent context
     */
    public function setParent(ContextInterface $context): self
    {
        $this->builder->setParent($context);

        return $this;
    }

    /**
     * Set as root span (no parent)
     *
     * @param  bool  $value  Whether this should be a root span
     */
    public function setNoParent(bool $value = true): self
    {
        if ($value) {
            $this->builder->setNoParent();
        }

        return $this;
    }

    /**
     * Add a link to another span
     *
     * Links allow associating this span with other spans that are
     * causally related but not in a parent-child relationship.
     *
     * @param  SpanContextInterface  $context  Context of the span to link to
     * @param  array<string, mixed>  $attributes  Link attributes
     */
    public function addLink(SpanContextInterface $context, array $attributes = []): self
    {
        $this->builder->addLink($context, $attributes);

        return $this;
    }

    /**
     * Set a single attribute
     *
     * @param  string  $key  Attribute key
     * @param  mixed  $value  Attribute value (null values are ignored)
     */
    public function setAttribute(string $key, mixed $value): self
    {
        if ($value !== null) {
            $this->attributes[$key] = $value;
            $this->builder->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Set multiple attributes
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
     * Set the start timestamp
     *
     * If not set, the current time is used when start() is called.
     *
     * @param  int  $timestampNanos  Timestamp in nanoseconds since epoch
     */
    public function setStartTimestamp(int $timestampNanos): self
    {
        $this->builder->setStartTimestamp($timestampNanos);

        return $this;
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
    public function forGenAiOperation(
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
     * @param  array<string, mixed>  $params  Request parameters
     */
    public function withGenAiRequestParams(array $params): self
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
     * Set server attributes
     *
     * @param  string  $address  Server address (e.g., "api.openai.com")
     * @param  int  $port  Server port (e.g., 443)
     */
    public function withServerAttributes(string $address, int $port = 443): self
    {
        return $this->setAttributes([
            GenAiAttributes::SERVER_ADDRESS => $address,
            GenAiAttributes::SERVER_PORT => $port,
        ]);
    }

    /**
     * For chat operations
     *
     * Convenience method that sets operation name to "chat".
     *
     * @param  string  $providerName  Provider name
     * @param  string  $model  Model name
     */
    public function forChat(string $providerName, string $model): self
    {
        return $this->forGenAiOperation('chat', $providerName, $model);
    }

    /**
     * For embeddings operations
     *
     * Convenience method that sets operation name to "embeddings".
     *
     * @param  string  $providerName  Provider name
     * @param  string  $model  Model name
     */
    public function forEmbeddings(string $providerName, string $model): self
    {
        return $this->forGenAiOperation('embeddings', $providerName, $model);
    }

    /**
     * For tool execution operations
     *
     * Convenience method that sets operation name to "execute_tool".
     *
     * @param  string  $toolName  Tool name
     */
    public function forToolExecution(string $toolName): self
    {
        return $this->setAttribute(GenAiAttributes::GEN_AI_TOOL_CALL_NAME, $toolName)
            ->setAttribute(GenAiAttributes::GEN_AI_OPERATION_NAME, 'execute_tool');
    }

    /**
     * Start the span
     *
     * This creates and returns the actual span. After this point,
     * you can't modify the span builder anymore.
     */
    public function start(): Span
    {
        $otelSpan = $this->builder->startSpan();

        return new Span($otelSpan);
    }

    /**
     * Start the span and activate it
     *
     * This creates the span and makes it the active span in the current context.
     * Returns both the span and its scope (for detachment).
     *
     * @return array{span: Span, scope: \OpenTelemetry\Context\ScopeInterface}
     */
    public function startAndActivate(): array
    {
        $span = $this->start();
        $scope = $span->activate();

        return [
            'span' => $span,
            'scope' => $scope,
        ];
    }

    /**
     * Get the underlying OpenTelemetry span builder
     */
    public function getBuilder(): SpanBuilderInterface
    {
        return $this->builder;
    }

    /**
     * Get all attributes that have been set
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
