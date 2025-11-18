# Mindwave Pivot: Comprehensive Implementation Plan

**Vision:** Transform Mindwave from "yet another agent framework" into Laravel's premier AI utilities toolkit for production-grade LLM features.

**Tagline:** *"The working developer's AI toolkit for Laravel - long prompts, streaming, tracing, and context discovery made simple."*

---

## 🎯 Strategic Positioning

### What We're NOT Building
- ❌ Another agent orchestration framework (Prism, Neuron-AI, LangChain clones)
- ❌ Multi-agent coordination systems
- ❌ Complex workflow engines
- ❌ Tool/function calling frameworks

### What We ARE Building
- ✅ **Context Window Management** - Auto-fit long prompts to model limits
- ✅ **Production Streaming** - EventSource SSE that "just works"
- ✅ **OpenTelemetry Tracing** - Industry-standard observability with database storage
- ✅ **Ad-Hoc Context Discovery** - TNTSearch-powered retrieval from DB/CSV
- ✅ **Laravel-Native DX** - Facades, commands, migrations, events

---

## 📊 Four Pillars Architecture

### Pillar 1: Prompt Composer with Context Management
**Problem:** Developers struggle with token limits and manually managing context windows.

**Solution:**
```php
Mindwave::prompt()
    ->reserveOutputTokens(512)
    ->section('system', $instructions, priority: 100)
    ->section('context', $largeDoc, priority: 50, shrinker: 'summarize')
    ->section('history', $messages, priority: 75, shrinker: 'truncate')
    ->section('user', $question, priority: 100)
    ->fit() // Auto-trims to model's context window
    ->run();
```

### Pillar 2: OpenTelemetry Tracing with Database Storage
**Problem:** No visibility into LLM operations, costs, performance, or debugging capabilities.

**Solution:** Full OpenTelemetry GenAI semantic conventions support with dual storage (OTLP exporters + database).

```php
// Automatic tracing of all LLM calls
$response = Mindwave::llm()->chat($messages);

// View in Jaeger/Grafana or query database
$traces = MindwaveTrace::where('operation', 'chat')
    ->where('cost', '>', 0.10)
    ->with('spans')
    ->get();
```

### Pillar 3: Streaming SSE (EventSource)
**Problem:** Implementing streaming responses correctly is complex (buffering, headers, error handling).

**Solution:**
```php
// Backend (3 lines)
return Mindwave::stream($prompt)
    ->model('gpt-4')
    ->respond();

// Frontend (3 lines)
const stream = new EventSource('/api/chat?q=' + query);
stream.onmessage = e => output.textContent += e.data;
stream.addEventListener('done', () => stream.close());
```

### Pillar 4: TNTSearch Ad-Hoc Context Discovery
**Problem:** Need context from application data without complex RAG infrastructure.

**Solution:**
```php
Mindwave::prompt()
    ->context(
        TntSearchSource::fromEloquent(
            User::where('active', true),
            fn($u) => "Name: {$u->name}, Skills: {$u->skills}"
        )
    )
    ->context(
        TntSearchSource::fromCsv('data/products.csv', ['name', 'description'])
    )
    ->ask('Who has Laravel expertise?');
```

---

## 🏗️ Technical Architecture

### Core Components

