# OpenTelemetry Tracing Core

This directory contains the core OpenTelemetry tracing implementation for Mindwave, providing production-grade LLM observability using OpenTelemetry standards.

## Overview

The tracing core provides a Laravel-friendly wrapper around the OpenTelemetry PHP SDK, specifically designed for GenAI observability with support for:

- OpenTelemetry GenAI semantic conventions
- Multiple exporters (OTLP, Database, custom)
- Context propagation and parent-child span relationships
- Batch processing for efficient span export
- Configurable sampling strategies
- GenAI-specific attribute helpers

## Components

### TracerManager

**File:** `TracerManager.php`

Central manager for OpenTelemetry tracing in Mindwave. Handles:

- TracerProvider initialization with configured exporters
- Tracer creation with proper instrumentation scope
- Span creation with automatic parent context
- Batch processing configuration
- Sampler configuration

**Example:**

```php
use Mindwave\Mindwave\Observability\Tracing\TracerManager;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;

$tracer = new TracerManager(
    exporters: [new InMemoryExporter()],
    serviceName: 'my-app',
    serviceVersion: '1.0.0',
    instrumentationScope: 'mindwave.llm'
);

// Create a simple span
$span = $tracer->startSpan('operation-name', [
    'custom.attribute' => 'value',
]);
$span->end();

// Force flush for testing
$tracer->forceFlush();
```

### Span

**File:** `Span.php`

Wrapper around OpenTelemetry Span providing convenient methods for:

- Setting attributes (single and batch)
- Recording exceptions
- Adding events
- Context activation/deactivation
- GenAI-specific helpers

**Example:**

```php
// Basic span usage
$span = $tracer->startSpan('chat gpt-4');

$span->setAttribute('custom.key', 'value')
    ->setAttributes([
        'key1' => 'value1',
        'key2' => 42,
    ]);

// GenAI-specific helpers
$span->setGenAiOperation('chat', 'openai', 'gpt-4')
    ->setGenAiRequestParams([
        'temperature' => 0.7,
        'max_tokens' => 100,
    ])
    ->setGenAiUsage(
        inputTokens: 100,
        outputTokens: 50
    );

$span->end();
```

**Wrapping execution:**

```php
$span = $tracer->startSpan('process-data');

$result = $span->wrap(function () {
    // Your code here
    return processData();
});

$span->end();
```

**Exception handling:**

```php
$span = $tracer->startSpan('risky-operation');

try {
    performRiskyOperation();
} catch (\Throwable $e) {
    $span->recordException($e);
    throw $e;
} finally {
    $span->end();
}
```

### SpanBuilder

**File:** `SpanBuilder.php`

Fluent interface for building spans with fine-grained control over:

- Parent context
- Start timestamp
- Span kind (client, server, internal, etc.)
- Initial attributes
- Links to other spans

**Example:**

```php
// Basic usage
$span = $tracer->spanBuilder('operation-name')
    ->asClient()
    ->setAttribute('key', 'value')
    ->start();

$span->end();

// GenAI chat operation
$span = $tracer->spanBuilder('chat gpt-4')
    ->forChat('openai', 'gpt-4')
    ->withGenAiRequestParams([
        'temperature' => 0.7,
        'max_tokens' => 100,
    ])
    ->withServerAttributes('api.openai.com', 443)
    ->start();

// Do work
$response = callLLM();

$span->setGenAiUsage(
    inputTokens: $response->usage->promptTokens,
    outputTokens: $response->usage->completionTokens
);

$span->end();

// Embeddings operation
$span = $tracer->spanBuilder('embeddings text-embedding-ada-002')
    ->forEmbeddings('openai', 'text-embedding-ada-002')
    ->setAttribute(GenAiAttributes::GEN_AI_EMBEDDINGS_DIMENSION, 1536)
    ->start();
```

## GenAI Attributes

All GenAI semantic convention attributes are available via the `GenAiAttributes` class in `GenAI/GenAiAttributes.php`.

### Required Attributes

Every GenAI span should have:

- `gen_ai.operation.name` - "chat", "embeddings", "execute_tool"
- `gen_ai.provider.name` - "openai", "anthropic", "mistral_ai"
- `gen_ai.request.model` - Model name

### Request Attributes

- `gen_ai.request.temperature`
- `gen_ai.request.max_tokens`
- `gen_ai.request.top_p`
- `gen_ai.request.top_k`
- `gen_ai.request.frequency_penalty`
- `gen_ai.request.presence_penalty`

