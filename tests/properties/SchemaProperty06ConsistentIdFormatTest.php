<?php
/**
 * Property 6: Consistent @id Format
 *
 * Feature: schema-sitemap-system, Property 6: For any schema node in the @graph array,
 * the @id property SHALL match the pattern `{url}#{fragment}` where url is a valid URL
 * and fragment is a non-empty string.
 *
 * Validates: Requirements 1.7
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use WP_UnitTestCase;
use MeowSEO\Helpers\Abstract_Schema_Node;
use MeowSEO\Options;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Concrete implementation of Abstract_Schema_Node for testing
 */
class Test_Schema_Node extends Abstract_Schema_Node {
	public function generate(): array {
		return array(
			'@type' => 'TestNode',
			'@id'   => $this->get_id_url( 'test' ),
		);
	}

	public function is_needed(): bool {
		return true;
	}
}

/**
 * Test Property 6: Consistent @id Format
 *
 * **Validates: Requirements 1.7**
 *
 * This test verifies that all schema nodes generate @id values in the consistent
 * format: {url}#{fragment}, where:
 * - url is a valid URL (starts with http:// or https://)
 * - fragment is a non-empty string (the part after #)
 *
 * This format is required for Google Knowledge Graph resolution.
 */
class SchemaProperty06ConsistentIdFormatTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test that get_id_url generates consistent format
	 *
	 * For any post, get_id_url should generate @id values matching
	 * the pattern {url}#{fragment}.
	 *
	 * @return void
	 */
	public function test_get_id_url_generates_consistent_format(): void {
		$this->forAll(
			Generator\string( 1, 100 ),
			Generator\string( 1, 50 )
		)->then( function( $post_title, $fragment ) {
			// Skip empty fragments.
			if ( empty( $fragment ) ) {
				return;
			}

			// Create a test post.
			$post_id = \wp_insert_post(
				array(
					'post_title'   => $post_title,
					'post_status'  => 'publish',
					'post_type'    => 'post',
				)
			);

			$post = \get_post( $post_id );
			$options = new Options();

			// Create a test node.
			$node = new Test_Schema_Node( $post_id, $post, $options );

			// Get the ID URL using the protected method via reflection.
			$reflection = new \ReflectionClass( $node );
			$method = $reflection->getMethod( 'get_id_url' );
			$method->setAccessible( true );
			$id_url = $method->invoke( $node, $fragment );

			// Property: @id must be a string.
			$this->assertIsString( $id_url );

			// Property: @id must contain # character.
			$this->assertStringContainsString( '#', $id_url );

			// Property: @id must match pattern {url}#{fragment}.
			$this->assert_id_format_is_valid( $id_url );

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test that @id URL part is valid
	 *
	 * For any post, the URL part of @id should be a valid URL.
	 *
	 * @return void
	 */
	public function test_id_url_part_is_valid(): void {
		$this->forAll(
			Generator\string( 1, 100 )
		)->then( function( $post_title ) {
			// Create a test post.
			$post_id = \wp_insert_post(
				array(
					'post_title'   => $post_title,
					'post_status'  => 'publish',
					'post_type'    => 'post',
				)
			);

			$post = \get_post( $post_id );
			$options = new Options();

			// Create a test node.
			$node = new Test_Schema_Node( $post_id, $post, $options );

			// Get the ID URL.
			$reflection = new \ReflectionClass( $node );
			$method = $reflection->getMethod( 'get_id_url' );
			$method->setAccessible( true );
			$id_url = $method->invoke( $node, 'test' );

			// Extract URL part (everything before the first #).
			$hash_pos = strpos( $id_url, '#' );
			$this->assertNotFalse( $hash_pos, '@id must contain # character' );
			$url_part = substr( $id_url, 0, $hash_pos );

			// Property: URL part must be valid.
			$this->assert_is_valid_url( $url_part );

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test that @id fragment part is non-empty
	 *
	 * For any post, the fragment part of @id should be non-empty.
	 *
	 * @return void
	 */
	public function test_id_fragment_part_is_non_empty(): void {
		$this->forAll(
			Generator\string( 1, 50 )
		)->then( function( $fragment ) {
			// Skip empty fragments and fragments with # characters.
			if ( empty( $fragment ) || strpos( $fragment, '#' ) !== false ) {
				return;
			}

			// Create a test post.
			$post_id = \wp_insert_post(
				array(
					'post_title'   => 'Test Post',
					'post_status'  => 'publish',
					'post_type'    => 'post',
				)
			);

			$post = \get_post( $post_id );
			$options = new Options();

			// Create a test node.
			$node = new Test_Schema_Node( $post_id, $post, $options );

			// Get the ID URL.
			$reflection = new \ReflectionClass( $node );
			$method = $reflection->getMethod( 'get_id_url' );
			$method->setAccessible( true );
			$id_url = $method->invoke( $node, $fragment );

			// Extract fragment part (everything after the first #).
			$hash_pos = strpos( $id_url, '#' );
			$this->assertNotFalse( $hash_pos, '@id must contain # character' );
			$fragment_part = substr( $id_url, $hash_pos + 1 );

			// Property: Fragment part must be non-empty.
			$this->assertNotEmpty( $fragment_part );

			// Property: Fragment part should match the input fragment.
			$this->assertEquals( $fragment, $fragment_part );

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test that @id contains exactly one # character
	 *
	 * For any post, the @id should contain exactly one # character.
	 *
	 * @return void
	 */
	public function test_id_contains_exactly_one_hash(): void {
		$this->forAll(
			Generator\string( 1, 100 )
		)->then( function( $post_title ) {
			// Create a test post.
			$post_id = \wp_insert_post(
				array(
					'post_title'   => $post_title,
					'post_status'  => 'publish',
					'post_type'    => 'post',
				)
			);

			$post = \get_post( $post_id );
			$options = new Options();

			// Create a test node.
			$node = new Test_Schema_Node( $post_id, $post, $options );

			// Get the ID URL.
			$reflection = new \ReflectionClass( $node );
			$method = $reflection->getMethod( 'get_id_url' );
			$method->setAccessible( true );
			$id_url = $method->invoke( $node, 'test' );

			// Property: @id must contain exactly one # character.
			$this->assertEquals(
				1,
				substr_count( $id_url, '#' ),
				'@id must contain exactly one # character'
			);

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test that @id URL matches post permalink
	 *
	 * For any post, the URL part of @id should be the post's permalink.
	 *
	 * @return void
	 */
	public function test_id_url_matches_post_permalink(): void {
		$this->forAll(
			Generator\string( 1, 100 )
		)->then( function( $post_title ) {
			// Create a test post.
			$post_id = \wp_insert_post(
				array(
					'post_title'   => $post_title,
					'post_status'  => 'publish',
					'post_type'    => 'post',
				)
			);

			$post = \get_post( $post_id );
			$options = new Options();

			// Get the post permalink.
			$post_permalink = \get_permalink( $post_id );
			$post_permalink = rtrim( $post_permalink, '/' );

			// Create a test node.
			$node = new Test_Schema_Node( $post_id, $post, $options );

			// Get the ID URL.
			$reflection = new \ReflectionClass( $node );
			$method = $reflection->getMethod( 'get_id_url' );
			$method->setAccessible( true );
			$id_url = $method->invoke( $node, 'test' );

			// Extract URL part from @id (everything before the first #).
			$hash_pos = strpos( $id_url, '#' );
			$this->assertNotFalse( $hash_pos, '@id must contain # character' );
			$id_url_part = substr( $id_url, 0, $hash_pos );
			$id_url_part = rtrim( $id_url_part, '/' );

			// Property: URL part should match post permalink.
			$this->assertEquals(
				$post_permalink,
				$id_url_part,
				'@id URL should match post permalink'
			);

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Assert that @id format is valid
	 *
	 * Validates that @id matches pattern {url}#{fragment}.
	 *
	 * @param string $id The @id value to validate.
	 * @return void
	 */
	private function assert_id_format_is_valid( string $id ): void {
		// Property: @id must not be empty.
		$this->assertNotEmpty( $id );

		// Property: @id must have URL part before #.
		$hash_pos = strpos( $id, '#' );
		$this->assertNotFalse( $hash_pos, '@id must contain # character' );

		$url_part = substr( $id, 0, $hash_pos );
		$fragment_part = substr( $id, $hash_pos + 1 );

		// Property: URL part must be non-empty.
		$this->assertNotEmpty( $url_part );

		// Property: Fragment part must be non-empty.
		$this->assertNotEmpty( $fragment_part );

		// Property: URL part must be a valid URL.
		$this->assert_is_valid_url( $url_part );
	}

	/**
	 * Assert that a string is a valid URL
	 *
	 * @param string $url The URL to validate.
	 * @return void
	 */
	private function assert_is_valid_url( string $url ): void {
		// Property: URL must start with http:// or https://.
		$this->assertTrue(
			strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0,
			sprintf( 'URL must start with http:// or https://, got: %s', $url )
		);

		// Property: URL must be parseable by filter_var.
		$this->assertNotFalse(
			filter_var( $url, FILTER_VALIDATE_URL ),
			sprintf( 'URL must be valid according to filter_var, got: %s', $url )
		);
	}
}
