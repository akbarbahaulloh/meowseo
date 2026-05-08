<?php
/**
 * Tests for Meta_Module class.
 *
 * @package MeowSEO\Tests\Unit\Modules
 */

namespace MeowSEO\Tests\Unit\Modules;

use MeowSEO\Modules\Meta\Meta_Module;
use MeowSEO\Options;
use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

/**
 * Meta_Module test case.
 */
class MetaModuleTest extends TestCase {

	/**
	 * Test that Meta_Module can be instantiated.
	 *
	 * @return void
	 */
	public function test_meta_module_can_be_instantiated(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$module = new Meta_Module( $options );

		$this->assertInstanceOf( 'MeowSEO\Modules\Meta\Meta_Module', $module );
	}

	/**
	 * Test that Meta_Module implements Module interface.
	 *
	 * @return void
	 */
	public function test_meta_module_implements_module_interface(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$module = new Meta_Module( $options );

		$this->assertInstanceOf( 'MeowSEO\Contracts\Module', $module );
	}

	/**
	 * Test get_id returns correct module ID.
	 *
	 * @return void
	 */
	public function test_get_id_returns_meta(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$module = new Meta_Module( $options );

		$this->assertEquals( 'meta', $module->get_id() );
	}

	/**
	 * Test boot method exists.
	 *
	 * @return void
	 */
	public function test_boot_method_exists(): void {
		$this->assertTrue( method_exists( Meta_Module::class, 'boot' ) );
	}

	/**
	 * Test output_head_tags method exists.
	 *
	 * @return void
	 */
	public function test_output_head_tags_method_exists(): void {
		$this->assertTrue( method_exists( Meta_Module::class, 'output_head_tags' ) );
	}

	/**
	 * Test filter_document_title_parts method exists.
	 *
	 * @return void
	 */
	public function test_filter_document_title_parts_method_exists(): void {
		$this->assertTrue( method_exists( Meta_Module::class, 'filter_document_title_parts' ) );
	}

	/**
	 * Test filter_document_title_parts returns empty array.
	 *
	 * @return void
	 */
	public function test_filter_document_title_parts_returns_empty_array(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$module = new Meta_Module( $options );

		$result = $module->filter_document_title_parts( array( 'title' => 'Test' ) );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test handle_save_post method exists.
	 *
	 * @return void
	 */
	public function test_handle_save_post_method_exists(): void {
		$this->assertTrue( method_exists( Meta_Module::class, 'handle_save_post' ) );
	}

	/**
	 * Test register_rest_fields method exists.
	 *
	 * @return void
	 */
	public function test_register_rest_fields_method_exists(): void {
		$this->assertTrue( method_exists( Meta_Module::class, 'register_rest_fields' ) );
	}

	/**
	 * Test enqueue_block_editor_assets method exists.
	 *
	 * @return void
	 */
	public function test_enqueue_block_editor_assets_method_exists(): void {
		$this->assertTrue( method_exists( Meta_Module::class, 'enqueue_block_editor_assets' ) );
	}
}
