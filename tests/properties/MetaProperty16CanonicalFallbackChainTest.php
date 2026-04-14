<?php
/**
 * Property 16: Canonical Fallback Chain Completeness
 *
 * Feature: meta-module-rebuild, Property 16: For any page, the canonical
 * URL resolution SHALL follow the fallback chain based on page type.
 *
 * Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5
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
 * Test Property 16: Canonical Fallback Chain Completeness
 */
class MetaProperty16CanonicalFallbackChainTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test canonical fallback chain with custom canonical
	 *
	 * @return void
	 */
	public function test_canonical_uses_custom_when_set(): void {
		$this->forAll(
			Generator\string()
		)->then( function( $custom_url ) {
			// Skip empty URLs.
			if ( empty( $custom_url ) ) {
				return;
			}

			// Create post.
			$post_id = $this->factory->post->create();

			// Set custom canonical.
			$full_url = 'https://example.com/' . $custom_url;
			update_post_meta( $post_id, '_meowseo_canonical', $full_url );

			// Resolve canonical.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_canonical( $post_id );

			// Property: Should use custom canonical when set.
			$this->assertStringContainsString( $custom_url, $result, 'Should use custom canonical' );

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test canonical never empty
	 *
	 * @return void
	 */
	public function test_canonical_never_empty(): void {
		$this->forAll(
			Generator\bool()
		)->then( function( $has_custom ) {
			// Create post.
			$post_id = $this->factory->post->create();

			if ( $has_custom ) {
				update_post_meta( $post_id, '_meowseo_canonical', 'https://example.com/custom' );
			}

			// Resolve canonical.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_canonical( $post_id );

			// Property: Canonical should never be empty.
			$this->assertNotEmpty( $result, 'Canonical should never be empty' );
			$this->assertStringStartsWith( 'http', $result, 'Should be valid URL' );

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}
}