```
mindwave/
├── LLM/
│   ├── LLMManager.php (existing, enhance)
│   ├── Drivers/
│   │   ├── OpenAI.php (add streaming + maxTokens)
│   │   ├── Mistral.php (fix config, add streaming)
│   │   └── Anthropic.php (new)
│   └── Streaming/
│       ├── StreamedResponse.php
│       └── SseFormatter.php
├── PromptComposer/
│   ├── PromptComposer.php
│   ├── Section.php
│   ├── Shrinkers/
│   │   ├── SummarizeShrinker.php
│   │   ├── TruncateShrinker.php
│   │   └── CompressShrinker.php
│   └── Tokenizer/
│       ├── TokenizerInterface.php
│       ├── TiktokenTokenizer.php (existing lib)
│       └── ModelTokenLimits.php
├── Observability/
│   ├── Tracing/
│   │   ├── TracerManager.php
│   │   ├── Span.php
│   │   ├── Trace.php (Eloquent model)
│   │   ├── SpanData.php (Eloquent model)
│   │   ├── Exporters/
│   │   │   ├── OtlpExporter.php (OTLP HTTP/gRPC)
│   │   │   ├── DatabaseExporter.php (local DB)
│   │   │   └── MultiExporter.php (fan-out)
│   │   └── GenAI/
│   │       ├── GenAiAttributes.php (semantic conventions)
│   │       └── GenAiInstrumentor.php
│   ├── Events/
│   │   ├── LlmRequestStarted.php
│   │   ├── LlmTokenStreamed.php
│   │   ├── LlmResponseCompleted.php
│   │   └── LlmErrorOccurred.php
│   └── Listeners/
│       └── TraceEventSubscriber.php
├── Context/
│   ├── ContextSource.php (interface)
│   ├── ContextPipeline.php
│   ├── Sources/
│   │   ├── TntSearchSource.php
│   │   ├── VectorStoreSource.php (Brain integration)
│   │   ├── StaticSource.php
│   │   └── EloquentSource.php
│   └── TntSearch/
│       ├── EphemeralIndexer.php
│       ├── IndexCache.php
│       └── SearchRanker.php
├── Commands/
│   ├── ExportTracesCommand.php
│   ├── PruneTracesCommand.php
│   └── IndexStatsCommand.php
└── Facades/
    └── Mindwave.php (updated)
```

---

## 📦 Deliverables & Acceptance Criteria

### Phase 1: Foundation & Cleanup (Week 1)

#### D1.1: Codebase Cleanup
- [ ] Remove `src/Agents/` directory
- [ ] Remove `src/Crew/` directory
- [ ] Remove agent-related tests
- [ ] Update README with new vision
- [ ] Create PIVOT_PLAN.md (this document)
- [ ] Update TODO.md with new roadmap

**Acceptance:** No agent code remains, README reflects utilities focus

#### D1.2: Dependency Updates
- [ ] Resolve Weaviate dependency issue (remove or wait for Laravel 11 support)
- [ ] Add `teamtnt/tntsearch` and `teamtnt/laravel-scout-tntsearch-driver`
- [ ] Add `open-telemetry/sdk` and related packages
- [ ] Add `open-telemetry/semantic-conventions`
- [ ] Update `composer.json` to reflect new focus

**Acceptance:** `composer install` succeeds, all tests pass

#### D1.3: LLM Driver Fixes
- [ ] Fix Mistral config key bug (reads OpenAI keys)
- [ ] Add `maxContextTokens(): int` to LLM interface
- [ ] Implement `maxContextTokens()` for OpenAI driver
- [ ] Implement `maxContextTokens()` for Mistral driver
- [ ] Add tests for token limit retrieval

**Acceptance:** All LLM drivers correctly report context window sizes

---

### Phase 2: Pillar 1 - Prompt Composer (Week 2)

#### D2.1: Tokenizer Service
- [ ] Create `TokenizerInterface`
- [ ] Implement `TiktokenTokenizer` (wraps yethee/tiktoken)
- [ ] Create `ModelTokenLimits` configuration
- [ ] Add service provider binding
- [ ] Write unit tests (20+ test cases)

**Acceptance:** Can count tokens accurately for GPT-4, GPT-3.5, Claude models

#### D2.2: Section Management
- [ ] Create `Section` value object
- [ ] Implement priority-based sorting
- [ ] Create section registry
- [ ] Add section validation
- [ ] Write unit tests

**Acceptance:** Sections can be created, prioritized, and validated

#### D2.3: Shrinkers
- [ ] Create `ShrinkerInterface`
- [ ] Implement `TruncateShrinker` (sentence-aware)
- [ ] Implement `SummarizeShrinker` (uses LLM)
- [ ] Implement `CompressShrinker` (removes whitespace, etc.)
- [ ] Add shrinker configuration
- [ ] Write unit tests

