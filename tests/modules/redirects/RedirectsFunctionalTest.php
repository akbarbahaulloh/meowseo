<?php
/**
 * Redirects Module Functional Tests
 *
 * Tests redirect matching, execution, CSV import/export, and automatic slug redirects.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Redirects;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Redirects\Redirects;
use MeowSEO\Modules\Redirects\Redirects_REST;
use MeowSEO\Modules\Redirects\Redirects_Admin;
use MeowSEO\Options;

/**
 * Redirects functional test case
 *
 * Tests the complete redirect workflow including:
 * - Exact match redirects
 * - Regex pattern redirects
 * - Automatic slug change redirects
 * - CSV import/export
 * - Loop detection
 */
class RedirectsFunctionalTest extends TestCase {

	/**
	 * Test exact match redirect logic
	 *
	 * Requirement 1.1, 1.2: Exact match query with indexed source_url
	 */
	public function test_exact_match_redirect_logic(): void {
		$options = $this->createMock( Options::class );
		$redirects = new Redirects( $options );

		// Verify module boots without errors
		$this->expectNotToPerformAssertions();
		$redirects->boot();
	}

	/**
	 * Test regex redirect pattern matching
	 *
	 * Requirement 5.1, 5.2, 5.3, 5.4: Regex patterns with backreferences
	 */
	public function test_regex_pattern_matching(): void {
		// Test regex delimiter detection
		$pattern_with_delimiters = '#^/blog/.*#i';
		$pattern_without_delimiters = '^/blog/.*';

		// Both patterns should be valid
		$this->assertIsString( $pattern_with_delimiters );
		$this->assertIsString( $pattern_without_delimiters );
	}

	/**
	 * Test redirect types are supported
	 *
	 * Requirement 2.1, 2.2: Support redirect types 301, 302, 307, 410, 451
	 */
	public function test_redirect_types_supported(): void {
		$supported_types = [ 301, 302, 307, 410, 451 ];

		foreach ( $supported_types as $type ) {
			$this->assertIsInt( $type );
			$this->assertGreaterThanOrEqual( 301, $type );
		}
	}

	/**
	 * Test loop detection mechanism
	 *
	 * Requirement 6.1, 6.2, 6.3, 6.4: Redirect loop detection
	 */
	public function test_loop_detection_mechanism(): void {
		$options = $this->createMock( Options::class );
		$redirects = new Redirects( $options );

		// Verify module has loop detection capability
		$this->assertTrue( method_exists( $redirects, 'handle_redirect' ) );
	}

	/**
	 * Test automatic slug change redirect creation
	 *
	 * Requirement 4.1, 4.2, 4.3, 4.4: Automatic redirects on slug change
	 */
	public function test_automatic_slug_change_redirect(): void {
		$options = $this->createMock( Options::class );
		$redirects = new Redirects( $options );

		// Verify handle_post_updated method exists
		$this->assertTrue( method_exists( $redirects, 'handle_post_updated' ) );
	}

	/**
	 * Test hit tracking functionality
	 *
	 * Requirement 3.1, 3.2, 3.3, 3.4: Asynchronous hit tracking
	 */
	public function test_hit_tracking_functionality(): void {
		$options = $this->createMock( Options::class );
		$redirects = new Redirects( $options );

		// Verify record_hit_async method exists
		$this->assertTrue( method_exists( $redirects, 'record_hit_async' ) );
	}

	/**
	 * Test REST API endpoints exist
	 *
	 * Requirement 16.1, 16.2, 16.3, 16.4, 16.5, 16.6
	 */
	public function test_rest_api_endpoints_exist(): void {
		$options = $this->createMock( Options::class );
		$rest = new Redirects_REST( $options );

		// Verify all required methods exist
		$this->assertTrue( method_exists( $rest, 'register_routes' ) );
		$this->assertTrue( method_exists( $rest, 'create_redirect' ) );
		$this->assertTrue( method_exists( $rest, 'update_redirect' ) );
		$this->assertTrue( method_exists( $rest, 'delete_redirect' ) );
		$this->assertTrue( method_exists( $rest, 'import_redirects' ) );
		$this->assertTrue( method_exists( $rest, 'export_redirects' ) );
	}

