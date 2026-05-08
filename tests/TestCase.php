<?php
/**
 * Base TestCase class for MeowSEO tests.
 *
 * Provides common setup and teardown functionality for all tests.
 *
 * @package MeowSEO\Tests
 */

namespace MeowSEO\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;

/**
 * Base TestCase class.
 */
abstract class TestCase extends PHPUnit_TestCase {

	use MockeryPHPUnitIntegration;

	/**
	 * Set up test environment before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Reset global wpdb storage.
		global $wpdb_storage;
		$wpdb_storage = array();

		// Mock common WordPress functions.
		$this->mockWordPressFunctions();
	}

	/**
	 * Tear down test environment after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Mock common WordPress functions used across tests.
	 *
	 * @return void
	 */
	protected function mockWordPressFunctions(): void {
		// Mock get_option.
		Monkey\Functions\when( 'get_option' )->alias( function( $option, $default = false ) {
			static $options = array();
			return $options[ $option ] ?? $default;
		} );

		// Mock update_option.
		Monkey\Functions\when( 'update_option' )->alias( function( $option, $value ) {
			static $options = array();
			$options[ $option ] = $value;
			return true;
		} );

		// Mock delete_option.
		Monkey\Functions\when( 'delete_option' )->alias( function( $option ) {
			static $options = array();
			unset( $options[ $option ] );
			return true;
		} );

		// Mock wp_json_encode.
		Monkey\Functions\when( 'wp_json_encode' )->alias( function( $data, $options = 0, $depth = 512 ) {
			return json_encode( $data, $options, $depth );
		} );

		// Mock esc_html.
		Monkey\Functions\when( 'esc_html' )->returnArg();

		// Mock esc_attr.
		Monkey\Functions\when( 'esc_attr' )->returnArg();

		// Mock esc_url.
		Monkey\Functions\when( 'esc_url' )->returnArg();

		// Mock wp_kses_post.
		Monkey\Functions\when( 'wp_kses_post' )->returnArg();

		// Mock sanitize_text_field.
		Monkey\Functions\when( 'sanitize_text_field' )->returnArg();

		// Mock sanitize_email.
		Monkey\Functions\when( 'sanitize_email' )->returnArg();

		// Mock is_admin.
		Monkey\Functions\when( 'is_admin' )->justReturn( false );

		// Mock wp_get_attachment_image_url.
		Monkey\Functions\when( 'wp_get_attachment_image_url' )->justReturn( '' );

		// Mock plugin_dir_path.
		Monkey\Functions\when( 'plugin_dir_path' )->alias( function( $file ) {
			return dirname( $file ) . '/';
		} );

		// Mock plugin_dir_url.
		Monkey\Functions\when( 'plugin_dir_url' )->alias( function( $file ) {
			return 'http://example.org/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
		} );

		// Mock plugin_basename.
		Monkey\Functions\when( 'plugin_basename' )->alias( function( $file ) {
			return basename( dirname( $file ) ) . '/' . basename( $file );
		} );

		// Mock load_plugin_textdomain.
		Monkey\Functions\when( 'load_plugin_textdomain' )->justReturn( true );

		// Mock remove_theme_support.
		Monkey\Functions\when( 'remove_theme_support' )->justReturn( true );

		// Mock register_rest_route.
		Monkey\Functions\when( 'register_rest_route' )->justReturn( true );

		// Mock current_user_can.
		Monkey\Functions\when( 'current_user_can' )->justReturn( true );

		// Mock WordPress cron functions.
		Monkey\Functions\when( 'wp_next_scheduled' )->justReturn( false );
		Monkey\Functions\when( 'wp_schedule_event' )->justReturn( true );
		Monkey\Functions\when( 'wp_clear_scheduled_hook' )->justReturn( true );

		// Mock do_action.
		Monkey\Functions\when( 'do_action' )->justReturn( null );

		// Mock multisite functions.
		Monkey\Functions\when( 'is_multisite' )->justReturn( false );
		Monkey\Functions\when( 'get_sites' )->justReturn( array() );
		Monkey\Functions\when( 'switch_to_blog' )->justReturn( true );
		Monkey\Functions\when( 'restore_current_blog' )->justReturn( true );

		// Mock add_action.
		Monkey\Actions\expectAdded( 'init' )->zeroOrMoreTimes();
		Monkey\Actions\expectAdded( 'admin_init' )->zeroOrMoreTimes();
		Monkey\Actions\expectAdded( 'rest_api_init' )->zeroOrMoreTimes();
		Monkey\Actions\expectAdded( 'graphql_register_types' )->zeroOrMoreTimes();
		Monkey\Actions\expectAdded( 'wp_head' )->zeroOrMoreTimes();
		Monkey\Actions\expectAdded( 'save_post' )->zeroOrMoreTimes();
		Monkey\Actions\expectAdded( 'enqueue_block_editor_assets' )->zeroOrMoreTimes();

		// Mock add_filter.
		Monkey\Filters\expectAdded( 'the_content' )->zeroOrMoreTimes();
		Monkey\Filters\expectAdded( 'the_title' )->zeroOrMoreTimes();
		Monkey\Filters\expectAdded( 'document_title_parts' )->zeroOrMoreTimes();
	}

	/**
	 * Assert that a string contains a substring.
	 *
	 * @param string $needle   Substring to search for.
	 * @param string $haystack String to search in.
	 * @param string $message  Optional failure message.
	 * @return void
	 */
	protected function assertStringContains( string $needle, string $haystack, string $message = '' ): void {
		$this->assertStringContainsString( $needle, $haystack, $message );
	}
}