### Response Attributes

- `gen_ai.response.id`
- `gen_ai.response.model`
- `gen_ai.response.finish_reasons`

### Usage Attributes

- `gen_ai.usage.input_tokens`
- `gen_ai.usage.output_tokens`
- `gen_ai.usage.total_tokens`
- `gen_ai.usage.cache_read_tokens` (Anthropic)
- `gen_ai.usage.cache_creation_tokens` (Anthropic)

### Sensitive Attributes (Opt-in)

- `gen_ai.input.messages`
- `gen_ai.output.messages`
- `gen_ai.system_instructions`
- `gen_ai.tool.call.arguments`
- `gen_ai.tool.call.result`

## Parent-Child Relationships

OpenTelemetry automatically manages parent-child span relationships using context propagation:

```php
// Create parent span
$parentSpan = $tracer->startSpan('parent-operation');
$parentScope = $parentSpan->activate();

// Child span automatically uses parent context
$childSpan = $tracer->startSpan('child-operation');
$childSpan->end();

// Clean up
$parentScope->detach();
$parentSpan->end();
```

## Samplers

Configure sampling to control what percentage of traces are recorded:

```php
// Always sample (100%)
$sampler = TracerManager::createSampler('always_on');

// Never sample (0%)
$sampler = TracerManager::createSampler('always_off');

// Sample 10% based on trace ID
$sampler = TracerManager::createSampler('traceidratio', 0.1);

// Parent-based sampling
$sampler = TracerManager::createSampler('parentbased');

$tracer = new TracerManager(
    exporters: [$exporter],
    sampler: $sampler
);
```

## Batch Processing

Configure batch processor for optimal performance:

```php
$tracer = new TracerManager(
    exporters: [$exporter],
    batchConfig: [
        'max_queue_size' => 2048,          // Buffer size
        'scheduled_delay_ms' => 5000,      // Export every 5s
        'export_timeout_ms' => 30000,      // 30s timeout
        'max_export_batch_size' => 512,    // Max batch size
    ]
);
```

## Multiple Exporters

Support for multiple exporters (e.g., database + OTLP):

```php
use OpenTelemetry\Contrib\Otlp\SpanExporter;

$tracer = new TracerManager(
    exporters: [
        new DatabaseSpanExporter(),  // Save to database
        new SpanExporter(/* OTLP config */),  // Send to Jaeger/Grafana
    ]
);
```

## Testing

Comprehensive tests are available in `tests/Observability/Tracing/TracerCoreTest.php`.

Run tests:

```bash
php vendor/bin/pest tests/Observability/Tracing/TracerCoreTest.php
```

## Integration

The tracing core is designed to be used by higher-level components:

1. **GenAI Instrumentor** - Automatically instruments LLM calls
2. **Database Exporter** - Persists spans to Laravel database
3. **OTLP Exporters** - Sends spans to Jaeger, Grafana, Datadog, etc.
4. **Laravel Service Provider** - Bootstraps tracing in Laravel apps

## Performance

The implementation is designed for minimal overhead:

- Batch processing reduces export frequency
- Sampling controls trace volume
- Asynchronous export (via batch processor)
- Efficient attribute handling (null values ignored)
- Auto-flushing on shutdown

Target: < 5ms overhead per LLM call for tracing

## Standards Compliance

This implementation follows:

- OpenTelemetry Trace API specification
- OpenTelemetry GenAI Semantic Conventions
- PSR-4 autoloading standards
- PHP 8.2+ type system
- SOLID principles

## Dependencies

- `open-telemetry/sdk` ^1.0
- `open-telemetry/exporter-otlp` ^1.0
- `open-telemetry/sem-conv` ^1.0

All dependencies are already installed in the project.

## Next Steps

To complete the tracing implementation:

1. **DatabaseSpanExporter** - Export spans to Laravel database
2. **GenAI Instrumentor** - Automatic instrumentation for LLM calls
3. **Laravel Service Provider** - Bootstrap tracing in Laravel
4. **Configuration** - Config file for exporters and sampling
5. **Eloquent Models** - Trace and Span models for querying
6. **Artisan Commands** - Export, prune, and analyze traces
7. **Facade** - Laravel facade for easy access

## License

MIT License - see main project LICENSE file.
