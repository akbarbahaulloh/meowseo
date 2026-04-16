<?php
/**
 * Module Manager Tests
 *
 * Unit tests for the Module_Manager class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Module_Manager;
use MeowSEO\Options;

/**
 * Module Manager test case
 *
 * @since 1.0.0
 */
class Test_Module_Manager extends TestCase {

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
		$this->options = new Options();
	}

	/**
	 * Test Module_Manager instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$manager = new Module_Manager( $this->options );
		$this->assertInstanceOf( Module_Manager::class, $manager );
	}

	/**
	 * Test that no modules are loaded before boot
	 *
	 * @return void
	 */
	public function test_no_modules_before_boot(): void {
		$manager = new Module_Manager( $this->options );
		$modules = $manager->get_modules();

		$this->assertIsArray( $modules );
		$this->assertEmpty( $modules, 'No modules should be loaded before boot' );
	}

	/**
	 * Test that is_active returns false for unloaded modules
	 *
	 * @return void
	 */
	public function test_is_active_returns_false_for_unloaded_modules(): void {
		$manager = new Module_Manager( $this->options );

		$this->assertFalse( $manager->is_active( 'meta' ) );
		$this->assertFalse( $manager->is_active( 'schema' ) );
		$this->assertFalse( $manager->is_active( 'nonexistent' ) );
	}

	/**
	 * Test that get_module returns null for unloaded modules
	 *
	 * @return void
	 */
	public function test_get_module_returns_null_for_unloaded_modules(): void {
		$manager = new Module_Manager( $this->options );

		$this->assertNull( $manager->get_module( 'meta' ) );
		$this->assertNull( $manager->get_module( 'schema' ) );
		$this->assertNull( $manager->get_module( 'nonexistent' ) );
	}

	/**
	 * Test that only enabled modules are loaded
	 *
	 * This test verifies Requirement 1.2: Module_Manager loads exactly the enabled set.
	 *
	 * @return void
	 */
	public function test_only_enabled_modules_are_loaded(): void {
		// Enable only meta and schema modules.
		$this->options->set( 'enabled_modules', array( 'meta', 'schema' ) );
		$this->options->save();

		$manager = new Module_Manager( $this->options );
		$manager->boot();

		// Meta and schema should be active.
		$this->assertTrue( $manager->is_active( 'meta' ), 'Meta module should be active' );
		$this->assertTrue( $manager->is_active( 'schema' ), 'Schema module should be active' );

		// Other modules should not be active.
		$this->assertFalse( $manager->is_active( 'sitemap' ), 'Sitemap module should not be active' );
		$this->assertFalse( $manager->is_active( 'redirects' ), 'Redirects module should not be active' );
		$this->assertFalse( $manager->is_active( 'monitor_404' ), '404 Monitor module should not be active' );
	}

	/**
	 * Test that disabled modules are never loaded
	 *
	 * This test verifies Requirement 1.3: Disabled modules are never loaded.
	 *
	 * @return void
	 */
	public function test_disabled_modules_are_never_loaded(): void {
		// Enable only meta module.
		$this->options->set( 'enabled_modules', array( 'meta' ) );
		$this->options->save();

		$manager = new Module_Manager( $this->options );
		$manager->boot();

		$modules = $manager->get_modules();

		// Only one module should be loaded.
		$this->assertCount( 1, $modules, 'Only one module should be loaded' );

		// Meta should be the only loaded module.
		$this->assertArrayHasKey( 'meta', $modules );
		$this->assertArrayNotHasKey( 'schema', $modules );
		$this->assertArrayNotHasKey( 'sitemap', $modules );
	}

	/**
	 * Test that WooCommerce module is not loaded when WooCommerce is not active
	 *
	 * @return void
	 */
	public function test_woocommerce_module_not_loaded_without_woocommerce(): void {
		// Enable WooCommerce module.
		$this->options->set( 'enabled_modules', array( 'woocommerce' ) );
		$this->options->save();

		$manager = new Module_Manager( $this->options );
		$manager->boot();

		// WooCommerce module should not be loaded since WooCommerce class doesn't exist.
		$this->assertFalse( $manager->is_active( 'woocommerce' ) );
	}

	/**
	 * Test that get_modules returns all loaded modules
	 *
	 * @return void
	 */
	public function test_get_modules_returns_all_loaded_modules(): void {
		$this->options->set( 'enabled_modules', array( 'meta', 'schema', 'social' ) );
		$this->options->save();

		$manager = new Module_Manager( $this->options );
		$manager->boot();

		$modules = $manager->get_modules();

		$this->assertIsArray( $modules );
		$this->assertCount( 3, $modules );
		$this->assertArrayHasKey( 'meta', $modules );
		$this->assertArrayHasKey( 'schema', $modules );
		$this->assertArrayHasKey( 'social', $modules );
	}

	/**
	 * Test that redirects, monitor_404, and gsc modules are registered and can be loaded
	 *
	 * This test verifies Task 16: Register modules with Module_Manager.
	 * Requirements: All
	 *
	 * @return void
	 */
	public function test_redirects_404_gsc_modules_are_registered(): void {
		// Enable the three new modules.
		$this->options->set( 'enabled_modules', array( 'redirects', 'monitor_404', 'gsc' ) );
		$this->options->save();

		$manager = new Module_Manager( $this->options );
		$manager->boot();

		// All three modules should be active.
		$this->assertTrue( $manager->is_active( 'redirects' ), 'Redirects module should be active' );
		$this->assertTrue( $manager->is_active( 'monitor_404' ), '404 Monitor module should be active' );
		$this->assertTrue( $manager->is_active( 'gsc' ), 'GSC module should be active' );

		// Verify modules are loaded.
		$modules = $manager->get_modules();
		$this->assertCount( 3, $modules, 'Three modules should be loaded' );
		$this->assertArrayHasKey( 'redirects', $modules );
		$this->assertArrayHasKey( 'monitor_404', $modules );
		$this->assertArrayHasKey( 'gsc', $modules );

		// Verify each module implements the Module interface.
		$redirects_module = $manager->get_module( 'redirects' );
		$monitor_404_module = $manager->get_module( 'monitor_404' );
		$gsc_module = $manager->get_module( 'gsc' );

		$this->assertInstanceOf( \MeowSEO\Contracts\Module::class, $redirects_module );
		$this->assertInstanceOf( \MeowSEO\Contracts\Module::class, $monitor_404_module );
		$this->assertInstanceOf( \MeowSEO\Contracts\Module::class, $gsc_module );

		// Verify each module has the correct ID.
		$this->assertEquals( 'redirects', $redirects_module->get_id() );
		$this->assertEquals( 'monitor_404', $monitor_404_module->get_id() );
		$this->assertEquals( 'gsc', $gsc_module->get_id() );
	}
}
