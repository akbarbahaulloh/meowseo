<?php
/**
 * Module Manager class.
 *
 * Conditionally loads and instantiates plugin modules based on enabled settings.
 *
 * @package MeowSEO
 */

namespace MeowSEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module Manager class.
 */
class Module_Manager {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Loaded modules.
	 *
	 * @var array<string, Contracts\Module>
	 */
	private array $modules = array();

	/**
	 * Module registry mapping module IDs to class names.
	 *
	 * @var array<string, string>
	 */
	private array $module_registry = array(
		'meta'          => 'Modules\Meta\Meta',
		'schema'        => 'Modules\Schema\Schema',
		'sitemap'       => 'Modules\Sitemap\Sitemap',
		'redirects'     => 'Modules\Redirects\Redirects',
		'monitor_404'   => 'Modules\Monitor_404\Monitor_404',
		'internal_links' => 'Modules\Internal_Links\Internal_Links',
		'gsc'           => 'Modules\GSC\GSC',
		'social'        => 'Modules\Social\Social',
		'woocommerce'   => 'Modules\WooCommerce\WooCommerce',
	);

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot all enabled modules.
	 *
	 * @return void
	 */
	public function boot(): void {
		$enabled_modules = $this->options->get_enabled_modules();

		foreach ( $enabled_modules as $module_id ) {
			$this->load_module( $module_id );
		}

		// Boot all loaded modules.
		foreach ( $this->modules as $module_id => $module ) {
			try {
				$module->boot();
			} catch ( \Exception $e ) {
				// Log exception via Logger with full details.
				Helpers\Logger::error(
					'Failed to boot module: ' . $module_id,
					array(
						'exception_class' => get_class( $e ),
						'exception_message' => $e->getMessage(),
						'file' => $e->getFile(),
						'line' => $e->getLine(),
						'stack_trace' => $e->getTraceAsString(),
					)
				);
				
				// Continue booting remaining modules.
				continue;
			}
		}
	}

	/**
	 * Load a module by ID.
	 *
	 * @param string $module_id Module ID.
	 * @return bool True if module was loaded, false otherwise.
	 */
	private function load_module( string $module_id ): bool {
		// Check if module is already loaded.
		if ( isset( $this->modules[ $module_id ] ) ) {
			return true;
		}

		// Check if module exists in registry.
		if ( ! isset( $this->module_registry[ $module_id ] ) ) {
			return false;
		}

		// Special handling for WooCommerce module - only load if WooCommerce is active.
		if ( 'woocommerce' === $module_id && ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		// Build fully qualified class name.
		$class_name = 'MeowSEO\\' . $this->module_registry[ $module_id ];

		// Check if class exists (autoloader will load it).
		if ( ! class_exists( $class_name ) ) {
			return false;
		}

		// Instantiate the module.
		try {
			$module = new $class_name( $this->options );
			
			// Verify it implements the Module interface.
			if ( ! $module instanceof Contracts\Module ) {
				return false;
			}

			$this->modules[ $module_id ] = $module;
			return true;
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'MeowSEO: Failed to instantiate module ' . $module_id . ': ' . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Get a module by ID.
	 *
	 * @param string $module_id Module ID.
	 * @return Contracts\Module|null Module instance or null if not loaded.
	 */
	public function get_module( string $module_id ): ?Contracts\Module {
		return $this->modules[ $module_id ] ?? null;
	}

	/**
	 * Check if a module is active (loaded).
	 *
	 * @param string $module_id Module ID.
	 * @return bool True if module is active, false otherwise.
	 */
	public function is_active( string $module_id ): bool {
		return isset( $this->modules[ $module_id ] );
	}

	/**
	 * Get all loaded modules.
	 *
	 * @return array<string, Contracts\Module> Array of loaded modules.
	 */
	public function get_modules(): array {
		return $this->modules;
	}
}
