/**
 * Content Sync Hook
 *
 * Single useEffect that subscribes to core/editor.
 * Reads post content, title, excerpt, slug.
 * Dispatches derived SEO signals to meowseo/data only.
 * Never dispatches back to core/editor from useEffect.
 *
 * @package
 * @since 1.0.0
 */

import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch, subscribe } from '@wordpress/data';
import { analyzeContent } from '../analysis/analysis-engine';

/**
 * Content Sync Hook
 *
 * Subscribes to core/editor changes and updates meowseo/data analysis.
 */
export function useContentSync() {
	const { setAnalysis } = useDispatch( 'meowseo/data' );
	const focusKeyword = useSelect(
		( select ) => select( 'meowseo/data' ).getMetaField( 'focusKeyword' ),
		[]
	);

	useEffect( () => {
		// Subscribe to core/editor changes
		const unsubscribe = subscribe( () => {
			try {
				const { select } = window.wp.data;

				// Read post data from core/editor (read-only)
				const content =
					select( 'core/editor' )?.getEditedPostContent?.() || '';
				const title =
					select( 'core/editor' )?.getEditedPostAttribute?.(
						'title'
					) || '';
				const excerpt =
					select( 'core/editor' )?.getEditedPostAttribute?.(
						'excerpt'
					) || '';
				const slug =
					select( 'core/editor' )?.getEditedPostAttribute?.(
						'slug'
					) || '';

				// Get current focus keyword from meowseo/data
				const keyword =
					select( 'meowseo/data' )?.getMetaField?.(
						'focusKeyword'
					) || '';

				// Get directAnswer and schemaType from meowseo/data
				const directAnswer =
					select( 'meowseo/data' )?.getMetaField?.(
						'directAnswer'
					) || '';
				const schemaType =
					select( 'meowseo/data' )?.getMetaField?.(
						'schemaType'
					) || '';

				// Compute analysis using new analysis engine
				const analysis = analyzeContent( {
					content,
					title,
					description: excerpt,
					slug,
					keyword,
					directAnswer,
					schemaType,
				} );

				// Dispatch to meowseo/data only (never to core/editor)
				// Map new analysis format to old setAnalysis action
				setAnalysis(
					analysis.seoScore,
					analysis.seoResults,
					analysis.readabilityScore,
					analysis.readabilityResults
				);
			} catch ( error ) {
				// Silently handle errors to avoid breaking the editor
				console.error( 'MeowSEO: Error in content sync hook', error );
			}
		} );

		// Cleanup subscription on unmount
		return unsubscribe;
	}, [ focusKeyword, setAnalysis ] );
}
