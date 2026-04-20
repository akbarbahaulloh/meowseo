<?php
/**
 * Property-Based Tests for Stack Frame Formatting
 *
 * Property 21: Stack Frame Formatting
 * Validates: Requirement 10.5
 *
 * This test uses property-based testing (eris/eris) to verify that for any stack trace
 * with N frames, the formatted output SHALL include file path and line number for each
 * of the N frames. This property validates that stack frames are properly formatted with
 * all required information for debugging purposes.
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
 * Stack Frame Formatting property-based test case
 *
 * @since 1.0.0
 */
class Property21StackFrameFormattingTest extends TestCase {
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
	 * Property 21: Stack Frame Formatting - Each frame includes file path and line number
	 *
	 * For any stack trace with N frames, the formatted output SHALL include file path
	 * and line number for each of the N frames.
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_each_frame_includes_file_path_and_line_number(): void {
		$this->forAll(
			Generators::choose( 1, 10 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a stack trace with N frames
				$frames = [];
				$file_paths = [];
				$line_numbers = [];

				for ( $i = 0; $i < $frame_count; $i++ ) {
					$file_path = sprintf( '/path/to/file%d.php', $i );
					$line_number = 100 + ( $i * 10 );
					$file_paths[] = $file_path;
					$line_numbers[] = $line_number;

					$frames[] = sprintf(
						'#%d %s(%d): function%d()',
						$i,
						$file_path,
						$line_number,
						$i
					);
				}
				$stack_trace = implode( "\n", $frames );

				// Create a log entry with the stack trace
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

				// Verify each frame includes file path and line number
				for ( $i = 0; $i < $frame_count; $i++ ) {
					$this->assertStringContainsString(
						$file_paths[ $i ],
						$formatted,
						'Formatted output should include file path for frame ' . $i . ': ' . $file_paths[ $i ]
					);

					$this->assertStringContainsString(
						'(' . $line_numbers[ $i ] . ')',
						$formatted,
						'Formatted output should include line number for frame ' . $i . ': ' . $line_numbers[ $i ]
					);
				}
			}
		);
	}

