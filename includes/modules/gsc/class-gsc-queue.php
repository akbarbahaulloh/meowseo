<?php
/**
 * GSC Queue class
 *
 * Manages queue of pending Google API requests with exponential backoff retry logic.
 * Handles job enqueueing, duplicate checking, batch processing, and retry scheduling.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\GSC
 */

namespace MeowSEO\Modules\GSC;

use MeowSEO\Options;
use MeowSEO\Helpers\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * GSC_Queue class
 *
 * Implements queue-based processing for Google Search Console API requests.
 */
class GSC_Queue {

	/**
	 * Maximum number of jobs to process per batch.
	 */
	private const MAX_BATCH_SIZE = 10;

	/**
	 * Retry delay multiplier for exponential backoff.
	 */
	private const RETRY_MULTIPLIER = 2;

	/**
	 * Base retry delay in seconds.
	 */
	private const BASE_RETRY_DELAY = 60;

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * GSC API instance (nullable until task 12).
	 *
	 * @var GSC_API|null
	 */
	private ?GSC_API $api;

	/**
	 * Constructor.
	 *
	 * @param Options      $options Options instance.
	 * @param GSC_API|null $api     GSC API instance (optional until task 12).
	 */
	public function __construct( Options $options, ?GSC_API $api = null ) {
		$this->options = $options;
		$this->api     = $api;
	}

