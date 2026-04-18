/**
 * Dashboard widget async loading functionality
 *
 * Fetches widget data from REST API endpoints and populates dashboard widgets.
 * Handles loading states, errors, and retry functionality.
 *
 * Requirements: 2.3, 2.4, 2.6
 *
 * @package
 * @since 1.0.0
 */

/**
 * Initialize dashboard widgets on DOM ready
 */
document.addEventListener( 'DOMContentLoaded', () => {
	const widgets = document.querySelectorAll( '.meowseo-widget' );

	if ( ! widgets.length ) {
		return;
	}

	// Load each widget independently
	widgets.forEach( ( widget ) => {
		loadWidget( widget );
	} );

	// Set up retry button handlers
	document.addEventListener( 'click', ( event ) => {
		if ( event.target.classList.contains( 'meowseo-widget-retry' ) ) {
			const widgetId = event.target.getAttribute( 'data-widget-id' );
			const widget = document.getElementById(
				`meowseo-widget-${ widgetId }`
			);
			if ( widget ) {
				loadWidget( widget );
			}
		}
	} );
} );

/**
 * Load widget data from REST API
 *
 * Fetches data for a single widget and updates the UI.
 * Handles loading states, success, and error cases.
 *
 * @param {HTMLElement} widget - The widget container element
 */
async function loadWidget( widget ) {
	const widgetId = widget.getAttribute( 'data-widget-id' );
	const endpoint = widget.getAttribute( 'data-endpoint' );
	const nonce = widget.getAttribute( 'data-nonce' );

	// Get widget UI elements
	const loadingEl = widget.querySelector( '.meowseo-widget-loading' );
	const errorEl = widget.querySelector( '.meowseo-widget-error' );
	const dataEl = widget.querySelector( '.meowseo-widget-data' );

	// Show loading state
	if ( loadingEl ) {
		loadingEl.style.display = 'block';
	}
	if ( errorEl ) {
		errorEl.style.display = 'none';
	}
	if ( dataEl ) {
		dataEl.style.display = 'none';
	}

	try {
		// Fetch widget data from REST API
		const response = await fetch( endpoint, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': nonce,
			},
			credentials: 'same-origin',
		} );

		if ( ! response.ok ) {
			throw new Error(
				`HTTP ${ response.status }: ${ response.statusText }`
			);
		}

		const data = await response.json();

		// Hide loading, show data
		if ( loadingEl ) {
			loadingEl.style.display = 'none';
		}
		if ( dataEl ) {
			dataEl.innerHTML = renderWidgetData( widgetId, data );
			dataEl.style.display = 'block';
		}
	} catch ( error ) {
		console.error( `Failed to load widget ${ widgetId }:`, error );

		// Hide loading, show error
		if ( loadingEl ) {
			loadingEl.style.display = 'none';
		}
		if ( errorEl ) {
			// Update error message if available
			const errorMessage = errorEl.querySelector(
				'.meowseo-widget-error-message'
			);
			if ( errorMessage && error.message ) {
				errorMessage.textContent = `Failed to load widget data: ${ error.message }`;
			}
			errorEl.style.display = 'block';
		}
	}
}

/**
 * Render widget data based on widget type
 *
 * Generates HTML for widget content based on the widget ID and data.
 *
 * @param {string} widgetId - The widget identifier
 * @param {Object} data     - The widget data from REST API
 * @return {string} HTML string for widget content
 */
function renderWidgetData( widgetId, data ) {
	switch ( widgetId ) {
		case 'content-health':
			return renderContentHealthWidget( data );
		case 'sitemap-status':
			return renderSitemapStatusWidget( data );
		case 'top-404s':
			return renderTop404sWidget( data );
		case 'gsc-summary':
			return renderGscSummaryWidget( data );
		case 'discover-performance':
			return renderDiscoverPerformanceWidget( data );
		case 'index-queue':
			return renderIndexQueueWidget( data );
		default:
			return '<p>Unknown widget type</p>';
	}
}

/**
 * Render Content Health widget
 *
 * @param {Object} data - Widget data
 * @return {string} HTML string
 */
