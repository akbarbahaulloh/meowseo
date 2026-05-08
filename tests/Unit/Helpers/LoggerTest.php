<?php
/**
 * Tests for Logger class.
 *
 * @package MeowSEO\Tests\Unit\Helpers
 */

namespace MeowSEO\Tests\Unit\Helpers;

use MeowSEO\Helpers\Logger;
use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Logger test case.
 */
class LoggerTest extends TestCase {

	/**
	 * Test that Logger is a singleton.
	 *
	 * @return void
	 */
	public function test_logger_is_singleton(): void {
		$instance1 = Logger::get_instance();
		$instance2 = Logger::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test that Logger instance is of correct type.
	 *
	 * @return void
	 */
	public function test_logger_instance_type(): void {
		$instance = Logger::get_instance();

		$this->assertInstanceOf( 'MeowSEO\Helpers\Logger', $instance );
	}

	/**
	 * Test debug logging method exists.
	 *
	 * @return void
	 */
	public function test_debug_method_exists(): void {
		$this->assertTrue( method_exists( Logger::class, 'debug' ) );
	}

	/**
	 * Test info logging method exists.
	 *
	 * @return void
	 */
	public function test_info_method_exists(): void {
		$this->assertTrue( method_exists( Logger::class, 'info' ) );
	}

	/**
	 * Test warning logging method exists.
	 *
	 * @return void
	 */
	public function test_warning_method_exists(): void {
		$this->assertTrue( method_exists( Logger::class, 'warning' ) );
	}

	/**
	 * Test error logging method exists.
	 *
	 * @return void
	 */
	public function test_error_method_exists(): void {
		$this->assertTrue( method_exists( Logger::class, 'error' ) );
	}

	/**
	 * Test critical logging method exists.
	 *
	 * @return void
	 */
	public function test_critical_method_exists(): void {
		$this->assertTrue( method_exists( Logger::class, 'critical' ) );
	}

	/**
	 * Test that logging methods can be called without errors.
	 *
	 * @return void
	 */
	public function test_logging_methods_can_be_called(): void {
		Functions\when( 'current_time' )->justReturn( '2026-05-08 10:00:00' );
		Functions\when( 'wp_json_encode' )->alias( function( $data ) {
			return json_encode( $data );
		} );

		// These should not throw exceptions
		Logger::debug( 'Debug message' );
		Logger::info( 'Info message' );
		Logger::warning( 'Warning message' );
		Logger::error( 'Error message' );
		Logger::critical( 'Critical message' );

		// If we get here without exceptions, test passes
		$this->assertTrue( true );
	}

	/**
	 * Test logging with context data.
	 *
	 * @return void
	 */
	public function test_logging_with_context(): void {
		Functions\when( 'current_time' )->justReturn( '2026-05-08 10:00:00' );
		Functions\when( 'wp_json_encode' )->alias( function( $data ) {
			return json_encode( $data );
		} );

		$context = array(
			'user_id' => 123,
			'action'  => 'test_action',
		);

		// Should not throw exception
		Logger::info( 'Test message with context', $context );

		$this->assertTrue( true );
	}

	/**
	 * Test error handler method exists.
	 *
	 * @return void
	 */
	public function test_error_handler_method_exists(): void {
		$instance = Logger::get_instance();

		$this->assertTrue( method_exists( $instance, 'error_handler' ) );
	}

	/**
	 * Test shutdown handler method exists.
	 *
	 * @return void
	 */
	public function test_shutdown_handler_method_exists(): void {
		$instance = Logger::get_instance();

		$this->assertTrue( method_exists( $instance, 'shutdown_handler' ) );
	}
}
