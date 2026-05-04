<?php
/**
 * Monitor 404 Admin Interface
 *
 * Provides admin UI for managing 404 log entries.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\Monitor_404
 */

namespace MeowSEO\Modules\Monitor_404;

use MeowSEO\Options;
use MeowSEO\Helpers\DB;
use MeowSEO\Helpers\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Monitor 404 Admin class
 *
 * Handles admin interface for 404 log management.
 * Requirements: 13.1, 13.2, 13.3, 13.4, 13.5
 */
class Monitor_404_Admin {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
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
	 * @return void
	 */
	public function boot(): void {
		// Menu registration is handled by Admin class to prevent duplicates
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register admin menu
	 *
	 * Adds 404 Monitor submenu under MeowSEO menu.
	 * Requirement: 13.1
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'meowseo-settings',
			__( '404 Monitor', 'meowseo' ),
			__( '404 Monitor', 'meowseo' ),
			'manage_options',
			'meowseo-404-monitor',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( string $hook ): void {
		// Only load on our admin page.
		if ( 'meowseo_page_meowseo-404-monitor' !== $hook ) {
			return;
		}

		$asset_file = MEOWSEO_PATH . 'build/admin-404-monitor.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = include $asset_file;

		wp_enqueue_script(
			'meowseo-admin-404-monitor',
			MEOWSEO_URL . 'build/admin-404-monitor.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_enqueue_style(
			'meowseo-admin-404-monitor',
			MEOWSEO_URL . 'build/admin-404-monitor.css',
			array(),
			$assets['version']
		);

		wp_set_script_translations( 'meowseo-admin-404-monitor', 'meowseo' );
	}

	/**
	 * Render admin page
	 *
	 * Outputs the main 404 monitor page.
	 * Requirements: 13.1, 13.2, 13.3, 13.4, 13.5
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Verify user has manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		echo '<div id="meowseo-404-monitor-root"></div>';
	}
}
