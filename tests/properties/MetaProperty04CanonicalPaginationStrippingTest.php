<?php
/**
 * Property 4: Canonical Pagination Stripping
 *
 * Feature: meta-module-rebuild, Property 4: For any URL containing
 * pagination parameters, the canonical URL SHALL strip all pagination
 * parameters.
 *
 * Validates: Requirements 2.5, 6.6
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
 * Test Property 4: Canonical Pagination Stripping
 */
class MetaProperty04CanonicalPaginationStrippingTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test canonical strips pagination parameters
	 *
	 * @return void
	 */
	public function test_canonical_strips_pagination_params(): void {
		$this->forAll(
			Generator\elements( '/page/2/', '/page/3/', '?paged=2', '?page=3' )
		)->then( function( $pagination_param ) {
			// Create post.
			$post_id = $this->factory->post->create();

			// Set custom canonical with pagination.
			$base_url = 'https://example.com/test-post';
			$url_with_pagination = $base_url . $pagination_param;
			update_post_meta( $post_id, '_meowseo_canonical', $url_with_pagination );

			// Resolve canonical.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_canonical( $post_id );

			// Property: Should strip pagination parameters.
			$this->assertStringNotContainsString( '/page/', $result, 'Should not contain /page/' );
			$this->assertStringNotContainsString( '?paged=', $result, 'Should not contain ?paged=' );
			$this->assertStringNotContainsString( '?page=', $result, 'Should not contain ?page=' );

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}
}
