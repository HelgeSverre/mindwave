# Mindwave Roadmap

> **Status:** v1.0 Release Ready
> **Version:** 1.0.0 (December 27, 2025)

See [PIVOT_PLAN.md](PIVOT_PLAN.md) for comprehensive implementation plan.

---

## ✅ v1.0 Complete - All Pillars Delivered

### Phase 1-5 Completed

**Phase 1-4:**
- [x] Update all namespaces
- [x] Vectorstore interface and drivers (InMemory, File, Pinecone, Qdrant, Weaviate)
- [x] LLM abstraction (OpenAI, Mistral, Anthropic)
- [x] Document loaders (PDF, URL, text, Word)
- [x] Embeddings manager
- [x] Brain class for RAG
- [x] Strategic pivot decision
- [x] Research OpenTelemetry and LLMetry standards
- [x] Create comprehensive pivot plan
- [x] Remove Agent and Crew code
- [x] Update README with new vision
- [x] Update TODO.md with new roadmap
- [x] Update dependencies (Weaviate installed, OpenTelemetry SDK)
- [x] Fix LLM driver issues (Model class → ModelNames, tool_choice API)
- [x] Complete Prompt Composer (Tokenizer, Sections, Shrinkers, Core)
- [x] Complete OpenTelemetry Tracing (Database, OTLP, Instrumentation, Events, Commands)
- [x] Complete Streaming SSE (LLM interface, OpenAI implementation, SSE formatter, Client examples)

**Phase 5 - TNTSearch Context Discovery:** ✅ COMPLETE
- [x] EphemeralIndexManager (create/search/delete indexes with TTL)
- [x] TntSearchSource (fromEloquent, fromArray, fromCsv)
- [x] VectorStoreSource (Brain integration)
- [x] EloquentSource (SQL LIKE searches)
- [x] StaticSource (hardcoded context)
- [x] ContextPipeline (multi-source aggregation, deduplication, re-ranking)
- [x] ContextCollection (formatForPrompt, truncateToTokens, getTotalTokens)
- [x] PromptComposer integration (context() method with auto-query extraction)
- [x] Artisan commands (mindwave:index-stats, mindwave:clear-indexes)
- [x] Config file (mindwave-context.php)
- [x] Tracing integration

**Bonus: Laravel Telescope Integration:**
- [x] MindwaveWatcher for Telescope client_request entries
- [x] Event-based integration (listens to LLM events)
- [x] Tags: mindwave, provider:*, model:*, slow, expensive, cached
- [x] Privacy controls (capture_messages option)

**Phase 6 - Documentation & Release:** ✅ COMPLETE
- [x] Full documentation (mindwave-docs site updated)
- [x] CHANGELOG.md with v1.0 release notes
- [x] Testing: 1300+ tests passing
- [x] GrumPHP pre-commit hooks
- [x] PHPStan baseline generated (153 errors baselined)
- [x] DevEx infrastructure (CONTRIBUTING.md, SECURITY.md, templates)
- [x] v1.0.0 release ready

---

## 📦 v1.0 Deliverables (December 2025)

### Pillar 1: Prompt Composer ✨ **COMPLETE**
**Auto-fit long prompts to model context windows**

- [x] Tokenizer service (using tiktoken-php)
- [x] Section management with priorities
- [x] Shrinkers (Truncate, Compress)
- [x] PromptComposer core with fit() algorithm
- [x] Facade integration: `Mindwave::prompt()`
- [x] Documentation and examples

**Example:**
```php
Mindwave::prompt()
    ->reserveOutputTokens(512)
    ->section('system', $instructions, priority: 100)
    ->section('context', $largeDoc, priority: 50, shrinker: 'summarize')
    ->section('user', $question, priority: 100)
    ->fit()
    ->run();
```

### Pillar 2: OpenTelemetry Tracing 📊 **COMPLETE**
**Industry-standard LLM observability**

- [x] Database schema (traces + spans tables)
- [x] GenAI semantic conventions implementation
- [x] Tracer core with span management
- [x] Database exporter
- [x] OTLP exporter (Jaeger, Grafana, etc.)
- [x] Multi-exporter (fan-out)
- [x] LLM driver instrumentation (GenAiInstrumentor + Decorator)
- [x] Events system (RequestStarted, TokenStreamed, ResponseCompleted, ErrorOccurred)
- [x] Configuration and PII redaction
- [x] Artisan commands (export, prune, stats)

**Features:**
- Dual storage: Database (queries) + OTLP (production tools)
- Automatic cost estimation
- Token usage tracking
- Query interface via Eloquent

### Pillar 3: Streaming SSE 🌊 **COMPLETE**
**EventSource streaming made simple**

- [x] Add streamText() to LLM interface
- [x] Add streamChat() for structured streaming
- [x] Implement OpenAI/Anthropic/Mistral streaming
- [x] SSE formatter (StreamedTextResponse)
- [x] StreamChunk DTO for consistent responses
- [x] StreamingException with retryable flag
- [x] StreamRetryHandler with exponential backoff
- [x] Client-side examples (Blade, vanilla JS, Alpine, Vue, TypeScript)

