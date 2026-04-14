<?php
/**
 * REST API Tests
 *
 * Tests for the centralized REST API layer.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

/**
 * REST API test case
 *
 * @since 1.0.0
 */
class Test_REST_API extends WP_UnitTestCase {

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a test post.
		$this->post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content for REST API testing.',
				'post_status'  => 'publish',
			)
		);

		// Set some SEO meta.
		update_post_meta( $this->post_id, 'meowseo_title', 'Test SEO Title' );
		update_post_meta( $this->post_id, 'meowseo_description', 'Test SEO Description' );
		update_post_meta( $this->post_id, 'meowseo_robots', 'index,follow' );
		update_post_meta( $this->post_id, 'meowseo_canonical', 'https://example.com/test' );
	}

	/**
	 * Test GET /meowseo/v1/meta/{post_id} endpoint
	 *
	 * @return void
	 */
	public function test_get_meta_endpoint(): void {
		// Create REST request.
		$request = new WP_REST_Request( 'GET', '/meowseo/v1/meta/' . $this->post_id );

		// Execute request.
		$response = rest_do_request( $request );

		// Assert response is successful.
		$this->assertEquals( 200, $response->get_status() );

		// Get response data.
		$data = $response->get_data();

		// Assert post_id is correct.
		$this->assertEquals( $this->post_id, $data['post_id'] );

		// Assert Cache-Control header is set.
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertEquals( 'public, max-age=300', $headers['Cache-Control'] );
	}

	/**
	 * Test POST /meowseo/v1/meta/{post_id} endpoint
	 *
	 * @return void
	 */
	public function test_update_meta_endpoint(): void {
		// Create admin user and authenticate.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Create REST request.
		$request = new WP_REST_Request( 'POST', '/meowseo/v1/meta/' . $this->post_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'title', 'Updated SEO Title' );
		$request->set_param( 'description', 'Updated SEO Description' );

		// Execute request.
		$response = rest_do_request( $request );

		// Assert response is successful.
		$this->assertEquals( 200, $response->get_status() );

		// Get response data.
		$data = $response->get_data();

		// Assert success is true.
		$this->assertTrue( $data['success'] );

		// Assert meta was updated.
		$this->assertEquals( 'Updated SEO Title', get_post_meta( $this->post_id, 'meowseo_title', true ) );
		$this->assertEquals( 'Updated SEO Description', get_post_meta( $this->post_id, 'meowseo_description', true ) );

		// Assert Cache-Control header is set to no-store.
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertEquals( 'no-store', $headers['Cache-Control'] );
	}

	/**
	 * Test POST /meowseo/v1/meta/{post_id} endpoint without nonce
	 *
	 * @return void
	 */
	public function test_update_meta_endpoint_without_nonce(): void {
		// Create admin user and authenticate.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Create REST request without nonce.
		$request = new WP_REST_Request( 'POST', '/meowseo/v1/meta/' . $this->post_id );
		$request->set_param( 'title', 'Updated SEO Title' );

		// Execute request.
		$response = rest_do_request( $request );

		// Assert response is forbidden.
		$this->assertEquals( 403, $response->get_status() );

		// Get response data.
		$data = $response->get_data();

		// Assert success is false.
		$this->assertFalse( $data['success'] );
	}

	/**
	 * Test POST /meowseo/v1/meta/{post_id} endpoint without permission
	 *
	 * @return void
	 */
	public function test_update_meta_endpoint_without_permission(): void {
		// Create subscriber user and authenticate.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Create REST request.
		$request = new WP_REST_Request( 'POST', '/meowseo/v1/meta/' . $this->post_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'title', 'Updated SEO Title' );

		// Execute request.
		$response = rest_do_request( $request );

		// Assert response is forbidden.
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test GET /meowseo/v1/settings endpoint
	 *
	 * @return void
	 */
	public function test_get_settings_endpoint(): void {
		// Create admin user and authenticate.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Create REST request.
		$request = new WP_REST_Request( 'GET', '/meowseo/v1/settings' );

		// Execute request.
		$response = rest_do_request( $request );

		// Assert response is successful.
		$this->assertEquals( 200, $response->get_status() );

		// Get response data.
		$data = $response->get_data();

		// Assert success is true.
		$this->assertTrue( $data['success'] );

		// Assert settings are returned.
		$this->assertArrayHasKey( 'settings', $data );
		$this->assertIsArray( $data['settings'] );

		// Assert Cache-Control header is set.
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertEquals( 'public, max-age=300', $headers['Cache-Control'] );
	}

	/**
	 * Test POST /meowseo/v1/settings endpoint
	 *
	 * @return void
	 */
	public function test_update_settings_endpoint(): void {
		// Create admin user and authenticate.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Create REST request.
		$request = new WP_REST_Request( 'POST', '/meowseo/v1/settings' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'separator' => '-' ) ) );

		// Execute request.
		$response = rest_do_request( $request );

		// Assert response is successful.
		$this->assertEquals( 200, $response->get_status() );

		// Get response data.
		$data = $response->get_data();

		// Assert success is true.
		$this->assertTrue( $data['success'] );

		// Assert Cache-Control header is set to no-store.
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertEquals( 'no-store', $headers['Cache-Control'] );
	}

	/**
	 * Test GET /meowseo/v1/settings endpoint without permission
	 *
	 * @return void
	 */
	public function test_get_settings_endpoint_without_permission(): void {
		// Create subscriber user and authenticate.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Create REST request.
		$request = new WP_REST_Request( 'GET', '/meowseo/v1/settings' );

		// Execute request.
		$response = rest_do_request( $request );

		// Assert response is forbidden.
		$this->assertEquals( 403, $response->get_status() );
	}
}
