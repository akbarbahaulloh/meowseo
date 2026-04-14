<?php
/**
 * Log_Formatter Tests
 *
 * Unit tests for the Log_Formatter class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Log_Formatter;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Log_Formatter test case
 *
 * @since 1.0.0
 */
class Test_Log_Formatter extends TestCase {

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Mock WordPress functions.
		Functions\when( 'wp_json_encode' )->alias( function( $data, $options = 0 ) {
			return json_encode( $data, $options );
		} );
		Functions\when( 'get_bloginfo' )->justReturn( '6.4.2' );
		Functions\when( 'phpversion' )->alias( 'phpversion' );

		// Define MEOWSEO_VERSION constant if not defined.
		if ( ! defined( 'MEOWSEO_VERSION' ) ) {
			define( 'MEOWSEO_VERSION', '1.0.0' );
		}
	}

	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test format_for_ai includes system information.
	 *
	 * Validates: Requirements 10.1, 18.1
	 *
	 * @return void
	 */
	public function test_format_for_ai_includes_system_information(): void {
		$log_entries = [
			[
				'id'         => 1,
				'level'      => 'ERROR',
				'module'     => 'gsc',
				'message'    => 'Test error',
				'context'    => null,
				'stack_trace' => null,
				'hit_count'  => 1,
				'created_at' => '2024-01-15 10:30:45',
			],
		];

		$output = Log_Formatter::format_for_ai( $log_entries );

		// Check system information is included.
		$this->assertStringContainsString( '# MeowSEO Debug Log Export', $output );
		$this->assertStringContainsString( '## System Information', $output );
		$this->assertStringContainsString( 'Plugin Version: 1.0.0', $output );
		$this->assertStringContainsString( 'WordPress Version: 6.4.2', $output );
		$this->assertStringContainsString( 'PHP Version:', $output );
	}

	/**
	 * Test format_for_ai includes active modules.
	 *
	 * Validates: Requirements 10.2, 18.2
	 *
	 * @return void
	 */
	public function test_format_for_ai_includes_active_modules(): void {
		$log_entries = [
			[
				'id'         => 1,
				'level'      => 'INFO',
				'module'     => 'meta',
				'message'    => 'Test message',
				'context'    => null,
				'stack_trace' => null,
				'hit_count'  => 1,
				'created_at' => '2024-01-15 10:30:45',
			],
		];

		$output = Log_Formatter::format_for_ai( $log_entries );

		// Check active modules section is included.
		$this->assertStringContainsString( 'Active Modules:', $output );
	}

	/**
	 * Test format_single_entry includes all required fields.
	 *
	 * Validates: Requirements 10.3, 18.3
	 *
	 * @return void
	 */
	public function test_format_single_entry_includes_required_fields(): void {
		$entry = [
			'id'         => 1,
			'level'      => 'ERROR',
			'module'     => 'gsc',
			'message'    => 'OAuth authentication failed',
			'context'    => null,
			'stack_trace' => null,
			'hit_count'  => 1,
			'created_at' => '2024-01-15 10:30:45',
		];

		$output = Log_Formatter::format_single_entry( $entry );

		// Check all required fields are included.
		$this->assertStringContainsString( 'Entry 1: ERROR - Gsc Module', $output );
		$this->assertStringContainsString( '**Timestamp**: 2024-01-15 10:30:45', $output );
		$this->assertStringContainsString( '**Module**: gsc', $output );
		$this->assertStringContainsString( '**Message**: OAuth authentication failed', $output );
	}

	/**
	 * Test format_single_entry includes context as JSON.
	 *
	 * Validates: Requirements 10.6, 18.4
	 *
	 * @return void
	 */
	public function test_format_single_entry_includes_context(): void {
		$context = [
			'job_type'   => 'fetch_url',
			'error_code' => 'invalid_grant',
		];

		$entry = [
			'id'         => 1,
			'level'      => 'ERROR',
			'module'     => 'gsc',
			'message'    => 'OAuth authentication failed',
			'context'    => json_encode( $context ),
			'stack_trace' => null,
			'hit_count'  => 1,
			'created_at' => '2024-01-15 10:30:45',
		];

		$output = Log_Formatter::format_single_entry( $entry );

		// Check context is included as JSON code block.
		$this->assertStringContainsString( '**Context**:', $output );
		$this->assertStringContainsString( '```json', $output );
		$this->assertStringContainsString( '"job_type": "fetch_url"', $output );
		$this->assertStringContainsString( '"error_code": "invalid_grant"', $output );
	}

	/**
	 * Test format_single_entry includes stack trace.
	 *
	 * Validates: Requirements 10.4, 10.7, 18.4
	 *
	 * @return void
	 */
	public function test_format_single_entry_includes_stack_trace(): void {
		$stack_trace = "#0 /path/to/includes/modules/gsc/class-gsc.php(123): GSC->execute_api_call()\n" .
		               "#1 /path/to/includes/modules/gsc/class-gsc.php(89): GSC->process_queue_entry()";

		$entry = [
			'id'         => 1,
			'level'      => 'ERROR',
			'module'     => 'gsc',
			'message'    => 'API call failed',
			'context'    => null,
			'stack_trace' => $stack_trace,
			'hit_count'  => 1,
			'created_at' => '2024-01-15 10:30:45',
		];

		$output = Log_Formatter::format_single_entry( $entry );

		// Check stack trace is included.
		$this->assertStringContainsString( '**Stack Trace**:', $output );
		$this->assertStringContainsString( '```', $output );
		$this->assertStringContainsString( '#0 /path/to/includes/modules/gsc/class-gsc.php(123)', $output );
		$this->assertStringContainsString( '#1 /path/to/includes/modules/gsc/class-gsc.php(89)', $output );
	}

	/**
	 * Test format_stack_trace formats file paths and line numbers.
	 *
	 * Validates: Requirements 10.5, 10.8, 18.4
	 *
	 * @return void
	 */
	public function test_format_stack_trace_formats_file_paths_and_line_numbers(): void {
		$stack_trace = "#0 /path/to/file.php(123): function_name()\n" .
		               "#1 /path/to/another.php(456): another_function()";

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( Log_Formatter::class );
		$method = $reflection->getMethod( 'format_stack_trace' );
		$method->setAccessible( true );

		$formatted = $method->invoke( null, $stack_trace );

		// Check file paths and line numbers are preserved.
		$this->assertStringContainsString( '/path/to/file.php(123)', $formatted );
		$this->assertStringContainsString( '/path/to/another.php(456)', $formatted );
		$this->assertStringContainsString( 'function_name()', $formatted );
		$this->assertStringContainsString( 'another_function()', $formatted );
	}

	/**
	 * Test parse_context returns array for valid JSON.
	 *
	 * Validates: Requirements 18.1
	 *
	 * @return void
	 */
	public function test_parse_context_returns_array_for_valid_json(): void {
		$json = json_encode( [
			'key1' => 'value1',
			'key2' => 'value2',
		] );

		$result = Log_Formatter::parse_context( $json );

		$this->assertIsArray( $result );
		$this->assertEquals( 'value1', $result['key1'] );
		$this->assertEquals( 'value2', $result['key2'] );
	}

	/**
	 * Test parse_context returns empty array for invalid JSON.
	 *
	 * Validates: Requirements 18.2
	 *
	 * @return void
	 */
	public function test_parse_context_returns_empty_array_for_invalid_json(): void {
		$invalid_json = '{ invalid json }';

		$result = Log_Formatter::parse_context( $invalid_json );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test parse_context returns empty array for empty string.
	 *
	 * Validates: Requirements 18.2
	 *
	 * @return void
	 */
	public function test_parse_context_returns_empty_array_for_empty_string(): void {
		$result = Log_Formatter::parse_context( '' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test format_single_entry handles missing fields gracefully.
	 *
	 * Validates: Requirements 18.3
	 *
	 * @return void
	 */
	public function test_format_single_entry_handles_missing_fields(): void {
		$entry = []; // Empty entry.

		$output = Log_Formatter::format_single_entry( $entry );

		// Should not throw exception and should include placeholder values.
		$this->assertStringContainsString( 'UNKNOWN', $output );
		$this->assertStringContainsString( 'unknown', $output );
		$this->assertStringContainsString( 'No message', $output );
	}

	/**
	 * Test format_single_entry includes hit count when greater than 1.
	 *
	 * Validates: Requirements 10.3
	 *
	 * @return void
	 */
	public function test_format_single_entry_includes_hit_count(): void {
		$entry = [
			'id'         => 1,
			'level'      => 'WARNING',
			'module'     => 'redirects',
			'message'    => 'Redirect loop detected',
			'context'    => null,
			'stack_trace' => null,
			'hit_count'  => 5,
			'created_at' => '2024-01-15 10:30:45',
		];

		$output = Log_Formatter::format_single_entry( $entry );

		// Check hit count is included.
		$this->assertStringContainsString( '**Hit Count**: 5', $output );
	}

	/**
	 * Test format_single_entry omits hit count when equal to 1.
	 *
	 * Validates: Requirements 10.3
	 *
	 * @return void
	 */
	public function test_format_single_entry_omits_hit_count_when_one(): void {
		$entry = [
			'id'         => 1,
			'level'      => 'INFO',
			'module'     => 'sitemap',
			'message'    => 'Sitemap generated',
			'context'    => null,
			'stack_trace' => null,
			'hit_count'  => 1,
			'created_at' => '2024-01-15 10:30:45',
		];

		$output = Log_Formatter::format_single_entry( $entry );

		// Check hit count is NOT included.
		$this->assertStringNotContainsString( '**Hit Count**:', $output );
	}

	/**
	 * Test format_for_ai handles multiple entries.
	 *
	 * Validates: Requirements 18.1
	 *
	 * @return void
	 */
	public function test_format_for_ai_handles_multiple_entries(): void {
		$log_entries = [
			[
				'id'         => 1,
				'level'      => 'ERROR',
				'module'     => 'gsc',
				'message'    => 'First error',
				'context'    => null,
				'stack_trace' => null,
				'hit_count'  => 1,
				'created_at' => '2024-01-15 10:30:45',
			],
			[
				'id'         => 2,
				'level'      => 'WARNING',
				'module'     => 'sitemap',
				'message'    => 'Second warning',
				'context'    => null,
				'stack_trace' => null,
				'hit_count'  => 1,
				'created_at' => '2024-01-15 10:31:00',
			],
			[
				'id'         => 3,
				'level'      => 'INFO',
				'module'     => 'redirects',
				'message'    => 'Third info',
				'context'    => null,
				'stack_trace' => null,
				'hit_count'  => 1,
				'created_at' => '2024-01-15 10:31:15',
			],
		];

		$output = Log_Formatter::format_for_ai( $log_entries );

		// Check all entries are included with correct numbering.
		$this->assertStringContainsString( 'Entry 1: ERROR', $output );
		$this->assertStringContainsString( 'Entry 2: WARNING', $output );
		$this->assertStringContainsString( 'Entry 3: INFO', $output );
		$this->assertStringContainsString( 'First error', $output );
		$this->assertStringContainsString( 'Second warning', $output );
		$this->assertStringContainsString( 'Third info', $output );
	}

	/**
	 * Test format_stack_trace handles malformed stack traces.
	 *
	 * Validates: Requirements 18.4
	 *
	 * @return void
	 */
	public function test_format_stack_trace_handles_malformed_traces(): void {
		$malformed_trace = "Some random text\nNot a proper stack trace\nAnother line";

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( Log_Formatter::class );
		$method = $reflection->getMethod( 'format_stack_trace' );
		$method->setAccessible( true );

		$formatted = $method->invoke( null, $malformed_trace );

		// Should include raw trace without throwing exception.
		$this->assertStringContainsString( 'Some random text', $formatted );
		$this->assertStringContainsString( 'Not a proper stack trace', $formatted );
	}

	/**
	 * Test format_stack_trace handles empty string.
	 *
	 * Validates: Requirements 18.4
	 *
	 * @return void
	 */
	public function test_format_stack_trace_handles_empty_string(): void {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( Log_Formatter::class );
		$method = $reflection->getMethod( 'format_stack_trace' );
		$method->setAccessible( true );

		$formatted = $method->invoke( null, '' );

		$this->assertEquals( '', $formatted );
	}

	/**
	 * Test parse_context handles nested arrays.
	 *
	 * Validates: Requirements 18.1
	 *
	 * @return void
	 */
	public function test_parse_context_handles_nested_arrays(): void {
		$nested_data = [
			'level1' => [
				'level2' => [
					'level3' => 'deep value',
				],
			],
		];

		$json = json_encode( $nested_data );
		$result = Log_Formatter::parse_context( $json );

		$this->assertIsArray( $result );
		$this->assertEquals( 'deep value', $result['level1']['level2']['level3'] );
	}
}
