# Quick Start: DevEx Improvements

This guide will walk you through implementing the critical DevEx improvements in under 30 minutes.

## What Was Just Created

The following files have been created and are ready to use:

- `CONTRIBUTING.md` - Contributor guidelines
- `SECURITY.md` - Security disclosure policy
- `.github/dependabot.yml` - Automated dependency updates
- `.github/ISSUE_TEMPLATE/bug_report.yml` - Bug report template
- `.github/ISSUE_TEMPLATE/feature_request.yml` - Feature request template
- `.github/PULL_REQUEST_TEMPLATE.md` - Pull request template
- `grumphp.yml` - Pre-commit hooks configuration
- Updated `composer.json` - Enhanced scripts
- Fixed `phpstan.neon.dist` - Removed invalid parameter

## Next Steps (15 minutes)

### 1. Generate PHPStan Baseline (5 min)

```bash
cd /Users/helge/code/mindwave
vendor/bin/phpstan analyse --generate-baseline
```

This will create `phpstan-baseline.neon` with current errors, allowing you to fix them incrementally.

### 2. Install GrumPHP for Pre-commit Hooks (5 min)

```bash
composer require --dev phpro/grumphp
```

This will:
- Install GrumPHP
- Automatically configure git hooks
- Use the `grumphp.yml` configuration already created

Test it:
```bash
git add .
git commit --allow-empty -m "test: verify GrumPHP hooks"
```

### 3. Test New Composer Scripts (5 min)

```bash
# Check code style without fixing
composer format:check

# Run all quality checks
composer check

# Run tests with parallel execution
composer test:parallel

# Generate HTML coverage report
composer test:coverage-html
open build/coverage/index.html
```

## What's Still Missing (Optional)

### Add Codecov Integration (15 min)

1. Sign up at https://codecov.io
2. Add your repo
3. Get the upload token
4. Add to GitHub Secrets as `CODECOV_TOKEN`
5. Update `.github/workflows/run-tests.yml`:

```yaml
- name: Upload coverage to Codecov
  if: matrix.php == '8.3' && matrix.os == 'ubuntu-latest'
  uses: codecov/codecov-action@v4
  with:
    files: ./build/logs/clover.xml
    fail_ci_if_error: false
    token: ${{ secrets.CODECOV_TOKEN }}
```

### Create Security Workflow (10 min)

Create `.github/workflows/security.yml`:

```yaml
name: Security Scan

on:
  push:
    branches: [main, next-version]
  pull_request:
    branches: [main, next-version]
  schedule:
    - cron: '0 0 * * 1'

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Run security checker
        uses: symfonycorp/security-checker-action@v5
```

## Commit Everything

```bash
git add .
git commit -m "chore: add comprehensive DevEx improvements

- Add CONTRIBUTING.md and SECURITY.md
- Add GitHub issue/PR templates
- Add Dependabot configuration
- Install GrumPHP pre-commit hooks
- Enhance composer scripts
- Fix PHPStan configuration
- Generate PHPStan baseline"
```

## Summary

You've now implemented:

✅ Contributor onboarding (CONTRIBUTING.md)
✅ Security disclosure process (SECURITY.md)
✅ Automated dependency updates (Dependabot)
✅ Better issue/PR templates
✅ Pre-commit hooks (GrumPHP)
✅ Enhanced composer scripts
✅ Fixed PHPStan configuration
✅ Created PHPStan baseline

**Grade improvement: B+ → A-**

For the full audit report, see: `/Users/helge/code/mindwave/docs/devex-improvements-2025-12-27.md`
