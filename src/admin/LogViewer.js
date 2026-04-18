/**
 * LogViewer React Component
 *
 * Provides admin UI for viewing and managing debug logs with filtering,
 * pagination, bulk operations, and AI-friendly export.
 *
 * @package
 * @since 1.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import {
	Button,
	CheckboxControl,
	SelectControl,
	Spinner,
	Notice,
	Modal,
	Flex,
	FlexBlock,
	FlexItem,
	Card,
	CardBody,
	__experimentalText as Text,
	__experimentalHeading as Heading,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * LogViewer Component
 *
 * Requirements: 7.3, 8.1, 8.2, 8.3, 8.4, 8.5, 9.1, 9.2, 9.3, 9.4, 9.5, 10.7, 10.8
 */
export default function LogViewer() {
	// State management (Requirement 7.3, 8.1)
	const [ logs, setLogs ] = useState( [] );
	const [ filters, setFilters ] = useState( {
		level: '',
		module: '',
		startDate: '',
		endDate: '',
	} );
	const [ pagination, setPagination ] = useState( {
		page: 1,
		perPage: 50,
		total: 0,
		pages: 0,
	} );
	const [ selectedIds, setSelectedIds ] = useState( [] );
	const [ expandedRows, setExpandedRows ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( null );
	const [ showDeleteModal, setShowDeleteModal ] = useState( false );

	// Available log levels
	const logLevels = [
		{ label: __( 'All Levels', 'meowseo' ), value: '' },
		{ label: __( 'DEBUG', 'meowseo' ), value: 'DEBUG' },
		{ label: __( 'INFO', 'meowseo' ), value: 'INFO' },
		{ label: __( 'WARNING', 'meowseo' ), value: 'WARNING' },
		{ label: __( 'ERROR', 'meowseo' ), value: 'ERROR' },
		{ label: __( 'CRITICAL', 'meowseo' ), value: 'CRITICAL' },
	];

	// Available modules (will be populated from logs)
	const [ availableModules, setAvailableModules ] = useState( [
		{ label: __( 'All Modules', 'meowseo' ), value: '' },
	] );

	/**
	 * Load filter state from sessionStorage on mount (Requirement 8.5)
	 */
	useEffect( () => {
		const savedFilters = sessionStorage.getItem( 'meowseo_log_filters' );
		if ( savedFilters ) {
			try {
				const parsed = JSON.parse( savedFilters );
				setFilters( parsed );
			} catch ( e ) {
				console.error( 'Failed to parse saved filters:', e );
			}
		}
	}, [] );

	/**
	 * Save filter state to sessionStorage on change (Requirement 8.5)
	 */
	useEffect( () => {
		sessionStorage.setItem(
			'meowseo_log_filters',
			JSON.stringify( filters )
		);
	}, [ filters ] );

	/**
	 * Fetch logs from REST API (Requirement 8.2)
	 */
	const fetchLogs = async () => {
		setLoading( true );
		setError( null );

		try {
			// Build query parameters
			const params = new URLSearchParams( {
				page: pagination.page.toString(),
				per_page: pagination.perPage.toString(),
			} );

			if ( filters.level ) {
				params.append( 'level', filters.level );
			}
			if ( filters.module ) {
				params.append( 'module', filters.module );
			}
			if ( filters.startDate ) {
				params.append( 'start_date', filters.startDate );
			}
			if ( filters.endDate ) {
				params.append( 'end_date', filters.endDate );
			}

			// Fetch logs via REST API
			const response = await apiFetch( {
				path: `/meowseo/v1/logs?${ params.toString() }`,
				method: 'GET',
			} );

			setLogs( response.logs || [] );
			setPagination( {
				page: response.page || 1,
				perPage: response.per_page || 50,
				total: response.total || 0,
				pages: response.pages || 0,
			} );

			// Extract unique modules for filter dropdown
			const modules = [
				...new Set( response.logs.map( ( log ) => log.module ) ),
			];
			setAvailableModules( [
				{ label: __( 'All Modules', 'meowseo' ), value: '' },
				...modules.map( ( module ) => ( {
					label: module,
					value: module,
				} ) ),
			] );
		} catch ( err ) {
			setError( err.message || __( 'Failed to fetch logs', 'meowseo' ) );
			console.error( 'Error fetching logs:', err );
		} finally {
			setLoading( false );
		}
	};

	/**
	 * Fetch logs on mount and when filters or pagination change
	 */
	useEffect( () => {
		fetchLogs();
	}, [ filters, pagination.page ] );

	/**
	 * Handle filter change (Requirement 8.1, 8.2, 8.3, 8.4)
	 * @param filterName
	 * @param value
	 */
	const handleFilterChange = ( filterName, value ) => {
		setFilters( ( prev ) => ( {
			...prev,
			[ filterName ]: value,
		} ) );
		// Reset to page 1 when filters change
		setPagination( ( prev ) => ( { ...prev, page: 1 } ) );
		// Clear selections
		setSelectedIds( [] );
	};

	/**
	 * Handle pagination (Requirement 7.4)
	 * @param newPage
	 */
	const handlePageChange = ( newPage ) => {
		setPagination( ( prev ) => ( { ...prev, page: newPage } ) );
		setSelectedIds( [] );
	};

	/**
	 * Toggle row expansion (Requirement 7.5)
	 * @param logId
	 */
	const toggleRowExpansion = ( logId ) => {
		setExpandedRows( ( prev ) =>
			prev.includes( logId )
				? prev.filter( ( id ) => id !== logId )
				: [ ...prev, logId ]
		);
	};

	/**
	 * Handle individual checkbox selection (Requirement 9.1)
	 * @param logId
	 */
	const handleCheckboxChange = ( logId ) => {
		setSelectedIds( ( prev ) =>
			prev.includes( logId )
				? prev.filter( ( id ) => id !== logId )
				: [ ...prev, logId ]
		);
	};

	/**
	 * Handle "Select All" checkbox (Requirement 9.2)
	 * @param checked
	 */
	const handleSelectAll = ( checked ) => {
		if ( checked ) {
			setSelectedIds( logs.map( ( log ) => log.id ) );
		} else {
			setSelectedIds( [] );
		}
	};

	/**
	 * Handle bulk delete operation (Requirement 9.3, 9.4)
	 */
	const handleBulkDelete = async () => {
		if ( selectedIds.length === 0 ) {
			return;
		}

		setLoading( true );
		setError( null );
		setSuccess( null );

		try {
			await apiFetch( {
				path: '/meowseo/v1/logs',
				method: 'DELETE',
				data: { ids: selectedIds },
			} );

			setSuccess(
				__( 'Successfully deleted selected log entries', 'meowseo' )
			);
			setSelectedIds( [] );
			setShowDeleteModal( false );

			// Refresh log list
			await fetchLogs();
		} catch ( err ) {
			setError(
				err.message || __( 'Failed to delete log entries', 'meowseo' )
			);
			console.error( 'Error deleting logs:', err );
		} finally {
			setLoading( false );
		}
	};

	/**
	 * Handle "Copy for AI Editor" operation (Requirement 9.5, 10.7, 10.8)
	 */
	const handleCopyForAI = async () => {
		if ( selectedIds.length === 0 ) {
			return;
		}

		setLoading( true );
		setError( null );
		setSuccess( null );

		try {
			// Fetch formatted logs for selected entries
			const selectedLogs = logs.filter( ( log ) =>
				selectedIds.includes( log.id )
			);

			// Format logs using the same structure as Log_Formatter
			let formatted = '# MeowSEO Debug Log Export\n\n';
			formatted += '## System Information\n';
			formatted += `- Plugin Version: ${
				window.meowseoLogViewer?.pluginVersion || 'Unknown'
			}\n`;
			formatted += `- WordPress Version: ${
				window.meowseoLogViewer?.wpVersion || 'Unknown'
			}\n`;
			formatted += `- PHP Version: ${
				window.meowseoLogViewer?.phpVersion || 'Unknown'
			}\n`;
			formatted += `- Active Modules: ${
				window.meowseoLogViewer?.activeModules || 'Unknown'
			}\n\n`;
			formatted += '## Log Entries\n\n';

			selectedLogs.forEach( ( log, index ) => {
				formatted += `### Entry ${ index + 1 }: ${ log.level } - ${
					log.module
				} Module\n`;
				formatted += `**Timestamp**: ${ log.created_at }\n`;
				formatted += `**Module**: ${ log.module }\n`;
				formatted += `**Message**: ${ log.message }\n`;

				if ( log.hit_count > 1 ) {
					formatted += `**Hit Count**: ${ log.hit_count }\n`;
				}

				if ( log.context ) {
					try {
						const context =
							typeof log.context === 'string'
								? JSON.parse( log.context )
								: log.context;
						formatted += '\n**Context**:\n```json\n';
						formatted += JSON.stringify( context, null, 2 );
						formatted += '\n```\n';
					} catch ( e ) {
						// Skip invalid context
					}
				}

				if ( log.stack_trace ) {
					formatted += '\n**Stack Trace**:\n```\n';
					formatted += log.stack_trace;
					formatted += '\n```\n';
				}

				formatted += '\n';
			} );

			// Copy to clipboard using Clipboard API
			await navigator.clipboard.writeText( formatted );
			setSuccess( __( 'Log entries copied to clipboard', 'meowseo' ) );
		} catch ( err ) {
			setError(
				err.message || __( 'Failed to copy log entries', 'meowseo' )
			);
			console.error( 'Error copying logs:', err );
		} finally {
			setLoading( false );
		}
	};

	/**
	 * Get log level badge color
	 * @param level
	 */
	const getLevelColor = ( level ) => {
		const colors = {
			DEBUG: '#6c757d',
			INFO: '#0073aa',
			WARNING: '#f0b849',
			ERROR: '#dc3232',
			CRITICAL: '#8b0000',
		};
		return colors[ level ] || '#6c757d';
	};

	return (
		<div className="meowseo-log-viewer">
			{ /* Notices */ }
			{ error && (
				<Notice
					status="error"
					isDismissible
					onRemove={ () => setError( null ) }
				>
					{ error }
				</Notice>
			) }
			{ success && (
				<Notice
					status="success"
					isDismissible
					onRemove={ () => setSuccess( null ) }
				>
					{ success }
				</Notice>
			) }

			{ /* Filter Controls (Requirement 8.1, 8.3, 8.4) */ }
			<Card>
				<CardBody>
					<Heading level={ 3 }>
						{ __( 'Filters', 'meowseo' ) }
					</Heading>
					<Flex gap={ 4 } wrap>
						<FlexItem style={ { minWidth: '200px' } }>
							<SelectControl
								label={ __( 'Log Level', 'meowseo' ) }
								value={ filters.level }
								options={ logLevels }
								onChange={ ( value ) =>
									handleFilterChange( 'level', value )
								}
							/>
						</FlexItem>
						<FlexItem style={ { minWidth: '200px' } }>
							<SelectControl
								label={ __( 'Module', 'meowseo' ) }
								value={ filters.module }
								options={ availableModules }
								onChange={ ( value ) =>
									handleFilterChange( 'module', value )
								}
							/>
						</FlexItem>
						<FlexItem style={ { minWidth: '200px' } }>
							<label
								style={ {
									display: 'block',
									marginBottom: '8px',
									fontWeight: 600,
								} }
							>
								{ __( 'Start Date', 'meowseo' ) }
							</label>
							<input
								type="datetime-local"
								value={ filters.startDate }
								onChange={ ( e ) =>
									handleFilterChange(
										'startDate',
										e.target.value
									)
								}
								style={ { width: '100%', padding: '6px 8px' } }
							/>
						</FlexItem>
						<FlexItem style={ { minWidth: '200px' } }>
							<label
								style={ {
									display: 'block',
									marginBottom: '8px',
									fontWeight: 600,
								} }
							>
								{ __( 'End Date', 'meowseo' ) }
							</label>
							<input
								type="datetime-local"
								value={ filters.endDate }
								onChange={ ( e ) =>
									handleFilterChange(
										'endDate',
										e.target.value
									)
								}
								style={ { width: '100%', padding: '6px 8px' } }
							/>
						</FlexItem>
					</Flex>
				</CardBody>
			</Card>

			{ /* Bulk Actions (Requirement 9.3) */ }
			{ selectedIds.length > 0 && (
				<Card style={ { marginTop: '16px' } }>
					<CardBody>
						<Flex gap={ 2 } align="center">
							<FlexItem>
								<Text>
									{ __( 'Selected:', 'meowseo' ) }{ ' ' }
									{ selectedIds.length }
								</Text>
							</FlexItem>
							<FlexItem>
								<Button
									variant="secondary"
									isDestructive
									onClick={ () => setShowDeleteModal( true ) }
									disabled={ loading }
								>
									{ __( 'Delete', 'meowseo' ) }
								</Button>
							</FlexItem>
							<FlexItem>
								<Button
									variant="secondary"
									onClick={ handleCopyForAI }
									disabled={ loading }
								>
									{ __( 'Copy for AI Editor', 'meowseo' ) }
								</Button>
							</FlexItem>
						</Flex>
					</CardBody>
				</Card>
			) }

			{ /* Log Table (Requirement 7.3, 7.5) */ }
			<Card style={ { marginTop: '16px' } }>
				<CardBody>
					{ loading && (
						<div style={ { textAlign: 'center', padding: '20px' } }>
							<Spinner />
						</div>
					) }

					{ ! loading && logs.length === 0 && (
						<Text>{ __( 'No log entries found', 'meowseo' ) }</Text>
					) }

					{ ! loading && logs.length > 0 && (
						<table className="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th style={ { width: '40px' } }>
										<CheckboxControl
											checked={
												selectedIds.length ===
												logs.length
											}
											onChange={ handleSelectAll }
										/>
									</th>
									<th style={ { width: '40px' } }></th>
									<th style={ { width: '100px' } }>
										{ __( 'Level', 'meowseo' ) }
									</th>
									<th style={ { width: '120px' } }>
										{ __( 'Module', 'meowseo' ) }
									</th>
									<th>{ __( 'Message', 'meowseo' ) }</th>
									<th style={ { width: '180px' } }>
										{ __( 'Timestamp', 'meowseo' ) }
									</th>
									<th style={ { width: '80px' } }>
										{ __( 'Hit Count', 'meowseo' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ logs.map( ( log ) => (
									<>
										<tr key={ log.id }>
											<td>
												<CheckboxControl
													checked={ selectedIds.includes(
														log.id
													) }
													onChange={ () =>
														handleCheckboxChange(
															log.id
														)
													}
												/>
											</td>
											<td>
												<Button
													variant="link"
													onClick={ () =>
														toggleRowExpansion(
															log.id
														)
													}
													style={ { padding: 0 } }
												>
													{ expandedRows.includes(
														log.id
													)
														? '▼'
														: '▶' }
												</Button>
											</td>
											<td>
												<span
													style={ {
														display: 'inline-block',
														padding: '4px 8px',
														borderRadius: '3px',
														backgroundColor:
															getLevelColor(
																log.level
															),
														color: '#fff',
														fontSize: '12px',
														fontWeight: 600,
													} }
												>
													{ log.level }
												</span>
											</td>
											<td>{ log.module }</td>
											<td>{ log.message }</td>
											<td>{ log.created_at }</td>
											<td
												style={ {
													textAlign: 'center',
												} }
											>
												{ log.hit_count }
											</td>
										</tr>
										{ expandedRows.includes( log.id ) && (
											<tr key={ `${ log.id }-expanded` }>
												<td
													colSpan="7"
													style={ {
														backgroundColor:
															'#f9f9f9',
														padding: '16px',
													} }
												>
													{ log.context && (
														<div
															style={ {
																marginBottom:
																	'16px',
															} }
														>
															<strong>
																{ __(
																	'Context:',
																	'meowseo'
																) }
															</strong>
															<pre
																style={ {
																	backgroundColor:
																		'#fff',
																	padding:
																		'12px',
																	border: '1px solid #ddd',
																	borderRadius:
																		'4px',
																	overflow:
																		'auto',
																	maxHeight:
																		'300px',
																} }
															>
																{ typeof log.context ===
																'string'
																	? log.context
																	: JSON.stringify(
																			JSON.parse(
																				log.context
																			),
																			null,
																			2
																	  ) }
															</pre>
														</div>
													) }
													{ log.stack_trace && (
														<div>
															<strong>
																{ __(
																	'Stack Trace:',
																	'meowseo'
																) }
															</strong>
															<pre
																style={ {
																	backgroundColor:
																		'#fff',
																	padding:
																		'12px',
																	border: '1px solid #ddd',
																	borderRadius:
																		'4px',
																	overflow:
																		'auto',
																	maxHeight:
																		'300px',
																} }
															>
																{
																	log.stack_trace
																}
															</pre>
														</div>
													) }
												</td>
											</tr>
										) }
									</>
								) ) }
							</tbody>
						</table>
					) }
				</CardBody>
			</Card>

			{ /* Pagination Controls (Requirement 7.4) */ }
			{ ! loading && logs.length > 0 && (
				<Card style={ { marginTop: '16px' } }>
					<CardBody>
						<Flex gap={ 4 } align="center" justify="space-between">
							<FlexItem>
								<Text>
									{ __( 'Showing', 'meowseo' ) }{ ' ' }
									{ ( pagination.page - 1 ) *
										pagination.perPage +
										1 }{ ' ' }
									-{ ' ' }
									{ Math.min(
										pagination.page * pagination.perPage,
										pagination.total
									) }{ ' ' }
									{ __( 'of', 'meowseo' ) }{ ' ' }
									{ pagination.total }{ ' ' }
									{ __( 'entries', 'meowseo' ) }
								</Text>
							</FlexItem>
							<FlexItem>
								<Flex gap={ 2 }>
									<Button
										variant="secondary"
										onClick={ () =>
											handlePageChange(
												pagination.page - 1
											)
										}
										disabled={ pagination.page === 1 }
									>
										{ __( 'Previous', 'meowseo' ) }
									</Button>
									<Text style={ { padding: '6px 12px' } }>
										{ __( 'Page', 'meowseo' ) }{ ' ' }
										{ pagination.page }{ ' ' }
										{ __( 'of', 'meowseo' ) }{ ' ' }
										{ pagination.pages }
									</Text>
									<Button
										variant="secondary"
										onClick={ () =>
											handlePageChange(
												pagination.page + 1
											)
										}
										disabled={
											pagination.page === pagination.pages
										}
									>
										{ __( 'Next', 'meowseo' ) }
									</Button>
								</Flex>
							</FlexItem>
						</Flex>
					</CardBody>
				</Card>
			) }

			{ /* Delete Confirmation Modal */ }
			{ showDeleteModal && (
				<Modal
					title={ __( 'Confirm Delete', 'meowseo' ) }
					onRequestClose={ () => setShowDeleteModal( false ) }
				>
					<Text>
						{ __( 'Are you sure you want to delete', 'meowseo' ) }{ ' ' }
						{ selectedIds.length }{ ' ' }
						{ __(
							'log entries? This action cannot be undone.',
							'meowseo'
						) }
					</Text>
					<Flex
						gap={ 2 }
						justify="flex-end"
						style={ { marginTop: '16px' } }
					>
						<Button
							variant="secondary"
							onClick={ () => setShowDeleteModal( false ) }
						>
							{ __( 'Cancel', 'meowseo' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ handleBulkDelete }
							disabled={ loading }
						>
							{ __( 'Delete', 'meowseo' ) }
						</Button>
					</Flex>
				</Modal>
			) }
		</div>
	);
}
