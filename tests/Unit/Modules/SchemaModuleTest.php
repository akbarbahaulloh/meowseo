<?php
/**
 * Tests for Schema_Module class.
 *
 * @package MeowSEO\Tests\Unit\Modules
 */

namespace MeowSEO\Tests\Unit\Modules;

use MeowSEO\Modules\Schema\Schema_Module;
use MeowSEO\Options;
use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Schema_Module test case.
 */
class SchemaModuleTest extends TestCase {

	/**
	 * Test that Schema_Module can be instantiated.
	 *
	 * @return void
	 */
	public function test_schema_module_can_be_instantiated(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$module = new Schema_Module( $options );

		$this->assertInstanceOf( 'MeowSEO\Modules\Schema\Schema_Module', $module );
	}

	/**
	 * Test that Schema_Module implements Module interface.
	 *
	 * @return void
	 */
	public function test_schema_module_implements_module_interface(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$module = new Schema_Module( $options );

		$this->assertInstanceOf( 'MeowSEO\Contracts\Module', $module );
	}

	/**
	 * Test get_id returns correct module ID.
	 *
	 * @return void
	 */
	public function test_get_id_returns_schema(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$module = new Schema_Module( $options );

		$this->assertEquals( 'schema', $module->get_id() );
	}

	/**
	 * Test boot method exists.
	 *
	 * @return void
	 */
	public function test_boot_method_exists(): void {
		$this->assertTrue( method_exists( Schema_Module::class, 'boot' ) );
	}

	/**
	 * Test init method exists.
	 *
	 * @return void
	 */
	public function test_init_method_exists(): void {
		$this->assertTrue( method_exists( Schema_Module::class, 'init' ) );
	}
}
