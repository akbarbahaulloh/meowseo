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
use MeowSEO\Admin\Setup_Wizard;
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
	 * Setup_Wizard instance
	 *
	 * @since 1.0.0
	 * @var Setup_Wizard
	 */
	private Setup_Wizard $setup_wizard;

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

		// Initialize Setup_Wizard.
		$this->setup_wizard = new Setup_Wizard( $this->options, $this->module_manager );
		$this->setup_wizard->init();

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

		// MeowIndex AJAX handlers.
		add_action( 'wp_ajax_meowseo_meowindex_regenerate_key', array( $this, 'ajax_meowindex_regenerate_key' ) );
		add_action( 'wp_ajax_meowseo_meowindex_console', array( $this, 'ajax_meowindex_console' ) );

		// Plugin action links (Requirements: matched MeowPack aesthetic).
		add_filter( 'plugin_action_links_' . plugin_basename( \MEOWSEO_FILE ), array( $this, 'add_plugin_action_links' ) );

		// Handle manual update check from plugin action links.
		add_action( 'admin_init', array( $this, 'handle_manual_update_check' ) );

		// Display admin notices.
		add_action( 'admin_notices', array( $this, 'display_update_notice' ) );

		// Save screen options (Requirement: standard WP behavior).
		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );



		// Reorder submenus (Requirement: Logical flow).
		add_action( 'admin_menu', array( $this, 'reorder_submenus' ), 999 );
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

		// Broken Links submenu.
		add_submenu_page(
			'meowseo',
			__( 'Broken Links', 'meowseo' ),
			__( 'Broken Links', 'meowseo' ),
			'manage_options',
			'meowseo-broken-links',
			array( $this, 'render_broken_links_page' )
		);

		// Content Refresh submenu.
		add_submenu_page(
			'meowseo',
			__( 'Content Refresh', 'meowseo' ),
			__( 'Content Refresh', 'meowseo' ),
			'manage_options',
			'meowseo-content-refresh',
			array( $this, 'render_content_refresh_page' )
		);

		// 6. Import - Migration
		$import_page = add_submenu_page(
			'meowseo',
			__( 'Import SEO Data', 'meowseo' ),
			__( 'Import', 'meowseo' ),
			'manage_options',
			'meowseo-import',
			array( $this, 'render_import_page' )
		);

		if ( $import_page ) {
			add_action( "load-$import_page", array( $this, 'add_import_screen_options' ) );
		}

		// 7. Tools - Utilities
		add_submenu_page(
			'meowseo',
			__( 'Tools', 'meowseo' ),
			__( 'Tools', 'meowseo' ),
			'manage_options',
			'meowseo-tools',
			array( $this, 'render_tools_page' )
		);

		// 8. Updates - GitHub update configuration
		add_submenu_page(
			'meowseo',
			__( 'Updates', 'meowseo' ),
			__( 'Updates', 'meowseo' ),
			'manage_options',
			'meowseo-updates',
			array( $this, 'render_update_settings_page' )
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
	 * Render broken links page
	 *
	 * Outputs the broken links management UI.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_broken_links_page(): void {
		// Verify user has manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		$internal_links = $this->module_manager->get_module( 'internal_links' );
		if ( $internal_links ) {
			if ( ! class_exists( 'MeowSEO\Modules\Internal_Links\Broken_Links_Admin' ) ) {
				require_once MEOWSEO_PATH . 'includes/modules/internal_links/class-broken-links-admin.php';
			}
			$admin = new \MeowSEO\Modules\Internal_Links\Broken_Links_Admin( $this->options );
			$admin->render_page();
		}
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

		// Handle OAuth callback.
		if ( isset( $_GET['action'] ) && 'oauth_callback' === $_GET['action'] && isset( $_GET['code'] ) ) {
			$gsc = $this->module_manager->get_module( 'gsc' );
			if ( $gsc ) {
				$success = $gsc->handle_oauth_callback( sanitize_text_field( $_GET['code'] ) );
				if ( $success ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Google Search Console connected successfully!', 'meowseo' ) . '</p></div>';
				} else {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to connect Google Search Console. Please check your credentials.', 'meowseo' ) . '</p></div>';
				}
			}
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Search Console', 'meowseo' ); ?></h1>
			<div id="meowseo-search-console-root"></div>
		</div>
		<?php
	}

	/**
	 * Render import page
	 *
	 * Outputs the import UI for migration from Yoast/RankMath.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_import_page(): void {
		// Verify user has manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		$import_module = $this->module_manager->get_module( 'import' );
		if ( $import_module ) {
			$import_module->get_import_admin()->render_import_page();
		} else {
			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Import SEO Data', 'meowseo' ); ?></h1>
				<div class="notice notice-error">
					<p><?php echo esc_html__( 'Import module is not active. Please enable it in settings.', 'meowseo' ); ?></p>
				</div>
			</div>
			<?php
		}
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
	 * Render the GitHub update settings page.
	 *
	 * @return void
	 */
	public function render_update_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		/** @var \MeowSEO\Updater\GitHub_Updater|null $updater */
		$updater = $GLOBALS['meowseo_updater'] ?? null;

		// Handle form actions.
		if ( isset( $_POST['meowseo_update_action'] ) ) {
			$update_action = sanitize_key( $_POST['meowseo_update_action'] );

			if ( 'save_settings' === $update_action ) {
				check_admin_referer( 'meowseo_save_update_settings', 'meowseo_update_settings_nonce' );

				// Save branch.
				$branch = sanitize_key( $_POST['meowseo_update_branch'] ?? 'main' );
				if ( ! in_array( $branch, array( 'main', 'master', 'develop' ), true ) ) {
					$branch = 'main';
				}
				update_option( 'meowseo_update_branch', $branch );

				// Save token only if a new value was provided.
				$raw_token = sanitize_text_field( $_POST['meowseo_github_token'] ?? '' );
				if ( ! empty( $raw_token ) ) {
					$encrypted = \MeowSEO\Updater\GitHub_Updater::encrypt_token( $raw_token );
					update_option( 'meowseo_github_token', $encrypted );
				}

				if ( $updater ) {
					$updater->clear_cache();
				}

				wp_redirect( add_query_arg( 'updated', '1', menu_page_url( 'meowseo-updates', false ) ) );
				exit;
			}

			if ( 'clear_cache' === $update_action ) {
				check_admin_referer( 'meowseo_clear_update_cache', 'meowseo_cache_nonce' );
				if ( $updater ) {
					$updater->clear_cache();
				}
				wp_redirect( add_query_arg( 'cache_cleared', '1', menu_page_url( 'meowseo-updates', false ) ) );
				exit;
			}

			if ( 'check_now' === $update_action ) {
				check_admin_referer( 'meowseo_check_update_now', 'meowseo_check_nonce' );
				if ( $updater ) {
					$updater->clear_cache();
				}
				// Trigger WordPress update check.
				delete_site_transient( 'update_plugins' );
				wp_update_plugins();
				wp_redirect( menu_page_url( 'meowseo-updates', false ) );
				exit;
			}
		}

		// Gather display data.
		$installed_sha  = get_option( 'meowseo_installed_sha', '' );
		$latest_commit  = $updater ? $updater->get_latest_commit() : false;
		$latest_sha     = $latest_commit ? $latest_commit->sha : '';
		$update_available = ! empty( $latest_sha ) && $latest_sha !== $installed_sha;
		$has_token      = $updater ? $updater->has_token() : ! empty( get_option( 'meowseo_github_token', '' ) );
		$branch         = get_option( 'meowseo_update_branch', 'main' );

		$token_placeholder  = $has_token ? __( '••••••••••••  (token saved — enter a new value to replace)', 'meowseo' ) : __( 'Paste your GitHub PAT here', 'meowseo' );
		$current_version    = defined( 'MEOWSEO_VERSION' ) ? MEOWSEO_VERSION : '1.0.0';
		$nonce              = wp_create_nonce( 'meowseo_save_update_settings' );

		include MEOWSEO_PATH . 'includes/admin/views/update-settings.php';
	}

	/**
	 * Add screen options for the import page.
	 *
	 * @return void
	 */
	public function add_import_screen_options(): void {
		add_screen_option( 'per_page', array(
			'label'   => __( 'Items per page', 'meowseo' ),
			'default' => 20,
			'option'  => 'meowseo_import_per_page',
		) );
	}

	/**
	 * Save screen options.
	 *
	 * @param mixed  $status The value to save instead of the option value.
	 * @param string $option The screen option name.
	 * @param mixed  $value  The screen option value.
	 * @return mixed The saved value.
	 */
	public function save_screen_options( $status, $option, $value ) {
		if ( 'meowseo_import_per_page' === $option ) {
			return $value;
		}
		return $status;
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

	/**
	 * Add custom links to the plugin list
	 *
	 * Adds "Pengaturan" and "Cek Pembaruan" links to the MeowSEO plugin entry.
	 *
	 * @since 1.0.0
	 * @param array $links Array of plugin action links.
	 * @return array Modified array of plugin action links.
	 */
	public function add_plugin_action_links( array $links ): array {
		$settings_url = admin_url( 'admin.php?page=meowseo-settings' );
		$update_url   = wp_nonce_url(
			add_query_arg(
				array(
					'meowseo_action' => 'check_update',
				),
				admin_url( 'plugins.php' )
			),
			'meowseo_check_update'
		);

		$custom_links = array(
			'settings' => sprintf( '<a href="%s">%s</a>', esc_url( $settings_url ), esc_html__( 'Pengaturan', 'meowseo' ) ),
			'update'   => sprintf( '<a href="%s">%s</a>', esc_url( $update_url ), esc_html__( 'Cek Pembaruan', 'meowseo' ) ),
		);

		return array_merge( $custom_links, $links );
	}

	/**
	 * Handle manual update check from plugin action links
	 */
	public function handle_manual_update_check(): void {
		if ( ! isset( $_GET['meowseo_action'] ) || 'check_update' !== $_GET['meowseo_action'] ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'meowseo_check_update' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'meowseo' ) );
		}

		// Verify capability.
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to check for updates.', 'meowseo' ) );
		}

		// Clear the cache.
		if ( isset( $GLOBALS['meowseo_updater'] ) ) {
			$GLOBALS['meowseo_updater']->clear_cache();
		}
		delete_site_transient( 'update_plugins' ); // Force WP to re-check all plugins.

		// Redirect back with a notice flag.
		wp_safe_redirect( admin_url( 'plugins.php?meowseo_update_checked=1' ) );
		exit;
	}

	/**
	 * Display a notice after checking for updates manually.
	 */
	public function display_update_notice(): void {
		if ( isset( $_GET['meowseo_update_checked'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'MeowSEO berhasil memeriksa pembaruan di GitHub. Jika ada versi baru, tombol "Update Now" akan muncul di bawah deskripsi plugin.', 'meowseo' ) . '</p></div>';
		}
	}


	/**
	 * Reorder MeowSEO submenus
	 *
	 * Organizes submenus in a logical order to improve user experience.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function reorder_submenus(): void {
		global $submenu;

		if ( ! isset( $submenu['meowseo'] ) ) {
			return;
		}

		// Desired order of submenu slugs.
		$desired_order = array(
			'meowseo',                          // Dashboard
			'meowseo-analytics',               // Analytics
			'meowseo-search-console',          // Search Console
			'edit.php?post_type=meowseo_location', // All Locations
			'meowseo-redirects',               // Redirects
			'meowseo-404-monitor',             // 404 Monitor
			'meowseo-orphaned',                // Orphaned Content
			'meowseo-roles',                   // Role Manager
			'meowseo-tools',                   // Tools
			'meowseo-import',                  // Import
			'meowseo-settings',                // Settings
		);

		$current_submenu = $submenu['meowseo'];
		$new_submenu     = array();

		// 1. Add items that match our desired order.
		foreach ( $desired_order as $slug ) {
			foreach ( $current_submenu as $key => $item ) {
				if ( isset( $item[2] ) && $item[2] === $slug ) {
					$new_submenu[] = $item;
					unset( $current_submenu[ $key ] );
					break;
				}
			}
		}

		// 2. Add any remaining items that weren't in our list (e.g. from third-party extensions).
		if ( ! empty( $current_submenu ) ) {
			foreach ( $current_submenu as $item ) {
				$new_submenu[] = $item;
			}
		}

		$submenu['meowseo'] = $new_submenu;
	}

	/**
	 * AJAX: Regenerate IndexNow API key
	 *
	 * @return void
	 */
	public function ajax_meowindex_regenerate_key(): void {
		check_ajax_referer( 'meowseo_meowindex_regenerate_key', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'meowseo' ) ) );
		}

		$meowindex_module = $this->module_manager->get_module( 'meowindex' );
		if ( ! $meowindex_module ) {
			wp_send_json_error( array( 'message' => __( 'MeowIndex module is not active.', 'meowseo' ) ) );
		}

		$new_key = $meowindex_module->get_client()->generate_api_key();
		// Also clear any cached Google tokens.
		delete_transient( 'meowseo_google_indexing_token' );

		wp_send_json_success( array( 'key' => $new_key ) );
	}

	/**
	 * AJAX: Manual URL submission console
	 *
	 * Handles console submissions for IndexNow submit, Google update,
	 * Google delete, and Google URL status check.
	 *
	 * @return void
	 */
	public function ajax_meowindex_console(): void {
		check_ajax_referer( 'meowseo_meowindex_console', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'meowseo' ) ) );
		}

		$meowindex_module = $this->module_manager->get_module( 'meowindex' );
		if ( ! $meowindex_module ) {
			wp_send_json_error( array( 'message' => __( 'MeowIndex module is not active.', 'meowseo' ) ) );
		}

		$client     = $meowindex_module->get_client();
		$raw_urls   = isset( $_POST['urls'] ) ? sanitize_textarea_field( wp_unslash( $_POST['urls'] ) ) : '';
		$api_action = isset( $_POST['api_action'] ) ? sanitize_key( $_POST['api_action'] ) : '';

		// Parse URLs — one per line, skip empty.
		$urls = array_filter(
			array_map( 'trim', explode( "\n", $raw_urls ) ),
			fn( $u ) => ! empty( $u ) && filter_var( $u, FILTER_VALIDATE_URL )
		);

		if ( empty( $urls ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid URLs found.', 'meowseo' ) ) );
		}

		$urls    = array_values( $urls );
		$results = array();

		switch ( $api_action ) {
			case 'indexnow_submit':
				$results = $client->submit_urls( $urls, 'URL_UPDATED' );
				break;

			case 'google_update':
				$results = $client->submit_urls( $urls, 'URL_UPDATED' );
				break;

			case 'google_delete':
				$results = $client->submit_urls( $urls, 'URL_DELETED' );
				break;

			case 'google_status':
				foreach ( $urls as $url ) {
					$status = $client->get_url_status( $url );
					$results[ $url ] = is_wp_error( $status )
						? array( 'error' => $status->get_error_message() )
						: $status;
				}
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown action.', 'meowseo' ) ) );
		}

		wp_send_json_success( $results );
	}

	/**
	 * Render Content Refresh page.
	 */
	public function render_content_refresh_page(): void {
		$module = new \MeowSEO\Modules\Content_Refresh\Content_Refresh_Admin( $this->options );
		$module->render_dashboard_page();
	}
}
