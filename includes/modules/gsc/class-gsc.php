<?php
/**
 * GSC (Google Search Console) Module
 *
 * Integrates with Google Search Console API via rate-limited queue processing.
 * All API calls are enqueued and processed asynchronously via WP-Cron.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\GSC
 */

namespace MeowSEO\Modules\GSC;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\DB;
use MeowSEO\Options;

defined( 'ABSPATH' ) || exit;

/**
 * GSC Module class
 *
 * Implements Google Search Console integration with rate-limited API queue.
 */
class GSC implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * REST API handler instance.
	 *
	 * @var GSC_REST
	 */
	private GSC_REST $rest;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->rest    = new GSC_REST( $options );
	}

	/**
	 * Boot the module.
	 *
	 * Register hooks for GSC queue processing and REST API.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register WP-Cron hook for queue processing (Requirement 10.2, 10.3).
		add_action( 'meowseo_process_gsc_queue', array( $this, 'process_queue' ) );

		// Register REST API endpoints (Requirement 10.6).
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Schedule cron event if not already scheduled.
		if ( ! wp_next_scheduled( 'meowseo_process_gsc_queue' ) ) {
			wp_schedule_event( time(), 'hourly', 'meowseo_process_gsc_queue' );
		}
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$this->rest->register_routes();
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'gsc';
	}

	/**
	 * Process GSC queue.
	 *
	 * Fetches up to 10 pending queue entries and processes them.
	 * Implements exponential backoff for HTTP 429 responses (Requirement 10.3, 10.4).
	 *
	 * @return void
	 */
	public function process_queue(): void {
		// Fetch max 10 queue entries (Requirement 10.3).
		$queue_entries = DB::get_gsc_queue( 10 );

		if ( empty( $queue_entries ) ) {
			return;
		}

		// Update status to 'processing' before execution.
		$this->update_queue_status_bulk( $queue_entries, 'processing' );

		foreach ( $queue_entries as $entry ) {
			$this->process_queue_entry( $entry );
		}
	}

	/**
	 * Process a single queue entry.
	 *
	 * Executes the API call and handles response codes (Requirement 10.4).
	 *
	 * @param array $entry Queue entry data.
	 * @return void
	 */
	private function process_queue_entry( array $entry ): void {
		$id       = absint( $entry['id'] );
		$job_type = $entry['job_type'];
		$payload  = json_decode( $entry['payload'], true );
		$attempts = absint( $entry['attempts'] );

		// Execute API call based on job type.
		$response = $this->execute_api_call( $job_type, $payload );

		if ( is_wp_error( $response ) ) {
			// Handle error response.
			$this->handle_error_response( $id, $attempts );
			return;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $status_code ) {
			// Success - store data and mark as done (Requirement 10.5).
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( ! empty( $data ) && is_array( $data ) ) {
				$this->store_gsc_data( $data );
			}

			$this->mark_queue_entry_done( $id );
		} elseif ( 429 === $status_code ) {
			// Rate limit - apply exponential backoff (Requirement 10.4).
			$this->handle_rate_limit( $id, $attempts );
		} else {
			// Other error - retry or mark as failed.
			$this->handle_error_response( $id, $attempts );
		}
	}

	/**
	 * Execute API call to Google Search Console.
	 *
	 * @param string $job_type Job type (e.g., 'fetch_url', 'fetch_sitemaps').
	 * @param array  $payload  Job payload data.
	 * @return array|\WP_Error Response array or WP_Error on failure.
	 */
	private function execute_api_call( string $job_type, array $payload ) {
		// Get OAuth credentials (Requirement 10.1, 15.6).
		$credentials = $this->options->get_gsc_credentials();

		if ( empty( $credentials ) || empty( $credentials['access_token'] ) ) {
			return new \WP_Error( 'no_credentials', 'GSC credentials not configured.' );
		}

		$access_token = $credentials['access_token'];

		// Build API request based on job type.
		$api_url = $this->build_api_url( $job_type, $payload );

		if ( is_wp_error( $api_url ) ) {
			return $api_url;
		}

		// Execute HTTP request with OAuth token.
		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
			)
		);

		return $response;
	}

	/**
	 * Build API URL based on job type and payload.
	 *
	 * @param string $job_type Job type.
	 * @param array  $payload  Job payload.
	 * @return string|\WP_Error API URL or WP_Error on invalid job type.
	 */
	private function build_api_url( string $job_type, array $payload ) {
		$base_url = 'https://www.googleapis.com/webmasters/v3';

		switch ( $job_type ) {
			case 'fetch_url':
				if ( empty( $payload['site_url'] ) || empty( $payload['url'] ) ) {
					return new \WP_Error( 'invalid_payload', 'Missing site_url or url in payload.' );
				}

				$site_url = urlencode( $payload['site_url'] );
				$url      = urlencode( $payload['url'] );
				$start    = $payload['start_date'] ?? gmdate( 'Y-m-d', strtotime( '-30 days' ) );
				$end      = $payload['end_date'] ?? gmdate( 'Y-m-d' );

				return "{$base_url}/sites/{$site_url}/searchAnalytics/query?startDate={$start}&endDate={$end}&dimensions=page&filters={$url}";

			case 'fetch_sitemaps':
				if ( empty( $payload['site_url'] ) ) {
					return new \WP_Error( 'invalid_payload', 'Missing site_url in payload.' );
				}

				$site_url = urlencode( $payload['site_url'] );
				return "{$base_url}/sites/{$site_url}/sitemaps";

			default:
				return new \WP_Error( 'invalid_job_type', 'Unknown job type: ' . $job_type );
		}
	}

	/**
	 * Store GSC performance data.
	 *
	 * @param array $data GSC data array.
	 * @return void
	 */
	private function store_gsc_data( array $data ): void {
		if ( empty( $data['rows'] ) ) {
			return;
		}

		$rows = array();

		foreach ( $data['rows'] as $row ) {
			if ( empty( $row['keys'][0] ) ) {
				continue;
			}

			$rows[] = array(
				'url'         => $row['keys'][0],
				'date'        => gmdate( 'Y-m-d' ),
				'clicks'      => $row['clicks'] ?? 0,
				'impressions' => $row['impressions'] ?? 0,
				'ctr'         => $row['ctr'] ?? 0.0,
				'position'    => $row['position'] ?? 0.0,
			);
		}

		if ( ! empty( $rows ) ) {
			DB::upsert_gsc_data( $rows );
		}
	}

	/**
	 * Handle rate limit response (HTTP 429).
	 *
	 * Applies exponential backoff: retry_after = NOW() + POW(2, attempts) * 60 seconds.
	 *
	 * @param int $id       Queue entry ID.
	 * @param int $attempts Current attempt count.
	 * @return void
	 */
	private function handle_rate_limit( int $id, int $attempts ): void {
		// Exponential backoff: 2^attempts * 60 seconds (Requirement 10.4).
		$backoff_seconds = pow( 2, $attempts + 1 ) * 60;
		$retry_after     = time() + $backoff_seconds;

		DB::update_gsc_queue_retry( $id, $retry_after );
	}

	/**
	 * Handle error response (HTTP 5xx or other errors).
	 *
	 * Marks as failed after 5 attempts, otherwise retries after 5 minutes.
	 *
	 * @param int $id       Queue entry ID.
	 * @param int $attempts Current attempt count.
	 * @return void
	 */
	private function handle_error_response( int $id, int $attempts ): void {
		if ( $attempts >= 5 ) {
			// Mark as failed after 5 attempts.
			$this->mark_queue_entry_failed( $id );
		} else {
			// Retry after 5 minutes.
			$retry_after = time() + 300;
			DB::update_gsc_queue_retry( $id, $retry_after );
		}
	}

	/**
	 * Mark queue entry as done.
	 *
	 * @param int $id Queue entry ID.
	 * @return void
	 */
	private function mark_queue_entry_done( int $id ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'done', processed_at = NOW() WHERE id = %d",
				$id
			)
		);
	}

	/**
	 * Mark queue entry as failed.
	 *
	 * @param int $id Queue entry ID.
	 * @return void
	 */
	private function mark_queue_entry_failed( int $id ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'failed', processed_at = NOW() WHERE id = %d",
				$id
			)
		);
	}

	/**
	 * Update queue status in bulk.
	 *
	 * @param array  $entries Queue entries.
	 * @param string $status  New status.
	 * @return void
	 */
	private function update_queue_status_bulk( array $entries, string $status ): void {
		if ( empty( $entries ) ) {
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';
		$ids   = array_map( 'absint', wp_list_pluck( $entries, 'id' ) );

		if ( empty( $ids ) ) {
			return;
		}

		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = %s WHERE id IN ({$ids_placeholder})",
				array_merge( array( $status ), $ids )
			)
		);
	}

	/**
	 * Enqueue a GSC API call.
	 *
	 * Public method to add jobs to the queue.
	 *
	 * @param string $job_type Job type.
	 * @param array  $payload  Job payload.
	 * @return int|false Queue entry ID on success, false on failure.
	 */
	public function enqueue_api_call( string $job_type, array $payload ) {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';

		$result = $wpdb->insert(
			$table,
			array(
				'job_type' => $job_type,
				'payload'  => wp_json_encode( $payload ),
				'status'   => 'pending',
				'attempts' => 0,
			),
			array( '%s', '%s', '%s', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}
}

