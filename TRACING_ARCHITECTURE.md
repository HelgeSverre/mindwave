# Mindwave Tracing Architecture: OpenTelemetry GenAI Implementation

**Goal:** Provide production-grade LLM observability using OpenTelemetry standards with dual storage (OTLP exporters + database).

---

## 🎯 Design Principles

1. **Standards-First** - Follow OpenTelemetry GenAI semantic conventions exactly
2. **Zero-Config Default** - Works out of the box with database storage
3. **Production-Ready** - OTLP exporters for Jaeger, Grafana, Datadog, etc.
4. **Laravel-Native** - Eloquent models, migrations, events, commands
5. **Minimal Overhead** - Async batch processing, sampling, efficient storage

---

## 📊 OpenTelemetry GenAI Semantic Conventions

### Standard Span Structure

```
Span Name: "{gen_ai.operation.name} {gen_ai.request.model}"
Example: "chat gpt-4"

Attributes (Required):
- gen_ai.operation.name: "chat" | "text_completion" | "embeddings" | "execute_tool"
- gen_ai.provider.name: "openai" | "mistral_ai" | "anthropic" | "gcp.gemini"

Request Attributes:
- gen_ai.request.model: "gpt-4"
- gen_ai.request.max_tokens: 100
- gen_ai.request.temperature: 0.7
- gen_ai.request.top_p: 1.0
- gen_ai.request.frequency_penalty: 0.0
- gen_ai.request.presence_penalty: 0.0

Response Attributes:
- gen_ai.response.id: "chatcmpl-123"
- gen_ai.response.model: "gpt-4-0613"
- gen_ai.response.finish_reasons: ["stop"]
- gen_ai.usage.input_tokens: 100
- gen_ai.usage.output_tokens: 50
- llm.usage.total_tokens: 150

Server Attributes:
- server.address: "api.openai.com"
- server.port: 443

Optional (Opt-in for sensitive data):
- gen_ai.system_instructions: [...]
- gen_ai.input.messages: [...]
- gen_ai.output.messages: [...]
```

### Trace Hierarchy Example

```
[TRACE: abc123...] User Query Processing
├─ [SPAN: def456] chat gpt-4
│  ├─ Attributes: model=gpt-4, temperature=0.7, tokens=150
│  └─ Duration: 2.3s
├─ [SPAN: ghi789] execute_tool get_weather
│  ├─ Attributes: tool_name=get_weather, arguments={...}
│  └─ Duration: 0.5s
└─ [SPAN: jkl012] chat gpt-4
   ├─ Attributes: model=gpt-4, tokens=200
   └─ Duration: 1.8s
```

---

## 🏗️ Architecture Layers

### Layer 1: OpenTelemetry SDK (open-telemetry/opentelemetry-php)

```php
// TracerProvider initialization
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;

$tracerProvider = new TracerProvider(
    new BatchSpanProcessor(
        $exporter,
        ClockFactory::getDefault(),
        2048,  // maxQueueSize
        5000,  // scheduledDelayMillis
        512,   // exportTimeoutMillis
        256    // maxExportBatchSize
    )
);
```

### Layer 2: Mindwave Tracer Manager

```php
namespace Mindwave\Mindwave\Observability\Tracing;

class TracerManager
{
    private TracerProvider $tracerProvider;
    private array $exporters = [];
    
    public function __construct()
    {
        $this->exporters = $this->createExporters();
        $this->tracerProvider = $this->createTracerProvider();
    }
    
    private function createExporters(): array
    {
        $exporters = [];
        
        // Database exporter (always enabled by default)
        if (config('mindwave-tracing.database.enabled', true)) {
            $exporters[] = new DatabaseSpanExporter();
        }
        
        // OTLP HTTP/gRPC exporter
        if (config('mindwave-tracing.otlp.enabled', false)) {
            $exporters[] = $this->createOtlpExporter();
        }
        
        // Custom exporters from config
        foreach (config('mindwave-tracing.exporters', []) as $exporter) {
            $exporters[] = app($exporter);
        }
        
        return $exporters;
    }
    
    public function startSpan(string $name, array $attributes = []): Span
    {
        $tracer = $this->tracerProvider->getTracer('mindwave.llm');
        
        $spanBuilder = $tracer->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_CLIENT);
        
        foreach ($attributes as $key => $value) {
            $spanBuilder->setAttribute($key, $value);
        }
        
        return new Span($spanBuilder->startSpan());
    }
}
```

