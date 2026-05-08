# Testing Quick Start Guide

## 🚀 Quick Start

### Run All Tests
```bash
# PHP + JavaScript
npm run test:all

# PHP only
composer test

# JavaScript only
npm test
```

### Watch Mode (Development)
```bash
npm run test:watch
```

## 📊 Current Status

✅ **Test Infrastructure**: Complete  
✅ **Sample Tests**: 74 test cases  
✅ **CI/CD**: GitHub Actions configured  
📝 **Coverage**: Infrastructure ready

## 📁 Test Structure

```
tests/
├── bootstrap.php              # PHPUnit setup
├── TestCase.php              # Base test class
├── Unit/                     # PHP unit tests
│   ├── OptionsTest.php       (15 tests)
│   ├── PluginTest.php        (5 tests)
│   └── ModuleManagerTest.php (5 tests)
└── js/                       # JavaScript tests
    ├── setup.js              # Jest setup
    ├── components/           # Component tests (9 tests)
    └── store/                # Store tests (40 tests)
```

## ✍️ Writing Your First Test

### PHP Test
```php
<?php
namespace MeowSEO\Tests\Unit;

use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;

class MyTest extends TestCase {
    public function test_something(): void {
        Functions\when('get_option')->justReturn('value');
        
        // Your test code here
        $this->assertEquals('expected', 'actual');
    }
}
```

### JavaScript Test
```javascript
import { render, screen } from '@testing-library/react';

describe('MyComponent', () => {
    it('should render', () => {
        render(<MyComponent />);
        expect(screen.getByText('Hello')).toBeInTheDocument();
    });
});
```

## 📖 Full Documentation

See [docs/TESTING.md](docs/TESTING.md) for comprehensive guide.

## 🎯 Coverage Goals

- **Minimum**: 50% for all code
- **Critical**: 80% for core functionality
- **New Features**: Must include tests

## 🔧 Troubleshooting

### PHPUnit: Class not found
```bash
composer dump-autoload
```

### Jest: Module not found
```bash
npm install
```

### Coverage not generated
```bash
# PHP (requires Xdebug)
composer test:coverage

# JavaScript
npm run test:coverage
```

## 📚 Resources

- [Testing Guide](docs/TESTING.md)
- [PHPUnit Docs](https://phpunit.de/)
- [Jest Docs](https://jestjs.io/)
- [Testing Library](https://testing-library.com/)

---

**Need Help?** Check [TESTING_IMPLEMENTATION_SUMMARY.md](TESTING_IMPLEMENTATION_SUMMARY.md)
