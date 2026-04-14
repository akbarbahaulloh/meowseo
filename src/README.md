# MeowSEO Gutenberg Integration

This directory contains the JavaScript source code for the MeowSEO Gutenberg editor integration.

## Architecture

The implementation follows the design document's architecture:

### Redux Store (`store/index.js`)
- Registered as `meowseo/data` via `@wordpress/data`
- State shape: `{ meta, analysis, ui }`
- Selectors: `getSeoMeta()`, `getSeoScore()`, `getReadabilityScore()`, etc.
- Actions: `updateMeta()`, `setAnalysis()`, `setActiveTab()`, `setSaving()`

### ContentSyncHook (`store/content-sync-hook.js`)
- Single `useEffect` that subscribes to `core/editor`
- Reads post content, title, excerpt, slug (read-only)
- Dispatches derived SEO signals to `meowseo/data` only
- **Never dispatches back to `core/editor`** (critical design rule)

### SEO Analysis (`analysis/compute-analysis.js`)
- Pure function: `computeAnalysis({ content, title, excerpt, slug, keyword })`
- Returns: `{ seoScore, seoChecks, readabilityScore, readabilityChecks }`
- SEO checks: keyword in title, description, first paragraph, headings, slug, length checks
- Readability checks: sentence length, paragraph length, transition words, passive voice

### Sidebar Components (`sidebar/`)
- `MeowSeoSidebar.js` - Main sidebar with tab navigation
- `tabs/MetaTab.js` - SEO title, description, robots, canonical
- `tabs/AnalysisTab.js` - SEO score and readability indicators
- `tabs/SocialTab.js` - Open Graph and Twitter Card overrides
- `tabs/SchemaTab.js` - Schema type selection
- `tabs/LinksTab.js` - Internal link suggestions
- `tabs/GscTab.js` - Google Search Console performance data

### Postmeta Persistence
All sidebar components use `useEntityProp` for WordPress postmeta integration:
```javascript
const [metaTitle, setMetaTitle] = useEntityProp('postType', postType, 'meta', 'meowseo_title');
```

## Build Process

### Install Dependencies
```bash
npm install
```

### Development Build (with watch)
```bash
npm start
```

### Production Build
```bash
npm run build
```

### Linting
```bash
npm run lint:js
```

### Formatting
```bash
npm run format:js
```

## Build Output

The build process compiles all source files into:
- `build/index.js` - Compiled JavaScript bundle
- `build/index.asset.php` - WordPress asset dependencies
- `build/index.css` - Compiled CSS styles

## WordPress Integration

The PHP class `includes/modules/meta/class-gutenberg.php` handles:
- Enqueuing the compiled JavaScript bundle
- Enqueuing the compiled CSS styles
- Localizing script with REST API data
- Checking for block editor context

## Key Design Rules

1. **Single Content Sync Hook**: Only `ContentSyncHook` may subscribe to `core/editor`
2. **No Circular Dispatches**: Never dispatch to `core/editor` from a `useEffect` that subscribes to it
3. **Store-First Architecture**: All sidebar components read exclusively from `meowseo/data`
4. **Postmeta Persistence**: Use `useEntityProp` for all postmeta fields
5. **Error Handling**: Wrap analysis in try/catch to avoid breaking the editor

## Testing

To test the Gutenberg integration:

1. Build the JavaScript: `npm run build`
2. Activate the MeowSEO plugin in WordPress
3. Edit a post in the Gutenberg editor
4. Open the MeowSEO sidebar from the editor sidebar
5. Verify all tabs load and function correctly
6. Check that SEO analysis updates in real-time as you edit content

## Dependencies

- `@wordpress/data` - Redux store management
- `@wordpress/element` - React wrapper
- `@wordpress/components` - UI components
- `@wordpress/core-data` - Entity data management
- `@wordpress/edit-post` - Editor integration
- `@wordpress/plugins` - Plugin registration
- `@wordpress/block-editor` - Block editor components
- `@wordpress/api-fetch` - REST API client
- `@wordpress/i18n` - Internationalization

## File Structure

```
src/
├── index.js                      # Entry point
├── editor.css                    # Styles
├── store/
│   ├── index.js                  # Redux store
│   └── content-sync-hook.js      # Content sync hook
├── analysis/
│   └── compute-analysis.js       # SEO analysis logic
└── sidebar/
    ├── MeowSeoSidebar.js         # Main sidebar
    └── tabs/
        ├── MetaTab.js            # Meta fields
        ├── AnalysisTab.js        # Analysis display
        ├── SocialTab.js          # Social overrides
        ├── SchemaTab.js          # Schema selection
        ├── LinksTab.js           # Link suggestions
        └── GscTab.js             # GSC performance
```