### Layer 3: GenAI Instrumentor (Wraps LLM Calls)

```php
namespace Mindwave\Mindwave\Observability\Tracing\GenAI;

class GenAiInstrumentor
{
    private TracerManager $tracerManager;
    
    public function instrumentChatCompletion(
        string $provider,
        string $model,
        array $messages,
        array $options,
        callable $execute
    ): mixed {
        $span = $this->tracerManager->startSpan(
            "chat {$model}",
            [
                GenAiAttributes::GEN_AI_OPERATION_NAME => 'chat',
                GenAiAttributes::GEN_AI_PROVIDER_NAME => $provider,
                GenAiAttributes::GEN_AI_REQUEST_MODEL => $model,
                GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE => $options['temperature'] ?? null,
                GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS => $options['max_tokens'] ?? null,
                GenAiAttributes::GEN_AI_REQUEST_TOP_P => $options['top_p'] ?? null,
                'server.address' => $this->getServerAddress($provider),
            ]
        );
        
        $scope = $span->activate();
        
        try {
            // Execute the actual LLM call
            $response = $execute();
            
            // Add response attributes
            $span->setAttributes([
                GenAiAttributes::GEN_AI_RESPONSE_ID => $response->id,
                GenAiAttributes::GEN_AI_RESPONSE_MODEL => $response->model,
                GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS => $response->usage->promptTokens,
                GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS => $response->usage->completionTokens,
                GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS => [$response->choices[0]->finishReason],
            ]);
            
            // Optionally capture messages (opt-in)
            if (config('mindwave-tracing.capture_messages', false)) {
                $span->setAttribute(GenAiAttributes::GEN_AI_INPUT_MESSAGES, $messages);
                $span->setAttribute(GenAiAttributes::GEN_AI_OUTPUT_MESSAGES, [
                    [
                        'role' => 'assistant',
                        'content' => $response->choices[0]->message->content,
                    ]
                ]);
            }
            
            return $response;
            
        } catch (\Throwable $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
```

### Layer 4: Database Storage

#### Schema Design

