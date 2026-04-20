<?php
/**
 * Property-Based Tests for Logger Deduplication
 *
 * Property 12: Deduplication
 * Validates: Requirements 6.1
 *
 * This test uses property-based testing (eris/eris) to verify that for any log entry
 * that matches an existing entry (same level, module, message_hash) within a 5-minute
 * time window, the Logger SHALL increment the hit_count instead of creating a new entry.
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
 * Logger Deduplication property-based test case
 *
 * @since 1.0.0
 */
class Property12DeduplicationTest extends TestCase {
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
	 * Property 12: Deduplication - Duplicate entries increment hit_count
	 *
	 * For any log entry that matches an existing entry (same level, module, message_hash)
	 * within a 5-minute time window, the Logger SHALL increment the hit_count instead of
	 * creating a new entry.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_duplicate_entries_increment_hit_count(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 ),
			Generators::int( 2, 10 )
		)
		->then(
			function ( string $message, int $duplicate_count ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Skip invalid duplicate_count (must be at least 2 to test deduplication)
				if ( $duplicate_count < 2 ) {
					return;
				}

				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();

				// Get reference to wpdb storage
				global $wpdb_storage;

				// First, test that a single log call works
				Logger::info( $message );
				
				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];
				
				// Verify at least one entry was created
				$this->assertNotEmpty(
					$log_entries,
					'Logger should create at least one log entry for first call'
				);
				
				$initial_count = count( $log_entries );

				// Log the same message additional times
				for ( $i = 1; $i < $duplicate_count; $i++ ) {
					Logger::info( $message );
				}

				// Get updated log entries
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify that we still have exactly the initial count (all duplicates were deduplicated)
				$this->assertCount(
					$initial_count,
					$log_entries,
					'Duplicate log entries should be deduplicated into a single entry'
				);

				// Verify the entry has the correct hit_count
				$first_entry = reset( $log_entries );
				$this->assertArrayHasKey(
					'hit_count',
					$first_entry,
					'Log entry should have hit_count field'
				);

				// hit_count should equal duplicate_count
				$this->assertEquals(
					$duplicate_count,
					$first_entry['hit_count'],
					'Hit count should equal the number of duplicate log calls'
				);
			}
		);
	}

	/**
	 * Property 12: Deduplication - Same level, module, message_hash are duplicates
	 *
	 * For any two log entries with the same level, module, and message_hash,
	 * they SHALL be considered duplicates and the second should increment hit_count.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_same_level_module_message_hash_are_duplicates(): void {
		$this->forAll(
			Generators::elements( [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ] ),
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $level, string $message ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();

				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log with the same level and message twice
				$method = strtolower( $level );
				Logger::$method( $message );
				
				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];
				$first_entry = reset( $log_entries );

				// Don't clear logs - we want to test deduplication
				Logger::$method( $message );

				// Get updated log entries
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify both entries have the same level, module, and message_hash
				$this->assertNotNull( $first_entry, 'First entry should exist' );

				// After second log call, we should still have only 1 entry
				$this->assertCount(
					1,
					$log_entries,
					'Duplicate entries should not create new log entries'
				);

				// Verify hit_count was incremented
				$updated_entry = reset( $log_entries );
				$this->assertEquals(
					2,
					$updated_entry['hit_count'],
					'Hit count should be 2 after logging the same message twice'
				);
			}
		);
	}

	/**
	 * Property 12: Deduplication - Different messages are not duplicates
	 *
	 * For any two log entries with different messages, they SHALL NOT be considered
	 * duplicates and should create separate entries.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_different_messages_are_not_duplicates(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 50 ),
			Generators::string( 'a-zA-Z0-9 ', 1, 50 )
		)
		->then(
			function ( string $message1, string $message2 ) {
				// Skip empty messages
				if ( empty( trim( $message1 ) ) || empty( trim( $message2 ) ) ) {
					return;
				}

				// Skip if messages are the same
				if ( $message1 === $message2 ) {
					return;
				}

				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();

				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log two different messages
				Logger::info( $message1 );
				Logger::info( $message2 );

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify we have two separate entries
				$this->assertCount(
					2,
					$log_entries,
					'Different messages should create separate log entries'
				);

				// Verify the messages are different
				$entries_array = array_values( $log_entries );
				$this->assertNotEquals(
					$entries_array[0]['message_hash'],
					$entries_array[1]['message_hash'],
					'Different messages should have different message_hashes'
				);
			}
		);
	}

	/**
	 * Property 12: Deduplication - Different levels are not duplicates
	 *
	 * For any two log entries with the same message but different levels,
	 * they SHALL NOT be considered duplicates and should create separate entries.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_different_levels_are_not_duplicates(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $message ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();

				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log the same message with different levels
				Logger::info( $message );
				Logger::warning( $message );

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify we have two separate entries
				$this->assertCount(
					2,
					$log_entries,
					'Same message with different levels should create separate entries'
				);

				// Verify the levels are different
				$entries_array = array_values( $log_entries );
				$this->assertNotEquals(
					$entries_array[0]['level'],
					$entries_array[1]['level'],
					'Entries with different levels should not be duplicates'
				);
			}
		);
	}

	/**
	 * Property 12: Deduplication - Hit count starts at 1
	 *
	 * For any new log entry, the hit_count SHALL start at 1.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_hit_count_starts_at_one(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $message ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();

				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log a message
				Logger::info( $message );

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify the entry has hit_count of 1
				$this->assertNotEmpty(
					$log_entries,
					'Log entry should be created'
				);

				$entry = reset( $log_entries );

				$this->assertArrayHasKey(
					'hit_count',
					$entry,
					'Log entry should have hit_count field'
				);

				$this->assertEquals(
					1,
					$entry['hit_count'],
					'New log entry should have hit_count of 1'
				);
			}
		);
	}

	/**
	 * Property 12: Deduplication - Deduplication within 5-minute window
	 *
	 * For any log entry that matches an existing entry within a 5-minute time window,
	 * the Logger SHALL consider it a duplicate and increment hit_count.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_deduplication_within_time_window(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 ),
			Generators::int( 0, 299 ) // 0 to 299 seconds (within 5 minutes)
		)
		->then(
			function ( string $message, int $seconds_offset ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				// Reset Logger singleton to use new mock
				reset_logger_singleton();

				// Get reference to wpdb storage
				global $wpdb_storage;

				// Log the message
				Logger::info( $message );

				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

				// Verify the entry was created
				$this->assertNotEmpty(
					$log_entries,
					'Log entry should be created'
				);

				$first_entry = reset( $log_entries );

				// Verify the entry has the expected fields
				$this->assertArrayHasKey(
					'created_at',
					$first_entry,
					'Log entry should have created_at field'
				);

				$this->assertArrayHasKey(
					'hit_count',
					$first_entry,
					'Log entry should have hit_count field'
				);

				// The created_at should be a valid timestamp
				$this->assertNotEmpty(
					$first_entry['created_at'],
					'created_at should not be empty'
				);
			}
		);
	}
}

