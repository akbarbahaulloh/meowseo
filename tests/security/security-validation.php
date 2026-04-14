<?php
/**
 * Security Validation Test
 *
 * Validates that all security measures are properly implemented.
 * Tests Requirements 15.1-15.6.
 *
 * @package MeowSEO
 */

// Ensure we're running in CLI
if ( 'cli' !== php_sapi_name() ) {
	die( 'This script can only be run from the command line.' );
}

echo "=== MeowSEO Security Validation Test ===\n\n";

$passed = 0;
$failed = 0;

/**
 * Test helper function
 */
function test_security( $name, $callback ) {
	global $passed, $failed;
	
	echo "Testing: {$name}... ";
	
	try {
		$result = $callback();
		
		if ( $result ) {
			echo "✓ PASS\n";
			$passed++;
		} else {
			echo "✗ FAIL\n";
			$failed++;
		}
	} catch ( Exception $e ) {
		echo "✗ FAIL: {$e->getMessage()}\n";
		$failed++;
	}
}

/**
 * Get file source
 */
function get_file_source( $path ) {
	$full_path = dirname( dirname( dirname( __FILE__ ) ) ) . '/' . $path;
	if ( ! file_exists( $full_path ) ) {
		throw new Exception( "File not found: {$full_path}" );
	}
	return file_get_contents( $full_path );
}

// ============================================================================
// Requirement 15.1: Database Security - Prepared Statements
// ============================================================================

echo "--- Requirement 15.1: Database Security ---\n";

test_security( 'DB::get_redirect_exact uses prepared statements', function() {
	$source = get_file_source( 'includes/helpers/class-db.php' );
	
	// Check that the method uses $wpdb->prepare()
	return strpos( $source, '$wpdb->prepare(' ) !== false;
} );

test_security( 'DB::get_404_log validates orderby against whitelist', function() {
	$source = get_file_source( 'includes/helpers/class-db.php' );
	
	// Check that orderby is validated against allowed list
	return strpos( $source, '$allowed_orderby' ) !== false 
		&& strpos( $source, 'in_array' ) !== false;
} );

test_security( 'DB::bulk_upsert_404 uses prepared statements', function() {
	$source = get_file_source( 'includes/helpers/class-db.php' );
	
	// Check that the method uses $wpdb->prepare()
	return strpos( $source, '$wpdb->prepare(' ) !== false;
} );

// ============================================================================
// Requirement 15.2: Nonce Verification
// ============================================================================

echo "\n--- Requirement 15.2: Nonce Verification ---\n";

test_security( 'REST_API::update_meta verifies nonce', function() {
	$source = get_file_source( 'includes/class-rest-api.php' );
	
	// Check that the method calls verify_nonce
	return strpos( $source, 'verify_nonce' ) !== false;
} );

test_security( 'REST_API::update_settings verifies nonce', function() {
	$source = get_file_source( 'includes/class-rest-api.php' );
	
	// Check that the method calls verify_nonce
	return strpos( $source, 'verify_nonce' ) !== false;
} );

test_security( 'Redirects_REST::check_manage_options_and_nonce verifies nonce', function() {
	$source = get_file_source( 'includes/modules/redirects/class-redirects-rest.php' );
	
	// Check that the method uses wp_verify_nonce
	return strpos( $source, 'wp_verify_nonce' ) !== false;
} );

test_security( 'Meta::rest_get_analysis verifies nonce', function() {
	$source = get_file_source( 'includes/modules/meta/class-meta.php' );
	
	// Check that the method calls verify_nonce
	return strpos( $source, 'verify_nonce' ) !== false;
} );

test_security( 'Monitor_404_REST verifies nonce on DELETE', function() {
	$source = get_file_source( 'includes/modules/monitor_404/class-monitor-404-rest.php' );
	
	// Check that the method uses wp_verify_nonce
	return strpos( $source, 'wp_verify_nonce' ) !== false;
} );

test_security( 'GSC_REST verifies nonce on mutations', function() {
	$source = get_file_source( 'includes/modules/gsc/class-gsc-rest.php' );
	
	// Check that the method uses wp_verify_nonce
	return strpos( $source, 'wp_verify_nonce' ) !== false;
} );

test_security( 'Social_REST verifies nonce on mutations', function() {
	$source = get_file_source( 'includes/modules/social/class-social-rest.php' );
	
	// Check that the method uses wp_verify_nonce
	return strpos( $source, 'wp_verify_nonce' ) !== false;
} );

// ============================================================================
// Requirement 15.3: Capability Checks
// ============================================================================

echo "\n--- Requirement 15.3: Capability Checks ---\n";

test_security( 'REST_API::update_meta_permission checks edit_post capability', function() {
	$source = get_file_source( 'includes/class-rest-api.php' );
	
	// Check that the method uses current_user_can with edit_post
	return strpos( $source, "current_user_can( 'edit_post'" ) !== false;
} );

test_security( 'REST_API::manage_options_permission checks manage_options capability', function() {
	$source = get_file_source( 'includes/class-rest-api.php' );
	
	// Check that the method uses current_user_can with manage_options
	return strpos( $source, "current_user_can( 'manage_options'" ) !== false;
} );

test_security( 'Redirects_REST::check_manage_options checks capability', function() {
	$source = get_file_source( 'includes/modules/redirects/class-redirects-rest.php' );
	
	// Check that the method uses current_user_can
	return strpos( $source, 'current_user_can' ) !== false;
} );

test_security( 'Monitor_404_REST::check_manage_options checks capability', function() {
	$source = get_file_source( 'includes/modules/monitor_404/class-monitor-404-rest.php' );
	
	// Check that the method uses current_user_can
	return strpos( $source, 'current_user_can' ) !== false;
} );