```sql
-- Traces table (one per request/conversation)
CREATE TABLE mindwave_traces (
    id CHAR(36) PRIMARY KEY,
    trace_id CHAR(32) UNIQUE NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    start_time BIGINT UNSIGNED NOT NULL,
    end_time BIGINT UNSIGNED,
    duration BIGINT UNSIGNED,
    status VARCHAR(20) NOT NULL, -- ok, error, unset
    root_span_id CHAR(16),
    total_spans INT UNSIGNED DEFAULT 0,
    total_input_tokens INT UNSIGNED DEFAULT 0,
    total_output_tokens INT UNSIGNED DEFAULT 0,
    estimated_cost DECIMAL(10, 6) DEFAULT 0,
    metadata JSON,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP,
    
    INDEX idx_trace_id (trace_id),
    INDEX idx_service_created (service_name, created_at),
    INDEX idx_duration (duration),
    INDEX idx_cost (estimated_cost),
    INDEX idx_status (status)
);

-- Spans table (LLM calls, tool executions, etc.)
CREATE TABLE mindwave_spans (
    id CHAR(36) PRIMARY KEY,
    trace_id CHAR(32) NOT NULL,
    span_id CHAR(16) UNIQUE NOT NULL,
    parent_span_id CHAR(16),
    name VARCHAR(500) NOT NULL,
    kind VARCHAR(20) NOT NULL, -- client, server, internal, producer, consumer
    start_time BIGINT UNSIGNED NOT NULL,
    end_time BIGINT UNSIGNED,
    duration BIGINT UNSIGNED,
    
    -- GenAI specific attributes
    operation_name VARCHAR(50), -- chat, embeddings, execute_tool
    provider_name VARCHAR(50), -- openai, anthropic, etc.
    request_model VARCHAR(100),
    response_model VARCHAR(100),
    
    input_tokens INT UNSIGNED,
    output_tokens INT UNSIGNED,
    cache_read_tokens INT UNSIGNED,
    cache_creation_tokens INT UNSIGNED,
    
    temperature DECIMAL(3, 2),
    max_tokens INT UNSIGNED,
    top_p DECIMAL(3, 2),
    
    finish_reasons JSON,
    
    -- Status
    status_code VARCHAR(20) NOT NULL,
    status_description TEXT,
    
    -- Full attributes (all other attributes as JSON)
    attributes JSON,
    
    -- Events (for special occurrences during span)
    events JSON,
    
    -- Links (to other spans)
    links JSON,
    
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_trace_id (trace_id),
    INDEX idx_span_id (span_id),
    INDEX idx_parent (parent_span_id),
    INDEX idx_name (name(255)),
    INDEX idx_operation (operation_name, provider_name),
    INDEX idx_model (request_model),
    INDEX idx_tokens (input_tokens, output_tokens),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (trace_id) REFERENCES mindwave_traces(trace_id) ON DELETE CASCADE
);

-- Span messages (opt-in, separate table for large content)
CREATE TABLE mindwave_span_messages (
    id CHAR(36) PRIMARY KEY,
    span_id CHAR(16) NOT NULL,
    type VARCHAR(20) NOT NULL, -- input, output, system
    messages JSON NOT NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_span_id (span_id),
    FOREIGN KEY (span_id) REFERENCES mindwave_spans(span_id) ON DELETE CASCADE
);
```

#### Eloquent Models

```php
namespace Mindwave\Mindwave\Observability\Models;

class Trace extends Model
{
    protected $table = 'mindwave_traces';
    
    protected $casts = [
        'start_time' => 'integer',
        'end_time' => 'integer',
        'duration' => 'integer',
        'total_input_tokens' => 'integer',
        'total_output_tokens' => 'integer',
        'estimated_cost' => 'decimal:6',
        'metadata' => 'array',
    ];
    
    public function spans()
    {
        return $this->hasMany(Span::class, 'trace_id', 'trace_id');
    }
    
    public function rootSpan()
    {
        return $this->hasOne(Span::class, 'span_id', 'root_span_id');
    }
    
    public function scopeSlow($query, int $thresholdMs = 5000)
    {
        return $query->where('duration', '>', $thresholdMs * 1000000); // Convert to ns
    }
    
    public function scopeExpensive($query, float $minCost = 0.01)
    {
        return $query->where('estimated_cost', '>', $minCost);
    }
}

class Span extends Model
{
    protected $table = 'mindwave_spans';
    
    protected $casts = [
        'start_time' => 'integer',
        'end_time' => 'integer',
        'duration' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'top_p' => 'float',
        'attributes' => 'array',
        'events' => 'array',
        'links' => 'array',
        'finish_reasons' => 'array',
    ];
    
    public function trace()
    {
        return $this->belongsTo(Trace::class, 'trace_id', 'trace_id');
    }
    
    public function parent()
    {
        return $this->belongsTo(Span::class, 'parent_span_id', 'span_id');
    }
    
    public function children()
    {
        return $this->hasMany(Span::class, 'parent_span_id', 'span_id');
    }
    
    public function messages()
    {
        return $this->hasMany(SpanMessage::class, 'span_id', 'span_id');
    }
    
    public function getDurationInSeconds(): float
    {
        return $this->duration / 1_000_000_000;
    }
    
    public function getTotalTokens(): int
    {
        return ($this->input_tokens ?? 0) + ($this->output_tokens ?? 0);
    }
}
```

### Layer 5: Database Exporter Implementation