**Acceptance:** Each shrinker reduces content predictably while maintaining coherence

#### D2.4: Prompt Composer Core
- [ ] Create `PromptComposer` class
- [ ] Implement `section()` method
- [ ] Implement `reserveOutputTokens()` method
- [ ] Implement `fit()` algorithm
- [ ] Implement `toMessages()` and `toText()` conversion
- [ ] Add `run()` method (executes LLM)
- [ ] Write integration tests (10+ scenarios)

**Acceptance:** Long prompts automatically fit into model context windows

#### D2.5: Facade Integration
- [ ] Add `Mindwave::prompt()` factory method
- [ ] Update facade documentation
- [ ] Create usage examples
- [ ] Write feature tests

**Acceptance:**
```php
Mindwave::prompt()
    ->section('system', $sys)
    ->section('user', $question)
    ->fit()
    ->run();
```

---

### Phase 3: Pillar 2 - OpenTelemetry Tracing (Week 3)

#### D3.1: Database Schema
- [ ] Create `mindwave_traces` migration
- [ ] Create `mindwave_spans` migration
- [ ] Add proper indexes for query performance
- [ ] Create `Trace` Eloquent model
- [ ] Create `Span` Eloquent model
- [ ] Add model relationships

**Schema:**
```sql
CREATE TABLE mindwave_traces (
    id CHAR(36) PRIMARY KEY,
    trace_id CHAR(32) UNIQUE NOT NULL,
    service_name VARCHAR(255),
    start_time BIGINT UNSIGNED,
    end_time BIGINT UNSIGNED,
    duration BIGINT UNSIGNED,
    status VARCHAR(20),
    created_at TIMESTAMP,
    INDEX idx_service_created (service_name, created_at),
    INDEX idx_trace_id (trace_id),
    INDEX idx_duration (duration)
);

CREATE TABLE mindwave_spans (
    id CHAR(36) PRIMARY KEY,
    trace_id CHAR(32) NOT NULL,
    span_id CHAR(16) UNIQUE NOT NULL,
    parent_span_id CHAR(16),
    name VARCHAR(255),
    kind VARCHAR(20),
    start_time BIGINT UNSIGNED,
    end_time BIGINT UNSIGNED,
    duration BIGINT UNSIGNED,
    attributes JSON,
    events JSON,
    status_code VARCHAR(20),
    status_description TEXT,
    created_at TIMESTAMP,
    INDEX idx_trace_id (trace_id),
    INDEX idx_span_id (span_id),
    INDEX idx_parent (parent_span_id),
    INDEX idx_name (name),
    FOREIGN KEY (trace_id) REFERENCES mindwave_traces(trace_id)
);
```

**Acceptance:** Migrations run successfully, models have proper relationships

#### D3.2: GenAI Semantic Conventions
- [ ] Create `GenAiAttributes` class (all OpenTelemetry GenAI attributes)
- [ ] Create `GenAiOperations` enum
- [ ] Create `GenAiProviders` enum
- [ ] Add attribute validators
- [ ] Write unit tests

**Acceptance:** All standard GenAI attributes are available and typed

#### D3.3: Tracer Core
- [ ] Create `TracerManager` (manages TracerProvider)
- [ ] Create `SpanBuilder` wrapper
- [ ] Implement context propagation
- [ ] Add span activation/deactivation
- [ ] Write unit tests

**Acceptance:** Can create nested spans with proper parent-child relationships

#### D3.4: Database Exporter
- [ ] Implement `DatabaseSpanExporter` (SpanExporterInterface)
- [ ] Add batch processing for performance
- [ ] Implement retry logic
- [ ] Add error handling
- [ ] Write integration tests

**Acceptance:** Spans are reliably stored in database with all attributes

