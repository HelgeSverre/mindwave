# OpenTelemetry Exporters Usage Guide

This document demonstrates how to use the OTLP and Multi-exporter classes for sending traces to various observability backends.

## OtlpExporterFactory

The `OtlpExporterFactory` creates OTLP exporters that send traces to OpenTelemetry-compatible backends.

### Basic Usage

```php
use Mindwave\Mindwave\Observability\Tracing\Exporters\OtlpExporterFactory;

$factory = new OtlpExporterFactory();

// Create HTTP exporter (most common)
$exporter = $factory->createHttpExporter(
    endpoint: 'http://localhost:4318',
    headers: ['X-API-Key' => 'your-api-key'],
    timeoutMs: 10000
);
```

### Create from Configuration

```php
// Using Laravel config from config/mindwave-tracing.php
$config = config('mindwave-tracing.otlp');

$exporter = $factory->fromConfig($config);
```

### Create from Environment Variables

```php
// Automatically reads OTEL_* environment variables
$exporter = $factory->createFromEnvironment();
```

### Supported Backends

#### Jaeger

```php
$exporter = $factory->createHttpExporter(
    endpoint: 'http://localhost:4318'
);
```

#### Grafana Tempo

```php
$exporter = $factory->createHttpExporter(
    endpoint: 'http://tempo:4318',
    headers: ['X-Scope-OrgID' => 'tenant1']
);
```

#### Honeycomb

```php
$exporter = $factory->createHttpExporter(
    endpoint: 'https://api.honeycomb.io',
    headers: ['x-honeycomb-team' => 'your-api-key']
);
```

#### Datadog (via Agent)

```php
$exporter = $factory->createHttpExporter(
    endpoint: 'http://localhost:4318'
);
```

### gRPC Protocol

For better performance with gRPC (requires grpc PHP extension):

```php
$exporter = $factory->createGrpcExporter(
    endpoint: 'localhost:4317',
    headers: [],
    timeoutMs: 10000
);
```

## MultiExporter

The `MultiExporter` fans out traces to multiple backends simultaneously.

### Basic Usage

```php
use Mindwave\Mindwave\Observability\Tracing\Exporters\MultiExporter;
use Mindwave\Mindwave\Observability\Tracing\Exporters\OtlpExporterFactory;
use Mindwave\Mindwave\Observability\Tracing\Exporters\DatabaseSpanExporter;

$factory = new OtlpExporterFactory();

// Create multiple exporters
$exporters = [
    new DatabaseSpanExporter(),  // Local database
    $factory->createHttpExporter('http://localhost:4318'),  // Jaeger
    $factory->createHttpExporter('http://tempo:4318'),  // Grafana Tempo
];

// Combine them
$multiExporter = new MultiExporter(
    exporters: $exporters,
    logger: logger(),
    failOnAllErrors: false  // Continue even if all backends fail
);
```

### Using with TracerProvider

```php
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;

$processor = new BatchSpanProcessor(
    exporter: $multiExporter,
    // ... batch processor config
);

$tracerProvider = new TracerProvider(
    processors: [$processor]
);
```

### Failure Handling

By default, MultiExporter succeeds if ANY backend succeeds:

```php
// Lenient mode (default) - succeeds if at least one backend works
$multiExporter = new MultiExporter(
    exporters: $exporters,
    failOnAllErrors: false
);

// Strict mode - fails if ALL backends fail
$multiExporter = new MultiExporter(
    exporters: $exporters,
    failOnAllErrors: true
);
```

### Statistics

Track export performance:

```php
$stats = $multiExporter->getStats();
// [
//     'total_exports' => 100,
//     'successful_exports' => 150,  // Can be > total_exports (multiple backends)
//     'failed_exports' => 0,
//     'total_spans_exported' => 1500,
// ]

// Reset statistics
$multiExporter->resetStats();
```

