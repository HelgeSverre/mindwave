# OpenTelemetry Tracing Examples

Mindwave provides production-grade OpenTelemetry tracing for all LLM operations using GenAI semantic conventions.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Automatic Tracing](#automatic-tracing)
3. [Querying Traces](#querying-traces)
4. [Cost Analysis](#cost-analysis)
5. [Custom Spans](#custom-spans)
6. [OTLP Exporters](#otlp-exporters)
7. [Privacy & Security](#privacy--security)
8. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Installation

1. Publish the migrations and configuration:

```bash
php artisan vendor:publish --tag=mindwave-migrations
php artisan vendor:publish --tag=mindwave-config
```

2. Run migrations to create the traces tables:

```bash
php artisan migrate
```

3. Configure in `.env`:

```env
MINDWAVE_TRACING_ENABLED=true
MINDWAVE_TRACING_SERVICE_NAME=my-app
MINDWAVE_TRACING_SAMPLE_RATE=1.0
```

### Basic Configuration

```php
// config/mindwave-tracing.php

return [
    'enabled' => env('MINDWAVE_TRACING_ENABLED', true),

    'service_name' => env('MINDWAVE_TRACING_SERVICE_NAME', 'mindwave-app'),

    'database' => [
        'enabled' => true,
        'connection' => env('DB_CONNECTION', 'mysql'),
    ],

    'sampling' => [
        'rate' => env('MINDWAVE_TRACING_SAMPLE_RATE', 1.0),
    ],
];
```

---

## Automatic Tracing

All LLM calls are automatically traced with zero configuration required!

### Example: Simple LLM Call

```php
use Mindwave\Mindwave\Facades\Mindwave;

// This LLM call is automatically traced
$response = Mindwave::llm()
    ->generateText('What is Laravel?');

// Behind the scenes, Mindwave:
// 1. Creates a trace and span
// 2. Records request parameters (model, temperature, etc.)
// 3. Tracks token usage
// 4. Estimates cost
// 5. Stores everything in the database
```

### What Gets Traced Automatically

Every LLM call captures:

- **Trace ID** - Unique identifier for the operation
- **Span ID** - Unique identifier for this specific LLM call
- **Provider** - `openai`, `mistral`, `anthropic`, etc.
- **Model** - `gpt-5`, `gpt-4-turbo`, etc.
- **Operation** - `chat`, `completion`, `embeddings`
- **Timestamps** - Start, end, duration (nanoseconds)
- **Token Usage** - Input tokens, output tokens, total
- **Cost** - Estimated cost in USD
- **Status** - `ok`, `error`
- **Messages** (optional) - Prompts and responses (PII-aware)

---

## Querying Traces

Use Eloquent models to query your traces.

### Find Recent LLM Calls

```php
use Mindwave\Mindwave\Observability\Models\Trace;

// Get the last 10 LLM calls
$traces = Trace::with('spans')
    ->orderBy('start_time', 'desc')
    ->limit(10)
    ->get();

foreach ($traces as $trace) {
    echo "Trace ID: {$trace->trace_id}\n";
    echo "Duration: {$trace->duration_ms}ms\n";
    echo "Spans: {$trace->spans->count()}\n\n";
}
```

### Find Expensive Queries

```php
use Mindwave\Mindwave\Observability\Models\Span;

// Find LLM calls that cost more than $0.10
$expensive = Span::where('cost_usd', '>', 0.10)
    ->with('trace')
    ->orderBy('cost_usd', 'desc')
    ->get();

foreach ($expensive as $span) {
    echo "Model: {$span->request_model}\n";
    echo "Cost: \${$span->cost_usd}\n";
    echo "Tokens: {$span->total_tokens}\n\n";
}
```

### Find Slow Requests

```php
// Find requests that took longer than 5 seconds
$slow = Span::slow(5000)->get(); // 5000ms = 5 seconds

foreach ($slow as $span) {
    echo "Model: {$span->request_model}\n";
    echo "Duration: {$span->duration_ms}ms\n";
    echo "Provider: {$span->provider_name}\n\n";
}
```

### Group by Provider

```php
// Get cost breakdown by provider
$costByProvider = Span::selectRaw('provider_name, SUM(cost_usd) as total_cost, COUNT(*) as count')
    ->groupBy('provider_name')
    ->get();

foreach ($costByProvider as $provider) {
    echo "{$provider->provider_name}: \${$provider->total_cost} ({$provider->count} calls)\n";
}
```

### Group by Model

```php
// Get usage breakdown by model
$usageByModel = Span::selectRaw('request_model, SUM(total_tokens) as total_tokens, AVG(duration) as avg_duration')
    ->groupBy('request_model')
    ->get();

foreach ($usageByModel as $model) {
    $avgMs = $model->avg_duration / 1_000_000; // Convert nanoseconds to ms
    echo "{$model->request_model}: {$model->total_tokens} tokens, {$avgMs}ms avg\n";
}
```

---

## Cost Analysis

### Daily Spending Report

```php
use Mindwave\Mindwave\Observability\Models\Span;
use Carbon\Carbon;

$today = Carbon::today();

$todayCost = Span::whereDate('created_at', $today)
    ->sum('cost_usd');

echo "Today's LLM spend: \${$todayCost}\n";
```

### Monthly Cost Breakdown

```php
$thisMonth = Span::whereMonth('created_at', now()->month)
    ->whereYear('created_at', now()->year)
    ->selectRaw('
        DATE(created_at) as date,
        SUM(cost_usd) as daily_cost,
        SUM(total_tokens) as daily_tokens,
        COUNT(*) as daily_calls
    ')
    ->groupBy('date')
    ->orderBy('date')
    ->get();

foreach ($thisMonth as $day) {
    echo "{$day->date}: \${$day->daily_cost} ({$day->daily_calls} calls, {$day->daily_tokens} tokens)\n";
}
```

### Cost Comparison: OpenAI vs Mistral

```php
$comparison = Span::selectRaw('
        provider_name,
        SUM(cost_usd) as total_cost,
        COUNT(*) as calls,
        AVG(cost_usd) as avg_cost_per_call
    ')
    ->whereIn('provider_name', ['openai', 'mistral'])
    ->groupBy('provider_name')
    ->get();

foreach ($comparison as $provider) {
    echo "{$provider->provider_name}:\n";
    echo "  Total: \${$provider->total_cost}\n";
    echo "  Calls: {$provider->calls}\n";
    echo "  Avg/call: \${$provider->avg_cost_per_call}\n\n";
}
```

### Budget Alerting

```php
use Mindwave\Mindwave\Observability\Events\LlmResponseCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

// Listen for expensive calls
Event::listen(LlmResponseCompleted::class, function ($event) {
    if ($event->span->cost_usd > 1.00) {
        Log::warning('Expensive LLM call detected', [
            'cost' => $event->span->cost_usd,
            'model' => $event->span->request_model,
            'tokens' => $event->span->total_tokens,
        ]);

        // Send alert, notify Slack, etc.
    }
});
```

---

## Custom Spans

Create manual spans for custom operations.

### Basic Custom Span

```php
use Mindwave\Mindwave\Observability\Tracing\TracerManager;

$tracer = app(TracerManager::class);

$span = $tracer->spanBuilder('custom-operation')
    ->setAttribute('user_id', auth()->id())
    ->setAttribute('action', 'export_data')
    ->start();

try {
    // Your custom logic here
    $result = performExpensiveOperation();

    $span->setAttribute('result_count', count($result));
    $span->setStatus('ok');
} catch (\Exception $e) {
    $span->recordException($e);
    $span->setStatus('error');
    throw $e;
} finally {
    $span->end();
}
```

### Parent-Child Span Relationships

```php
$tracer = app(TracerManager::class);

// Parent span
$parentSpan = $tracer->spanBuilder('batch-process')
    ->start();

$context = $parentSpan->context();

// Child spans
foreach ($items as $item) {
    $childSpan = $tracer->spanBuilder('process-item')
        ->setParent($context) // Link to parent
        ->setAttribute('item_id', $item->id)
        ->start();

    processItem($item);

    $childSpan->end();
}

$parentSpan->end();
```

### Wrapping Code in Spans

```php
use Mindwave\Mindwave\Observability\Tracing\Span;

$result = Span::wrap('database-query', function () use ($query) {
    return DB::table('users')->where('active', true)->get();
}, [
    'query_type' => 'select',
    'table' => 'users',
]);
```

### GenAI Custom Spans

```php
$span = $tracer->spanBuilder()
    ->createChatSpan(
        provider: 'custom-provider',
        model: 'my-model-v1'
    );

$span->setGenAiRequestParameters([
    'temperature' => 0.7,
    'max_tokens' => 1000,
]);

$span->setGenAiTokenUsage(
    inputTokens: 150,
    outputTokens: 300
);

$span->end();
```

---

## OTLP Exporters

Send traces to production observability platforms.

### Jaeger Setup

1. Run Jaeger locally:

```bash
docker run -d --name jaeger \
  -p 4317:4317 \
  -p 4318:4318 \
  -p 16686:16686 \
  jaegertracing/all-in-one:latest
```

2. Configure in `.env`:

```env
MINDWAVE_OTLP_ENABLED=true
MINDWAVE_OTLP_ENDPOINT=http://localhost:4318
MINDWAVE_OTLP_PROTOCOL=http
```

3. View traces: http://localhost:16686

### Grafana Tempo Setup

```env
MINDWAVE_OTLP_ENABLED=true
MINDWAVE_OTLP_ENDPOINT=http://tempo:4318
MINDWAVE_OTLP_PROTOCOL=http
MINDWAVE_OTLP_HEADERS='{"X-Scope-OrgID":"tenant1"}'
```

### Honeycomb Setup

```env
MINDWAVE_OTLP_ENABLED=true
MINDWAVE_OTLP_ENDPOINT=https://api.honeycomb.io:443
MINDWAVE_OTLP_PROTOCOL=http
MINDWAVE_OTLP_HEADERS='{"x-honeycomb-team":"YOUR_API_KEY","x-honeycomb-dataset":"mindwave"}'
```

### Datadog Setup (via OpenTelemetry Collector)

```env
MINDWAVE_OTLP_ENABLED=true
MINDWAVE_OTLP_ENDPOINT=http://otel-collector:4318
MINDWAVE_OTLP_PROTOCOL=http
```

### Multi-Exporter (Database + OTLP)

```php
// config/mindwave-tracing.php

'exporters' => [
    'database' => [
        'enabled' => true,
    ],

    'otlp' => [
        'enabled' => true,
        'endpoint' => env('MINDWAVE_OTLP_ENDPOINT'),
        'protocol' => env('MINDWAVE_OTLP_PROTOCOL', 'http'),
    ],
],
```

Both exporters run simultaneously - database for local queries, OTLP for distributed tracing!

---

## Privacy & Security

### PII Redaction

Configure what data is captured:

```php
// config/mindwave-tracing.php

'privacy' => [
    'capture_messages' => env('MINDWAVE_CAPTURE_MESSAGES', false), // Default: false

    'pii_redaction' => [
        'enabled' => true,
        'patterns' => [
            '/\b[\w\.-]+@[\w\.-]+\.\w{2,4}\b/', // Email addresses
            '/\b\d{3}-\d{2}-\d{4}\b/',          // SSN
            '/\b\d{16}\b/',                     // Credit card numbers
        ],
    ],
],
```

### Production Best Practices

**Development:**
```env
MINDWAVE_TRACING_ENABLED=true
MINDWAVE_CAPTURE_MESSAGES=true  # OK for local dev
MINDWAVE_TRACING_SAMPLE_RATE=1.0
```

**Production:**
```env
MINDWAVE_TRACING_ENABLED=true
MINDWAVE_CAPTURE_MESSAGES=false  # Protect user privacy
MINDWAVE_TRACING_SAMPLE_RATE=0.1  # Sample 10% to reduce overhead
```

### Sampling Strategies

```php
// config/mindwave-tracing.php

'sampling' => [
    'rate' => 0.1, // Sample 10% of traces

    // Or use AlwaysOnSampler for critical operations
    'sampler' => \OpenTelemetry\Sdk\Trace\Sampler\AlwaysOnSampler::class,
],
```

---

## Artisan Commands

### Export Traces

```bash
# Export to JSON
php artisan mindwave:export-traces --format=json --output=traces.json

# Export to CSV
php artisan mindwave:export-traces --format=csv --output=traces.csv

# Export with filters
php artisan mindwave:export-traces \
    --provider=openai \
    --since="2025-01-01" \
    --format=json
```

### Prune Old Traces

```bash
# Delete traces older than 30 days
php artisan mindwave:prune-traces --days=30

# Dry run (see what would be deleted)
php artisan mindwave:prune-traces --days=30 --dry-run
```

### Trace Statistics

```bash
# View trace statistics
php artisan mindwave:trace-stats

# Output:
# Total traces: 1,234
# Total spans: 2,456
# Total cost: $45.67
# Providers: openai (80%), mistral (20%)
```

---

## Troubleshooting

### Traces Not Appearing in Database

**Check configuration:**
```php
config('mindwave-tracing.enabled'); // Should be true
config('mindwave-tracing.database.enabled'); // Should be true
```

**Check migrations:**
```bash
php artisan migrate:status | grep mindwave
```

**Check database connection:**
```php
DB::table('mindwave_traces')->count(); // Should not throw error
```

### OTLP Export Failing

**Check endpoint:**
```bash
curl http://localhost:4318/v1/traces
```

**Enable debug logging:**
```env
LOG_LEVEL=debug
```

**Check exporter configuration:**
```php
config('mindwave-tracing.exporters.otlp');
```

### High Memory Usage

**Reduce sampling rate:**
```env
MINDWAVE_TRACING_SAMPLE_RATE=0.1  # Sample only 10%
```

**Disable message capture:**
```env
MINDWAVE_CAPTURE_MESSAGES=false
```

**Enable batch processing:**
```php
'batch' => [
    'enabled' => true,
    'max_queue_size' => 2048,
    'schedule_delay_millis' => 5000,
    'max_export_batch_size' => 512,
],
```

### Slow Performance

**Check batch settings:**
- Increase `schedule_delay_millis` to batch more aggressively
- Reduce `max_export_batch_size` for smaller batches

**Use async export:**
```php
'async' => true, // Export in background queue
```

**Disable database exporter in production:**
```php
'database' => [
    'enabled' => false, // Use only OTLP in prod
],
```

---

## Advanced Examples

### Correlation with Laravel Logs

```php
use Illuminate\Support\Facades\Log;
use Mindwave\Mindwave\Observability\Tracing\TracerManager;

$tracer = app(TracerManager::class);
$span = $tracer->getCurrentSpan();

Log::info('Processing user request', [
    'trace_id' => $span?->getContext()->getTraceId(),
    'span_id' => $span?->getContext()->getSpanId(),
    'user_id' => auth()->id(),
]);
```

### Custom Metrics from Traces

```php
use Mindwave\Mindwave\Observability\Models\Span;

// Calculate average response time per model
$metrics = Span::selectRaw('
        request_model,
        AVG(duration) as avg_duration,
        PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY duration) as p50,
        PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration) as p95,
        PERCENTILE_CONT(0.99) WITHIN GROUP (ORDER BY duration) as p99
    ')
    ->whereDate('created_at', today())
    ->groupBy('request_model')
    ->get();
```

### Distributed Tracing Across Services

```php
// Service A: Create trace
$tracer = app(TracerManager::class);
$span = $tracer->spanBuilder('api-call')->start();
$context = $span->context();

// Pass trace context to Service B via HTTP headers
Http::withHeaders([
    'traceparent' => $context->getTraceParent(),
])->post('https://service-b.com/api/process');

$span->end();

// Service B: Continue the trace
// OpenTelemetry automatically propagates context from headers!
```

---

## Resources

- [TRACING_ARCHITECTURE.md](../TRACING_ARCHITECTURE.md) - Deep dive into architecture
- [OpenTelemetry GenAI Conventions](https://github.com/open-telemetry/semantic-conventions/tree/main/docs/gen-ai)
- [Jaeger Documentation](https://www.jaegertracing.io/docs/)
- [Grafana Tempo Documentation](https://grafana.com/docs/tempo/)
- [Honeycomb Documentation](https://docs.honeycomb.io/)

---

**Last Updated:** November 18, 2025
**Version:** 1.0
**Feedback:** [GitHub Issues](https://github.com/helgesverre/mindwave/issues)
