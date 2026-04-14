<?php
/**
 * Property 3: Google Discover Directives Always Present
 *
 * Feature: meta-module-rebuild, Property 3: For any robots configuration,
 * the output SHALL always contain the Google Discover directives.
 *
 * Validates: Requirements 2.4, 7.2, 7.7
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
 * Test Property 3: Google Discover Directives Always Present
 */
class MetaProperty03GoogleDiscoverDirectivesTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test Google Discover directives always present
	 *
	 * @return void
	 */
	public function test_google_discover_directives_always_present(): void {
		$this->forAll(
			Generator\bool(),
			Generator\bool()
		)->then( function( $noindex, $nofollow ) {
			// Create post.
			$post_id = $this->factory->post->create();

			// Set random overrides.
			if ( $noindex ) {
				update_post_meta( $post_id, '_meowseo_robots_noindex', true );
			}
			if ( $nofollow ) {
				update_post_meta( $post_id, '_meowseo_robots_nofollow', true );
			}

			// Resolve robots.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_robots( $post_id );

			// Property: Should always contain Google Discover directives.
			$this->assertStringContainsString( 'max-image-preview:large', $result, 'Should contain max-image-preview:large' );
			$this->assertStringContainsString( 'max-snippet:-1', $result, 'Should contain max-snippet:-1' );
			$this->assertStringContainsString( 'max-video-preview:-1', $result, 'Should contain max-video-preview:-1' );

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}
}
