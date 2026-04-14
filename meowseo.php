<?php
/**
 * Plugin Name: MeowSEO
 * Plugin URI: https://github.com/akbarbahaulloh/meowseo
 * Description: A modular WordPress SEO plugin optimized for Google Discover, AI Overviews, and headless WordPress deployments.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Your Name
 * Author URI: https://puskomedia.id
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: meowseo
 * Domain Path: /languages
 *
 * @package MeowSEO
 */

namespace MeowSEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'MEOWSEO_VERSION', '1.0.0' );
define( 'MEOWSEO_PLUGIN_FILE', __FILE__ );
define( 'MEOWSEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEOWSEO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MEOWSEO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Minimum requirements.
define( 'MEOWSEO_MIN_PHP_VERSION', '8.0' );
define( 'MEOWSEO_MIN_WP_VERSION', '6.0' );

/**
 * Check minimum requirements before loading the plugin.
 *
 * @return bool True if requirements are met, false otherwise.
 */
function meowseo_check_requirements(): bool {
	global $wp_version;

	$php_version = phpversion();
	$wp_version_clean = preg_replace( '/[^0-9.]/', '', $wp_version );

	// Check PHP version.
	if ( version_compare( $php_version, MEOWSEO_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\meowseo_php_version_notice' );
		return false;
	}

	// Check WordPress version.
	if ( version_compare( $wp_version_clean, MEOWSEO_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\meowseo_wp_version_notice' );
		return false;
	}

	return true;
}

/**
 * Display admin notice for insufficient PHP version.
 */
function meowseo_php_version_notice(): void {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				esc_html__( 'MeowSEO requires PHP version %1$s or higher. You are running version %2$s. Please upgrade PHP to activate this plugin.', 'meowseo' ),
				esc_html( MEOWSEO_MIN_PHP_VERSION ),
				esc_html( phpversion() )
			);
			?>
		</p>
	</div>
	<?php
	// Deactivate the plugin.
	deactivate_plugins( MEOWSEO_PLUGIN_BASENAME );
}

/**
 * Display admin notice for insufficient WordPress version.
 */
function meowseo_wp_version_notice(): void {
	global $wp_version;
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required WordPress version, 2: Current WordPress version */
				esc_html__( 'MeowSEO requires WordPress version %1$s or higher. You are running version %2$s. Please upgrade WordPress to activate this plugin.', 'meowseo' ),
				esc_html( MEOWSEO_MIN_WP_VERSION ),
				esc_html( $wp_version )
			);
			?>
		</p>
	</div>
	<?php
	// Deactivate the plugin.
	deactivate_plugins( MEOWSEO_PLUGIN_BASENAME );
}

// Check requirements before proceeding.
if ( ! meowseo_check_requirements() ) {
	return;
}

// Register autoloader.
require_once MEOWSEO_PLUGIN_DIR . 'includes/class-autoloader.php';
Autoloader::register();

// Register activation, deactivation, and uninstall hooks.
register_activation_hook( __FILE__, array( '\MeowSEO\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\MeowSEO\Installer', 'deactivate' ) );

/**
 * Initialize the plugin.
 */
function meowseo_init(): void {
	try {
		Plugin::instance()->boot();
	} catch ( \Exception $e ) {
		// Log error in debug mode.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'MeowSEO: Failed to initialize plugin: ' . $e->getMessage() );
		}

		// Display admin notice for fatal errors.
		add_action( 'admin_notices', function() use ( $e ) {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: %s: Error message */
						esc_html__( 'MeowSEO encountered an error during initialization: %s', 'meowseo' ),
						esc_html( $e->getMessage() )
					);
					?>
				</p>
			</div>
			<?php
		} );
	}
}

// Hook into plugins_loaded to initialize the plugin.
add_action( 'plugins_loaded', __NAMESPACE__ . '\meowseo_init' );
