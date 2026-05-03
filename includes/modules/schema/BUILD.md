# Building the Schema Generator UI

This document explains how to build the React-based UI for the Schema Generator module.

## Prerequisites

- Node.js 14+ and npm
- WordPress 5.0+
- PHP 7.4+

## Installation

1. Navigate to the schema module directory:
```bash
cd includes/modules/schema
```

2. Install dependencies:
```bash
npm install
```

## Development

### Start Development Server

Run the development server with hot reload:

```bash
npm start
```

This will:
- Watch for file changes
- Automatically rebuild on save
- Enable source maps for debugging

### Build for Production

Create optimized production builds:

```bash
npm run build
```

This will:
- Minify JavaScript
- Optimize for performance
- Generate asset files
- Output to `assets/js/` directory

## File Structure

```
src/
├── builder/
│   ├── index.jsx              # Builder entry point
│   └── SchemaBuilder.jsx      # Main builder component
│
├── sidebar/
│   ├── index.jsx              # Sidebar entry point
│   └── SchemaSidebar.jsx      # Sidebar component
│
├── components/
│   ├── SchemaList.jsx         # List all schemas
│   ├── SchemaCard.jsx         # Single schema card
│   ├── SchemaEditor.jsx       # Schema editor form
│   ├── SchemaTypeSelector.jsx # Type selection
│   ├── FieldRenderer.jsx      # Field renderer
│   ├── GroupField.jsx         # Group field
│   ├── RepeaterField.jsx      # Repeater field
│   └── PreviewModal.jsx       # JSON-LD preview
│
└── hooks/
    └── useSchemas.js          # Schema management hook
```

## Output Files

After building, the following files are generated:

```
assets/js/
├── schema-builder.js          # Builder app (compiled)
├── schema-builder.asset.php   # Builder dependencies
├── schema-sidebar.js          # Sidebar plugin (compiled)
└── schema-sidebar.asset.php   # Sidebar dependencies
```

## WordPress Integration

The built files are automatically enqueued by `class-schema-admin.php`:

- **Classic Editor**: Metabox with `schema-builder.js`
- **Gutenberg**: Sidebar panel with `schema-sidebar.js`

## Development Tips

### Hot Reload

When running `npm start`, changes to React components will automatically reload in the browser.

### Debugging

Source maps are enabled in development mode. Use browser DevTools to debug React components.

### Code Formatting

Format code with:
```bash
npm run format
```

### Linting

Check for JavaScript errors:
```bash
npm run lint:js
```

## Troubleshooting

### Build Fails

If the build fails, try:
1. Delete `node_modules` and reinstall: `rm -rf node_modules && npm install`
2. Clear npm cache: `npm cache clean --force`
3. Check Node.js version: `node --version` (should be 14+)

### Assets Not Loading

If assets don't load in WordPress:
1. Check file permissions on `assets/js/` directory
2. Verify files exist: `ls -la assets/js/`
3. Check browser console for errors
4. Clear WordPress cache

### React Components Not Rendering

1. Check browser console for errors
2. Verify REST API is accessible: `/wp-json/meowseo/v1/schema-types`
3. Check nonce is valid
4. Verify user has `edit_posts` capability

## Production Deployment

1. Build for production: `npm run build`
2. Commit built files to version control
3. Deploy to WordPress site
4. Clear WordPress cache
5. Test in both Classic Editor and Gutenberg

## Dependencies

### WordPress Packages

- `@wordpress/element` - React wrapper
- `@wordpress/components` - UI components
- `@wordpress/api-fetch` - REST API client
- `@wordpress/i18n` - Internationalization
- `@wordpress/data` - State management
- `@wordpress/plugins` - Plugin registration
- `@wordpress/edit-post` - Block editor integration

### Build Tools

- `@wordpress/scripts` - Build configuration
- Webpack - Module bundler
- Babel - JavaScript compiler

## Browser Support

The built JavaScript supports:
- Chrome (last 2 versions)
- Firefox (last 2 versions)
- Safari (last 2 versions)
- Edge (last 2 versions)

## Performance

The production build is optimized for:
- Small bundle size (~50KB gzipped)
- Fast initial load
- Code splitting
- Tree shaking

## Next Steps

After building:
1. Test in WordPress admin
2. Create/edit a post
3. Add schemas via metabox or sidebar
4. Preview JSON-LD output
5. Save and verify frontend output

## Support

For issues or questions:
- Check browser console for errors
- Review REST API responses
- Check WordPress debug log
- Verify file permissions

---

**Last Updated:** May 3, 2026  
**Build System:** @wordpress/scripts v26.0.0  
**Status:** ✅ Ready to Build
