<?php
/**
 * Test API Connection Script
 * 
 * This script tests the API connection for the test-provider endpoint.
 * It simulates what the JavaScript does and helps debug the 403 error.
 * 
 * Usage:
 * 1. Place this file in the plugin root directory
 * 2. Access it via: http://yoursite.com/wp-content/plugins/meowseo/test-api-connection.php
 * 3. Follow the instructions on the page
 */

// Load WordPress
require_once( dirname( dirname( dirname( __FILE__ ) ) ) . '/wp-load.php' );

// Check if user is logged in
if ( ! is_user_logged_in() ) {
	wp_die( 'You must be logged in to test this.' );
}

// Check if user has manage_options capability
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You must have manage_options capability to test this.' );
}

echo '<h1>MeowSEO API Connection Test</h1>';

// Get nonce
$nonce = wp_create_nonce( 'wp_rest' );

echo '<h2>Step 1: Verify Nonce</h2>';
echo '<p><strong>Nonce created:</strong> ' . esc_html( $nonce ) . '</p>';
echo '<p><strong>Nonce verification:</strong> ' . ( wp_verify_nonce( $nonce, 'wp_rest' ) ? 'VALID' : 'INVALID' ) . '</p>';

echo '<h2>Step 2: Test API Endpoint</h2>';
echo '<p>Copy and paste the following code into your browser console (F12 > Console tab):</p>';
echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto;">';
echo "const testConnection = async () => {\n";
echo "  const nonce = '" . esc_html( $nonce ) . "';\n";
echo "  const response = await fetch('/wp-json/meowseo/v1/ai/test-provider', {\n";
echo "    method: 'POST',\n";
echo "    headers: {\n";
echo "      'Content-Type': 'application/json',\n";
echo "      'X-WP-Nonce': nonce,\n";
echo "    },\n";
echo "    credentials: 'same-origin',\n";
echo "    body: JSON.stringify({\n";
echo "      provider: 'gemini',\n";
echo "      api_key: 'test-key-12345',\n";
echo "    })\n";
echo "  });\n";
echo "  const data = await response.json();\n";
echo "  console.log('Status:', response.status);\n";
echo "  console.log('Response:', data);\n";
echo "};\n";
echo "testConnection();\n";
echo '</pre>';

echo '<h2>Step 3: Check Endpoint Registration</h2>';
$rest_routes = rest_get_routes();
if ( isset( $rest_routes['/meowseo/v1/ai/test-provider'] ) ) {
	echo '<p style="color: green;"><strong>✓ Endpoint is registered</strong></p>';
	echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 3px;">';
	echo esc_html( print_r( $rest_routes['/meowseo/v1/ai/test-provider'], true ) );
	echo '</pre>';
} else {
	echo '<p style="color: red;"><strong>✗ Endpoint is NOT registered</strong></p>';
	echo '<p>Available endpoints starting with /meowseo/v1/ai/:</p>';
	echo '<ul>';
	foreach ( $rest_routes as $route => $data ) {
		if ( strpos( $route, '/meowseo/v1/ai/' ) === 0 ) {
			echo '<li>' . esc_html( $route ) . '</li>';
		}
	}
	echo '</ul>';
}

echo '<h2>Step 4: Check User Capabilities</h2>';
echo '<p><strong>Current user:</strong> ' . esc_html( wp_get_current_user()->user_login ) . '</p>';
echo '<p><strong>manage_options:</strong> ' . ( current_user_can( 'manage_options' ) ? 'YES' : 'NO' ) . '</p>';
echo '<p><strong>edit_posts:</strong> ' . ( current_user_can( 'edit_posts' ) ? 'YES' : 'NO' ) . '</p>';

echo '<h2>Step 5: Check AI Module Status</h2>';
if ( class_exists( 'MeowSEO\Modules\AI\AI_REST' ) ) {
	echo '<p style="color: green;"><strong>✓ AI_REST class is loaded</strong></p>';
} else {
	echo '<p style="color: red;"><strong>✗ AI_REST class is NOT loaded</strong></p>';
}

if ( class_exists( 'MeowSEO\Modules\AI\AI_Module' ) ) {
	echo '<p style="color: green;"><strong>✓ AI_Module class is loaded</strong></p>';
} else {
	echo '<p style="color: red;"><strong>✗ AI_Module class is NOT loaded</strong></p>';
}

echo '<h2>Step 6: Manual Permission Check</h2>';
// Simulate what the permission callback does
$can_manage = current_user_can( 'manage_options' );
$nonce_valid = wp_verify_nonce( $nonce, 'wp_rest' );

echo '<p><strong>Can manage options:</strong> ' . ( $can_manage ? 'YES' : 'NO' ) . '</p>';
echo '<p><strong>Nonce is valid:</strong> ' . ( $nonce_valid ? 'YES' : 'NO' ) . '</p>';

if ( $can_manage && $nonce_valid ) {
	echo '<p style="color: green;"><strong>✓ Permission check would PASS</strong></p>';
} else {
	echo '<p style="color: red;"><strong>✗ Permission check would FAIL</strong></p>';
	if ( ! $can_manage ) {
		echo '<p style="color: red;">  - User does not have manage_options capability</p>';
	}
	if ( ! $nonce_valid ) {
		echo '<p style="color: red;">  - Nonce is not valid</p>';
	}
}

echo '<h2>Step 7: Debug Information</h2>';
echo '<p><strong>WordPress version:</strong> ' . esc_html( get_bloginfo( 'version' ) ) . '</p>';
echo '<p><strong>PHP version:</strong> ' . esc_html( phpversion() ) . '</p>';
echo '<p><strong>WP_DEBUG:</strong> ' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'ENABLED' : 'DISABLED' ) . '</p>';

if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
	$debug_log = WP_CONTENT_DIR . '/debug.log';
	if ( file_exists( $debug_log ) ) {
		echo '<p><strong>Debug log:</strong> ' . esc_html( $debug_log ) . ' (' . size_format( filesize( $debug_log ) ) . ')</p>';
		echo '<p><a href="#" onclick="alert(\'Check the debug.log file in wp-content directory for detailed error messages.\'); return false;">View debug.log</a></p>';
	}
}

echo '<hr>';
echo '<p><em>This test page is for development purposes only. Delete this file when done.</em></p>';
?>
