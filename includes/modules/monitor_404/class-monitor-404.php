<?php
/**
 * Monitor 404 Module
 *
 * Detects 404 responses and buffers them in Object Cache for efficient batch processing.
 * Prevents synchronous database writes during requests.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\Monitor_404
 */

namespace MeowSEO\Modules\Monitor_404;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\DB;
use MeowSEO\Helpers\Logger;
use MeowSEO\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Monitor 404 Module class
 *
 * Implements buffered 404 logging with per-minute bucket keys.
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6
 */
class Monitor_404 implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Admin interface instance.
	 *
	 * @var Monitor_404_Admin|null
	 */
	private ?Monitor_404_Admin $admin = null;

	/**
	 * REST API handler instance.
	 *
	 * @var Monitor_404_REST|null
	 */
	private ?Monitor_404_REST $rest = null;

	/**
	 * Static asset file extensions to skip.
	 *
	 * Requirement 7.3: Skip requests where the URL path has a file extension
	 * indicating a static asset.
	 */
	private const ASSET_EXTENSIONS = [
		'jpg',
		'jpeg',
		'png',
		'gif',
		'css',
		'js',
		'ico',
		'woff',
		'woff2',
		'svg',
		'pdf',
	];

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->admin   = new Monitor_404_Admin( $options );
		$this->rest    = new Monitor_404_REST( $options );
	}

	/**
	 * Boot the module.
	 *
	 * Register hooks for 404 detection and cron flushing.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Hook into template_redirect to capture 404 responses (Requirement 7.1, priority 999).
		add_action( 'template_redirect', array( $this, 'capture_404' ), 999 );

		// Register custom cron interval.
		add_filter( 'cron_schedules', array( $this, 'register_cron_interval' ) );

		// Register cron hook for flushing buffered data (Requirement 8.1).
		add_action( 'meowseo_flush_404_cron', array( $this, 'flush_buffer' ) );

		// Schedule cron event if not already scheduled (Requirement 8.1).
		$this->schedule_flush();

		// Boot admin interface.
		if ( is_admin() ) {
			$this->admin->boot();
		}

		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this->rest, 'register_routes' ) );
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'monitor_404';
	}

	/**
	 * Register custom 60-second cron interval.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified schedules.
	 */
	public function register_cron_interval( array $schedules ): array {
		$schedules['meowseo_60s'] = array(
			'interval' => 60,
			'display'  => __( 'Every 60 seconds', 'meowseo' ),
		);

		return $schedules;
	}

	/**
	 * Capture 404 responses and buffer in Object Cache.
	 *
	 * Implements filtering logic to skip:
	 * - Requests with empty User-Agent (Requirement 7.2)
	 * - Static assets (Requirement 7.3)
	 * - URLs on ignore list (Requirement 7.4)
	 *
	 * @return void
	 */
	public function capture_404(): void {
		// Requirement 7.1: Detect 404 responses.
		if ( ! is_404() ) {
			return;
		}

		// Requirement 7.2: Skip requests with empty User-Agent.
		$user_agent = $this->get_user_agent();
		if ( empty( $user_agent ) ) {
			return;
		}

		// Get current request URL.
		$url = $this->get_request_url();

		// Requirement 7.3: Skip static assets.
		if ( $this->is_static_asset( $url ) ) {
			return;
		}

		// Requirement 7.4: Skip URLs on ignore list.
		if ( $this->is_ignored_url( $url ) ) {
			return;
		}

		// Buffer the 404 hit (Requirements 7.5, 7.6).
		$this->buffer_404( $url );
	}

	/**
	 * Buffer 404 hit in Object Cache.
	 *
	 * Requirements 7.5, 7.6: Use per-minute bucket keys with 120-second TTL.
	 *
	 * @param string $url URL that returned 404.
	 * @return void
	 */
	private function buffer_404( string $url ): void {
		// Requirement 7.5: Use per-minute bucket key format 404_YYYYMMDD_HHmm.
		$bucket_key = $this->get_bucket_key();

		// Get existing bucket data from Object Cache.
		$bucket = wp_cache_get( $bucket_key );
		if ( ! is_array( $bucket ) ) {
			$bucket = array();
		}

		// Prepare 404 hit data.
		$hit = array(
			'url'        => $url,
			'referrer'   => $this->get_referrer(),
			'user_agent' => $this->get_user_agent(),
			'timestamp'  => time(),
		);

		// Append to bucket.
		$bucket[] = $hit;

		// Requirement 7.6: Store bucket with 120-second TTL.
		wp_cache_set( $bucket_key, $bucket, '', 120 );
	}

	/**
	 * Schedule WP-Cron event for buffer flushing.
	 *
	 * Requirement 8.1: Schedule cron event to run every 60 seconds.
	 *
	 * @return void
	 */
	private function schedule_flush(): void {
		if ( ! wp_next_scheduled( 'meowseo_flush_404_cron' ) ) {
			wp_schedule_event( time(), 'meowseo_60s', 'meowseo_flush_404_cron' );
		}
	}

	/**
	 * Flush buffered 404 data to database.
	 *
	 * Requirements 8.2, 8.3, 8.4, 8.5, 8.6:
	 * - Retrieve buckets for -1 and -2 minutes
	 * - Aggregate URLs by counting occurrences
	 * - Perform single upsert per unique URL
	 * - Increment hit_count and update last_seen
	 * - Delete processed buckets
	 *
	 * @return void
	 */
	public function flush_buffer(): void {
		// Requirement 8.2: Retrieve buckets for -1 and -2 minutes.
		$bucket_keys = $this->get_recent_bucket_keys( 2 );

		$all_hits = array();

		foreach ( $bucket_keys as $bucket_key ) {
			$bucket = wp_cache_get( $bucket_key );

			if ( is_array( $bucket ) && ! empty( $bucket ) ) {
				$all_hits = array_merge( $all_hits, $bucket );

				// Requirement 8.6: Delete processed bucket from Object Cache.
				wp_cache_delete( $bucket_key );
			}
		}

		if ( empty( $all_hits ) ) {
			return;
		}

		// Requirement 8.3: Aggregate URLs by counting occurrences.
		$aggregated = $this->aggregate_hits( $all_hits );

		// Requirement 8.4, 8.5: Perform single upsert per unique URL.
		// Increment hit_count and update last_seen on existing rows.
		DB::bulk_upsert_404( $aggregated );

		// Log successful flush.
		Logger::debug(
			'404 buffer flushed',
			array(
				'unique_urls' => count( $aggregated ),
				'total_hits'  => count( $all_hits ),
			)
		);
	}

	/**
	 * Get current bucket key.
	 *
	 * Requirement 7.5: Format 404_YYYYMMDD_HHmm.
	 *
	 * @return string Bucket key.
	 */
	private function get_bucket_key(): string {
		return '404_' . gmdate( 'Ymd_Hi' );
	}

	/**
	 * Get recent bucket keys.
	 *
	 * @param int $minutes Number of minutes to look back.
	 * @return array Array of bucket keys.
	 */
	private function get_recent_bucket_keys( int $minutes ): array {
		$keys = array();

		for ( $i = 1; $i <= $minutes; $i++ ) {
			$timestamp = time() - ( $i * 60 );
			$keys[]    = '404_' . gmdate( 'Ymd_Hi', $timestamp );
		}

		return $keys;
	}

	/**
	 * Aggregate hits by URL.
	 *
	 * Requirement 8.3: Count occurrences of each unique URL.
	 *
	 * @param array $hits Array of hit data.
	 * @return array Aggregated rows for database insertion.
	 */
	private function aggregate_hits( array $hits ): array {
		$aggregated = array();

		foreach ( $hits as $hit ) {
			$url = $hit['url'];

			if ( ! isset( $aggregated[ $url ] ) ) {
				$aggregated[ $url ] = array(
					'url'        => $url,
					'referrer'   => $hit['referrer'],
					'user_agent' => $hit['user_agent'],
					'hit_count'  => 0,
					'first_seen' => gmdate( 'Y-m-d', $hit['timestamp'] ),
					'last_seen'  => gmdate( 'Y-m-d', $hit['timestamp'] ),
				);
			}

			$aggregated[ $url ]['hit_count']++;

			// Update last_seen if this hit is more recent.
			$last_seen_timestamp = strtotime( $aggregated[ $url ]['last_seen'] );
			if ( $hit['timestamp'] > $last_seen_timestamp ) {
				$aggregated[ $url ]['last_seen'] = gmdate( 'Y-m-d', $hit['timestamp'] );
			}
		}

		return array_values( $aggregated );
	}

	/**
	 * Check if URL is a static asset.
	 *
	 * Requirement 7.3: Skip requests where the URL path has a file extension
	 * indicating a static asset.
	 *
	 * @param string $url URL to check.
	 * @return bool True if static asset, false otherwise.
	 */
	private function is_static_asset( string $url ): bool {
		$path = wp_parse_url( $url, PHP_URL_PATH );

		if ( empty( $path ) ) {
			return false;
		}

		$extension = pathinfo( $path, PATHINFO_EXTENSION );

		if ( empty( $extension ) ) {
			return false;
		}

		return in_array( strtolower( $extension ), self::ASSET_EXTENSIONS, true );
	}

	/**
	 * Check if URL is on ignore list.
	 *
	 * Requirement 7.4: Skip URLs on ignore list stored in plugin options.
	 *
	 * @param string $url URL to check.
	 * @return bool True if ignored, false otherwise.
	 */
	private function is_ignored_url( string $url ): bool {
		$ignore_list = $this->options->get( 'monitor_404_ignore_list', array() );

		if ( ! is_array( $ignore_list ) || empty( $ignore_list ) ) {
			return false;
		}

		// Check for exact match or pattern match.
		foreach ( $ignore_list as $pattern ) {
			// Simple wildcard matching: convert * to .* for regex.
			$regex_pattern = '#' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '#i';

			if ( preg_match( $regex_pattern, $url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the current request URL.
	 *
	 * @return string Request URL.
	 */
	private function get_request_url(): string {
		$protocol = is_ssl() ? 'https://' : 'http://';
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return $protocol . $host . $uri;
	}

	/**
	 * Get the referrer URL.
	 *
	 * @return string Referrer URL or empty string.
	 */
	private function get_referrer(): string {
		return isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	}

	/**
	 * Get the user agent string.
	 *
	 * @return string User agent or empty string.
	 */
	private function get_user_agent(): string {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}
}
