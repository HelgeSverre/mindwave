# Developer Experience (DevEx) Audit - Mindwave PHP Library

**Package:** Mindwave Laravel Package
**Audit Date:** 2025-12-27
**Branch:** next-version
**Status:** Pre-v1.0 Production AI Utilities
**Current Grade:** B+ → Target: A+

---

## Executive Summary

The Mindwave package has a **solid DevEx foundation** with modern tooling, comprehensive tests (101 test files, 940+ passing tests), and active CI/CD. However, several **quick wins** and **critical gaps** remain that will significantly improve contributor experience and project maintainability.

### Critical Issues Found

1. **PHPStan configuration error** - `checkMissingIterableValueType` is not a valid parameter (FIXED)
2. **151 PHPStan errors at level 4** - Type safety issues need addressing
3. **No CONTRIBUTING.md** - Blocks community contributions
4. **No SECURITY.md** - Missing security disclosure process
5. **No Dependabot** - Manual dependency management
6. **No pre-commit hooks** - Code quality checks happen in CI, not locally
7. **No coverage driver in local env** - Can't run coverage locally

---

## 1. CI/CD Pipeline Assessment

### ✅ What's Working Well

| Workflow | Status | Details |
|----------|--------|---------|
| **run-tests.yml** | Excellent | Multi-version (PHP 8.2-8.4), Laravel 11.x, 80% coverage minimum |
| **phpstan.yml** | Good | Level 4, runs on PR/push to main/next-version |
| **fix-php-code-style-issues.yml** | Good | Auto-commits Pint fixes on push |

**Test Stats:**
- 101 test files
- 940+ passing tests
- 19 skipped tests (likely external API tests)
- 2017 assertions
- Average run time: ~47 seconds

### ❌ Missing CI/CD Components

#### 1. Dependency Security Scanning (Priority: CRITICAL)

**Why:** Automatically detect vulnerable dependencies before they reach production.

**Solution:** Create `.github/dependabot.yml`

```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
    open-pull-requests-limit: 5
    reviewers:
      - "helgesverre"
    labels:
      - "dependencies"
      - "automated"
    versioning-strategy: increase
```

**Effort:** 5 minutes | **Impact:** High

---

#### 2. Code Coverage Reporting (Priority: HIGH)

**Why:** Track coverage trends over time, prevent coverage regression.

**Current State:** Coverage runs in CI but results are not published anywhere.

**Solution:** Add Codecov integration to `run-tests.yml`

```yaml
- name: Execute tests with coverage
  if: matrix.php == '8.3' && matrix.os == 'ubuntu-latest'
  run: vendor/bin/pest --coverage --min=80

- name: Upload coverage to Codecov
  if: matrix.php == '8.3' && matrix.os == 'ubuntu-latest'
  uses: codecov/codecov-action@v4
  with:
    files: ./build/logs/clover.xml
    fail_ci_if_error: false
    token: ${{ secrets.CODECOV_TOKEN }}
```

**Effort:** 15 minutes (+ Codecov account setup) | **Impact:** Medium

---

#### 3. Security Scanning with SAST (Priority: MEDIUM)

**Why:** Detect security vulnerabilities in code before they ship.

**Solution:** Create `.github/workflows/security.yml`

```yaml
name: Security Scan

on:
  push:
    branches: [main, next-version]
  pull_request:
    branches: [main, next-version]
  schedule:
    - cron: '0 0 * * 1' # Weekly on Monday

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Run security checker
        uses: symfonycorp/security-checker-action@v5
```

**Effort:** 10 minutes | **Impact:** Medium

---

## 2. Code Quality Tools

### PHPStan - CRITICAL FIXES NEEDED

#### Issue #1: Invalid Configuration Parameter (FIXED ✅)

**Problem:** `checkMissingIterableValueType: false` is not a valid PHPStan parameter.

**Status:** FIXED - Removed from `phpstan.neon.dist`

#### Issue #2: 151 Type Errors at Level 4

