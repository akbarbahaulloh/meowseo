<?php
/**
 * Tests for Plugin class.
 *
 * @package MeowSEO\Tests\Unit
 */

namespace MeowSEO\Tests\Unit;

use MeowSEO\Plugin;
use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Plugin test case.
 */
class PluginTest extends TestCase {

	/**
	 * Test that Plugin is a singleton.
	 *
	 * @return void
	 */
	public function test_plugin_is_singleton(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$instance1 = Plugin::instance();
		$instance2 = Plugin::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test that Plugin instance returns Options.
	 *
	 * @return void
	 */
	public function test_plugin_returns_options(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$plugin = Plugin::instance();
		$options = $plugin->get_options();

		$this->assertInstanceOf( 'MeowSEO\Options', $options );
	}

	/**
	 * Test that boot method exists and is callable.
	 *
	 * Note: We skip actually calling boot() in tests because it requires
	 * extensive WordPress environment mocking. The boot() method is tested
	 * in integration tests and real WordPress environment.
	 *
	 * @return void
	 */
	public function test_boot_method_exists_and_is_callable(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$plugin = Plugin::instance();
		
		$this->assertTrue( method_exists( $plugin, 'boot' ) );
		$this->assertTrue( is_callable( array( $plugin, 'boot' ) ) );
	}

	/**
	 * Test that __wakeup throws exception.
	 *
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Cannot unserialize singleton' );

		Functions\when( 'get_option' )->justReturn( array() );

		$plugin = Plugin::instance();
		$plugin->__wakeup();
	}

	/**
	 * Test that get_module_manager returns null before boot.
	 *
	 * @return void
	 */
	public function test_get_module_manager_returns_null_before_boot(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		// Create new instance using reflection to bypass singleton.
		$reflection = new \ReflectionClass( Plugin::class );
		$instance_property = $reflection->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );

		$plugin = Plugin::instance();
		$module_manager = $plugin->get_module_manager();

		$this->assertNull( $module_manager );
	}
}
