# MeowSEO Build Instructions

This document provides instructions for building the MeowSEO plugin's JavaScript assets.

## Prerequisites

- Node.js 14.x or higher
- npm 6.x or higher

## Initial Setup

1. Install Node.js dependencies:
```bash
npm install
```

This will install all required WordPress packages and build tools.

## Development Workflow

### Development Build with Watch Mode

For active development, use the watch mode which automatically rebuilds when you make changes:

```bash
npm start
```

This will:
- Compile JavaScript from `src/` to `build/`
- Watch for file changes and rebuild automatically
- Generate source maps for debugging
- Start a development server

### Production Build

For production deployment, create an optimized build:

```bash
npm run build
```

This will:
- Compile and minify JavaScript
- Optimize CSS
- Generate asset dependency files
- Create production-ready bundles in `build/`

## Build Output

The build process generates the following files in the `build/` directory:

- `index.js` - Compiled JavaScript bundle
- `index.asset.php` - WordPress asset dependencies and version
- `index.css` - Compiled CSS styles

## Code Quality

### Linting

Check JavaScript code for errors and style issues:

```bash
npm run lint:js
```

### Formatting

Auto-format JavaScript code:

```bash
npm run format:js
```

## WordPress Integration

After building, the plugin automatically enqueues the compiled assets when:

1. The Meta module is enabled
2. You're editing a post in the Gutenberg editor
3. The build files exist in the `build/` directory

The PHP class `includes/modules/meta/class-gutenberg.php` handles asset enqueuing.

## Troubleshooting

### Build file not found error

If you see "Build file not found" in the WordPress debug log:

1. Run `npm install` to install dependencies
2. Run `npm run build` to compile assets
3. Verify `build/index.js` exists

### Module not found errors

If you see module import errors:

1. Delete `node_modules/` directory
2. Delete `package-lock.json`
3. Run `npm install` again

### Watch mode not working

If file changes aren't triggering rebuilds:

1. Stop the watch process (Ctrl+C)
2. Clear the build directory: `rm -rf build/`
3. Restart: `npm start`

## Deployment

For production deployment:

1. Run `npm run build` to create optimized bundles
2. Commit the `build/` directory to version control (or deploy it separately)
3. Do NOT commit `node_modules/` directory

## Development Tips

- Use `npm start` during development for fast rebuilds
- The build process uses `@wordpress/scripts` which includes webpack, Babel, and ESLint
- Source maps are generated in development mode for easier debugging
- Production builds are minified and optimized for performance

## File Structure

```
meowseo/
├── src/                          # Source files (edit these)
│   ├── index.js
│   ├── editor.css
│   ├── store/
│   ├── analysis/
│   └── sidebar/
├── build/                        # Compiled files (generated)
│   ├── index.js
│   ├── index.asset.php
│   └── index.css
├── package.json                  # npm configuration
├── webpack.config.js             # Build configuration
└── node_modules/                 # Dependencies (generated)
```

## Additional Resources

- [WordPress Scripts Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)
- [Gutenberg Development Guide](https://developer.wordpress.org/block-editor/)
- [WordPress Data Package](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/)
