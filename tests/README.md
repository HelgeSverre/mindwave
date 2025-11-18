# Mindwave Test Suite

This directory contains the test suite for Mindwave. We use [Pest PHP](https://pestphp.com/) as our testing framework.

## Running Tests

### Run All Unit Tests

```bash
vendor/bin/pest
```

### Run Specific Test File

```bash
vendor/bin/pest tests/LLM/MaxContextTokensTest.php
```

### Run Tests with Coverage

```bash
vendor/bin/pest --coverage
```

### Run Tests with Minimum Coverage Requirement

```bash
vendor/bin/pest --coverage --min=80
```

## Test Groups

### Unit Tests (Default)

Unit tests are fast, isolated tests that don't require external dependencies:

```bash
vendor/bin/pest
```

### Integration Tests

Integration tests make real API calls and require:
- `OPENAI_API_KEY` environment variable set
- Active internet connection
- OpenAI API credits

**Run integration tests:**

```bash
vendor/bin/pest --group=integration
```

**Skip integration tests (run only unit tests):**

```bash
vendor/bin/pest --exclude-group=integration
```

## Environment Variables

### Required for Integration Tests

Create a `.env.testing` file in the project root:

```env
# OpenAI API
OPENAI_API_KEY=sk-your-api-key-here

# Optional: Tracing
MINDWAVE_TRACING_ENABLED=true
MINDWAVE_TRACING_SERVICE_NAME=mindwave-tests
```

### Optional Test Configuration

```env
# Vector Store Services (optional, skip tests if not available)
PINECONE_API_KEY=
PINECONE_ENVIRONMENT=
QDRANT_HOST=localhost
QDRANT_PORT=6333
WEAVIATE_HOST=localhost
WEAVIATE_PORT=8080

# Mistral API (optional)
MISTRAL_API_KEY=
```

## Test Structure

```
tests/
├── README.md                     # This file
├── Pest.php                      # Pest configuration
├── LLM/
│   ├── MaxContextTokensTest.php       # Tests for maxContextTokens() method
│   ├── StreamingTest.php              # Unit tests for streaming (mocked)
│   └── StreamingIntegrationTest.php   # Integration tests for streaming (real API)
├── Managers/
│   ├── LLMManagerTest.php
│   └── EmbeddingsManagerTest.php
├── PromptComposer/
│   ├── PromptComposerTest.php
│   └── Tokenizer/
│       ├── TiktokenTokenizerTest.php
│       └── ModelTokenLimitsTest.php
├── Observability/
│   └── Tracing/
│       └── TracerCoreTest.php
└── Vectorstores/
    ├── InMemoryTest.php
    ├── PineconeTest.php    # Skipped if PINECONE_API_KEY not set
    ├── QdrantTest.php      # Skipped if Qdrant not available
    └── WeaviateTest.php    # Skipped if Weaviate not available
```

## Test Categories

### 1. Unit Tests
- Fast, isolated tests
- No external dependencies
- Mocked services
- **Run:** `vendor/bin/pest`

### 2. Integration Tests
- Real API calls
- Require credentials
- Test end-to-end functionality
- **Run:** `vendor/bin/pest --group=integration`

### 3. Skipped Tests
Some tests are skipped when:
- External service not available (Pinecone, Qdrant, Weaviate)
- API key not provided
- Complex mocking not worth the effort (covered by integration tests)

## CI/CD Integration

### GitHub Actions

The CI workflow automatically:
1. Runs unit tests on PHP 8.2, 8.3, 8.4
2. Skips integration tests (no API keys in CI)
3. Skips external service tests (Pinecone, Qdrant, etc.)

**Integration tests are run locally only** with proper credentials.

### Running Tests Like CI

```bash
# Exclude integration and external service tests
vendor/bin/pest --exclude-group=integration
```

## Writing Tests

### Unit Test Example

```php
<?php

use Mindwave\Mindwave\Facades\Mindwave;

it('returns correct context window for GPT-5', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-5');

    expect($driver->maxContextTokens())->toBe(400_000);
});
```

### Integration Test Example

```php
<?php

use Mindwave\Mindwave\Facades\Mindwave;

it('can stream text from OpenAI', function () {
    $driver = Mindwave::llm('openai')
        ->model('gpt-3.5-turbo');

    $chunks = [];
    foreach ($driver->streamText('Say hello') as $chunk) {
        $chunks[] = $chunk;
    }

    expect($chunks)->not()->toBeEmpty();
})->group('integration');

beforeEach(function () {
    if (! env('OPENAI_API_KEY')) {
        test()->markTestSkipped('OPENAI_API_KEY not set');
    }
});
```

### Skipped Test Example

```php
<?php

it('complex external service integration', function () {
    // Test implementation when service is available
    expect(true)->toBeTrue();
})->skip('Requires Weaviate service running on localhost:8080');
```

## Test Naming Conventions

- Use descriptive names: `it('returns correct context window for GPT-5 model', ...)`
- Use present tense: `it('throws exception when...')` not `it('should throw exception...')`
- Be specific: Include model names, exact expectations
- Group related tests in the same file

## Debugging Tests

### Run Single Test

```bash
vendor/bin/pest --filter="returns correct context window"
```

### Verbose Output

```bash
vendor/bin/pest -vvv
```

### Stop on First Failure

```bash
vendor/bin/pest --stop-on-failure
```

### Show Test Names

```bash
vendor/bin/pest --list-tests
```

## Test Coverage Goals

- **Overall:** 80%+ coverage
- **Core Components:** 90%+ coverage
  - PromptComposer
  - LLM Drivers
  - Tokenizer
  - Tracing Core
- **Integration:** Real API coverage via integration tests

## Common Issues

### "OPENAI_API_KEY not set"
- Set the environment variable in `.env.testing`
- Or run without integration tests: `vendor/bin/pest --exclude-group=integration`

### "Pinecone/Qdrant/Weaviate service not available"
- These tests are automatically skipped when services aren't running
- Not required for CI/CD
- Optional for local development

### "Streaming tests skipped"
- Some complex mocking scenarios are intentionally skipped
- Functionality is covered by integration tests
- See `StreamingIntegrationTest.php` for real API tests

## Contributing

When adding new features:
1. **Write unit tests first** - Fast, isolated, no external dependencies
2. **Add integration tests if needed** - For real API verification
3. **Mark with `@group integration`** - For tests that require API keys
4. **Skip when appropriate** - Use `skip()` for unavailable services
5. **Document in this README** - Update test structure if adding new categories

## Resources

- [Pest Documentation](https://pestphp.com/docs)
- [Pest Expectations](https://pestphp.com/docs/expectations)
- [Laravel Testing](https://laravel.com/docs/testing)
- [OpenAI API Documentation](https://platform.openai.com/docs/api-reference)
