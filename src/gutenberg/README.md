# MeowSEO Gutenberg Editor Integration

This directory contains the React-based Gutenberg sidebar for the MeowSEO WordPress plugin.

## Directory Structure

```
src/gutenberg/
├── components/     # React components for the sidebar
├── store/          # Redux store (actions, reducers, selectors)
├── workers/        # Web Workers for SEO analysis
└── index.tsx       # Entry point and plugin registration
```

## Build Configuration

- **Build Tool**: @wordpress/scripts v27+
- **Language**: TypeScript with React
- **Module Bundler**: Webpack 5
- **Output**: `build/index.js` and `build/index.asset.php`

## Development Commands

```bash
# Build for production
npm run build

# Start development mode with watch
npm run start

# Type checking
npm run type-check

# Lint JavaScript/TypeScript
npm run lint:js

# Format code
npm run format:js
```

## Architecture

The integration follows a centralized state management pattern:

1. **Single Source of Truth**: `meowseo/data` Redux store
2. **Centralized Content Sync**: `useContentSync` hook (only place that reads from `core/editor`)
3. **Debounced Updates**: 800ms debounce on content changes
4. **Web Worker Analysis**: SEO analysis runs in separate thread
5. **WordPress Integration**: All postmeta operations use `useEntityProp`

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Node.js 18+
- Modern browser with ES6+ and Web Worker support

## Next Steps

See `.kiro/specs/gutenberg-editor-integration/tasks.md` for the implementation plan.