**Problem:** PHPStan currently has 151 errors, primarily:
- Undefined methods on `Probots\Pinecone\Client::index()`
- Type safety violations

**Recommended Action:**
```bash
# Generate baseline to track progress
vendor/bin/phpstan analyse --generate-baseline

# Then incrementally fix errors
vendor/bin/phpstan analyse --no-baseline
```

#### Issue #3: Low Analysis Level

**Current:** Level 4
**Recommended:** Level 6-8
**Rationale:** Production AI library needs stricter type safety

**Upgrade Path:**
```bash
# Test each level incrementally
vendor/bin/phpstan analyse --level=5
vendor/bin/phpstan analyse --level=6
# etc.

# Update phpstan.neon.dist when passing
```

#### Recommended PHPStan Configuration

```neon
includes:
    - phpstan-baseline.neon

parameters:
    level: 6  # Increase from 4
    paths:
        - src
        - config
        - database
        - tests  # Add tests for better coverage
    tmpDir: build/phpstan
    checkOctaneCompatibility: true
    checkModelProperties: true

    # Enable parallel processing for speed
    parallel:
        maximumNumberOfProcesses: 4

    # Stricter checks
    treatPhpDocTypesAsCertain: false

    # Ignore vendor issues
    excludePaths:
        - vendor
```

**Effort:** 4-8 hours to fix errors | **Impact:** High

---

### Laravel Pint - Code Style

#### ✅ Current State: Good

- Pint 1.25.1 (latest)
- Laravel preset
- Auto-fix in CI
- Custom configuration in `pint.json`

#### Missing: Check-only Script

**Problem:** `composer format` auto-fixes. No way to check without modifying files.

**Solution:** Add to `composer.json`:

```json
{
  "scripts": {
    "format": "vendor/bin/pint",
    "format:check": "vendor/bin/pint --test",
    "format:dirty": "vendor/bin/pint --dirty"
  }
}
```

**Effort:** 2 minutes | **Impact:** Low

---

## 3. Pre-commit Hooks - MISSING (Priority: HIGH)

### Current State: ❌ None

**Problem:** Developers can commit broken code. CI catches it later, wasting time.

### Recommended: GrumPHP

**Why GrumPHP over Husky:**
- PHP-native (no Node.js dependency)
- Composer-based installation
- Laravel ecosystem standard
- Integrates with existing tools (PHPStan, Pint, Pest)

**Installation:**
```bash
composer require --dev phpro/grumphp
```

**Configuration:** Create `grumphp.yml`

```yaml
grumphp:
  stop_on_failure: true

  tasks:
    # Fast checks (< 1 second)
    phplint:
      exclude: ['vendor']
      triggered_by: ['php']

    composer:
      file: ./composer.json
      no_check_all: true
      no_check_lock: false
      no_check_publish: false

    # Code style (fast)
    exec:
      name: 'pint-check'
      command: 'vendor/bin/pint'
      args: ['--test', '--dirty']
      triggered_by: ['php']

    # Static analysis (slower, but worth it)
    phpstan:
      autoload_file: ~
      configuration: phpstan.neon.dist
      level: 4
      triggered_by: ['php']
      memory_limit: "1G"

  # Hooks configuration
  hooks:
    pre-commit:
      tasks:
        - phplint
        - composer
        - exec
        - phpstan

    pre-push:
      tasks:
        - phpstan
```

**Optional: Faster variant (skip PHPStan on commit, run on push):**

```yaml
grumphp:
  tasks:
    phplint: ~
    exec:
      name: 'pint-check'
      command: 'vendor/bin/pint --test --dirty'

  hooks:
    pre-commit:
      tasks: [phplint, exec]
    pre-push:
      tasks: [phpstan]
```

**Effort:** 30 minutes | **Impact:** High

---

## 4. Documentation Gaps

### Missing Critical Files

