# Changelog

All notable changes to `mindwave` will be documented in this file.

## [1.0.0] - 2025-12-27

### Added

#### Core Features - The Four Pillars

**Prompt Composer**
- Tokenizer service using tiktoken-php with support for 46+ models
- Section management with priorities for intelligent prompt assembly
- Shrinkers (Truncate, Compress) for fitting content to context windows
- `Mindwave::prompt()` facade for fluent prompt building
- Automatic token counting and context window management

**OpenTelemetry Tracing**
- Full GenAI semantic conventions implementation
- Database exporter for local development and queries
- OTLP exporter for production observability (Jaeger, Grafana, Datadog, Honeycomb)
- Multi-exporter support for fan-out to multiple backends
- Automatic cost estimation and token usage tracking
- Events system (RequestStarted, TokenStreamed, ResponseCompleted, ErrorOccurred)
- Artisan commands: `mindwave:trace-export`, `mindwave:trace-prune`, `mindwave:trace-stats`

**Streaming SSE**
- `streamText()` method on LLM interface
- `streamChat()` method for structured streaming with metadata
- `StreamChunk` DTO for consistent streaming responses
- `StreamingException` with retryable flag and provider tracking
- `StreamRetryHandler` with exponential backoff and jitter
- SSE formatter (`StreamedTextResponse`) for EventSource compatibility
- Client examples: Vanilla JS, Alpine.js, Vue.js, React, TypeScript

**TNTSearch Context Discovery**
- `TntSearchSource` for Eloquent, arrays, and CSV data
- `VectorStoreSource` for Brain/RAG integration
- `EloquentSource` for SQL LIKE searches
- `StaticSource` for hardcoded context
- `ContextPipeline` for multi-source aggregation and deduplication
- `EphemeralIndexManager` for temporary search indexes with TTL
- Artisan commands: `mindwave:index-stats`, `mindwave:clear-indexes`

#### LLM Drivers
- **OpenAI** - Full support including GPT-4, GPT-4 Turbo, GPT-4o, o1 models
- **Anthropic** - Claude 3 Opus, Sonnet, Haiku; Claude 3.5 Sonnet
- **Mistral** - Mistral Medium, Large, and other models
- **Fake** - Testing driver with configurable responses

#### Vector Store Drivers
- **Pinecone** - Cloud vector database
- **Qdrant** - Open-source vector database
- **Weaviate** - AI-native vector database
- **InMemory** - For testing and small datasets
- **File** - JSON file-based persistence

#### Document Loaders
- PDF loader (using smalot/pdfparser)
- Word document loader (.docx, .odt)
- HTML loader with tag stripping
- URL loader with content extraction
- Plain text loader

#### Integrations
- **Laravel Telescope** - MindwaveWatcher for request monitoring
  - Tags: mindwave, provider:*, model:*, slow, expensive, cached
  - Privacy controls with `capture_messages` option

### Changed
- Namespace updated to `Mindwave\Mindwave`
- Removed agent/crew orchestration code (pivot to utilities focus)
- LLM interface now requires `streamText()` and `streamChat()` methods

### Fixed
- Mistral driver config key resolution
- Weaviate dependency installation
- Model class renamed to ModelNames to avoid conflicts
- Tool choice API compatibility across providers

### Developer Experience
- 1300+ tests with Pest PHP
- PHPStan level 4 with baseline
- Laravel Pint code style
- GrumPHP pre-commit hooks
- GitHub issue/PR templates
- Dependabot for dependency updates
- CONTRIBUTING.md and SECURITY.md

---

## [0.x] - Pre-release

Initial development versions with agent framework (deprecated).
