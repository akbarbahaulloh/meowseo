# ✅ Autoloader Conflict Fix

**Date**: 2026-05-08  
**Issue**: Fatal error when running `composer test` from root directory  
**Status**: ✅ **FIXED**

## 🐛 Problem

When running `composer test` from the root directory (`D:\meowseo`), the following error occurred:

```
Fatal error: Cannot declare class ComposerAutoloaderInit4e1c0ad9890e1b7ab3a4c2baf19febdc, 
because the name is already in use in 
D:\meowseo\.claude\worktrees\reverent-beaver-c7065a\vendor\composer\autoload_real.php on line 5

Script cd .claude/worktrees/reverent-beaver-c7065a && phpunit handling the test event 
returned with error code 255
```

## 🔍 Root Cause

The original composer script used:
```json
"scripts": {
    "test": "cd .claude/worktrees/reverent-beaver-c7065a && phpunit",
    "test:worktree": "cd .claude/worktrees/reverent-beaver-c7065a && composer test"
}
```

**The Problem**:
1. Running `composer test` from root loads the root's Composer autoloader
2. The script then tries to `cd` into worktree and run `phpunit`
3. PHPUnit tries to load the worktree's Composer autoloader
4. **Conflict**: The autoloader class is already declared from step 1
5. Fatal error occurs

## ✅ Solution

Changed the composer script to directly invoke PHPUnit with explicit paths:

```json
"scripts": {
    "test": "@php .claude/worktrees/reverent-beaver-c7065a/vendor/bin/phpunit -c .claude/worktrees/reverent-beaver-c7065a/phpunit.xml",
    "test:filter": "@php .claude/worktrees/reverent-beaver-c7065a/vendor/bin/phpunit -c .claude/worktrees/reverent-beaver-c7065a/phpunit.xml --filter"
}
```

**Why This Works**:
- Uses `@php` prefix to run PHP directly (Composer's built-in feature)
- Specifies full path to PHPUnit binary in worktree
- Uses `-c` flag to specify phpunit.xml location
- No `cd` command needed, so no shell subprocess
- Only one autoloader gets loaded (the worktree's)

## 🧪 Verification

```bash
PS D:\meowseo> composer test
MeowSEO Test Bootstrap loaded successfully.
PHPUnit 9.6.34 by Sebastian Bergmann and contributors.

................................................................. 65 / 81 ( 80%)
................                                                  81 / 81 (100%)

Time: 00:00.598, Memory: 20.00 MB

OK (81 tests, 934 assertions)
```

✅ **All 81 tests passing!**

## 📝 Updated Commands

### From Root Directory (Recommended)
```bash
# Run all tests
composer test

# Run specific test (using filter)
composer test:filter OptionsTest
```

### From Worktree Directory
```bash
cd .claude/worktrees/reverent-beaver-c7065a

# Run all tests
composer test
./vendor/bin/phpunit

# Run with detailed output
./vendor/bin/phpunit --testdox

# Run specific test file
./vendor/bin/phpunit tests/Unit/Helpers/CacheTest.php
```

## 🎯 Benefits

1. ✅ **Works from root directory** - No need to navigate to worktree
2. ✅ **No autoloader conflicts** - Only loads one autoloader
3. ✅ **Simpler workflow** - Just run `composer test`
4. ✅ **Added filter support** - `composer test:filter TestName`
5. ✅ **Faster** - No shell subprocess overhead

## 📚 Documentation Updated

- ✅ `TEST_COMMANDS.md` - Updated with new commands
- ✅ `QUICK_TEST_GUIDE.md` - Updated troubleshooting section
- ✅ `composer.json` - Fixed scripts
- ✅ `AUTOLOADER_CONFLICT_FIX.md` - This document

## 🔧 Technical Details

### Composer Script Prefixes

Composer supports special prefixes for scripts:
- `@php` - Runs PHP directly
- `@composer` - Runs Composer
- `@putenv` - Sets environment variables

Using `@php` ensures:
- Cross-platform compatibility (Windows/Linux/Mac)
- No shell interpretation issues
- Direct PHP execution without subprocess

### PHPUnit Configuration

The `-c` flag tells PHPUnit where to find its configuration:
```bash
phpunit -c /path/to/phpunit.xml
```

This allows running tests from any directory while using the correct configuration.

## 🎉 Result

**Before**: ❌ Fatal error when running from root  
**After**: ✅ Works perfectly from root directory

```bash
# Simple, clean, works!
composer test
```

---

**Fixed by**: Kiro AI  
**Date**: 2026-05-08  
**Status**: ✅ Production Ready  
**Tests**: 81/81 passing (100%)