| File | Status | Priority | Purpose |
|------|--------|----------|---------|
| `CONTRIBUTING.md` | ❌ Missing | **CRITICAL** | Onboarding contributors |
| `SECURITY.md` | ❌ Missing | **CRITICAL** | Security disclosure process |
| `.github/ISSUE_TEMPLATE/bug_report.yml` | ❌ Missing | High | Better bug reports |
| `.github/ISSUE_TEMPLATE/feature_request.yml` | ❌ Missing | High | Feature tracking |
| `.github/PULL_REQUEST_TEMPLATE.md` | ❌ Missing | Medium | PR quality |
| `UPGRADING.md` | ❌ Missing | Medium | Migration guides (v1→v2) |

### Quick-Win Templates

These files are ready to use - see **Appendix A** at the end of this document for complete templates.

**Effort:** 30 minutes total | **Impact:** High

---

## 5. Development Setup Issues

### Issue #1: No Coverage Driver in Local Dev

**Problem:** Running `vendor/bin/pest --coverage` fails:
```
ERROR No code coverage driver is available.
```

**Cause:** Missing Xdebug or PCOV extension in local PHP.

**Solution:** Add to README.md under "Development Setup":

```markdown
## Development Setup

### Prerequisites

- PHP 8.2+ with coverage driver (Xdebug or PCOV)
- Composer 2.x

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

### Running Tests

```bash
composer install
composer test              # Run tests
composer test-coverage     # Run with coverage (requires driver)
composer analyse           # Static analysis
composer format            # Auto-fix code style
```
```

**Effort:** 10 minutes | **Impact:** Medium

---

### Issue #2: Incomplete Composer Scripts

**Current scripts:**
```json
{
  "analyse": "vendor/bin/phpstan analyse",
  "test": "vendor/bin/pest",
  "test-coverage": "vendor/bin/pest --coverage",
  "format": "vendor/bin/pint"
}
```

**Recommended additions:**

```json
{
  "scripts": {
    "post-autoload-dump": "@php ./vendor/bin/testbench package:discover --ansi",

    // Testing
    "test": "vendor/bin/pest",
    "test:parallel": "vendor/bin/pest --parallel",
    "test:coverage": "vendor/bin/pest --coverage --min=80",
    "test:coverage-html": "vendor/bin/pest --coverage-html build/coverage",

    // Static Analysis
    "analyse": "vendor/bin/phpstan analyse",
    "analyse:baseline": "vendor/bin/phpstan analyse --generate-baseline",
    "analyse:level": "vendor/bin/phpstan analyse --level",

    // Code Style
    "format": "vendor/bin/pint",
    "format:check": "vendor/bin/pint --test",
    "format:dirty": "vendor/bin/pint --dirty",

    // All Checks (for CI and pre-commit)
    "check": [
      "@format:check",
      "@analyse",
      "@test"
    ],

    // CI-specific
    "ci": [
      "@check",
      "@test:coverage"
    ]
  }
}
```

**Effort:** 5 minutes | **Impact:** Medium

---

## 6. Priority Action Plan

### 🔥 Critical (Do First - 2 hours)

| # | Action | Effort | Impact | File |
|---|--------|--------|--------|------|
| 1 | Create `CONTRIBUTING.md` | 15 min | Critical | `/CONTRIBUTING.md` |
| 2 | Create `SECURITY.md` | 10 min | Critical | `/SECURITY.md` |
| 3 | Add Dependabot | 5 min | Critical | `/.github/dependabot.yml` |
| 4 | Fix PHPStan baseline | 30 min | Critical | `phpstan-baseline.neon` |
| 5 | Update composer scripts | 10 min | High | `/composer.json` |
| 6 | Install GrumPHP | 30 min | High | `grumphp.yml` |
| 7 | Add coverage setup docs | 10 min | High | `README.md` |

**Total:** ~2 hours

---

### 🎯 High Priority (Next - 4 hours)

