<?php
/**
 * Dashboard_Widgets class for MeowSEO plugin.
 *
 * Handles rendering of async-loaded dashboard widgets with loading indicators
 * and error state templates.
 *
 * @package MeowSEO
 * @subpackage MeowSEO\Admin
 * @since 1.0.0
 */

namespace MeowSEO\Admin;

use MeowSEO\Options;
use MeowSEO\Module_Manager;
use MeowSEO\Helpers\Cache;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard_Widgets class
 *
 * Renders empty widget containers on initial page load for async population.
 * Requirements: 2.1, 2.2, 2.4
 *
 * @since 1.0.0
 */
class Dashboard_Widgets {

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Module_Manager instance
	 *
	 * @since 1.0.0
	 * @var Module_Manager
	 */
	private Module_Manager $module_manager;

	/**
	 * Widget definitions
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private array $widgets;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options        $options        Options instance.
	 * @param Module_Manager $module_manager Module_Manager instance.
	 */
	public function __construct( Options $options, Module_Manager $module_manager ) {
		$this->options        = $options;
		$this->module_manager = $module_manager;

		// Register cache invalidation hooks.
		$this->register_cache_invalidation_hooks();
	}

	/**
	 * Get widget definitions
	 *
	 * Returns widget metadata with translated strings.
	 * Lazy-loaded to avoid translation loading issues.
	 *
	 * @since 1.0.0
	 * @return array<string, array<string, string>> Widget definitions.
	 */
	private function get_widgets(): array {
		if ( ! isset( $this->widgets ) ) {
			// Define available widgets with their metadata.
			$this->widgets = array(
				'content-health'         => array(
					'title'    => __( 'Content Health', 'meowseo' ),
					'endpoint' => '/meowseo/v1/dashboard/content-health',
					'icon'     => 'dashicons-heart',
				),
				'cornerstone-content'    => array(
					'title'    => __( 'Cornerstone Content', 'meowseo' ),
					'endpoint' => '/meowseo/v1/dashboard/cornerstone-content',
					'icon'     => 'dashicons-star-filled',
				),
				'sitemap-status'         => array(
					'title'    => __( 'Sitemap Status', 'meowseo' ),
					'endpoint' => '/meowseo/v1/dashboard/sitemap-status',
					'icon'     => 'dashicons-networking',
				),
				'top-404s'               => array(
					'title'    => __( 'Top 404 Errors', 'meowseo' ),
					'endpoint' => '/meowseo/v1/dashboard/top-404s',
					'icon'     => 'dashicons-warning',
				),
				'gsc-summary'            => array(
					'title'    => __( 'Search Console Summary', 'meowseo' ),
					'endpoint' => '/meowseo/v1/dashboard/gsc-summary',
					'icon'     => 'dashicons-chart-line',
				),
				'discover-performance'   => array(
					'title'    => __( 'Discover Performance', 'meowseo' ),
					'endpoint' => '/meowseo/v1/dashboard/discover-performance',
					'icon'     => 'dashicons-google',
				),
				'index-queue'            => array(
					'title'    => __( 'Index Queue Status', 'meowseo' ),
					'endpoint' => '/meowseo/v1/dashboard/index-queue',
					'icon'     => 'dashicons-list-view',
				),
			);
		}

		return $this->widgets;
	}

