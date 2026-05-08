# Contributing to MeowSEO

Thank you for your interest in contributing to MeowSEO! This document provides guidelines and instructions for contributing.

## 🚀 Getting Started

### Prerequisites

- PHP 8.0 or higher
- Node.js 18 or higher
- Composer
- npm or yarn
- WordPress 6.0+ (for testing)

### Development Setup

⚠️ **Important**: `/build/` directory is not committed to Git. You must build assets before the plugin will work.

```bash
# 1. Fork and clone the repository
git clone https://github.com/YOUR_USERNAME/meowseo.git
cd meowseo

# 2. Install dependencies
composer install
npm install

# 3. Build assets (REQUIRED!)
npm run build

# 4. Run tests to verify setup
composer test
npm test
```

See [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md) for detailed setup instructions.

## 📁 Project Structure

```
meowseo/
├── src/                    # Source files (React, TypeScript)
│   ├── blocks/            # Gutenberg blocks
│   ├── components/        # React components
│   └── store/             # Redux store
├── includes/              # PHP classes
│   ├── modules/           # Feature modules
│   ├── admin/             # Admin functionality
│   └── helpers/           # Helper classes
├── assets/                # Static assets
├── tests/                 # Test files
│   ├── Unit/              # PHP unit tests
│   └── js/                # JavaScript tests
├── build/                 # Built assets (GENERATED, not in Git)
└── vendor/                # Composer dependencies
```

## 🔧 Development Workflow

### Daily Development

```bash
# 1. Create a feature branch
git checkout -b feature/your-feature-name

# 2. Start watch mode (auto-rebuild on changes)
npm run start

# 3. Make your changes
# Edit files in src/ or includes/

# 4. Run tests frequently
composer test
npm test

# 5. Commit your changes (DON'T commit /build/)
git add src/ includes/ tests/
git commit -m "Add feature: your feature description"

# 6. Push and create pull request
git push origin feature/your-feature-name
```

### Before Submitting PR

```bash
# 1. Run all tests
composer test && npm test

# 2. Lint and format code
npm run lint:js
npm run format:js

# 3. Type check
npm run type-check

# 4. Build production assets
npm run build

# 5. Verify plugin works
# Test in WordPress admin
```

## ✅ Testing

### PHP Tests (PHPUnit)

```bash
# Run all PHP tests
composer test

# Run specific test file
cd .claude/worktrees/reverent-beaver-c7065a
./vendor/bin/phpunit tests/Unit/Helpers/CacheTest.php

# Run with detailed output
./vendor/bin/phpunit --testdox
```

### JavaScript Tests (Jest)

```bash
# Run all JavaScript tests
npm test

# Watch mode (auto-rerun on changes)
npm run test:watch

# With coverage
npm test -- --coverage

# Run specific test
npm test -- tests/js/components/SampleComponent.test.jsx
```

### Writing Tests

**Always add tests for new features or bug fixes!**

#### PHP Test Example:
```php
<?php
namespace MeowSEO\Tests\Unit;

use MeowSEO\Tests\TestCase;
use MeowSEO\YourClass;

class YourClassTest extends TestCase {
    public function test_your_feature() {
        $instance = new YourClass();
        $result = $instance->yourMethod();
        
        $this->assertEquals( 'expected', $result );
    }
}
```

#### JavaScript Test Example:
```javascript
import { render, screen } from '@testing-library/react';
import YourComponent from '../YourComponent';

describe( 'YourComponent', () => {
    it( 'should render correctly', () => {
        render( <YourComponent /> );
        expect( screen.getByText( 'Expected Text' ) ).toBeInTheDocument();
    } );
} );
```

## 📝 Coding Standards

### PHP

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use PSR-4 autoloading
- Add PHPDoc comments for all classes and methods
- Use type hints (PHP 8.0+)

```php
<?php
/**
 * Class description.
 *
 * @package MeowSEO
 */

namespace MeowSEO;

class YourClass {
    /**
     * Method description.
     *
     * @param string $param Parameter description.
     * @return bool Return value description.
     */
    public function yourMethod( string $param ): bool {
        // Implementation
        return true;
    }
}
```

### JavaScript/TypeScript

- Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- Use ESLint and Prettier (configured in project)
- Use TypeScript for type safety
- Add JSDoc comments

```javascript
/**
 * Component description.
 *
 * @param {Object} props - Component props.
 * @param {string} props.title - Title prop.
 * @return {JSX.Element} Component element.
 */
const YourComponent = ( { title } ) => {
    return <div>{ title }</div>;
};
```

## 🎯 Pull Request Guidelines

### PR Checklist

- [ ] Tests added/updated and passing
- [ ] Code follows project coding standards
- [ ] Documentation updated (if needed)
- [ ] Commit messages are clear and descriptive
- [ ] No merge conflicts
- [ ] `/build/` directory NOT committed

### PR Title Format

```
Type: Brief description

Examples:
- Feature: Add breadcrumb schema support
- Fix: Resolve cache invalidation issue
- Docs: Update installation instructions
- Test: Add tests for Meta module
```

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
How to test these changes

## Screenshots (if applicable)
Add screenshots here

## Checklist
- [ ] Tests pass
- [ ] Code follows standards
- [ ] Documentation updated
```

## 🐛 Reporting Bugs

### Before Reporting

1. Check [existing issues](https://github.com/your/meowseo/issues)
2. Test with latest version
3. Disable other plugins to rule out conflicts

### Bug Report Template

```markdown
**Describe the bug**
Clear description of the bug

**To Reproduce**
Steps to reproduce:
1. Go to '...'
2. Click on '...'
3. See error

**Expected behavior**
What you expected to happen

**Screenshots**
If applicable

**Environment:**
- WordPress version:
- PHP version:
- MeowSEO version:
- Browser (if relevant):

**Additional context**
Any other relevant information
```

## 💡 Feature Requests

We welcome feature requests! Please:

1. Check if feature already requested
2. Explain the use case
3. Describe expected behavior
4. Consider implementation complexity

## 🔄 Release Process

### For Maintainers

```bash
# 1. Update version numbers
# Edit: meowseo.php, package.json, composer.json

# 2. Update CHANGELOG.md

# 3. Run all tests
composer test && npm test

# 4. Commit and tag
git add .
git commit -m "Release v1.0.0"
git tag v1.0.0
git push origin main --tags

# 5. Build release ZIP
.\build-release.ps1  # Windows
./build-release.sh   # Linux/Mac

# 6. Create GitHub Release
# Upload meowseo.zip to GitHub Releases
```

## 📚 Additional Resources

- [Development Setup Guide](DEVELOPMENT_SETUP.md)
- [Release Guide](PANDUAN_RELEASE.md)
- [Testing Guide](docs/TESTING.md)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [React Documentation](https://react.dev/)

## 🤝 Code of Conduct

### Our Standards

- Be respectful and inclusive
- Accept constructive criticism
- Focus on what's best for the community
- Show empathy towards others

### Unacceptable Behavior

- Harassment or discriminatory language
- Trolling or insulting comments
- Public or private harassment
- Publishing others' private information

## 📞 Getting Help

- **Issues**: [GitHub Issues](https://github.com/your/meowseo/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your/meowseo/discussions)
- **Documentation**: [Wiki](https://github.com/your/meowseo/wiki)

## 📄 License

By contributing to MeowSEO, you agree that your contributions will be licensed under the GPL-2.0-or-later license.

---

**Thank you for contributing to MeowSEO!** 🎉

Your contributions help make MeowSEO better for everyone.
