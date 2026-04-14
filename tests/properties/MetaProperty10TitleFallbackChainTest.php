<?php
/**
 * Property 10: Title Fallback Chain Completeness
 *
 * Feature: meta-module-rebuild, Property 10: For any singular post,
 * the title resolution SHALL follow the fallback chain and never return empty.
 *
 * Validates: Requirements 3.1, 3.2, 3.3, 3.5, 3.6
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
 * Test Property 10: Title Fallback Chain Completeness
 */
class MetaProperty10TitleFallbackChainTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test title fallback chain completeness
	 *
	 * For any post, the title resolution should follow the fallback chain
	 * and never return an empty string.
	 *
	 * @return void
	 */
	public function test_title_fallback_chain_completeness(): void {
		$this->forAll(
			Generator\string(),
			Generator\string(),
			Generator\oneOf(
				Generator\constant( '' ),
				Generator\string()
			)
		)->then( function( $post_title, $site_name, $custom_title ) {
			// Create post.
			$post_id = $this->factory->post->create(
				array(
					'post_title' => $post_title,
				)
			);

			// Set custom title if provided.
			if ( ! empty( $custom_title ) ) {
				update_post_meta( $post_id, '_meowseo_title', $custom_title );
			}

			// Mock site name.
			add_filter(
				'option_blogname',
				function() use ( $site_name ) {
					return $site_name;
				}
			);

			// Resolve title.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_title( $post_id );

			// Property: Result is never empty.
			$this->assertNotEmpty( $result, 'Title should never be empty' );

			// Property: Follows fallback chain.
			if ( ! empty( $custom_title ) ) {
				$this->assertEquals( $custom_title, $result, 'Should use custom title when set' );
			} else {
				// Should contain post title or site name.
				$contains_post_title = ! empty( $post_title ) && strpos( $result, $post_title ) !== false;
				$contains_site_name  = ! empty( $site_name ) && strpos( $result, $site_name ) !== false;
				$this->assertTrue(
					$contains_post_title || $contains_site_name,
					'Should contain post title or site name in fallback'
				);
			}

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test title never empty even with empty inputs
	 *
	 * @return void
	 */
	public function test_title_never_empty_with_empty_inputs(): void {
		$this->forAll(
			Generator\constant( '' ),
			Generator\constant( '' )
		)->then( function( $post_title, $custom_title ) {
			// Create post with empty title.
			$post_id = $this->factory->post->create(
				array(
					'post_title' => $post_title,
				)
			);

			// Resolve title.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_title( $post_id );

			// Property: Result is never empty (should fall back to site name).
			$this->assertNotEmpty( $result, 'Title should never be empty even with empty inputs' );

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}
}
