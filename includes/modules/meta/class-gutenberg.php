<?php
/**
 * Gutenberg Integration
 *
 * Enqueues JavaScript assets for Gutenberg editor integration.
 * Registers the meowseo-editor script and dependencies.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg integration class
 *
 * Handles asset enqueuing for the Gutenberg editor.
 *
 * @since 1.0.0
 */
class Gutenberg {

	/**
	 * Plugin root directory
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $plugin_dir;

	/**
	 * Plugin root URL
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $plugin_url;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param string $plugin_dir Plugin root directory path.
	 * @param string $plugin_url Plugin root URL.
	 */
	public function __construct( string $plugin_dir, string $plugin_url ) {
		$this->plugin_dir = $plugin_dir;
		$this->plugin_url = $plugin_url;
	}

	/**
	 * Initialize Gutenberg integration
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Enqueue editor assets
	 *
	 * Enqueues JavaScript and CSS for the Gutenberg editor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		// Check if we're in the block editor
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! $screen->is_block_editor() ) {
			return;
		}

		// Build script path
		$script_path = $this->plugin_dir . '/build/index.js';
		$script_url  = $this->plugin_url . '/build/index.js';

		// Check if build file exists, fallback to src if not
		if ( ! file_exists( $script_path ) ) {
			// Development mode - use src files (requires build step)
			// For now, we'll just return and log a notice
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'MeowSEO: Build file not found. Run npm run build to compile JavaScript assets.' );
			}
			return;
		}

		// Get asset dependencies
		$asset_file = $this->plugin_dir . '/build/index.asset.php';
		$asset_data = file_exists( $asset_file ) ? require $asset_file : array(
			'dependencies' => array(),
			'version'      => '1.0.0',
		);

		// Enqueue script
		wp_enqueue_script(
			'meowseo-editor',
			$script_url,
			$asset_data['dependencies'],
			$asset_data['version'],
			true
		);

		// Enqueue editor styles if they exist
		$style_path = $this->plugin_dir . '/build/index.css';
		$style_url  = $this->plugin_url . '/build/index.css';

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'meowseo-editor',
				$style_url,
				array( 'wp-edit-blocks' ),
				$asset_data['version']
			);
		}

		// Localize script with REST API data
		wp_localize_script(
			'meowseo-editor',
			'meowseoData',
			array(
				'restUrl'   => rest_url( 'meowseo/v1' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'postTypes' => $this->get_supported_post_types(),
			)
		);
	}

	/**
	 * Get supported post types
	 *
	 * Returns array of post types that support MeowSEO.
	 *
	 * @since 1.0.0
	 * @return array Array of post type names.
	 */
	private function get_supported_post_types(): array {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		return array_values( $post_types );
	}
}
