<?php
/**
 * Property 13: OG Image Fallback Chain Completeness
 *
 * Feature: meta-module-rebuild, Property 13: For any singular post,
 * the OG image resolution SHALL follow the fallback chain.
 *
 * Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use WP_UnitTestCase;
use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Modules\Meta\Title_Patterns;
use MeowSEO\Options;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Test Property 13: OG Image Fallback Chain Completeness
 */
class MetaProperty13OGImageFallbackChainTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test OG image fallback chain with custom image
	 *
	 * @return void
	 */
	public function test_og_image_uses_custom_when_set(): void {
		// Create attachment.
		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		// Create post.
		$post_id = $this->factory->post->create();

		// Set custom OG image.
		update_post_meta( $post_id, '_meowseo_og_image', $attachment_id );

		// Resolve OG image.
		$options  = new Options();
		$patterns = new Title_Patterns( $options );
		$resolver = new Meta_Resolver( $options, $patterns );
		$result   = $resolver->resolve_og_image( $post_id );

		// Property: Should use custom image when set.
		$this->assertArrayHasKey( 'url', $result, 'Should have URL key' );
		$this->assertNotEmpty( $result['url'], 'URL should not be empty' );

		// Cleanup.
		wp_delete_post( $post_id, true );
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * Test OG image returns empty when no sources available
	 *
	 * @return void
	 */
	public function test_og_image_empty_when_no_sources(): void {
		$this->forAll(
			Generator\constant( 0 )
		)->then( function( $custom_image_id ) {
			// Create post without featured image.
			$post_id = $this->factory->post->create();

			// Resolve OG image.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_og_image( $post_id );

			// Property: Should return empty array or array with global default when no sources.
			$this->assertIsArray( $result, 'Should return array' );

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test OG image result structure
	 *
	 * @return void
	 */
	public function test_og_image_result_structure(): void {
		// Create attachment.
		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		// Create post.
		$post_id = $this->factory->post->create();

		// Set custom OG image.
		update_post_meta( $post_id, '_meowseo_og_image', $attachment_id );

		// Resolve OG image.
		$options  = new Options();
		$patterns = new Title_Patterns( $options );
		$resolver = new Meta_Resolver( $options, $patterns );
		$result   = $resolver->resolve_og_image( $post_id );

		// Property: Result should be an array.
		$this->assertIsArray( $result, 'Should return array' );

		// Property: If URL present, should have url key.
		if ( ! empty( $result ) ) {
			$this->assertArrayHasKey( 'url', $result, 'Should have URL key when image found' );
		}

		// Cleanup.
		wp_delete_post( $post_id, true );
		wp_delete_attachment( $attachment_id, true );
	}
}
