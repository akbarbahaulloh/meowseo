<?php
/**
 * Property 11: Description Fallback Chain Completeness
 *
 * Feature: meta-module-rebuild, Property 11: For any singular post,
 * the description resolution SHALL follow the fallback chain.
 *
 * Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.6
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
 * Test Property 11: Description Fallback Chain Completeness
 */
class MetaProperty11DescriptionFallbackChainTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test description fallback chain completeness
	 *
	 * @return void
	 */
	public function test_description_fallback_chain_completeness(): void {
		$this->forAll(
			Generator\oneOf(
				Generator\constant( '' ),
				Generator\string()
			),
			Generator\oneOf(
				Generator\constant( '' ),
				Generator\string()
			),
			Generator\oneOf(
				Generator\constant( '' ),
				Generator\string()
			)
		)->then( function( $custom_description, $excerpt, $content ) {
			// Create post.
			$post_id = $this->factory->post->create(
				array(
					'post_excerpt' => $excerpt,
					'post_content' => $content,
				)
			);

			// Set custom description if provided.
			if ( ! empty( $custom_description ) ) {
				update_post_meta( $post_id, '_meowseo_description', $custom_description );
			}

			// Resolve description.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_description( $post_id );

			// Property: Follows fallback chain.
			if ( ! empty( $custom_description ) ) {
				$this->assertEquals( $custom_description, $result, 'Should use custom description when set' );
			} elseif ( ! empty( $excerpt ) ) {
				$this->assertStringContainsString(
					substr( strip_tags( $excerpt ), 0, 20 ),
					$result,
					'Should use excerpt when no custom description'
				);
			} elseif ( ! empty( $content ) ) {
				$this->assertStringContainsString(
					substr( strip_tags( $content ), 0, 20 ),
					$result,
					'Should use content when no excerpt'
				);
			} else {
				$this->assertEmpty( $result, 'Should return empty when no sources available' );
			}

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test description returns empty when all sources empty
	 *
	 * @return void
	 */
	public function test_description_empty_when_all_sources_empty(): void {
		$this->forAll(
			Generator\constant( '' ),
			Generator\constant( '' ),
			Generator\constant( '' )
		)->then( function( $custom_description, $excerpt, $content ) {
			// Create post with empty content.
			$post_id = $this->factory->post->create(
				array(
					'post_excerpt' => $excerpt,
					'post_content' => $content,
				)
			);

			// Resolve description.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_description( $post_id );

			// Property: Should return empty string when no sources available.
			$this->assertEmpty( $result, 'Description should be empty when all sources are empty' );

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}
}
