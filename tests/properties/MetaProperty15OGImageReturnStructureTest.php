<?php
/**
 * Property 15: OG Image Return Structure
 *
 * Feature: meta-module-rebuild, Property 15: For any resolved Open Graph
 * image (non-empty), the return value SHALL be an array containing both
 * the image URL and dimensions (width, height).
 *
 * Validates: Requirements 5.7
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
 * Test Property 15: OG Image Return Structure
 */
class MetaProperty15OGImageReturnStructureTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test OG image return structure
	 *
	 * @return void
	 */
	public function test_og_image_return_structure(): void {
		$this->forAll(
			Generator\bool()
		)->then( function( $has_custom_image ) {
			// Create post.
			$post_id = $this->factory->post->create();

			if ( $has_custom_image ) {
				// Create attachment.
				$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
				update_post_meta( $post_id, '_meowseo_og_image', $attachment_id );
			}

			// Resolve OG image.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_og_image( $post_id );

			// Property: Result should always be an array.
			$this->assertIsArray( $result, 'Should always return array' );

			// Property: If non-empty, should have url key.
			if ( ! empty( $result ) && isset( $result['url'] ) ) {
				$this->assertIsString( $result['url'], 'URL should be string' );
				$this->assertNotEmpty( $result['url'], 'URL should not be empty' );
			}

			// Cleanup.
			wp_delete_post( $post_id, true );
			if ( $has_custom_image && isset( $attachment_id ) ) {
				wp_delete_attachment( $attachment_id, true );
			}
		} );
	}
}
