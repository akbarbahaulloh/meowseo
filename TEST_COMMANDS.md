# 🧪 Test Commands Quick Reference

## ✅ From Root Directory (D:\meowseo)

```bash
# Run all tests (recommended)
composer test

# Or navigate to worktree and run
cd .claude\worktrees\reverent-beaver-c7065a
composer test
```

## ✅ From Worktree Directory

```bash
# If you're already in .claude\worktrees\reverent-beaver-c7065a
composer test
./vendor/bin/phpunit
./vendor/bin/phpunit --testdox
```

## 📊 Current Status

```
✅ Tests:       81
✅ Passing:     81 (100%)
✅ Assertions:  934
✅ Time:        < 600ms
```

## 🎯 Specific Test Commands

```bash
# From worktree directory:

# Run specific test file
./vendor/bin/phpunit tests/Unit/Helpers/CacheTest.php

# Run specific directory
./vendor/bin/phpunit tests/Unit/Helpers/

# Run with detailed output
./vendor/bin/phpunit --testdox

# Run with coverage (requires Xdebug)
composer test:coverage
```

## 📁 Where Are Tests?

```
D:\meowseo\                           ← You are here
└── .claude\
    └── worktrees\
        └── reverent-beaver-c7065a\   ← Tests are here
            ├── tests\
            │   ├── Unit\
            │   │   ├── Helpers\
            │   │   ├── Modules\
            │   │   └── *.php
            │   ├── bootstrap.php
            │   └── TestCase.php
            ├── vendor\
            ├── composer.json
            └── phpunit.xml
```

## 🚀 One-Line Commands

```bash
# From root directory (recommended)
composer test

# From anywhere, run tests and return
cd .claude\worktrees\reverent-beaver-c7065a && composer test && cd ..\..\..
```

## ⚠️ Common Errors

### "Command test is not defined"
**Problem**: Old composer.json configuration  
**Solution**: Use `composer test` from root directory (now fixed!)

### "Cannot open file tests/"
**Problem**: Wrong path  
**Solution**: Make sure you're in `.claude\worktrees\reverent-beaver-c7065a`

### "Class not found"
**Problem**: Autoload not updated  
**Solution**: Run `composer dump-autoload` in worktree directory

## 📚 Documentation

- `RUN_TESTS.md` - Detailed instructions
- `QUICK_TEST_GUIDE.md` - Quick reference
- `ALL_TESTS_PASSING.md` - Success report
- `docs/TESTING.md` - Comprehensive guide

---

**Quick Answer**: From root, use `composer test` ✅
