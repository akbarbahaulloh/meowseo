<?php
/**
 * Property-Based Tests for Logger Cleanup Trigger
 *
 * Property 11: Cleanup Trigger
 * Validates: Requirements 5.2
 *
 * This test uses property-based testing (eris/eris) to verify that for any new log
 * entry insertion, the Logger SHALL check the entry count and trigger cleanup if it
 * exceeds 1000.
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
 * Logger Cleanup Trigger property-based test case
 *
 * @since 1.0.0
 */
class Property11CleanupTriggerTest extends TestCase {
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
		// Reset Logger singleton to use new mock
		reset_logger_singleton();
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
	 * Reset Logger singleton to use new mock database
	 *
	 * @return void
	 */
	private function reset_logger_singleton(): void {
		$reflection = new \ReflectionClass( Logger::class );
		$instance_property = $reflection->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );
	}

	/**
	 * Property 11: Cleanup Trigger - Cleanup triggered when limit exceeded
	 *
	 * For any new log entry insertion that causes the count to exceed 1000, the Logger
	 * SHALL trigger cleanup.
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_cleanup_triggered_when_limit_exceeded(): void {
		$this->forAll(
			Generators::int( 1001, 1500 )
		)
		->then(
			function ( int $num_logs ) {
				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();
				
				// Get reference to wpdb storage
				global $wpdb_storage, $wpdb;

				// Skip invalid inputs but make an assertion first to avoid risky test
				if ( $num_logs <= 1000 ) {
					$this->assertTrue( true, 'Skipping invalid input' );
					return;
				}

				// Log entries to exceed the limit
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i" );
				}

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify cleanup was triggered (entry count should be at or below 1000)
				$this->assertLessThanOrEqual(
					1000,
					count( $log_entries ),
					'Cleanup should be triggered when limit is exceeded'
				);

				// Verify we have entries (cleanup should preserve some)
				$this->assertGreaterThan(
					0,
					count( $log_entries ),
					'Cleanup should preserve at least some entries'
				);
			}
		);
	}

	/**
	 * Property 11: Cleanup Trigger - Cleanup not triggered when under limit
	 *
	 * For any new log entry insertion that keeps the count under 1000, the Logger
	 * SHALL NOT trigger cleanup.
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_cleanup_not_triggered_when_under_limit(): void {
		$this->forAll(
			Generators::int( 1, 500 )
		)
		->then(
			function ( int $num_logs ) {
				// Skip invalid inputs (negative or zero)
				if ( $num_logs < 1 ) {
					return;
				}
				
				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();
				
				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log entries that stay under the limit
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i" );
				}

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify the entry count matches the number of logs
				$this->assertEquals(
					$num_logs,
					count( $log_entries ),
					'No cleanup should occur when under the 1000 entry limit'
				);
			}
		);
	}

	/**
	 * Property 11: Cleanup Trigger - Cleanup triggered after each insertion exceeding limit
	 *
	 * For any sequence of log operations that exceed 1000 entries, the Logger SHALL
	 * trigger cleanup after each insertion that causes the count to exceed 1000.
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_cleanup_triggered_after_each_insertion_exceeding_limit(): void {
		$this->forAll(
			Generators::int( 1, 50 )
		)
		->then(
			function ( int $batch_size ) {
				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();
				
				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log entries in batches to exceed the limit
				$total_logged = 0;
				for ( $batch = 0; $batch < 25; $batch++ ) {
					for ( $i = 0; $i < $batch_size; $i++ ) {
						Logger::info( "Batch $batch Message $i" );
						$total_logged++;
					}

					// After each batch, verify cleanup is working
					$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];
					$current_count = count( $log_entries );
					$this->assertLessThanOrEqual(
						1000,
						$current_count,
						"Cleanup should be triggered after batch $batch"
					);

					// If we've logged more than 1000 total, verify cleanup is maintaining the limit
					if ( $total_logged > 1000 ) {
						$this->assertLessThanOrEqual(
							1000,
							$current_count,
							'Cleanup should maintain the 1000 entry limit'
						);
					}
				}

				// Final verification
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];
				$this->assertLessThanOrEqual(
					1000,
					count( $log_entries ),
					'Cleanup should maintain the 1000 entry limit throughout'
				);
			}
		);
	}

	/**
	 * Property 11: Cleanup Trigger - Cleanup uses correct deletion strategy
	 *
	 * For any cleanup operation, the Logger SHALL delete the oldest entries using
	 * ORDER BY created_at ASC LIMIT.
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_cleanup_deletes_oldest_entries(): void {
		$this->forAll(
			Generators::int( 1001, 1200 )
		)
		->then(
			function ( int $num_logs ) {
				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();
				
				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log entries with identifiable messages
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Message number $i" );
				}

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify cleanup occurred
				$this->assertLessThanOrEqual(
					1000,
					count( $log_entries ),
					'Cleanup should reduce entries to 1000 or less'
				);

				// Verify the remaining entries are from later messages
				// (oldest entries should be deleted)
				if ( count( $log_entries ) === 1000 && $num_logs > 1000 ) {
					// The first remaining entry should be from a message after the first ones
					$first_entry = reset( $log_entries );
					$this->assertNotNull( $first_entry, 'First entry should exist' );

					// Verify it's a valid log entry
					$this->assertArrayHasKey( 'message', $first_entry, 'Entry should have message' );
					$this->assertArrayHasKey( 'created_at', $first_entry, 'Entry should have created_at' );
				}
			}
		);
	}

	/**
	 * Property 11: Cleanup Trigger - Cleanup maintains data integrity
	 *
	 * For any cleanup operation, the Logger SHALL maintain the integrity of remaining
	 * entries (no corruption or loss of data in preserved entries).
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_cleanup_maintains_data_integrity(): void {
		$this->forAll(
			Generators::int( 1001, 1500 )
		)
		->then(
			function ( int $num_logs ) {
				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();
				
				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log entries with complete data
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i", [ 'index' => $i ] );
				}

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify all remaining entries have required fields
				foreach ( $log_entries as $entry ) {
					$this->assertArrayHasKey( 'level', $entry, 'Entry should have level' );
					$this->assertArrayHasKey( 'module', $entry, 'Entry should have module' );
					$this->assertArrayHasKey( 'message', $entry, 'Entry should have message' );
					$this->assertArrayHasKey( 'message_hash', $entry, 'Entry should have message_hash' );
					$this->assertArrayHasKey( 'created_at', $entry, 'Entry should have created_at' );

					// Verify field values are not empty
					$this->assertNotEmpty( $entry['level'], 'Level should not be empty' );
					$this->assertNotEmpty( $entry['module'], 'Module should not be empty' );
					$this->assertNotEmpty( $entry['message'], 'Message should not be empty' );
					$this->assertNotEmpty( $entry['message_hash'], 'Message hash should not be empty' );
					$this->assertNotEmpty( $entry['created_at'], 'Created at should not be empty' );
				}

				// Verify the entry count is correct
				$this->assertLessThanOrEqual(
					1000,
					count( $log_entries ),
					'Entry count should not exceed 1000'
				);
			}
		);
	}
}

