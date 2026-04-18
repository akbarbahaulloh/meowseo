/**
 * GSCIntegration Component
 *
 * Provides Google Search Console integration for requesting indexing.
 * Displays last submission timestamp and button to request indexing.
 *
 * Requirements: 14.7, 14.8
 */

import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';

/**
 * GSCIntegration Component
 *
 * Requirements:
 * - 14.7: Display last submission timestamp from _meowseo_gsc_last_submit
 * - 14.8: Display "Request Indexing" button and call Google Search Console API
 */
const GSCIntegration: React.FC = () => {
	// Get last submission timestamp
	// Requirement: 14.7
	const [ lastSubmit ] = useEntityPropBinding( '_meowseo_gsc_last_submit' );

	// Get current post ID and permalink
	const { postId, permalink } = useSelect( ( select: any ) => {
		try {
			const editorSelect = select( 'core/editor' );
			if ( ! editorSelect ) {
				return {
					postId: 0,
					permalink: '',
				};
			}
			return {
				postId: editorSelect.getCurrentPostId() || 0,
				permalink: editorSelect.getPermalink() || '',
			};
		} catch ( error ) {
			console.error( 'MeowSEO: Error reading post ID/permalink:', error );
			return {
				postId: 0,
				permalink: '',
			};
		}
	}, [] );

	// Local state for loading and error handling
	const [ isRequesting, setIsRequesting ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );
	const [ success, setSuccess ] = useState< string | null >( null );

	// Handle request indexing button click
	// Requirement: 14.8
	const handleRequestIndexing = async () => {
		setIsRequesting( true );
		setError( null );
		setSuccess( null );

		try {
			// Call GSC API endpoint
			// Note: This endpoint requires manage_options capability
			const response = ( await apiFetch( {
				path: `/meowseo/v1/gsc/request-indexing`,
				method: 'POST',
				data: {
					post_id: postId,
					url: permalink,
				},
			} ) ) as { success: boolean; message: string };

			if ( response.success ) {
				setSuccess(
					response.message ||
						__(
							'Indexing request submitted successfully',
							'meowseo'
						)
				);
			} else {
				setError(
					response.message ||
						__( 'Failed to submit indexing request', 'meowseo' )
				);
			}
		} catch ( err: any ) {
			console.error( 'GSC indexing request failed:', err );
			setError(
				err.message ||
					__( 'Failed to submit indexing request', 'meowseo' )
			);
		} finally {
			setIsRequesting( false );
		}
	};

	// Format last submission timestamp
	const formatTimestamp = ( timestamp: string ): string => {
		if ( ! timestamp ) {
			return __( 'Never', 'meowseo' );
		}

		try {
			const date = new Date( timestamp );
			return date.toLocaleString();
		} catch {
			return timestamp;
		}
	};

	return (
		<div className="meowseo-gsc-integration">
			<h3>{ __( 'Google Search Console', 'meowseo' ) }</h3>

			<div className="meowseo-gsc-last-submit">
				<label className="components-base-control__label">
					{ __( 'Last Indexing Request', 'meowseo' ) }
				</label>
				<div className="meowseo-gsc-timestamp">
					{ formatTimestamp( lastSubmit ) }
				</div>
			</div>

			<Button
				variant="secondary"
				onClick={ handleRequestIndexing }
				isBusy={ isRequesting }
				disabled={ isRequesting }
			>
				{ isRequesting
					? __( 'Requesting…', 'meowseo' )
					: __( 'Request Indexing', 'meowseo' ) }
			</Button>

			{ success && (
				<div className="meowseo-gsc-success notice notice-success">
					<p>{ success }</p>
				</div>
			) }

			{ error && (
				<div className="meowseo-gsc-error notice notice-error">
					<p>{ error }</p>
				</div>
			) }

			<p className="components-base-control__help">
				{ __(
					'Request Google to index or re-index this page. Requires manage_options capability.',
					'meowseo'
				) }
			</p>
		</div>
	);
};

export default GSCIntegration;