```php
namespace Mindwave\Mindwave\Observability\Tracing\Exporters;

use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use Illuminate\Support\Facades\DB;

class DatabaseSpanExporter implements SpanExporterInterface
{
    private array $tracesBuffer = [];
    private array $spansBuffer = [];
    
    public function export(iterable $batch): int
    {
        $exported = 0;
        
        foreach ($batch as $span) {
            try {
                $this->bufferSpan($span);
                $exported++;
            } catch (\Throwable $e) {
                Log::error('Failed to buffer span', [
                    'span_id' => $span->getSpanId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Batch insert
        $this->flush();
        
        return $exported;
    }
    
    private function bufferSpan(SpanDataInterface $span): void
    {
        $traceId = $span->getContext()->getTraceId();
        
        // Buffer trace (will upsert)
        if (!isset($this->tracesBuffer[$traceId])) {
            $this->tracesBuffer[$traceId] = $this->createTraceRecord($span);
        } else {
            // Update end time and duration
            $this->updateTraceRecord($traceId, $span);
        }
        
        // Buffer span
        $this->spansBuffer[] = $this->createSpanRecord($span);
    }
    
    private function createTraceRecord(SpanDataInterface $span): array
    {
        $traceId = $span->getContext()->getTraceId();
        
        return [
            'id' => Str::uuid()->toString(),
            'trace_id' => $traceId,
            'service_name' => $this->getServiceName($span),
            'start_time' => $span->getStartEpochNanos(),
            'end_time' => $span->getEndEpochNanos(),
            'duration' => $span->getEndEpochNanos() - $span->getStartEpochNanos(),
            'status' => $span->getStatus()->getCode(),
            'root_span_id' => $span->getParentContext()->isValid() 
                ? null 
                : $span->getContext()->getSpanId(),
            'total_spans' => 1,
            'total_input_tokens' => $this->getInputTokens($span),
            'total_output_tokens' => $this->getOutputTokens($span),
            'estimated_cost' => $this->estimateCost($span),
            'metadata' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    private function createSpanRecord(SpanDataInterface $span): array
    {
        $attributes = iterator_to_array($span->getAttributes());
        
        return [
            'id' => Str::uuid()->toString(),
            'trace_id' => $span->getContext()->getTraceId(),
            'span_id' => $span->getContext()->getSpanId(),
            'parent_span_id' => $span->getParentContext()->isValid() 
                ? $span->getParentContext()->getSpanId() 
                : null,
            'name' => $span->getName(),
            'kind' => $this->getSpanKind($span->getKind()),
            'start_time' => $span->getStartEpochNanos(),
            'end_time' => $span->getEndEpochNanos(),
            'duration' => $span->getEndEpochNanos() - $span->getStartEpochNanos(),
            
            // Extract GenAI attributes to columns
            'operation_name' => $attributes[GenAiAttributes::GEN_AI_OPERATION_NAME] ?? null,
            'provider_name' => $attributes[GenAiAttributes::GEN_AI_PROVIDER_NAME] ?? null,
            'request_model' => $attributes[GenAiAttributes::GEN_AI_REQUEST_MODEL] ?? null,
            'response_model' => $attributes[GenAiAttributes::GEN_AI_RESPONSE_MODEL] ?? null,
            'input_tokens' => $attributes[GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS] ?? null,
            'output_tokens' => $attributes[GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS] ?? null,
            'temperature' => $attributes[GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE] ?? null,
            'max_tokens' => $attributes[GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS] ?? null,
            'top_p' => $attributes[GenAiAttributes::GEN_AI_REQUEST_TOP_P] ?? null,
            'finish_reasons' => isset($attributes[GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS])
                ? json_encode($attributes[GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS])
                : null,
            
            'status_code' => $span->getStatus()->getCode(),
            'status_description' => $span->getStatus()->getDescription(),
            
            // Store all attributes (redacted if configured)
            'attributes' => json_encode($this->redactSensitiveAttributes($attributes)),
            
            'events' => json_encode($span->getEvents()),
            'links' => json_encode($span->getLinks()),
            
            'created_at' => now(),
        ];
    }
    
    private function flush(): void
    {
        DB::transaction(function () {
            // Upsert traces
            if (!empty($this->tracesBuffer)) {
                DB::table('mindwave_traces')->upsert(
                    array_values($this->tracesBuffer),
                    ['trace_id'],
                    ['end_time', 'duration', 'status', 'total_spans', 
                     'total_input_tokens', 'total_output_tokens', 
                     'estimated_cost', 'updated_at']
                );
            }
            
            // Insert spans
            if (!empty($this->spansBuffer)) {
                foreach (array_chunk($this->spansBuffer, 500) as $chunk) {
                    DB::table('mindwave_spans')->insert($chunk);
                }
            }
        });
        
        // Clear buffers
        $this->tracesBuffer = [];
        $this->spansBuffer = [];
    }
    
    private function redactSensitiveAttributes(array $attributes): array
    {
        $redactKeys = config('mindwave-tracing.pii_redact', [
            GenAiAttributes::GEN_AI_INPUT_MESSAGES,
            GenAiAttributes::GEN_AI_OUTPUT_MESSAGES,
            GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS,
        ]);
        
        foreach ($redactKeys as $key) {
            if (isset($attributes[$key])) {
                $attributes[$key] = '[REDACTED]';
            }
        }
        
        return $attributes;
    }
    
    private function estimateCost(SpanDataInterface $span): float
    {
        // Cost estimation based on provider and model
        $attributes = iterator_to_array($span->getAttributes());
        
        $provider = $attributes[GenAiAttributes::GEN_AI_PROVIDER_NAME] ?? null;
        $model = $attributes[GenAiAttributes::GEN_AI_REQUEST_MODEL] ?? null;
        $inputTokens = $attributes[GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS] ?? 0;
        $outputTokens = $attributes[GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS] ?? 0;
        
        return app(CostEstimator::class)->estimate(
            $provider, 
            $model, 
            $inputTokens, 
            $outputTokens
        );
    }
}
```