| # | Action | Effort | Impact |
|---|--------|--------|--------|
| 8 | Add issue templates | 20 min | High |
| 9 | Add PR template | 10 min | Medium |
| 10 | Add Codecov integration | 15 min | Medium |
| 11 | Add security workflow | 15 min | Medium |
| 12 | Increase PHPStan to level 6 | 3-4 hours | High |

**Total:** ~4-5 hours

---

### 📊 Medium Priority (Future - 8 hours)

- Create `UPGRADING.md` for v1→v2 migration
- Enable parallel tests in CI
- Add mutation testing (pest-plugin-mutate)
- Set up benchmark tracking
- Auto-generate API documentation
- Add performance regression tests

---

## 7. Immediate Quick Wins (< 30 min)

These can be done **right now** with copy-paste:

### 1. Fix PHPStan Baseline (5 min)

```bash
cd /Users/helge/code/mindwave
vendor/bin/phpstan analyse --generate-baseline
git add phpstan-baseline.neon
git commit -m "chore: generate PHPStan baseline for existing errors"
```

### 2. Add Dependabot (5 min)

Create `.github/dependabot.yml` (see Appendix B)

### 3. Add Composer Scripts (5 min)

Update `composer.json` with scripts above.

### 4. Create CONTRIBUTING.md (10 min)

Use template in Appendix C.

### 5. Create SECURITY.md (5 min)

Use template in Appendix D.

---

## 8. Testing Gaps Analysis

### Current State

✅ **Strengths:**
- 940+ tests across 101 files
- Good coverage of core features
- Architecture tests enabled
- Fast execution (~47s)

⚠️ **Gaps:**
- No mutation testing (test quality validation)
- No integration tests for external APIs (19 skipped)
- No performance benchmarks
- Coverage driver not available locally

### Recommendations

1. **Add mutation testing:**
   ```bash
   composer require --dev pestphp/pest-plugin-mutate
   vendor/bin/pest --mutate
   ```

2. **Organize test suites:**
   ```xml
   <!-- phpunit.xml.dist -->
   <testsuites>
     <testsuite name="Unit">
       <directory>tests/Unit</directory>
     </testsuite>
     <testsuite name="Feature">
       <directory>tests/Feature</directory>
     </testsuite>
     <testsuite name="Integration">
       <directory>tests/Integration</directory>
     </testsuite>
   </testsuites>
   ```

3. **Enable parallel testing in CI:**
   ```yaml
   - name: Execute tests
     run: vendor/bin/pest --parallel --ci
   ```

---

## 9. Repository Hygiene

### ✅ Good

- `.editorconfig` - Proper configuration
- `.gitattributes` - Export-ignore set correctly
- `.gitignore` - Comprehensive
- `phpunit.xml.dist` - Well configured
- `CHANGELOG.md` - Present (but empty - needs updates)

### ⚠️ Needs Attention

1. **CHANGELOG.md is empty** - Should track all changes
2. **No release automation** - Manual versioning
3. **No GitHub release workflow** - Could automate

---

## 10. Tool Versions (All Current ✅)

| Tool | Installed | Latest | Status |
|------|-----------|--------|--------|
| PHP | 8.2-8.4 | 8.4 | ✅ |
| Laravel | 11.x | 11.x | ✅ |
| Pest | 3.8.4 | 3.8.x | ✅ |
| PHPStan | 2.1.32+ | 2.1.x | ✅ |
| Larastan | 3.8.0 | 3.8.x | ✅ |
| Pint | 1.25.1 | 1.25.x | ✅ |

---

## 11. Final Assessment

### Scoring Breakdown

| Category | Current | Target | Gap |
|----------|---------|--------|-----|
| CI/CD Pipeline | 8/10 | 10/10 | Missing Dependabot, Codecov |
| Static Analysis | 6/10 | 9/10 | 151 errors, level 4→6 needed |
| Code Style | 9/10 | 10/10 | Missing check script |
| Pre-commit Hooks | 0/10 | 10/10 | Not configured |
| Documentation | 7/10 | 10/10 | Missing CONTRIBUTING, SECURITY, templates |
| Testing | 9/10 | 10/10 | Missing mutation tests, parallel CI |
| Developer Setup | 7/10 | 10/10 | Coverage driver docs needed |

