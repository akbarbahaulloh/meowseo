<?php
/**
 * IndexNow Submission Logger
 *
 * Logs IndexNow submission attempts with timestamp, URLs, success status, and errors.
 * Maintains a history of the last 100 submissions for debugging and monitoring.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\MeowIndex;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submission Logger class
 *
 * Logs IndexNow submission attempts and maintains submission history.
 *
 * @since 1.0.0
 */
class Submission_Logger {

	/**
	 * Log option key
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const LOG_OPTION_KEY = 'meowseo_meowindex_logs';

	/**
	 * Maximum number of log entries to keep
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const MAX_LOG_ENTRIES = 100;

	/**
	 * Log a submission attempt
	 *
	 * Creates a log entry with timestamp, URLs, success status, and error message.
	 * Keeps only the last 100 entries to prevent database bloat.
	 * Requirement 5.9: Log all submission attempts with timestamp, URL, and response status.
	 *
	 * @since 1.0.0
	 * @param array                $urls    Array of URLs submitted.
	 * @param bool|\WP_Error|array $result  Submission result (true/false/WP_Error/array).
	 * @param string               $engine  Indexing engine (meowindex or google).
	 * @return bool True on success, false on failure.
	 */
	public function log( array $urls, $result, string $engine = 'meowindex' ): bool {
		// Determine success status and error message.
		$success = false;
		$error   = '';

		if ( is_wp_error( $result ) ) {
			$success = false;
			$error   = $result->get_error_message();
		} elseif ( is_array( $result ) ) {
			$success = isset( $result['success'] ) ? (bool) $result['success'] : false;
			$error   = isset( $result['error'] ) ? (string) $result['error'] : '';
		} elseif ( is_bool( $result ) ) {
			$success = $result;
		}

		// Create log entry.
		$entry = array(
			'timestamp' => current_time( 'mysql' ),
			'urls'      => $urls,
			'engine'    => $engine,
			'success'   => $success,
			'error'     => $error,
		);

		// Get current log.
		$log = $this->get_log();

		// Add new entry at the beginning (most recent first).
		array_unshift( $log, $entry );

		// Keep only last MAX_LOG_ENTRIES entries.
		if ( count( $log ) > self::MAX_LOG_ENTRIES ) {
			$log = array_slice( $log, 0, self::MAX_LOG_ENTRIES );
		}

		// Save updated log.
		return $this->save_log( $log );
	}

	/**
	 * Get submission history
	 *
	 * Retrieves log entries from WordPress options.
	 * Returns entries in reverse chronological order (most recent first).
	 * Requirement 5.9: Provide submission history view showing last 100 submissions.
	 *
	 * @since 1.0.0
	 * @param int $limit Optional. Maximum number of entries to return. Default 100.
	 * @return array Array of log entries.
	 */
	public function get_history( int $limit = 100 ): array {
		$log = $this->get_log();

		// Apply limit if specified.
		if ( $limit > 0 && count( $log ) > $limit ) {
			$log = array_slice( $log, 0, $limit );
		}

		return $log;
	}

	/**
	 * Get log
	 *
	 * Retrieves the current log from WordPress options.
	 *
	 * @since 1.0.0
	 * @return array Array of log entries.
	 */
	private function get_log(): array {
		$log = get_option( self::LOG_OPTION_KEY, array() );

		// Ensure log is an array.
		if ( ! is_array( $log ) ) {
			return array();
		}

		return $log;
	}

	/**
	 * Save log
	 *
	 * Saves the log to WordPress options.
	 *
	 * @since 1.0.0
	 * @param array $log Array of log entries to save.
	 * @return bool True on success, false on failure.
	 */
	private function save_log( array $log ): bool {
		return update_option( self::LOG_OPTION_KEY, $log );
	}

	/**
	 * Clear log
	 *
	 * Removes all log entries.
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public function clear(): bool {
		return delete_option( self::LOG_OPTION_KEY );
	}

	/**
	 * Get log size
	 *
	 * Returns the number of log entries currently stored.
	 *
	 * @since 1.0.0
	 * @return int Number of log entries.
	 */
	public function get_size(): int {
		return count( $this->get_log() );
	}
}
