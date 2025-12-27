# Contributing to Mindwave

Thank you for considering contributing to Mindwave! This document provides guidelines for contributing.

## Development Setup

### Prerequisites

- PHP 8.2+ with coverage driver (Xdebug or PCOV)
- Composer 2.x
- OpenAI API key (for integration tests)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/helgesverre/mindwave.git
   cd mindwave
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Copy environment file (if needed):
   ```bash
   cp .env.example .env
   ```

4. Run tests to verify setup:
   ```bash
   composer test
   ```

### Installing Coverage Driver

**macOS (Homebrew):**
```bash
pecl install pcov
# Or
pecl install xdebug
```

**Ubuntu/Debian:**
```bash
sudo apt-get install php-pcov
# Or
sudo apt-get install php-xdebug
```

## Code Quality Standards

### Running Quality Checks

```bash
# Run all checks
composer check

# Individual checks
composer test              # Unit/feature tests
composer test-coverage     # With coverage report
composer analyse           # PHPStan static analysis
composer format            # Auto-fix code style
composer format:check      # Check style without fixing
```

### Before Committing

All code must:
- ✅ Pass all tests (`composer test`)
- ✅ Pass PHPStan level 4+ (`composer analyse`)
- ✅ Follow Laravel Pint style (`composer format`)
- ✅ Include tests for new features
- ✅ Update documentation

**Note:** Pre-commit hooks (GrumPHP) will run automatically if installed.

## Pull Request Process

1. **Fork the repository** and create a feature branch
   ```bash
   git checkout -b feature/my-new-feature
   ```

2. **Write tests** for new functionality

3. **Update documentation** if adding features

4. **Run quality checks**:
   ```bash
   composer check
   ```

5. **Commit with conventional commit format**:
   ```
   feat: add support for Google Gemini
   fix: resolve token counting issue in Mistral
   docs: update installation instructions
   test: add tests for streaming responses
   chore: update dependencies
   ```

6. **Push and create PR** against `main` branch

7. **Respond to review feedback**

## Coding Standards

- Follow **PSR-12** coding style (enforced by Pint)
- Use **strict types** (`declare(strict_types=1)`)
- Write **PHPDoc comments** for public methods
- Prefer **named parameters** for clarity
- Use **type hints** everywhere

## Testing Guidelines

- Write tests in **Pest PHP**
- Aim for **80%+ code coverage**
- Use **descriptive test names**:
  ```php
  it('embeds question and searches vectorstore');
  it('returns I dont know message when no documents found');
  ```
- Mock external API calls (OpenAI, Mistral, etc.)
- Use factories for test data

## Architecture Patterns

Mindwave follows these patterns:

- **Driver-based**: LLM, Embeddings, Vectorstore drivers
- **Fluent interfaces**: Prompt composer, Brain, QA
- **Facades**: `Mindwave::prompt()`, `Mindwave::llm()`
- **Laravel conventions**: Service providers, config, Artisan commands

## Questions?

- Open an issue for **bugs** or **feature requests**
- Start a **discussion** for questions
- Email **helge.sverre@gmail.com** for security issues

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
