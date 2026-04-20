<?php
/**
 * Property-Based Tests for Module Manager Loading
 *
 * Property 1: Module_Manager loads exactly the enabled set
 * Validates: Requirements 1.2, 1.3
 *
 * This test uses property-based testing (eris/eris) to verify that the Module_Manager
 * correctly loads only the modules that are enabled in Options, and never loads
 * disabled modules.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Module_Manager;
use MeowSEO\Options;

/**
 * Module Manager property-based test case
 *
 * @since 1.0.0
 */
class ModuleLoadingPropertyTest extends TestCase {
	use TestTrait;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Reset global wpdb storage to ensure clean state between tests
		reset_wpdb_storage();
	}

	/**
	 * Property 1: Module_Manager loads exactly the enabled set
	 *
	 * For any set of enabled modules in Options:
	 * 1. Module_Manager should instantiate exactly those modules
	 * 2. No additional modules should be loaded
	 * 3. Disabled modules should never be instantiated
	 * 4. The loaded modules should match the enabled set exactly
	 *
	 * **Validates: Requirements 1.2, 1.3**
	 *
	 * @return void
	 */
	public function test_module_manager_loads_exactly_enabled_set(): void {
		// Define all available modules
		$all_modules = [
			'meta',
			'schema',
			'sitemap',
			'redirects',
			'monitor_404',
			'internal_links',
			'gsc',
			'social',
			'woocommerce',
		];

		$this->forAll(
			Generators::choose( 0, count( $all_modules ) - 1 ),
			Generators::choose( 0, count( $all_modules ) - 1 )
		)
		->then(
			function ( int $start_idx, int $end_idx ) use ( $all_modules ) {
				// Ensure start <= end
				$start = min( $start_idx, $end_idx );
				$end = max( $start_idx, $end_idx );

				// Generate a subset of enabled modules
				$enabled_modules = array_slice( $all_modules, $start, $end - $start + 1 );

				// Create a mock Options instance with the enabled modules
				$options = $this->create_mock_options( $enabled_modules );

				// Create Module_Manager with the mock options
				$manager = new Module_Manager( $options );

				// Boot the manager (may fail due to missing WordPress functions, but that's OK)
				try {
					$manager->boot();
				} catch ( \Throwable $e ) {
					// Ignore exceptions and errors from WordPress function calls
				}

				// Get all loaded modules
				$loaded_modules = $manager->get_modules();
				$loaded_module_ids = array_keys( $loaded_modules );

				// Filter expected modules: WooCommerce module is only loaded if WooCommerce is active
				$expected_modules = array_filter(
					$enabled_modules,
					function ( $module_id ) {
						if ( 'woocommerce' === $module_id && ! class_exists( 'WooCommerce' ) ) {
							return false;
						}
						return true;
					}
				);

				// Verify: exactly the enabled modules are loaded (accounting for WooCommerce special case)
				$this->assertEqualsCanonicalizing(
					$expected_modules,
					$loaded_module_ids,
					'Module_Manager should load exactly the enabled modules'
				);

				// Verify: no disabled modules are loaded
				foreach ( $all_modules as $module_id ) {
					if ( ! in_array( $module_id, $enabled_modules, true ) ) {
						$this->assertFalse(
							$manager->is_active( $module_id ),
							"Disabled module '$module_id' should not be active"
						);
						$this->assertNull(
							$manager->get_module( $module_id ),
							"Disabled module '$module_id' should not be instantiated"
						);
					}
				}

				// Verify: all enabled modules are active (except WooCommerce if not installed)
				foreach ( $enabled_modules as $module_id ) {
					// Skip WooCommerce if not active
					if ( 'woocommerce' === $module_id && ! class_exists( 'WooCommerce' ) ) {
						continue;
					}
					$this->assertTrue(
						$manager->is_active( $module_id ),
						"Enabled module '$module_id' should be active"
					);
					$this->assertNotNull(
						$manager->get_module( $module_id ),
						"Enabled module '$module_id' should be instantiated"
					);
				}
			}
		);
	}

	/**
	 * Property: Module_Manager never loads disabled modules
	 *
	 * For any configuration where a module is disabled:
	 * 1. That module should not be instantiated
	 * 2. is_active() should return false
	 * 3. get_module() should return null
	 *
	 * **Validates: Requirement 1.3**
	 *
	 * @return void
	 */
	public function test_module_manager_never_loads_disabled_modules(): void {
		$all_modules = [
			'meta',
			'schema',
			'sitemap',
			'redirects',
			'monitor_404',
			'internal_links',
			'gsc',
			'social',
			'woocommerce',
		];

		$this->forAll(
			Generators::elements( $all_modules )
		)
		->then(
			function ( string $disabled_module ) use ( $all_modules ) {
				// Create a set of enabled modules excluding the disabled one
				$enabled_modules = array_filter(
					$all_modules,
					function ( $module ) use ( $disabled_module ) {
						return $module !== $disabled_module;
					}
				);

				// Create mock Options with the enabled modules
				$options = $this->create_mock_options( array_values( $enabled_modules ) );

				// Create Module_Manager
				$manager = new Module_Manager( $options );

				// Boot the manager (may fail due to missing WordPress functions, but that's OK)
				try {
					$manager->boot();
				} catch ( \Throwable $e ) {
					// Ignore exceptions and errors from WordPress function calls
				}

				// Verify the disabled module is not loaded
				$this->assertFalse(
					$manager->is_active( $disabled_module ),
					"Disabled module '$disabled_module' should not be active"
				);

				$this->assertNull(
					$manager->get_module( $disabled_module ),
					"Disabled module '$disabled_module' should not be instantiated"
				);
			}
		);
	}

	/**
	 * Property: Module_Manager loads all enabled modules
	 *
	 * For any set of enabled modules:
	 * 1. All enabled modules should be instantiated
	 * 2. is_active() should return true for all enabled modules
	 * 3. get_module() should return a non-null instance for all enabled modules
	 *
	 * **Validates: Requirement 1.2**
	 *
	 * @return void
	 */
	public function test_module_manager_loads_all_enabled_modules(): void {
		$all_modules = [
			'meta',
			'schema',
			'sitemap',
			'redirects',
			'monitor_404',
			'internal_links',
			'gsc',
			'social',
			'woocommerce',
		];

		$this->forAll(
			Generators::choose( 1, count( $all_modules ) )
		)
		->then(
			function ( int $count ) use ( $all_modules ) {
				// Generate a random subset of enabled modules
				$enabled_modules = array_slice( $all_modules, 0, $count );

				// Create mock Options
				$options = $this->create_mock_options( $enabled_modules );

				// Create Module_Manager
				$manager = new Module_Manager( $options );

				// Boot the manager (may fail due to missing WordPress functions, but that's OK)
				try {
					$manager->boot();
				} catch ( \Throwable $e ) {
					// Ignore exceptions and errors from WordPress function calls
				}

				// Verify all enabled modules are loaded (except WooCommerce if not installed)
				foreach ( $enabled_modules as $module_id ) {
					// Skip WooCommerce if not active
					if ( 'woocommerce' === $module_id && ! class_exists( 'WooCommerce' ) ) {
						continue;
					}
					$this->assertTrue(
						$manager->is_active( $module_id ),
						"Enabled module '$module_id' should be active"
					);

					$module = $manager->get_module( $module_id );
					$this->assertNotNull(
						$module,
						"Enabled module '$module_id' should be instantiated"
					);

					// Verify the module implements the Module interface
					$this->assertInstanceOf(
						'MeowSEO\Contracts\Module',
						$module,
						"Module '$module_id' should implement Module interface"
					);
				}
			}
		);
	}

	/**
	 * Property: Module_Manager respects Options on each boot
	 *
	 * For any configuration change in Options:
	 * 1. Module_Manager should read the enabled modules from Options
	 * 2. The loaded modules should reflect the current Options state
	 * 3. Changes to Options should be reflected in subsequent boots
	 *
	 * **Validates: Requirement 1.2**
	 *
	 * @return void
	 */
	public function test_module_manager_respects_options_on_each_boot(): void {
		$all_modules = [
			'meta',
			'schema',
			'sitemap',
			'redirects',
			'monitor_404',
			'internal_links',
			'gsc',
			'social',
			'woocommerce',
		];

		$this->forAll(
			Generators::choose( 0, count( $all_modules ) - 1 ),
			Generators::choose( 0, count( $all_modules ) - 1 )
		)
		->then(
			function ( int $first_idx, int $second_idx ) use ( $all_modules ) {
				// Generate two different sets of enabled modules
				$first_set = array_slice( $all_modules, 0, $first_idx + 1 );
				$second_set = array_slice( $all_modules, 0, $second_idx + 1 );

				// Create mock Options with first set
				$options = $this->create_mock_options( $first_set );

				// Create Module_Manager with first set
				$manager = new Module_Manager( $options );

				// Boot the manager (may fail due to missing WordPress functions, but that's OK)
				try {
					$manager->boot();
				} catch ( \Throwable $e ) {
					// Ignore exceptions and errors from WordPress function calls
				}

				// Verify first set is loaded (accounting for WooCommerce special case)
				$expected_first = array_filter(
					$first_set,
					function ( $module_id ) {
						if ( 'woocommerce' === $module_id && ! class_exists( 'WooCommerce' ) ) {
							return false;
						}
						return true;
					}
				);
				$this->assertEqualsCanonicalizing(
					$expected_first,
					array_keys( $manager->get_modules() ),
					'First boot should load the first set of modules'
				);

				// Update Options with second set
				$options = $this->create_mock_options( $second_set );

				// Create new Module_Manager with updated Options
				$manager = new Module_Manager( $options );

				// Boot the manager (may fail due to missing WordPress functions, but that's OK)
				try {
					$manager->boot();
				} catch ( \Throwable $e ) {
					// Ignore exceptions and errors from WordPress function calls
				}

				// Verify second set is loaded (accounting for WooCommerce special case)
				$expected_second = array_filter(
					$second_set,
					function ( $module_id ) {
						if ( 'woocommerce' === $module_id && ! class_exists( 'WooCommerce' ) ) {
							return false;
						}
						return true;
					}
				);
				$this->assertEqualsCanonicalizing(
					$expected_second,
					array_keys( $manager->get_modules() ),
					'Second boot should load the second set of modules'
				);
			}
		);
	}

	/**
	 * Create a mock Options instance with specified enabled modules.
	 *
	 * @param array $enabled_modules Array of enabled module IDs.
	 * @return Options Mock Options instance.
	 */
	private function create_mock_options( array $enabled_modules ): Options {
		// Create a mock Options object
		$options = $this->createMock( Options::class );

		// Mock the get_enabled_modules method to return our test data
		$options->method( 'get_enabled_modules' )
			->willReturn( $enabled_modules );

		// Mock other methods that might be called
		$options->method( 'get' )
			->willReturnCallback(
				function ( $key, $default = null ) {
					return $default;
				}
			);

		return $options;
	}
}
