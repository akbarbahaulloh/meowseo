/**
 * Links Tab Component
 *
 * Internal link suggestions and link health data.
 * Fetches data from Internal Links Module REST API.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Spinner, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Links Tab Component
 */
export default function LinksTab() {
	const [ linkData, setLinkData ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	// Get current post ID
	const postId = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostId(),
		[]
	);

	/**
	 * Fetch link health data
	 */
	const fetchLinkData = async () => {
		setLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/meowseo/v1/internal-links?post_id=${ postId }`,
			} );
			setLinkData( response );
		} catch ( err ) {
			setError( err.message || __( 'Failed to load link data', 'meowseo' ) );
		} finally {
			setLoading( false );
		}
	};

	// Fetch data on mount
	useEffect( () => {
		if ( postId ) {
			fetchLinkData();
		}
	}, [ postId ] );

	/**
	 * Render loading state
	 */
	if ( loading ) {
		return (
			<div className="meowseo-links-tab meowseo-loading">
				<Spinner />
				<p>{ __( 'Loading link data...', 'meowseo' ) }</p>
			</div>
		);
	}

	/**
	 * Render error state
	 */
	if ( error ) {
		return (
			<div className="meowseo-links-tab meowseo-error">
				<p className="error">{ error }</p>
				<Button variant="secondary" onClick={ fetchLinkData }>
					{ __( 'Retry', 'meowseo' ) }
				</Button>
			</div>
		);
	}

	/**
	 * Render empty state
	 */
	if ( ! linkData || ( ! linkData.suggestions?.length && ! linkData.outbound?.length ) ) {
		return (
			<div className="meowseo-links-tab meowseo-empty">
				<p>{ __( 'No link data available yet.', 'meowseo' ) }</p>
				<p className="description">
					{ __( 'Link suggestions will appear after you publish this post.', 'meowseo' ) }
				</p>
			</div>
		);
	}

	return (
		<div className="meowseo-links-tab">
			{ /* Link Suggestions */ }
			{ linkData.suggestions && linkData.suggestions.length > 0 && (
				<div className="meowseo-link-section">
					<h3>{ __( 'Internal Link Suggestions', 'meowseo' ) }</h3>
					<p className="description">
						{ __( 'Related posts you might want to link to:', 'meowseo' ) }
					</p>
					<ul className="meowseo-link-suggestions">
						{ linkData.suggestions.map( ( suggestion ) => (
							<li key={ suggestion.id } className="meowseo-link-suggestion">
								<a
									href={ suggestion.edit_url }
									target="_blank"
									rel="noopener noreferrer"
								>
									{ suggestion.title }
								</a>
								{ suggestion.score && (
									<span className="meowseo-suggestion-score">
										{ __( 'Match:', 'meowseo' ) } { suggestion.score }%
									</span>
								) }
							</li>
						) ) }
					</ul>
				</div>
			) }

			{ /* Outbound Links */ }
			{ linkData.outbound && linkData.outbound.length > 0 && (
				<div className="meowseo-link-section">
					<h3>{ __( 'Outbound Links', 'meowseo' ) }</h3>
					<p className="description">
						{ __( 'Internal links from this post:', 'meowseo' ) }
					</p>
					<ul className="meowseo-link-list">
						{ linkData.outbound.map( ( link, index ) => (
							<li
								key={ index }
								className={ `meowseo-link-item meowseo-link-status-${ link.status }` }
							>
								<span className="meowseo-link-url">{ link.url }</span>
								{ link.anchor_text && (
									<span className="meowseo-link-anchor">
										{ __( 'Anchor:', 'meowseo' ) } { link.anchor_text }
									</span>
								) }
								{ link.http_status && (
									<span className="meowseo-link-http-status">
										{ __( 'Status:', 'meowseo' ) } { link.http_status }
									</span>
								) }
							</li>
						) ) }
					</ul>
				</div>
			) }

			<div className="meowseo-link-actions">
				<Button variant="secondary" onClick={ fetchLinkData }>
					{ __( 'Refresh', 'meowseo' ) }
				</Button>
			</div>
		</div>
	);
}