	/**
	 * Render all dashboard widgets
	 *
	 * Outputs empty widget containers with data attributes for async loading.
	 * Requirements: 2.1, 2.2, 2.4, 28.3, 28.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_widgets(): void {
		// Generate unique nonce for dashboard page (Requirement 28.5).
		$dashboard_nonce = wp_create_nonce( 'meowseo_dashboard_widgets' );
		
		// Get widgets with translated strings.
		$widgets = $this->get_widgets();
		?>
		<div class="meowseo-dashboard-widgets">
			<?php foreach ( $widgets as $widget_id => $widget_config ) : ?>
				<div 
					class="meowseo-widget" 
					id="meowseo-widget-<?php echo esc_attr( $widget_id ); ?>"
					data-widget-id="<?php echo esc_attr( $widget_id ); ?>"
					data-endpoint="<?php echo esc_attr( $widget_config['endpoint'] ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"
					data-dashboard-nonce="<?php echo esc_attr( $dashboard_nonce ); ?>"
				>
					<div class="meowseo-widget-header">
						<span class="<?php echo esc_attr( $widget_config['icon'] ); ?> meowseo-widget-icon"></span>
						<h2 class="meowseo-widget-title"><?php echo esc_html( $widget_config['title'] ); ?></h2>
					</div>
					
					<div class="meowseo-widget-content">
						<!-- Loading indicator -->
						<div class="meowseo-widget-loading" aria-live="polite">
							<span class="spinner is-active"></span>
							<p><?php esc_html_e( 'Loading...', 'meowseo' ); ?></p>
						</div>
						
						<!-- Error state template (hidden by default) -->
						<div class="meowseo-widget-error" style="display: none;" role="alert">
							<span class="dashicons dashicons-warning"></span>
							<p class="meowseo-widget-error-message">
								<?php esc_html_e( 'Failed to load widget data. Please try refreshing the page.', 'meowseo' ); ?>
							</p>
							<button type="button" class="button meowseo-widget-retry" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
								<?php esc_html_e( 'Retry', 'meowseo' ); ?>
							</button>
						</div>
						
						<!-- Widget data container (populated by JavaScript) -->
						<div class="meowseo-widget-data" style="display: none;"></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Get content health data
	 *
	 * Queries posts missing SEO data (title, description, focus keyword).
	 * Requirements: 2.5, 3.4
	 *
	 * @since 1.0.0
	 * @return array Content health widget data.
	 */
	public function get_content_health_data(): array {
		// Check cache first.
		$cache_key = 'dashboard_content_health';
		$cached_data = Cache::get( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		global $wpdb;

		// Get total published posts.
		// Note: Post types are hardcoded and safe; no user input.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_posts = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post', 'page')"
		);

		// Count posts missing SEO title.
		$missing_title = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) 
				FROM {$wpdb->posts} p 
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s 
				WHERE p.post_status = 'publish' 
				AND p.post_type IN ('post', 'page') 
				AND (pm.meta_value IS NULL OR pm.meta_value = '')",
				'meowseo_title'
			)
		);

		// Count posts missing meta description.
		$missing_description = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) 
				FROM {$wpdb->posts} p 
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s 
				WHERE p.post_status = 'publish' 
				AND p.post_type IN ('post', 'page') 
				AND (pm.meta_value IS NULL OR pm.meta_value = '')",
				'meowseo_description'
			)
		);

		// Count posts missing focus keyword.
		$missing_focus_keyword = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) 
				FROM {$wpdb->posts} p 
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s 
				WHERE p.post_status = 'publish' 
				AND p.post_type IN ('post', 'page') 
				AND (pm.meta_value IS NULL OR pm.meta_value = '')",
				'meowseo_focus_keyword'
			)
		);

		// Calculate percentage complete.
		$percentage_complete = $total_posts > 0 
			? round( ( ( $total_posts - max( $missing_title, $missing_description, $missing_focus_keyword ) ) / $total_posts ) * 100, 2 )
			: 100.0;

		$data = array(
			'total_posts'            => $total_posts,
			'missing_title'          => $missing_title,
			'missing_description'    => $missing_description,
			'missing_focus_keyword'  => $missing_focus_keyword,
			'percentage_complete'    => $percentage_complete,
		);

		// Cache for 5 minutes (300 seconds).
		Cache::set( $cache_key, $data, 300 );

		return $data;
	}

	/**
	 * Get sitemap status data
	 *
	 * Checks sitemap generation status and last update time.
	 * Requirements: 2.5, 3.4
	 *
	 * @since 1.0.0
	 * @return array Sitemap status widget data.
	 */
	public function get_sitemap_status_data(): array {
		// Check cache first.
		$cache_key = 'dashboard_sitemap_status';
		$cached_data = Cache::get( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		// Check if sitemap module is enabled.
		$enabled = $this->options->get( 'meowseo_sitemap_enabled', false );

		if ( ! $enabled ) {
			$data = array(
				'enabled'       => false,
				'last_generated' => null,
				'total_urls'    => 0,
				'post_types'    => array(),
				'cache_status'  => 'disabled',
			);

			// Cache for 5 minutes (300 seconds).
			Cache::set( $cache_key, $data, 300 );

			return $data;
		}

		// Get sitemap directory path.
		$upload_dir = wp_upload_dir();
		$sitemap_dir = $upload_dir['basedir'] . '/meowseo-sitemaps';
		$index_file = $sitemap_dir . '/sitemap-index.xml';

		// Check if sitemap index exists.
		$last_generated = null;
		$cache_status = 'stale';

		if ( file_exists( $index_file ) ) {
			$last_generated = gmdate( 'c', filemtime( $index_file ) );
			
			// Check if cache is fresh (less than 24 hours old).
			$cache_ttl = $this->options->get( 'meowseo_sitemap_cache_ttl', 86400 );
			$age = time() - filemtime( $index_file );
			$cache_status = $age < $cache_ttl ? 'fresh' : 'stale';
		}

		// Get enabled post types.
		$enabled_post_types = $this->options->get( 'meowseo_sitemap_post_types', array( 'post', 'page' ) );
		
		// Count URLs per post type.
		global $wpdb;
		$post_types_data = array();
		$total_urls = 0;

		foreach ( $enabled_post_types as $post_type ) {
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = %s",
					$post_type
				)
			);
			$post_types_data[ $post_type ] = $count;
			$total_urls += $count;
		}

		$data = array(
			'enabled'        => true,
			'last_generated' => $last_generated,
			'total_urls'     => $total_urls,
			'post_types'     => $post_types_data,
			'cache_status'   => $cache_status,
		);

		// Cache for 5 minutes (300 seconds).
		Cache::set( $cache_key, $data, 300 );

		return $data;
	}

	/**
	 * Get top 404 errors data
	 *
	 * Queries 404 logs for top errors from last 30 days.
	 * Requirements: 2.5, 3.4
	 *
	 * @since 1.0.0
	 * @return array Top 404s widget data.
	 */
	public function get_top_404s_data(): array {
		// Check cache first.
		$cache_key = 'dashboard_top_404s';
		$cached_data = Cache::get( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_404_log';

		// Check if table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

		if ( ! $table_exists ) {
			$data = array();
			// Cache for 5 minutes (300 seconds).
			Cache::set( $cache_key, $data, 300 );
			return $data;
		}

		// Get top 10 404s from last 30 days.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT url, hit_count as count, last_seen 
				FROM {$table} 
				WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
				ORDER BY hit_count DESC 
				LIMIT %d",
				10
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			$data = array();
			// Cache for 5 minutes (300 seconds).
			Cache::set( $cache_key, $data, 300 );
			return $data;
		}

		// Check if each URL has a redirect.
		$redirects_table = $wpdb->prefix . 'meowseo_redirects';
		$redirects_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $redirects_table ) ) === $redirects_table;

		foreach ( $results as &$row ) {
			$row['last_seen'] = gmdate( 'c', strtotime( $row['last_seen'] ) );
			$row['count'] = (int) $row['count'];
			
			// Check if redirect exists for this URL.
			$row['has_redirect'] = false;
			if ( $redirects_table_exists ) {
				$redirect_exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$redirects_table} WHERE source_url = %s AND is_active = 1",
						$row['url']
					)
				);
				$row['has_redirect'] = (bool) $redirect_exists;
			}
		}

		// Cache for 5 minutes (300 seconds).
		Cache::set( $cache_key, $results, 300 );

		return $results;
	}

	/**
	 * Get GSC summary data
	 *
	 * Aggregates GSC metrics (clicks, impressions, CTR, position).
	 * Requirements: 2.5, 3.4
	 *
	 * @since 1.0.0
	 * @return array GSC summary widget data.
	 */
	public function get_gsc_summary_data(): array {
		// Check cache first.
		$cache_key = 'dashboard_gsc_summary';
		$cached_data = Cache::get( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_data';

		// Check if table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

		if ( ! $table_exists ) {
			$data = array(
				'clicks'      => 0,
				'impressions' => 0,
				'ctr'         => 0.0,
				'position'    => 0.0,
				'date_range'  => array(
					'start' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
					'end'   => gmdate( 'Y-m-d' ),
				),
				'last_synced' => null,
			);

			// Cache for 5 minutes (300 seconds).
			Cache::set( $cache_key, $data, 300 );

			return $data;
		}

		// Get aggregated data for last 30 days.
		$results = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					SUM(clicks) as total_clicks,
					SUM(impressions) as total_impressions,
					AVG(ctr) as avg_ctr,
					AVG(position) as avg_position,
					MAX(date) as last_date
				FROM {$table} 
				WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
				null
			),
			ARRAY_A
		);

		// Get last sync time from options.
		$last_synced = $this->options->get( 'meowseo_gsc_last_sync', null );
		if ( $last_synced ) {
			$last_synced = gmdate( 'c', $last_synced );
		}

		$data = array(
			'clicks'      => (int) ( $results['total_clicks'] ?? 0 ),
			'impressions' => (int) ( $results['total_impressions'] ?? 0 ),
			'ctr'         => (float) ( $results['avg_ctr'] ?? 0.0 ),
			'position'    => (float) ( $results['avg_position'] ?? 0.0 ),
			'date_range'  => array(
				'start' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
				'end'   => gmdate( 'Y-m-d' ),
			),
			'last_synced' => $last_synced,
		);

		// Cache for 5 minutes (300 seconds).
		Cache::set( $cache_key, $data, 300 );

		return $data;
	}

	/**
	 * Get Discover performance data
	 *
	 * Queries Discover metrics if available.
	 * Requirements: 2.5, 3.4
	 *
	 * Note: Discover data is stored separately from regular GSC data.
	 * This method checks for discover-specific metrics in options.
	 *
	 * @since 1.0.0
	 * @return array Discover performance widget data.
	 */
	public function get_discover_performance_data(): array {
		// Check cache first.
		$cache_key = 'dashboard_discover_performance';
		$cached_data = Cache::get( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		// Check if Discover data is available in options.
		$discover_data = $this->options->get( 'meowseo_gsc_discover_data', null );

		if ( empty( $discover_data ) || ! is_array( $discover_data ) ) {
			$data = array(
				'impressions' => 0,
				'clicks'      => 0,
				'ctr'         => 0.0,
				'available'   => false,
				'date_range'  => array(
					'start' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
					'end'   => gmdate( 'Y-m-d' ),
				),
			);

			// Cache for 5 minutes (300 seconds).
			Cache::set( $cache_key, $data, 300 );

			return $data;
		}

		// Extract Discover metrics.
		$impressions = (int) ( $discover_data['impressions'] ?? 0 );
		$clicks = (int) ( $discover_data['clicks'] ?? 0 );
		$ctr = $impressions > 0 ? ( $clicks / $impressions ) : 0.0;

		$data = array(
			'impressions' => $impressions,
			'clicks'      => $clicks,
			'ctr'         => (float) $ctr,
			'available'   => $impressions > 0 || $clicks > 0,
			'date_range'  => array(
				'start' => $discover_data['start_date'] ?? gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
				'end'   => $discover_data['end_date'] ?? gmdate( 'Y-m-d' ),
			),
		);

		// Cache for 5 minutes (300 seconds).
		Cache::set( $cache_key, $data, 300 );

		return $data;
	}

	/**
	 * Get index queue data
	 *
	 * Counts pending/processing/completed/failed indexing requests.
	 * Requirements: 2.5, 3.4
	 *
	 * @since 1.0.0
	 * @return array Index queue status widget data.
	 */
	public function get_index_queue_data(): array {
		// Check cache first.
		$cache_key = 'dashboard_index_queue';
		$cached_data = Cache::get( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';

		// Check if table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

		if ( ! $table_exists ) {
			$data = array(
				'pending'        => 0,
				'processing'     => 0,
				'completed'      => 0,
				'failed'         => 0,
				'last_processed' => null,
			);

			// Cache for 5 minutes (300 seconds).
			Cache::set( $cache_key, $data, 300 );

			return $data;
		}

		// Count by status.
		$pending = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE status = %s",
				'pending'
			)
		);

		$processing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE status = %s",
				'processing'
			)
		);

		$completed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE status = %s",
				'completed'
			)
		);

		$failed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE status = %s",
				'failed'
			)
		);

		// Get last processed time.
		// Note: Status values are hardcoded and safe; no user input.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$last_processed = $wpdb->get_var(
			"SELECT MAX(processed_at) FROM {$table} WHERE status IN ('completed', 'failed')"
		);

		if ( $last_processed ) {
			$last_processed = gmdate( 'c', strtotime( $last_processed ) );
		}

		$data = array(
			'pending'        => $pending,
			'processing'     => $processing,
			'completed'      => $completed,
			'failed'         => $failed,
			'last_processed' => $last_processed,
		);

		// Cache for 5 minutes (300 seconds).
		Cache::set( $cache_key, $data, 300 );

		return $data;
	}

	/**
	 * Get cornerstone content data
	 *
	 * Lists cornerstone posts with edit links.
	 * Requirements: 6.10
	 *
	 * @since 1.0.0
	 * @return array Cornerstone content widget data.
	 */
	public function get_cornerstone_content_data(): array {
		// Check cache first.
		$cache_key = 'dashboard_cornerstone_content';
		$cached_data = Cache::get( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		global $wpdb;

		// Get total count of cornerstone posts.
		$total_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
				WHERE meta_key = %s AND meta_value = '1'",
				'_meowseo_is_cornerstone'
			)
		);

		// Get cornerstone posts with details.
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, p.post_type, p.post_status, p.post_modified
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE pm.meta_key = %s AND pm.meta_value = '1'
				AND p.post_status = 'publish'
				ORDER BY p.post_modified DESC
				LIMIT 10",
				'_meowseo_is_cornerstone'
			),
			ARRAY_A
		);

		// Format posts data.
		$posts_data = array();
		foreach ( $posts as $post ) {
			$posts_data[] = array(
				'id'            => (int) $post['ID'],
				'title'         => $post['post_title'],
				'post_type'     => $post['post_type'],
				'edit_url'      => get_edit_post_link( $post['ID'], 'raw' ),
				'view_url'      => get_permalink( $post['ID'] ),
				'last_modified' => gmdate( 'c', strtotime( $post['post_modified'] ) ),
			);
		}

		// Generate filter URL for all cornerstone posts.
		$filter_url = add_query_arg(
			array(
				'post_type'                   => 'post',
				'meowseo_cornerstone_filter' => 'cornerstone',
			),
			admin_url( 'edit.php' )
		);

		$data = array(
			'total_count' => $total_count,
			'posts'       => $posts_data,
			'filter_url'  => $filter_url,
		);

		// Cache for 5 minutes (300 seconds).
		Cache::set( $cache_key, $data, 300 );

		return $data;
	}

	/**
	 * Register cache invalidation hooks
	 *
	 * Invalidates widget caches when relevant data changes.
	 * Requirements: 2.4, 25.4, 25.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_cache_invalidation_hooks(): void {
		// Invalidate content health cache when posts are saved or deleted.
		add_action( 'save_post', array( $this, 'invalidate_content_health_cache' ) );
		add_action( 'delete_post', array( $this, 'invalidate_content_health_cache' ) );
		add_action( 'update_postmeta', array( $this, 'invalidate_content_health_cache_on_meta_update' ), 10, 4 );

		// Invalidate sitemap status cache when sitemap is generated.
		add_action( 'meowseo_sitemap_generated', array( $this, 'invalidate_sitemap_status_cache' ) );

		// Invalidate top 404s cache when 404 logs are updated.
		add_action( 'meowseo_404_logged', array( $this, 'invalidate_top_404s_cache' ) );

		// Invalidate GSC summary cache when GSC data is synced.
		add_action( 'meowseo_gsc_data_synced', array( $this, 'invalidate_gsc_summary_cache' ) );

		// Invalidate Discover performance cache when Discover data is synced.
		add_action( 'meowseo_gsc_discover_synced', array( $this, 'invalidate_discover_performance_cache' ) );

		// Invalidate index queue cache when queue status changes.
		add_action( 'meowseo_gsc_queue_updated', array( $this, 'invalidate_index_queue_cache' ) );

		// Invalidate cornerstone content cache when cornerstone meta is updated.
		add_action( 'update_postmeta', array( $this, 'invalidate_cornerstone_cache_on_meta_update' ), 10, 4 );
		add_action( 'delete_postmeta', array( $this, 'invalidate_cornerstone_cache_on_meta_delete' ), 10, 4 );
	}

	/**
	 * Invalidate content health cache
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function invalidate_content_health_cache(): void {
		Cache::delete( 'dashboard_content_health' );
	}

	/**
	 * Invalidate content health cache on meta update
	 *
	 * Only invalidates when SEO-related meta keys are updated.
	 *
	 * @since 1.0.0
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return void
	 */
	public function invalidate_content_health_cache_on_meta_update( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		// Only invalidate for SEO-related meta keys.
		$seo_meta_keys = array( 'meowseo_title', 'meowseo_description', 'meowseo_focus_keyword' );
		if ( in_array( $meta_key, $seo_meta_keys, true ) ) {
			Cache::delete( 'dashboard_content_health' );
		}
	}

	/**
	 * Invalidate sitemap status cache
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function invalidate_sitemap_status_cache(): void {
		Cache::delete( 'dashboard_sitemap_status' );
	}

	/**
	 * Invalidate top 404s cache
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function invalidate_top_404s_cache(): void {
		Cache::delete( 'dashboard_top_404s' );
	}

	/**
	 * Invalidate GSC summary cache
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function invalidate_gsc_summary_cache(): void {
		Cache::delete( 'dashboard_gsc_summary' );
	}

	/**
	 * Invalidate Discover performance cache
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function invalidate_discover_performance_cache(): void {
		Cache::delete( 'dashboard_discover_performance' );
	}

	/**
	 * Invalidate index queue cache
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function invalidate_index_queue_cache(): void {
		Cache::delete( 'dashboard_index_queue' );
	}

	/**
	 * Invalidate cornerstone content cache on meta update
	 *
	 * Only invalidates when cornerstone meta key is updated.
	 *
	 * @since 1.0.0
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return void
	 */
	public function invalidate_cornerstone_cache_on_meta_update( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		if ( '_meowseo_is_cornerstone' === $meta_key ) {
			Cache::delete( 'dashboard_cornerstone_content' );
		}
	}

	/**
	 * Invalidate cornerstone content cache on meta delete
	 *
	 * Only invalidates when cornerstone meta key is deleted.
	 *
	 * @since 1.0.0
	 * @param int|array $meta_id    Meta ID or array of meta IDs.
	 * @param int       $object_id  Object ID (optional).
	 * @param string    $meta_key   Meta key (optional).
	 * @param mixed     $meta_value Meta value (optional).
	 * @return void
	 */
	public function invalidate_cornerstone_cache_on_meta_delete( $meta_id, int $object_id = 0, string $meta_key = '', $meta_value = null ): void {
		if ( '_meowseo_is_cornerstone' === $meta_key ) {
			Cache::delete( 'dashboard_cornerstone_content' );
		}
	}
}
