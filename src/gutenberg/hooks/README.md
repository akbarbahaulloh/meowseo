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
