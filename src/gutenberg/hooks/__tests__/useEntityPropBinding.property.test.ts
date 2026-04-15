/**
 * Property-Based Tests for useEntityPropBinding Hook
 * 
 * **Property 4: Postmeta persistence**
 * **Validates: Requirements 15.1, 15.2**
 * 
 * Tests that all postmeta updates use useEntityProp and persist correctly.
 */

import * as fc from 'fast-check';
import { renderHook } from '@testing-library/react';
import { useEntityPropBinding } from '../useEntityPropBinding';

// Mock WordPress dependencies
jest.mock('@wordpress/element', () => ({
  useCallback: jest.requireActual('react').useCallback,
}));

jest.mock('@wordpress/data', () => ({
  useSelect: jest.fn(),
}));

jest.mock('@wordpress/core-data', () => ({
  useEntityProp: jest.fn(),
}));

import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

describe('useEntityPropBinding - Property Tests', () => {
  let mockEditorSelect: any;
  let mockMeta: Record<string, string>;
  let mockSetMeta: jest.Mock;

  beforeEach(() => {
    jest.clearAllMocks();

    mockMeta = {};
    mockSetMeta = jest.fn((newMeta: Record<string, string>) => {
      // Simulate WordPress behavior: merge the new meta
      mockMeta = { ...mockMeta, ...newMeta };
    });

    mockEditorSelect = {
      getCurrentPostType: jest.fn(() => 'post'),
      getCurrentPostId: jest.fn(() => 123),
    };

    (useSelect as jest.Mock).mockImplementation((callback: any) => {
      const select = (storeName: string) => {
        if (storeName === 'core/editor') {
          return mockEditorSelect;
        }
        return {};
      };
      return callback(select);
    });

    (useEntityProp as jest.Mock).mockImplementation(() => {
      return [mockMeta, mockSetMeta];
    });
  });

  /**
   * Property 4: Postmeta Persistence
   * 
   * For any postmeta key-value pair, updating via useEntityPropBinding
   * should call useEntityProp's setMeta with the correct structure.
   */
  it('should persist all postmeta updates via useEntityProp', () => {
    fc.assert(
      fc.property(
        // Generate random postmeta key-value pairs
        fc.array(
          fc.record({
            key: fc.constantFrom(
              '_meowseo_title',
              '_meowseo_description',
              '_meowseo_focus_keyword',
              '_meowseo_direct_answer',
              '_meowseo_og_title',
              '_meowseo_og_description',
              '_meowseo_twitter_title',
              '_meowseo_canonical',
              '_meowseo_robots_noindex',
              '_meowseo_robots_nofollow'
            ),
            value: fc.string({ minLength: 0, maxLength: 200 }),
          }),
          { minLength: 1, maxLength: 10 }
        ),
        (metaUpdates) => {
          // Reset mocks for each iteration
          mockSetMeta.mockClear();
          mockMeta = {};

          // Track all updates
          const updateResults: boolean[] = [];

          metaUpdates.forEach((update) => {
            // Render the hook for this specific meta key
            const { result } = renderHook(() => useEntityPropBinding(update.key));

            // Get the setValue function
            const [, setValue] = result.current;

            // Update the value
            setValue(update.value);

            // Verify setMeta was called
            const setMetaCalled = mockSetMeta.mock.calls.length > 0;
            if (!setMetaCalled) {
              updateResults.push(false);
              return;
            }

            // Get the last call to setMeta
            const lastCall = mockSetMeta.mock.calls[mockSetMeta.mock.calls.length - 1];
            const updatedMeta = lastCall[0];

            // Verify the structure: should be { ...meta, [metaKey]: newValue }
            const hasCorrectKey = update.key in updatedMeta;
            const hasCorrectValue = updatedMeta[update.key] === update.value;

            updateResults.push(hasCorrectKey && hasCorrectValue);

            // Clear for next iteration
            mockSetMeta.mockClear();
          });

          // All updates should have succeeded
          return updateResults.every((result) => result === true);
        }
      ),
      {
        numRuns: 50,
        verbose: true,
      }
    );
  });

  /**
   * Property 4b: Value Retrieval Consistency
   * 
   * For any postmeta key, if a value exists in meta, it should be returned.
   * If it doesn't exist, an empty string should be returned.
   */
  it('should return correct value or empty string fallback', () => {
    fc.assert(
      fc.property(
        fc.constantFrom(
          '_meowseo_title',
          '_meowseo_description',
          '_meowseo_focus_keyword',
          '_meowseo_canonical'
        ),
        fc.option(fc.string({ minLength: 1, maxLength: 100 }), { nil: null }),
        (metaKey, metaValue) => {
          // Reset mocks
          mockMeta = {};
          mockSetMeta.mockClear();

          // Set up the meta object
          if (metaValue !== null) {
            mockMeta[metaKey] = metaValue;
          }

          // Render the hook
          const { result } = renderHook(() => useEntityPropBinding(metaKey));

          // Get the value
          const [value] = result.current;

          // Verify: should return the value if it exists, or empty string
          const expectedValue = metaValue !== null ? metaValue : '';
          return value === expectedValue;
        }
      ),
      {
        numRuns: 50,
        verbose: true,
      }
    );
  });

  /**
   * Property 4c: Idempotent Updates
   * 
   * Setting the same value multiple times should call setMeta each time
   * (WordPress handles deduplication).
   */
  it('should call setMeta for each update, even if value is the same', () => {
    fc.assert(
      fc.property(
        fc.constantFrom('_meowseo_title', '_meowseo_description'),
        fc.string({ minLength: 1, maxLength: 50 }),
        fc.integer({ min: 2, max: 5 }),
        (metaKey, value, numUpdates) => {
          // Reset mocks
          mockMeta = {};
          mockSetMeta.mockClear();

          // Render the hook
          const { result } = renderHook(() => useEntityPropBinding(metaKey));
          const [, setValue] = result.current;

          // Update the same value multiple times
          for (let i = 0; i < numUpdates; i++) {
            setValue(value);
          }

          // Verify setMeta was called numUpdates times
          return mockSetMeta.mock.calls.length === numUpdates;
        }
      ),
      {
        numRuns: 30,
        verbose: true,
      }
    );
  });

  /**
   * Property 4d: Meta Preservation
   * 
   * When updating one meta key, other existing meta keys should be preserved.
   */
  it('should preserve other meta keys when updating one key', () => {
    fc.assert(
      fc.property(
        fc.record({
          existingKey1: fc.constantFrom('_meowseo_title', '_meowseo_description'),
          existingValue1: fc.string({ minLength: 1, maxLength: 50 }),
          existingKey2: fc.constantFrom('_meowseo_focus_keyword', '_meowseo_canonical'),
          existingValue2: fc.string({ minLength: 1, maxLength: 50 }),
          updateKey: fc.constantFrom('_meowseo_og_title', '_meowseo_twitter_title'),
          updateValue: fc.string({ minLength: 1, maxLength: 50 }),
        }),
        (data) => {
          // Ensure keys are unique
          const keys = [data.existingKey1, data.existingKey2, data.updateKey];
          const uniqueKeys = new Set(keys);
          if (uniqueKeys.size !== keys.length) {
            // Skip this test case if keys are not unique
            return true;
          }

          // Reset mocks
          mockMeta = {
            [data.existingKey1]: data.existingValue1,
            [data.existingKey2]: data.existingValue2,
          };
          mockSetMeta.mockClear();

          // Render the hook for the update key
          const { result } = renderHook(() => useEntityPropBinding(data.updateKey));
          const [, setValue] = result.current;

          // Update the value
          setValue(data.updateValue);

          // Get the updated meta from the last setMeta call
          const lastCall = mockSetMeta.mock.calls[mockSetMeta.mock.calls.length - 1];
          const updatedMeta = lastCall[0];

          // Verify all three keys are present
          const hasExistingKey1 = updatedMeta[data.existingKey1] === data.existingValue1;
          const hasExistingKey2 = updatedMeta[data.existingKey2] === data.existingValue2;
          const hasUpdateKey = updatedMeta[data.updateKey] === data.updateValue;

          return hasExistingKey1 && hasExistingKey2 && hasUpdateKey;
        }
      ),
      {
        numRuns: 40,
        verbose: true,
      }
    );
  });

  /**
   * Property 4e: Null/Undefined Handling
   * 
   * If meta is null or undefined, or if a specific key doesn't exist,
   * the hook should return an empty string.
   */
  it('should handle null/undefined meta with empty string fallback', () => {
    fc.assert(
      fc.property(
        fc.constantFrom('_meowseo_title', '_meowseo_description', '_meowseo_focus_keyword'),
        fc.constantFrom(null, undefined, {}),
        (metaKey, metaValue) => {
          // Reset mocks
          mockMeta = metaValue as any;
          mockSetMeta.mockClear();

          (useEntityProp as jest.Mock).mockImplementation(() => {
            return [metaValue, mockSetMeta];
          });

          // Render the hook
          const { result } = renderHook(() => useEntityPropBinding(metaKey));
          const [value] = result.current;

          // Should always return empty string for null/undefined/missing keys
          return value === '';
        }
      ),
      {
        numRuns: 30,
        verbose: true,
      }
    );
  });
});