function renderContentHealthWidget( data ) {
	const percentage = data.percentage_complete || 0;
	const totalPosts = data.total_posts || 0;
	const missingTitle = data.missing_title || 0;
	const missingDescription = data.missing_description || 0;
	const missingFocusKeyword = data.missing_focus_keyword || 0;

	return `
		<div class="meowseo-content-health">
			<div class="meowseo-progress-circle">
				<svg viewBox="0 0 36 36" class="meowseo-circular-chart">
					<path class="meowseo-circle-bg"
						d="M18 2.0845
							a 15.9155 15.9155 0 0 1 0 31.831
							a 15.9155 15.9155 0 0 1 0 -31.831"
					/>
					<path class="meowseo-circle"
						stroke-dasharray="${ percentage }, 100"
						d="M18 2.0845
							a 15.9155 15.9155 0 0 1 0 31.831
							a 15.9155 15.9155 0 0 1 0 -31.831"
					/>
					<text x="18" y="20.35" class="meowseo-percentage">${ percentage.toFixed(
						1
					) }%</text>
				</svg>
			</div>
			<div class="meowseo-content-health-stats">
				<p><strong>Total Posts:</strong> ${ totalPosts }</p>
				<p><strong>Missing Title:</strong> ${ missingTitle }</p>
				<p><strong>Missing Description:</strong> ${ missingDescription }</p>
				<p><strong>Missing Focus Keyword:</strong> ${ missingFocusKeyword }</p>
			</div>
		</div>
	`;
}

/**
 * Render Sitemap Status widget
 *
 * @param {Object} data - Widget data
 * @return {string} HTML string
 */
function renderSitemapStatusWidget( data ) {
	if ( ! data.enabled ) {
		return '<p>Sitemap generation is disabled. Enable it in Settings to see status.</p>';
	}

	const lastGenerated = data.last_generated
		? new Date( data.last_generated ).toLocaleString()
		: 'Never';
	const totalUrls = data.total_urls || 0;
	const cacheStatus = data.cache_status || 'unknown';
	const postTypes = data.post_types || {};

	const postTypesList = Object.entries( postTypes )
		.map(
			( [ type, count ] ) =>
				`<li><strong>${ escapeHtml(
					type
				) }:</strong> ${ count } URLs</li>`
		)
		.join( '' );

	const statusClass =
		cacheStatus === 'fresh' ? 'status-good' : 'status-warning';
	const statusText = cacheStatus === 'fresh' ? 'Fresh' : 'Stale';

	return `
		<div class="meowseo-sitemap-status">
			<p><strong>Status:</strong> <span class="${ statusClass }">${ statusText }</span></p>
			<p><strong>Last Generated:</strong> ${ escapeHtml( lastGenerated ) }</p>
			<p><strong>Total URLs:</strong> ${ totalUrls }</p>
			<div class="meowseo-post-types">
				<strong>Post Types:</strong>
				<ul>${ postTypesList }</ul>
			</div>
		</div>
	`;
}

/**
 * Render Top 404s widget
 *
 * @param {Array} data - Widget data (array of 404 entries)
 * @return {string} HTML string
 */
function renderTop404sWidget( data ) {
	if ( ! Array.isArray( data ) || data.length === 0 ) {
		return '<p>No 404 errors recorded in the last 30 days.</p>';
	}

	const rows = data
		.map( ( entry ) => {
			const url = escapeHtml( entry.url || '' );
			const count = entry.count || 0;
			const lastSeen = entry.last_seen
				? new Date( entry.last_seen ).toLocaleString()
				: 'Unknown';
			const hasRedirect = entry.has_redirect;
			const redirectBadge = hasRedirect
				? '<span class="meowseo-badge meowseo-badge-success">Redirected</span>'
				: '<span class="meowseo-badge meowseo-badge-warning">No Redirect</span>';

			return `
				<tr>
					<td>${ url }</td>
					<td>${ count }</td>
					<td>${ escapeHtml( lastSeen ) }</td>
					<td>${ redirectBadge }</td>
				</tr>
			`;
		} )
		.join( '' );

	return `
		<div class="meowseo-top-404s">
			<table class="meowseo-table">
				<thead>
					<tr>
						<th>URL</th>
						<th>Hits</th>
						<th>Last Seen</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					${ rows }
				</tbody>
			</table>
		</div>
	`;
}

/**
 * Render GSC Summary widget
 *
 * @param {Object} data - Widget data
 * @return {string} HTML string
 */
