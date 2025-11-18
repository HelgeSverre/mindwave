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

- **Automatic tracing** - All LLM calls tracked
- **Database storage** - Query traces via Eloquent
- **OTLP export** - Send to Jaeger, Grafana, Datadog, etc.
- **Cost tracking** - Automatic cost estimation per call
- **PII protection** - Configurable redaction

### 🔍 TNTSearch Context Discovery

Pull context from your application data without complex RAG setup:

- **No infrastructure** - Pure PHP, no external services
- **Multiple sources** - Eloquent, arrays, CSV files
- **Fast indexing** - Ephemeral indexes with caching
- **BM25 ranking** - Industry-standard relevance scoring

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

# Clear TNTSearch indexes
php artisan mindwave:clear-indexes
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
- 🔄 **Anthropic** (Coming soon)
- 🔄 **Google Gemini** (Coming soon)

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
