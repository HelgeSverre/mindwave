# Mindwave Roadmap

> **Status:** Pivoting from agent framework to production AI utilities toolkit  
> **Target:** v1.0 release by December 2025

See [PIVOT_PLAN.md](PIVOT_PLAN.md) for comprehensive implementation plan.

---

## 🎯 Current Focus: Phase 1 - Foundation (Week 1)

### ✅ Completed
- [x] Update all namespaces
- [x] Vectorstore interface and drivers (InMemory, File, Pinecone, Qdrant)
- [x] LLM abstraction (OpenAI, Mistral)
- [x] Document loaders (PDF, URL, text)
- [x] Embeddings manager
- [x] Brain class for RAG
- [x] Strategic pivot decision
- [x] Research OpenTelemetry and LLMetry standards
- [x] Create comprehensive pivot plan
- [x] Remove Agent and Crew code
- [x] Update README with new vision

### 🔄 In Progress
- [ ] Update TODO.md with new roadmap
- [ ] Update dependencies
- [ ] Fix LLM driver issues

---

## 📦 v1.0 Deliverables (December 2025)

### Pillar 1: Prompt Composer ✨
**Auto-fit long prompts to model context windows**

- [ ] Tokenizer service (using tiktoken-php)
- [ ] Section management with priorities
- [ ] Shrinkers (Truncate, Summarize, Compress)
- [ ] PromptComposer core with fit() algorithm
- [ ] Facade integration: `Mindwave::prompt()`
- [ ] Documentation and examples

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

### Pillar 2: OpenTelemetry Tracing 📊
**Industry-standard LLM observability**

- [ ] Database schema (traces + spans tables)
- [ ] GenAI semantic conventions implementation
- [ ] Tracer core with span management
- [ ] Database exporter
- [ ] OTLP exporter (Jaeger, Grafana, etc.)
- [ ] Multi-exporter (fan-out)
- [ ] LLM driver instrumentation
- [ ] Events system (RequestStarted, TokenStreamed, etc.)
- [ ] Configuration and PII redaction
- [ ] Artisan commands (export, prune, stats)

**Features:**
- Dual storage: Database (queries) + OTLP (production tools)
- Automatic cost estimation
- Token usage tracking
- Query interface via Eloquent

### Pillar 3: Streaming SSE 🌊
**EventSource streaming made simple**

- [ ] Add streamText() to LLM interface
- [ ] Implement OpenAI streaming
- [ ] Implement Mistral streaming
- [ ] SSE formatter
- [ ] StreamedResponse helper
- [ ] Client-side examples (Blade, vanilla JS, Alpine)

**Example:**
```php
// Backend (3 lines)
return Mindwave::stream($prompt)->model('gpt-4')->respond();

// Frontend (3 lines)
const stream = new EventSource('/api/chat?q=' + query);
stream.onmessage = e => output.textContent += e.data;
stream.addEventListener('done', () => stream.close());
```

### Pillar 4: TNTSearch Context Discovery 🔍
**Ad-hoc context from DB/CSV without complex RAG**

- [ ] TNTSearch integration
- [ ] Ephemeral index manager
- [ ] ContextSource interface
- [ ] TntSearchSource (fromEloquent, fromArray, fromCsv)
- [ ] VectorStoreSource (Brain integration)
- [ ] ContextPipeline (multi-source aggregation)
- [ ] Prompt Composer integration
- [ ] Artisan commands (index-stats, clear-indexes)

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

## 🗓️ Timeline

### Week 1: Foundation (Nov 1-7, 2025) - IN PROGRESS
- [x] Remove agent code
- [ ] Fix dependencies
- [ ] Fix LLM driver bugs
- [ ] Add maxContextTokens() to drivers

### Week 2: Prompt Composer (Nov 8-14, 2025)
- [ ] Tokenizer service
- [ ] Section management
- [ ] Shrinkers
- [ ] PromptComposer core
- [ ] Facade integration

### Week 3: Tracing Part 1 (Nov 15-21, 2025)
- [ ] Database schema
- [ ] GenAI attributes
- [ ] Tracer core
- [ ] Exporters (Database + OTLP)

### Week 4: Tracing Part 2 + Streaming (Nov 22-28, 2025)
- [ ] LLM instrumentation
- [ ] Events system
- [ ] Artisan commands
- [ ] Streaming implementation

### Week 5-6: TNTSearch (Nov 29 - Dec 12, 2025)
- [ ] TNTSearch integration
- [ ] Context sources
- [ ] Context pipeline
- [ ] Prompt Composer integration

### Week 7: Documentation & Release (Dec 13-19, 2025)
- [ ] Full documentation
- [ ] Demo application
- [ ] Testing (>90% coverage)
- [ ] v1.0.0 release

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

### v1.1 (January 2026)
- [ ] Anthropic LLM driver
- [ ] Cohere LLM driver
- [ ] Groq LLM driver
- [ ] Advanced shrinkers (semantic compression)
- [ ] Cost estimation and budgets per request
- [ ] Grafana dashboard templates

### v1.2 (February 2026)
- [ ] Prompt testing framework
- [ ] A/B testing for prompts
- [ ] Batch processing utilities
- [ ] Queue integration for async LLM calls

### v2.0 (Q2 2026)
- [ ] Multi-modal support (images, audio)
- [ ] Advanced re-ranking algorithms
- [ ] Distributed tracing across microservices
- [ ] Real-time streaming analytics dashboard

---

## 🏗️ Technical Debt & Fixes

### High Priority
- [ ] Fix Mistral config keys (currently reads OpenAI config)
- [ ] Resolve Weaviate dependency (Laravel 11 incompatible)
- [ ] Add maxContextTokens() to LLM interface
- [ ] Fix test suite failures (Qdrant, Manager tests)

### Medium Priority
- [ ] Migrate from nunomaduro/larastan to larastan/larastan (package abandoned)
- [ ] Update PHPStan baseline after cleanup
- [ ] Add PHP 8.4 to CI matrix

### Low Priority
- [ ] Add more document loaders (CSV, XML, Excel, iCal)
- [ ] Gmail search tool (nice-to-have)
- [ ] Laravel Scout-like model indexing

---

## 🎯 Success Metrics

### Technical
- [ ] Zero agent framework code
- [ ] 90%+ test coverage
- [ ] All 4 pillars functional
- [ ] < 10 minute quick start
- [ ] < 100ms tracing overhead

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
- [OpenTelemetry GenAI Conventions](https://github.com/open-telemetry/semantic-conventions/tree/main/docs/gen-ai)
- [TNTSearch Documentation](https://github.com/teamtnt/tntsearch)

---

**Last Updated:** November 1, 2025  
**Next Review:** Weekly during active development