function renderGscSummaryWidget( data ) {
	const clicks = data.clicks || 0;
	const impressions = data.impressions || 0;
	const ctr = data.ctr || 0;
	const position = data.position || 0;
	const dateRange = data.date_range || {};
	const lastSynced = data.last_synced
		? new Date( data.last_synced ).toLocaleString()
		: 'Never';

	const startDate = dateRange.start || 'N/A';
	const endDate = dateRange.end || 'N/A';

	return `
		<div class="meowseo-gsc-summary">
			<div class="meowseo-metric-grid">
				<div class="meowseo-metric">
					<span class="meowseo-metric-label">Clicks</span>
					<span class="meowseo-metric-value">${ clicks.toLocaleString() }</span>
				</div>
				<div class="meowseo-metric">
					<span class="meowseo-metric-label">Impressions</span>
					<span class="meowseo-metric-value">${ impressions.toLocaleString() }</span>
				</div>
				<div class="meowseo-metric">
					<span class="meowseo-metric-label">CTR</span>
					<span class="meowseo-metric-value">${ ( ctr * 100 ).toFixed( 2 ) }%</span>
				</div>
				<div class="meowseo-metric">
					<span class="meowseo-metric-label">Avg. Position</span>
					<span class="meowseo-metric-value">${ position.toFixed( 1 ) }</span>
				</div>
			</div>
			<div class="meowseo-gsc-meta">
				<p><strong>Date Range:</strong> ${ escapeHtml( startDate ) } to ${ escapeHtml(
					endDate
				) }</p>
				<p><strong>Last Synced:</strong> ${ escapeHtml( lastSynced ) }</p>
			</div>
		</div>
	`;
}

/**
 * Render Discover Performance widget
 *
 * @param {Object} data - Widget data
 * @return {string} HTML string
 */
function renderDiscoverPerformanceWidget( data ) {
	if ( ! data.available ) {
		return '<p>No Discover data available. Your site may not have content appearing in Google Discover yet.</p>';
	}

	const impressions = data.impressions || 0;
	const clicks = data.clicks || 0;
	const ctr = data.ctr || 0;
	const dateRange = data.date_range || {};

	const startDate = dateRange.start || 'N/A';
	const endDate = dateRange.end || 'N/A';

	return `
		<div class="meowseo-discover-performance">
			<div class="meowseo-metric-grid">
				<div class="meowseo-metric">
					<span class="meowseo-metric-label">Impressions</span>
					<span class="meowseo-metric-value">${ impressions.toLocaleString() }</span>
				</div>
				<div class="meowseo-metric">
					<span class="meowseo-metric-label">Clicks</span>
					<span class="meowseo-metric-value">${ clicks.toLocaleString() }</span>
				</div>
				<div class="meowseo-metric">
					<span class="meowseo-metric-label">CTR</span>
					<span class="meowseo-metric-value">${ ( ctr * 100 ).toFixed( 2 ) }%</span>
				</div>
			</div>
			<div class="meowseo-discover-meta">
				<p><strong>Date Range:</strong> ${ escapeHtml( startDate ) } to ${ escapeHtml(
					endDate
				) }</p>
			</div>
		</div>
	`;
}

/**
 * Render Index Queue widget
 *
 * @param {Object} data - Widget data
 * @return {string} HTML string
 */
function renderIndexQueueWidget( data ) {
	const pending = data.pending || 0;
	const processing = data.processing || 0;
	const completed = data.completed || 0;
	const failed = data.failed || 0;
	const lastProcessed = data.last_processed
		? new Date( data.last_processed ).toLocaleString()
		: 'Never';

	return `
		<div class="meowseo-index-queue">
			<div class="meowseo-queue-stats">
				<div class="meowseo-queue-stat">
					<span class="meowseo-queue-label">Pending</span>
					<span class="meowseo-queue-value meowseo-queue-pending">${ pending }</span>
				</div>
				<div class="meowseo-queue-stat">
					<span class="meowseo-queue-label">Processing</span>
					<span class="meowseo-queue-value meowseo-queue-processing">${ processing }</span>
				</div>
				<div class="meowseo-queue-stat">
					<span class="meowseo-queue-label">Completed</span>
					<span class="meowseo-queue-value meowseo-queue-completed">${ completed }</span>
				</div>
				<div class="meowseo-queue-stat">
					<span class="meowseo-queue-label">Failed</span>
					<span class="meowseo-queue-value meowseo-queue-failed">${ failed }</span>
				</div>
			</div>
			<div class="meowseo-queue-meta">
				<p><strong>Last Processed:</strong> ${ escapeHtml( lastProcessed ) }</p>
			</div>
		</div>
	`;
}

/**
 * Escape HTML to prevent XSS
 *
 * @param {string} text - Text to escape
 * @return {string} Escaped text
 */
function escapeHtml( text ) {
	const div = document.createElement( 'div' );
	div.textContent = text;
	return div.innerHTML;
}
