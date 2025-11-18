# OpenTelemetry Tracing - Usage Examples

This document provides practical examples of using the Mindwave OpenTelemetry tracing core.

## Basic Setup

```php
use Mindwave\Mindwave\Observability\Tracing\TracerManager;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;

// Create a tracer with in-memory exporter
$exporter = new InMemoryExporter();
$tracer = new TracerManager(
    exporters: [$exporter],
    serviceName: 'my-app',
    serviceVersion: '1.0.0'
);
```

## Example 1: Simple Span

```php
// Create a span
$span = $tracer->startSpan('process-documents');

// Do some work
processDocuments();

// End the span
$span->end();

// Force flush (mainly for testing)
$tracer->forceFlush();
```

## Example 2: Span with Attributes

```php
$span = $tracer->startSpan('process-user-request', [
    'user.id' => auth()->id(),
    'request.path' => request()->path(),
    'request.method' => request()->method(),
]);

// Process request
$result = processRequest();

// Add more attributes based on result
$span->setAttribute('result.status', $result->status);
$span->setAttribute('result.items_count', count($result->items));

$span->end();
```

## Example 3: Parent-Child Spans

```php
// Create parent span
$parentSpan = $tracer->startSpan('process-batch');
$parentScope = $parentSpan->activate();

// Process items in batch
foreach ($items as $item) {
    // Child span automatically uses parent context
    $childSpan = $tracer->startSpan('process-item', [
        'item.id' => $item->id,
        'item.type' => $item->type,
    ]);

    processItem($item);

    $childSpan->end();
}

// Clean up parent
$parentScope->detach();
$parentSpan->end();
```

## Example 4: GenAI Chat Completion

```php
use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiAttributes;

// Start span for LLM call
$span = $tracer->spanBuilder('chat gpt-4')
    ->forChat('openai', 'gpt-4')
    ->withGenAiRequestParams([
        'temperature' => 0.7,
        'max_tokens' => 150,
        'top_p' => 1.0,
    ])
    ->withServerAttributes('api.openai.com', 443)
    ->start();

try {
    // Make the LLM call
    $response = $openai->chat()->create([
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'user', 'content' => 'Hello!']
        ],
        'temperature' => 0.7,
        'max_tokens' => 150,
    ]);

    // Add response attributes
    $span->setGenAiResponse([
        'id' => $response->id,
        'model' => $response->model,
        'finish_reasons' => [$response->choices[0]->finishReason],
    ]);

    // Add token usage
    $span->setGenAiUsage(
        inputTokens: $response->usage->promptTokens,
        outputTokens: $response->usage->completionTokens
    );

    $span->markAsOk();

} catch (\Throwable $e) {
    $span->recordException($e);
    throw $e;
} finally {
    $span->end();
}
```

## Example 5: GenAI with Sensitive Data (Opt-in)

```php
$span = $tracer->startSpan('chat gpt-4');

$span->setGenAiOperation('chat', 'openai', 'gpt-4');

// Optionally capture messages (sensitive data)
if (config('tracing.capture_messages', false)) {
    $span->setGenAiInputMessages([
        ['role' => 'user', 'content' => 'Tell me a joke'],
    ]);
}

$response = callLLM();

if (config('tracing.capture_messages', false)) {
    $span->setGenAiOutputMessages([
        ['role' => 'assistant', 'content' => $response->content],
    ]);
}

$span->end();
```

## Example 6: Embeddings Operation

```php
$span = $tracer->spanBuilder('embeddings text-embedding-ada-002')
    ->forEmbeddings('openai', 'text-embedding-ada-002')
    ->withServerAttributes('api.openai.com', 443)
    ->start();

$response = $openai->embeddings()->create([
    'model' => 'text-embedding-ada-002',
    'input' => 'The quick brown fox jumps over the lazy dog',
]);

$span->setAttribute(GenAiAttributes::GEN_AI_EMBEDDINGS_DIMENSION, 1536)
    ->setGenAiUsage(inputTokens: $response->usage->promptTokens)
    ->setGenAiResponse([
        'id' => $response->id,
        'model' => $response->model,
    ]);

$span->end();
```

## Example 7: Tool Execution Tracing

