<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing;

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;

/**
 * TracerManager - Central manager for OpenTelemetry tracing in Mindwave
 *
 * This class provides a Laravel-friendly wrapper around the OpenTelemetry SDK,
 * handling initialization, configuration, and span creation for GenAI observability.
 *
 * Features:
 * - Automatic TracerProvider initialization with configured exporters
 * - Context propagation and parent-child span relationships
 * - Batch processing for efficient span export
 * - Configurable sampling strategies
 * - GenAI-specific attribute support
 *
 * @see https://opentelemetry.io/docs/specs/otel/trace/api/
 */
class TracerManager
{
    private TracerProviderInterface $tracerProvider;
    private TracerInterface $tracer;

    /** @var array<SpanExporterInterface> */
    private array $exporters = [];

    private string $serviceName;
    private string $serviceVersion;
    private string $instrumentationScope;

    /**
     * @param  array<SpanExporterInterface>  $exporters  Span exporters (database, OTLP, etc.)
     * @param  string  $serviceName  Service name for resource attributes
     * @param  string  $serviceVersion  Service version for resource attributes
     * @param  string  $instrumentationScope  Instrumentation scope name
     * @param  SamplerInterface|null  $sampler  Custom sampler (defaults to AlwaysOnSampler)
     * @param  array<string, mixed>  $batchConfig  Batch processor configuration
     */
    public function __construct(
        array $exporters = [],
        string $serviceName = 'mindwave',
        string $serviceVersion = '1.0.0',
        string $instrumentationScope = 'mindwave.llm',
        ?SamplerInterface $sampler = null,
        array $batchConfig = []
    ) {
        $this->exporters = $exporters;
        $this->serviceName = $serviceName;
        $this->serviceVersion = $serviceVersion;
        $this->instrumentationScope = $instrumentationScope;

        $this->tracerProvider = $this->createTracerProvider($sampler, $batchConfig);
        $this->tracer = $this->tracerProvider->getTracer($this->instrumentationScope);
    }

    /**
     * Create the TracerProvider with configured exporters and processors
     *
     * @param  array<string, mixed>  $batchConfig
     */
    private function createTracerProvider(
        ?SamplerInterface $sampler,
        array $batchConfig
    ): TracerProviderInterface {
        $resource = $this->createResourceInfo();
        $processors = $this->createSpanProcessors($batchConfig);
        $sampler = $sampler ?? new AlwaysOnSampler;

        return new TracerProvider(
            $processors,
            $sampler,
            $resource
        );
    }

    /**
     * Create ResourceInfo with service metadata
     */
    private function createResourceInfo(): ResourceInfo
    {
        return ResourceInfoFactory::emptyResource()->merge(
            ResourceInfo::create(
                Attributes::create([
                    ResourceAttributes::SERVICE_NAME => $this->serviceName,
                    ResourceAttributes::SERVICE_VERSION => $this->serviceVersion,
                    'mindwave.sdk.version' => '2.0.0',
                ])
            )
        );
    }

    /**
     * Create span processors for all configured exporters
     *
     * @param  array<string, mixed>  $batchConfig
     * @return array<SpanProcessorInterface>
     */
    private function createSpanProcessors(array $batchConfig): array
    {
        if (empty($this->exporters)) {
            return [];
        }

        $processors = [];

        foreach ($this->exporters as $exporter) {
            $processors[] = new BatchSpanProcessor(
                $exporter,
                \OpenTelemetry\SDK\Common\Time\ClockFactory::getDefault(),
                $batchConfig['max_queue_size'] ?? 2048,
                $batchConfig['scheduled_delay_ms'] ?? 5000,
                $batchConfig['export_timeout_ms'] ?? 30000,
                $batchConfig['max_export_batch_size'] ?? 512
            );
        }

        return $processors;
    }

    /**
     * Start a new span with the given name and attributes
     *
     * This creates a new span in the current trace context. If called within
     * an active span, the new span will be a child of the active span.
     *
     * @param  string  $name  Span name (e.g., "chat gpt-4")
     * @param  array<string, mixed>  $attributes  Initial span attributes
     * @param  int  $kind  Span kind (default: SpanKind::KIND_CLIENT)
     */
    public function startSpan(
        string $name,
        array $attributes = [],
        int $kind = SpanKind::KIND_CLIENT
    ): Span {
        $spanBuilder = $this->tracer
            ->spanBuilder($name)
            ->setSpanKind($kind);

        foreach ($attributes as $key => $value) {
            if ($value !== null) {
                $spanBuilder->setAttribute($key, $value);
            }
        }

        $otelSpan = $spanBuilder->startSpan();

        return new Span($otelSpan);
    }

    /**
     * Create a SpanBuilder for more control over span creation
     *
     * Use this when you need fine-grained control over:
     * - Parent context
     * - Start timestamp
     * - Links to other spans
     * - Additional attributes
     *
     * @param  string  $name  Span name
     */
    public function spanBuilder(string $name): SpanBuilder
    {
        return new SpanBuilder($this->tracer->spanBuilder($name));
    }

    /**
     * Get the underlying OpenTelemetry tracer
     */
    public function getTracer(): TracerInterface
    {
        return $this->tracer;
    }

    /**
     * Get the TracerProvider instance
     */
    public function getTracerProvider(): TracerProviderInterface
    {
        return $this->tracerProvider;
    }

    /**
     * Force flush all pending spans to exporters
     *
     * Useful for testing or before application shutdown.
     * Blocks until all spans are exported or timeout is reached.
     *
     * @return bool True if flush succeeded
     */
    public function forceFlush(): bool
    {
        if ($this->tracerProvider instanceof TracerProvider) {
            return $this->tracerProvider->forceFlush();
        }

        return true;
    }

    /**
     * Shutdown the tracer provider and flush remaining spans
     *
     * This should be called before application shutdown to ensure
     * all spans are properly exported.
     *
     * @return bool True if shutdown succeeded
     */
    public function shutdown(): bool
    {
        if ($this->tracerProvider instanceof TracerProvider) {
            return $this->tracerProvider->shutdown();
        }

        return true;
    }

    /**
     * Create a sampler from configuration
     *
     * Supports:
     * - always_on: Always sample (100%)
     * - always_off: Never sample (0%)
     * - traceidratio: Sample based on trace ID ratio
     * - parentbased: Respect parent span sampling decision
     *
     * @param  string  $type  Sampler type
     * @param  float  $ratio  Sampling ratio (for traceidratio)
     */
    public static function createSampler(string $type, float $ratio = 1.0): SamplerInterface
    {
        return match ($type) {
            'always_on' => new AlwaysOnSampler,
            'always_off' => new \OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler,
            'traceidratio' => new TraceIdRatioBasedSampler($ratio),
            'parentbased' => new ParentBased(new AlwaysOnSampler),
            default => new AlwaysOnSampler,
        };
    }

    /**
     * Add an exporter to the manager
     *
     * Note: This requires recreating the TracerProvider to take effect.
     * Best used during initialization, not at runtime.
     */
    public function addExporter(SpanExporterInterface $exporter): void
    {
        $this->exporters[] = $exporter;
    }

    /**
     * Get all registered exporters
     *
     * @return array<SpanExporterInterface>
     */
    public function getExporters(): array
    {
        return $this->exporters;
    }

    /**
     * Get service name
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * Get service version
     */
    public function getServiceVersion(): string
    {
        return $this->serviceVersion;
    }

    /**
     * Get instrumentation scope
     */
    public function getInstrumentationScope(): string
    {
        return $this->instrumentationScope;
    }
}
