<?php
/**
 * Main Plugin class.
 *
 * Singleton that holds references to Module_Manager, Options, and Installer.
 *
 * @package MeowSEO
 */

namespace MeowSEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin class.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Module Manager instance.
	 *
	 * @var Module_Manager|null
	 */
	private ?Module_Manager $module_manager = null;

	/**
	 * Options instance.
	 *
	 * @var Options|null
	 */
	private ?Options $options = null;

	/**
	 * REST API instance.
	 *
	 * @var REST_API|null
	 */
	private ?REST_API $rest_api = null;

	/**
	 * WPGraphQL instance.
	 *
	 * @var WPGraphQL|null
	 */
	private ?WPGraphQL $wpgraphql = null;

	/**
	 * Admin instance.
	 *
	 * @var Admin|null
	 */
	private ?Admin $admin = null;

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		// Initialize Options.
		$this->options = new Options();
	}

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the plugin.
	 *
	 * Initializes Module_Manager and triggers module loading.
	 *
	 * @return void
	 * @throws \Exception If critical initialization fails.
	 */
	public function boot(): void {
		try {
			// Initialize Module_Manager.
			$this->module_manager = new Module_Manager( $this->options );

			// Boot all enabled modules (wrapped in try/catch internally).
			$this->module_manager->boot();

			// Initialize REST API layer.
			$this->rest_api = new REST_API( $this->options, $this->module_manager );
			add_action( 'rest_api_init', array( $this->rest_api, 'register_routes' ) );

			// Initialize WPGraphQL integration if WPGraphQL is active.
			if ( class_exists( 'WPGraphQL' ) ) {
				try {
					$this->wpgraphql = new WPGraphQL( $this->module_manager );
					add_action( 'graphql_register_types', array( $this->wpgraphql, 'register_fields' ) );
				} catch ( \Exception $e ) {
					// Log WPGraphQL registration error but don't break the plugin.
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'MeowSEO: Failed to initialize WPGraphQL integration: ' . $e->getMessage() );
					}
				}
			}

			// Initialize Admin interface (only in admin context).
			if ( is_admin() ) {
				try {
					$this->admin = new Admin( $this->options );
					$this->admin->boot();
				} catch ( \Exception $e ) {
					// Log admin initialization error but don't break the plugin.
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'MeowSEO: Failed to initialize admin interface: ' . $e->getMessage() );
					}
				}
			}
		} catch ( \Exception $e ) {
			// Re-throw critical errors for handling at the entry point.
			throw $e;
		}
	}

	/**
	 * Get Module_Manager instance.
	 *
	 * @return Module_Manager|null
	 */
	public function get_module_manager(): ?Module_Manager {
		return $this->module_manager;
	}

	/**
	 * Get Options instance.
	 *
	 * @return Options
	 */
	public function get_options(): Options {
		return $this->options;
	}

	/**
	 * Prevent cloning of the instance.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing of the instance.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
