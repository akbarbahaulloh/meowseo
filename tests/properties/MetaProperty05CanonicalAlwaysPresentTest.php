<?php
/**
 * Property 5: Canonical Always Present
 *
 * Feature: meta-module-rebuild, Property 5: For any page type, the
 * canonical URL SHALL always be non-empty.
 *
 * Validates: Requirements 2.5, 6.7
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
 * Test Property 5: Canonical Always Present
 */
class MetaProperty05CanonicalAlwaysPresentTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test canonical always present
	 *
	 * @return void
	 */
	public function test_canonical_always_present(): void {
		$this->forAll(
			Generator\bool(),
			Generator\bool()
		)->then( function( $has_custom, $has_post_id ) {
			$post_id = null;

			if ( $has_post_id ) {
				// Create post.
				$post_id = $this->factory->post->create();

				if ( $has_custom ) {
					update_post_meta( $post_id, '_meowseo_canonical', 'https://example.com/custom' );
				}
			}

			// Resolve canonical.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_canonical( $post_id );

			// Property: Canonical should always be non-empty.
			$this->assertNotEmpty( $result, 'Canonical should always be non-empty' );
			$this->assertIsString( $result, 'Canonical should be string' );
			$this->assertStringStartsWith( 'http', $result, 'Should be valid URL' );

			// Cleanup.
			if ( $post_id ) {
				wp_delete_post( $post_id, true );
			}
		} );
	}
}
