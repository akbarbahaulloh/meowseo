# 🐛 Debug Test Files

This directory contains debug and manual test PHP files used during development.

## ⚠️ Important

**These files are NOT part of the automated test suite!**

- These are manual debug scripts
- Used for troubleshooting during development
- Should NOT be run in production
- NOT included in release builds

## 📁 Files

### API Testing
- `debug-test-connection.php` - Debug API connection issues
- `test-api-connection.php` - Manual API connection test
- `force-update-check.php` - Force update check test

### Autoloader Testing
- `test-autoload.php` - Test autoloader functionality

### Logger Testing
- `test-logger-dedup-debug.php` - Debug logger deduplication
- `test-logger-property-debug.php` - Debug logger properties
- `test-reset-logger.php` - Reset logger state

### Schema Testing
- `test-article-node-manual.php` - Manual article schema test
- `test-property11-minimal.php` - Minimal property test

### GitHub Updater Testing
- `test-github-updater.php` - Test GitHub auto-update

### General Debug
- `test-debug.php` - General debug script
- `test-debug2.php` - Additional debug script

## 🚀 Usage

### Running Debug Scripts

```bash
# From WordPress root
php wp-content/plugins/meowseo/tests/debug/test-api-connection.php

# Or via browser (if in WordPress)
# https://yoursite.com/wp-content/plugins/meowseo/tests/debug/test-api-connection.php
```

### Security Warning

⚠️ **Never run these in production!**

These scripts may:
- Output sensitive information
- Bypass security checks
- Modify database directly
- Expose internal state

## 🧪 Automated Tests

For automated tests, see:
- `/tests/Unit/` - PHPUnit unit tests
- `/tests/js/` - Jest JavaScript tests

Run automated tests:
```bash
# PHP tests
composer test

# JavaScript tests
npm test
```

## 📝 Creating Debug Scripts

### Template

```php
<?php
/**
 * Debug script: [Description]
 *
 * Purpose: [What this script tests/debugs]
 * Usage: php test-your-script.php
 *
 * @package MeowSEO\Tests\Debug
 */

// Load WordPress
require_once dirname( __DIR__, 4 ) . '/wp-load.php';

// Your debug code here
echo "Debug output:\n";

// Example
$result = your_function_to_test();
var_dump( $result );
```

### Best Practices

1. ✅ Add clear description at top
2. ✅ Use descriptive file names
3. ✅ Include usage instructions
4. ✅ Clean up after yourself
5. ✅ Don't commit sensitive data

## 🗑️ Cleanup

These files should be:
- ✅ Kept in Git (for development reference)
- ❌ NOT included in release builds
- ❌ NOT run in production

The build script automatically excludes this directory from releases.

## 📚 Related

- [Automated Tests](../Unit/) - PHPUnit tests
- [JavaScript Tests](../js/) - Jest tests
- [Testing Guide](../../docs/TESTING.md) - Complete testing guide

---

**Remember**: These are debug tools, not production code!