#### D3.5: OTLP Exporter
- [ ] Configure OTLP HTTP exporter
- [ ] Configure OTLP gRPC exporter
- [ ] Add configuration for endpoint/headers
- [ ] Test with Jaeger
- [ ] Test with Grafana Tempo

**Acceptance:** Traces appear in Jaeger UI with all GenAI attributes

#### D3.6: Multi-Exporter
- [ ] Create `MultiExporter` (fan-out pattern)
- [ ] Add configuration for multiple backends
- [ ] Implement partial failure handling
- [ ] Write tests

**Acceptance:** Traces sent to database AND OTLP backend simultaneously

#### D3.7: LLM Instrumentation
- [ ] Create `GenAiInstrumentor`
- [ ] Wrap OpenAI driver with span creation
- [ ] Wrap Mistral driver with span creation
- [ ] Capture request attributes (model, temperature, etc.)
- [ ] Capture response attributes (tokens, finish_reason)
- [ ] Capture streaming token deltas
- [ ] Write integration tests

**Acceptance:** All LLM calls automatically create spans with GenAI attributes

#### D3.8: Events System
- [ ] Create `LlmRequestStarted` event
- [ ] Create `LlmTokenStreamed` event (for streaming)
- [ ] Create `LlmResponseCompleted` event
- [ ] Create `LlmErrorOccurred` event
- [ ] Create `TraceEventSubscriber` listener
- [ ] Register events in service provider
- [ ] Write event tests

**Acceptance:** Events fire for all LLM operations and can be listened to

#### D3.9: Configuration
- [ ] Create `config/mindwave-tracing.php`
- [ ] Add database exporter enable/disable
- [ ] Add OTLP exporter configuration
- [ ] Add PII redaction settings
- [ ] Add sampling configuration
- [ ] Add retention policy settings
- [ ] Document all options

**Acceptance:** Tracing can be configured via config file and .env

#### D3.10: Artisan Commands
- [ ] Create `mindwave:export-traces` command (CSV/JSON/NDJSON)
- [ ] Create `mindwave:prune-traces` command (cleanup old traces)
- [ ] Create `mindwave:trace-stats` command (summary statistics)
- [ ] Add command tests

**Acceptance:**
```bash
php artisan mindwave:export-traces --since=yesterday --format=csv
php artisan mindwave:prune-traces --older-than=30days
php artisan mindwave:trace-stats
```

---

### Phase 4: Pillar 3 - Streaming SSE (Week 4)

#### D4.1: Streaming LLM Interface
- [ ] Add `streamText()` to LLM interface
- [ ] Implement OpenAI streaming (already in openai-php/client)
- [ ] Implement Mistral streaming
- [ ] Add streaming event emission
- [ ] Write streaming tests

**Acceptance:** Can iterate over streaming responses

#### D4.2: SSE Formatter
- [ ] Create `SseFormatter` class
- [ ] Implement EventSource format (data: \n\n)
- [ ] Add named events support
- [ ] Add heartbeat mechanism
- [ ] Write unit tests

**Acceptance:** Outputs valid SSE format that browsers can parse

#### D4.3: StreamedResponse Helper
- [ ] Create `Mindwave::stream()` factory
- [ ] Return Laravel `StreamedResponse`
- [ ] Set proper headers (Content-Type, X-Accel-Buffering)
- [ ] Add connection abort detection
- [ ] Add error handling
- [ ] Write integration tests

**Acceptance:**
```php
return Mindwave::stream('Tell me a story')
    ->model('gpt-4')
    ->respond();
```

#### D4.4: Client-Side Examples
- [ ] Create Blade component example
- [ ] Create vanilla JS example
- [ ] Create Alpine.js example
- [ ] Add to documentation

**Acceptance:** Copy-paste examples work out of the box

---

### Phase 5: Pillar 4 - TNTSearch Context Discovery (Week 5-6)

#### D5.1: TNTSearch Integration
- [ ] Install `teamtnt/tntsearch` dependency
- [ ] Create ephemeral index manager
- [ ] Implement index caching (hash-based)
- [ ] Add TTL cleanup
- [ ] Write tests

