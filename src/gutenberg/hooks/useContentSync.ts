/**
 * useContentSync Hook
 *
 * The ONLY hook allowed to read from core/editor.
 * Implements 800ms debounce to prevent excessive updates.
 *
 * This is a critical architectural constraint: NO other component
 * should read from core/editor. All content data flows through
 * this hook into the meowseo/data store.
 */

import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { updateContentSnapshot } from '../store/actions';

export function useContentSync(): void {
	const dispatch = useDispatch( 'meowseo/data' );

	// Read from core/editor (ONLY place this is allowed)
	const contentData = useSelect( ( select: any ) => {
		try {
			const editorSelect = select( 'core/editor' );

			// Check if editor is available
			if ( ! editorSelect ) {
				console.warn( 'MeowSEO: core/editor store not available' );
				return {
					title: '',
					content: '',
					excerpt: '',
					postType: '',
					permalink: '',
				};
			}

			return {
				title: editorSelect.getEditedPostAttribute( 'title' ) || '',
				content: editorSelect.getEditedPostAttribute( 'content' ) || '',
				excerpt: editorSelect.getEditedPostAttribute( 'excerpt' ) || '',
				postType: editorSelect.getCurrentPostType() || '',
				permalink: editorSelect.getPermalink() || '',
			};
		} catch ( error ) {
			// Requirement 17.5: Log error to console
			console.error( 'MeowSEO: Error reading from core/editor:', error );
			// Requirement 17.3: Fallback to empty values
			return {
				title: '',
				content: '',
				excerpt: '',
				postType: '',
				permalink: '',
			};
		}
	}, [] );

	// 800ms debounce
	useEffect( () => {
		const timeoutId = setTimeout( () => {
			dispatch(
				updateContentSnapshot( {
					title: contentData.title,
					content: contentData.content,
					excerpt: contentData.excerpt,
					focusKeyword: '', // focusKeyword is managed separately via postmeta
					postType: contentData.postType,
					permalink: contentData.permalink,
				} )
			);
		}, 800 );

		return () => clearTimeout( timeoutId );
	}, [
		contentData.title,
		contentData.content,
		contentData.excerpt,
		contentData.postType,
		contentData.permalink,
		dispatch,
	] );
}
