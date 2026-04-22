<?php
/**
 * Plugin Name: MeowSEO
 * Plugin URI: https://github.com/akbarbahaulloh/meowseo
 * Description: A modular WordPress SEO plugin optimized for Google Discover, AI Overviews, and headless WordPress deployments.
 * Version: 1.0.0-b1b0d0d
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Akbar Bahaulloh
 * Author URI: https://puskomedia.id
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: meowseo
 * Domain Path: /languages
 *
 * @package MeowSEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'MEOWSEO_VERSION', '1.0.0-b1b0d0d' );
define( 'MEOWSEO_FILE', __FILE__ );
define( 'MEOWSEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEOWSEO_URL', plugin_dir_url( __FILE__ ) );
define( 'MEOWSEO_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'build/' );

// PSR-4 autoloader for MeowSEO namespace.
spl_autoload_register( function ( $class ) {
	// Only handle MeowSEO namespace.
	if ( 0 !== strpos( $class, 'MeowSEO\\' ) ) {
		return;
	}

	// Remove namespace prefix.
	$class = str_replace( 'MeowSEO\\', '', $class );

	// Convert namespace separators to directory separators.
	$class = str_replace( '\\', DIRECTORY_SEPARATOR, $class );

	// Convert class name to file name (WordPress convention: class-{name}.php or interface-{name}.php).
	$parts = explode( DIRECTORY_SEPARATOR, $class );
	$last_part = array_pop( $parts );
	
	// Convert CamelCase to kebab-case and underscores to hyphens for file name.
	$file_name = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $last_part ) );
	$file_name = str_replace( '_', '-', $file_name );
	
	// Convert directory parts to lowercase (WordPress convention).
	$parts = array_map( 'strtolower', $parts );
	
	// Try class file first, then interface file.
	$class_file = MEOWSEO_PATH . 'includes' . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $parts ) . ( empty( $parts ) ? '' : DIRECTORY_SEPARATOR ) . 'class-' . $file_name . '.php';
	$interface_file = MEOWSEO_PATH . 'includes' . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $parts ) . ( empty( $parts ) ? '' : DIRECTORY_SEPARATOR ) . 'interface-' . $file_name . '.php';

	// Load file if it exists.
	if ( file_exists( $class_file ) ) {
		require_once $class_file;
	} elseif ( file_exists( $interface_file ) ) {
		require_once $interface_file;
	}
} );

// Register activation, deactivation, and uninstall hooks.
register_activation_hook( __FILE__, array( 'MeowSEO\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MeowSEO\Installer', 'deactivate' ) );

// Load plugin textdomain at init hook.
// Requirement: WordPress 6.7+ compatibility (avoid early translation loading notice).
add_action( 'init', function() {
	load_plugin_textdomain( 'meowseo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}, 1 );

// Check for migrations (Requirement: Data integrity).
add_action( 'init', array( 'MeowSEO\Installer', 'maybe_migrate' ), 5 );

// Initialize the plugin on init hook at priority 10.
add_action( 'init', function() {
	try {
		\MeowSEO\Plugin::instance()->boot();
	} catch ( \Exception $e ) {
		// Log critical initialization error.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'MeowSEO: Critical initialization error: ' . $e->getMessage() );
		}
		// Display admin notice for critical errors.
		add_action( 'admin_notices', function() use ( $e ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><strong>MeowSEO Error:</strong> <?php echo esc_html( $e->getMessage() ); ?></p>
			</div>
			<?php
		});
	}
}, 10 );

// Register WP-CLI commands.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	add_action( 'init', function() {
		try {
			$plugin = \MeowSEO\Plugin::instance();
			$options = $plugin->get_options();
			
			$cli_commands = new \MeowSEO\CLI\CLI_Commands( $options );
			$cli_commands->register();
		} catch ( \Exception $e ) {
			// Log CLI registration error.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'MeowSEO: Failed to register WP-CLI commands: ' . $e->getMessage() );
			}
		}
	}, 20 ); // Priority 20 to ensure plugin is booted first
}
