<?php
/**
 * Integration tests for Meta_Module
 *
 * Tests hook registration, priorities, and component wiring.
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use MeowSEO\Modules\Meta\Meta_Module;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Meta_Module integration test case
 */
class MetaModuleIntegrationTest extends TestCase {
	/**
	 * Meta_Module instance
	 *
	 * @var Meta_Module
	 */
	private Meta_Module $module;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset global filter storage.
		global $wp_filter;
		$wp_filter = array();

		// Create Options instance with default values.
		$this->options = new Options();

		// Create Meta_Module instance.
		$this->module = new Meta_Module( $this->options );
	}

	/**
	 * Test module ID
	 *
	 * @return void
	 */
	public function test_get_id_returns_meta(): void {
		$this->assertSame( 'meta', $this->module->get_id() );
	}

	/**
	 * Test wp_head hook registration
	 *
	 * Verifies that the wp_head hook is registered with priority 1.
	 *
	 * @return void
	 */
	public function test_wp_head_hook_registered_with_priority_1(): void {
		global $wp_filter;

		// Boot the module to register hooks.
		$this->module->boot();

		// Check if wp_head hook is registered.
		$this->assertArrayHasKey( 'wp_head', $wp_filter );

		// Find our callback with priority 1.
		$found = false;
		foreach ( $wp_filter['wp_head'] as $filter ) {
			if ( $filter['priority'] === 1 &&
				is_array( $filter['callback'] ) &&
				$filter['callback'][0] === $this->module &&
				$filter['callback'][1] === 'output_head_tags' ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'wp_head hook with output_head_tags callback not found at priority 1' );
	}

	/**
	 * Test document_title_parts filter registration
	 *
	 * Verifies that the document_title_parts filter is registered.
	 *
	 * @return void
	 */
	public function test_document_title_parts_filter_registered(): void {
		global $wp_filter;

		// Boot the module to register hooks.
		$this->module->boot();

		// Check if document_title_parts filter is registered.
		$this->assertArrayHasKey( 'document_title_parts', $wp_filter );

		// Find our callback.
		$found = false;
		foreach ( $wp_filter['document_title_parts'] as $filter ) {
			if ( is_array( $filter['callback'] ) &&
				$filter['callback'][0] === $this->module &&
				$filter['callback'][1] === 'filter_document_title_parts' ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'document_title_parts filter with filter_document_title_parts callback not found' );
	}

	/**
	 * Test save_post hook registration
	 *
	 * Verifies that the save_post hook is registered.
	 *
	 * @return void
	 */
	public function test_save_post_hook_registered(): void {
		global $wp_filter;

		// Boot the module to register hooks.
		$this->module->boot();

		// Check if save_post hook is registered.
		$this->assertArrayHasKey( 'save_post', $wp_filter );

		// Find our callback.
		$found = false;
		foreach ( $wp_filter['save_post'] as $filter ) {
			if ( is_array( $filter['callback'] ) &&
				$filter['callback'][0] === $this->module &&
				$filter['callback'][1] === 'handle_save_post' ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'save_post hook with handle_save_post callback not found' );
	}

	/**
	 * Test rest_api_init hook registration
	 *
	 * Verifies that the rest_api_init hook is registered.
	 *
	 * @return void
	 */
	public function test_rest_api_init_hook_registered(): void {
		global $wp_filter;

		// Boot the module to register hooks.
		$this->module->boot();

		// Check if rest_api_init hook is registered.
		$this->assertArrayHasKey( 'rest_api_init', $wp_filter );

		// Find our callback.
		$found = false;
		foreach ( $wp_filter['rest_api_init'] as $filter ) {
			if ( is_array( $filter['callback'] ) &&
				$filter['callback'][0] === $this->module &&
				$filter['callback'][1] === 'register_rest_fields' ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'rest_api_init hook with register_rest_fields callback not found' );
	}

	/**
	 * Test enqueue_block_editor_assets hook registration
	 *
	 * Verifies that the enqueue_block_editor_assets hook is registered.
	 *
	 * @return void
	 */
	public function test_enqueue_block_editor_assets_hook_registered(): void {
		global $wp_filter;

		// Boot the module to register hooks.
		$this->module->boot();

		// Check if enqueue_block_editor_assets hook is registered.
		$this->assertArrayHasKey( 'enqueue_block_editor_assets', $wp_filter );

		// Find our callback.
		$found = false;
		foreach ( $wp_filter['enqueue_block_editor_assets'] as $filter ) {
			if ( is_array( $filter['callback'] ) &&
				$filter['callback'][0] === $this->module &&
				$filter['callback'][1] === 'enqueue_block_editor_assets' ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'enqueue_block_editor_assets hook with enqueue_block_editor_assets callback not found' );
	}

	/**
	 * Test robots_txt filter registration
	 *
	 * Verifies that the robots_txt filter is registered via Robots_Txt component.
	 *
	 * @return void
	 */
	public function test_robots_txt_filter_registered(): void {
		global $wp_filter;

		// Boot the module to register hooks.
		$this->module->boot();

		// Check if robots_txt filter is registered.
		$this->assertArrayHasKey( 'robots_txt', $wp_filter );

		// Verify that at least one callback is registered.
		$this->assertNotEmpty( $wp_filter['robots_txt'] );
	}

	/**
	 * Test filter_document_title_parts returns empty array
	 *
	 * Verifies that the filter returns an empty array to suppress WordPress's
	 * default title generation.
	 *
	 * @return void
	 */
	public function test_filter_document_title_parts_returns_empty_array(): void {
		$this->module->boot();

		$input_parts = array(
			'title' => 'Test Title',
			'site'  => 'Test Site',
		);

		$result = $this->module->filter_document_title_parts( $input_parts );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test component wiring
	 *
	 * Verifies that all components are properly instantiated and wired together.
	 * This is a smoke test to ensure no fatal errors occur during initialization.
	 *
	 * @return void
	 */
	public function test_component_wiring(): void {
		// If we got this far without errors, component wiring is successful.
		$this->assertInstanceOf( Meta_Module::class, $this->module );

		// Boot the module to ensure all components are initialized.
		$this->module->boot();

		// If no exceptions were thrown, the test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test all hooks are registered after boot
	 *
	 * Verifies that all expected hooks are registered after calling boot().
	 *
	 * @return void
	 */
	public function test_all_hooks_registered_after_boot(): void {
		global $wp_filter;

		// Boot the module.
		$this->module->boot();

		// Verify all expected hooks are registered.
		$expected_hooks = array(
			'wp_head',
			'document_title_parts',
			'save_post',
			'rest_api_init',
			'enqueue_block_editor_assets',
			'robots_txt',
		);

		foreach ( $expected_hooks as $hook ) {
			$this->assertArrayHasKey( $hook, $wp_filter, "Hook '{$hook}' not registered" );
		}
	}

	/**
	 * Test hook priorities are correct
	 *
	 * Verifies that hooks are registered with the correct priorities.
	 *
	 * @return void
	 */
	public function test_hook_priorities(): void {
		global $wp_filter;

		// Boot the module.
		$this->module->boot();

		// Check wp_head priority is 1.
		$wp_head_priority = null;
		foreach ( $wp_filter['wp_head'] as $filter ) {
			if ( is_array( $filter['callback'] ) &&
				$filter['callback'][0] === $this->module &&
				$filter['callback'][1] === 'output_head_tags' ) {
				$wp_head_priority = $filter['priority'];
				break;
			}
		}

		$this->assertSame( 1, $wp_head_priority, 'wp_head hook should have priority 1' );
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Reset global filter storage.
		global $wp_filter;
		$wp_filter = array();

		parent::tearDown();
	}
}
