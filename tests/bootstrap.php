<?php
/**
 * PHPUnit bootstrap file for MeowSEO tests.
 *
 * Sets up WordPress test environment and loads necessary dependencies.
 *
 * @package MeowSEO\Tests
 */

// Define test environment constants.
define( 'MEOWSEO_TESTS', true );
define( 'MEOWSEO_VERSION', '1.0.0-test' );
define( 'MEOWSEO_FILE', dirname( __DIR__ ) . '/meowseo.php' );
define( 'MEOWSEO_PATH', dirname( __DIR__ ) . '/' );
define( 'MEOWSEO_URL', 'http://example.org/wp-content/plugins/meowseo/' );
define( 'MEOWSEO_ASSETS_URL', 'http://example.org/wp-content/plugins/meowseo/build/' );

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load Brain Monkey for WordPress function mocking.
require_once dirname( __DIR__ ) . '/vendor/antecedent/patchwork/Patchwork.php';

// Mock wp_generate_password function BEFORE defining constants.
if ( ! function_exists( 'wp_generate_password' ) ) {
	/**
	 * Mock wp_generate_password.
	 *
	 * @param int  $length  Password length.
	 * @param bool $special Include special characters.
	 * @param bool $extra   Include extra special characters.
	 * @return string Generated password.
	 */
	function wp_generate_password( $length = 12, $special = true, $extra = false ) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if ( $special ) {
			$chars .= '!@#$%^&*()';
		}
		if ( $extra ) {
			$chars .= '-_ []{}<>~`+=,.;:/?|';
		}
		return substr( str_shuffle( str_repeat( $chars, ceil( $length / strlen( $chars ) ) ) ), 0, $length );
	}
}

// Initialize Brain Monkey.
\Brain\Monkey\setUp();

// Mock WordPress constants if not defined.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', false );
}

// Mock WordPress time constants
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 604800 );
}

if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
	define( 'MONTH_IN_SECONDS', 2592000 );
}

if ( ! defined( 'YEAR_IN_SECONDS' ) ) {
	define( 'YEAR_IN_SECONDS', 31536000 );
}

// Mock WordPress encryption keys for Options tests.
if ( ! defined( 'AUTH_KEY' ) ) {
	define( 'AUTH_KEY', 'test-auth-key-' . wp_generate_password( 64, true, true ) );
}

if ( ! defined( 'SECURE_AUTH_KEY' ) ) {
	define( 'SECURE_AUTH_KEY', 'test-secure-auth-key-' . wp_generate_password( 64, true, true ) );
}

// Register MeowSEO autoloader (same as in meowseo.php).
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

// Global storage for mocked WordPress database operations.
global $wpdb_storage;
$wpdb_storage = array();

// Mock global $wpdb object.
global $wpdb;
$wpdb = new stdClass();
$wpdb->prefix = 'wp_';
$wpdb->insert = function( $table, $data, $format = null ) {
	global $wpdb_storage;
	if ( ! isset( $wpdb_storage[ $table ] ) ) {
		$wpdb_storage[ $table ] = array();
	}
	$data['id'] = count( $wpdb_storage[ $table ] ) + 1;
	$wpdb_storage[ $table ][] = $data;
	return 1;
};
$wpdb->get_results = function( $query ) {
	global $wpdb_storage;
	// Simple mock - return empty array.
	return array();
};
$wpdb->get_var = function( $query ) {
	return null;
};
$wpdb->prepare = function( $query, ...$args ) {
	return vsprintf( str_replace( '%s', "'%s'", str_replace( '%d', '%d', $query ) ), $args );
};

// Register shutdown function to tear down Brain Monkey.
register_shutdown_function( function() {
	\Brain\Monkey\tearDown();
} );

echo "MeowSEO Test Bootstrap loaded successfully.\n";
