/**
 * GSC Dashboard Component
 */

import { useState, useEffect } from '@wordpress/element';
import { 
	Panel, 
	PanelBody, 
	PanelRow, 
	Spinner, 
	Placeholder, 
	Button,
	Dashicon
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function GSCDashboard() {
	const [ loading, setLoading ] = useState( true );
	const [ status, setStatus ] = useState( null );
	const [ data, setData ] = useState( [] );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		fetchStatus();
		fetchData();
	}, [] );

	const fetchStatus = async () => {
		try {
			const result = await apiFetch( { path: '/meowseo/v1/gsc/status' } );
			setStatus( result );
		} catch ( err ) {
			console.error( 'Error fetching GSC status', err );
		}
	};

	const fetchData = async () => {
		setLoading( true );
		try {
			const result = await apiFetch( { path: '/meowseo/v1/gsc/data' } );
			setData( result.data || [] );
			setLoading( false );
		} catch ( err ) {
			setError( err.message || __( 'Failed to fetch data', 'meowseo' ) );
			setLoading( false );
		}
	};

	if ( loading && ! status ) {
		return (
			<div className="meowseo-gsc-loading">
				<Spinner />
				<p>{ __( 'Connecting to Google Search Console...', 'meowseo' ) }</p>
			</div>
		);
	}

	if ( status && ! status.connected ) {
		return (
			<Placeholder
				icon="google"
				label={ __( 'Google Search Console Not Connected', 'meowseo' ) }
				instructions={ __( 'Connect your site to Google Search Console to see performance data and enable automatic indexing.', 'meowseo' ) }
			>
				<Button 
					variant="primary" 
					onClick={ () => window.location.href = meowseoAdmin.restUrl + '/gsc/auth/start' }
				>
					{ __( 'Connect Google Search Console', 'meowseo' ) }
				</Button>
			</Placeholder>
		);
	}

	return (
		<div className="meowseo-gsc-dashboard">
			<div className="meowseo-gsc-header">
				<div className="meowseo-gsc-status">
					<span className="meowseo-status-badge success">
						<Dashicon icon="yes" /> { __( 'Connected', 'meowseo' ) }
					</span>
				</div>
				<Button isSecondary onClick={ fetchData }>
					<Dashicon icon="update" /> { __( 'Refresh Data', 'meowseo' ) }
				</Button>
			</div>

			<div className="meowseo-gsc-stats-grid">
				<StatCard 
					label={ __( 'Total Clicks', 'meowseo' ) } 
					value={ data.reduce( ( sum, row ) => sum + parseInt( row.clicks ), 0 ) } 
					icon="pointer-right"
				/>
				<StatCard 
					label={ __( 'Total Impressions', 'meowseo' ) } 
					value={ data.reduce( ( sum, row ) => sum + parseInt( row.impressions ), 0 ) } 
					icon="visibility"
				/>
				<StatCard 
					label={ __( 'Avg. CTR', 'meowseo' ) } 
					value={ ( data.length ? ( data.reduce( ( sum, row ) => sum + parseFloat( row.ctr ), 0 ) / data.length * 100 ).toFixed( 2 ) : 0 ) + '%' } 
					icon="chart-line"
				/>
				<StatCard 
					label={ __( 'Avg. Position', 'meowseo' ) } 
					value={ data.length ? ( data.reduce( ( sum, row ) => sum + parseFloat( row.position ), 0 ) / data.length ).toFixed( 1 ) : 0 } 
					icon="performance"
				/>
			</div>

			<Panel>
				<PanelBody title={ __( 'Top Keywords', 'meowseo' ) } initialOpen={ true }>
					<table className="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>{ __( 'Keyword', 'meowseo' ) }</th>
								<th>{ __( 'Clicks', 'meowseo' ) }</th>
								<th>{ __( 'Impressions', 'meowseo' ) }</th>
								<th>{ __( 'Position', 'meowseo' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ data.slice( 0, 10 ).map( ( row, index ) => (
								<tr key={ index }>
									<td><strong>{ row.query }</strong></td>
									<td>{ row.clicks }</td>
									<td>{ row.impressions }</td>
									<td>{ parseFloat( row.position ).toFixed( 1 ) }</td>
								</tr>
							) ) }
							{ ! data.length && (
								<tr>
									<td colSpan="4" style={ { textAlign: 'center' } }>
										{ __( 'No data available yet. Please wait for the next sync.', 'meowseo' ) }
									</td>
								</tr>
							) }
						</tbody>
					</table>
				</PanelBody>
			</Panel>
		</div>
	);
}

function StatCard( { label, value, icon } ) {
	return (
		<div className="meowseo-stat-card">
			<div className="meowseo-stat-card__icon">
				<Dashicon icon={ icon } />
			</div>
			<div className="meowseo-stat-card__content">
				<span className="meowseo-stat-card__label">{ label }</span>
				<span className="meowseo-stat-card__value">{ value }</span>
			</div>
		</div>
	);
}
