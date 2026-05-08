<?php
/**
 * Tests for Module_Manager class.
 *
 * @package MeowSEO\Tests\Unit
 */

namespace MeowSEO\Tests\Unit;

use MeowSEO\Module_Manager;
use MeowSEO\Options;
use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Module_Manager test case.
 */
class ModuleManagerTest extends TestCase {

	/**
	 * Test that Module_Manager can be instantiated.
	 *
	 * @return void
	 */
	public function test_module_manager_can_be_instantiated(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$manager = new Module_Manager( $options );

		$this->assertInstanceOf( 'MeowSEO\Module_Manager', $manager );
	}

	/**
	 * Test that is_active returns false for unloaded module.
	 *
	 * @return void
	 */
	public function test_is_active_returns_false_for_unloaded_module(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$manager = new Module_Manager( $options );

		$this->assertFalse( $manager->is_active( 'nonexistent_module' ) );
	}

	/**
	 * Test that get_module returns null for unloaded module.
	 *
	 * @return void
	 */
	public function test_get_module_returns_null_for_unloaded_module(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$manager = new Module_Manager( $options );

		$this->assertNull( $manager->get_module( 'nonexistent_module' ) );
	}

	/**
	 * Test that get_modules returns empty array before boot.
	 *
	 * @return void
	 */
	public function test_get_modules_returns_empty_array_before_boot(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$manager = new Module_Manager( $options );

		$this->assertIsArray( $manager->get_modules() );
		$this->assertEmpty( $manager->get_modules() );
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

		$options = new Options();
		$manager = new Module_Manager( $options );

		$this->assertTrue( method_exists( $manager, 'boot' ) );
		$this->assertTrue( is_callable( array( $manager, 'boot' ) ) );
	}
}