	/**
	 * Property 21: Stack Frame Formatting - Frame numbers are preserved
	 *
	 * For any stack trace with N frames, the formatted output SHALL include frame
	 * numbers (#0, #1, ..., #N-1) for each frame.
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_frame_numbers_are_preserved(): void {
		$this->forAll(
			Generators::choose( 1, 10 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a stack trace with N frames
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

				// Verify each frame number is present
				for ( $i = 0; $i < $frame_count; $i++ ) {
					$this->assertStringContainsString(
						'#' . $i,
						$formatted,
						'Formatted output should include frame number #' . $i
					);
				}
			}
		);
	}

	/**
	 * Property 21: Stack Frame Formatting - Function names are preserved
	 *
	 * For any stack trace with N frames, the formatted output SHALL include the
	 * function name for each frame.
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_function_names_are_preserved(): void {
		$this->forAll(
			Generators::choose( 1, 10 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a stack trace with N frames
				$frames = [];
				$function_names = [];

				for ( $i = 0; $i < $frame_count; $i++ ) {
					$function_name = 'function' . $i;
					$function_names[] = $function_name;

					$frames[] = sprintf(
						'#%d /path/to/file%d.php(%d): %s()',
						$i,
						$i,
						100 + ( $i * 10 ),
						$function_name
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

				// Verify each function name is present
				for ( $i = 0; $i < $frame_count; $i++ ) {
					$this->assertStringContainsString(
						$function_names[ $i ] . '()',
						$formatted,
						'Formatted output should include function name for frame ' . $i . ': ' . $function_names[ $i ]
					);
				}
			}
		);
	}

	/**
	 * Property 21: Stack Frame Formatting - Various file path formats
	 *
	 * For stack traces with different file path formats, the formatted output
	 * SHALL include all file paths with their line numbers.
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_various_file_path_formats(): void {
		$file_paths = [
			'/path/to/file.php',
			'/var/www/html/wp-content/plugins/meowseo/includes/class-logger.php',
			'/home/user/project/src/Main.php',
			'C:\\Users\\User\\project\\src\\Main.php',
			'/usr/local/bin/script.php',
			'./relative/path/file.php',
		];

		foreach ( $file_paths as $file_path ) {
			// Create a stack trace with this file path
			$stack_trace = sprintf(
				'#0 %s(123): function()',
				$file_path
			);

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

			// Verify file path is included
			$this->assertStringContainsString(
				$file_path,
				$formatted,
				'Formatted output should include file path: ' . $file_path
			);

			// Verify line number is included
			$this->assertStringContainsString(
				'(123)',
				$formatted,
				'Formatted output should include line number for file path: ' . $file_path
			);
		}
	}

	/**
	 * Property 21: Stack Frame Formatting - Various line number formats
	 *
	 * For stack traces with different line numbers, the formatted output
	 * SHALL include all line numbers in the correct format.
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_various_line_number_formats(): void {
		$this->forAll(
			Generators::choose( 1, 10 ),
			Generators::choose( 1, 9999 )
		)
		->then(
			function ( int $frame_count, int $base_line_number ) {
				// Generate a stack trace with various line numbers
				$frames = [];
				$line_numbers = [];

				for ( $i = 0; $i < $frame_count; $i++ ) {
					$line_number = $base_line_number + ( $i * 10 );
					$line_numbers[] = $line_number;

					$frames[] = sprintf(
						'#%d /path/to/file%d.php(%d): function%d()',
						$i,
						$i,
						$line_number,
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

				// Verify each line number is present
				for ( $i = 0; $i < $frame_count; $i++ ) {
					$this->assertStringContainsString(
						'(' . $line_numbers[ $i ] . ')',
						$formatted,
						'Formatted output should include line number for frame ' . $i . ': ' . $line_numbers[ $i ]
					);
				}
			}
		);
	}

	/**
	 * Property 21: Stack Frame Formatting - All frames in multi-frame traces
	 *
	 * For stack traces with multiple frames, the formatted output SHALL include
	 * file path and line number for ALL frames, not just some.
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_all_frames_in_multi_frame_traces(): void {
		$this->forAll(
			Generators::choose( 3, 10 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a stack trace with multiple frames
				$frames = [];
				$expected_patterns = [];

				for ( $i = 0; $i < $frame_count; $i++ ) {
					$file_path = sprintf( '/path/to/file%d.php', $i );
					$line_number = 100 + ( $i * 10 );

					$frames[] = sprintf(
						'#%d %s(%d): function%d()',
						$i,
						$file_path,
						$line_number,
						$i
					);

					// Store expected pattern for verification
					$expected_patterns[] = [
						'file' => $file_path,
						'line' => $line_number,
					];
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

				// Verify all frames are present with file path and line number
				foreach ( $expected_patterns as $index => $pattern ) {
					$this->assertStringContainsString(
						$pattern['file'],
						$formatted,
						'Frame ' . $index . ' file path should be present: ' . $pattern['file']
					);

					$this->assertStringContainsString(
						'(' . $pattern['line'] . ')',
						$formatted,
						'Frame ' . $index . ' line number should be present: ' . $pattern['line']
					);
				}

				// Verify frame count matches
				$frame_count_in_output = substr_count( $formatted, '#' );
				$this->assertGreaterThanOrEqual(
					$frame_count,
					$frame_count_in_output,
					'Formatted output should include all ' . $frame_count . ' frames'
				);
			}
		);
	}

	/**
	 * Property 21: Stack Frame Formatting - Frame format consistency
	 *
	 * For any stack trace, the formatted output SHALL maintain consistent formatting
	 * across all frames with the pattern: #N /path/to/file.php(line): function()
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_frame_format_consistency(): void {
		$this->forAll(
			Generators::choose( 1, 10 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a stack trace
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

				// Extract the stack trace section
				preg_match( '/\*\*Stack Trace\*\*:\s*```\n(.*?)\n```/s', $formatted, $matches );
				$this->assertNotEmpty( $matches, 'Stack trace section should be present' );

				$trace_content = $matches[1];
				$trace_lines = array_filter( explode( "\n", $trace_content ) );

				// Verify each line matches the expected format
				foreach ( $trace_lines as $line ) {
					// Expected format: #N /path/to/file.php(line): function()
					$this->assertMatchesRegularExpression(
						'/^#\d+\s+.+\.php\(\d+\):\s+.+\(\)$/',
						$line,
						'Frame should match expected format: ' . $line
					);
				}
			}
		);
	}

	/**
	 * Property 21: Stack Frame Formatting - Frame order preservation
	 *
	 * For any stack trace with N frames, the formatted output SHALL preserve the
	 * order of frames (#0 before #1 before #2, etc.).
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_frame_order_preservation(): void {
		$this->forAll(
			Generators::choose( 2, 10 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a stack trace
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

				// Find positions of frame numbers
				$positions = [];
				for ( $i = 0; $i < $frame_count; $i++ ) {
					$pos = strpos( $formatted, '#' . $i );
					if ( $pos !== false ) {
						$positions[ $i ] = $pos;
					}
				}

				// Verify frame order is preserved (positions should be increasing)
				for ( $i = 1; $i < $frame_count; $i++ ) {
					$this->assertGreaterThan(
						$positions[ $i - 1 ],
						$positions[ $i ],
						'Frame #' . $i . ' should appear after frame #' . ( $i - 1 )
					);
				}
			}
		);
	}

	/**
	 * Property 21: Stack Frame Formatting - Complex function signatures
	 *
	 * For stack traces with complex function signatures (namespaces, class methods),
	 * the formatted output SHALL include the complete function signature.
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_complex_function_signatures(): void {
		$complex_traces = [
			"#0 /path/to/file.php(123): MeowSEO\\Helpers\\Logger->log()\n#1 /path/to/other.php(456): MeowSEO\\Helpers\\Logger::error()",
			"#0 /path/to/file.php(100): MyClass->method()\n#1 /path/to/file.php(200): MyClass::staticMethod()",
			"#0 /path/to/file.php(50): namespace\\Class->method()\n#1 /path/to/file.php(75): namespace\\Class::staticMethod()",
		];

		foreach ( $complex_traces as $stack_trace ) {
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

			// Verify file paths are included
			$this->assertStringContainsString(
				'/path/to/file.php',
				$formatted,
				'Formatted output should include file path'
			);

			// Verify line numbers are included
			$this->assertMatchesRegularExpression(
				'/\(\d+\)/',
				$formatted,
				'Formatted output should include line numbers'
			);

			// Verify function signatures are preserved
			$this->assertStringContainsString(
				'->',
				$formatted,
				'Formatted output should preserve method call syntax'
			);
		}
	}

	/**
	 * Property 21: Stack Frame Formatting - Single frame trace
	 *
	 * For a stack trace with a single frame, the formatted output SHALL include
	 * the file path and line number for that frame.
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_single_frame_trace(): void {
		$this->forAll(
			Generators::choose( 1, 9999 )
		)
		->then(
			function ( int $line_number ) {
				// Create a single-frame stack trace
				$stack_trace = sprintf(
					'#0 /path/to/file.php(%d): function()',
					$line_number
				);

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

				// Verify file path is included
				$this->assertStringContainsString(
					'/path/to/file.php',
					$formatted,
					'Formatted output should include file path'
				);

				// Verify line number is included
				$this->assertStringContainsString(
					'(' . $line_number . ')',
					$formatted,
					'Formatted output should include line number: ' . $line_number
				);

				// Verify frame number is included
				$this->assertStringContainsString(
					'#0',
					$formatted,
					'Formatted output should include frame number #0'
				);
			}
		);
	}

	/**
	 * Property 21: Stack Frame Formatting - Frame formatting in multiple entries
	 *
	 * For formatted log output with multiple entries, each entry's stack frames
	 * SHALL include file path and line number.
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_frame_formatting_in_multiple_entries(): void {
		$this->forAll(
			Generators::choose( 2, 5 )
		)
		->then(
			function ( int $entry_count ) {
				// Create multiple log entries with stack traces
				$log_entries = [];
				for ( $i = 0; $i < $entry_count; $i++ ) {
					$frames = [];
					for ( $j = 0; $j < 3; $j++ ) {
						$frames[] = sprintf(
							'#%d /path/to/file%d_%d.php(%d): function%d()',
							$j,
							$i,
							$j,
							100 + ( $j * 10 ),
							$j
						);
					}
					$stack_trace = implode( "\n", $frames );

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

				// Verify each entry's frames include file path and line number
				for ( $i = 0; $i < $entry_count; $i++ ) {
					for ( $j = 0; $j < 3; $j++ ) {
						$file_path = sprintf( '/path/to/file%d_%d.php', $i, $j );
						$line_number = 100 + ( $j * 10 );

						$this->assertStringContainsString(
							$file_path,
							$formatted,
							'Entry ' . $i . ' frame ' . $j . ' should include file path: ' . $file_path
						);

						$this->assertStringContainsString(
							'(' . $line_number . ')',
							$formatted,
							'Entry ' . $i . ' frame ' . $j . ' should include line number: ' . $line_number
						);
					}
				}
			}
		);
	}

	/**
	 * Property 21: Stack Frame Formatting - Deterministic formatting
	 *
	 * For any stack trace, calling format_single_entry multiple times SHALL produce
	 * identical output (deterministic formatting).
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_deterministic_frame_formatting(): void {
		$this->forAll(
			Generators::choose( 1, 10 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a stack trace
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

				// Format three times
				$formatted1 = Log_Formatter::format_single_entry( $log_entry, 1 );
				$formatted2 = Log_Formatter::format_single_entry( $log_entry, 1 );
				$formatted3 = Log_Formatter::format_single_entry( $log_entry, 1 );

				// Verify output is identical
				$this->assertEquals(
					$formatted1,
					$formatted2,
					'Frame formatting should be deterministic (call 1 vs 2)'
				);

				$this->assertEquals(
					$formatted2,
					$formatted3,
					'Frame formatting should be deterministic (call 2 vs 3)'
				);
			}
		);
	}

	/**
	 * Property 21: Stack Frame Formatting - Frame count accuracy
	 *
	 * For any stack trace with N frames, the formatted output SHALL contain exactly
	 * N frame numbers (#0 through #N-1).
	 *
	 * **Validates: Requirement 10.5**
	 *
	 * @return void
	 */
	public function test_frame_count_accuracy(): void {
		$this->forAll(
			Generators::choose( 1, 10 )
		)
		->then(
			function ( int $frame_count ) {
				// Generate a stack trace with exactly N frames
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

				// Count frame numbers in output
				$frame_count_in_output = 0;
				for ( $i = 0; $i < $frame_count; $i++ ) {
					if ( strpos( $formatted, '#' . $i ) !== false ) {
						$frame_count_in_output++;
					}
				}

				// Verify frame count matches
				$this->assertEquals(
					$frame_count,
					$frame_count_in_output,
					'Formatted output should contain exactly ' . $frame_count . ' frames'
				);
			}
		);
	}
}
