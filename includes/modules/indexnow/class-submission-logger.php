<?php
/**
 * Submission Logger
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\IndexNow;

/**
 * Submission_Logger class
 *
 * Logs IndexNow submission attempts and results.
 */
class Submission_Logger {
	/**
	 * Log submission
	 *
	 * @param array $urls   URLs submitted.
	 * @param mixed $result Submission result.
	 * @return void
	 */
	public function log( array $urls, $result ): void {
		// Implementation will be added in task 19.1
	}

	/**
	 * Get submission history
	 *
	 * @return array Log entries.
	 */
	public function get_history(): array {
		// Implementation will be added in task 19.1
		return array();
	}
}
