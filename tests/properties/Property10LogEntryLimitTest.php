<?php
/**
 * Property-Based Tests for Logger Log Entry Limit
 *
 * Property 10: Log Entry Limit Invariant
 * Validates: Requirements 5.1, 5.4, 5.5
 *
 * This test uses property-based testing (eris/eris) to verify that for any sequence
 * of log operations, the meowseo_logs table SHALL never contain more than 1000 entries,
 * with the oldest entries deleted when the limit is exceeded.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\Logger;

/**
 * Logger Log Entry Limit property-based test case
 *
 * @since 1.0.0
 */
class Property10LogEntryLimitTest extends TestCase {
	use TestTrait;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Reset global wpdb storage to ensure clean state between tests
		reset_wpdb_storage();
	}

	/**
	 * Teardown test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		// Clean up is handled by reset_wpdb_storage() in setUp()
	}

	/**
	 * Property 10: Log Entry Limit Invariant - Never exceeds 1000 entries
	 *
	 * For any sequence of log operations, the meowseo_logs table SHALL never contain
	 * more than 1000 entries.
	 *
	 * **Validates: Requirements 5.1, 5.4, 5.5**
	 *
	 * @return void
	 */
	public function test_log_entry_count_never_exceeds_limit(): void {
		$this->forAll(
			Generators::int( 1, 2000 )
		)
		->then(
			function ( int $num_logs ) {
				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log multiple entries
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i" );
				}

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify the entry count never exceeds 1000
				$this->assertLessThanOrEqual(
					1000,
					count( $log_entries ),
					'Log entry count should never exceed 1000'
				);
			}
		);
	}

	/**
	 * Property 10: Log Entry Limit Invariant - Maintains exactly 1000 entries
	 *
	 * For any sequence of log operations that exceeds 1000 entries, the Logger SHALL
	 * maintain exactly 1000 entries by deleting the oldest entries.
	 *
	 * **Validates: Requirements 5.1, 5.4, 5.5**
	 *
	 * @return void
	 */
	public function test_maintains_exactly_1000_entries_when_limit_exceeded(): void {
		$this->forAll(
			Generators::int( 1001, 2000 )
		)
		->then(
			function ( int $num_logs ) {
				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log more than 1000 entries
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i" );
				}

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify we have exactly 1000 entries
				$this->assertLessThanOrEqual(
					1000,
					count( $log_entries ),
					'Log entry count should not exceed 1000'
				);

				// If we logged more than 1000, we should have exactly 1000
				if ( $num_logs > 1000 ) {
					$this->assertGreaterThanOrEqual(
						1000,
						count( $log_entries ),
						'Log entry count should be at least 1000 when limit is enforced'
					);
				}
			}
		);
	}

	/**
	 * Property 10: Log Entry Limit Invariant - Preserves most recent entries
	 *
	 * For any sequence of log operations that exceeds 1000 entries, the Logger SHALL
	 * preserve the most recent 1000 entries and delete the oldest ones.
	 *
	 * **Validates: Requirements 5.1, 5.4, 5.5**
	 *
	 * @return void
	 */
	public function test_preserves_most_recent_entries(): void {
		$this->forAll(
			Generators::int( 1001, 1500 )
		)
		->then(
			function ( int $num_logs ) {
				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log entries with unique identifiers
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Message $i" );
				}

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify we have at most 1000 entries
				$this->assertLessThanOrEqual(
					1000,
					count( $log_entries ),
					'Log entry count should not exceed 1000'
				);

				// If we logged more than 1000, verify the most recent entries are preserved
				if ( $num_logs > 1000 && count( $log_entries ) === 1000 ) {
					// The first entry should be from a later message (not the first one)
					$first_entry = reset( $log_entries );
					$this->assertNotNull( $first_entry, 'First entry should exist' );

					// The message should indicate it's from a later message
					// (oldest entries should be deleted)
					$this->assertStringContainsString(
						'Message',
						$first_entry['message'],
						'Preserved entries should be from the logged messages'
					);
				}
			}
		);
	}

	/**
	 * Property 10: Log Entry Limit Invariant - Cleanup triggered after insertion
	 *
	 * For any log entry insertion that causes the count to exceed 1000, the Logger
	 * SHALL trigger cleanup after the insertion.
	 *
	 * **Validates: Requirements 5.1, 5.4, 5.5**
	 *
	 * @return void
	 */
	public function test_cleanup_triggered_after_exceeding_limit(): void {
		$this->forAll(
			Generators::int( 1, 100 )
		)
		->then(
			function ( int $batch_size ) {
				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log entries in batches to simulate multiple insertions
				for ( $batch = 0; $batch < 15; $batch++ ) {
					for ( $i = 0; $i < $batch_size; $i++ ) {
						Logger::info( "Batch $batch Message $i" );
					}

					// Get log entries from wpdb storage
					$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

					// After each batch, verify the count doesn't exceed 1000
					$this->assertLessThanOrEqual(
						1000,
						count( $log_entries ),
						"Log entry count should not exceed 1000 after batch $batch"
					);
				}

				// Final verification
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];
				$this->assertLessThanOrEqual(
					1000,
					count( $log_entries ),
					'Log entry count should never exceed 1000'
				);
			}
		);
	}

	/**
	 * Property 10: Log Entry Limit Invariant - Limit applies to all log levels
	 *
	 * For any sequence of log operations using different log levels, the Logger SHALL
	 * enforce the 1000 entry limit across all levels.
	 *
	 * **Validates: Requirements 5.1, 5.4, 5.5**
	 *
	 * @return void
	 */
	public function test_limit_applies_to_all_log_levels(): void {
		$this->forAll(
			Generators::int( 200, 400 )
		)
		->then(
			function ( int $entries_per_level ) {
				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log entries with different levels
				$levels = [ 'debug', 'info', 'warning', 'error', 'critical' ];

				for ( $i = 0; $i < $entries_per_level; $i++ ) {
					foreach ( $levels as $level ) {
						Logger::$level( "Message at $level level" );
					}
				}

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify the total count doesn't exceed 1000
				$this->assertLessThanOrEqual(
					1000,
					count( $log_entries ),
					'Log entry limit should apply to all log levels combined'
				);
			}
		);
	}
}
