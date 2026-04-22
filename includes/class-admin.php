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
use MeowSEO\Admin\Dashboard_Widgets;
use MeowSEO\Admin\Settings_Manager;
use MeowSEO\Admin\Tools_Manager;
use MeowSEO\Modules\Admin\List_Table_Columns;

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
	 * Dashboard_Widgets instance
	 *
	 * @since 1.0.0
	 * @var Dashboard_Widgets
	 */
	private Dashboard_Widgets $dashboard_widgets;

	/**
	 * Settings_Manager instance
	 *
	 * @since 1.0.0
	 * @var Settings_Manager
	 */
	private Settings_Manager $settings_manager;

	/**
	 * Tools_Manager instance
	 *
	 * @since 1.0.0
	 * @var Tools_Manager|null
	 */
	private ?Tools_Manager $tools_manager = null;

	/**
	 * List_Table_Columns instance
	 *
	 * @since 1.0.0
	 * @var List_Table_Columns|null
	 */
	private ?List_Table_Columns $list_table_columns = null;

	/**
	 * Module_Manager instance
	 *
	 * @since 1.0.0
	 * @var Module_Manager
	 */
	private Module_Manager $module_manager;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options        $options        Options instance.
	 * @param Module_Manager $module_manager Module_Manager instance.
	 */
	public function __construct( Options $options, Module_Manager $module_manager ) {
		$this->options        = $options;
		$this->module_manager = $module_manager;
	}

	/**
	 * Get Tools_Manager instance (lazy initialization)
	 *
	 * @since 1.0.0
	 * @return Tools_Manager Tools_Manager instance.
	 */
	private function get_tools_manager(): Tools_Manager {
		if ( ! isset( $this->tools_manager ) ) {
			$this->tools_manager = new Tools_Manager( $this->options );
		}
		return $this->tools_manager;
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

		// Initialize Dashboard_Widgets (Requirement 2.1).
		$this->dashboard_widgets = new Dashboard_Widgets( $this->options, $this->module_manager );

		// Initialize Settings_Manager (Requirement 4.1).
		$this->settings_manager = new Settings_Manager( $this->options, $this->module_manager );
		$this->settings_manager->register_handlers();

		// Initialize Tools_Manager (Requirement 10.1).
		$this->tools_manager = $this->get_tools_manager();

		// Initialize List_Table_Columns (Requirement 3.1).
		$this->list_table_columns = new List_Table_Columns( $this->options );
		$this->list_table_columns->register_hooks();

		// Register admin-post handlers for tools operations.
		add_action( 'admin_post_meowseo_export_settings', array( $this, 'handle_export_settings' ) );
		add_action( 'admin_post_meowseo_export_redirects', array( $this, 'handle_export_redirects' ) );
		add_action( 'admin_post_meowseo_import_settings', array( $this, 'handle_import_settings' ) );
		add_action( 'admin_post_meowseo_import_redirects', array( $this, 'handle_import_redirects' ) );
		add_action( 'admin_post_meowseo_clear_logs', array( $this, 'handle_clear_logs' ) );
		add_action( 'admin_post_meowseo_repair_tables', array( $this, 'handle_repair_tables' ) );
		add_action( 'admin_post_meowseo_flush_caches', array( $this, 'handle_flush_caches' ) );
		add_action( 'admin_post_meowseo_bulk_descriptions', array( $this, 'handle_bulk_descriptions' ) );
		add_action( 'admin_post_meowseo_scan_missing', array( $this, 'handle_scan_missing' ) );
	}

	/**
	 * Register admin menu
	 *
	 * Adds top-level admin menu page with submenu pages.
	 * Requirements: 1.1, 1.2, 1.3, 1.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_admin_menu(): void {
		// Custom SEO icon (magnifying glass with chart) - free to use.
		$icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">' .
			'<path fill="#a0aec0" d="M9 0C4.03 0 0 4.03 0 9s4.03 9 9 9c1.84 0 3.55-.55 4.99-1.5l4.51 4.51 1.41-1.41-4.51-4.51C16.45 14.55 17 12.84 17 11c0-4.97-4.03-9-9-9zm0 16c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/>' .
			'<path fill="#a0aec0" d="M8 5h2v5H8zM8 11h2v2H8z"/>' .
			'</svg>'
		);

		// Add top-level menu with SEO icon.
		add_menu_page(
			__( 'MeowSEO', 'meowseo' ),
			__( 'MeowSEO', 'meowseo' ),
			'manage_options',
			'meowseo',
			array( $this, 'render_dashboard_page' ),
			$icon_svg,
			25  // Position after Dashboard, before Media
		);

		// Register submenu pages in logical order.
		// 1. Dashboard - Overview
		add_submenu_page(
			'meowseo',
			__( 'Dashboard', 'meowseo' ),
			__( 'Dashboard', 'meowseo' ),
			'manage_options',
			'meowseo',
			array( $this, 'render_dashboard_page' )
		);

		// 2. Settings - Configuration
		add_submenu_page(
			'meowseo',
			__( 'Settings', 'meowseo' ),
			__( 'Settings', 'meowseo' ),
			'manage_options',
			'meowseo-settings',
			array( $this, 'render_settings_page' )
		);

		// 3. Search Console - Integration
		add_submenu_page(
			'meowseo',
			__( 'Search Console', 'meowseo' ),
			__( 'Search Console', 'meowseo' ),
			'manage_options',
			'meowseo-search-console',
			array( $this, 'render_search_console_page' )
		);

		// 4. Redirects - URL Management
		add_submenu_page(
			'meowseo',
			__( 'Redirects', 'meowseo' ),
			__( 'Redirects', 'meowseo' ),
			'manage_options',
			'meowseo-redirects',
			array( $this, 'render_redirects_page' )
		);

		// 5. 404 Monitor - Error Tracking
		add_submenu_page(
			'meowseo',
			__( '404 Monitor', 'meowseo' ),
			__( '404 Monitor', 'meowseo' ),
			'manage_options',
			'meowseo-404-monitor',
			array( $this, 'render_404_monitor_page' )
		);

		// 6. Tools - Utilities (last)
		add_submenu_page(
			'meowseo',
			__( 'Tools', 'meowseo' ),
			__( 'Tools', 'meowseo' ),
			'manage_options',
			'meowseo-tools',
			array( $this, 'render_tools_page' )
		);
	}

	/**
	 * Render dashboard page
	 *
	 * Outputs the dashboard UI with async-loaded widgets.
	 * Requirement: 1.4, 2.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_dashboard_page(): void {
		// Verify user has manage_options capability (Requirement 1.4).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'MeowSEO Dashboard', 'meowseo' ); ?></h1>
			<?php $this->dashboard_widgets->render_widgets(); ?>
		</div>
		<?php
	}

	/**
	 * Render settings page
	 *
	 * Outputs the settings UI with tabbed interface.
	 * Requirements: 1.4, 4.1, 4.2, 4.3
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page(): void {
		// Verify user has manage_options capability (Requirement 1.4).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php $this->settings_manager->render_settings_form(); ?>
		</div>
		<?php
	}

	/**
	 * Render redirects page
	 *
	 * Outputs the redirects management UI.
	 * Requirement: 1.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_redirects_page(): void {
		// Verify user has manage_options capability (Requirement 1.4).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Redirects', 'meowseo' ); ?></h1>
			<div id="meowseo-redirects-root"></div>
		</div>
		<?php
	}

	/**
	 * Render 404 monitor page
	 *
	 * Outputs the 404 monitoring UI.
	 * Requirement: 1.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_404_monitor_page(): void {
		// Verify user has manage_options capability (Requirement 1.4).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( '404 Monitor', 'meowseo' ); ?></h1>
			<div id="meowseo-404-monitor-root"></div>
		</div>
		<?php
	}

	/**
	 * Render search console page
	 *
	 * Outputs the Google Search Console integration UI.
	 * Requirement: 1.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_search_console_page(): void {
		// Verify user has manage_options capability (Requirement 1.4).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Search Console', 'meowseo' ); ?></h1>
			<div id="meowseo-search-console-root"></div>
		</div>
		<?php
	}

	/**
	 * Render tools page
	 *
	 * Outputs the tools UI for import/export and maintenance.
	 * Requirement: 1.4, 29.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_tools_page(): void {
		// Verify user has manage_options capability (Requirement 1.4, 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Tools', 'meowseo' ); ?></h1>
			<?php $this->get_tools_manager()->render_tools_page(); ?>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets
	 *
	 * Loads assets for MeowSEO admin pages.
	 * Requirement: 1.5
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Enqueue list table columns CSS on all post list pages.
		$screen = get_current_screen();
		if ( $screen && $screen->base === 'edit' ) {
			wp_enqueue_style(
				'meowseo-list-table-columns',
				\MEOWSEO_URL . 'admin/css/list-table-columns.css',
				array(),
				\MEOWSEO_VERSION
			);
		}

		// Map hook suffixes to asset names.
		$page_assets = array(
			'toplevel_page_meowseo'           => 'admin-dashboard',
			'meowseo_page_meowseo-settings'   => 'admin-settings',
			'meowseo_page_meowseo-redirects'  => 'admin-redirects',
			'meowseo_page_meowseo-404-monitor' => 'admin-404-monitor',
			'meowseo_page_meowseo-search-console' => 'admin-search-console',
			'meowseo_page_meowseo-tools'      => 'admin-tools',
		);

		// Check if current page has assets to load.
		if ( ! isset( $page_assets[ $hook_suffix ] ) ) {
			return;
		}

		$asset_name = $page_assets[ $hook_suffix ];
		$asset_file = \MEOWSEO_PATH . "build/{$asset_name}.asset.php";

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		// Enqueue JavaScript.
		wp_enqueue_script(
			"meowseo-{$asset_name}",
			\MEOWSEO_URL . "build/{$asset_name}.js",
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Enqueue CSS.
		wp_enqueue_style(
			"meowseo-{$asset_name}",
			\MEOWSEO_URL . "build/{$asset_name}.css",
			array( 'wp-components' ),
			$asset['version']
		);

		// Localize script with common data.
		// Generate unique nonce for each admin page (Requirement 28.5).
		$page_nonce_action = "meowseo_{$asset_name}_nonce";
		wp_localize_script(
			"meowseo-{$asset_name}",
			'meowseoAdmin',
			array(
				'restUrl'             => rest_url( 'meowseo/v1' ),
				'nonce'               => wp_create_nonce( 'wp_rest' ),
				'pageNonce'           => wp_create_nonce( $page_nonce_action ),
				'pageNonceAction'     => $page_nonce_action,
				'isWooCommerceActive' => class_exists( 'WooCommerce' ),
				'settings'            => $this->options->get_all(),
				'currentPage'         => $asset_name,
			)
		);
	}

	/**
	 * Handle export settings
	 *
	 * Requirements: 28.2, 28.4, 29.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_export_settings(): void {
		// Verify nonce (Requirement 28.2).
		if ( ! isset( $_POST['meowseo_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_tools_nonce'] ), 'meowseo_tools_export_settings' ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify capability (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Export settings.
		$content = $this->tools_manager->export_settings();

		// Send file.
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="meowseo-settings-' . gmdate( 'Y-m-d-H-i-s' ) . '.json"' );
		echo $content;
		exit;
	}

	/**
	 * Handle export redirects
	 *
	 * Requirements: 28.2, 28.4, 29.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_export_redirects(): void {
		// Verify nonce (Requirement 28.2).
		if ( ! isset( $_POST['meowseo_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_tools_nonce'] ), 'meowseo_tools_export_redirects' ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify capability (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Export redirects.
		$content = $this->tools_manager->export_redirects();

		// Send file.
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="meowseo-redirects-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv"' );
		echo $content;
		exit;
	}

	/**
	 * Handle import settings
	 *
	 * Requirements: 28.2, 28.4, 29.1, 30.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_import_settings(): void {
		// Verify nonce (Requirement 28.2).
		if ( ! isset( $_POST['meowseo_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_tools_nonce'] ), 'meowseo_tools_import_settings' ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify capability (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Check file upload.
		if ( empty( $_FILES['import_settings_file'] ) ) {
			wp_safe_redirect( add_query_arg( 'meowseo_import_error', urlencode( __( 'No file uploaded.', 'meowseo' ) ), wp_get_referer() ) );
			exit;
		}

		// Sanitize file upload data (Requirement 30.1).
		$file = array(
			'name'     => sanitize_file_name( $_FILES['import_settings_file']['name'] ),
			'type'     => sanitize_text_field( $_FILES['import_settings_file']['type'] ),
			'tmp_name' => sanitize_text_field( $_FILES['import_settings_file']['tmp_name'] ),
			'error'    => (int) $_FILES['import_settings_file']['error'],
			'size'     => (int) $_FILES['import_settings_file']['size'],
		);

		// Import settings.
		$result = $this->tools_manager->import_settings( $file );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'meowseo_import_error', urlencode( $result->get_error_message() ), wp_get_referer() ) );
		} else {
			wp_safe_redirect( add_query_arg( 'meowseo_import_success', '1', wp_get_referer() ) );
		}

		exit;
	}

	/**
	 * Handle import redirects
	 *
	 * Requirements: 28.2, 28.4, 29.1, 30.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_import_redirects(): void {
		// Verify nonce (Requirement 28.2).
		if ( ! isset( $_POST['meowseo_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_tools_nonce'] ), 'meowseo_tools_import_redirects' ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify capability (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Check file upload.
		if ( empty( $_FILES['import_redirects_file'] ) ) {
			wp_safe_redirect( add_query_arg( 'meowseo_import_error', urlencode( __( 'No file uploaded.', 'meowseo' ) ), wp_get_referer() ) );
			exit;
		}

		// Sanitize file upload data (Requirement 30.1).
		$file = array(
			'name'     => sanitize_file_name( $_FILES['import_redirects_file']['name'] ),
			'type'     => sanitize_text_field( $_FILES['import_redirects_file']['type'] ),
			'tmp_name' => sanitize_text_field( $_FILES['import_redirects_file']['tmp_name'] ),
			'error'    => (int) $_FILES['import_redirects_file']['error'],
			'size'     => (int) $_FILES['import_redirects_file']['size'],
		);

		// Import redirects.
		$result = $this->tools_manager->import_redirects( $file );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'meowseo_import_error', urlencode( $result->get_error_message() ), wp_get_referer() ) );
		} else {
			wp_safe_redirect( add_query_arg( 'meowseo_import_success', '1', wp_get_referer() ) );
		}

		exit;
	}

	/**
	 * Handle clear logs
	 *
	 * Requirements: 28.2, 28.4, 29.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_clear_logs(): void {
		// Verify nonce (Requirement 28.2).
		if ( ! isset( $_POST['meowseo_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_tools_nonce'] ), 'meowseo_tools_clear_logs' ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify capability (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Clear logs.
		$deleted = $this->tools_manager->clear_old_logs();

		wp_safe_redirect( add_query_arg( 'meowseo_maintenance_success', urlencode( sprintf( __( 'Deleted %d log entries.', 'meowseo' ), $deleted ) ), wp_get_referer() ) );
		exit;
	}

	/**
	 * Handle repair tables
	 *
	 * Requirements: 28.2, 28.4, 29.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_repair_tables(): void {
		// Verify nonce (Requirement 28.2).
		if ( ! isset( $_POST['meowseo_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_tools_nonce'] ), 'meowseo_tools_repair_tables' ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify capability (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Repair tables.
		$this->tools_manager->repair_tables();

		wp_safe_redirect( add_query_arg( 'meowseo_maintenance_success', urlencode( __( 'Database tables repaired.', 'meowseo' ) ), wp_get_referer() ) );
		exit;
	}

	/**
	 * Handle flush caches
	 *
	 * Requirements: 28.2, 28.4, 29.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_flush_caches(): void {
		// Verify nonce (Requirement 28.2).
		if ( ! isset( $_POST['meowseo_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_tools_nonce'] ), 'meowseo_tools_flush_caches' ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify capability (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Flush caches.
		$this->tools_manager->flush_caches();

		wp_safe_redirect( add_query_arg( 'meowseo_maintenance_success', urlencode( __( 'Caches flushed.', 'meowseo' ) ), wp_get_referer() ) );
		exit;
	}

	/**
	 * Handle bulk descriptions
	 *
	 * Requirements: 28.2, 28.4, 29.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_bulk_descriptions(): void {
		// Verify nonce (Requirement 28.2).
		if ( ! isset( $_POST['meowseo_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_tools_nonce'] ), 'meowseo_tools_bulk_descriptions' ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify capability (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Generate descriptions.
		$result = $this->tools_manager->bulk_generate_descriptions();

		wp_safe_redirect( add_query_arg( 'meowseo_seo_success', urlencode( sprintf( __( 'Generated %d descriptions.', 'meowseo' ), $result['generated'] ) ), wp_get_referer() ) );
		exit;
	}

	/**
	 * Handle scan missing SEO data
	 *
	 * Requirements: 28.2, 28.4, 29.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_scan_missing(): void {
		// Verify nonce (Requirement 28.2).
		if ( ! isset( $_POST['meowseo_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_tools_nonce'] ), 'meowseo_tools_scan_missing' ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify capability (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Scan for missing data.
		$report = $this->tools_manager->scan_missing_seo_data();

		// Store report in transient for display.
		set_transient( 'meowseo_scan_report', $report, 3600 );

		wp_safe_redirect( add_query_arg( 'meowseo_scan_complete', '1', wp_get_referer() ) );
		exit;
	}

}
