# MeowSEO Tests

This directory contains comprehensive unit and integration tests for the MeowSEO plugin.

## Setup

1. Install dependencies:
   ```bash
   composer install
   ```

2. Run tests:
   ```bash
   ./vendor/bin/phpunit
   ```

   Or use the provided script:
   ```bash
   bash run-tests.sh
   ```

## Test Structure

### Unit Tests

#### Core Classes
- `tests/test-plugin.php` - Tests for Plugin singleton class
- `tests/test-module-manager.php` - Tests for Module_Manager class
- `tests/test-options.php` - Tests for Options class
- `tests/test-installer.php` - Tests for Installer class

#### Helper Classes
- `tests/test-cache-helper.php` - Tests for Cache helper class
- `tests/test-db-helper.php` - Tests for DB helper class
- `tests/test-schema-builder.php` - Tests for Schema_Builder helper class

#### Module Tests
- `tests/modules/meta/SEOAnalyzerTest.php` - Tests for SEO analysis functionality
- `tests/modules/meta/ReadabilityTest.php` - Tests for readability analysis functionality

#### API Tests
- `tests/test-rest-api.php` - Tests for REST API endpoints
- `tests/test-wpgraphql.php` - Tests for WPGraphQL integration

### Integration Tests

- `tests/integration/test-redirect-integration.php` - Redirect module with database seeding
- `tests/integration/test-sitemap-integration.php` - Sitemap generation with multiple post types
- `tests/integration/test-404-integration.php` - 404 flush with concurrent hit simulation
- `tests/integration/test-gsc-integration.php` - GSC queue processing across multiple cron cycles
- `tests/integration/test-wpgraphql-integration.php` - WPGraphQL field registration and queries

## Test Coverage

### Core Functionality
- **Plugin Singleton**: Instance creation, Options access, Module_Manager initialization
- **Module Manager**: Conditional module loading, enabled/disabled module handling
- **Options**: Settings management, credential encryption/decryption, typed getters
- **Installer**: Database schema creation, table indexes, unique constraints

### Helper Classes
- **Cache**: Key prefixing, atomic operations, TTL handling, data type support
- **DB**: Prepared statements, query structure, bulk operations, pagination
- **Schema Builder**: WebSite, Organization, WebPage, Article, BreadcrumbList, FAQPage, Product schemas

### SEO Analysis
- Focus keyword presence in title, description, first paragraph, headings, and slug
- Meta description length validation (50-160 characters)
- Title length validation (30-60 characters)
- Score calculation and color indicators
- Case-insensitive keyword matching

### Readability Analysis
- Average sentence length (≤ 20 words)
- Paragraph length (≤ 150 words)
- Transition word usage (≥ 30% of sentences)
- Passive voice detection (≤ 10% of sentences)
- Score calculation and color indicators
- HTML and shortcode stripping

### Integration Tests
- **Redirects**: Exact match, regex fallback, hit tracking, redirect types
- **Sitemap**: File path caching, lock pattern, noindex exclusion, multi-post-type support
- **404 Monitor**: Buffering, hit count preservation, concurrent hits, bucket keys
- **GSC**: Queue processing, exponential backoff, rate limiting, multi-cycle processing
- **WPGraphQL**: Field registration, query structure, SEO data structure

### Security
- All database queries use prepared statements (Requirement 15.1)
- Credential encryption/decryption using AES-256-CBC (Requirement 15.6)
- Nonce verification on REST endpoints (Requirement 15.2)
- Capability checks on admin operations (Requirement 15.3)

### Performance
- Cache key prefixing (Requirement 14.2)
- Object Cache with transient fallback (Requirement 14.3)
- Sitemap file path caching (Requirement 6.2)
- 404 buffering to prevent synchronous DB writes (Requirement 8.1)

## Requirements

- PHP 8.0 or higher
- Composer
- PHPUnit 9.5 or higher

## Running Specific Test Suites

Run only unit tests:
```bash
./vendor/bin/phpunit --testsuite unit
```

Run only integration tests:
```bash
./vendor/bin/phpunit --testsuite integration
```

Run tests for a specific class:
```bash
./vendor/bin/phpunit tests/test-plugin.php
```

Run tests with coverage report:
```bash
./vendor/bin/phpunit --coverage-html coverage/
```
