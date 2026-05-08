# 🚀 GitHub Actions Workflows

This directory contains CI/CD workflows for automated testing, linting, building, and releasing.

## 📋 Workflows

### 1. Tests (`tests.yml`)
**Trigger**: Push to `main`/`develop`, Pull Requests

**What it does**:
- ✅ Runs PHPUnit tests on PHP 8.0, 8.1, 8.2, 8.3
- ✅ Runs Jest tests on Node 18.x, 20.x
- ✅ Validates composer.json
- ✅ Reports test results

**Status Badge**:
```markdown
![Tests](https://github.com/YOUR_USERNAME/meowseo/workflows/Tests/badge.svg)
```

### 2. Lint (`lint.yml`)
**Trigger**: Push to `main`/`develop`, Pull Requests

**What it does**:
- ✅ Checks PHP syntax
- ✅ Runs ESLint on JavaScript
- ✅ Runs TypeScript type checking
- ✅ Reports linting issues

**Status Badge**:
```markdown
![Lint](https://github.com/YOUR_USERNAME/meowseo/workflows/Lint/badge.svg)
```

### 3. Build (`build.yml`)
**Trigger**: Push to `main`/`develop`, Pull Requests

**What it does**:
- ✅ Builds production assets
- ✅ Verifies build output
- ✅ Uploads build artifacts
- ✅ Checks file sizes

**Status Badge**:
```markdown
![Build](https://github.com/YOUR_USERNAME/meowseo/workflows/Build/badge.svg)
```

### 4. Release (`release.yml`)
**Trigger**: Push tags matching `v*.*.*` (e.g., `v1.0.0`)

**What it does**:
- ✅ Runs all tests
- ✅ Builds production assets
- ✅ Creates release ZIP
- ✅ Creates GitHub Release
- ✅ Attaches ZIP to release

**Usage**:
```bash
# Create and push tag
git tag v1.0.0
git push origin v1.0.0

# Workflow will automatically:
# 1. Run tests
# 2. Build release
# 3. Create GitHub Release
```

### 5. PR Checks (`pr-checks.yml`)
**Trigger**: Pull Request opened/updated

**What it does**:
- ✅ Validates PR title format
- ✅ Checks for /build/ in commits
- ✅ Warns about missing tests
- ✅ Checks file sizes
- ✅ Runs code quality checks
- ✅ Security vulnerability scan

**PR Title Format**:
```
Type: Description

Valid types:
- Feature: New feature
- Fix: Bug fix
- Docs: Documentation
- Test: Tests
- Refactor: Code refactoring
- Chore: Maintenance
- Style: Code style
- Perf: Performance

Examples:
✅ Feature: Add breadcrumb schema support
✅ Fix: Resolve cache invalidation issue
✅ Docs: Update installation instructions
❌ add new feature (wrong format)
```

## 🎯 Workflow Status

All workflows must pass before merging:
- ✅ Tests (PHP + JavaScript)
- ✅ Lint (PHP + JavaScript + TypeScript)
- ✅ Build (Assets)
- ✅ PR Checks (Validation)

## 📊 Matrix Testing

### PHP Tests
Tests run on multiple PHP versions:
- PHP 8.0
- PHP 8.1
- PHP 8.2
- PHP 8.3

### JavaScript Tests
Tests run on multiple Node versions:
- Node 18.x
- Node 20.x

## 🔒 Security

### Automated Checks
- ✅ npm audit for vulnerabilities
- ✅ Sensitive file detection
- ✅ No secrets in commits

### Protected Branches
Configure branch protection rules:
1. Go to Settings > Branches
2. Add rule for `main` branch
3. Enable:
   - ✅ Require status checks to pass
   - ✅ Require branches to be up to date
   - ✅ Require pull request reviews

## 📦 Release Process

### Automatic Release
```bash
# 1. Update version in files
# - meowseo.php
# - package.json
# - composer.json

# 2. Update CHANGELOG.md

# 3. Commit changes
git add .
git commit -m "Release v1.0.0"

# 4. Create and push tag
git tag v1.0.0
git push origin main --tags

# 5. GitHub Actions will:
# - Run all tests
# - Build release ZIP
# - Create GitHub Release
# - Attach meowseo.zip
```

### Manual Release
Use `build-release.ps1` or `build-release.sh` locally.

## 🎨 Status Badges

Add to README.md:

```markdown
![Tests](https://github.com/YOUR_USERNAME/meowseo/workflows/Tests/badge.svg)
![Lint](https://github.com/YOUR_USERNAME/meowseo/workflows/Lint/badge.svg)
![Build](https://github.com/YOUR_USERNAME/meowseo/workflows/Build/badge.svg)
![Release](https://github.com/YOUR_USERNAME/meowseo/workflows/Release/badge.svg)
```

## 🔧 Troubleshooting

### Tests Failing
```bash
# Run tests locally first
composer test
npm test

# Check specific PHP version
docker run -v $(pwd):/app php:8.2 php /app/vendor/bin/phpunit
```

### Build Failing
```bash
# Run build locally
npm run build

# Check build output
ls -la build/
```

### Lint Failing
```bash
# Run linting locally
npm run lint:js
npm run type-check

# Auto-fix issues
npm run format:js
```

## 📝 Adding New Workflows

### Template
```yaml
name: Your Workflow

on:
  push:
    branches: [ main ]

jobs:
  your-job:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Your step
        run: echo "Hello"
```

### Best Practices
1. ✅ Use specific action versions (@v4, not @latest)
2. ✅ Cache dependencies (npm, composer)
3. ✅ Fail fast on errors
4. ✅ Provide clear error messages
5. ✅ Use matrix for multi-version testing

## 🎯 Workflow Dependencies

```
PR Checks ──┐
Tests ──────┼──> Merge to main
Lint ───────┤
Build ──────┘

Tag v*.*.* ──> Release ──> GitHub Release
```

## 📚 Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Workflow Syntax](https://docs.github.com/en/actions/reference/workflow-syntax-for-github-actions)
- [Action Marketplace](https://github.com/marketplace?type=actions)

---

**All workflows are configured and ready to use!** ✅

Push to `main` or create a PR to see them in action.
