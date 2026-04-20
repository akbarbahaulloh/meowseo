<?php
/**
 * Property-Based Tests for Stack Trace Inclusion
 *
 * Property 20: Stack Trace Inclusion
 * Validates: Requirement 10.4
 *
 * This test uses property-based testing (eris/eris) to verify that for any log entry
 * with a non-null stack_trace field, the formatted output SHALL include the full stack trace.
 * This property validates that stack traces are reliably included in formatted log entries
 * for debugging purposes.
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
 * Stack Trace Inclusion property-based test case
 *
 * @since 1.0.0
 */
class Property20StackTraceInclusionTest extends TestCase {
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
	 * Property 20: Stack Trace Inclusion - Stack trace included when present
	 *
	 * For any log entry with a non-null stack_trace field, the formatted output
	 * SHALL include the full stack trace.
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_included_when_present(): void {
		$this->forAll(
			Generators::choose( 1, 5 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a realistic stack trace
				$frames = [];
				for ( $i = 0; $i < $frame_count; $i++ ) {
					$frames[] = sprintf(
						'#%d /path/to/file%d.php(%d): function%d()',
						$i,
						$i,
						100 + ( $i * 10 ),
						$i
					);
				}
				$stack_trace = implode( "\n", $frames );

				// Create a log entry with a stack trace
				$log_entry = [
					'id' => 1,
					'level' => 'ERROR',
					'module' => 'test',
					'message' => 'Test error message',
					'context' => null,
					'stack_trace' => $stack_trace,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the single entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify stack trace is included
				$this->assertStringContainsString(
					$stack_trace,
					$formatted,
					'Formatted entry should include the full stack trace'
				);

				// Verify stack trace label is present
				$this->assertStringContainsString(
					'**Stack Trace**:',
					$formatted,
					'Formatted entry should include "**Stack Trace**:" label'
				);

				// Verify stack trace is in code block
				$this->assertStringContainsString(
					'```',
					$formatted,
					'Stack trace should be wrapped in code block'
				);
			}
		);
	}

	/**
	 * Property 20: Stack Trace Inclusion - Stack trace not included when null
	 *
	 * For any log entry with a null stack_trace field, the formatted output
	 * SHALL NOT include a stack trace section.
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_not_included_when_null(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry without a stack trace
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

				// Verify stack trace label is NOT included
				$this->assertStringNotContainsString(
					'**Stack Trace**:',
					$formatted,
					'Formatted entry should not include stack trace label when stack_trace is null'
				);
			}
		);
	}

	/**
	 * Property 20: Stack Trace Inclusion - Stack trace not included when empty
	 *
	 * For any log entry with an empty stack_trace field, the formatted output
	 * SHALL NOT include a stack trace section.
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_not_included_when_empty(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry with empty stack trace
				$log_entry = [
					'id' => 1,
					'level' => 'WARNING',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => '',
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the single entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify stack trace label is NOT included
				$this->assertStringNotContainsString(
					'**Stack Trace**:',
					$formatted,
					'Formatted entry should not include stack trace label when stack_trace is empty'
				);
			}
		);
	}

	/**
	 * Property 20: Stack Trace Inclusion - Stack trace with various formats
	 *
	 * For log entries with stack traces in various formats, the formatted output
	 * SHALL include the full stack trace regardless of format.
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_with_various_formats(): void {
		$stack_traces = [
			"#0 /path/to/file.php(123): function()\n#1 /path/to/other.php(456): other_function()",
			"#0 /var/www/html/wp-content/plugins/meowseo/includes/class-logger.php(100): Logger->log()\n#1 /var/www/html/wp-content/plugins/meowseo/includes/class-plugin.php(50): Plugin->init()",
			"#0 /home/user/project/src/Main.php(1): Main->execute()\n#1 /home/user/project/src/Handler.php(200): Handler->process()\n#2 /home/user/project/index.php(10): main()",
		];

		foreach ( $stack_traces as $stack_trace ) {
			// Create a log entry with this stack trace
			$log_entry = [
				'id' => 1,
				'level' => 'ERROR',
				'module' => 'test',
				'message' => 'Test error',
				'context' => null,
				'stack_trace' => $stack_trace,
				'hit_count' => 1,
				'created_at' => '2024-01-15 10:30:45',
			];

			// Format the entry
			$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

			// Verify stack trace is included
			$this->assertStringContainsString(
				$stack_trace,
				$formatted,
				'Formatted entry should include the stack trace: ' . substr( $stack_trace, 0, 50 )
			);

			// Verify stack trace label is present
			$this->assertStringContainsString(
				'**Stack Trace**:',
				$formatted,
				'Formatted entry should include stack trace label'
			);
		}
	}

	/**
	 * Property 20: Stack Trace Inclusion - Stack trace with multiple frames
	 *
	 * For log entries with stack traces containing multiple frames, the formatted output
	 * SHALL include all frames.
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_with_multiple_frames(): void {
		$this->forAll(
			Generators::choose( 1, 10 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a stack trace with multiple frames
				$frames = [];
				for ( $i = 0; $i < $frame_count; $i++ ) {
					$frames[] = sprintf(
						'#%d /path/to/file%d.php(%d): function%d()',
						$i,
						$i,
						100 + ( $i * 10 ),
						$i
					);
				}
				$stack_trace = implode( "\n", $frames );

				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'CRITICAL',
					'module' => 'test',
					'message' => 'Critical error',
					'context' => null,
					'stack_trace' => $stack_trace,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify all frames are included
				for ( $i = 0; $i < $frame_count; $i++ ) {
					$this->assertStringContainsString(
						sprintf( '#%d', $i ),
						$formatted,
						'Formatted entry should include frame #' . $i
					);

					$this->assertStringContainsString(
						sprintf( 'file%d.php', $i ),
						$formatted,
						'Formatted entry should include file path for frame ' . $i
					);
				}

				// Verify stack trace label is present
				$this->assertStringContainsString(
					'**Stack Trace**:',
					$formatted,
					'Formatted entry should include stack trace label'
				);
			}
		);
	}

	/**
	 * Property 20: Stack Trace Inclusion - Stack trace consistent across calls
	 *
	 * For any log entry with a stack trace, calling format_single_entry multiple times
	 * SHALL produce output with the same stack trace (deterministic).
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_consistent_across_calls(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 :/()_-', 50, 200 )
		)
		->then(
			function ( string $stack_trace ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'ERROR',
					'module' => 'test',
					'message' => 'Test error',
					'context' => null,
					'stack_trace' => $stack_trace,
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
					'Stack trace formatting should be consistent (call 1 vs 2)'
				);

				$this->assertEquals(
					$formatted2,
					$formatted3,
					'Stack trace formatting should be consistent (call 2 vs 3)'
				);
			}
		);
	}

	/**
	 * Property 20: Stack Trace Inclusion - Stack trace with various log levels
	 *
	 * For log entries with stack traces at various log levels, the formatted output
	 * SHALL include the stack trace regardless of the log level.
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_with_various_log_levels(): void {
		$log_levels = [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ];
		$stack_trace = "#0 /path/to/file.php(123): function()\n#1 /path/to/other.php(456): other_function()";

		foreach ( $log_levels as $level ) {
			// Create a log entry with this level
			$log_entry = [
				'id' => 1,
				'level' => $level,
				'module' => 'test',
				'message' => 'Test message for ' . $level,
				'context' => null,
				'stack_trace' => $stack_trace,
				'hit_count' => 1,
				'created_at' => '2024-01-15 10:30:45',
			];

			// Format the entry
			$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

			// Verify stack trace is included
			$this->assertStringContainsString(
				$stack_trace,
				$formatted,
				'Stack trace should be included for level: ' . $level
			);

			// Verify stack trace label is present
			$this->assertStringContainsString(
				'**Stack Trace**:',
				$formatted,
				'Stack trace label should be present for level: ' . $level
			);
		}
	}

	/**
	 * Property 20: Stack Trace Inclusion - Stack trace with context data
	 *
	 * For log entries with both stack trace and context data, the formatted output
	 * SHALL include both the context and the stack trace.
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_with_context_data(): void {
		$this->forAll(
			Generators::string( 'a-z', 1, 20 ),
			Generators::choose( 1, 3 )
		)
		->then(
			function ( string $context_key, int $frame_count ) {
				// Create context data
				$context = [
					$context_key => 'context_value',
					'error_code' => 'TEST_ERROR',
				];

				// Generate a realistic stack trace
				$frames = [];
				for ( $i = 0; $i < $frame_count; $i++ ) {
					$frames[] = sprintf(
						'#%d /path/to/file%d.php(%d): function%d()',
						$i,
						$i,
						100 + ( $i * 10 ),
						$i
					);
				}
				$stack_trace = implode( "\n", $frames );

				// Create a log entry with both context and stack trace
				$log_entry = [
					'id' => 1,
					'level' => 'ERROR',
					'module' => 'test',
					'message' => 'Test error with context',
					'context' => wp_json_encode( $context ),
					'stack_trace' => $stack_trace,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify both context and stack trace are included
				$this->assertStringContainsString(
					'**Context**:',
					$formatted,
					'Formatted entry should include context label'
				);

				$this->assertStringContainsString(
					$stack_trace,
					$formatted,
					'Formatted entry should include stack trace'
				);

				$this->assertStringContainsString(
					'**Stack Trace**:',
					$formatted,
					'Formatted entry should include stack trace label'
				);
			}
		);
	}

	/**
	 * Property 20: Stack Trace Inclusion - Stack trace in multiple entries
	 *
	 * For formatted log output with multiple entries, each entry with a stack trace
	 * SHALL include its stack trace in the formatted output.
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_in_multiple_entries(): void {
		$this->forAll(
			Generators::choose( 2, 5 )
		)
		->then(
			function ( int $entry_count ) {
				// Create multiple log entries, some with stack traces
				$log_entries = [];
				for ( $i = 0; $i < $entry_count; $i++ ) {
					$stack_trace = null;
					if ( $i % 2 === 0 ) {
						// Even entries have stack traces
						$stack_trace = sprintf(
							"#0 /path/to/file%d.php(100): function%d()\n#1 /path/to/other%d.php(200): other_function%d()",
							$i,
							$i,
							$i,
							$i
						);
					}

					$log_entries[] = [
						'id' => $i + 1,
						'level' => 'ERROR',
						'module' => 'test',
						'message' => 'Test message ' . $i,
						'context' => null,
						'stack_trace' => $stack_trace,
						'hit_count' => 1,
						'created_at' => '2024-01-15 10:30:45',
					];
				}

				// Format all entries
				$formatted = Log_Formatter::format_for_ai( $log_entries );

				// Verify each entry with stack trace includes it
				for ( $i = 0; $i < $entry_count; $i++ ) {
					if ( $i % 2 === 0 ) {
						// Even entries should have stack traces
						$expected_trace = sprintf(
							"#0 /path/to/file%d.php(100): function%d()",
							$i,
							$i
						);
						$this->assertStringContainsString(
							$expected_trace,
							$formatted,
							'Entry ' . $i . ' should include its stack trace'
						);
					}
				}

				// Verify stack trace label appears for entries with traces
				$stack_trace_count = ceil( $entry_count / 2 );
				$this->assertGreaterThanOrEqual(
					$stack_trace_count - 1,
					substr_count( $formatted, '**Stack Trace**:' ),
					'Formatted output should include stack trace labels for entries with traces'
				);
			}
		);
	}

	/**
	 * Property 20: Stack Trace Inclusion - Stack trace appears in code block
	 *
	 * For any log entry with a stack trace, the stack trace SHALL be wrapped in
	 * a markdown code block (triple backticks).
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_appears_in_code_block(): void {
		$this->forAll(
			Generators::choose( 1, 5 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a realistic stack trace
				$frames = [];
				for ( $i = 0; $i < $frame_count; $i++ ) {
					$frames[] = sprintf(
						'#%d /path/to/file%d.php(%d): function%d()',
						$i,
						$i,
						100 + ( $i * 10 ),
						$i
					);
				}
				$stack_trace = implode( "\n", $frames );

				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'ERROR',
					'module' => 'test',
					'message' => 'Test error',
					'context' => null,
					'stack_trace' => $stack_trace,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the entry
				$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify stack trace is in code block
				$this->assertMatchesRegularExpression(
					'/\*\*Stack Trace\*\*:\s*```/',
					$formatted,
					'Stack trace should be wrapped in markdown code block'
				);

				// Verify closing backticks
				$this->assertStringContainsString(
					"```\n",
					$formatted,
					'Stack trace code block should be properly closed'
				);
			}
		);
	}

	/**
	 * Property 20: Stack Trace Inclusion - Stack trace with special characters
	 *
	 * For log entries with stack traces containing special characters, the formatted output
	 * SHALL include the full stack trace with all special characters preserved.
	 *
	 * **Validates: Requirement 10.4**
	 *
	 * @return void
	 */
	public function test_stack_trace_with_special_characters(): void {
		$special_traces = [
			"#0 /path/to/file.php(123): function() [with special chars: @#$%^&*]",
			"#0 /path/to/file.php(123): function() [with quotes: \"double\" and 'single']",
			"#0 /path/to/file.php(123): function() [with brackets: {curly} [square] (paren)]",
			"#0 /path/to/file.php(123): function() [with backslash: \\path\\to\\file]",
		];

		foreach ( $special_traces as $stack_trace ) {
			// Create a log entry
			$log_entry = [
				'id' => 1,
				'level' => 'ERROR',
				'module' => 'test',
				'message' => 'Test error',
				'context' => null,
				'stack_trace' => $stack_trace,
				'hit_count' => 1,
				'created_at' => '2024-01-15 10:30:45',
			];

			// Format the entry
			$formatted = Log_Formatter::format_single_entry( $log_entry, 1 );

			// Verify stack trace is included with special characters preserved
			$this->assertStringContainsString(
				$stack_trace,
				$formatted,
				'Stack trace with special characters should be preserved: ' . substr( $stack_trace, 0, 50 )
			);
		}
	}
}
