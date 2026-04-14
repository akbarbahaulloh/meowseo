<?php
/**
 * Property 8: Twitter Card Independence
 *
 * Feature: meta-module-rebuild, Property 8: For any post where Twitter
 * Card values are set differently from Open Graph values, the Twitter
 * Card output SHALL reflect the Twitter-specific values.
 *
 * Validates: Requirements 2.8
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
 * Test Property 8: Twitter Card Independence
 */
class MetaProperty08TwitterCardIndependenceTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test Twitter Card independence from Open Graph
	 *
	 * @return void
	 */
	public function test_twitter_card_independence(): void {
		$this->forAll(
			Generator\string(),
			Generator\string()
		)->then( function( $twitter_title, $og_title ) {
			// Skip if both empty.
			if ( empty( $twitter_title ) && empty( $og_title ) ) {
				return;
			}

			// Create post.
			$post_id = $this->factory->post->create(
				array(
					'post_title' => $og_title,
				)
			);

			// Set Twitter-specific title.
			if ( ! empty( $twitter_title ) ) {
				update_post_meta( $post_id, '_meowseo_twitter_title', $twitter_title );
			}

			// Resolve Twitter title.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_twitter_title( $post_id );

			// Property: Should use Twitter-specific value when set.
			if ( ! empty( $twitter_title ) ) {
				$this->assertEquals( $twitter_title, $result, 'Should use Twitter-specific title' );
			}

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test Twitter description independence
	 *
	 * @return void
	 */
	public function test_twitter_description_independence(): void {
		$this->forAll(
			Generator\string()
		)->then( function( $twitter_description ) {
			// Skip if empty.
			if ( empty( $twitter_description ) ) {
				return;
			}

			// Create post.
			$post_id = $this->factory->post->create();

			// Set Twitter-specific description.
			update_post_meta( $post_id, '_meowseo_twitter_description', $twitter_description );

			// Resolve Twitter description.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_twitter_description( $post_id );

			// Property: Should use Twitter-specific value when set.
			$this->assertEquals( $twitter_description, $result, 'Should use Twitter-specific description' );

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}
}
