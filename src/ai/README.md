# AI Generator Module - Gutenberg Sidebar Plugin

This directory contains the React components and styles for the AI Generator sidebar panel in the WordPress Gutenberg block editor.

## Overview

The AI Generator module provides a sidebar panel in the Gutenberg editor that allows content editors to:
- Generate SEO metadata (title, description, keywords, etc.)
- Generate featured images using AI
- Preview generated content before applying
- Apply generated content to post fields

## Architecture

### File Structure

```
src/ai/
├── index.js                          # Entry point - registers the sidebar plugin
├── components/
│   ├── index.js                      # Component exports
│   ├── AiGeneratorPanel.js           # Main panel component
│   └── PreviewPanel.js               # Preview and edit component
└── styles/
    ├── ai-generator.css             # Sidebar plugin styles
    ├── ai-generator-panel.css        # Panel component styles
    └── preview-panel.css             # Preview component styles
```

## Build Process

### Webpack Configuration

The AI module is built using `wp-scripts` with a custom webpack configuration that:

1. **Entry Point**: `src/ai/index.js`
2. **Output**: `build/ai-sidebar.js` and `build/ai-sidebar.css`
3. **Asset File**: `build/ai-sidebar.asset.php` (contains dependencies and version)

### Build Scripts

```bash
# Build AI module only
npm run build:ai

# Build all modules (including AI)
npm run build

# Watch mode for development
npm run start:ai
```

### Dependencies

The built JavaScript file automatically includes all WordPress dependencies:
- `@wordpress/plugins` - Plugin registration
- `@wordpress/edit-post` - PluginSidebar component
- `@wordpress/element` - React
- `@wordpress/components` - UI components
- `@wordpress/data` - State management
- `@wordpress/api-fetch` - REST API calls
- `@wordpress/i18n` - Internationalization

These are automatically detected by `wp-scripts` and included in the `ai-sidebar.asset.php` file.

## Components

### AiGeneratorPanel

Main component that manages the generation workflow:
- Displays generation buttons (Generate All, Text Only, Image Only)
- Handles API calls to generate content
- Displays preview of generated content
- Shows provider indicator and fallback notifications
- Displays error messages with retry functionality

**Props**: None (uses WordPress data store)

**State**:
- `isGenerating`: Boolean indicating if generation is in progress
- `generatedContent`: Object containing generated content
- `error`: Error message if generation failed
- `usedProvider`: Name of the provider used
- `isFallback`: Boolean indicating if fallback provider was used

### PreviewPanel

Component for previewing and editing generated content:
- Displays all generated fields with character counts
- Highlights fields exceeding recommended limits
- Allows editing of generated values before applying
- Shows generated image thumbnail
- Provides Apply and Cancel buttons

**Props**:
- `content`: Object containing generated content
- `onApply`: Callback when Apply button is clicked
- `onCancel`: Callback when Cancel button is clicked
- `isApplying`: Boolean indicating if applying is in progress
- `provider`: Name of the provider used

## Styling

### CSS Architecture

The module uses three CSS files for organization:

1. **ai-generator.css**: Sidebar plugin wrapper styles
2. **ai-generator-panel.css**: Main panel component styles
3. **preview-panel.css**: Preview panel component styles

All styles are scoped with `.meowseo-` prefix to avoid conflicts.

### Design System

- **Colors**:
  - Primary: `#0073aa` (WordPress blue)
  - Error: `#d63638` (WordPress red)
  - Warning: `#f0ad4e` (WordPress orange)
  - Background: `#fafafa` (Light gray)

- **Typography**:
  - Font: System font stack (Apple system fonts, Segoe UI, etc.)
  - Base size: 13px
  - Headings: 14px, 600 weight

- **Spacing**: 8px, 12px, 16px increments

## Integration with PHP

The AI module is enqueued by the PHP `AI_Module` class:

```php
// In includes/modules/ai/class-ai-module.php
private function enqueue_sidebar_assets(): void {
    wp_enqueue_script(
        'meowseo-ai-sidebar',
        MEOWSEO_URL . 'build/ai-sidebar.js',
        $asset_data['dependencies'],
        $asset_data['version'],
        true
    );

    wp_enqueue_style(
        'meowseo-ai-sidebar',
        MEOWSEO_URL . 'build/ai-sidebar.css',
        [],
        $asset_data['version']
    );

    wp_localize_script(
        'meowseo-ai-sidebar',
        'meowseoAiData',
        [
            'nonce'        => wp_create_nonce( 'wp_rest' ),
            'restUrl'      => rest_url( 'meowseo/v1' ),
            'postId'       => get_the_ID(),
            'postType'     => get_post_type(),
            'isConfigured' => $this->is_module_configured(),
            'settingsUrl'  => admin_url( 'admin.php?page=meowseo-settings&tab=ai' ),
        ]
    );
}
```