---

## 🔧 Configuration

```php
// config/mindwave-tracing.php
return [
    'enabled' => env('MINDWAVE_TRACING_ENABLED', true),
    
    'service_name' => env('MINDWAVE_SERVICE_NAME', env('APP_NAME', 'laravel-app')),
    
    // Database storage
    'database' => [
        'enabled' => env('MINDWAVE_TRACE_DATABASE', true),
        'connection' => env('MINDWAVE_TRACE_DB_CONNECTION', null), // null = default
    ],
    
    // OTLP Exporter (Jaeger, Grafana Tempo, etc.)
    'otlp' => [
        'enabled' => env('MINDWAVE_TRACE_OTLP_ENABLED', false),
        'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),
        'protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'), // http/protobuf, grpc
        'headers' => [],
    ],
    
    // Sampling
    'sampler' => [
        'type' => env('MINDWAVE_TRACE_SAMPLER', 'always_on'), // always_on, always_off, traceidratio
        'ratio' => env('MINDWAVE_TRACE_SAMPLE_RATIO', 1.0), // For traceidratio sampler
    ],
    
    // Batch processing
    'batch' => [
        'max_queue_size' => 2048,
        'scheduled_delay_ms' => 5000,
        'export_timeout_ms' => 512,
        'max_export_batch_size' => 256,
    ],
    
    // Privacy & Security
    'capture_messages' => env('MINDWAVE_TRACE_CAPTURE_MESSAGES', false),
    'pii_redact' => [
        'gen_ai.input.messages',
        'gen_ai.output.messages',
        'gen_ai.system_instructions',
        'gen_ai.tool.call.arguments',
        'gen_ai.tool.call.result',
    ],
    
    // Retention
    'retention_days' => env('MINDWAVE_TRACE_RETENTION_DAYS', 30),
    
    // Cost estimation
    'cost_estimation' => [
        'enabled' => true,
        'pricing' => [
            'openai' => [
                'gpt-4' => ['input' => 0.03, 'output' => 0.06],
                'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
                'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
            ],
            'anthropic' => [
                'claude-3-opus' => ['input' => 0.015, 'output' => 0.075],
                'claude-3-sonnet' => ['input' => 0.003, 'output' => 0.015],
            ],
        ],
    ],
];
```

---

