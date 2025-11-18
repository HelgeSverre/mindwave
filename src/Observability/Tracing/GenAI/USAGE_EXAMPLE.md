# GenAI Instrumentor Usage Examples

This document demonstrates how to use the GenAI Instrumentor and LLM Driver Decorator for automatic LLM call tracing.

## Basic Setup

### 1. Manual Instrumentation

Use the `GenAiInstrumentor` directly when you want fine-grained control:

```php
use Mindwave\Mindwave\Observability\Tracing\TracerManager;
use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiInstrumentor;
use Mindwave\Mindwave\Observability\Tracing\Exporters\DatabaseSpanExporter;

// Initialize TracerManager
$tracerManager = new TracerManager(
    exporters: [new DatabaseSpanExporter()],
    serviceName: config('mindwave-tracing.service_name'),
);

// Create instrumentor
$instrumentor = new GenAiInstrumentor(
    tracerManager: $tracerManager,
    captureMessages: config('mindwave-tracing.capture_messages', false),
    enabled: config('mindwave-tracing.enabled', true)
);

// Instrument a chat completion
$response = $instrumentor->instrumentChatCompletion(
    provider: 'openai',
    model: 'gpt-4',
    messages: [
        ['role' => 'user', 'content' => 'Hello!']
    ],
    options: [
        'temperature' => 0.7,
        'max_tokens' => 100,
    ],
    execute: fn() => $openaiClient->chat()->create([...])
);
```

### 2. Automatic Instrumentation with Decorator

Use the `LLMDriverInstrumentorDecorator` for transparent, automatic tracing:

```php
use Mindwave\Mindwave\LLM\Drivers\OpenAI\OpenAI;
use Mindwave\Mindwave\Observability\Tracing\GenAI\LLMDriverInstrumentorDecorator;

// Create your normal LLM driver
$driver = new OpenAI(
    client: $openaiClient,
    model: 'gpt-4',
    temperature: 0.7
);

// Wrap it with the instrumentor decorator
$instrumentedDriver = new LLMDriverInstrumentorDecorator(
    driver: $driver,
    instrumentor: $instrumentor,
    provider: 'openai',
    model: 'gpt-4'
);

// Now all calls are automatically traced!
$response = $instrumentedDriver->generateText('What is Laravel?');
// Creates span: "text_completion gpt-4" with all attributes

$chatResponse = $instrumentedDriver->chat([
    ['role' => 'user', 'content' => 'Hello!']
]);
// Creates span: "chat gpt-4" with all attributes
```

## Integration with LLM Manager

The decorator pattern allows you to instrument at the manager level:

```php
use Mindwave\Mindwave\LLM\LLMManager;

class InstrumentedLLMManager extends LLMManager
{
    public function driver(?string $name = null): LLM
    {
        $driver = parent::driver($name);

        // Only instrument if tracing is enabled
        if (!config('mindwave-tracing.enabled')) {
            return $driver;
        }

        // Get provider from config
        $provider = $this->getProviderForDriver($name);

        // Wrap with instrumentor
        return new LLMDriverInstrumentorDecorator(
            driver: $driver,
            instrumentor: app(GenAiInstrumentor::class),
            provider: $provider
        );
    }

    private function getProviderForDriver(?string $name): string
    {
        return match($name) {
            'openai' => 'openai',
            'mistral' => 'mistral_ai',
            'anthropic' => 'anthropic',
            default => 'openai',
        };
    }
}
```

## Advanced Examples

### Tool Execution Tracing

```php
// Trace tool/function execution
$result = $instrumentor->instrumentToolExecution(
    toolName: 'get_weather',
    arguments: ['city' => 'London', 'unit' => 'celsius'],
    execute: fn() => $weatherTool->execute(['city' => 'London'])
);
```

### Embeddings Tracing

```php
// Trace embeddings generation
$embeddings = $instrumentor->instrumentEmbeddings(
    provider: 'openai',
    model: 'text-embedding-ada-002',
    input: 'Text to embed',
    options: [],
    execute: fn() => $openaiClient->embeddings()->create([...])
);
```

### Streaming Responses

The instrumentor handles streaming responses properly:

```php
$stream = $instrumentor->instrumentChatCompletion(
    provider: 'openai',
    model: 'gpt-4',
    messages: $messages,
    options: ['stream' => true],
    execute: fn() => $client->chat()->createStreamed([...])
);

// Span is created and ended properly even for streams
// Token usage captured at the end of streaming
```

