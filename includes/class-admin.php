<?php
/**
 * Admin class for MeowSEO plugin.
 *
 * Handles admin menu registration and settings page rendering.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO;

use MeowSEO\Admin\Log_Viewer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class
 *
 * Manages admin interface and settings page.
 * Requirement: 2.4
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Log_Viewer instance
	 *
	 * @since 1.0.0
	 * @var Log_Viewer
	 */
	private Log_Viewer $log_viewer;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot admin functionality
	 *
	 * Registers admin hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Initialize Log_Viewer (Requirement 7.1).
		$this->log_viewer = new Log_Viewer( $this->options );
		$this->log_viewer->boot();
	}

	/**
	 * Register admin menu
	 *
	 * Adds top-level admin menu page.
	 * Requirement: 2.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_admin_menu(): void {
		add_menu_page(
			__( 'MeowSEO Settings', 'meowseo' ),
			__( 'MeowSEO', 'meowseo' ),
			'manage_options',
			'meowseo-settings',
			array( $this, 'render_settings_page' ),
			'dashicons-search',
			80
		);
	}

	/**
	 * Render settings page
	 *
	 * Outputs the React root element for the settings UI.
	 * Requirement: 2.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page(): void {
		// Verify user has manage_options capability (Requirement 15.3).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="meowseo-settings-root"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets
	 *
	 * Loads React-based settings UI on the settings page.
	 * Requirement: 2.4
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only load on MeowSEO settings page.
		if ( 'toplevel_page_meowseo-settings' !== $hook_suffix ) {
			return;
		}

		// Enqueue the admin-settings asset
		$asset_file = \MEOWSEO_PATH . 'build/admin-settings.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'meowseo-admin-settings',
			\MEOWSEO_URL . 'build/admin-settings.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'meowseo-admin-settings',
			\MEOWSEO_URL . 'build/admin-settings.css',
			array( 'wp-components' ),
			$asset['version']
		);

		// Localize script with settings data.
		wp_localize_script(
			'meowseo-admin-settings',
			'meowseoAdmin',
			array(
				'restUrl'   => rest_url( 'meowseo/v1' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'isWooCommerceActive' => class_exists( 'WooCommerce' ),
				'settings'  => $this->options->get_all(),
			)
		);
	}
}
