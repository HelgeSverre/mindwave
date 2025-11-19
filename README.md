![Mindwave](art/header.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mindwave/mindwave.svg?style=flat-square)](https://packagist.org/packages/mindwave/mindwave)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mindwave/mindwave/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mindwave/mindwave/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mindwave/mindwave/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mindwave/mindwave/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mindwave/mindwave.svg?style=flat-square)](https://packagist.org/packages/mindwave/mindwave)

# Mindwave: Production AI Utilities for Laravel

**The working developer's AI toolkit** - Long prompts, streaming, tracing, and context discovery made simple.

> **Status:** 🚧 Under active development. v1.0 coming soon!

## What is Mindwave?

Mindwave is a Laravel package that provides **production-grade AI utilities** for building LLM-powered features. Unlike complex agent frameworks, Mindwave focuses on practical tools that Laravel developers actually need:

- ✅ **Auto-fit long prompts** to any model's context window
- ✅ **Stream LLM responses** with 3 lines of code (SSE/EventSource)
- ✅ **OpenTelemetry tracing** with database storage for costs, tokens, and performance
- ✅ **Ad-hoc context discovery** from your database/CSV using TNTSearch

## Why Mindwave?

**Not another agent framework.** Just batteries-included utilities for shipping AI features fast.

```php
// Write long prompts, Mindwave auto-fits to model limits
Mindwave::prompt()
    ->section('system', $instructions)
    ->section('context', $largeDocument, priority: 50, shrinker: 'summarize')
    ->section('user', $question)
    ->fit()  // Auto-trims to context window
    ->run();

// Stream responses in 3 lines (backend)
return Mindwave::stream($prompt)->respond();

// View traces and costs
$traces = MindwaveTrace::expensive(0.10)->with('spans')->get();

// Pull context from your DB on-the-fly
Mindwave::prompt()
    ->context(TntSearchSource::fromEloquent(User::query(), fn($u) => "Name: {$u->name}"))
    ->ask('Who has Laravel expertise?');
```

## Installation

Install via Composer:

```bash
composer require mindwave/mindwave
```

Publish the config files:

```bash
php artisan vendor:publish --tag="mindwave-config"
```

Run migrations for tracing (optional but recommended):

```bash
php artisan migrate
```

## Quick Start

### 1. Basic LLM Chat

```php
use Mindwave\Mindwave\Facades\Mindwave;

$response = Mindwave::llm()->chat([
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'Explain Laravel in one sentence.'],
]);

echo $response->choices[0]->message->content;
```

### 2. Streaming Responses

**Backend:**
```php
use Mindwave\Mindwave\Facades\Mindwave;

Route::get('/chat', function (Request $request) {
    return Mindwave::stream($request->input('message'))
        ->model('gpt-4')
        ->respond();
});
```

**Frontend:**
```javascript
const stream = new EventSource('/chat?message=' + encodeURIComponent(question));
stream.onmessage = e => output.textContent += e.data;
stream.addEventListener('done', () => stream.close());
```

### 3. Auto-Fit Long Prompts

```php
use Mindwave\Mindwave\Facades\Mindwave;

// Automatically handles token limits
Mindwave::prompt()
    ->reserveOutputTokens(500)
    ->section('system', 'You are an expert analyst', priority: 100)
    ->section('documentation', $longDocContent, priority: 50, shrinker: 'summarize')
    ->section('history', $conversationHistory, priority: 75)
    ->section('user', $userQuestion, priority: 100)
    ->fit()  // Trims to model's context window
    ->run();
```

### 4. View Costs & Traces

```php
use Mindwave\Mindwave\Observability\Models\Trace;
use Mindwave\Mindwave\Observability\Models\Span;

// Find expensive traces
$expensive = Trace::where('estimated_cost', '>', 0.10)
    ->with('spans')
    ->orderByDesc('created_at')
    ->get();

// Find slow LLM calls
$slow = Span::where('operation_name', 'chat')
    ->where('duration', '>', 5_000_000_000) // 5 seconds in nanoseconds
    ->with('trace')
    ->get();

// Daily cost summary
$dailyCosts = Trace::selectRaw('
        DATE(created_at) as date,
        COUNT(*) as total_traces,
        SUM(estimated_cost) as total_cost,
        SUM(total_input_tokens) as input_tokens,
        SUM(total_output_tokens) as output_tokens
    ')
    ->groupBy('date')
    ->orderByDesc('date')
    ->get();
```

### 5. Ad-Hoc Context Discovery

```php
use Mindwave\Mindwave\Context\Sources\TntSearchSource;

// Search your database on-the-fly
Mindwave::prompt()
    ->context(
        TntSearchSource::fromEloquent(
            Product::where('active', true),
            fn($p) => "Product: {$p->name}, Price: {$p->price}"
        )
    )
    ->ask('What products under $50 do you have?');

// Or from CSV files
Mindwave::prompt()
    ->context(TntSearchSource::fromCsv('data/knowledge-base.csv'))
    ->ask('How do I reset my password?');
```

## Core Features

### 🧩 Prompt Composer

Automatically manage context windows with priority-based section trimming:

- **Token budgeting** - Reserve tokens for output, auto-fit sections
- **Smart shrinkers** - Summarize, truncate, or compress content
- **Priority system** - Keep important sections, trim less critical ones
- **Multi-model support** - Works with GPT-4, Claude, Mistral, etc.

### 🌊 Streaming (SSE)

Production-ready Server-Sent Events streaming:

- **3-line setup** - Backend and frontend
- **Proper headers** - Works with Nginx/Apache out of the box
- **Connection monitoring** - Handles client disconnects
- **Error handling** - Graceful failure and retry

### 📊 OpenTelemetry Tracing

Industry-standard observability with GenAI semantic conventions:

- **Automatic tracing** - All LLM calls tracked (zero configuration)
- **Database storage** - Query traces via Eloquent models
- **OTLP export** - Send to Jaeger, Grafana, Datadog, Honeycomb, etc.
- **Cost tracking** - Automatic cost estimation per call
- **Token usage** - Input/output/total tokens tracked
- **PII protection** - Configurable message capture and redaction
- **Artisan commands** - Export, prune, and analyze traces

**Quick Start:**

```php
// 1. Enable tracing in .env
// MINDWAVE_TRACING_ENABLED=true

// 2. LLM calls are automatically traced
$response = Mindwave::llm()->generateText('Hello!');

// 3. Query traces
use Mindwave\Mindwave\Observability\Models\Trace;

$expensive = Span::where('cost_usd', '>', 0.10)
    ->orderBy('cost_usd', 'desc')
    ->get();
```

📖 **[Complete Tracing Guide](examples/tracing-examples.md)** - Querying, cost analysis, custom spans, OTLP setup

📐 **[Architecture Documentation](TRACING_ARCHITECTURE.md)** - Technical deep dive

### 🔍 TNTSearch Context Discovery

Pull context from your application data without complex RAG setup:

- **No infrastructure** - Pure PHP, no external services
- **Multiple sources** - Eloquent, arrays, CSV files, VectorStores
- **Fast indexing** - Ephemeral indexes with automatic cleanup
- **BM25 ranking** - Industry-standard relevance scoring
- **Auto-query extraction** - Automatically extracts search terms from user messages
- **OpenTelemetry tracing** - Track search performance and results

**Quick Example:**

```php
use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;
use Mindwave\Mindwave\Context\ContextPipeline;

// Search Eloquent models
$userSource = TntSearchSource::fromEloquent(
    User::where('active', true),
    fn($u) => "Name: {$u->name}, Skills: {$u->skills}"
);

// Search CSV files
$docsSource = TntSearchSource::fromCsv('data/knowledge-base.csv');

// Combine multiple sources
$pipeline = (new ContextPipeline)
    ->addSource($userSource)
    ->addSource($docsSource)
    ->deduplicate()  // Remove duplicates
    ->rerank();      // Sort by relevance

// Use in prompt (query auto-extracted from user message)
Mindwave::prompt()
    ->context($pipeline, limit: 5)
    ->section('user', 'Who has Laravel expertise?')
    ->run();
```

📖 **[Complete Context Discovery Guide](examples/context-discovery-examples.md)** - All source types, pipelines, advanced features

## Configuration

### LLM Configuration

```php
// config/mindwave-llm.php
return [
    'default' => env('MINDWAVE_LLM_DRIVER', 'openai'),
    
    'llms' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4-turbo'),
            'max_tokens' => 4096,
            'temperature' => 0.7,
        ],
        'mistral' => [
            'api_key' => env('MISTRAL_API_KEY'),
            'model' => env('MISTRAL_MODEL', 'mistral-large-latest'),
        ],
    ],
];
```

### Tracing Configuration

```php
// config/mindwave-tracing.php
return [
    'enabled' => env('MINDWAVE_TRACING_ENABLED', true),
    
    'database' => [
        'enabled' => true,  // Store in database
    ],
    
    'otlp' => [
        'enabled' => env('MINDWAVE_TRACE_OTLP_ENABLED', false),
        'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),
    ],
    
    'capture_messages' => false,  // PII protection
    'retention_days' => 30,
];
```

## Artisan Commands

```bash
# Export traces to CSV/JSON
php artisan mindwave:export-traces --since=yesterday --format=csv

# Prune old traces
php artisan mindwave:prune-traces --older-than=30days

# View trace statistics
php artisan mindwave:trace-stats

# View TNTSearch index statistics
php artisan mindwave:index-stats

# Clear old TNTSearch indexes (default: 24 hours)
php artisan mindwave:clear-indexes --ttl=24 --force
```

## Use Cases

### 💬 AI-Powered Customer Support

```php
Mindwave::prompt()
    ->section('system', 'You are a helpful support agent')
    ->context(TntSearchSource::fromEloquent(
        FAQ::published(),
        fn($f) => "Q: {$f->question}\nA: {$f->answer}"
    ))
    ->section('history', $conversation)
    ->section('user', $userMessage)
    ->fit()
    ->run();
```

### 📄 Document Q&A

```php
Mindwave::prompt()
    ->context(TntSearchSource::fromCsv('uploads/company-handbook.csv'))
    ->ask('What is the vacation policy?');
```

### 🔍 Data Analysis

```php
Mindwave::prompt()
    ->context(TntSearchSource::fromEloquent(
        Order::where('created_at', '>', now()->subMonth()),
        fn($o) => "Order #{$o->id}: {$o->total}, Status: {$o->status}"
    ))
    ->ask('Summarize sales trends from last month');
```

## Supported LLM Providers

- ✅ **OpenAI** (GPT-4, GPT-3.5, etc.)
- ✅ **Mistral AI** (Mistral Large, Small, etc.)
- ✅ **Anthropic** (Claude 3.5 Sonnet, Opus, Haiku, etc.)
- 🔄 **Google Gemini** (Coming soon)

## Supported Vector Stores

- ✅ **Qdrant** - High-performance vector database with UUID-based IDs
- ✅ **Weaviate** - Open-source vector search engine
- ✅ **Pinecone** - Managed vector database service
- ✅ **In-Memory** - For testing and development
- ✅ **File-based** - JSON file storage for simple use cases

**Vector Store Configuration:**

All vector stores now support configurable embedding dimensions. Set the dimension in your `.env` file to match your embedding model:

```bash
# Common values: 1536 (OpenAI ada-002, 3-small), 3072 (OpenAI 3-large)
MINDWAVE_QDRANT_DIMENSIONS=1536
MINDWAVE_WEAVIATE_DIMENSIONS=1536
MINDWAVE_PINECONE_DIMENSIONS=1536
```

## Breaking Changes in v2.0

**⚠️ Important:** Version 2.0 introduces breaking changes:

1. **Removed `OPENAI_EMBEDDING_LENGTH` constant** - Embedding dimensions are now configured per vector store in `config/mindwave-vectorstore.php` and environment variables.

2. **Qdrant ID generation changed** - Now uses UUID strings instead of auto-incrementing integers. Existing Qdrant collections will need to be recreated.

3. **Weaviate dependency moved** - `timkley/weaviate-php` is now in `require` instead of `require-dev` to prevent production crashes.

**Migration Guide:**

```bash
# 1. Update your .env file with dimension settings
MINDWAVE_QDRANT_DIMENSIONS=1536
MINDWAVE_WEAVIATE_DIMENSIONS=1536

# 2. Update your config (if you published it)
php artisan vendor:publish --tag="mindwave-config" --force

# 3. Rebuild Qdrant collections (if using Qdrant)
# The new UUID-based IDs are incompatible with old integer IDs
```

## Documentation

Full documentation available at [mindwave.no](https://mindwave.no) (coming soon).

For now, see:
- [PIVOT_PLAN.md](PIVOT_PLAN.md) - Implementation roadmap
- [TRACING_ARCHITECTURE.md](TRACING_ARCHITECTURE.md) - OpenTelemetry details

## Roadmap

### v1.0 (December 2025)
- [x] LLM abstraction (OpenAI, Mistral)
- [ ] Prompt Composer with auto-fitting
- [ ] Streaming SSE support
- [ ] OpenTelemetry tracing + database storage
- [ ] TNTSearch context discovery

### v1.1 (Q1 2026)
- [ ] More LLM providers (Anthropic, Cohere, Groq)
- [ ] Advanced shrinkers (semantic compression)
- [ ] Cost budgets and alerts
- [ ] Grafana dashboard templates

### v2.0 (Q2 2026)
- [ ] Multi-modal support (images, audio)
- [ ] Prompt testing framework
- [ ] A/B testing utilities
- [ ] Batch processing

## Credits

- [Helge Sverre](https://twitter.com/helgesverre) - Creator
- [OpenAI PHP Client](https://github.com/openai-php/client) - OpenAI integration
- [TeamTNT/TNTSearch](https://github.com/teamtnt/tntsearch) - Full-text search
- [OpenTelemetry PHP](https://github.com/open-telemetry/opentelemetry-php) - Observability
- [Tiktoken PHP](https://github.com/yethee/tiktoken-php) - Token counting

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
