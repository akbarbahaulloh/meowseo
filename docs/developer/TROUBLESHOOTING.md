# 🔧 Troubleshooting Guide

Common issues and solutions for MeowSEO development.

## 📋 Table of Contents

- [Setup Issues](#setup-issues)
- [Build Issues](#build-issues)
- [Test Issues](#test-issues)
- [Runtime Issues](#runtime-issues)
- [Performance Issues](#performance-issues)
- [Database Issues](#database-issues)

---

## Setup Issues

### ❌ "Cannot find module" after git clone

**Problem**: `/build/` directory missing after clone.

**Cause**: `/build/` is in `.gitignore` and not committed.

**Solution**:
```bash
# Build assets
npm install
npm run build

# Verify build directory exists
ls -la build/
```

### ❌ "composer: command not found"

**Problem**: Composer not installed.

**Solution**:
```bash
# Install Composer
# Visit: https://getcomposer.org/download/

# Verify installation
composer --version
```

### ❌ "npm: command not found"

**Problem**: Node.js/npm not installed.

**Solution**:
```bash
# Install Node.js (includes npm)
# Visit: https://nodejs.org/

# Verify installation
node --version
npm --version
```

### ❌ PHP version too old

**Problem**: PHP < 8.0

**Solution**:
```bash
# Check PHP version
php --version

# Upgrade PHP
# Ubuntu/Debian:
sudo apt-get install php8.2

# macOS (Homebrew):
brew install php@8.2

# Windows: Download from php.net
```

---

## Build Issues

### ❌ "npm run build" fails

**Problem**: Build errors.

**Diagnosis**:
```bash
# Check Node version
node --version  # Should be 18.x or higher

# Clear cache
rm -rf node_modules package-lock.json
npm install

# Try build again
npm run build
```

**Common Causes**:
1. **Old Node version**: Upgrade to 18.x+
2. **Corrupted node_modules**: Delete and reinstall
3. **Syntax errors**: Check console output

### ❌ "Module not found" in build

**Problem**: Import errors.

**Solution**:
```bash
# Check import paths
# ❌ Wrong:
import Component from 'components/Component';

# ✅ Correct:
import Component from '../components/Component';

# Or use webpack alias (if configured)
import Component from '@/components/Component';
```

### ❌ Build is very slow

**Problem**: Webpack taking too long.

**Solution**:
```bash
# Use development mode (faster)
npm run start  # Watch mode, faster builds

# Check for large dependencies
npm ls --depth=0

# Clear webpack cache
rm -rf node_modules/.cache
```

### ❌ "ENOSPC: System limit for number of file watchers reached"

**Problem**: Linux file watcher limit.

**Solution**:
```bash
# Increase limit
echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf
sudo sysctl -p

# Verify
cat /proc/sys/fs/inotify/max_user_watches
```

---

## Test Issues

### ❌ "composer test" fails with "Command not defined"

**Problem**: Running from wrong directory.

**Solution**:
```bash
# From root directory
composer test

# Or navigate to worktree
cd .claude/worktrees/reverent-beaver-c7065a
composer test
```

### ❌ PHP tests fail with "Class not found"

**Problem**: Autoloader not updated.

**Solution**:
```bash
cd .claude/worktrees/reverent-beaver-c7065a
composer dump-autoload
composer test
```

### ❌ JavaScript tests fail with "toBeInTheDocument is not a function"

**Problem**: Test setup not loaded.

**Solution**:
```bash
# Check jest.config.js
# Should have:
setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js']

# Verify setup file exists
ls tests/js/setup.js

# Reinstall dependencies
npm install
```

### ❌ Tests pass locally but fail in CI

**Problem**: Environment differences.

**Diagnosis**:
```bash
# Check PHP version
php --version

# Check Node version
node --version

# Run tests with same versions as CI
# See .github/workflows/tests.yml for versions
```

**Solution**:
```bash
# Use Docker to match CI environment
docker run -v $(pwd):/app php:8.2 php /app/vendor/bin/phpunit
```

### ❌ "Jest Haste Map collision"

**Problem**: Duplicate files in root and worktree.

**Solution**:
```javascript
// In jest.config.js, add:
testPathIgnorePatterns: [
  '/node_modules/',
  '/.claude/',  // Ignore worktree
],
```

---

## Runtime Issues

### ❌ Plugin doesn't activate

**Problem**: PHP errors.

**Diagnosis**:
```bash
# Enable WordPress debug
# In wp-config.php:
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

# Check error log
tail -f wp-content/debug.log
```

**Common Causes**:
1. **PHP version**: Requires PHP 8.0+
2. **Missing dependencies**: Run `composer install`
3. **Syntax errors**: Check debug.log
4. **Conflicting plugins**: Deactivate other SEO plugins

### ❌ "Fatal error: Cannot redeclare class"

**Problem**: Class already declared.

**Cause**: Usually from conflicting plugins or duplicate includes.

**Solution**:
```bash
# Deactivate other SEO plugins
# Check for duplicate class names
grep -r "class YourClass" includes/

# Ensure proper namespacing
namespace MeowSEO;
```

### ❌ JavaScript not loading

**Problem**: Assets not enqueued or 404.

**Diagnosis**:
```bash
# Check browser console for errors
# Check if build files exist
ls -la build/

# Verify asset URLs
# Should be: /wp-content/plugins/meowseo/build/index.js
```

**Solution**:
```bash
# Rebuild assets
npm run build

# Clear WordPress cache
# Clear browser cache

# Check file permissions
chmod 644 build/*
```

### ❌ "Uncaught ReferenceError: wp is not defined"

**Problem**: WordPress dependencies not loaded.

**Solution**:
```php
// In PHP, ensure dependencies are declared:
wp_enqueue_script(
    'meowseo-admin',
    MEOWSEO_URL . 'build/index.js',
    [ 'wp-element', 'wp-components', 'wp-data' ],  // Dependencies
    MEOWSEO_VERSION,
    true
);
```

### ❌ REST API returns 404

**Problem**: Permalinks not flushed.

**Solution**:
```bash
# In WordPress admin:
# Settings > Permalinks > Save Changes

# Or via WP-CLI:
wp rewrite flush
```

---

## Performance Issues

### ❌ Plugin is slow

**Diagnosis**:
```php
// Add timing code
$start = microtime( true );
// Your code here
$end = microtime( true );
error_log( 'Execution time: ' . ( $end - $start ) . 's' );
```

**Common Causes**:
1. **No caching**: Enable object cache
2. **Too many queries**: Use query monitor
3. **Large datasets**: Add pagination
4. **No indexes**: Add database indexes

**Solutions**:
```php
// Use transients for caching
$data = get_transient( 'meowseo_cache_key' );
if ( false === $data ) {
    $data = expensive_operation();
    set_transient( 'meowseo_cache_key', $data, HOUR_IN_SECONDS );
}

// Use object cache
$data = wp_cache_get( 'key', 'meowseo' );
if ( false === $data ) {
    $data = expensive_operation();
    wp_cache_set( 'key', $data, 'meowseo', HOUR_IN_SECONDS );
}
```

### ❌ High memory usage

**Diagnosis**:
```php
// Check memory usage
error_log( 'Memory: ' . memory_get_usage( true ) / 1024 / 1024 . ' MB' );
```

**Solutions**:
```php
// Unset large variables
unset( $large_array );

// Use generators for large datasets
function get_posts_generator() {
    $paged = 1;
    while ( $posts = get_posts( [ 'paged' => $paged++ ] ) ) {
        foreach ( $posts as $post ) {
            yield $post;
        }
        if ( count( $posts ) < 10 ) break;
    }
}

// Process in batches
foreach ( get_posts_generator() as $post ) {
    process_post( $post );
}
```

### ❌ Too many database queries

**Diagnosis**:
```bash
# Install Query Monitor plugin
# Check queries in admin bar

# Or use SAVEQUERIES
# In wp-config.php:
define( 'SAVEQUERIES', true );

# In your code:
global $wpdb;
print_r( $wpdb->queries );
```

**Solutions**:
```php
// Use WP_Query with proper args
$query = new WP_Query( [
    'post_type' => 'post',
    'posts_per_page' => 10,
    'no_found_rows' => true,  // Skip count query
    'update_post_meta_cache' => false,  // Skip meta cache
    'update_post_term_cache' => false,  // Skip term cache
] );

// Batch get_post_meta
$meta = get_post_meta( $post_id );  // Get all at once
// Instead of multiple get_post_meta() calls
```

---

## Database Issues

### ❌ "Table doesn't exist"

**Problem**: Database tables not created.

**Solution**:
```bash
# Deactivate and reactivate plugin
# Or run installer manually

# Via WP-CLI:
wp plugin deactivate meowseo
wp plugin activate meowseo
```

### ❌ Database migration fails

**Problem**: Migration errors.

**Diagnosis**:
```php
// Check migration status
$version = get_option( 'meowseo_db_version' );
error_log( 'DB Version: ' . $version );
```

**Solution**:
```bash
# Reset and retry
# Backup database first!

# Delete option
wp option delete meowseo_db_version

# Reactivate plugin
wp plugin deactivate meowseo
wp plugin activate meowseo
```

### ❌ "Duplicate entry" error

**Problem**: Unique constraint violation.

**Solution**:
```php
// Use INSERT IGNORE or ON DUPLICATE KEY UPDATE
global $wpdb;
$wpdb->query( "
    INSERT IGNORE INTO {$wpdb->prefix}meowseo_table
    (column1, column2) VALUES ('value1', 'value2')
" );

// Or check before insert
$exists = $wpdb->get_var( "
    SELECT id FROM {$wpdb->prefix}meowseo_table
    WHERE column1 = 'value1'
" );
if ( ! $exists ) {
    $wpdb->insert( ... );
}
```

---

## 🔍 Debugging Tools

### PHP Debugging

```php
// var_dump with die
var_dump( $variable );
die();

// error_log
error_log( print_r( $variable, true ) );

// WordPress debug functions
_doing_it_wrong( __FUNCTION__, 'Message', '1.0.0' );
trigger_error( 'Message', E_USER_WARNING );
```

### JavaScript Debugging

```javascript
// Console logging
console.log( 'Debug:', variable );
console.table( array );
console.trace();

// Debugger
debugger;

// React DevTools
// Install browser extension
```

### WordPress Tools

```bash
# WP-CLI
wp plugin list
wp option get meowseo_options
wp cache flush

# Query Monitor plugin
# Install from WordPress.org

# Debug Bar plugin
# Install from WordPress.org
```

---

## 📞 Getting More Help

### Before Asking

1. ✅ Check this troubleshooting guide
2. ✅ Search GitHub Issues
3. ✅ Check documentation
4. ✅ Enable debug mode
5. ✅ Check error logs

### When Asking

Include:
- PHP version
- WordPress version
- Node version (if relevant)
- Error messages
- Steps to reproduce
- What you've tried

### Where to Ask

- [GitHub Issues](https://github.com/YOUR_USERNAME/meowseo/issues) - Bugs
- [GitHub Discussions](https://github.com/YOUR_USERNAME/meowseo/discussions) - Questions
- [Stack Overflow](https://stackoverflow.com/questions/tagged/meowseo) - General questions

---

## ✅ Quick Fixes Checklist

- [ ] Run `composer install`
- [ ] Run `npm install`
- [ ] Run `npm run build`
- [ ] Clear WordPress cache
- [ ] Clear browser cache
- [ ] Flush permalinks
- [ ] Check file permissions
- [ ] Enable debug mode
- [ ] Check error logs
- [ ] Deactivate conflicting plugins

---

**Still stuck?** Ask in [GitHub Discussions](https://github.com/YOUR_USERNAME/meowseo/discussions)!