**Example:**
```php
// Backend (1 line)
return Mindwave::stream($prompt)->toStreamedResponse();

// Frontend (6 lines)
const eventSource = new EventSource('/api/chat?prompt=' + query);
eventSource.addEventListener('message', (e) => output.textContent += e.data);
eventSource.addEventListener('done', () => eventSource.close());
```

### Pillar 4: TNTSearch Context Discovery 🔍 **COMPLETE**
**Ad-hoc context from DB/CSV without complex RAG**

- [x] TNTSearch integration
- [x] Ephemeral index manager
- [x] ContextSource interface
- [x] TntSearchSource (fromEloquent, fromArray, fromCsv)
- [x] VectorStoreSource (Brain integration)
- [x] EloquentSource (SQL LIKE searches)
- [x] StaticSource (hardcoded context)
- [x] ContextPipeline (multi-source aggregation)
- [x] Prompt Composer integration
- [x] Artisan commands (index-stats, clear-indexes)

**Example:**
```php
Mindwave::prompt()
    ->context(
        TntSearchSource::fromEloquent(
            User::where('active', true),
            fn($u) => "Name: {$u->name}, Skills: {$u->skills}"
        )
    )
    ->ask('Who has Laravel expertise?');
```

---

## 🚫 Explicitly NOT Building (v1.0)

- ❌ Agent orchestration frameworks
- ❌ Multi-agent coordination
- ❌ Tool/function calling systems
- ❌ Workflow engines
- ❌ Chain-of-thought frameworks

**Focus:** Simple, production-ready utilities for common AI tasks.

---

## 📋 Post-v1.0 Roadmap

### v1.1 (Q1 2026)
- [ ] Cohere LLM driver
- [ ] Groq LLM driver
- [ ] Advanced shrinkers (semantic compression)
- [ ] Cost estimation and budgets per request
- [ ] Grafana dashboard templates
- [ ] Demo application

### v1.2 (Q2 2026)
- [ ] Prompt testing framework
- [ ] A/B testing for prompts
- [ ] Batch processing utilities
- [ ] Queue integration for async LLM calls

### v2.0 (Q3 2026)
- [ ] Multi-modal support (images, audio)
- [ ] Advanced re-ranking algorithms
- [ ] Distributed tracing across microservices
- [ ] Real-time streaming analytics dashboard

---

## 🏗️ Technical Debt & Fixes

### Completed
- [x] Fix Mistral config keys
- [x] Resolve Weaviate dependency
- [x] Fix test suite failures (1300+ tests passing)
- [x] Add maxContextTokens() to LLM interface
- [x] Add GPT-5 and GPT-4.1 model families
- [x] Migrate to larastan/larastan ^3.7
- [x] PHPStan baseline generated
- [x] Add PHP 8.4 to CI matrix
- [x] Fix env() calls (use config())
- [x] Fix resource leaks in FileTypeDetector
- [x] Add error handling to Tools

### Low Priority (Post v1.0)
- [ ] Add more document loaders (CSV, XML, Excel, iCal)
- [ ] Gmail search tool (nice-to-have)
- [ ] Laravel Scout-like model indexing
- [ ] Extract common LLM driver trait

---

## 🎯 Success Metrics

### Technical ✅
- [x] Zero agent framework code
- [x] 90%+ test coverage (1300+ tests passing)
- [x] All 4 pillars functional
- [x] Telescope integration (bonus)
- [x] < 100ms tracing overhead (< 5ms per call)

### Adoption (Post-Launch)
- [ ] 50 GitHub stars (first month)
- [ ] 10 production users
- [ ] 5 community contributions
- [ ] Featured in Laravel News

---

## 📝 Notes & Design Decisions

### Why OpenTelemetry?
- Industry standard for observability
- Future-proof and interoperable
- Works with existing tools (Jaeger, Grafana, Datadog)

### Why Database + OTLP for Tracing?
- **Database:** Easy queries, cost tracking, local dev
- **OTLP:** Production observability tools, distributed tracing

### Why TNTSearch?
- Pure PHP, no infrastructure
- Fast ephemeral indexes
- Perfect for ad-hoc needs
- Zero-config for simple cases

### Why Not Vector Stores by Default?
- Too much setup for simple use cases
- Optional Brain integration for advanced RAG
- Most apps just need search + context

---

## 🔗 Resources

- [PIVOT_PLAN.md](PIVOT_PLAN.md) - Detailed implementation plan
- [TRACING_ARCHITECTURE.md](TRACING_ARCHITECTURE.md) - OpenTelemetry architecture
- [CHANGELOG.md](CHANGELOG.md) - Version history
- [OpenTelemetry GenAI Conventions](https://github.com/open-telemetry/semantic-conventions/tree/main/docs/gen-ai)
- [TNTSearch Documentation](https://github.com/teamtnt/tntsearch)

---

**Last Updated:** December 27, 2025
**Current Status:** v1.0.0 Release Ready - All 4 pillars complete!
