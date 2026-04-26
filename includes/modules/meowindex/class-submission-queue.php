<?php
/**
 * IndexNow Submission Queue
 *
 * Manages a queue of URLs to be submitted to IndexNow API.
 * Implements batching and throttling to comply with API rate limits.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\MeowIndex;

use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submission Queue class
 *
 * Manages URL submission queue with batching and throttling.
 *
 * @since 1.0.0
 */
class Submission_Queue {

	/**
	 * Queue option key
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const QUEUE_OPTION_KEY = 'meowseo_meowindex_queue';

	/**
	 * Last submission timestamp option key
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const LAST_SUBMISSION_KEY = 'meowseo_meowindex_last_submission';

	/**
	 * Maximum URLs per batch
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const MAX_BATCH_SIZE = 10;

	/**
	 * Minimum delay between submissions (seconds)
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const THROTTLE_DELAY = 5;

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Add URL to queue
	 *
	 * Adds a URL to the submission queue, avoiding duplicates.
	 * Requirement 5.7: Implement request throttling with minimum 5-second delay.
	 *
	 * @since 1.0.0
	 * @param string $url URL to add to queue.
	 * @return bool True if added, false if already in queue.
	 */
	public function add( string $url ): bool {
		// Validate URL.
		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		// Get current queue.
		$queue = $this->get_queue();

		// Check if URL already in queue (avoid duplicates).
		if ( in_array( $url, $queue, true ) ) {
			return false;
		}

		// Add URL to queue.
		$queue[] = $url;

		// Save updated queue.
		return $this->save_queue( $queue );
	}

	/**
	 * Process queue
	 *
	 * Processes queued URLs by submitting batches to IndexNow API.
	 * Implements throttling to enforce 5-second minimum delay between submissions.
	 * Requirements 5.7, 5.8: Batch submissions with throttling.
	 *
	 * @since 1.0.0
	 * @return array Result array with success status and message.
	 */
	public function process(): array {
		// Check if throttling is needed.
		if ( $this->should_throttle() ) {
			return array(
				'success' => false,
				'message' => __( 'Throttling: waiting for minimum delay between submissions', 'meowseo' ),
			);
		}

		// Get current queue.
		$queue = $this->get_queue();

		// Check if queue is empty.
		if ( empty( $queue ) ) {
			return array(
				'success' => true,
				'message' => __( 'Queue is empty', 'meowseo' ),
			);
		}

		// Get batch of URLs (up to MAX_BATCH_SIZE).
		$batch = array_slice( $queue, 0, self::MAX_BATCH_SIZE );

		// Remove batch from queue.
		$remaining_queue = array_slice( $queue, self::MAX_BATCH_SIZE );
		$this->save_queue( $remaining_queue );

		// Update last submission timestamp.
		$this->update_last_submission_time();

		// Return batch for submission.
		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of URLs */
				__( 'Processing batch of %d URLs', 'meowseo' ),
				count( $batch )
			),
			'urls'    => $batch,
		);
	}

	/**
	 * Check if throttling is needed
	 *
	 * Checks if minimum delay has passed since last submission.
	 * Requirement 5.8: Enforce 5-second minimum delay between submissions.
	 *
	 * @since 1.0.0
	 * @return bool True if should throttle, false otherwise.
	 */
	public function should_throttle(): bool {
		$last_submission = get_option( self::LAST_SUBMISSION_KEY, 0 );

		// If no previous submission, no throttling needed.
		if ( empty( $last_submission ) ) {
			return false;
		}

		// Calculate time since last submission.
		$time_since_last = time() - (int) $last_submission;

		// Throttle if less than THROTTLE_DELAY seconds have passed.
		return $time_since_last < self::THROTTLE_DELAY;
	}

	/**
	 * Get queue
	 *
	 * Retrieves the current queue from WordPress options.
	 *
	 * @since 1.0.0
	 * @return array Array of URLs in queue.
	 */
	private function get_queue(): array {
		$queue = get_option( self::QUEUE_OPTION_KEY, array() );

		// Ensure queue is an array.
		if ( ! is_array( $queue ) ) {
			return array();
		}

		return $queue;
	}

	/**
	 * Save queue
	 *
	 * Saves the queue to WordPress options.
	 *
	 * @since 1.0.0
	 * @param array $queue Array of URLs to save.
	 * @return bool True on success, false on failure.
	 */
	private function save_queue( array $queue ): bool {
		return update_option( self::QUEUE_OPTION_KEY, $queue );
	}

	/**
	 * Update last submission time
	 *
	 * Updates the timestamp of the last submission.
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	private function update_last_submission_time(): bool {
		return update_option( self::LAST_SUBMISSION_KEY, time() );
	}

	/**
	 * Get queue size
	 *
	 * Returns the number of URLs currently in the queue.
	 *
	 * @since 1.0.0
	 * @return int Number of URLs in queue.
	 */
	public function get_size(): int {
		return count( $this->get_queue() );
	}

	/**
	 * Clear queue
	 *
	 * Removes all URLs from the queue.
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public function clear(): bool {
		return delete_option( self::QUEUE_OPTION_KEY );
	}
}
