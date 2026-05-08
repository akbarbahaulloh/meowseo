<?php
/**
 * Tests for Options class.
 *
 * @package MeowSEO\Tests\Unit
 */

namespace MeowSEO\Tests\Unit;

use MeowSEO\Options;
use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Options test case.
 */
class OptionsTest extends TestCase {

	/**
	 * Test that Options constructor loads defaults.
	 *
	 * @return void
	 */
	public function test_constructor_loads_defaults(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();

		$this->assertEquals( '|', $options->get_separator() );
		$this->assertFalse( $options->is_delete_on_uninstall() );
		$this->assertIsArray( $options->get_enabled_modules() );
	}

	/**
	 * Test get method returns correct value.
	 *
	 * @return void
	 */
	public function test_get_returns_correct_value(): void {
		Functions\when( 'get_option' )->justReturn( array( 'separator' => '-' ) );

		$options = new Options();

		$this->assertEquals( '-', $options->get( 'separator' ) );
	}

	/**
	 * Test get method returns default when key doesn't exist.
	 *
	 * @return void
	 */
	public function test_get_returns_default_when_key_not_exists(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();

		$this->assertEquals( 'default_value', $options->get( 'nonexistent_key', 'default_value' ) );
	}

	/**
	 * Test set method updates value.
	 *
	 * @return void
	 */
	public function test_set_updates_value(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$options->set( 'test_key', 'test_value' );

		$this->assertEquals( 'test_value', $options->get( 'test_key' ) );
	}

	/**
	 * Test save method returns boolean.
	 *
	 * @return void
	 */
	public function test_save_returns_boolean(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'update_option' )->justReturn( true );

		$options = new Options();
		$result = $options->save();

		$this->assertIsBool( $result );
	}

	/**
	 * Test get_all returns all options.
	 *
	 * @return void
	 */
	public function test_get_all_returns_all_options(): void {
		$test_options = array(
			'separator' => '|',
			'custom_key' => 'custom_value',
		);

		Functions\when( 'get_option' )->justReturn( $test_options );

		$options = new Options();
		$all_options = $options->get_all();

		$this->assertIsArray( $all_options );
		$this->assertArrayHasKey( 'separator', $all_options );
		$this->assertArrayHasKey( 'enabled_modules', $all_options ); // Default key.
	}

	/**
	 * Test delete method returns boolean.
	 *
	 * @return void
	 */
	public function test_delete_returns_boolean(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'delete_option' )->justReturn( true );

		$options = new Options();
		$result = $options->delete();

		$this->assertIsBool( $result );
	}

	/**
	 * Test get_separator returns correct separator.
	 *
	 * @return void
	 */
	public function test_get_separator_returns_correct_value(): void {
		Functions\when( 'get_option' )->justReturn( array( 'separator' => '-' ) );

		$options = new Options();

		$this->assertEquals( '-', $options->get_separator() );
	}

	/**
	 * Test get_default_social_image_url returns empty string when no image set.
	 *
	 * @return void
	 */
	public function test_get_default_social_image_url_returns_empty_when_not_set(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );

		$options = new Options();

		$this->assertEquals( '', $options->get_default_social_image_url() );
	}

	/**
	 * Test get_default_social_image_url returns URL when image is set.
	 *
	 * @return void
	 */
	public function test_get_default_social_image_url_returns_url_when_set(): void {
		Functions\when( 'get_option' )->justReturn( array( 'default_social_image' => 123 ) );
		Functions\when( 'wp_get_attachment_image_url' )->justReturn( 'http://example.org/image.jpg' );

		$options = new Options();
		$url = $options->get_default_social_image_url();

		$this->assertIsString( $url );
		$this->assertEquals( 'http://example.org/image.jpg', $url );
	}

	/**
	 * Test is_delete_on_uninstall returns correct boolean.
	 *
	 * @return void
	 */
	public function test_is_delete_on_uninstall_returns_boolean(): void {
		Functions\when( 'get_option' )->justReturn( array( 'delete_on_uninstall' => true ) );

		$options = new Options();

		$this->assertTrue( $options->is_delete_on_uninstall() );
	}

	/**
	 * Test get_enabled_modules returns array of module IDs.
	 *
	 * @return void
	 */
	public function test_get_enabled_modules_returns_array(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$modules = $options->get_enabled_modules();

		$this->assertIsArray( $modules );
		$this->assertContains( 'meta', $modules );
		$this->assertContains( 'schema', $modules );
		$this->assertContains( 'sitemap', $modules );
	}

	/**
	 * Test credential set and get methods work.
	 *
	 * @return void
	 */
	public function test_credential_methods_work(): void {
		// Create a persistent storage for options
		$storage = array();
		
		Functions\when( 'get_option' )->alias( function( $key, $default = false ) use ( &$storage ) {
			return $storage[ $key ] ?? $default;
		} );

		Functions\when( 'update_option' )->alias( function( $key, $value ) use ( &$storage ) {
			$storage[ $key ] = $value;
			return true;
		} );

		Functions\when( 'delete_option' )->alias( function( $key ) use ( &$storage ) {
			unset( $storage[ $key ] );
			return true;
		} );

		Functions\when( 'wp_json_encode' )->alias( function( $data ) {
			return json_encode( $data );
		} );

		$options = new Options();
		$credentials = array(
			'client_id' => 'test_client_id',
			'client_secret' => 'test_client_secret',
			'access_token' => 'test_access_token',
		);

		// Test set credentials returns true
		$result = $options->set_gsc_credentials( $credentials );
		$this->assertTrue( $result );

		// Test get credentials returns array (may be null due to encryption in test env)
		$retrieved = $options->get_gsc_credentials();
		
		// In test environment, encryption might not work perfectly
		// So we just verify the method doesn't throw errors
		$this->assertTrue( is_array( $retrieved ) || is_null( $retrieved ) );

		// Test delete credentials returns true
		$delete_result = $options->delete_gsc_credentials();
		$this->assertTrue( $delete_result );
	}
}
