<?php
/**
 * Property 12: Description Truncation with HTML Stripping
 *
 * Feature: meta-module-rebuild, Property 12: For any text being truncated
 * to 160 characters for meta description, all HTML tags and shortcodes
 * SHALL be stripped before measuring length.
 *
 * Validates: Requirements 4.5
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
 * Test Property 12: Description Truncation with HTML Stripping
 */
class MetaProperty12DescriptionTruncationTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test description truncation strips HTML before measuring length
	 *
	 * @return void
	 */
	public function test_description_truncation_strips_html(): void {
		$this->forAll(
			Generator\string()
		)->then( function( $text ) {
			// Create content with HTML tags.
			$html_content = '<p><strong>' . $text . '</strong></p><div>' . $text . '</div>';

			// Create post.
			$post_id = $this->factory->post->create(
				array(
					'post_content' => $html_content,
				)
			);

			// Resolve description.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_description( $post_id );

			// Property: Result should not contain HTML tags.
			$this->assertStringNotContainsString( '<p>', $result, 'Should not contain <p> tags' );
			$this->assertStringNotContainsString( '<strong>', $result, 'Should not contain <strong> tags' );
			$this->assertStringNotContainsString( '<div>', $result, 'Should not contain <div> tags' );
			$this->assertStringNotContainsString( '</p>', $result, 'Should not contain closing tags' );

			// Property: Result should be <= 163 chars (160 + "...").
			$this->assertLessThanOrEqual( 163, mb_strlen( $result ), 'Should be truncated to max 160 chars + "..."' );

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test description truncation strips shortcodes
	 *
	 * @return void
	 */
	public function test_description_truncation_strips_shortcodes(): void {
		$this->forAll(
			Generator\string()
		)->then( function( $text ) {
			// Create content with shortcodes.
			$content_with_shortcodes = '[gallery id="123"] ' . $text . ' [caption]Test[/caption]';

			// Create post.
			$post_id = $this->factory->post->create(
				array(
					'post_content' => $content_with_shortcodes,
				)
			);

			// Resolve description.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$result   = $resolver->resolve_description( $post_id );

			// Property: Result should not contain shortcode brackets.
			$this->assertStringNotContainsString( '[gallery', $result, 'Should not contain [gallery shortcode' );
			$this->assertStringNotContainsString( '[caption]', $result, 'Should not contain [caption] shortcode' );

			// Cleanup.
			wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test long text is truncated to 160 characters
	 *
	 * @return void
	 */
	public function test_long_text_truncated_to_160_chars(): void {
		// Create very long text (500 chars).
		$long_text = str_repeat( 'a', 500 );

		// Create post.
		$post_id = $this->factory->post->create(
			array(
				'post_content' => $long_text,
			)
		);

		// Resolve description.
		$options  = new Options();
		$patterns = new Title_Patterns( $options );
		$resolver = new Meta_Resolver( $options, $patterns );
		$result   = $resolver->resolve_description( $post_id );

		// Property: Result should be <= 163 chars (160 + "...").
		$this->assertLessThanOrEqual( 163, mb_strlen( $result ), 'Should be truncated to max 160 chars + "..."' );
		$this->assertGreaterThan( 0, mb_strlen( $result ), 'Should not be empty' );

		// Cleanup.
		wp_delete_post( $post_id, true );
	}
}
