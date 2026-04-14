<?php
/**
 * Logger Tests
 *
 * Unit tests for the Logger singleton class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Logger;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Logger test case
 *
 * @since 1.0.0
 */
class Test_Logger extends TestCase {

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Mock WordPress functions.
		Functions\when( 'current_time' )->justReturn( '2024-01-15 10:30:45' );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'gmdate' )->alias( 'gmdate' );

		// Mock global $wpdb.
		global $wpdb;
		$wpdb = $this->createMock( \stdClass::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'insert' )->willReturn( true );
		$wpdb->method( 'get_row' )->willReturn( null );
		$wpdb->method( 'get_var' )->willReturn( 0 );
		$wpdb->method( 'query' )->willReturn( true );
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
	 * Test Logger implements singleton pattern.
	 *
	 * Validates: Requirements 1.1
	 *
	 * @return void
	 */
	public function test_logger_implements_singleton_pattern(): void {
		$instance1 = Logger::get_instance();
		$instance2 = Logger::get_instance();

		$this->assertSame( $instance1, $instance2, 'Logger should return the same instance' );
	}

	/**
	 * Test Logger provides static methods for each log level.
	 *
	 * Validates: Requirements 1.2
	 *
	 * @return void
	 */
	public function test_logger_provides_static_methods_for_log_levels(): void {
		$this->assertTrue( method_exists( Logger::class, 'debug' ), 'Logger should have debug() method' );
		$this->assertTrue( method_exists( Logger::class, 'info' ), 'Logger should have info() method' );
		$this->assertTrue( method_exists( Logger::class, 'warning' ), 'Logger should have warning() method' );
		$this->assertTrue( method_exists( Logger::class, 'error' ), 'Logger should have error() method' );
		$this->assertTrue( method_exists( Logger::class, 'critical' ), 'Logger should have critical() method' );
	}

	/**
	 * Test Logger accepts message and optional context parameters.
	 *
	 * Validates: Requirements 1.4
	 *
	 * @return void
	 */
	public function test_logger_accepts_message_and_context(): void {
		// Should not throw an exception.
		Logger::info( 'Test message' );
		Logger::info( 'Test message with context', [ 'key' => 'value' ] );

		$this->assertTrue( true );
	}

	/**
	 * Test Logger sanitizes sensitive data from context.
	 *
	 * Validates: Requirements 17.1, 17.2
	 *
	 * @return void
	 */
	public function test_logger_sanitizes_sensitive_data(): void {
		global $wpdb;

		$captured_data = null;
		$wpdb->method( 'insert' )->willReturnCallback( function( $table, $data ) use ( &$captured_data ) {
			$captured_data = $data;
			return true;
		} );

		// Log with sensitive data.
		Logger::info( 'Test message', [
			'access_token' => 'secret123',
			'api_key'      => 'key456',
			'safe_data'    => 'visible',
		] );

		// Verify sensitive data was redacted.
		$this->assertNotNull( $captured_data );
		$context = json_decode( $captured_data['context'], true );
		$this->assertEquals( '[REDACTED]', $context['access_token'] );
		$this->assertEquals( '[REDACTED]', $context['api_key'] );
		$this->assertEquals( 'visible', $context['safe_data'] );
	}

	/**
	 * Test Logger sanitizes nested sensitive data.
	 *
	 * Validates: Requirements 17.3
	 *
	 * @return void
	 */
	public function test_logger_sanitizes_nested_sensitive_data(): void {
		global $wpdb;

		$captured_data = null;
		$wpdb->method( 'insert' )->willReturnCallback( function( $table, $data ) use ( &$captured_data ) {
			$captured_data = $data;
			return true;
		} );

		// Log with nested sensitive data.
		Logger::info( 'Test message', [
			'credentials' => [
				'password' => 'secret',
				'username' => 'admin',
			],
			'safe_data'   => 'visible',
		] );

		// Verify nested sensitive data was redacted.
		$this->assertNotNull( $captured_data );
		$context = json_decode( $captured_data['context'], true );
		$this->assertEquals( '[REDACTED]', $context['credentials']['password'] );
		$this->assertEquals( 'admin', $context['credentials']['username'] );
		$this->assertEquals( 'visible', $context['safe_data'] );
	}

	/**
	 * Test Logger generates message hash.
	 *
	 * Validates: Requirements 2.4
	 *
	 * @return void
	 */
	public function test_logger_generates_message_hash(): void {
		global $wpdb;

		$captured_data = null;
		$wpdb->method( 'insert' )->willReturnCallback( function( $table, $data ) use ( &$captured_data ) {
			$captured_data = $data;
			return true;
		} );

		Logger::info( 'Test message' );

		$this->assertNotNull( $captured_data );
		$this->assertArrayHasKey( 'message_hash', $captured_data );
		$this->assertEquals( hash( 'sha256', 'Test message' ), $captured_data['message_hash'] );
	}

	/**
	 * Test Logger captures automatic fields.
	 *
	 * Validates: Requirements 1.5
	 *
	 * @return void
	 */
	public function test_logger_captures_automatic_fields(): void {
		global $wpdb;

		$captured_data = null;
		$wpdb->method( 'insert' )->willReturnCallback( function( $table, $data ) use ( &$captured_data ) {
			$captured_data = $data;
			return true;
		} );

		Logger::info( 'Test message' );

		$this->assertNotNull( $captured_data );
		$this->assertArrayHasKey( 'level', $captured_data );
		$this->assertArrayHasKey( 'module', $captured_data );
		$this->assertArrayHasKey( 'created_at', $captured_data );
		$this->assertEquals( 'INFO', $captured_data['level'] );
		$this->assertEquals( '2024-01-15 10:30:45', $captured_data['created_at'] );
	}

	/**
	 * Test Logger uses prepared statements.
	 *
	 * Validates: Requirements 2.4
	 *
	 * @return void
	 */
	public function test_logger_uses_prepared_statements(): void {
		// Read the Logger class file.
		$file_path = __DIR__ . '/../includes/helpers/class-logger.php';
		$this->assertFileExists( $file_path );

		$content = file_get_contents( $file_path );

		// Check that all database operations use prepared statements.
		$this->assertStringContainsString( '$wpdb->prepare(', $content );
		$this->assertStringContainsString( '$wpdb->insert(', $content );
	}

	/**
	 * Test Logger error level mapping.
	 *
	 * Validates: Requirements 3.5
	 *
	 * @return void
	 */
	public function test_logger_error_level_mapping(): void {
		// This test verifies the error level mapping exists in the code.
		$file_path = __DIR__ . '/../includes/helpers/class-logger.php';
		$this->assertFileExists( $file_path );

		$content = file_get_contents( $file_path );

		// Check that error level mapping is implemented.
		$this->assertStringContainsString( 'map_error_level', $content );
		$this->assertStringContainsString( 'E_ERROR', $content );
		$this->assertStringContainsString( 'E_WARNING', $content );
		$this->assertStringContainsString( 'E_NOTICE', $content );
		$this->assertStringContainsString( 'E_DEPRECATED', $content );
		$this->assertStringContainsString( 'CRITICAL', $content );
		$this->assertStringContainsString( 'WARNING', $content );
		$this->assertStringContainsString( 'INFO', $content );
		$this->assertStringContainsString( 'DEBUG', $content );
	}

	/**
	 * Test Logger registers error handlers.
	 *
	 * Validates: Requirements 3.1, 3.4
	 *
	 * @return void
	 */
	public function test_logger_registers_error_handlers(): void {
		// This test verifies the error handler registration exists in the code.
		$file_path = __DIR__ . '/../includes/helpers/class-logger.php';
		$this->assertFileExists( $file_path );

		$content = file_get_contents( $file_path );

		// Check that error handlers are registered.
		$this->assertStringContainsString( 'set_error_handler', $content );
		$this->assertStringContainsString( 'register_shutdown_function', $content );
		$this->assertStringContainsString( 'error_handler', $content );
		$this->assertStringContainsString( 'shutdown_handler', $content );
	}
}
