# How to Run Tests

## ⚠️ Important: Test Location

Tests are located in the **worktree directory**, not the main project root.

## 🚀 Option 1: Navigate to Worktree (Recommended)

```bash
# Navigate to worktree
cd .claude/worktrees/reverent-beaver-c7065a

# Run tests
composer test

# Or with PHPUnit directly
./vendor/bin/phpunit --testdox
```

## 🚀 Option 2: Run from Root Directory

```bash
# From D:\meowseo
composer test:worktree

# Or directly
cd .claude/worktrees/reverent-beaver-c7065a && composer test
```

## 📊 Expected Output

```
MeowSEO Test Bootstrap loaded successfully.
PHPUnit 9.6.34 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.30

OK (81 tests, 934 assertions)
```

## 📁 Directory Structure

```
D:\meowseo\                           ← Root (you are here)
└── .claude\
    └── worktrees\
        └── reverent-beaver-c7065a\   ← Tests are here!
            ├── tests\
            ├── vendor\
            ├── composer.json
            └── phpunit.xml
```

## ✅ Quick Commands

```bash
# From root directory (D:\meowseo)
cd .claude\worktrees\reverent-beaver-c7065a
composer test

# Or in one line
cd .claude\worktrees\reverent-beaver-c7065a && composer test && cd ..\..\..
```

## 🎯 Test Results

- **Total Tests**: 81
- **Passing**: 81 (100%)
- **Assertions**: 934
- **Time**: < 600ms

---

**Note**: Always navigate to the worktree directory first, or use the full path commands.