```php
$span = $tracer->spanBuilder('execute_tool get_weather')
    ->forToolExecution('get_weather')
    ->setAttribute(GenAiAttributes::GEN_AI_TOOL_CALL_ARGUMENTS, json_encode([
        'location' => 'San Francisco',
        'unit' => 'celsius',
    ]))
    ->start();

try {
    $result = getWeather('San Francisco', 'celsius');

    $span->setAttribute(GenAiAttributes::GEN_AI_TOOL_CALL_RESULT, json_encode([
        'temperature' => 18,
        'conditions' => 'sunny',
    ]));

    $span->markAsOk();

} catch (\Throwable $e) {
    $span->recordException($e);
    throw $e;
} finally {
    $span->end();
}
```

## Example 8: Exception Handling

```php
$span = $tracer->startSpan('risky-operation');

try {
    performRiskyOperation();
    $span->markAsOk();

} catch (\InvalidArgumentException $e) {
    // Record exception with additional context
    $span->recordException($e, [
        'error.category' => 'validation',
        'error.recoverable' => true,
    ]);

    // Handle gracefully
    handleValidationError($e);

} catch (\Throwable $e) {
    $span->recordException($e);
    throw $e;

} finally {
    $span->end();
}
```

## Example 9: Span with Events

```php
$span = $tracer->startSpan('document-processing');

// Record events during processing
$span->addEvent('document-loaded', [
    'document.id' => $doc->id,
    'document.size' => $doc->size,
]);

processDocument($doc);

$span->addEvent('document-validated', [
    'validation.passed' => true,
]);

saveDocument($doc);

$span->addEvent('document-saved', [
    'storage.location' => $doc->path,
]);

$span->end();
```

## Example 10: Wrapping Execution

```php
$span = $tracer->startSpan('process-data');

// Wrap execution - automatically handles activation and exceptions
$result = $span->wrap(function () {
    // Your code here
    return processData();
});

$span->end();

// If an exception is thrown, it's automatically recorded and re-thrown
```

## Example 11: Multiple Exporters

```php
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use Mindwave\Mindwave\Observability\Tracing\Exporters\DatabaseSpanExporter;

// Export to both database and OTLP (Jaeger/Grafana)
$tracer = new TracerManager(
    exporters: [
        new DatabaseSpanExporter(),
        new SpanExporter(
            (new \OpenTelemetry\Contrib\Otlp\HttpEndpointResolver())
                ->resolve('http://localhost:4318/v1/traces')
        ),
    ]
);
```

## Example 12: Sampling Configuration

```php
// Production: Sample 10% of traces
$sampler = TracerManager::createSampler('traceidratio', 0.1);

$tracer = new TracerManager(
    exporters: [$exporter],
    sampler: $sampler
);

// Development: Sample everything
$sampler = TracerManager::createSampler('always_on');

$tracer = new TracerManager(
    exporters: [$exporter],
    sampler: $sampler
);
```

## Example 13: Custom Batch Configuration

```php
// High-throughput configuration
$tracer = new TracerManager(
    exporters: [$exporter],
    batchConfig: [
        'max_queue_size' => 4096,       // Larger buffer
        'scheduled_delay_ms' => 2000,   // Export every 2s
        'export_timeout_ms' => 30000,   // 30s timeout
        'max_export_batch_size' => 1024, // Larger batches
    ]
);
```

## Example 14: Testing with In-Memory Exporter

```php
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;

// Create in-memory exporter for testing
$exporter = new InMemoryExporter();
$tracer = new TracerManager(exporters: [$exporter]);

// Create some spans
$span1 = $tracer->startSpan('operation-1');
$span1->end();

$span2 = $tracer->startSpan('operation-2');
$span2->end();

// Force flush
$tracer->forceFlush();

// Assert spans were created
$spans = $exporter->getSpans();
assert(count($spans) === 2);
assert($spans[0]->getName() === 'operation-1');
assert($spans[1]->getName() === 'operation-2');
```

## Example 15: Complete LLM Pipeline

