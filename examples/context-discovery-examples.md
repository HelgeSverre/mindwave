# Context Discovery Examples

This guide demonstrates how to use Mindwave's Context Discovery feature to pull relevant information from your application data and inject it into LLM prompts.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Data Sources](#data-sources)
  - [Eloquent Models](#eloquent-models)
  - [CSV Files](#csv-files)
  - [Arrays](#arrays)
  - [Vector Stores (Semantic Search)](#vector-stores-semantic-search)
  - [Static Content (FAQs)](#static-content-faqs)
- [Multi-Source Pipeline](#multi-source-pipeline)
- [PromptComposer Integration](#promptcomposer-integration)
- [Advanced Features](#advanced-features)
- [Performance Considerations](#performance-considerations)
- [Tracing and Observability](#tracing-and-observability)

---

## Basic Usage

Context Discovery allows you to search through data sources and automatically inject relevant results into your prompts:

```php
use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;

// Create a searchable source from an array
$source = TntSearchSource::fromArray([
    'Laravel is a PHP web framework with expressive syntax',
    'Vue.js is a progressive JavaScript framework',
    'Python Django is a high-level web framework',
]);

// Search and inject into prompt
$response = Mindwave::prompt()
    ->context($source, query: 'PHP framework')
    ->section('user', 'Tell me about PHP frameworks')
    ->run();
```

---

## Data Sources

### Eloquent Models

Search through your Eloquent models using full-text search:

```php
use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;
use App\Models\User;

// Create source from Eloquent query with custom transformer
$userSource = TntSearchSource::fromEloquent(
    User::where('active', true)->where('role', 'developer'),
    fn($user) => "Name: {$user->name}, Skills: {$user->skills}, Bio: {$user->bio}",
    name: 'active-developers'
);

// Use in prompt
$response = Mindwave::prompt()
    ->context($userSource, query: 'Laravel expert with Vue experience')
    ->section('user', 'Who should I assign to the new Laravel + Vue project?')
    ->run();

// Metadata is preserved (model_id, model_type)
```

**Real-world example: Customer Support**

```php
use App\Models\SupportTicket;

$ticketSource = TntSearchSource::fromEloquent(
    SupportTicket::where('status', 'resolved')
        ->where('rating', '>=', 4),
    fn($ticket) => "Issue: {$ticket->title}\nSolution: {$ticket->resolution}",
    name: 'resolved-tickets'
);

$response = Mindwave::prompt()
    ->section('system', 'You are a customer support agent. Use past resolutions to help.')
    ->context($ticketSource, query: 'password reset not working')
    ->section('user', 'Customer cannot reset their password')
    ->run();
```

### CSV Files

Index and search CSV files:

```php
use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;

// Search all columns
$productSource = TntSearchSource::fromCsv(
    filepath: storage_path('data/products.csv')
);

// Or specify which columns to index
$faqSource = TntSearchSource::fromCsv(
    filepath: storage_path('data/faq.csv'),
    columns: ['question', 'answer'],
    name: 'product-faq'
);

$response = Mindwave::prompt()
    ->context($faqSource, query: 'refund policy')
    ->section('user', 'How do I request a refund?')
    ->run();
```

**CSV Format Example:**

```csv
question,answer,category
How do I reset my password?,Click 'Forgot Password' on the login page,Account
What is your refund policy?,Full refunds within 30 days of purchase,Billing
How do I upgrade my plan?,Go to Settings > Billing > Change Plan,Billing
```

### Arrays

Create searchable sources from in-memory arrays:

```php
use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;

// Simple string array
$docs = [
    'Laravel provides an expressive ORM called Eloquent',
    'Vue.js uses a virtual DOM for efficient rendering',
    'Docker containers package applications with dependencies',
];

$source = TntSearchSource::fromArray($docs);

// Structured data
$apiDocs = [
    ['endpoint' => 'POST /users', 'description' => 'Create a new user'],
    ['endpoint' => 'GET /users/:id', 'description' => 'Retrieve user details'],
];

$apiSource = TntSearchSource::fromArray($apiDocs, name: 'api-docs');
```

### Vector Stores (Semantic Search)

Use Mindwave's Brain for semantic similarity search:

```php
use Mindwave\Mindwave\Context\Sources\VectorStoreSource;

// Assuming you've already stored embeddings in Brain
$brain = Mindwave::brain('documentation');

$vectorSource = VectorStoreSource::from($brain, name: 'docs-vectorstore');

// Semantic search (finds conceptually similar content, not just keywords)
$response = Mindwave::prompt()
    ->context($vectorSource, query: 'authentication mechanisms')
    ->section('user', 'How do I implement login?')
    ->run();

// Will find content about "OAuth", "JWT", "sessions" even without exact word matches
```

### Static Content (FAQs)

For hardcoded content with keyword matching:

```php
use Mindwave\Mindwave\Context\Sources\StaticSource;

// Simple strings
$faqSource = StaticSource::fromStrings([
    'Our office hours are Monday-Friday, 9 AM to 5 PM EST',
    'We accept Visa, Mastercard, and American Express',
    'Shipping takes 3-5 business days for domestic orders',
]);

// Structured with custom keywords
$policiesSource = StaticSource::fromStrings([
    [
        'content' => 'Full refunds within 30 days, partial refunds up to 60 days',
        'keywords' => ['refund', 'return', 'money back', 'cancel'],
    ],
    [
        'content' => 'Enterprise plans include priority support and dedicated account manager',
        'keywords' => ['enterprise', 'business', 'support', 'SLA'],
    ],
]);

$response = Mindwave::prompt()
    ->context($policiesSource, query: 'return policy')
    ->section('user', 'Can I get my money back?')
    ->run();
```

---

## Multi-Source Pipeline

Combine multiple sources for comprehensive context:

```php
use Mindwave\Mindwave\Context\ContextPipeline;
use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;
use Mindwave\Mindwave\Context\Sources\VectorStoreSource;
use Mindwave\Mindwave\Context\Sources\StaticSource;

// Create multiple sources
$userSource = TntSearchSource::fromEloquent(
    User::where('active', true),
    fn($u) => "Expert: {$u->name}, Skills: {$u->skills}"
);

$docsSource = VectorStoreSource::from(Mindwave::brain('docs'));

$faqSource = StaticSource::fromStrings([
    'Internal projects require manager approval',
    'Use Slack for urgent communications',
]);

// Combine into pipeline
$pipeline = (new ContextPipeline)
    ->addSource($userSource)
    ->addSource($docsSource)
    ->addSource($faqSource)
    ->deduplicate(true)  // Remove duplicates (default: true)
    ->rerank(true);      // Sort by relevance (default: true)

// Use in prompt
$response = Mindwave::prompt()
    ->context($pipeline, query: 'project approval process', limit: 10)
    ->section('user', 'How do I start a new internal project?')
    ->run();
```

**Pipeline Benefits:**
- **Deduplication**: Automatically removes duplicate content from different sources
- **Re-ranking**: Sorts all results by relevance score
- **Limit enforcement**: Controls total number of context items

---

## PromptComposer Integration

Context Discovery integrates seamlessly with PromptComposer:

### Auto Query Extraction

The query is automatically extracted from the user's message:

```php
$source = TntSearchSource::fromArray([...]);

Mindwave::prompt()
    ->context($source)  // No query needed!
    ->section('user', 'How do I deploy to production?')
    ->run();

// Query "How do I deploy to production?" is automatically used for search
```

### Explicit Query

Override the auto-extracted query:

```php
Mindwave::prompt()
    ->section('user', 'Can you help me with something?')
    ->context($source, query: 'deployment process')  // Explicit query
    ->run();
```

### Priority and Shrinkers

Context sections respect PromptComposer's priority and shrinking:

```php
Mindwave::prompt()
    ->section('system', 'You are a helpful assistant', priority: 100)
    ->context($source, priority: 75, query: 'Laravel')  // Will shrink before system
    ->section('user', 'Question?', priority: 100)
    ->reserveOutputTokens(500)
    ->fit()
    ->run();
```

### Backward Compatibility

String and array context still work:

```php
// Old way (still works)
Mindwave::prompt()
    ->context('Hardcoded context information')
    ->section('user', 'Question')
    ->run();

// New way (with search)
Mindwave::prompt()
    ->context($source, query: 'search term')
    ->section('user', 'Question')
    ->run();
```

---

## Advanced Features

### Limiting Results

```php
// Get top 3 results
Mindwave::prompt()
    ->context($source, query: 'Laravel', limit: 3)
    ->section('user', 'Tell me about Laravel')
    ->run();
```

### Custom Formatting

```php
use Mindwave\Mindwave\Context\ContextCollection;

$source = TntSearchSource::fromArray([...]);
$results = $source->search('Laravel', 5);

// Numbered format (default)
echo $results->formatForPrompt('numbered');
// Output:
// [1] (score: 0.95, source: tntsearch)
// Laravel is a PHP framework...
//
// [2] (score: 0.87, source: tntsearch)
// Laravel provides...

// Markdown format
echo $results->formatForPrompt('markdown');
// Output:
// ### Context 1 (score: 0.95)
// Laravel is a PHP framework...
// *Source: tntsearch*

// JSON format
echo $results->formatForPrompt('json');
// Output: [{"content": "...", "score": 0.95, ...}]
```

### Token Management

```php
use Mindwave\Mindwave\PromptComposer\Tokenizer\TiktokenTokenizer;

$results = $source->search('Laravel', 20);

// Check token count
$totalTokens = $results->getTotalTokens();

// Truncate to fit budget
$truncated = $results->truncateToTokens(1000, 'gpt-4');
```

### Metadata Access

```php
$source = TntSearchSource::fromEloquent(
    User::all(),
    fn($u) => $u->bio
);

$results = $source->search('Laravel expert');

foreach ($results as $item) {
    echo $item->content;      // Bio text
    echo $item->score;         // Relevance score (0.0 - 1.0)
    echo $item->source;        // 'eloquent-search'
    echo $item->metadata['model_id'];    // User ID
    echo $item->metadata['model_type'];  // 'App\Models\User'
}
```

---

## Performance Considerations

### Dataset Sizes

**Recommended limits:**
- **TNTSearch**: Best for < 10,000 documents
- **Eloquent Source**: Best for < 1,000 records (uses LIKE)
- **VectorStore**: Scales to millions (uses vector similarity)
- **Static Source**: Best for < 100 items (in-memory keyword matching)

### Index Lifecycle

TNTSearch creates ephemeral indexes that are automatically cleaned up:

```php
// Index is created when initialized
$source = TntSearchSource::fromArray([...]);
$source->initialize();  // Creates temp SQLite index

// Search multiple times (reuses same index)
$results1 = $source->search('query 1');
$results2 = $source->search('query 2');

// Cleanup when done (automatic on destruction)
$source->cleanup();  // Deletes temp index
```

### Manual Index Management

```bash
# View index statistics
php artisan mindwave:index-stats

# Clean old indexes (default: 24 hours)
php artisan mindwave:clear-indexes

# Custom TTL
php artisan mindwave:clear-indexes --ttl=12

# Skip confirmation
php artisan mindwave:clear-indexes --force
```

### Configuration

Customize in `config/mindwave-context.php`:

```php
return [
    'tntsearch' => [
        'storage_path' => storage_path('mindwave/tnt-indexes'),
        'ttl_hours' => env('MINDWAVE_TNT_INDEX_TTL', 24),
        'max_index_size_mb' => env('MINDWAVE_TNT_MAX_INDEX_SIZE', 100),
    ],
    'pipeline' => [
        'default_limit' => 10,
        'deduplicate' => true,
        'format' => 'numbered',
    ],
];
```

---

## Tracing and Observability

Context searches are automatically traced with OpenTelemetry:

### Span Attributes

Each search creates a span with:
- `context.source`: Source name
- `context.source.type`: 'tntsearch', 'vectorstore', etc.
- `context.query`: Search query
- `context.limit`: Result limit
- `context.result_count`: Number of results found
- `context.index_name`: TNTSearch index name (if applicable)

### Configuration

```php
// config/mindwave-context.php
'tracing' => [
    'enabled' => env('MINDWAVE_CONTEXT_TRACING', true),
    'trace_searches' => true,
    'trace_index_creation' => true,
],
```

### Example Trace

```
Span: context.search
  ├─ context.source = "user-database"
  ├─ context.source.type = "tntsearch"
  ├─ context.query = "Laravel expert"
  ├─ context.limit = 5
  ├─ context.result_count = 3
  ├─ context.index_name = "ephemeral_abc123"
  └─ duration = 45ms
```

---

## Complete Examples

### Example 1: Customer Support Bot

```php
use App\Models\SupportTicket;
use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;
use Mindwave\Mindwave\Context\Sources\StaticSource;
use Mindwave\Mindwave\Context\ContextPipeline;

// Past resolved tickets
$ticketSource = TntSearchSource::fromEloquent(
    SupportTicket::where('status', 'resolved')->where('rating', '>=', 4),
    fn($t) => "Issue: {$t->title}\nResolution: {$t->resolution}",
    name: 'resolved-tickets'
);

// Company policies
$policySource = StaticSource::fromStrings([
    'Refunds: Full refund within 30 days, partial within 60 days',
    'Support hours: Mon-Fri 9 AM - 5 PM EST, tickets answered within 24h',
    'Enterprise SLA: 4-hour response time, 99.9% uptime guarantee',
]);

// Combine sources
$pipeline = (new ContextPipeline)
    ->addSource($ticketSource)
    ->addSource($policySource);

// Handle support request
$response = Mindwave::prompt()
    ->section('system', 'You are a friendly customer support agent. Use past resolutions and company policies.')
    ->context($pipeline, limit: 5)
    ->section('user', 'I want to cancel my subscription and get a refund')
    ->run();

echo $response->content;
```

### Example 2: Code Documentation Assistant

```php
use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;
use Mindwave\Mindwave\Context\Sources\VectorStoreSource;
use Mindwave\Mindwave\Context\ContextPipeline;

// Search codebase documentation
$docsSource = TntSearchSource::fromCsv(
    storage_path('docs/api-reference.csv'),
    columns: ['endpoint', 'description', 'example']
);

// Semantic search in tutorials
$tutorialSource = VectorStoreSource::from(
    Mindwave::brain('tutorials'),
    name: 'tutorial-embeddings'
);

$pipeline = (new ContextPipeline)
    ->addSource($docsSource)
    ->addSource($tutorialSource);

$response = Mindwave::prompt()
    ->section('system', 'You are a coding assistant. Provide accurate examples based on documentation.')
    ->context($pipeline, query: 'user authentication')
    ->section('user', 'How do I implement JWT authentication in our API?')
    ->run();
```

### Example 3: HR Knowledge Base

```php
use App\Models\Employee;
use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;

$employeeSource = TntSearchSource::fromEloquent(
    Employee::where('department', 'engineering'),
    fn($e) => "Name: {$e->name}\nSkills: {$e->skills}\nProjects: {$e->past_projects}\nAvailability: {$e->availability}",
    name: 'engineers'
);

$policySource = TntSearchSource::fromCsv(
    storage_path('hr/policies.csv'),
    columns: ['policy', 'description']
);

$pipeline = (new ContextPipeline)
    ->addSource($employeeSource)
    ->addSource($policySource);

$response = Mindwave::prompt()
    ->section('system', 'You are an HR assistant helping with team assignments.')
    ->context($pipeline, query: 'React developers available')
    ->section('user', 'I need 2 React developers for a 3-month project starting next week')
    ->run();
```

---

## Best Practices

1. **Choose the right source type:**
   - TNTSearch: Keyword-based full-text search
   - VectorStore: Semantic similarity
   - Eloquent: Small datasets with dynamic queries
   - Static: Fixed content, FAQs

2. **Use pipelines for comprehensive coverage:**
   - Combine multiple source types
   - Deduplicate to avoid repetition
   - Re-rank for best results first

3. **Optimize token usage:**
   - Set appropriate `limit` values
   - Use `truncateToTokens()` for large results
   - Set context priority lower than critical sections

4. **Monitor with tracing:**
   - Enable OpenTelemetry tracing
   - Track search performance
   - Identify slow sources

5. **Clean up indexes:**
   - Run `mindwave:clear-indexes` periodically
   - Set appropriate TTL in config
   - Monitor disk usage with `mindwave:index-stats`

---

## Troubleshooting

### "Index not found" error
```php
// Make sure to initialize before searching
$source->initialize();
$results = $source->search('query');
```

### Poor search results
```php
// Try different source types
// TNTSearch is keyword-based
$tntSource = TntSearchSource::fromArray([...]);

// VectorStore is semantic
$vectorSource = VectorStoreSource::from(Mindwave::brain());

// Or combine both
$pipeline = (new ContextPipeline)
    ->addSource($tntSource)
    ->addSource($vectorSource);
```

### Too many tokens
```php
// Reduce limit
->context($source, limit: 3)

// Or truncate results
$results = $source->search('query', 10);
$results = $results->truncateToTokens(500);
```

### Performance issues
```php
// Check index stats
php artisan mindwave:index-stats

// Clear old indexes
php artisan mindwave:clear-indexes --ttl=1

// Consider VectorStore for large datasets
$vectorSource = VectorStoreSource::from(Mindwave::brain());
```

---

## Further Reading

- [PromptComposer Documentation](../README.md#prompt-composer)
- [Vector Stores / Brain](../README.md#vector-stores--brain)
- [OpenTelemetry Tracing](../README.md#tracing-observability)
- [Configuration Reference](../config/mindwave-context.php)
