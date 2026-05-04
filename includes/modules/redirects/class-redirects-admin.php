<?php
/**
 * Redirects Admin Interface
 *
 * Provides admin UI for managing redirect rules.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Redirects;

use MeowSEO\Options;
use MeowSEO\Helpers\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirects Admin class
 *
 * Handles admin interface for redirect management.
 * Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6
 *
 * @since 1.0.0
 */
class Redirects_Admin {

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
	 * Adds Redirects submenu under MeowSEO menu.
	 * Requirement: 12.1
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'meowseo-settings',
			__( 'Redirects', 'meowseo' ),
			__( 'Redirects', 'meowseo' ),
			'manage_options',
			'meowseo-redirects',
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
		if ( 'meowseo_page_meowseo-redirects' !== $hook ) {
			return;
		}

		$asset_file = MEOWSEO_PATH . 'build/admin-redirects.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = include $asset_file;

		wp_enqueue_script(
			'meowseo-admin-redirects',
			MEOWSEO_URL . 'build/admin-redirects.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_enqueue_style(
			'meowseo-admin-redirects',
			MEOWSEO_URL . 'build/admin-redirects.css',
			array(),
			$assets['version']
		);

		wp_set_script_translations( 'meowseo-admin-redirects', 'meowseo' );
	}

	/**
	 * Render admin page
	 *
	 * Outputs the main redirects management page.
	 * Requirements: 12.1, 12.2
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Verify user has manage_options capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		echo '<div id="meowseo-redirects-root"></div>';
	}
}
