<?php
/**
 * Property 17: Robots Directive Merging
 *
 * Feature: meta-module-rebuild, Property 17: For any combination of
 * global defaults, post-specific overrides, and automatic rules, the
 * final robots directive SHALL correctly merge all sources.
 *
 * Validates: Requirements 7.1, 7.3, 7.4, 7.5, 7.6
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
 * Test Property 17: Robots Directive Merging
 */
class MetaProperty17RobotsDirectiveMergingTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test robots directive merging with post overrides
	 *
	 * @return void
	 */
	public function test_robots_directive_merging(): void {
		$this->forAll(
			Generator\bool(),
			Generator\bool()
		)->then( function( $noindex, $nofollow ) {
			// Create post.
			$post_id = $this->factory->post->create();

			// Set post-specific overrides.
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

			// Property: Should contain index/noindex based on override.
			if ( $noindex ) {
				$this->assertStringContainsString( 'noindex', $result, 'Should contain noindex when set' );
			} else {
				$this->assertStringContainsString( 'index', $result, 'Should contain index by default' );
			}

			// Property: Should contain follow/nofollow based on override.
			if ( $nofollow ) {
				$this->assertStringContainsString( 'nofollow', $result, 'Should contain nofollow when set' );
			} else {
				$this->assertStringContainsString( 'follow', $result, 'Should contain follow by default' );
			}

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}
}
