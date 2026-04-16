<?php
/**
 * Tests for Redirects REST API
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Redirects;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Redirects\Redirects_REST;
use MeowSEO\Options;

/**
 * Test Redirects REST API endpoints
 *
 * Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6
 */
class RedirectsRESTTest extends TestCase {

	/**
	 * Test REST API instance creation
	 *
	 * Verifies that the REST API class can be instantiated.
	 */
	public function test_rest_api_instantiation(): void {
		$options = $this->createMock( Options::class );
		$rest = new Redirects_REST( $options );

		$this->assertInstanceOf( Redirects_REST::class, $rest );
	}

	/**
	 * Test register routes method exists
	 *
	 * Verifies that the register_routes method can be called without errors.
	 */
	public function test_register_routes(): void {
		$options = $this->createMock( Options::class );
		$rest = new Redirects_REST( $options );

		// Should not throw any exceptions
		$this->expectNotToPerformAssertions();
		$rest->register_routes();
	}

	/**
	 * Test check_manage_options method
	 *
	 * Requirement 16.6: Verify user capability check method exists
	 */
	public function test_check_manage_options_method_exists(): void {
		$options = $this->createMock( Options::class );
		$rest = new Redirects_REST( $options );

		$this->assertTrue( method_exists( $rest, 'check_manage_options' ) );
	}

	/**
	 * Test all required public methods exist
	 *
	 * Requirements: 16.1, 16.2, 16.3, 16.4, 16.5
	 */
	public function test_required_methods_exist(): void {
		$options = $this->createMock( Options::class );
		$rest = new Redirects_REST( $options );

		// Requirement 16.1: Create redirect
		$this->assertTrue( method_exists( $rest, 'create_redirect' ) );

		// Requirement 16.2: Update redirect
		$this->assertTrue( method_exists( $rest, 'update_redirect' ) );

		// Requirement 16.3: Delete redirect
		$this->assertTrue( method_exists( $rest, 'delete_redirect' ) );

		// Requirement 16.4: Import redirects
		$this->assertTrue( method_exists( $rest, 'import_redirects' ) );

		// Requirement 16.5: Export redirects
		$this->assertTrue( method_exists( $rest, 'export_redirects' ) );

		// Requirement 16.6: Permission checks
		$this->assertTrue( method_exists( $rest, 'check_manage_options_and_nonce' ) );
	}
}

