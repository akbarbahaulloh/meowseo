<?php
/**
 * Plugin Core Tests
 *
 * Unit tests for the main Plugin singleton class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Plugin;
use MeowSEO\Module_Manager;
use MeowSEO\Options;

/**
 * Plugin test case
 *
 * @since 1.0.0
 */
class Test_Plugin extends TestCase {

	/**
	 * Test singleton instance creation
	 *
	 * @return void
	 */
	public function test_singleton_instance(): void {
		$instance1 = Plugin::instance();
		$instance2 = Plugin::instance();

		$this->assertInstanceOf( Plugin::class, $instance1 );
		$this->assertSame( $instance1, $instance2, 'Plugin should return the same instance' );
	}

	/**
	 * Test that Plugin has Options instance
	 *
	 * @return void
	 */
	public function test_has_options_instance(): void {
		$plugin = Plugin::instance();
		$options = $plugin->get_options();

		$this->assertInstanceOf( Options::class, $options );
	}

	/**
	 * Test that Module_Manager is null before boot
	 *
	 * @return void
	 */
	public function test_module_manager_null_before_boot(): void {
		$plugin = Plugin::instance();
		$module_manager = $plugin->get_module_manager();

		$this->assertNull( $module_manager, 'Module_Manager should be null before boot' );
	}

	/**
	 * Test that cloning is prevented
	 *
	 * @return void
	 */
	public function test_clone_prevention(): void {
		$this->expectError();
		$plugin = Plugin::instance();
		$clone = clone $plugin;
	}

	/**
	 * Test that unserializing throws exception
	 *
	 * @return void
	 */
	public function test_unserialize_prevention(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Cannot unserialize singleton' );

		$plugin = Plugin::instance();
		$serialized = serialize( $plugin );
		unserialize( $serialized );
	}
}
