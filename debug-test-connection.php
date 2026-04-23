<?php
/**
 * Debug script for testing API connection
 * 
 * This script helps debug the HTTP 403 error when testing provider connections.
 * 
 * Usage:
 * 1. Place this file in the plugin root directory
 * 2. Access it via: http://yoursite.com/wp-content/plugins/meowseo/debug-test-connection.php
 * 3. Check the output for debugging information
 */

// Load WordPress
require_once( dirname( dirname( dirname( __FILE__ ) ) ) . '/wp-load.php' );

// Check if user is logged in
if ( ! is_user_logged_in() ) {
	wp_die( 'You must be logged in to debug this.' );
}

// Check if user has manage_options capability
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You must have manage_options capability to debug this.' );
}

echo '<h1>MeowSEO API Test Connection Debug</h1>';

// 1. Check nonce creation
echo '<h2>1. Nonce Creation</h2>';
$nonce = wp_create_nonce( 'wp_rest' );
echo '<p><strong>Created nonce:</strong> ' . esc_html( $nonce ) . '</p>';

// 2. Verify nonce
echo '<h2>2. Nonce Verification</h2>';
$verified = wp_verify_nonce( $nonce, 'wp_rest' );
echo '<p><strong>Nonce verification result:</strong> ' . ( $verified ? 'VALID (1)' : 'INVALID (0 or false)' ) . '</p>';

// 3. Check user capabilities
echo '<h2>3. User Capabilities</h2>';
echo '<p><strong>Current user:</strong> ' . esc_html( wp_get_current_user()->user_login ) . '</p>';
echo '<p><strong>manage_options:</strong> ' . ( current_user_can( 'manage_options' ) ? 'YES' : 'NO' ) . '</p>';
echo '<p><strong>edit_posts:</strong> ' . ( current_user_can( 'edit_posts' ) ? 'YES' : 'NO' ) . '</p>';

// 4. Check REST API endpoints
echo '<h2>4. REST API Endpoints</h2>';
echo '<p><strong>REST URL:</strong> ' . esc_html( rest_url() ) . '</p>';
echo '<p><strong>Test endpoint:</strong> ' . esc_html( rest_url( 'meowseo/v1/ai/test-provider' ) ) . '</p>';

// 5. Check if AI module is loaded
echo '<h2>5. AI Module Status</h2>';
if ( class_exists( 'MeowSEO\Modules\AI\AI_REST' ) ) {
	echo '<p><strong>AI_REST class:</strong> LOADED</p>';
} else {
	echo '<p><strong>AI_REST class:</strong> NOT LOADED</p>';
}

// 6. Test nonce in different scenarios
echo '<h2>6. Nonce Scenarios</h2>';

// Scenario 1: Fresh nonce
$fresh_nonce = wp_create_nonce( 'wp_rest' );
$fresh_verify = wp_verify_nonce( $fresh_nonce, 'wp_rest' );
echo '<p><strong>Fresh nonce verification:</strong> ' . ( $fresh_verify ? 'VALID' : 'INVALID' ) . '</p>';

// Scenario 2: Check nonce action
echo '<p><strong>Nonce action used:</strong> wp_rest</p>';

// 7. JavaScript test code
echo '<h2>7. JavaScript Test Code</h2>';
echo '<pre>';
echo 'Copy and paste this into browser console to test the API:' . "\n\n";
echo "fetch('/wp-json/meowseo/v1/ai/test-provider', {\n";
echo "  method: 'POST',\n";
echo "  headers: {\n";
echo "    'Content-Type': 'application/json',\n";
echo "    'X-WP-Nonce': '" . esc_html( $nonce ) . "',\n";
echo "  },\n";
echo "  credentials: 'same-origin',\n";
echo "  body: JSON.stringify({\n";
echo "    provider: 'gemini',\n";
echo "    api_key: 'test-key',\n";
echo "  })\n";
echo "})\n";
echo ".then(r => r.json())\n";
echo ".then(d => console.log(d))\n";
echo ".catch(e => console.error(e));\n";
echo '</pre>';

// 8. Check WordPress REST API nonce handling
echo '<h2>8. WordPress REST API Nonce Handling</h2>';
echo '<p><strong>REST nonce header name:</strong> X-WP-Nonce</p>';
echo '<p><strong>REST nonce action:</strong> wp_rest</p>';
echo '<p><strong>REST nonce verification:</strong> ' . ( wp_verify_nonce( $nonce, 'wp_rest' ) ? 'WORKING' : 'NOT WORKING' ) . '</p>';

// 9. Check if WP_DEBUG is enabled
echo '<h2>9. Debug Settings</h2>';
echo '<p><strong>WP_DEBUG:</strong> ' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'ENABLED' : 'DISABLED' ) . '</p>';
echo '<p><strong>WP_DEBUG_LOG:</strong> ' . ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? 'ENABLED' : 'DISABLED' ) . '</p>';

if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
	$debug_log = WP_CONTENT_DIR . '/debug.log';
	echo '<p><strong>Debug log location:</strong> ' . esc_html( $debug_log ) . '</p>';
	if ( file_exists( $debug_log ) ) {
		echo '<p><strong>Debug log exists:</strong> YES</p>';
		echo '<p><strong>Debug log size:</strong> ' . size_format( filesize( $debug_log ) ) . '</p>';
	} else {
		echo '<p><strong>Debug log exists:</strong> NO</p>';
	}
}

echo '<hr>';
echo '<p><em>This debug page is for development purposes only. Delete this file when done.</em></p>';
?>
