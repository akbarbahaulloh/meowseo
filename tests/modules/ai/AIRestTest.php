<?php
/**
 * AI REST API Tests
 *
 * Tests for the AI_REST class.
 *
 * @package MeowSEO\Tests\Modules\AI
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\AI_REST;
use MeowSEO\Modules\AI\AI_Generator;
use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Options;

/**
 * AI_REST test case
 *
 * Tests the AI_REST class for REST endpoint registration and handling.
 * Requirements: 28.1, 28.2, 28.3, 28.4, 28.5, 28.6, 28.8, 25.1, 25.2, 25.3, 25.4, 25.5, 25.6, 26.1, 26.2, 26.3, 26.4, 26.5
 */
class AIRestTest extends TestCase {

	/**
	 * AI_REST instance
	 *
	 * @var AI_REST
	 */
	private AI_REST $rest;

	/**
	 * Generator mock
	 *
	 * @var AI_Generator
	 */
	private $generator_mock;

	/**
	 * Provider Manager mock
	 *
	 * @var AI_Provider_Manager
	 */
	private $provider_manager_mock;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Create mocks
		$this->generator_mock = $this->createMock( AI_Generator::class );
		$this->provider_manager_mock = $this->createMock( AI_Provider_Manager::class );

		// Create AI_REST instance
		$this->rest = new AI_REST( $this->generator_mock, $this->provider_manager_mock );
	}

	/**
	 * Test AI_REST class can be loaded
	 *
	 * @return void
	 */
	public function test_ai_rest_class_can_be_loaded(): void {
		$this->assertTrue( class_exists( AI_REST::class ) );
	}

	/**
	 * Test AI_REST can be instantiated
	 *
	 * @return void
	 */
	public function test_ai_rest_can_be_instantiated(): void {
		$this->assertInstanceOf( AI_REST::class, $this->rest );
	}

	/**
	 * Test register_routes method exists
	 *
	 * Requirement: 28.1, 25.1
	 *
	 * @return void
	 */
	public function test_register_routes_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'register_routes' ) );
	}

	/**
	 * Test generate method exists
	 *
	 * Requirement: 28.1, 28.2, 28.3, 28.4, 28.5, 28.6, 28.8
	 *
	 * @return void
	 */
	public function test_generate_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'generate' ) );
	}

	/**
	 * Test generate_image method exists
	 *
	 * Requirement: 9.2, 9.3, 9.4
	 *
	 * @return void
	 */
	public function test_generate_image_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'generate_image' ) );
	}

	/**
	 * Test get_provider_status method exists
	 *
	 * Requirement: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6
	 *
	 * @return void
	 */
	public function test_get_provider_status_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'get_provider_status' ) );
	}

	/**
	 * Test apply method exists
	 *
	 * Requirement: 8.6, 27.1-27.10
	 *
	 * @return void
	 */
	public function test_apply_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'apply' ) );
	}

	/**
	 * Test test_provider method exists
	 *
	 * Requirement: 2.4, 2.5, 2.6, 2.7
	 *
	 * @return void
	 */
	public function test_test_provider_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'test_provider' ) );
	}

	/**
	 * Test check_edit_posts_capability method exists
	 *
	 * Requirement: 25.2, 25.5
	 *
	 * @return void
	 */
	public function test_check_edit_posts_capability_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'check_edit_posts_capability' ) );
	}

	/**
	 * Test check_manage_options_capability method exists
	 *
	 * Requirement: 25.2, 25.5
	 *
	 * @return void
	 */
	public function test_check_manage_options_capability_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'check_manage_options_capability' ) );
	}

	/**
	 * Test sanitize_content_object method exists
	 *
	 * Requirement: 26.1, 26.2, 26.3
	 *
	 * @return void
	 */
	public function test_sanitize_content_object_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'sanitize_content_object' ) );
	}

	/**
	 * Test sanitize_image_object method exists
	 *
	 * Requirement: 26.1, 26.2, 26.3
	 *
	 * @return void
	 */
	public function test_sanitize_image_object_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'sanitize_image_object' ) );
	}

	/**
	 * Test sanitize_fields_array method exists
	 *
	 * Requirement: 26.1, 26.2, 26.3
	 *
	 * @return void
	 */
	public function test_sanitize_fields_array_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'sanitize_fields_array' ) );
	}

	/**
	 * Test sanitize_content_object returns array
	 *
	 * Requirement: 26.1, 26.2, 26.3
	 *
	 * @return void
	 */
	public function test_sanitize_content_object_returns_array(): void {
		$result = $this->rest->sanitize_content_object( array( 'key' => 'value' ) );
		$this->assertIsArray( $result );
	}

	/**
	 * Test sanitize_content_object handles non-array input
	 *
	 * Requirement: 26.1, 26.2, 26.3
	 *
	 * @return void
	 */
	public function test_sanitize_content_object_handles_non_array(): void {
		$result = $this->rest->sanitize_content_object( 'not an array' );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test sanitize_image_object returns array
	 *
	 * Requirement: 26.1, 26.2, 26.3
	 *
	 * @return void
	 */
	public function test_sanitize_image_object_returns_array(): void {
		$result = $this->rest->sanitize_image_object( array( 'url' => 'https://example.com/image.png' ) );
		$this->assertIsArray( $result );
	}

	/**
	 * Test sanitize_image_object handles non-array input
	 *
	 * Requirement: 26.1, 26.2, 26.3
	 *
	 * @return void
	 */
	public function test_sanitize_image_object_handles_non_array(): void {
		$result = $this->rest->sanitize_image_object( 'not an array' );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test sanitize_fields_array returns array
	 *
	 * Requirement: 26.1, 26.2, 26.3
	 *
	 * @return void
	 */
	public function test_sanitize_fields_array_returns_array(): void {
		$result = $this->rest->sanitize_fields_array( array( 'field1', 'field2' ) );
		$this->assertIsArray( $result );
	}

	/**
	 * Test sanitize_fields_array handles non-array input
	 *
	 * Requirement: 26.1, 26.2, 26.3
	 *
	 * @return void
	 */
	public function test_sanitize_fields_array_handles_non_array(): void {
		$result = $this->rest->sanitize_fields_array( 'not an array' );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test AI_REST has correct namespace constant
	 *
	 * Requirement: 28.1, 25.1
	 *
	 * @return void
	 */
	public function test_ai_rest_has_correct_namespace(): void {
		$reflection = new \ReflectionClass( AI_REST::class );
		$constants = $reflection->getConstants();
		$this->assertArrayHasKey( 'NAMESPACE', $constants );
		$this->assertEquals( 'meowseo/v1', $constants['NAMESPACE'] );
	}

	/**
	 * Test check_permission_and_nonce method exists
	 *
	 * Requirement: 25.2, 25.3, 25.4, 25.5
	 *
	 * @return void
	 */
	public function test_check_permission_and_nonce_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'check_permission_and_nonce' ) );
	}

	/**
	 * Test check_permission_and_nonce_for_settings method exists
	 *
	 * Requirement: 25.2, 25.3, 25.4, 25.5
	 *
	 * @return void
	 */
	public function test_check_permission_and_nonce_for_settings_method_exists(): void {
		$this->assertTrue( method_exists( $this->rest, 'check_permission_and_nonce_for_settings' ) );
	}
}
