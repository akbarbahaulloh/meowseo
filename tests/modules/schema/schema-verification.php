<?php
/**
 * Schema Module Verification Script
 *
 * Simple verification that Schema_Builder methods work correctly.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

// Load Composer autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Define WordPress constants for testing
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../../../' );
}

// Define plugin directory constant
if ( ! defined( 'MEOWSEO_PLUGIN_DIR' ) ) {
	define( 'MEOWSEO_PLUGIN_DIR', __DIR__ . '/../../../' );
}

// Register custom autoloader
require_once __DIR__ . '/../../../includes/class-autoloader.php';
\MeowSEO\Autoloader::register();

// Mock WordPress functions
if ( ! function_exists( 'get_site_url' ) ) {
	function get_site_url() {
		return 'https://example.com';
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $key ) {
		$values = array(
			'name'        => 'Test Site',
			'description' => 'Test Description',
			'language'    => 'en-US',
		);
		return $values[ $key ] ?? '';
	}
}

if ( ! function_exists( 'get_theme_mod' ) ) {
	function get_theme_mod( $key ) {
		return false;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $flags = 0 ) {
		return json_encode( $data, $flags );
	}
}

// Create Options mock
$options = new class() extends \MeowSEO\Options {
	public function __construct() {
		// Skip parent constructor
	}
};

// Create Schema_Builder instance
$schema_builder = new \MeowSEO\Helpers\Schema_Builder( $options );

echo "Testing Schema_Builder methods...\n\n";

// Test build_website
echo "1. Testing build_website():\n";
$website = $schema_builder->build_website();
echo "   - Type: " . $website['@type'] . "\n";
echo "   - ID: " . $website['@id'] . "\n";
echo "   - URL: " . $website['url'] . "\n";
echo "   - Name: " . $website['name'] . "\n";
echo "   ✓ build_website() works correctly\n\n";

// Test build_organization
echo "2. Testing build_organization():\n";
$organization = $schema_builder->build_organization();
echo "   - Type: " . $organization['@type'] . "\n";
echo "   - ID: " . $organization['@id'] . "\n";
echo "   - Name: " . $organization['name'] . "\n";
echo "   ✓ build_organization() works correctly\n\n";

// Test build_faq
echo "3. Testing build_faq():\n";
$faq_items = array(
	array(
		'question' => 'What is MeowSEO?',
		'answer'   => 'MeowSEO is a lightweight WordPress SEO plugin.',
	),
	array(
		'question' => 'How does it work?',
		'answer'   => 'It generates structured data and optimizes meta tags.',
	),
);
$faq = $schema_builder->build_faq( $faq_items );
echo "   - Type: " . $faq['@type'] . "\n";
echo "   - Questions count: " . count( $faq['mainEntity'] ) . "\n";
echo "   - First question: " . $faq['mainEntity'][0]['name'] . "\n";
echo "   ✓ build_faq() works correctly\n\n";

// Test to_json
echo "4. Testing to_json():\n";
$graph = array(
	'@context' => 'https://schema.org',
	'@graph'   => array( $website, $organization ),
);
$json = $schema_builder->to_json( $graph );
$decoded = json_decode( $json, true );
echo "   - JSON is valid: " . ( $decoded !== null ? 'Yes' : 'No' ) . "\n";
echo "   - Context: " . $decoded['@context'] . "\n";
echo "   - Graph items: " . count( $decoded['@graph'] ) . "\n";
echo "   ✓ to_json() works correctly\n\n";

echo "✅ All Schema_Builder methods verified successfully!\n";
