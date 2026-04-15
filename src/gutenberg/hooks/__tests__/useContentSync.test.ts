/**
 * Unit Tests for useContentSync Hook
 * 
 * Tests that the hook:
 * - Reads from core/editor
 * - Implements 800ms debounce timer
 * - Cleans up timeout on unmount
 * 
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5
 */

import { renderHook } from '@testing-library/react';
import { useContentSync } from '../useContentSync';

// Mock WordPress dependencies
jest.mock('@wordpress/element', () => ({
  useEffect: jest.requireActual('react').useEffect,
}));

jest.mock('@wordpress/data', () => ({
  useSelect: jest.fn(),
  useDispatch: jest.fn(),
}));

import { useSelect, useDispatch } from '@wordpress/data';

describe('useContentSync - Unit Tests', () => {
  let mockDispatch: jest.Mock;
  let mockUpdateContentSnapshot: jest.Mock;
  let mockEditorSelect: any;

  beforeEach(() => {
    jest.clearAllMocks();
    jest.useFakeTimers();

    mockUpdateContentSnapshot = jest.fn();
    mockDispatch = jest.fn(() => mockUpdateContentSnapshot);

    (useDispatch as jest.Mock).mockReturnValue(mockUpdateContentSnapshot);

    mockEditorSelect = {
      getEditedPostAttribute: jest.fn((attr: string) => {
        const defaults: Record<string, string> = {
          title: 'Test Title',
          content: 'Test Content',
          excerpt: 'Test Excerpt',
        };
        return defaults[attr] || '';
      }),
      getCurrentPostType: jest.fn(() => 'post'),
      getPermalink: jest.fn(() => 'https://example.com/test'),
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
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  /**
   * Test: Hook reads from core/editor
   * Requirements: 2.1, 2.2
   */
  it('should read title, content, excerpt, postType, and permalink from core/editor', () => {
    renderHook(() => useContentSync());

    // Verify that the hook called the correct core/editor methods
    expect(mockEditorSelect.getEditedPostAttribute).toHaveBeenCalledWith('title');
    expect(mockEditorSelect.getEditedPostAttribute).toHaveBeenCalledWith('content');
    expect(mockEditorSelect.getEditedPostAttribute).toHaveBeenCalledWith('excerpt');
    expect(mockEditorSelect.getCurrentPostType).toHaveBeenCalled();
    expect(mockEditorSelect.getPermalink).toHaveBeenCalled();
  });

  /**
   * Test: 800ms debounce timer
   * Requirements: 2.3, 2.4
   */
  it('should debounce updates by 800ms', () => {
    renderHook(() => useContentSync());

    // Initially, no dispatch should have occurred
    expect(mockUpdateContentSnapshot).not.toHaveBeenCalled();

    // Advance time by 799ms (just before debounce completes)
    jest.advanceTimersByTime(799);
    expect(mockUpdateContentSnapshot).not.toHaveBeenCalled();

    // Advance time by 1ms more (total 800ms)
    jest.advanceTimersByTime(1);
    expect(mockUpdateContentSnapshot).toHaveBeenCalledTimes(1);
  });

  /**
   * Test: Dispatch with correct content snapshot
   * Requirements: 2.5
   */
  it('should dispatch updateContentSnapshot with correct data', () => {
    renderHook(() => useContentSync());

    // Advance time to trigger dispatch
    jest.advanceTimersByTime(800);

    expect(mockUpdateContentSnapshot).toHaveBeenCalledWith({
      type: 'UPDATE_CONTENT_SNAPSHOT',
      payload: {
        title: 'Test Title',
        content: 'Test Content',
        excerpt: 'Test Excerpt',
        focusKeyword: '', // focusKeyword is managed separately
        postType: 'post',
        permalink: 'https://example.com/test',
      },
    });
  });

  /**
   * Test: Cleanup on unmount
   * Requirements: 2.4
   */
  it('should clean up timeout on unmount', () => {
    const { unmount } = renderHook(() => useContentSync());

    // Unmount before debounce completes
    unmount();

    // Advance time past debounce
    jest.advanceTimersByTime(800);

    // Dispatch should not have been called
    expect(mockUpdateContentSnapshot).not.toHaveBeenCalled();
  });

  /**
   * Test: Timer resets on content change
   * Requirements: 2.4
   */
  it('should reset timer when content changes', () => {
    const { rerender } = renderHook(() => useContentSync());

    // Advance time by 500ms
    jest.advanceTimersByTime(500);

    // Change content
    mockEditorSelect.getEditedPostAttribute.mockImplementation((attr: string) => {
      if (attr === 'title') return 'New Title';
      if (attr === 'content') return 'Test Content';
      if (attr === 'excerpt') return 'Test Excerpt';
      return '';
    });

    // Trigger re-render
    rerender();

    // Advance time by another 500ms (total 1000ms from start)
    jest.advanceTimersByTime(500);

    // Should not have dispatched yet (timer was reset)
    expect(mockUpdateContentSnapshot).not.toHaveBeenCalled();

    // Advance time by 300ms more (800ms from last change)
    jest.advanceTimersByTime(300);

    // Now it should have dispatched
    expect(mockUpdateContentSnapshot).toHaveBeenCalledTimes(1);
    expect(mockUpdateContentSnapshot).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'UPDATE_CONTENT_SNAPSHOT',
        payload: expect.objectContaining({
          title: 'New Title',
        }),
      })
    );
  });

  /**
   * Test: Handle null/undefined values from core/editor
   * Requirements: 2.2
   */
  it('should handle null/undefined values with empty strings', () => {
    mockEditorSelect.getEditedPostAttribute.mockReturnValue(null);
    mockEditorSelect.getCurrentPostType.mockReturnValue(undefined);
    mockEditorSelect.getPermalink.mockReturnValue(null);

    renderHook(() => useContentSync());

    jest.advanceTimersByTime(800);

    expect(mockUpdateContentSnapshot).toHaveBeenCalledWith({
      type: 'UPDATE_CONTENT_SNAPSHOT',
      payload: {
        title: '',
        content: '',
        excerpt: '',
        focusKeyword: '',
        postType: '',
        permalink: '',
      },
    });
  });

  /**
   * Test: Multiple rapid changes result in single dispatch
   * Requirements: 2.3, 2.4
   */
  it('should dispatch only once for multiple rapid changes', () => {
    const { rerender } = renderHook(() => useContentSync());

    // Make 5 rapid changes
    for (let i = 0; i < 5; i++) {
      mockEditorSelect.getEditedPostAttribute.mockImplementation((attr: string) => {
        if (attr === 'title') return `Title ${i}`;
        return '';
      });
      rerender();
      jest.advanceTimersByTime(100); // 100ms between changes
    }

    // Should not have dispatched yet
    expect(mockUpdateContentSnapshot).not.toHaveBeenCalled();

    // Wait for debounce to complete
    jest.advanceTimersByTime(800);

    // Should have dispatched exactly once with the last value
    expect(mockUpdateContentSnapshot).toHaveBeenCalledTimes(1);
    expect(mockUpdateContentSnapshot).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'UPDATE_CONTENT_SNAPSHOT',
        payload: expect.objectContaining({
          title: 'Title 4', // Last change (index 4)
        }),
      })
    );
  });
});
