/**
 * SERP Preview Component
 *
 * Displays a live preview of how content appears in Google search results.
 * Implements Sprint 2 requirements for real-time SERP preview with character counting.
 *
 * Requirements: 2.1, 2.2, 2.3, 2.12, 2.13, 2.14, 2.15, 2.16
 *
 * @package MeowSEO
 * @since 2.0.0
 */

import { useState, useEffect, memo, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

interface SERPPreviewProps {
	title: string;
	description: string;
	url: string;
	mode: 'desktop' | 'mobile';
	onModeChange: ( mode: 'desktop' | 'mobile' ) => void;
}

/**
 * Truncate text to specified length with ellipsis
 *
 * Requirements 2.15, 2.16: Truncate title at 60 chars, description at 155 chars
 *
 * @param text Text to truncate
 * @param maxLength Maximum length before truncation
 * @returns Truncated text with ellipsis if needed
 */
const truncateText = ( text: string, maxLength: number ): string => {
	if ( ! text || text.length <= maxLength ) {
		return text;
	}
	return text.substring( 0, maxLength ) + '...';
};

/**
 * SERP Preview Component
 *
 * Displays Google-style search result preview with:
 * - Blue clickable title (truncated at 60 chars)
 * - Green URL breadcrumb
 * - Gray description text (truncated at 155 chars)
 * - Mobile (360px) and Desktop (600px) width modes
 * - Mode toggle button
 *
 * Requirements:
 * - 2.1: Display Google-style search result card
 * - 2.2, 2.3: Real-time updates with 300ms debounce
 * - 2.12, 2.13, 2.14: Mobile/Desktop toggle with localStorage
 * - 2.15, 2.16: Text truncation with ellipsis
 */
const SERPPreview: React.FC< SERPPreviewProps > = memo(
	( { title, description, url, mode, onModeChange } ) => {
		const [ debouncedTitle, setDebouncedTitle ] = useState( title );
		const [ debouncedDescription, setDebouncedDescription ] =
			useState( description );

		// Requirement 2.2, 2.3: Debounced updates (300ms delay)
		useEffect( () => {
			const timeoutId = setTimeout( () => {
				setDebouncedTitle( title );
			}, 300 );

			return () => clearTimeout( timeoutId );
		}, [ title ] );

		useEffect( () => {
			const timeoutId = setTimeout( () => {
				setDebouncedDescription( description );
			}, 300 );

			return () => clearTimeout( timeoutId );
		}, [ description ] );

		// Requirement 2.12, 2.13, 2.14: Store mode preference in localStorage
		const handleModeChange = useCallback(
			( newMode: 'desktop' | 'mobile' ) => {
				onModeChange( newMode );
				try {
					localStorage.setItem( 'meowseo_serp_preview_mode', newMode );
				} catch ( error ) {
					// Silently fail if localStorage is not available
					console.warn(
						'MeowSEO: Could not save SERP preview mode to localStorage',
						error
					);
				}
			},
			[ onModeChange ]
		);

		// Requirements 2.15, 2.16: Truncate text with ellipsis
		const displayTitle = truncateText( debouncedTitle, 60 );
		const displayDescription = truncateText( debouncedDescription, 155 );

		// Format URL for display (remove protocol, truncate if needed)
		const displayUrl = url.replace( /^https?:\/\//, '' );

		return (
			<div className="meowseo-serp-preview">
				<div className="meowseo-serp-preview__header">
					<span className="meowseo-serp-preview__label">
						{ __( 'Search Preview', 'meowseo' ) }
					</span>
					{ /* Requirement 2.12: Mode toggle button */ }
					<div className="meowseo-serp-preview__toggle">
						<button
							type="button"
							className={ `meowseo-serp-preview__toggle-btn ${
								mode === 'desktop' ? 'is-active' : ''
							}` }
							onClick={ () => handleModeChange( 'desktop' ) }
							aria-pressed={ mode === 'desktop' }
						>
							{ __( 'Desktop', 'meowseo' ) }
						</button>
						<button
							type="button"
							className={ `meowseo-serp-preview__toggle-btn ${
								mode === 'mobile' ? 'is-active' : ''
							}` }
							onClick={ () => handleModeChange( 'mobile' ) }
							aria-pressed={ mode === 'mobile' }
						>
							{ __( 'Mobile', 'meowseo' ) }
						</button>
					</div>
				</div>

				{ /* Requirement 2.1: Google-style search result card */ }
				<div
					className={ `meowseo-serp-preview__content meowseo-serp-preview__content--${ mode }` }
				>
					{ /* Green URL breadcrumb */ }
					<div className="meowseo-serp-preview__url">
						{ displayUrl || __( 'example.com', 'meowseo' ) }
					</div>

					{ /* Blue clickable title */ }
					<div className="meowseo-serp-preview__title">
						{ displayTitle ||
							__( 'Enter a title...', 'meowseo' ) }
					</div>

					{ /* Gray description text */ }
					<div className="meowseo-serp-preview__description">
						{ displayDescription ||
							__( 'Enter a description...', 'meowseo' ) }
					</div>
				</div>
			</div>
		);
	}
);

SERPPreview.displayName = 'SERPPreview';

export default SERPPreview;
