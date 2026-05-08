# 🚀 Developer Getting Started Guide

Welcome to MeowSEO development! This guide will help you get started quickly.

## 📋 Prerequisites

### Required
- **PHP**: 8.0 or higher
- **Node.js**: 18.x or higher
- **Composer**: Latest version
- **npm**: Latest version
- **WordPress**: 6.0 or higher (for testing)
- **Git**: For version control

### Recommended
- **Docker**: For local WordPress environment
- **VS Code**: With PHP and JavaScript extensions
- **Xdebug**: For PHP debugging
- **React DevTools**: For React debugging

## 🛠️ Initial Setup

### 1. Clone Repository

```bash
# Clone the repository
git clone https://github.com/YOUR_USERNAME/meowseo.git
cd meowseo
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Build Assets

⚠️ **Important**: `/build/` is not in Git, you must build it!

```bash
# Production build
npm run build

# Development build (watch mode)
npm run start
```

### 4. Run Tests

```bash
# PHP tests
composer test

# JavaScript tests
npm test

# All tests
composer test && npm test
```

### 5. Setup WordPress

#### Option A: Local WordPress Install

```bash
# Copy plugin to WordPress
cp -r . /path/to/wordpress/wp-content/plugins/meowseo

# Or create symlink
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/meowseo
```

#### Option B: Docker (Recommended)

```bash
# Use wp-env (WordPress official)
npm install -g @wordpress/env
wp-env start

# Plugin will be automatically available
```

### 6. Activate Plugin

1. Go to WordPress admin
2. Navigate to Plugins
3. Activate "MeowSEO"
4. Setup wizard will launch

## 📁 Project Structure

```
meowseo/
├── includes/              # PHP classes (PSR-4)
│   ├── class-plugin.php   # Main plugin class
│   ├── class-options.php  # Options management
│   ├── modules/           # Feature modules
│   ├── admin/             # Admin functionality
│   └── helpers/           # Helper classes
│
├── src/                   # Source files (React, TypeScript)
│   ├── blocks/            # Gutenberg blocks
│   ├── components/        # React components
│   ├── store/             # Redux store
│   └── index.js           # Entry point
│
├── build/                 # Built assets (GENERATED)
│   ├── index.js           # Built JavaScript
│   └── index.css          # Built CSS
│
├── assets/                # Static assets
│   ├── css/               # Static CSS
│   ├── js/                # Static JavaScript
│   └── images/            # Images
│
├── tests/                 # Test files
│   ├── Unit/              # PHP unit tests
│   ├── js/                # JavaScript tests
│   └── debug/             # Debug scripts
│
├── docs/                  # Documentation
│   ├── developer/         # Developer docs
│   ├── api/               # API documentation
│   └── performance/       # Performance docs
│
├── .github/               # GitHub Actions
│   └── workflows/         # CI/CD workflows
│
├── meowseo.php            # Main plugin file
├── composer.json          # PHP dependencies
├── package.json           # Node dependencies
└── README.md              # Project readme
```

## 🔧 Development Workflow

### Daily Development

```bash
# 1. Create feature branch
git checkout -b feature/your-feature

# 2. Start watch mode (auto-rebuild)
npm run start

# 3. Make changes
# Edit files in src/ or includes/

# 4. Test frequently
composer test
npm test

# 5. Commit (DON'T commit /build/)
git add src/ includes/ tests/
git commit -m "Add feature: description"

# 6. Push and create PR
git push origin feature/your-feature
```

### Before Committing

```bash
# 1. Run all tests
composer test && npm test

# 2. Lint code
npm run lint:js
npm run type-check

# 3. Format code
npm run format:js

# 4. Build production assets
npm run build

# 5. Verify plugin works
# Test in WordPress admin
```

## 🧪 Testing

### PHP Tests (PHPUnit)

```bash
# Run all tests
composer test

# Run specific test file
cd .claude/worktrees/reverent-beaver-c7065a
./vendor/bin/phpunit tests/Unit/Helpers/CacheTest.php

# Run with detailed output
./vendor/bin/phpunit --testdox

# Run with coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/
```

### JavaScript Tests (Jest)

```bash
# Run all tests
npm test

# Watch mode (auto-rerun)
npm run test:watch

# With coverage
npm test -- --coverage

# Run specific test
npm test -- tests/js/components/SampleComponent.test.jsx
```

### Writing Tests

#### PHP Test Example

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

#### JavaScript Test Example

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

## 🎨 Coding Standards

### PHP

Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

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

Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/):

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

## 🔍 Debugging

### PHP Debugging

#### Using Xdebug

```php
// Set breakpoint in your IDE
xdebug_break();

