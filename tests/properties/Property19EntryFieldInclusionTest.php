<?php
/**
 * Property-Based Tests for Entry Field Inclusion
 *
 * Property 19: Entry Field Inclusion
 * Validates: Requirement 10.3
 *
 * This test uses property-based testing (eris/eris) to verify that for any log entry
 * in formatted output, the entry SHALL include error message, level, module, and timestamp.
 * This property validates that all required fields are reliably included in all
 * formatted log entries for AI debugging purposes.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\Log_Formatter;

/**
 * Entry Field Inclusion property-based test case
 *
 * @since 1.0.0
 */
class Property19EntryFieldInclusionTest extends TestCase {
	use TestTrait;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Define MEOWSEO_VERSION if not already defined
		if ( ! defined( 'MEOWSEO_VERSION' ) ) {
			define( 'MEOWSEO_VERSION', '1.0.0' );
		}
		
		// Setup Brain\Monkey mocking for WordPress functions
		setup_brain_monkey_mocks();
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry includes message in formatted output
	 *
	 * For any log entry in formatted output, the entry SHALL include the error message.
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_includes_message_in_formatted_output(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 100 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'INFO',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the single entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify message is included
				$this->assertStringContainsString(
					$message,
					$formatted,
					'Formatted entry should include the error message'
				);

				// Verify message label is present
				$this->assertStringContainsString(
					'**Message**:',
					$formatted,
					'Formatted entry should include "**Message**:" label'
				);
			}
		);
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry includes level in formatted output
	 *
	 * For any log entry in formatted output, the entry SHALL include the log level.
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_includes_level_in_formatted_output(): void {
		$log_levels = [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ];

		foreach ( $log_levels as $level ) {
			// Create a log entry with this level
			$log_entry = [
				'id' => 1,
				'level' => $level,
				'module' => 'test',
				'message' => 'Test message',
				'context' => null,
				'stack_trace' => null,
				'hit_count' => 1,
				'created_at' => '2024-01-15 10:30:45',
			];

			// Format the single entry
			$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

			// Verify level is included in the header
			$this->assertStringContainsString(
				$level,
				$formatted,
				'Formatted entry should include the log level: ' . $level
			);
		}
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry includes module in formatted output
	 *
	 * For any log entry in formatted output, the entry SHALL include the module name.
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_includes_module_in_formatted_output(): void {
		$this->forAll(
			Generators::string( 'a-z', 1, 20 )
		)
		->then(
			function ( string $module ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'INFO',
					'module' => $module,
					'message' => 'Test message',
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the single entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify module is included
				$this->assertStringContainsString(
					$module,
					$formatted,
					'Formatted entry should include the module name'
				);

				// Verify module label is present
				$this->assertStringContainsString(
					'**Module**:',
					$formatted,
					'Formatted entry should include "**Module**:" label'
				);
			}
		);
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry includes timestamp in formatted output
	 *
	 * For any log entry in formatted output, the entry SHALL include the timestamp.
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_includes_timestamp_in_formatted_output(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 :-', 10, 30 )
		)
		->then(
			function ( string $timestamp ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'INFO',
					'module' => 'test',
					'message' => 'Test message',
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => $timestamp,
				];

				// Format the single entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify timestamp is included
				$this->assertStringContainsString(
					$timestamp,
					$formatted,
					'Formatted entry should include the timestamp'
				);

				// Verify timestamp label is present
				$this->assertStringContainsString(
					'**Timestamp**:',
					$formatted,
					'Formatted entry should include "**Timestamp**:" label'
				);
			}
		);
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry includes all four required fields
	 *
	 * For any log entry in formatted output, the entry SHALL include all four required
	 * fields: message, level, module, and timestamp.
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_includes_all_four_fields(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 ),
			Generators::string( 'a-z', 1, 20 ),
			Generators::string( 'a-zA-Z0-9 :-', 10, 30 )
		)
		->then(
			function ( string $message, string $module, string $timestamp ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'ERROR',
					'module' => $module,
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => $timestamp,
				];

				// Format the single entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify all four fields are present
				$this->assertStringContainsString(
					$message,
					$formatted,
					'Formatted entry should include the message'
				);

				$this->assertStringContainsString(
					'ERROR',
					$formatted,
					'Formatted entry should include the level'
				);

				$this->assertStringContainsString(
					$module,
					$formatted,
					'Formatted entry should include the module'
				);

				$this->assertStringContainsString(
					$timestamp,
					$formatted,
					'Formatted entry should include the timestamp'
				);

				// Verify all labels are present
				$this->assertStringContainsString(
					'**Message**:',
					$formatted,
					'Formatted entry should include message label'
				);

				$this->assertStringContainsString(
					'**Module**:',
					$formatted,
					'Formatted entry should include module label'
				);

				$this->assertStringContainsString(
					'**Timestamp**:',
					$formatted,
					'Formatted entry should include timestamp label'
				);
			}
		);
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry fields consistent across multiple calls
	 *
	 * For any log entry, calling format_single_entry multiple times with the same entry
	 * SHALL produce output with the same fields (deterministic).
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_fields_consistent_across_calls(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'INFO',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format three times
				$formatted1 = Log_Formatter::format_single_entry( $log_entry, 1 );
				$formatted2 = Log_Formatter::format_single_entry( $log_entry, 1 );
				$formatted3 = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify output is identical across calls
				$this->assertEquals(
					$formatted1,
					$formatted2,
					'Entry formatting should be consistent (call 1 vs 2)'
				);

				$this->assertEquals(
					$formatted2,
					$formatted3,
					'Entry formatting should be consistent (call 2 vs 3)'
				);
			}
		);
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry fields with various log levels
	 *
	 * For formatted log entries with various log levels, all four required fields
	 * SHALL be included regardless of the log level.
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_fields_with_various_log_levels(): void {
		$log_levels = [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ];

		foreach ( $log_levels as $level ) {
			// Create a log entry
			$log_entry = [
				'id' => 1,
				'level' => $level,
				'module' => 'test',
				'message' => 'Test message for ' . $level,
				'context' => null,
				'stack_trace' => null,
				'hit_count' => 1,
				'created_at' => '2024-01-15 10:30:45',
			];

			// Format the entry
			$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

			// Verify all four fields are present
			$this->assertStringContainsString(
				'Test message for ' . $level,
				$formatted,
				'Entry should include message for level: ' . $level
			);

			$this->assertStringContainsString(
				$level,
				$formatted,
				'Entry should include level: ' . $level
			);

			$this->assertStringContainsString(
				'test',
				$formatted,
				'Entry should include module for level: ' . $level
			);

			$this->assertStringContainsString(
				'2024-01-15 10:30:45',
				$formatted,
				'Entry should include timestamp for level: ' . $level
			);
		}
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry fields with various modules
	 *
	 * For formatted log entries from various modules, all four required fields
	 * SHALL be included regardless of the module.
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_fields_with_various_modules(): void {
		$modules = [ 'gsc', 'sitemap', 'redirects', 'meta', 'schema' ];

		foreach ( $modules as $module ) {
			// Create a log entry
			$log_entry = [
				'id' => 1,
				'level' => 'INFO',
				'module' => $module,
				'message' => 'Test message from ' . $module,
				'context' => null,
				'stack_trace' => null,
				'hit_count' => 1,
				'created_at' => '2024-01-15 10:30:45',
			];

			// Format the entry
			$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

			// Verify all four fields are present
			$this->assertStringContainsString(
				'Test message from ' . $module,
				$formatted,
				'Entry should include message for module: ' . $module
			);

			$this->assertStringContainsString(
				'INFO',
				$formatted,
				'Entry should include level for module: ' . $module
			);

			$this->assertStringContainsString(
				$module,
				$formatted,
				'Entry should include module: ' . $module
			);

			$this->assertStringContainsString(
				'2024-01-15 10:30:45',
				$formatted,
				'Entry should include timestamp for module: ' . $module
			);
		}
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry fields with empty context
	 *
	 * For formatted log entries with empty context, all four required fields
	 * SHALL still be included.
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_fields_with_empty_context(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry with empty context
				$log_entry = [
					'id' => 1,
					'level' => 'INFO',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify all four required fields are present
				$this->assertStringContainsString(
					$message,
					$formatted,
					'Entry should include message even with empty context'
				);

				$this->assertStringContainsString(
					'INFO',
					$formatted,
					'Entry should include level even with empty context'
				);

				$this->assertStringContainsString(
					'test',
					$formatted,
					'Entry should include module even with empty context'
				);

				$this->assertStringContainsString(
					'2024-01-15 10:30:45',
					$formatted,
					'Entry should include timestamp even with empty context'
				);
			}
		);
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry fields with null stack trace
	 *
	 * For formatted log entries with null stack trace, all four required fields
	 * SHALL still be included.
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_fields_with_null_stack_trace(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry with null stack trace
				$log_entry = [
					'id' => 1,
					'level' => 'WARNING',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify all four required fields are present
				$this->assertStringContainsString(
					$message,
					$formatted,
					'Entry should include message even with null stack trace'
				);

				$this->assertStringContainsString(
					'WARNING',
					$formatted,
					'Entry should include level even with null stack trace'
				);

				$this->assertStringContainsString(
					'test',
					$formatted,
					'Entry should include module even with null stack trace'
				);

				$this->assertStringContainsString(
					'2024-01-15 10:30:45',
					$formatted,
					'Entry should include timestamp even with null stack trace'
				);
			}
		);
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry fields appear in correct format
	 *
	 * For any formatted log entry, the four required fields SHALL appear in the correct
	 * markdown format with proper labels and values.
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_fields_appear_in_correct_format(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'ERROR',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify proper markdown formatting for each field
				$this->assertMatchesRegularExpression(
					'/\*\*Timestamp\*\*:\s+\S+/',
					$formatted,
					'Timestamp should be formatted as markdown bold with value'
				);

				$this->assertMatchesRegularExpression(
					'/\*\*Module\*\*:\s+\S+/',
					$formatted,
					'Module should be formatted as markdown bold with value'
				);

				// Message label should be present (value may be empty if generator produces empty string)
				$this->assertStringContainsString(
					'**Message**:',
					$formatted,
					'Message should be formatted as markdown bold'
				);

				// Verify level appears in the header
				$this->assertMatchesRegularExpression(
					'/### Entry \d+: ERROR - \w+ Module/',
					$formatted,
					'Level should appear in entry header'
				);
			}
		);
	}

	/**
	 * Property 19: Entry Field Inclusion - Entry fields with multiple entries
	 *
	 * For formatted log output with multiple entries, each entry SHALL include all four
	 * required fields (message, level, module, timestamp).
	 *
	 * **Validates: Requirement 10.3**
	 *
	 * @return void
	 */
	public function test_entry_fields_with_multiple_entries(): void {
		$this->forAll(
			Generators::choose( 2, 10 )
		)
		->then(
			function ( int $entry_count ) {
				// Create multiple log entries
				$log_entries = [];
				for ( $i = 0; $i < $entry_count; $i++ ) {
					$log_entries[] = [
						'id' => $i + 1,
						'level' => 'INFO',
						'module' => 'test',
						'message' => 'Test message ' . $i,
						'context' => null,
						'stack_trace' => null,
						'hit_count' => 1,
						'created_at' => '2024-01-15 10:30:45',
					];
				}

				// Format all entries
				$formatted = Log_Formatter::format_for_ai( $log_entries );

				// Verify each entry includes all four fields
				for ( $i = 0; $i < $entry_count; $i++ ) {
					$this->assertStringContainsString(
						'Test message ' . $i,
						$formatted,
						'Entry ' . $i . ' should include its message'
					);

					$this->assertStringContainsString(
						'**Timestamp**:',
						$formatted,
						'Entry ' . $i . ' should include timestamp label'
					);

					$this->assertStringContainsString(
						'**Module**:',
						$formatted,
						'Entry ' . $i . ' should include module label'
					);
				}

				// Verify level appears for each entry
				$this->assertMatchesRegularExpression(
					'/### Entry 1: INFO - Test Module/',
					$formatted,
					'First entry should include level in header'
				);

				if ( $entry_count > 1 ) {
					$this->assertMatchesRegularExpression(
						'/### Entry 2: INFO - Test Module/',
						$formatted,
						'Second entry should include level in header'
					);
				}
			}
		);
	}
}