## Complete Example: Laravel Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Mindwave\Mindwave\Observability\Tracing\Exporters\OtlpExporterFactory;
use Mindwave\Mindwave\Observability\Tracing\Exporters\MultiExporter;
use Mindwave\Mindwave\Observability\Tracing\Exporters\DatabaseSpanExporter;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;

class TracingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('tracing.exporter', function ($app) {
            $exporters = [];

            // Database exporter (always enabled)
            if (config('mindwave-tracing.database.enabled', true)) {
                $exporters[] = new DatabaseSpanExporter();
            }

            // OTLP exporter (optional)
            if (config('mindwave-tracing.otlp.enabled', false)) {
                $factory = new OtlpExporterFactory(logger());
                $exporters[] = $factory->fromConfig(
                    config('mindwave-tracing.otlp')
                );
            }

            // If multiple exporters, use MultiExporter
            if (count($exporters) > 1) {
                return new MultiExporter(
                    exporters: $exporters,
                    logger: logger(),
                    failOnAllErrors: false
                );
            }

            return $exporters[0];
        });

        $this->app->singleton(TracerProvider::class, function ($app) {
            $exporter = $app->make('tracing.exporter');

            $processor = new BatchSpanProcessor(
                exporter: $exporter,
                // ... config from mindwave-tracing.batch
            );

            return new TracerProvider(
                processors: [$processor]
            );
        });
    }
}
```

## Environment Variables

The OTLP exporter factory supports standard OpenTelemetry environment variables:

```bash
# Endpoint
OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:4318
OTEL_EXPORTER_OTLP_TRACES_ENDPOINT=http://localhost:4318/v1/traces

# Protocol
OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf  # or 'grpc'

# Headers (comma-separated key=value pairs)
OTEL_EXPORTER_OTLP_HEADERS=x-api-key=secret,x-tenant=acme

# Timeout (in seconds)
OTEL_EXPORTER_OTLP_TIMEOUT=10
```

## Configuration File

Example `config/mindwave-tracing.php`:

```php
return [
    'otlp' => [
        'enabled' => env('MINDWAVE_TRACE_OTLP_ENABLED', false),
        'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),
        'protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'),
        'headers' => [
            'X-API-Key' => env('OTLP_API_KEY'),
        ],
        'timeout_ms' => 10000,
    ],

    'database' => [
        'enabled' => env('MINDWAVE_TRACE_DATABASE', true),
    ],
];
```

## Testing

### Mock Exporter for Testing

```php
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Common\Future\CompletedFuture;

class InMemorySpanExporter implements SpanExporterInterface
{
    public array $exportedSpans = [];

    public function export(iterable $batch, $cancellation = null)
    {
        $this->exportedSpans = array_merge(
            $this->exportedSpans,
            iterator_to_array($batch)
        );
        return new CompletedFuture(true);
    }

    public function shutdown($cancellation = null): bool { return true; }
    public function forceFlush($cancellation = null): bool { return true; }
}

// Use in tests
$exporter = new InMemorySpanExporter();
$multiExporter = new MultiExporter([$exporter]);

// ... trace some operations ...

assert(count($exporter->exportedSpans) > 0);
```

## Troubleshooting

### Enable Debug Logging

```php
use Psr\Log\LogLevel;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('tracing');
$logger->pushHandler(new StreamHandler('php://stdout', LogLevel::DEBUG));

$factory = new OtlpExporterFactory($logger);
$multiExporter = new MultiExporter($exporters, $logger);
```

### Common Issues

**Issue: Connection refused**
- Ensure OTLP endpoint is reachable
- Check firewall rules
- Verify endpoint URL includes correct port

**Issue: Protobuf errors**
- Ensure `ext-protobuf` or `google/protobuf` package is installed
- Run: `composer require google/protobuf`

**Issue: gRPC not available**
- Install grpc extension: `pecl install grpc`
- Enable in php.ini: `extension=grpc.so`

**Issue: Spans not appearing**
- Check if batch processor delay is too long
- Call `forceFlush()` to export immediately
- Verify exporter configuration