**Acceptance:** Can create/destroy ephemeral TNT indexes

#### D5.2: Context Source Interface
- [ ] Create `ContextSource` interface
- [ ] Create `ContextItem` value object
- [ ] Add ranking/scoring
- [ ] Write interface tests

**Acceptance:** Context sources return ranked, scored items

#### D5.3: TNTSearch Sources
- [ ] Implement `TntSearchSource::fromEloquent()`
- [ ] Implement `TntSearchSource::fromArray()`
- [ ] Implement `TntSearchSource::fromCsv()`
- [ ] Add fuzzy search support
- [ ] Write integration tests

**Acceptance:**
```php
TntSearchSource::fromEloquent(
    User::query(),
    fn($u) => "Name: {$u->name}"
)->search('john', limit: 10);
```

#### D5.4: Other Context Sources
- [ ] Create `VectorStoreSource` (wraps existing Brain)
- [ ] Create `StaticSource` (hardcoded context)
- [ ] Create `EloquentSource` (direct DB query, no search)
- [ ] Write tests

**Acceptance:** Multiple context source types available

#### D5.5: Context Pipeline
- [ ] Create `ContextPipeline` class
- [ ] Implement multi-source aggregation
- [ ] Add deduplication logic
- [ ] Implement re-ranking
- [ ] Add token budget allocation
- [ ] Write integration tests

**Acceptance:** Can combine multiple sources and fit within token budget

#### D5.6: Prompt Composer Integration
- [ ] Add `context()` method to PromptComposer
- [ ] Auto-create context section
- [ ] Apply token budgeting
- [ ] Write feature tests

**Acceptance:**
```php
Mindwave::prompt()
    ->context(TntSearchSource::fromCsv('data.csv'))
    ->ask('What products are available?');
```

#### D5.7: Artisan Commands
- [ ] Create `mindwave:index-stats` command
- [ ] Create `mindwave:clear-indexes` command
- [ ] Add scheduled cleanup task
- [ ] Write command tests

**Acceptance:**
```bash
php artisan mindwave:index-stats
php artisan mindwave:clear-indexes --older-than=7days
```

---

### Phase 6: Documentation & Polish (Week 7)

#### D6.1: Documentation
- [ ] Update README with new focus
- [ ] Create Quick Start guide
- [ ] Write PromptComposer documentation
- [ ] Write Streaming documentation
- [ ] Write Tracing documentation
- [ ] Write TNTSearch documentation
- [ ] Add troubleshooting guide
- [ ] Add performance tuning guide

**Acceptance:** Developers can get started in < 10 minutes

#### D6.2: Examples
- [ ] Create demo app repository
- [ ] Add chatbot example
- [ ] Add document Q&A example
- [ ] Add cost tracking dashboard example
- [ ] Add streaming chat UI example

**Acceptance:** Working examples for each pillar

#### D6.3: Testing
- [ ] Achieve >90% code coverage
- [ ] Add performance benchmarks
- [ ] Test with multiple PHP versions (8.2, 8.3, 8.4)
- [ ] Test with Laravel 11+
- [ ] Add CI/CD pipeline

**Acceptance:** All tests pass, coverage target met

#### D6.4: Configuration
- [ ] Review all config files
- [ ] Add sensible defaults
- [ ] Document all options
- [ ] Add .env.example updates

**Acceptance:** Zero-config for basic usage, fully configurable for advanced

---

## 📅 Timeline & Milestones

### Week 1: Foundation (Nov 1-7, 2025)
- **Phase 1 Complete**
- Milestone: Clean codebase, updated dependencies, working LLM drivers

### Week 2: Prompt Composer (Nov 8-14, 2025)
- **Phase 2 Complete**
- Milestone: Can auto-fit long prompts to any model's context window

### Week 3: Tracing Part 1 (Nov 15-21, 2025)
- **Phase 3 (D3.1-D3.6) Complete**
- Milestone: Database schema, exporters, basic tracing working