**Overall Grade: B+ (78/70)**
**Target: A+ (95/70)**

### Path to A+

1. ✅ Fix PHPStan config error (DONE)
2. 🔥 Complete all Critical items (2 hours)
3. 🎯 Complete High Priority items (4 hours)
4. 📊 Selectively add Medium items

**Total Effort: 6-8 hours to reach A+ grade**

---

## Appendix A: Ready-to-Use File Templates

### CONTRIBUTING.md

```markdown
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

## Code Quality Standards

### Running Quality Checks

```bash
# Run all checks
composer check

# Individual checks
composer test              # Unit/feature tests
composer test:coverage     # With coverage report
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
```

---

### SECURITY.md

```markdown
# Security Policy

## Supported Versions

We provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 2.x     | :white_check_mark: |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

**Please DO NOT create public GitHub issues for security vulnerabilities.**

### How to Report

Email security reports to: **helge.sverre@gmail.com**

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Initial assessment**: Within 5 business days
- **Fix timeline**: Depends on severity
  - Critical: 1-3 days
  - High: 1-2 weeks
  - Medium: 2-4 weeks
  - Low: Next release

### Disclosure Policy

- We will coordinate disclosure with you
- We prefer coordinated disclosure after fix is released
- You will be credited in release notes (if desired)

### Security Best Practices

When using Mindwave:

1. **API Keys**: Never commit API keys to version control
2. **PII Protection**: Enable `capture_messages: false` in production
3. **Rate Limiting**: Implement rate limiting for public-facing LLM endpoints
4. **Input Validation**: Sanitize user input before sending to LLMs
5. **Cost Controls**: Set budget limits in config

## Known Security Considerations

- **LLM Prompt Injection**: User input should be validated
- **Token Costs**: Implement rate limiting to prevent abuse
- **API Key Exposure**: Use environment variables, never hardcode
- **Tracing PII**: Disable message capture in production

## Security Updates

Subscribe to releases to be notified of security updates:
https://github.com/helgesverre/mindwave/releases
```

---

### .github/dependabot.yml

```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
      time: "09:00"
      timezone: "Europe/Oslo"
    open-pull-requests-limit: 5
    reviewers:
      - "helgesverre"
    assignees:
      - "helgesverre"
    labels:
      - "dependencies"
      - "automated"
    versioning-strategy: increase

    # Auto-merge patch and minor updates
    allow:
      - dependency-type: "all"

    # Group Laravel updates together
    groups:
      laravel:
        patterns:
          - "illuminate/*"
          - "laravel/*"

      testing:
        patterns:
          - "pestphp/*"
          - "phpunit/*"
          - "mockery/*"

      code-quality:
        patterns:
          - "phpstan/*"
          - "laravel/pint"

      opentelemetry:
        patterns:
          - "open-telemetry/*"
```

---

### .github/ISSUE_TEMPLATE/bug_report.yml

```yaml
name: Bug Report
description: Report a bug or issue with Mindwave
title: "[Bug]: "
labels: ["bug", "triage"]
assignees:
  - helgesverre