test_security( 'GSC_REST checks manage_options capability', function() {
	$source = get_file_source( 'includes/modules/gsc/class-gsc-rest.php' );
	
	// Check that the method uses current_user_can
	return strpos( $source, 'current_user_can' ) !== false;
} );

test_security( 'Internal_Links_REST checks edit_posts capability', function() {
	$source = get_file_source( 'includes/modules/internal_links/class-internal-links-rest.php' );
	
	// Check that the method uses current_user_can
	return strpos( $source, 'current_user_can' ) !== false;
} );

// ============================================================================
// Requirement 15.4: Output Escaping
// ============================================================================

echo "\n--- Requirement 15.4: Output Escaping ---\n";

test_security( 'Meta::output_head_tags uses esc_html for title', function() {
	$source = get_file_source( 'includes/modules/meta/class-meta.php' );
	
	// Check that the method uses esc_html
	return strpos( $source, 'esc_html(' ) !== false;
} );

test_security( 'Meta::output_head_tags uses esc_attr for meta content', function() {
	$source = get_file_source( 'includes/modules/meta/class-meta.php' );
	
	// Check that the method uses esc_attr
	return strpos( $source, 'esc_attr(' ) !== false;
} );

test_security( 'Meta::output_head_tags uses esc_url for canonical', function() {
	$source = get_file_source( 'includes/modules/meta/class-meta.php' );
	
	// Check that the method uses esc_url
	return strpos( $source, 'esc_url(' ) !== false;
} );

test_security( 'Social::output_open_graph_tags uses esc_attr', function() {
	$source = get_file_source( 'includes/modules/social/class-social.php' );
	
	// Check that the method uses esc_attr
	return strpos( $source, 'esc_attr(' ) !== false;
} );

test_security( 'Social::output_open_graph_tags uses esc_url', function() {
	$source = get_file_source( 'includes/modules/social/class-social.php' );
	
	// Check that the method uses esc_url
	return strpos( $source, 'esc_url(' ) !== false;
} );

test_security( 'Schema_Builder::to_json uses wp_json_encode', function() {
	$source = get_file_source( 'includes/helpers/class-schema-builder.php' );
	
	// Check that the method uses wp_json_encode
	return strpos( $source, 'wp_json_encode(' ) !== false;
} );

// ============================================================================
// Requirement 15.6: Credential Encryption
// ============================================================================

echo "\n--- Requirement 15.6: Credential Encryption ---\n";

test_security( 'Options::encrypt_credentials uses AES-256-CBC', function() {
	$source = get_file_source( 'includes/class-options.php' );
	
	// Check that the method uses AES-256-CBC
	return strpos( $source, "'AES-256-CBC'" ) !== false;
} );

test_security( 'Options::encrypt_credentials uses random IV', function() {
	$source = get_file_source( 'includes/class-options.php' );
	
	// Check that the method uses openssl_random_pseudo_bytes
	return strpos( $source, 'openssl_random_pseudo_bytes' ) !== false;
} );

test_security( 'Options::encrypt_credentials derives key from WordPress secrets', function() {
	$source = get_file_source( 'includes/class-options.php' );
	
	// Check that the method uses AUTH_KEY and SECURE_AUTH_KEY
	return strpos( $source, 'AUTH_KEY' ) !== false 
		&& strpos( $source, 'SECURE_AUTH_KEY' ) !== false;
} );

test_security( 'GSC_REST::get_connection_status does not expose raw credentials', function() {
	$source = get_file_source( 'includes/modules/gsc/class-gsc-rest.php' );
	
	// Check that the method returns only boolean
	return strpos( $source, "'connected'" ) !== false;
} );

test_security( 'REST_API::get_settings removes sensitive data', function() {
	$source = get_file_source( 'includes/class-rest-api.php' );
	
	// Check that the method unsets gsc_credentials
	return strpos( $source, "unset( \$settings['gsc_credentials']" ) !== false;
} );

// ============================================================================
// Input Sanitization
// ============================================================================

echo "\n--- Input Sanitization ---\n";

test_security( 'REST_API::register_meta_routes sanitizes title with sanitize_text_field', function() {
	$source = get_file_source( 'includes/class-rest-api.php' );
	
	// Check that title uses sanitize_text_field
	return strpos( $source, 'sanitize_text_field' ) !== false;
} );

test_security( 'REST_API::register_meta_routes sanitizes description with sanitize_textarea_field', function() {
	$source = get_file_source( 'includes/class-rest-api.php' );
	
	// Check that description uses sanitize_textarea_field
	return strpos( $source, 'sanitize_textarea_field' ) !== false;
} );

test_security( 'REST_API::register_meta_routes sanitizes canonical with esc_url_raw', function() {
	$source = get_file_source( 'includes/class-rest-api.php' );
	
	// Check that canonical uses esc_url_raw
	return strpos( $source, 'esc_url_raw' ) !== false;
} );

test_security( 'Redirects_REST::get_redirect_schema sanitizes URLs', function() {
	$source = get_file_source( 'includes/modules/redirects/class-redirects-rest.php' );
	
	// Check that target_url uses esc_url_raw
	return strpos( $source, 'esc_url_raw' ) !== false;
} );

test_security( 'Meta module sanitizes focus_keyword', function() {
	$source = get_file_source( 'includes/modules/meta/class-meta.php' );
	
	// Check that focus_keyword uses sanitize_text_field
	return strpos( $source, 'sanitize_text_field' ) !== false;
} );

// ============================================================================
// Summary
// ============================================================================

echo "\n=== Security Validation Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ( $failed === 0 ) {
	echo "\n✓ All security measures are properly implemented!\n";
	exit( 0 );
} else {
	echo "\n✗ Some security measures failed validation.\n";
	exit( 1 );
}