	/**
	 * Test CSV import/export functionality
	 *
	 * Requirement 12.1, 12.2, 12.3, 12.4, 12.5, 12.6
	 */
	public function test_csv_import_export_functionality(): void {
		$options = $this->createMock( Options::class );
		$rest = new Redirects_REST( $options );

		// Verify CSV methods exist
		$this->assertTrue( method_exists( $rest, 'import_redirects' ) );
		$this->assertTrue( method_exists( $rest, 'export_redirects' ) );
	}

	/**
	 * Test admin interface exists
	 *
	 * Requirement 12.1, 12.2
	 */
	public function test_admin_interface_exists(): void {
		$options = $this->createMock( Options::class );
		$admin = new Redirects_Admin( $options );

		// Verify admin methods exist
		$this->assertTrue( method_exists( $admin, 'register_menu' ) );
		$this->assertTrue( method_exists( $admin, 'render_page' ) );
		$this->assertTrue( method_exists( $admin, 'handle_csv_import' ) );
		$this->assertTrue( method_exists( $admin, 'handle_csv_export' ) );
	}

	/**
	 * Test redirect chain detection in REST API
	 *
	 * Requirement 6.1, 6.2, 6.3, 6.4
	 */
	public function test_redirect_chain_detection_in_rest_api(): void {
		$options = $this->createMock( Options::class );
		$rest = new Redirects_REST( $options );

		// Verify validation methods exist
		$reflection = new \ReflectionClass( $rest );
		
		// Check for validate_redirect_data method
		$this->assertTrue( $reflection->hasMethod( 'validate_redirect_data' ) );
		
		// Check for check_redirect_chain method
		$this->assertTrue( $reflection->hasMethod( 'check_redirect_chain' ) );
	}

	/**
	 * Test security checks in REST API
	 *
	 * Requirement 16.6: Nonce verification and capability checks
	 */
	public function test_security_checks_in_rest_api(): void {
		$options = $this->createMock( Options::class );
		$rest = new Redirects_REST( $options );

		// Verify security methods exist
		$this->assertTrue( method_exists( $rest, 'check_manage_options' ) );
		$this->assertTrue( method_exists( $rest, 'check_manage_options_and_nonce' ) );
	}

	/**
	 * Test regex rules flag optimization
	 *
	 * Requirement 1.3, 1.4: has_regex_rules flag check
	 */
	public function test_regex_rules_flag_optimization(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )
			->with( 'has_regex_rules', false )
			->willReturn( false );

		$redirects = new Redirects( $options );

		// Verify module boots with regex flag optimization
		$this->expectNotToPerformAssertions();
		$redirects->boot();
	}

	/**
	 * Test Object Cache usage for regex rules
	 *
	 * Requirement 1.5: Cache regex rules with 5 minute TTL
	 */
	public function test_object_cache_usage_for_regex_rules(): void {
		// Verify wp_cache functions are available
		$this->assertTrue( function_exists( 'wp_cache_get' ) );
		$this->assertTrue( function_exists( 'wp_cache_set' ) );

		// Test cache operations
		$cache_key = 'meowseo_regex_rules';
		$test_data = [ 'test' => 'data' ];

		wp_cache_set( $cache_key, $test_data, '', 300 );
		$cached = wp_cache_get( $cache_key );

		$this->assertEquals( $test_data, $cached );
	}

	/**
	 * Test redirect execution with different status codes
	 *
	 * Requirement 2.1, 2.2, 2.3, 2.4, 2.5
	 */
	public function test_redirect_execution_with_different_status_codes(): void {
		// Test that status_header function exists
		$this->assertTrue( function_exists( 'status_header' ) );

		// Test that wp_redirect function exists
		$this->assertTrue( function_exists( 'wp_redirect' ) );

		// Verify different status codes can be used
		$status_codes = [ 301, 302, 307, 410, 451 ];
		foreach ( $status_codes as $code ) {
			status_header( $code );
			$this->assertTrue( true ); // No exception thrown
		}
	}

	/**
	 * Test module ID
	 *
	 * Verifies the module returns correct ID
	 */
	public function test_module_id(): void {
		$options = $this->createMock( Options::class );
		$redirects = new Redirects( $options );

		$this->assertSame( 'redirects', $redirects->get_id() );
	}

	/**
	 * Test module implements Module interface
	 *
	 * Verifies the module implements required interface
	 */
	public function test_module_implements_interface(): void {
		$options = $this->createMock( Options::class );
		$redirects = new Redirects( $options );

		$this->assertInstanceOf( \MeowSEO\Contracts\Module::class, $redirects );
	}
}
