<?php
/**
 * Simplified tests for Options class.
 *
 * @package MeowSEO\Tests\Unit
 */

namespace MeowSEO\Tests\Unit;

use MeowSEO\Options;
use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Options simple test case.
 */
class OptionsSimpleTest extends TestCase {

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
}
