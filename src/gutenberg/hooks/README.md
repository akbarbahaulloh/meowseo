# Gutenberg Hooks

This directory contains custom React hooks for the MeowSEO Gutenberg integration.

## useContentSync

**Location:** `useContentSync.ts`

### Purpose

The `useContentSync` hook is the **ONLY** hook allowed to read from the `core/editor` store. This is a critical architectural constraint that prevents performance issues caused by scattered subscriptions to the editor store.

### How It Works

1. **Reads from core/editor**: Subscribes to title, content, excerpt, postType, and permalink
2. **Debounces updates**: Waits 800ms after the last change before dispatching
3. **Dispatches to meowseo/data**: Updates the Redux store with the content snapshot
4. **Cleans up on unmount**: Clears the timeout to prevent memory leaks

### Usage

```typescript
import { useContentSync } from './hooks';

function Sidebar() {
  // This is the ONLY place we read from core/editor
  useContentSync();
  
  // All other components read from meowseo/data store
  const { contentSnapshot } = useSelect((select) => ({
    contentSnapshot: select('meowseo/data').getContentSnapshot(),
  }));
  
  return <div>...</div>;
}
```

### Architecture Benefits

- **Performance**: 800ms debounce prevents excessive re-renders
- **Centralization**: Single point of content synchronization
- **Predictability**: All content data flows through one hook
- **Testability**: Easy to test debounce behavior in isolation

### Testing

The hook has comprehensive test coverage:

- **Unit tests** (`useContentSync.test.ts`): 7 tests covering basic functionality
- **Property tests** (`useContentSync.property.test.ts`): 3 property-based tests validating debounce guarantees

All tests use Jest with fake timers to verify debounce behavior without waiting for real time to pass.

### Requirements Validated

- **2.1**: Hook reads from core/editor
- **2.2**: Reads title, content, excerpt, postType, permalink
- **2.3**: Implements 800ms debounce
- **2.4**: Resets timer on new changes
- **2.5**: Dispatches updateContentSnapshot action
- **16.1**: Prevents keystroke re-renders
- **16.2**: Optimizes performance with debouncing

## useEntityPropBinding

**Location:** `useEntityPropBinding.ts`

### Purpose

The `useEntityPropBinding` hook is a utility that wraps WordPress's `useEntityProp` for seamless postmeta operations. It provides a simple interface for reading and writing postmeta values with automatic WordPress auto-save integration.

### How It Works

1. **Gets post context**: Retrieves postType and postId from core/editor
2. **Uses useEntityProp**: Leverages WordPress's built-in hook for postmeta operations
3. **Returns tuple**: Provides [value, setValue] interface similar to useState
4. **Handles null/undefined**: Falls back to empty string for missing values
5. **Triggers auto-save**: WordPress automatically saves when setValue is called

### Usage

```typescript
import { useEntityPropBinding } from './hooks';

function FocusKeywordInput() {
  const [focusKeyword, setFocusKeyword] = useEntityPropBinding('_meowseo_focus_keyword');
  
  return (
    <TextControl
      label="Focus Keyword"
      value={focusKeyword}
      onChange={setFocusKeyword}
      help="Enter the main keyword for this content"
    />
  );
}
```

### Supported Postmeta Keys

The hook works with any postmeta key, but is designed for MeowSEO keys:

- `_meowseo_title` - SEO title
- `_meowseo_description` - Meta description
- `_meowseo_focus_keyword` - Focus keyword
- `_meowseo_direct_answer` - Direct answer
- `_meowseo_og_title` - Open Graph title
- `_meowseo_og_description` - Open Graph description
- `_meowseo_twitter_title` - Twitter title
- `_meowseo_canonical` - Canonical URL
- `_meowseo_robots_noindex` - Noindex directive
- `_meowseo_robots_nofollow` - Nofollow directive

### Architecture Benefits

- **Automatic persistence**: No manual save logic needed
- **WordPress integration**: Uses native useEntityProp for compatibility
- **Type safety**: Returns string values with empty string fallback
- **Auto-save**: WordPress handles save timing automatically
- **Consistent interface**: Same pattern as React's useState

### Testing

The hook has comprehensive test coverage:

- **Unit tests** (`useEntityPropBinding.test.ts`): 14 tests covering functionality
- **Property tests** (`useEntityPropBinding.property.test.ts`): 5 property-based tests validating persistence

All tests mock WordPress dependencies and verify correct useEntityProp usage.

### Requirements Validated

- **15.1**: Uses Entity_Prop for all postmeta operations
- **15.2**: Triggers WordPress auto-save on updates
- **15.11**: Uses empty string default for missing keys
- **17.3**: Handles null/undefined with empty string fallback

## Adding New Hooks

When adding new hooks to this directory:

1. Create the hook file (e.g., `useMyHook.ts`)
2. Export it from `index.ts`
3. Add unit tests in `__tests__/useMyHook.test.ts`
4. Add property tests if applicable in `__tests__/useMyHook.property.test.ts`
5. Update this README with documentation

## Testing Guidelines

- Use `jest.useFakeTimers()` for testing debounce behavior
- Mock WordPress dependencies (`@wordpress/data`, `@wordpress/element`)
- Use `@testing-library/react` for rendering hooks
- Use `fast-check` for property-based tests
- Aim for 100% code coverage on hooks
