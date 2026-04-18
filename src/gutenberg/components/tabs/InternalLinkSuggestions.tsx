/**
 * InternalLinkSuggestions Component
 *
 * Displays internal link suggestions based on the focus keyword.
 * Implements 3-second debounce and fetches suggestions from REST API.
 *
 * Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 11.8, 17.2
 */

import { useState, useEffect } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';

interface LinkSuggestion {
	post_id: number;
	title: string;
	url: string;
	relevance_score: number;
}

/**
 * InternalLinkSuggestions Component
 *
 * Requirements:
 * - 11.1: Display internal link suggestions component
 * - 11.2: Fetch link suggestions after 3 seconds when focus keyword changes
 * - 11.3: Skip fetch if focus keyword < 3 characters
 * - 11.4: Call /meowseo/v1/internal-links/suggestions REST endpoint
 * - 11.5: Send post ID, focus keyword, and limit of 5 in request
 * - 11.6: Display loading indicator during fetch
 * - 11.7: Handle API errors gracefully
 * - 11.8: Display post title, URL, and relevance score for each suggestion
 * - 17.2: Return empty array and log error if API call fails
 */
const InternalLinkSuggestions: React.FC = () => {
	const [ suggestions, setSuggestions ] = useState< LinkSuggestion[] >( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	// Get focus keyword from postmeta
	const [ focusKeyword ] = useEntityPropBinding( '_meowseo_focus_keyword' );

	// Get current post ID
	const postId = useSelect( ( select: any ) => {
		try {
			const editorSelect = select( 'core/editor' );
			if ( ! editorSelect ) {
				return 0;
			}
			return editorSelect.getCurrentPostId() || 0;
		} catch ( error ) {
			console.error( 'MeowSEO: Error reading post ID:', error );
			return 0;
		}
	}, [] );

	// Requirement 11.2: Implement 3-second debounce for focus keyword changes
	// Requirement 11.3: Skip fetch if keyword < 3 characters
	useEffect( () => {
		// Reset state
		setError( null );

		// Skip if keyword is too short
		if ( ! focusKeyword || focusKeyword.length < 3 ) {
			setSuggestions( [] );
			setIsLoading( false );
			return;
		}

		setIsLoading( true );

		const timeoutId = setTimeout( async () => {
			try {
				// Requirement 11.4: Call /meowseo/v1/internal-links/suggestions REST endpoint
				// Requirement 11.5: Send post ID, focus keyword, and limit of 5
				const response = await apiFetch< {
					suggestions: LinkSuggestion[];
				} >( {
					path: '/meowseo/v1/internal-links/suggestions',
					method: 'POST',
					data: {
						post_id: postId,
						keyword: focusKeyword,
						limit: 5,
					},
				} );

				setSuggestions( response.suggestions || [] );
				setIsLoading( false );
			} catch ( err ) {
				// Requirement 11.7, 17.2: Handle API errors gracefully
				console.error(
					'Failed to fetch internal link suggestions:',
					err
				);
				setSuggestions( [] );
				setError(
					__(
						'Unable to load link suggestions. Please try again later.',
						'meowseo'
					)
				);
				setIsLoading( false );
			}
		}, 3000 ); // 3-second debounce

		return () => {
			clearTimeout( timeoutId );
			setIsLoading( false );
		};
	}, [ focusKeyword, postId ] );

	// Don't render if no focus keyword
	if ( ! focusKeyword || focusKeyword.length < 3 ) {
		return null;
	}

	return (
		<div className="meowseo-internal-links">
			<h3 className="meowseo-internal-links-title">
				{ __( 'Internal Link Suggestions', 'meowseo' ) }
			</h3>

			{ /* Requirement 11.6: Display loading indicator during fetch */ }
			{ isLoading && (
				<div className="meowseo-internal-links-loading">
					<Spinner />
					<span>{ __( 'Loading suggestions…', 'meowseo' ) }</span>
				</div>
			) }

			{ /* Display error message */ }
			{ error && ! isLoading && (
				<div className="meowseo-internal-links-error">{ error }</div>
			) }

			{ /* Requirement 11.8: Display suggestions with title, URL, and relevance score */ }
			{ ! isLoading && ! error && suggestions.length > 0 && (
				<ul className="meowseo-internal-links-list">
					{ suggestions.map( ( suggestion ) => (
						<li
							key={ suggestion.post_id }
							className="meowseo-internal-link-item"
						>
							<div className="meowseo-internal-link-content">
								<a
									href={ suggestion.url }
									target="_blank"
									rel="noopener noreferrer"
									className="meowseo-internal-link-title"
								>
									{ suggestion.title }
								</a>
								<div className="meowseo-internal-link-url">
									{ suggestion.url }
								</div>
							</div>
							<div className="meowseo-internal-link-score">
								<span className="meowseo-internal-link-score-label">
									{ __( 'Relevance:', 'meowseo' ) }
								</span>
								<span className="meowseo-internal-link-score-value">
									{ Math.round(
										suggestion.relevance_score * 100
									) }
									%
								</span>
							</div>
						</li>
					) ) }
				</ul>
			) }

			{ ! isLoading && ! error && suggestions.length === 0 && (
				<div className="meowseo-internal-links-empty">
					{ __(
						'No internal link suggestions found for this keyword.',
						'meowseo'
					) }
				</div>
			) }
		</div>
	);
};

export default InternalLinkSuggestions;
