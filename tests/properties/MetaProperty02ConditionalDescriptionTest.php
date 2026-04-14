<?php
/**
 * Property 2: Conditional Description Output
 *
 * Feature: meta-module-rebuild, Property 2: For any post, if the resolved description
 * value is empty, no meta description tag SHALL be output; if the description is
 * non-empty, the meta description tag SHALL be present in the output.
 *
 * Validates: Requirements 2.3
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
 * Test Property 2: Conditional Description Output
 *
 * Note: This test validates the resolver behavior. Full integration testing
 * with Meta_Output would require WordPress test framework with proper query context.
 */
class MetaProperty02ConditionalDescriptionTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test conditional description resolution with empty description
	 *
	 * When all description sources are empty, resolver should return empty string.
	 *
	 * @return void
	 */
	public function test_empty_description_when_no_sources(): void {
		$this->forAll(
			Generator\string()
		)->then( function( $post_title ) {
			// Create post with no content or excerpt (empty description sources).
			$post_id = \wp_insert_post(
				array(
					'post_title'   => $post_title,
					'post_content' => '',
					'post_excerpt' => '',
					'post_status'  => 'publish',
					'post_type'    => 'post',
				)
			);

			// Do NOT set custom description meta.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			
			// Get description directly from resolver.
			$resolved_description = $resolver->resolve_description( $post_id );

			// Property: Description should be empty when no sources are available.
			$this->assertEmpty(
				$resolved_description,
				'Resolved description should be empty when no sources are available'
			);

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test conditional description resolution with non-empty custom description
	 *
	 * When custom description is set, resolver should return it.
	 *
	 * @return void
	 */
	public function test_non_empty_description_with_custom_meta(): void {
		$this->forAll(
			Generator\string(),
			Generator\string()
		)->then( function( $post_title, $description ) {
			// Skip empty descriptions.
			if ( empty( $description ) ) {
				return;
			}

			// Create post.
			$post_id = \wp_insert_post(
				array(
					'post_title'  => $post_title,
					'post_status' => 'publish',
					'post_type'   => 'post',
				)
			);

			// Set custom description meta.
			\update_post_meta( $post_id, '_meowseo_description', $description );

			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			
			// Get description directly from resolver.
			$resolved_description = $resolver->resolve_description( $post_id );

			// Property: Description should be non-empty when custom description is set.
			$this->assertNotEmpty(
				$resolved_description,
				'Resolved description should be non-empty when custom description is set'
			);

			// Property: Description should match the set value.
			$this->assertEquals(
				$description,
				$resolved_description,
				'Resolved description should match the set value'
			);

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test conditional description with excerpt fallback
	 *
	 * When custom description is empty but excerpt exists, resolver should use excerpt.
	 *
	 * @return void
	 */
	public function test_non_empty_description_with_excerpt_fallback(): void {
		$this->forAll(
			Generator\string(),
			Generator\string()
		)->then( function( $post_title, $excerpt ) {
			// Skip empty excerpts.
			if ( empty( $excerpt ) ) {
				return;
			}

			// Create post with excerpt.
			$post_id = \wp_insert_post(
				array(
					'post_title'   => $post_title,
					'post_excerpt' => $excerpt,
					'post_status'  => 'publish',
					'post_type'    => 'post',
				)
			);

			// Do NOT set custom description meta (should fall back to excerpt).
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			
			// Get description directly from resolver.
			$resolved_description = $resolver->resolve_description( $post_id );

			// Property: Description should be non-empty when excerpt exists.
			$this->assertNotEmpty(
				$resolved_description,
				'Resolved description should be non-empty when excerpt is available'
			);

			// Property: Description length should be <= 160 chars (truncation requirement).
			$this->assertLessThanOrEqual(
				160,
				strlen( $resolved_description ),
				'Resolved description should be truncated to 160 characters'
			);

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test conditional description with content fallback
	 *
	 * When custom description and excerpt are empty but content exists,
	 * resolver should use content.
	 *
	 * @return void
	 */
	public function test_non_empty_description_with_content_fallback(): void {
		$this->forAll(
			Generator\string(),
			Generator\string()
		)->then( function( $post_title, $content ) {
			// Skip empty content and content that becomes empty after stripping.
			$stripped_content = \wp_strip_all_tags( $content );
			if ( empty( $content ) || empty( $stripped_content ) ) {
				return;
			}

			// Create post with content but no excerpt.
			$post_id = \wp_insert_post(
				array(
					'post_title'   => $post_title,
					'post_content' => $content,
					'post_excerpt' => '',
					'post_status'  => 'publish',
					'post_type'    => 'post',
				)
			);

			// Do NOT set custom description meta (should fall back to content).
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			
			// Get description directly from resolver.
			$resolved_description = $resolver->resolve_description( $post_id );

			// Property: Description should be non-empty when content exists.
			$this->assertNotEmpty(
				$resolved_description,
				'Resolved description should be non-empty when content is available'
			);

			// Property: Description length should be <= 160 chars (truncation requirement).
			$this->assertLessThanOrEqual(
				160,
				strlen( $resolved_description ),
				'Resolved description should be truncated to 160 characters'
			);

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}
}