### Week 4: Tracing Part 2 + Streaming (Nov 22-28, 2025)
- **Phase 3 (D3.7-D3.10) Complete**
- **Phase 4 Complete**
- Milestone: Full OpenTelemetry support, streaming SSE functional

### Week 5-6: TNTSearch Context (Nov 29 - Dec 12, 2025)
- **Phase 5 Complete**
- Milestone: Ad-hoc context discovery from DB/CSV working

### Week 7: Documentation & Release (Dec 13-19, 2025)
- **Phase 6 Complete**
- Milestone: v1.0.0 release

---

## 🔧 Development Process

### Daily Workflow
1. Pick deliverable from current phase
2. Write tests first (TDD)
3. Implement feature
4. Update documentation
5. Mark deliverable complete
6. Commit with conventional commits

### Code Standards
- PSR-12 coding standard
- 90%+ test coverage
- No suppressed errors (@ts-expect-error equivalent)
- Type hints everywhere
- Descriptive variable names

### Review Process
- Self-review checklist before marking complete
- Run full test suite
- Test with demo app
- Update CHANGELOG.md

---

## 📊 Success Metrics

### Technical KPIs
- [ ] Zero agent framework code remaining
- [ ] 90%+ test coverage
- [ ] All 4 pillars functional
- [ ] < 10 minute quick start
- [ ] < 100ms overhead for tracing

### Adoption Metrics (Post-Launch)
- [ ] 50 GitHub stars in first month
- [ ] 10 production users
- [ ] 5 community contributions
- [ ] Featured in Laravel News

---

## 🚨 Risk Management

### Technical Risks

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| OpenTelemetry PHP maturity | High | Medium | Use stable packages, test extensively |
| TNTSearch performance with large datasets | Medium | Medium | Add index size limits, caching |
| Streaming buffering issues | High | Low | Document Nginx config, add detection |
| Token counting accuracy | High | Low | Use official tiktoken library |

### Scope Risks

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| Feature creep back to agent framework | High | Medium | Strict scope control, refer to this doc |
| Timeline slippage | Medium | Medium | Break into smaller milestones |
| Over-engineering | Medium | Low | Start simple, iterate based on feedback |

---

## 🎯 Post-V1.0 Roadmap

### V1.1 (January 2026)
- [ ] Add more LLM providers (Anthropic, Cohere, Groq)
- [ ] Advanced shrinkers (semantic compression)
- [ ] Cost estimation and budgets
- [ ] Grafana dashboard templates

### V1.2 (February 2026)
- [ ] Prompt testing framework
- [ ] A/B testing for prompts
- [ ] Batch processing utilities
- [ ] Queue integration

### V2.0 (Q2 2026)
- [ ] Multi-modal support (images, audio)
- [ ] Advanced re-ranking algorithms
- [ ] Distributed tracing across microservices
- [ ] Real-time streaming analytics

---

## 📝 Notes

### Design Decisions
- **Why OpenTelemetry?** Industry standard, future-proof, interoperable
- **Why Database + OTLP?** Database for easy queries, OTLP for production observability tools
- **Why TNTSearch?** Pure PHP, no infrastructure, perfect for ad-hoc needs
- **Why not vector stores by default?** Too much setup for simple use cases, optional Brain for advanced needs

### Philosophy
> "Make the simple things simple, and the complex things possible."

Every feature should have:
1. **Zero-config default** - Works out of the box
2. **Sensible middleware** - Common cases covered
3. **Full control option** - Advanced users can customize

---

## ✅ Definition of Done

A deliverable is "done" when:
- [ ] Tests written and passing (>90% coverage for that component)
- [ ] Documentation updated
- [ ] Code reviewed (self-review checklist)
- [ ] Integrated with demo app
- [ ] CHANGELOG.md updated
- [ ] Marked complete in this document

---

**Document Version:** 1.0  
**Last Updated:** November 1, 2025  
**Next Review:** Weekly during implementation