// Or use var_dump
var_dump( $variable );
```

#### Using WordPress Debug

```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

// In your code
error_log( print_r( $variable, true ) );
```

### JavaScript Debugging

```javascript
// Browser console
console.log( 'Debug:', variable );

// React DevTools
// Install React DevTools browser extension

// Debugger statement
debugger;
```

## 📦 Building & Releasing

### Local Build

```bash
# Build production assets
npm run build

# Create release ZIP
.\build-release.ps1  # Windows
./build-release.sh   # Linux/Mac
```

### Automated Release

```bash
# Create and push tag
git tag v1.0.0
git push origin v1.0.0

# GitHub Actions will automatically:
# 1. Run tests
# 2. Build assets
# 3. Create release ZIP
# 4. Create GitHub Release
```

## 🔌 Plugin Architecture

### Modular System

MeowSEO uses a modular architecture:

```php
// Module interface
interface Module_Interface {
    public function get_id(): string;
    public function boot(): void;
}

// Example module
class Meta_Module implements Module_Interface {
    public function get_id(): string {
        return 'meta';
    }
    
    public function boot(): void {
        // Initialize module
    }
}
```

### Hooks & Filters

```php
// Actions
do_action( 'meowseo_before_save', $post_id );
do_action( 'meowseo_after_save', $post_id );

// Filters
$title = apply_filters( 'meowseo_title', $title, $post_id );
$description = apply_filters( 'meowseo_description', $description, $post_id );
```

## 🌐 REST API

### Endpoints

```php
// Register endpoint
register_rest_route( 'meowseo/v1', '/meta/(?P<id>\d+)', [
    'methods'  => 'GET',
    'callback' => [ $this, 'get_meta' ],
    'permission_callback' => [ $this, 'check_permission' ],
] );

// Use endpoint
GET /wp-json/meowseo/v1/meta/123
```

### GraphQL

```php
// Register GraphQL field
register_graphql_field( 'Post', 'seoMeta', [
    'type' => 'SeoMeta',
    'resolve' => function( $post ) {
        return get_post_meta( $post->ID, '_meowseo_meta', true );
    },
] );
```

## 🎯 Common Tasks

### Adding a New Module

1. Create module class in `includes/modules/`
2. Implement `Module_Interface`
3. Register in `class-module-manager.php`
4. Add tests in `tests/Unit/Modules/`
5. Update documentation

### Adding a New Setting

1. Add to `class-options.php` defaults
2. Add UI in admin settings
3. Add validation
4. Add tests
5. Update documentation

### Adding a New Schema Type

1. Add template in schema module
2. Add UI in schema builder
3. Add validation
4. Add tests
5. Update documentation

## 📚 Resources

### Documentation
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [React Documentation](https://react.dev/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [WPGraphQL](https://www.wpgraphql.com/)

### Tools
- [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards)
- [PHPUnit](https://phpunit.de/)
- [Jest](https://jestjs.io/)
- [React Testing Library](https://testing-library.com/react)

### Community
- [GitHub Issues](https://github.com/YOUR_USERNAME/meowseo/issues)
- [GitHub Discussions](https://github.com/YOUR_USERNAME/meowseo/discussions)
- [Contributing Guide](../../CONTRIBUTING.md)

## 🆘 Getting Help

### Documentation
1. Check this guide first
2. Read `CONTRIBUTING.md`
3. Check `docs/` directory
4. Read inline code comments

### Community
1. Search GitHub Issues
2. Ask in GitHub Discussions
3. Check existing PRs
4. Read test files for examples

### Troubleshooting
1. Check `docs/developer/TROUBLESHOOTING.md`
2. Run tests to verify setup
3. Check build output
4. Enable WordPress debug mode

## ✅ Checklist for New Developers

- [ ] Clone repository
- [ ] Install dependencies (composer + npm)
- [ ] Build assets (`npm run build`)
- [ ] Run tests (all passing)
- [ ] Setup WordPress environment
- [ ] Activate plugin
- [ ] Read project structure
- [ ] Read coding standards
- [ ] Make first commit
- [ ] Create first PR

## 🎉 Welcome!

You're now ready to contribute to MeowSEO!

**Next steps**:
1. Check [GitHub Issues](https://github.com/YOUR_USERNAME/meowseo/issues) for "good first issue"
2. Read [CONTRIBUTING.md](../../CONTRIBUTING.md)
3. Join discussions
4. Start coding!

---

**Happy coding!** 🚀

If you have questions, don't hesitate to ask in GitHub Discussions.
