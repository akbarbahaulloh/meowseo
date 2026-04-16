<?php
/**
 * GSC API Test
 *
 * Tests for the GSC_API class.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Tests\Modules\GSC
 */

namespace MeowSEO\Tests\Modules\GSC;

use MeowSEO\Modules\GSC\GSC_API;
use MeowSEO\Modules\GSC\GSC_Auth;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * GSC_API Test class
 */
class GSCAPITest extends TestCase {

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Define AUTH_KEY if not already defined (for testing).
		if ( ! defined( 'AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'test-auth-key-for-encryption-testing-purposes-only' );
		}
	}

	/**
	 * Test inspect_url returns error when no valid token.
	 *
	 * Validates Requirement 14.4: Return error array if get_valid_token() returns false.
	 *
	 * @return void
	 */
	public function test_inspect_url_returns_error_when_no_valid_token(): void {
		// Mock GSC_Auth to return null token.
		$auth = $this->createMock( GSC_Auth::class );
		$auth->method( 'get_valid_token' )->willReturn( null );

		$api = new GSC_API( $auth );

		$result = $api->inspect_url( 'https://example.com/test-page/' );

		$this->assertFalse( $result['success'] );
		$this->assertNull( $result['data'] );
		$this->assertEquals( 0, $result['http_code'] );
	}

	/**
	 * Test submit_for_indexing returns error when no valid token.
	 *
	 * Validates Requirement 11.4: Return error array if get_valid_token() returns false.
	 *
	 * @return void
	 */
	public function test_submit_for_indexing_returns_error_when_no_valid_token(): void {
		// Mock GSC_Auth to return null token.
		$auth = $this->createMock( GSC_Auth::class );
		$auth->method( 'get_valid_token' )->willReturn( null );

		$api = new GSC_API( $auth );

		$result = $api->submit_for_indexing( 'https://example.com/test-page/' );

		$this->assertFalse( $result['success'] );
		$this->assertNull( $result['data'] );
		$this->assertEquals( 0, $result['http_code'] );
	}

	/**
	 * Test get_search_analytics returns error when no valid token.
	 *
	 * Validates Requirement 15.4: Return consistent array shape with keys for success, data, and http_code.
	 *
	 * @return void
	 */
	public function test_get_search_analytics_returns_error_when_no_valid_token(): void {
		// Mock GSC_Auth to return null token.
		$auth = $this->createMock( GSC_Auth::class );
		$auth->method( 'get_valid_token' )->willReturn( null );

		$api = new GSC_API( $auth );

		$result = $api->get_search_analytics(
			'https://example.com/',
			'2024-01-01',
			'2024-01-31',
			[ 'page', 'query' ]
		);

		$this->assertFalse( $result['success'] );
		$this->assertNull( $result['data'] );
		$this->assertEquals( 0, $result['http_code'] );
	}

	/**
	 * Test response format is consistent.
	 *
	 * Validates Requirement 15.4: Return consistent array shape with keys for success, data, and http_code.
	 *
	 * @return void
	 */
	public function test_response_format_is_consistent(): void {
		// Mock GSC_Auth to return null token.
		$auth = $this->createMock( GSC_Auth::class );
		$auth->method( 'get_valid_token' )->willReturn( null );

		$api = new GSC_API( $auth );

		// Test inspect_url response format.
		$result = $api->inspect_url( 'https://example.com/test-page/' );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'http_code', $result );
		$this->assertIsBool( $result['success'] );
		$this->assertIsInt( $result['http_code'] );

		// Test submit_for_indexing response format.
		$result = $api->submit_for_indexing( 'https://example.com/test-page/' );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'http_code', $result );
		$this->assertIsBool( $result['success'] );
		$this->assertIsInt( $result['http_code'] );

		// Test get_search_analytics response format.
		$result = $api->get_search_analytics(
			'https://example.com/',
			'2024-01-01',
			'2024-01-31',
			[ 'page', 'query' ]
		);
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'http_code', $result );
		$this->assertIsBool( $result['success'] );
		$this->assertIsInt( $result['http_code'] );
	}

	/**
	 * Test API constants are defined correctly.
	 *
	 * Validates that the API URLs match Google's documented endpoints.
	 *
	 * @return void
	 */
	public function test_api_constants(): void {
		// Use reflection to access private constants.
		$reflection = new \ReflectionClass( GSC_API::class );

		$inspection_url = $reflection->getConstant( 'INSPECTION_API_URL' );
		$indexing_url = $reflection->getConstant( 'INDEXING_API_URL' );
		$analytics_url = $reflection->getConstant( 'ANALYTICS_API_URL' );

		$this->assertEquals( 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', $inspection_url );
		$this->assertEquals( 'https://indexing.googleapis.com/v3/urlNotifications:publish', $indexing_url );
		$this->assertEquals( 'https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/searchAnalytics/query', $analytics_url );
	}

	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
	}
}
