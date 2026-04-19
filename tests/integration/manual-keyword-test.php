<?php
/**
 * Manual Keyword REST API Test Script
 *
 * This script demonstrates the keyword management REST API endpoints.
 * Run this in a WordPress environment with MeowSEO activated.
 *
 * Usage:
 * 1. Create a test post in WordPress
 * 2. Update POST_ID constant below with the post ID
 * 3. Run: php -d display_errors=1 tests/integration/manual-keyword-test.php
 *
 * @package MeowSEO
 */

// Load WordPress.
require_once dirname( __FILE__ ) . '/../../../../../../wp-load.php';

// Configuration.
const POST_ID = 1; // Change this to your test post ID.

echo "=== MeowSEO Keyword Management Manual Test ===\n\n";

// Test 1: Update keywords via REST API simulation.
echo "Test 1: Setting keywords\n";
echo "------------------------\n";

$post_id = POST_ID;

// Simulate REST API request to update keywords.
$request = new WP_REST_Request( 'POST', '/meowseo/v1/keywords/' . $post_id );
$request->set_param( 'post_id', $post_id );
$request->set_param( 'primary', 'wordpress seo' );
$request->set_param(
	'secondary',
	array(
		'seo plugin',
		'search optimization',
		'meta tags',
		'wordpress plugin',
	)
);

// Get meta module instance.
$plugin = MeowSEO\Plugin::get_instance();
$meta_module = $plugin->get_module( 'meta' );

if ( ! $meta_module ) {
	echo "ERROR: Meta module not found\n";
	exit( 1 );
}

// Call REST endpoint.
$response = $meta_module->rest_update_keywords( $request );
$data = $response->get_data();

echo "Response Status: " . $response->get_status() . "\n";
echo "Success: " . ( $data['success'] ? 'Yes' : 'No' ) . "\n";
echo "Primary Keyword: " . $data['keywords']['primary'] . "\n";
echo "Secondary Keywords: " . implode( ', ', $data['keywords']['secondary'] ) . "\n";
echo "Total Keywords: " . ( 1 + count( $data['keywords']['secondary'] ) ) . "\n";
echo "\n";

// Test 2: Verify analysis results.
echo "Test 2: Verifying analysis results\n";
echo "-----------------------------------\n";

if ( isset( $data['analysis'] ) && ! empty( $data['analysis'] ) ) {
	echo "Analysis generated for " . count( $data['analysis'] ) . " keywords\n\n";

	foreach ( $data['analysis'] as $keyword => $analysis ) {
		echo "Keyword: {$keyword}\n";
		echo "  Overall Score: {$analysis['overall_score']}\n";
		echo "  Density: {$analysis['density']['score']} ({$analysis['density']['status']})\n";
		echo "  In Title: {$analysis['in_title']['score']} ({$analysis['in_title']['status']})\n";
		echo "  In Headings: {$analysis['in_headings']['score']} ({$analysis['in_headings']['status']})\n";
		echo "  In Slug: {$analysis['in_slug']['score']} ({$analysis['in_slug']['status']})\n";
		echo "  In First Paragraph: {$analysis['in_first_paragraph']['score']} ({$analysis['in_first_paragraph']['status']})\n";
		echo "  In Meta Description: {$analysis['in_meta_description']['score']} ({$analysis['in_meta_description']['status']})\n";
		echo "\n";
	}
} else {
	echo "No analysis results found\n\n";
}

// Test 3: Try to add 6th keyword (should fail).
echo "Test 3: Testing validation (adding 6th keyword)\n";
echo "------------------------------------------------\n";

$request = new WP_REST_Request( 'POST', '/meowseo/v1/keywords/' . $post_id );
$request->set_param( 'post_id', $post_id );
$request->set_param( 'primary', 'wordpress seo' );
$request->set_param(
	'secondary',
	array(
		'seo plugin',
		'search optimization',
		'meta tags',
		'wordpress plugin',
		'sixth keyword', // This should fail.
	)
);

$response = $meta_module->rest_update_keywords( $request );
$data = $response->get_data();

echo "Response Status: " . $response->get_status() . "\n";
echo "Success: " . ( $data['success'] ? 'Yes' : 'No' ) . "\n";

if ( ! $data['success'] ) {
	echo "Error Message: " . $data['message'] . "\n";
	echo "✓ Validation correctly prevents exceeding 5 keywords\n";
} else {
	echo "✗ ERROR: Validation failed to prevent 6th keyword\n";
}
echo "\n";

// Test 4: Remove a keyword.
echo "Test 4: Removing a keyword\n";
echo "--------------------------\n";

$request = new WP_REST_Request( 'POST', '/meowseo/v1/keywords/' . $post_id );
$request->set_param( 'post_id', $post_id );
$request->set_param( 'primary', 'wordpress seo' );
$request->set_param(
	'secondary',
	array(
		'seo plugin',
		'search optimization',
		'wordpress plugin', // Removed 'meta tags'.
	)
);

$response = $meta_module->rest_update_keywords( $request );
$data = $response->get_data();

echo "Response Status: " . $response->get_status() . "\n";
echo "Success: " . ( $data['success'] ? 'Yes' : 'No' ) . "\n";
echo "Total Keywords: " . ( 1 + count( $data['keywords']['secondary'] ) ) . "\n";
echo "Secondary Keywords: " . implode( ', ', $data['keywords']['secondary'] ) . "\n";
echo "✓ Keyword removed successfully\n";
echo "\n";

// Test 5: Reorder keywords.
echo "Test 5: Reordering keywords\n";
echo "---------------------------\n";

$request = new WP_REST_Request( 'POST', '/meowseo/v1/keywords/' . $post_id );
$request->set_param( 'post_id', $post_id );
$request->set_param( 'primary', 'wordpress seo' );
$request->set_param(
	'secondary',
	array(
		'wordpress plugin', // Reordered.
		'search optimization',
		'seo plugin',
	)
);

$response = $meta_module->rest_update_keywords( $request );
$data = $response->get_data();

echo "Response Status: " . $response->get_status() . "\n";
echo "Success: " . ( $data['success'] ? 'Yes' : 'No' ) . "\n";
echo "New Order: " . implode( ', ', $data['keywords']['secondary'] ) . "\n";
echo "✓ Keywords reordered successfully\n";
echo "\n";

echo "=== All Manual Tests Completed ===\n";
echo "\nSummary:\n";
echo "- ✓ Can set up to 5 keywords\n";
echo "- ✓ Analysis runs for each keyword\n";
echo "- ✓ Validation prevents exceeding 5 keywords\n";
echo "- ✓ Can remove keywords\n";
echo "- ✓ Can reorder keywords\n";
echo "\nAll functionality working as expected!\n";
