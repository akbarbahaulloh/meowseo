/**
 * useEntityPropBinding Hook
 * 
 * Utility hook that wraps WordPress's useEntityProp for seamless postmeta operations.
 * This hook provides a simple interface for reading and writing postmeta values
 * with automatic WordPress auto-save integration.
 * 
 * Requirements: 15.1, 15.2, 15.11, 17.3
 */

import { useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Hook for binding to a postmeta field with automatic persistence
 * 
 * @param metaKey - The postmeta key to bind to (e.g., '_meowseo_focus_keyword')
 * @returns Tuple of [value, setValue] where value is the current postmeta value
 *          and setValue is a function to update the postmeta
 * 
 * Preconditions:
 * - metaKey is valid postmeta key string
 * - core/editor store is available
 * - Post is being edited (postId > 0)
 * - useEntityProp hook is available from @wordpress/core-data
 * 
 * Postconditions:
 * - Returns tuple [value, setValue]
 * - value is current postmeta value or empty string
 * - setValue updates postmeta and triggers auto-save
 * - No direct database queries (handled by WordPress)
 */
export function useEntityPropBinding(
  metaKey: string
): [string, (value: string) => void] {
  // Get current post type and ID from core/editor
  const { postType, postId } = useSelect((select: any) => {
    const editorSelect = select('core/editor');
    return {
      postType: editorSelect.getCurrentPostType(),
      postId: editorSelect.getCurrentPostId(),
    };
  }, []);
  
  // Use WordPress's useEntityProp for automatic persistence
  const [meta, setMeta] = useEntityProp(
    'postType',
    postType,
    'meta',
    postId
  );
  
  // Extract the specific meta value, fallback to empty string
  // Requirements: 15.11, 17.3 - Handle null/undefined with empty string fallback
  const value = meta?.[metaKey] || '';
  
  // Create setter that updates the specific meta key
  // Requirements: 15.1, 15.2 - Use Entity_Prop for postmeta operations and trigger auto-save
  const setValue = useCallback(
    (newValue: string) => {
      setMeta({ ...meta, [metaKey]: newValue });
    },
    [meta, metaKey, setMeta]
  );
  
  return [value, setValue];
}
