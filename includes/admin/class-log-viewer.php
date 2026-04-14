<?php
/**
 * Log_Viewer Admin Page
 *
 * Provides WordPress admin interface for viewing and managing debug logs.
 * Conditionally visible based on WP_DEBUG or debug mode option.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Admin
 */

namespace MeowSEO\Admin;

use MeowSEO\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Log_Viewer class
 *
 * Handles admin page registration, rendering, and asset enqueuing for the log viewer.
 * Requirements: 7.1, 7.2, 7.3, 15.4
 */
class Log_Viewer {

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
	 * Boot log viewer functionality
	 *
	 * Registers admin hooks.
	 * Requirements: 7.1
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register admin menu
	 *
	 * Adds submenu under MeowSEO admin menu.
	 * Conditionally registers based on WP_DEBUG or debug mode option.
	 * Requirements: 7.1, 7.2
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		// Only show menu if WP_DEBUG is enabled or debug mode option is set
		if ( ! $this->should_show_menu() ) {
			return;
		}

		add_submenu_page(
			'meowseo-settings',
			__( 'Debug Logs', 'meowseo' ),
			__( 'Debug Logs', 'meowseo' ),
			'manage_options',
			'meowseo-logs',
			array( $this, 'render_log_viewer_page' )
		);
	}

	/**
	 * Check if log viewer menu should be shown
	 *
	 * Requirements: 7.2
	 *
	 * @return bool True if menu should be shown, false otherwise.
	 */
	private function should_show_menu(): bool {
		// Show if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}

		// Show if debug mode option is explicitly enabled
		$debug_mode = $this->options->get( 'meowseo_debug_mode', false );
		if ( $debug_mode ) {
			return true;
		}

		return false;
	}

	/**
	 * Render log viewer page
	 *
	 * Outputs the React root element for the log viewer UI.
	 * Requirements: 7.3, 15.4
	 *
	 * @return void
	 */
	public function render_log_viewer_page(): void {
		// Verify user has manage_options capability (Requirement 15.4)
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="meowseo-log-viewer-root"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets
	 *
	 * Loads React-based log viewer UI on the log viewer page.
	 * Requirements: 7.3
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only load on MeowSEO log viewer page
		if ( 'meowseo_page_meowseo-logs' !== $hook_suffix ) {
			return;
		}

		// Enqueue the meowseo-editor asset handle (same as other admin pages)
		$asset_file = MEOWSEO_PLUGIN_DIR . 'build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'meowseo-log-viewer',
			MEOWSEO_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'meowseo-log-viewer',
			MEOWSEO_PLUGIN_URL . 'build/index.css',
			array( 'wp-components' ),
			$asset['version']
		);

		// Localize script with REST URL, nonce, and initial data
		wp_localize_script(
			'meowseo-log-viewer',
			'meowseoLogViewer',
			array(
				'restUrl' => rest_url( 'meowseo/v1' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
