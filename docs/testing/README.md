# Testing Documentation

This directory contains documentation about testing implementation and coverage.

## Files

### Quick Guides
- **[QUICK_TEST_GUIDE.md](QUICK_TEST_GUIDE.md)** - Quick reference for running tests
- **[README_TESTING.md](README_TESTING.md)** - Testing overview and setup

### Implementation Summaries
- **[ALL_TESTS_PASSING.md](ALL_TESTS_PASSING.md)** - Summary of all passing tests
- **[TESTING_IMPLEMENTATION_SUMMARY.md](TESTING_IMPLEMENTATION_SUMMARY.md)** - Testing implementation details
- **[TEST_COVERAGE_EXPANSION_SUMMARY.md](TEST_COVERAGE_EXPANSION_SUMMARY.md)** - Test coverage expansion summary
- **[ZERO_TEST_COVERAGE_FIXED.md](ZERO_TEST_COVERAGE_FIXED.md)** - How zero test coverage was fixed

## Quick Start

### Run All Tests

```bash
# PHP Tests (PHPUnit)
composer test

# JavaScript Tests (Jest)
npm test

# Run specific test
composer test:filter TestClassName
```

### Test Results

- **PHP Tests**: 81/81 passing (100%)
- **JavaScript Tests**: 23/23 passing (100%)
- **Total**: 104 tests passing

## Test Structure

```
tests/
├── Unit/                    # PHP unit tests
│   ├── Helpers/            # Helper class tests
│   ├── Modules/            # Module tests
│   └── ...
├── js/                      # JavaScript tests
│   ├── components/         # Component tests
│   ├── store/              # Store tests
│   └── setup.js            # Test setup
└── bootstrap.php           # PHPUnit bootstrap
```

## Related Documentation

- [Development Setup](../development/)
- [CI/CD Workflows](../../.github/workflows/)
- [Troubleshooting](../developer/TROUBLESHOOTING.md)