## 🎨 Usage Examples

### Basic Tracing (Automatic)

```php
// All LLM calls are automatically traced
$response = Mindwave::llm()->chat([
    ['role' => 'user', 'content' => 'Hello!']
]);

// Trace is automatically created with:
// - Span name: "chat gpt-3.5-turbo"
// - All GenAI attributes
// - Token usage
// - Cost estimation
```

### Manual Span Creation

```php
use Mindwave\Mindwave\Facades\Tracer;

$span = Tracer::startSpan('process-documents', [
    'document.count' => count($documents),
    'user.id' => auth()->id(),
]);

foreach ($documents as $doc) {
    $childSpan = Tracer::startSpan('process-document', [
        'document.id' => $doc->id,
    ]);
    
    // Process document
    processDocument($doc);
    
    $childSpan->end();
}

$span->end();
```

### Query Traces from Database

```php
use Mindwave\Mindwave\Observability\Models\Trace;
use Mindwave\Mindwave\Observability\Models\Span;

// Find expensive traces
$expensiveTraces = Trace::expensive(0.10)
    ->with('spans')
    ->orderByDesc('estimated_cost')
    ->take(10)
    ->get();

// Find slow LLM calls
$slowSpans = Span::where('operation_name', 'chat')
    ->where('duration', '>', 5_000_000_000) // 5 seconds
    ->with('trace')
    ->get();

// Aggregations
$stats = Span::where('provider_name', 'openai')
    ->selectRaw('
        request_model,
        COUNT(*) as call_count,
        AVG(duration) as avg_duration,
        SUM(input_tokens) as total_input_tokens,
        SUM(output_tokens) as total_output_tokens
    ')
    ->groupBy('request_model')
    ->get();
```

### Export to CSV

```bash
php artisan mindwave:export-traces --since=yesterday --format=csv --output=traces.csv
```

### Cleanup Old Traces

```bash
php artisan mindwave:prune-traces --older-than=30days
```

---

## 📊 Metrics & Analytics

### Built-in Queries

```php
// Daily cost by provider
$dailyCosts = DB::table('mindwave_spans')
    ->join('mindwave_traces', 'mindwave_spans.trace_id', '=', 'mindwave_traces.trace_id')
    ->selectRaw('
        DATE(mindwave_traces.created_at) as date,
        provider_name,
        SUM(mindwave_traces.estimated_cost) as total_cost
    ')
    ->groupBy('date', 'provider_name')
    ->orderBy('date', 'desc')
    ->get();

// Model performance
$modelPerf = Span::selectRaw('
        request_model,
        COUNT(*) as calls,
        AVG(duration / 1000000000.0) as avg_duration_sec,
        AVG(input_tokens) as avg_input_tokens,
        AVG(output_tokens) as avg_output_tokens,
        SUM(input_tokens + output_tokens) as total_tokens
    ')
    ->where('operation_name', 'chat')
    ->groupBy('request_model')
    ->get();
```

---

## 🚀 Performance Considerations

1. **Batch Processing** - Use `BatchSpanProcessor` with appropriate buffer sizes
2. **Async Export** - Consider queue-based export for high-volume applications
3. **Sampling** - Use `TraceIdRatioBasedSampler` in production (10-20%)
4. **Index Optimization** - Ensure database indexes are used for common queries
5. **Retention** - Schedule automatic cleanup of old traces

---

## 🔐 Security & Privacy

1. **PII Redaction** - Messages are redacted by default (opt-in to capture)
2. **Sensitive Attributes** - Tool arguments/results can be redacted
3. **Access Control** - Restrict database access to traces table
4. **Export Controls** - Command requires appropriate permissions

---

## 🎯 Success Metrics

- [ ] < 5ms overhead per LLM call for tracing
- [ ] 100% span capture rate with database exporter
- [ ] All OpenTelemetry GenAI attributes supported
- [ ] Compatible with Jaeger, Grafana, Datadog, New Relic
- [ ] Query response time < 100ms for common trace lookups

---

**Document Version:** 1.0  
**Last Updated:** November 1, 2025