body:
  - type: markdown
    attributes:
      value: |
        Thanks for taking the time to report this bug!

  - type: textarea
    id: description
    attributes:
      label: Bug Description
      description: A clear description of the bug
      placeholder: When I call Mindwave::prompt()->fit(), it throws...
    validations:
      required: true

  - type: textarea
    id: reproduction
    attributes:
      label: Steps to Reproduce
      description: Minimal code to reproduce the issue
      placeholder: |
        ```php
        $response = Mindwave::prompt()
            ->section('user', $longText)
            ->fit()
            ->run();
        ```
      render: php
    validations:
      required: true

  - type: textarea
    id: expected
    attributes:
      label: Expected Behavior
      description: What should happen?
    validations:
      required: true

  - type: textarea
    id: actual
    attributes:
      label: Actual Behavior
      description: What actually happens? Include error messages.
      render: shell
    validations:
      required: true

  - type: input
    id: php-version
    attributes:
      label: PHP Version
      placeholder: "8.3"
    validations:
      required: true

  - type: input
    id: laravel-version
    attributes:
      label: Laravel Version
      placeholder: "11.x"
    validations:
      required: true

  - type: input
    id: mindwave-version
    attributes:
      label: Mindwave Version
      placeholder: "2.0"
    validations:
      required: true

  - type: dropdown
    id: llm-provider
    attributes:
      label: LLM Provider
      options:
        - OpenAI
        - Mistral
        - Anthropic
        - Other
        - Not applicable

  - type: textarea
    id: additional
    attributes:
      label: Additional Context
      description: Any other relevant information
```

---

### .github/ISSUE_TEMPLATE/feature_request.yml

```yaml
name: Feature Request
description: Suggest a new feature for Mindwave
title: "[Feature]: "
labels: ["enhancement", "triage"]
assignees:
  - helgesverre

body:
  - type: markdown
    attributes:
      value: |
        Thanks for suggesting a feature!

  - type: textarea
    id: problem
    attributes:
      label: Problem Statement
      description: What problem does this feature solve?
      placeholder: "I'm frustrated when..."
    validations:
      required: true

  - type: textarea
    id: solution
    attributes:
      label: Proposed Solution
      description: How would you like this to work?
      placeholder: |
        ```php
        Mindwave::newFeature()
            ->doSomething()
            ->run();
        ```
    validations:
      required: true

  - type: textarea
    id: alternatives
    attributes:
      label: Alternatives Considered
      description: What other solutions did you consider?

  - type: dropdown
    id: category
    attributes:
      label: Feature Category
      options:
        - LLM Integration
        - Prompt Management
        - Streaming
        - Tracing/Observability
        - Context Discovery
        - Vector Stores
        - Testing/DevEx
        - Documentation
        - Other
    validations:
      required: true

  - type: checkboxes
    id: contribution
    attributes:
      label: Contribution
      options:
        - label: I'd be willing to implement this feature
```

---

### .github/PULL_REQUEST_TEMPLATE.md

```markdown
## Description

<!-- Describe your changes in detail -->

## Motivation and Context

<!-- Why is this change required? What problem does it solve? -->
<!-- If it fixes an open issue, please link to the issue here -->

Fixes #

## Type of Change

- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update
- [ ] Code refactoring
- [ ] Performance improvement
- [ ] Test improvement

## Checklist

- [ ] My code follows the project's coding standards (Pint)
- [ ] I have run `composer check` and all checks pass
- [ ] I have run `composer test` and all tests pass
- [ ] I have run `composer analyse` and PHPStan passes
- [ ] I have added tests that prove my fix/feature works
- [ ] I have updated the documentation (if applicable)
- [ ] I have added/updated PHPDoc comments
- [ ] My changes generate no new warnings or errors
- [ ] I have checked for breaking changes
- [ ] I have updated CHANGELOG.md (if applicable)

## Testing

<!-- Describe the tests you ran and/or added -->

```bash
composer test
# Output:
```

## Screenshots (if applicable)