### Nested Spans (Parent-Child Relationships)

```php
// Parent span for the entire workflow
$parentSpan = $tracerManager->startSpan('process-document');
$parentScope = $parentSpan->activate();

try {
    // Child span 1: Summarization
    $summary = $instrumentedDriver->generateText('Summarize: ' . $document);

    // Child span 2: Translation
    $translation = $instrumentedDriver->generateText('Translate to French: ' . $summary);

    // Child span 3: Sentiment analysis
    $sentiment = $instrumentedDriver->generateText('Analyze sentiment: ' . $translation);

    $parentSpan->markAsOk();
} finally {
    $parentScope->detach();
    $parentSpan->end();
}

// Result: One parent span with three child LLM spans
```

## Configuration-Based Usage

The instrumentor respects configuration settings:

```php
// In config/mindwave-tracing.php
return [
    'enabled' => true,
    'capture_messages' => false, // PII protection
    'instrumentation' => [
        'llm' => true,
        'tools' => true,
    ],
];

// Usage - automatically respects config
$instrumentor = new GenAiInstrumentor(
    tracerManager: $tracerManager,
    captureMessages: config('mindwave-tracing.capture_messages'),
    enabled: config('mindwave-tracing.enabled')
);
```

## Service Provider Integration

Register in your service provider for automatic dependency injection:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiInstrumentor;
use Mindwave\Mindwave\Observability\Tracing\TracerManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GenAiInstrumentor::class, function ($app) {
            return new GenAiInstrumentor(
                tracerManager: $app->make(TracerManager::class),
                captureMessages: config('mindwave-tracing.capture_messages', false),
                enabled: config('mindwave-tracing.enabled', true)
            );
        });
    }
}
```

Then use it anywhere:

```php
class DocumentProcessor
{
    public function __construct(
        private LLM $llm,
        private GenAiInstrumentor $instrumentor
    ) {}

    public function process(string $document): string
    {
        return $this->instrumentor->instrumentTextCompletion(
            provider: 'openai',
            model: 'gpt-4',
            prompt: 'Process: ' . $document,
            options: [],
            execute: fn() => $this->llm->generateText('Process: ' . $document)
        );
    }
}
```

## Querying Traced Data

After tracing, query the data from the database:

```php
use Mindwave\Mindwave\Observability\Models\Span;
use Mindwave\Mindwave\Observability\Models\Trace;

// Find slow LLM calls
$slowCalls = Span::where('operation_name', 'chat')
    ->where('duration', '>', 5_000_000_000) // 5 seconds
    ->with('trace')
    ->get();

// Find expensive operations
$expensive = Trace::expensive(0.10) // $0.10+
    ->orderByDesc('estimated_cost')
    ->take(10)
    ->get();

// Token usage by model
$stats = Span::where('provider_name', 'openai')
    ->selectRaw('
        request_model,
        COUNT(*) as call_count,
        SUM(input_tokens) as total_input,
        SUM(output_tokens) as total_output
    ')
    ->groupBy('request_model')
    ->get();
```

## Best Practices

1. **Always use the decorator pattern** for application-wide instrumentation
2. **Enable message capture only in development** to avoid storing sensitive data
3. **Use sampling in production** to reduce overhead (10-20% sampling ratio)
4. **Monitor span export performance** and adjust batch settings if needed
5. **Regularly prune old traces** to manage database size
6. **Add custom attributes** to spans for application-specific context
7. **Test with tracing disabled** to ensure zero overhead when not needed

## Error Handling

The instrumentor properly handles and records exceptions:

```php
try {
    $response = $instrumentedDriver->generateText('Trigger error');
} catch (ApiException $e) {
    // Exception is recorded in span with:
    // - Status: ERROR
    // - Exception details in span events
    // - Stack trace captured
    // Exception is then re-thrown
}
```

## Performance Considerations

- **Minimal overhead**: < 5ms per LLM call when enabled
- **Zero overhead**: When tracing is disabled, decorator bypasses instrumentation
- **Batch processing**: Spans are exported in batches to minimize I/O
- **Async export**: Consider queue-based export for high-volume applications
- **Sampling**: Use 10-20% sampling in production for large-scale systems