### Localized Data

The following data is passed to the JavaScript via `wp_localize_script`:

- `nonce`: WordPress REST API nonce for security
- `restUrl`: Base URL for REST API calls
- `postId`: Current post ID
- `postType`: Current post type
- `isConfigured`: Boolean indicating if module is configured
- `settingsUrl`: URL to AI settings page

## API Integration

The components communicate with the REST API endpoints:

### Generate Endpoint
```
POST /meowseo/v1/ai/generate
```

**Request**:
```javascript
{
    post_id: 123,
    type: 'all' | 'text' | 'image',
    generate_image: true,
    bypass_cache: false
}
```

**Response**:
```javascript
{
    success: true,
    data: {
        seo_title: "...",
        seo_description: "...",
        focus_keyword: "...",
        og_title: "...",
        og_description: "...",
        twitter_title: "...",
        twitter_description: "...",
        direct_answer: "...",
        schema_type: "...",
        image_url: "...",
        provider: "Gemini",
        is_fallback: false
    }
}
```

### Apply Endpoint
```
POST /meowseo/v1/ai/apply
```

**Request**:
```javascript
{
    post_id: 123,
    content: { /* generated content object */ }
}
```

**Response**:
```javascript
{
    success: true,
    message: "Content applied successfully"
}
```

## Development

### Local Development

```bash
# Start watching for changes
npm run start:ai

# This will rebuild the module whenever files change
```

### Testing

```bash
# Run tests
npm test

# Run tests in watch mode
npm run test:watch
```

### Linting

```bash
# Lint JavaScript
npm run lint:js

# Format JavaScript
npm run format:js

# Type checking
npm run type-check
```

## Requirements Coverage

This module implements the following requirements:

- **Requirement 7**: Gutenberg Sidebar Panel
  - 7.1: Sidebar panel displays in editor
  - 7.2: Generate All SEO button with loading state
  - 7.3: Provider indicator badge
  - 7.7: Loading spinner during generation
  - 7.8: Success message with provider name
  - 7.9: Error message with fallback notification

- **Requirement 8**: Preview Panel
  - 8.1: Display all generated fields
  - 8.2: Show field labels and values
  - 8.3: Display character counts
  - 8.4: Highlight fields exceeding limits
  - 8.5: Display generated image thumbnail
  - 8.6: Allow editing before applying
  - 8.7: Apply to Fields button

- **Requirement 9**: Partial Generation Options
  - 9.1: Text Only button
  - 9.2: Image Only button
  - 9.3: Skip image generation for Text Only
  - 9.4: Skip text generation for Image Only
  - 9.5: Appropriate loading messages

- **Requirement 10**: Fallback Notifications
  - 10.1: Display fallback notification
  - 10.2: Warning color styling
  - 10.3: Link to settings page
  - 10.4: Log fallback usage

- **Requirement 11**: Error Messages
  - 11.1: Display generation failed message
  - 11.2: Link to settings page
  - 11.3: Log provider errors
  - 11.4: Content too short message
  - 11.5: Permission denied message

## Accessibility

The components include accessibility features:

- ARIA labels on all buttons
- ARIA live regions for status messages
- Proper role attributes
- Keyboard navigation support
- Focus indicators
- Label associations for form fields

## Browser Support

The module supports all modern browsers that WordPress supports:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers

## Performance

- Lazy loading of preview panel
- Debounced API calls
- CSS-in-JS optimization
- Code splitting for large components
- Efficient re-renders using React.memo

## Troubleshooting

### Module not appearing in editor

1. Check that `enqueue_gutenberg_assets()` is being called
2. Verify `build/ai-sidebar.js` exists
3. Check browser console for JavaScript errors
4. Verify nonce is being passed correctly

### API calls failing

1. Check that REST API endpoints are registered
2. Verify nonce is valid
3. Check user capabilities
4. Review browser Network tab for error responses

### Styling issues

1. Verify `build/ai-sidebar.css` is being loaded
2. Check for CSS conflicts with other plugins
3. Review browser DevTools for CSS specificity issues
4. Clear browser cache

## Future Enhancements

- [ ] Batch generation for multiple posts
- [ ] Custom prompt templates
- [ ] Generation history/undo
- [ ] A/B testing different generations
- [ ] Integration with other SEO tools
- [ ] Advanced scheduling options