```php
// Create root span for entire pipeline
$pipelineSpan = $tracer->startSpan('llm-pipeline', [
    'pipeline.type' => 'question-answering',
    'user.id' => auth()->id(),
]);
$pipelineScope = $pipelineSpan->activate();

try {
    // Step 1: Embeddings
    $embeddingSpan = $tracer->spanBuilder('embeddings text-embedding-ada-002')
        ->forEmbeddings('openai', 'text-embedding-ada-002')
        ->start();

    $embedding = generateEmbedding($question);
    $embeddingSpan->setGenAiUsage(inputTokens: $embedding->usage->totalTokens);
    $embeddingSpan->end();

    // Step 2: Vector search
    $searchSpan = $tracer->startSpan('vector-search', [
        'search.collection' => 'documents',
        'search.top_k' => 5,
    ]);

    $results = searchVectorDB($embedding->data[0]->embedding);
    $searchSpan->setAttribute('search.results_count', count($results));
    $searchSpan->end();

    // Step 3: Chat completion with context
    $chatSpan = $tracer->spanBuilder('chat gpt-4')
        ->forChat('openai', 'gpt-4')
        ->withGenAiRequestParams([
            'temperature' => 0.7,
            'max_tokens' => 500,
        ])
        ->start();

    $response = generateAnswer($question, $results);

    $chatSpan->setGenAiUsage(
        inputTokens: $response->usage->promptTokens,
        outputTokens: $response->usage->completionTokens
    );
    $chatSpan->setGenAiResponse([
        'id' => $response->id,
        'model' => $response->model,
        'finish_reasons' => [$response->choices[0]->finishReason],
    ]);
    $chatSpan->end();

    $pipelineSpan->setAttribute('pipeline.success', true);
    $pipelineSpan->markAsOk();

} catch (\Throwable $e) {
    $pipelineSpan->recordException($e);
    throw $e;
} finally {
    $pipelineScope->detach();
    $pipelineSpan->end();
}
```

## Example 16: Streaming LLM Responses

```php
$span = $tracer->spanBuilder('chat gpt-4-stream')
    ->forChat('openai', 'gpt-4')
    ->withGenAiRequestParams([
        'temperature' => 0.7,
        'stream' => true,
    ])
    ->start();

$totalTokens = 0;
$chunks = 0;

try {
    $stream = $openai->chat()->createStreamed([
        'model' => 'gpt-4',
        'messages' => $messages,
        'stream' => true,
    ]);

    foreach ($stream as $response) {
        $chunks++;

        if ($response->choices[0]->finishReason !== null) {
            // Stream complete
            $span->addEvent('stream-completed', [
                'chunks.count' => $chunks,
            ]);

            if (isset($response->usage)) {
                $span->setGenAiUsage(
                    inputTokens: $response->usage->promptTokens,
                    outputTokens: $response->usage->completionTokens
                );
            }
        }

        yield $response;
    }

    $span->markAsOk();

} catch (\Throwable $e) {
    $span->recordException($e);
    throw $e;
} finally {
    $span->end();
}
```

## Best Practices

1. **Always end spans** - Use try-finally to ensure spans are ended
2. **Use meaningful names** - Follow pattern: "{operation} {model}" for GenAI
3. **Set required attributes** - operation, provider, model for GenAI spans
4. **Record exceptions** - Use `recordException()` for proper error tracking
5. **Use span builders** - For complex spans with many attributes
6. **Activate parent spans** - Ensure proper parent-child relationships
7. **Force flush in tests** - Use `forceFlush()` to ensure spans are exported
8. **Configure sampling** - Use sampling in production to control volume
9. **Batch configuration** - Tune batch settings for your workload
10. **Sensitive data** - Only capture messages/inputs when explicitly enabled

## Performance Tips

1. **Sampling** - Use ratio-based sampling in production (10-20%)
2. **Batch processing** - Configure appropriate batch sizes and delays
3. **Null attributes** - Framework automatically ignores null values
4. **Lazy evaluation** - Only compute expensive attributes if span is recording
5. **Async export** - Batch processor exports asynchronously by default

## Next Steps

- Implement DatabaseSpanExporter for persistent storage
- Create GenAI Instrumentor for automatic LLM tracing
- Add Laravel Facade for easy access
- Implement cost estimation based on token usage
- Add Artisan commands for trace management
