# MeowSEO Testing Guide

Comprehensive testing documentation for MeowSEO plugin.

## Table of Contents

- [Overview](#overview)
- [Test Structure](#test-structure)
- [PHP Testing (PHPUnit)](#php-testing-phpunit)
- [JavaScript Testing (Jest)](#javascript-testing-jest)
- [Running Tests](#running-tests)
- [Writing Tests](#writing-tests)
- [Coverage Reports](#coverage-reports)
- [CI/CD Integration](#cicd-integration)
- [Best Practices](#best-practices)

## Overview

MeowSEO uses a comprehensive testing strategy to ensure code quality and prevent regressions:

- **PHPUnit** for PHP unit and integration tests
- **Jest** for JavaScript/React component tests
- **Brain Monkey** for WordPress function mocking
- **Testing Library** for React component testing

### Test Coverage Goals

- **Minimum Coverage**: 50% for all code
- **Critical Paths**: 80%+ coverage for core functionality
- **New Features**: Must include tests before merge

## Test Structure

```
tests/
├── bootstrap.php              # PHPUnit bootstrap
├── TestCase.php              # Base test class
├── Unit/                     # PHP unit tests
│   ├── OptionsTest.php
│   ├── PluginTest.php
│   └── ModuleManagerTest.php
├── Integration/              # PHP integration tests
│   └── (to be added)
└── js/                       # JavaScript tests
    ├── setup.js              # Jest setup
    ├── __mocks__/            # Mock files
    ├── components/           # Component tests
    └── store/                # Store tests
```

## PHP Testing (PHPUnit)

### Setup

PHPUnit is configured via `phpunit.xml` in the project root.

**Dependencies:**
- `phpunit/phpunit`: ^9.5
- `brain/monkey`: ^2.6 (WordPress function mocking)
- `mockery/mockery`: ^1.5 (Object mocking)
- `yoast/phpunit-polyfills`: ^1.0 (PHP 8+ compatibility)

### Running PHP Tests

```bash
# Run all PHP tests
composer test

# Or directly with PHPUnit
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/Unit/OptionsTest.php

# Run with coverage report
./vendor/bin/phpunit --coverage-html coverage/
```

### Writing PHP Tests

All test classes should extend `MeowSEO\Tests\TestCase`:

```php
<?php
namespace MeowSEO\Tests\Unit;

use MeowSEO\Options;
use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;

class MyTest extends TestCase {
    
    public function test_something(): void {
        // Mock WordPress functions
        Functions\when('get_option')->justReturn(array());
        
        // Your test code
        $options = new Options();
        $this->assertInstanceOf('MeowSEO\Options', $options);
    }
}
```

### Mocking WordPress Functions

Use Brain Monkey for WordPress function mocking:

```php
// Simple return value
Functions\when('get_option')->justReturn('value');

// Return different values based on arguments
Functions\when('get_option')->alias(function($key, $default) {
    return $default;
});

// Expect function to be called
Functions\expect('update_option')
    ->once()
    ->with('key', 'value')
    ->andReturn(true);
```

## JavaScript Testing (Jest)

### Setup

Jest is configured via `jest.config.js` using `@wordpress/scripts`.

**Dependencies:**
- `@wordpress/scripts`: ^27.9.0
- `@testing-library/react`: ^14.0.0
- `@testing-library/jest-dom`: ^6.1.0
- `@types/jest`: ^29.5.0

### Running JavaScript Tests

```bash
# Run all JS tests
npm test

# Run in watch mode
npm run test:watch

# Run with coverage
npm test -- --coverage

# Run specific test file
npm test -- tests/js/store/store.test.js
```

### Writing JavaScript Tests

```javascript
import { render, screen, fireEvent } from '@testing-library/react';

describe('MyComponent', () => {
    it('should render correctly', () => {
        render(<MyComponent />);
        
        expect(screen.getByText('Hello')).toBeInTheDocument();
    });
    
    it('should handle click events', () => {
        const handleClick = jest.fn();
        render(<MyComponent onClick={handleClick} />);
        
        fireEvent.click(screen.getByRole('button'));
        
        expect(handleClick).toHaveBeenCalledTimes(1);
    });
});
```

### Testing Redux Store

```javascript
import { createRegistry } from '@wordpress/data';

describe('Store Tests', () => {
    let registry;
    
    beforeEach(() => {
        registry = createRegistry();
        // Register your store
    });
    
    it('should update state', () => {
        registry.dispatch('meowseo/data').updateMeta('title', 'Test');
        
        const title = registry.select('meowseo/data').getMetaField('title');
        expect(title).toBe('Test');
    });
});
```

## Running Tests

### Quick Commands

```bash
# Run all tests (PHP + JS)
npm run test:all

# Run only PHP tests
composer test

# Run only JS tests
npm test

# Run tests with coverage
npm run test:coverage
```

### Watch Mode (Development)

```bash
# Watch JS tests
npm run test:watch

# Watch specific test file
npm test -- --watch tests/js/store/store.test.js
```

## Writing Tests

### Test Naming Conventions

**PHP:**
- Test files: `*Test.php`
- Test methods: `test_description_of_what_is_tested()`
- Use snake_case for method names

**JavaScript:**
- Test files: `*.test.js`, `*.test.jsx`, `*.test.ts`, `*.test.tsx`
- Describe blocks: `describe('ComponentName', () => {})`
- Test cases: `it('should do something', () => {})`

### Test Organization

**Unit Tests:**
- Test single class/function in isolation
- Mock all dependencies
- Fast execution

**Integration Tests:**
- Test multiple components working together
- Minimal mocking
- Test real interactions

**Component Tests:**
- Test React components
- Test user interactions
- Test rendering and state changes

### What to Test

✅ **DO Test:**
- Public API methods
- Edge cases and error handling
- State changes
- User interactions
- Data transformations
- Validation logic

❌ **DON'T Test:**
- Private methods directly
- Third-party library internals
- WordPress core functions
- Trivial getters/setters

## Coverage Reports

### Generating Coverage

```bash
# PHP coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/php/

# JS coverage
npm test -- --coverage

# View coverage reports
open coverage/php/index.html
open coverage/lcov-report/index.html
```

### Coverage Thresholds

Configured in `phpunit.xml` and `jest.config.js`:

- **Branches**: 50%
- **Functions**: 50%
- **Lines**: 50%
- **Statements**: 50%

Critical modules should aim for 80%+ coverage.

## CI/CD Integration

### GitHub Actions

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  php-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: php-actions/composer@v6
      - name: Run PHPUnit
        run: ./vendor/bin/phpunit
        
  js-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      - run: npm ci
      - run: npm test
```

## Best Practices

### General

1. **Write tests first** (TDD) when possible
2. **Keep tests simple** and focused
3. **Use descriptive names** for tests
4. **Test behavior, not implementation**
5. **Avoid test interdependence**
6. **Mock external dependencies**
7. **Keep tests fast**

### PHP Specific

1. Use type hints in test methods
2. Use `setUp()` and `tearDown()` for common setup
3. Use data providers for testing multiple scenarios
4. Mock WordPress functions with Brain Monkey
5. Test both success and failure paths

### JavaScript Specific

1. Use Testing Library queries (getByRole, getByText)
2. Test user interactions, not implementation details
3. Use `beforeEach()` for common setup
4. Mock API calls with `jest.fn()`
5. Test accessibility (ARIA roles, labels)

### Code Examples

**Good Test:**
```php
public function test_get_returns_default_when_key_not_exists(): void {
    Functions\when('get_option')->justReturn(array());
    
    $options = new Options();
    
    $this->assertEquals('default', $options->get('missing', 'default'));
}
```

**Bad Test:**
```php
public function test_options(): void {
    $options = new Options();
    $this->assertTrue(true); // Meaningless assertion
}
```

## Troubleshooting

### Common Issues

**PHPUnit: Class not found**
- Run `composer dump-autoload`
- Check namespace in test file

**Jest: Module not found**
- Run `npm install`
- Check import paths

**Brain Monkey: Function not mocked**
- Add mock in `setUp()` or `TestCase::mockWordPressFunctions()`

**Coverage not generated**
- Install Xdebug for PHP coverage
- Use `--coverage` flag

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Testing Library](https://testing-library.com/docs/react-testing-library/intro/)
- [Brain Monkey](https://brain-wp.github.io/BrainMonkey/)

## Contributing

When contributing to MeowSEO:

1. **Write tests** for new features
2. **Update existing tests** when changing behavior
3. **Ensure all tests pass** before submitting PR
4. **Maintain coverage** above minimum thresholds
5. **Document complex test scenarios**

---

**Last Updated**: 2026-05-08
**Maintainer**: MeowSEO Team