	/**
	 * Enqueue a job in the queue.
	 *
	 * Inserts a new job into the meowseo_gsc_queue table if no duplicate pending job exists.
	 * Requirement 10.1: Enqueue API requests in meowseo_gsc_queue database table.
	 * Requirement 10.2: Check whether an identical pending job exists before inserting to avoid duplicates.
	 *
	 * @param string $url      URL to process.
	 * @param string $job_type Job type ('indexing', 'inspection', 'analytics').
	 * @return bool True on success, false on failure or duplicate.
	 */
	public function enqueue( string $url, string $job_type ): bool {
		// Validate job type.
		$valid_types = [ 'indexing', 'inspection', 'analytics' ];
		if ( ! in_array( $job_type, $valid_types, true ) ) {
			Logger::warning(
				'Invalid job type for GSC queue',
				[
					'module'   => 'gsc',
					'job_type' => $job_type,
					'url'      => $url,
				]
			);
			return false;
		}

		// Check for duplicate pending job.
		if ( $this->check_duplicate( $url, $job_type ) ) {
			Logger::debug(
				'Duplicate GSC job not enqueued',
				[
					'module'   => 'gsc',
					'job_type' => $job_type,
					'url'      => $url,
				]
			);
			return false;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';

		// Prepare payload.
		$payload = wp_json_encode( [ 'url' => $url ] );

		if ( false === $payload ) {
			Logger::error(
				'Failed to encode GSC job payload',
				[
					'module'   => 'gsc',
					'job_type' => $job_type,
					'url'      => $url,
				]
			);
			return false;
		}

		// Insert job into queue.
		$result = $wpdb->insert(
			$table,
			[
				'job_type' => $job_type,
				'payload'  => $payload,
				'status'   => 'pending',
				'attempts' => 0,
			],
			[ '%s', '%s', '%s', '%d' ]
		);

		if ( false === $result ) {
			Logger::error(
				'Failed to insert GSC job into queue',
				[
					'module'   => 'gsc',
					'job_type' => $job_type,
					'url'      => $url,
					'error'    => $wpdb->last_error,
				]
			);
			return false;
		}

		Logger::info(
			'GSC job enqueued successfully',
			[
				'module'   => 'gsc',
				'job_type' => $job_type,
				'url'      => $url,
				'job_id'   => $wpdb->insert_id,
			]
		);

		return true;
	}

	/**
	 * Check if a duplicate pending job exists.
	 *
	 * Queries the queue for an identical pending job with the same URL and job type.
	 * Requirement 10.2: Check whether an identical pending job exists before inserting to avoid duplicates.
	 *
	 * @param string $url      URL to check.
	 * @param string $job_type Job type to check.
	 * @return bool True if duplicate exists, false otherwise.
	 */
	public function check_duplicate( string $url, string $job_type ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';

		// Prepare payload for comparison.
		$payload = wp_json_encode( [ 'url' => $url ] );

		if ( false === $payload ) {
			return false;
		}

		// Query for existing pending job.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE job_type = %s AND payload = %s AND status = 'pending'",
				$job_type,
				$payload
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Process a batch of pending jobs.
	 *
	 * Queries up to MAX_BATCH_SIZE pending jobs and processes them via the GSC API.
	 * Handles rate limits with exponential backoff, success responses, and error responses.
	 * Requirement 10.3: Process up to 10 queue entries per batch.
	 * Requirement 10.4: Update job status to processing before making any API call.
	 * Requirement 10.5: When HTTP 429 rate limit response is received, update job status to pending and set retry_after.
	 * Requirement 10.6: When successful API response is received, update job status to done and store response data.
	 *
	 * @return void
	 */
	public function process_batch(): void {
		// Check if API is available.
		if ( null === $this->api ) {
			Logger::debug(
				'GSC API not available, skipping batch processing',
				[
					'module' => 'gsc',
				]
			);
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';

		// Query pending jobs ready for processing.
		$jobs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = 'pending' AND (retry_after IS NULL OR retry_after <= NOW()) ORDER BY created_at ASC LIMIT %d",
				self::MAX_BATCH_SIZE
			),
			ARRAY_A
		);

		if ( empty( $jobs ) ) {
			Logger::debug(
				'No pending GSC jobs to process',
				[
					'module' => 'gsc',
				]
			);
			return;
		}

		Logger::info(
			'Processing GSC job batch',
			[
				'module'    => 'gsc',
				'job_count' => count( $jobs ),
			]
		);

		// Process each job.
		foreach ( $jobs as $job ) {
			$this->process_job( $job );
		}

		// Schedule next batch if pending jobs remain.
		$this->schedule_next_batch();
	}

	/**
	 * Process a single job.
	 *
	 * Updates status to processing, makes API call, and handles response.
	 *
	 * @param array $job Job data from database.
	 * @return void
	 */
	private function process_job( array $job ): void {
		global $wpdb;

		$table  = $wpdb->prefix . 'meowseo_gsc_queue';
		$job_id = (int) $job['id'];

		// Update status to processing.
		$wpdb->update(
			$table,
			[ 'status' => 'processing' ],
			[ 'id' => $job_id ],
			[ '%s' ],
			[ '%d' ]
		);

		// Decode payload.
		$payload = json_decode( $job['payload'], true );

		if ( ! $payload || ! isset( $payload['url'] ) ) {
			Logger::error(
				'Invalid GSC job payload',
				[
					'module' => 'gsc',
					'job_id' => $job_id,
				]
			);

			// Mark as failed.
			$wpdb->update(
				$table,
				[
					'status'       => 'failed',
					'processed_at' => current_time( 'mysql' ),
				],
				[ 'id' => $job_id ],
				[ '%s', '%s' ],
				[ '%d' ]
			);

			return;
		}

		$url      = $payload['url'];
		$job_type = $job['job_type'];

		// Make API call based on job type.
		$response = $this->make_api_call( $job_type, $url );

		// Handle response.
		$this->handle_response( $job_id, $job, $response );
	}

	/**
	 * Make API call based on job type.
	 *
	 * @param string $job_type Job type.
	 * @param string $url      URL to process.
	 * @return array API response.
	 */
	private function make_api_call( string $job_type, string $url ): array {
		// Check if API is available.
		if ( null === $this->api ) {
			return [
				'success'   => false,
				'http_code' => 0,
				'data'      => null,
			];
		}

		// Call appropriate API method based on job type.
		switch ( $job_type ) {
			case 'indexing':
				return $this->api->submit_for_indexing( $url );

			case 'inspection':
				return $this->api->inspect_url( $url );

			case 'analytics':
				// Analytics requires additional parameters, skip for now.
				Logger::warning(
					'Analytics job type not yet supported in queue processing',
					[
						'module' => 'gsc',
						'url'    => $url,
					]
				);
				return [
					'success'   => false,
					'http_code' => 0,
					'data'      => null,
				];

			default:
				Logger::error(
					'Unknown job type in queue',
					[
						'module'   => 'gsc',
						'job_type' => $job_type,
						'url'      => $url,
					]
				);
				return [
					'success'   => false,
					'http_code' => 0,
					'data'      => null,
				];
		}
	}

	/**
	 * Handle API response.
	 *
	 * Updates job status based on response: done, pending (rate limit), or failed.
	 * Requirement 10.5: When HTTP 429 rate limit response is received, update job status to pending and set retry_after.
	 * Requirement 10.6: When successful API response is received, update job status to done and store response data.
	 *
	 * @param int   $job_id  Job ID.
	 * @param array $job     Job data.
	 * @param array $response API response.
	 * @return void
	 */
	private function handle_response( int $job_id, array $job, array $response ): void {
		global $wpdb;

		$table    = $wpdb->prefix . 'meowseo_gsc_queue';
		$attempts = (int) $job['attempts'];

		// Handle HTTP 429 rate limit.
		if ( 429 === $response['http_code'] ) {
			$attempts++;
			$retry_delay = $this->calculate_retry_delay( $attempts );
			$retry_after = time() + $retry_delay;

			$wpdb->update(
				$table,
				[
					'status'      => 'pending',
					'attempts'    => $attempts,
					'retry_after' => gmdate( 'Y-m-d H:i:s', $retry_after ),
				],
				[ 'id' => $job_id ],
				[ '%s', '%d', '%s' ],
				[ '%d' ]
			);

			Logger::warning(
				'GSC API rate limit hit, job rescheduled',
				[
					'module'      => 'gsc',
					'job_id'      => $job_id,
					'attempts'    => $attempts,
					'retry_after' => $retry_after,
					'retry_delay' => $retry_delay,
				]
			);

			return;
		}

		// Handle success.
		if ( $response['success'] ) {
			$wpdb->update(
				$table,
				[
					'status'       => 'done',
					'processed_at' => current_time( 'mysql' ),
				],
				[ 'id' => $job_id ],
				[ '%s', '%s' ],
				[ '%d' ]
			);

			Logger::info(
				'GSC job completed successfully',
				[
					'module' => 'gsc',
					'job_id' => $job_id,
				]
			);

			return;
		}

		// Handle error.
		$wpdb->update(
			$table,
			[
				'status'       => 'failed',
				'processed_at' => current_time( 'mysql' ),
			],
			[ 'id' => $job_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		Logger::error(
			'GSC job failed',
			[
				'module'    => 'gsc',
				'job_id'    => $job_id,
				'http_code' => $response['http_code'],
				'error'     => $response['error'] ?? 'unknown',
			]
		);
	}

	/**
	 * Calculate retry delay using exponential backoff.
	 *
	 * Formula: BASE_RETRY_DELAY * RETRY_MULTIPLIER^attempts
	 * Example: 60 * 2^1 = 120 seconds, 60 * 2^2 = 240 seconds, etc.
	 * Requirement 10.5: Set retry_after to current time plus 60 seconds multiplied by 2 to the power of the attempts count.
	 *
	 * @param int $attempts Number of attempts.
	 * @return int Retry delay in seconds.
	 */
	public function calculate_retry_delay( int $attempts ): int {
		return self::BASE_RETRY_DELAY * ( self::RETRY_MULTIPLIER ** $attempts );
	}

	/**
	 * Schedule next batch processing if pending jobs remain.
	 *
	 * Checks if there are pending jobs and schedules a cron event to process them.
	 *
	 * @return void
	 */
	public function schedule_next_batch(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';

		// Check if pending jobs remain.
		$pending_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE status = 'pending' AND (retry_after IS NULL OR retry_after <= NOW())"
		);

		if ( (int) $pending_count > 0 ) {
			// Schedule next batch in 60 seconds if not already scheduled.
			if ( ! wp_next_scheduled( 'meowseo_gsc_process_queue' ) ) {
				wp_schedule_single_event( time() + 60, 'meowseo_gsc_process_queue' );

				Logger::debug(
					'Scheduled next GSC batch processing',
					[
						'module'        => 'gsc',
						'pending_count' => $pending_count,
					]
				);
			}
		}
	}
}