## Additional Notes
```

---

## Appendix B: GrumPHP Configuration

### grumphp.yml (Recommended Configuration)

```yaml
grumphp:
  stop_on_failure: true
  ignore_unstaged_changes: false

  fixer:
    enabled: true
    fix_by_default: false

  tasks:
    # PHP Syntax Check (very fast)
    phplint:
      exclude: ['vendor', 'build', 'storage']
      jobs: ~
      short_open_tag: false
      ignore_patterns: []
      triggered_by: ['php']

    # Composer Validation
    composer:
      file: ./composer.json
      no_check_all: true
      no_check_lock: false
      no_check_publish: false
      no_local_repository: false
      with_dependencies: false
      strict: false

    # Code Style (Laravel Pint)
    exec:
      name: 'Laravel Pint (check)'
      command: vendor/bin/pint
      args: ['--test', '--dirty']
      triggered_by: [php]
      run_always: false

    # Static Analysis (PHPStan)
    phpstan:
      autoload_file: ~
      configuration: phpstan.neon.dist
      level: null  # Use config file
      force_patterns: []
      ignore_patterns: []
      triggered_by: ['php']
      memory_limit: "1G"
      use_grumphp_paths: false

  # Environment-specific settings
  testsuites:
    git_pre_commit:
      tasks:
        - phplint
        - composer
        - exec  # Pint check

    git_pre_push:
      tasks:
        - phplint
        - composer
        - exec
        - phpstan

  # Extensions
  extensions: []
```

### Lighter Alternative (Fast Commits)

```yaml
grumphp:
  stop_on_failure: true

  tasks:
    phplint: ~

    exec:
      name: 'pint-check'
      command: vendor/bin/pint
      args: ['--test', '--dirty']

  testsuites:
    git_pre_commit:
      tasks: [phplint, exec]

    git_pre_push:
      tasks: [phpstan]
```

---

## Appendix C: Updated Composer Scripts

```json
{
  "scripts": {
    "post-autoload-dump": "@php ./vendor/bin/testbench package:discover --ansi",

    "test": "vendor/bin/pest",
    "test:parallel": "vendor/bin/pest --parallel",
    "test:coverage": "vendor/bin/pest --coverage --min=80",
    "test:coverage-html": "vendor/bin/pest --coverage-html build/coverage",
    "test:unit": "vendor/bin/pest --testsuite=Unit",
    "test:feature": "vendor/bin/pest --testsuite=Feature",

    "analyse": "vendor/bin/phpstan analyse",
    "analyse:baseline": "vendor/bin/phpstan analyse --generate-baseline",
    "analyse:clear": "vendor/bin/phpstan clear-result-cache",

    "format": "vendor/bin/pint",
    "format:check": "vendor/bin/pint --test",
    "format:dirty": "vendor/bin/pint --dirty",

    "check": [
      "@format:check",
      "@analyse",
      "@test"
    ],

    "ci": [
      "@check",
      "@test:coverage"
    ],

    "fix": [
      "@format",
      "@test"
    ]
  }
}
```

---

## Appendix D: Implementation Checklist

Use this to track progress:

### Critical (Do This Week)
- [ ] Create `CONTRIBUTING.md`
- [ ] Create `SECURITY.md`
- [ ] Create `.github/dependabot.yml`
- [ ] Run `vendor/bin/phpstan analyse --generate-baseline`
- [ ] Update `composer.json` with new scripts
- [ ] Install GrumPHP: `composer require --dev phpro/grumphp`
- [ ] Create `grumphp.yml`
- [ ] Add coverage driver docs to README.md
- [ ] Fix PHPStan config (DONE ✅)

### High Priority (Next Week)
- [ ] Create `.github/ISSUE_TEMPLATE/bug_report.yml`
- [ ] Create `.github/ISSUE_TEMPLATE/feature_request.yml`
- [ ] Create `.github/PULL_REQUEST_TEMPLATE.md`
- [ ] Add Codecov to `run-tests.yml`
- [ ] Create `.github/workflows/security.yml`
- [ ] Start fixing PHPStan errors (target: level 5)

### Medium Priority (This Month)
- [ ] Increase PHPStan to level 6
- [ ] Add mutation testing
- [ ] Enable parallel tests in CI
- [ ] Create `UPGRADING.md`
- [ ] Update `CHANGELOG.md` with v2.0 changes
- [ ] Set up auto-release workflow

---

**End of Audit Report**

Generated: 2025-12-27
Next Review: After critical items completed
