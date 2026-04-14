/**
 * GSC Tab Component
 *
 * Google Search Console performance data.
 * Fetches data from GSC Module REST API.
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
 * GSC Tab Component
 */
export default function GscTab() {
	const [ gscData, setGscData ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	// Get current post permalink
	const postUrl = useSelect(
		( select ) => {
			const postId = select( 'core/editor' ).getCurrentPostId();
			return select( 'core/editor' ).getPermalink();
		},
		[]
	);

	/**
	 * Fetch GSC performance data
	 */
	const fetchGscData = async () => {
		if ( ! postUrl ) {
			return;
		}

		setLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/meowseo/v1/gsc?url=${ encodeURIComponent( postUrl ) }`,
			} );
			setGscData( response );
		} catch ( err ) {
			setError( err.message || __( 'Failed to load GSC data', 'meowseo' ) );
		} finally {
			setLoading( false );
		}
	};

	// Fetch data on mount
	useEffect( () => {
		if ( postUrl ) {
			fetchGscData();
		}
	}, [ postUrl ] );

	/**
	 * Render loading state
	 */
	if ( loading ) {
		return (
			<div className="meowseo-gsc-tab meowseo-loading">
				<Spinner />
				<p>{ __( 'Loading GSC data...', 'meowseo' ) }</p>
			</div>
		);
	}

	/**
	 * Render error state
	 */
	if ( error ) {
		return (
			<div className="meowseo-gsc-tab meowseo-error">
				<p className="error">{ error }</p>
				<Button variant="secondary" onClick={ fetchGscData }>
					{ __( 'Retry', 'meowseo' ) }
				</Button>
			</div>
		);
	}

	/**
	 * Render empty state
	 */
	if ( ! gscData || ! gscData.data || gscData.data.length === 0 ) {
		return (
			<div className="meowseo-gsc-tab meowseo-empty">
				<p>{ __( 'No GSC data available yet.', 'meowseo' ) }</p>
				<p className="description">
					{ __(
						'Performance data will appear after this post is indexed by Google and receives impressions.',
						'meowseo'
					) }
				</p>
			</div>
		);
	}

	// Calculate totals
	const totals = gscData.data.reduce(
		( acc, row ) => {
			acc.clicks += row.clicks || 0;
			acc.impressions += row.impressions || 0;
			return acc;
		},
		{ clicks: 0, impressions: 0 }
	);

	const avgCtr = totals.impressions > 0 ? ( totals.clicks / totals.impressions ) * 100 : 0;
	const avgPosition =
		gscData.data.reduce( ( sum, row ) => sum + ( row.position || 0 ), 0 ) / gscData.data.length;

	return (
		<div className="meowseo-gsc-tab">
			<h3>{ __( 'Search Performance', 'meowseo' ) }</h3>
			<p className="description">
				{ __( 'Google Search Console data for this post:', 'meowseo' ) }
			</p>

			{ /* Performance Summary */ }
			<div className="meowseo-gsc-summary">
				<div className="meowseo-gsc-metric">
					<div className="meowseo-gsc-metric-label">{ __( 'Total Clicks', 'meowseo' ) }</div>
					<div className="meowseo-gsc-metric-value">{ totals.clicks.toLocaleString() }</div>
				</div>

				<div className="meowseo-gsc-metric">
					<div className="meowseo-gsc-metric-label">{ __( 'Total Impressions', 'meowseo' ) }</div>
					<div className="meowseo-gsc-metric-value">{ totals.impressions.toLocaleString() }</div>
				</div>

				<div className="meowseo-gsc-metric">
					<div className="meowseo-gsc-metric-label">{ __( 'Average CTR', 'meowseo' ) }</div>
					<div className="meowseo-gsc-metric-value">{ avgCtr.toFixed( 2 ) }%</div>
				</div>

				<div className="meowseo-gsc-metric">
					<div className="meowseo-gsc-metric-label">{ __( 'Average Position', 'meowseo' ) }</div>
					<div className="meowseo-gsc-metric-value">{ avgPosition.toFixed( 1 ) }</div>
				</div>
			</div>

			{ /* Recent Data */ }
			{ gscData.data.length > 0 && (
				<div className="meowseo-gsc-recent">
					<h4>{ __( 'Recent Performance', 'meowseo' ) }</h4>
					<table className="meowseo-gsc-table">
						<thead>
							<tr>
								<th>{ __( 'Date', 'meowseo' ) }</th>
								<th>{ __( 'Clicks', 'meowseo' ) }</th>
								<th>{ __( 'Impressions', 'meowseo' ) }</th>
								<th>{ __( 'CTR', 'meowseo' ) }</th>
								<th>{ __( 'Position', 'meowseo' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ gscData.data.slice( 0, 10 ).map( ( row, index ) => (
								<tr key={ index }>
									<td>{ row.date }</td>
									<td>{ row.clicks }</td>
									<td>{ row.impressions }</td>
									<td>{ ( row.ctr * 100 ).toFixed( 2 ) }%</td>
									<td>{ row.position.toFixed( 1 ) }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }

			<div className="meowseo-gsc-actions">
				<Button variant="secondary" onClick={ fetchGscData }>
					{ __( 'Refresh', 'meowseo' ) }
				</Button>
			</div>
		</div>
	);
}
