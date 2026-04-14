<?php
/**
 * Property 6: Open Graph Tag Order
 *
 * Feature: meta-module-rebuild, Property 6: For any post with Open Graph data,
 * the OG tags SHALL appear in exactly this order: og:type, og:title, og:description,
 * og:url, og:image (with og:image:width and og:image:height), og:site_name,
 * article:published_time, article:modified_time.
 *
 * Validates: Requirements 2.6
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use WP_UnitTestCase;
use MeowSEO\Modules\Meta\Meta_Output;
use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Modules\Meta\Title_Patterns;
use MeowSEO\Options;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Test Property 6: Open Graph Tag Order
 */
class MetaProperty06OpenGraphTagOrderTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test Open Graph tag order
	 *
	 * For any post, OG tags should appear in the specified order.
	 *
	 * @return void
	 */
	public function test_open_graph_tag_order(): void {
		$this->forAll(
			Generator\string(),
			Generator\string(),
			Generator\string()
		)->then( function( $post_title, $post_content, $post_excerpt ) {
			// Create post.
			$post_id = \wp_insert_post(
				array(
					'post_title'   => $post_title,
					'post_content' => $post_content,
					'post_excerpt' => $post_excerpt,
					'post_status'  => 'publish',
					'post_type'    => 'post',
				)
			);

			// Capture output.
			ob_start();
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$output   = new Meta_Output( $resolver );
			$output->output_head_tags();
			$html = ob_get_clean();

			// Property: Verify OG tags appear in correct order.
			$this->assert_og_tag_order( $html );

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Assert Open Graph tag order in HTML output
	 *
	 * Verifies that OG tags appear in the correct order:
	 * og:type, og:title, og:description, og:url, og:image,
	 * og:site_name, article:published_time, article:modified_time.
	 *
	 * @param string $html HTML output to check.
	 * @return void
	 */
	private function assert_og_tag_order( string $html ): void {
		// Find positions of each OG tag.
		$positions = array(
			'og:type'                 => $this->find_tag_position( $html, '<meta property="og:type"' ),
			'og:title'                => $this->find_tag_position( $html, '<meta property="og:title"' ),
			'og:description'          => $this->find_tag_position( $html, '<meta property="og:description"' ),
			'og:url'                  => $this->find_tag_position( $html, '<meta property="og:url"' ),
			'og:image'                => $this->find_tag_position( $html, '<meta property="og:image"' ),
			'og:site_name'            => $this->find_tag_position( $html, '<meta property="og:site_name"' ),
			'article:published_time'  => $this->find_tag_position( $html, '<meta property="article:published_time"' ),
			'article:modified_time'   => $this->find_tag_position( $html, '<meta property="article:modified_time"' ),
		);

		// Filter out tags that weren't found (some are conditional).
		$found_positions = array_filter(
			$positions,
			function( $pos ) {
				return $pos !== false;
			}
		);

		// Property: Tags that are present must be in correct order.
		$previous_pos  = -1;
		$previous_name = '';
		$expected_order = array(
			'og:type',
			'og:title',
			'og:description',
			'og:url',
			'og:image',
			'og:site_name',
			'article:published_time',
			'article:modified_time',
		);

		foreach ( $expected_order as $tag_name ) {
			if ( isset( $found_positions[ $tag_name ] ) ) {
				$current_pos = $found_positions[ $tag_name ];
				$this->assertGreaterThan(
					$previous_pos,
					$current_pos,
					sprintf(
						'OG tag "%s" (position %d) should appear after "%s" (position %d)',
						$tag_name,
						$current_pos,
						$previous_name,
						$previous_pos
					)
				);
				$previous_pos  = $current_pos;
				$previous_name = $tag_name;
			}
		}

		// Property: At minimum, og:type should always be present.
		$this->assertNotFalse(
			$positions['og:type'],
			'og:type tag should always be present in Open Graph output'
		);
	}

	/**
	 * Find position of first occurrence of tag in HTML
	 *
	 * @param string $html HTML to search.
	 * @param string $tag  Tag to find.
	 * @return int|false Position of tag, or false if not found.
	 */
	private function find_tag_position( string $html, string $tag ) {
		return strpos( $html, $tag );
	}

	/**
	 * Test Open Graph tag order with all tags present
	 *
	 * Creates a post with dates to ensure article:published_time and
	 * article:modified_time are output.
	 *
	 * @return void
	 */
	public function test_open_graph_tag_order_all_tags(): void {
		// Create post with dates.
		$post_id = \wp_insert_post(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_excerpt' => 'Test excerpt',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		// Capture output.
		ob_start();
		$options  = new Options();
		$patterns = new Title_Patterns( $options );
		$resolver = new Meta_Resolver( $options, $patterns );
		$output   = new Meta_Output( $resolver );
		$output->output_head_tags();
		$html = ob_get_clean();

		// Property: Verify OG tags appear in correct order.
		$this->assert_og_tag_order( $html );

		// Property: Verify expected OG tags are present.
		$this->assertStringContainsString( '<meta property="og:type"', $html, 'og:type should be present' );
		$this->assertStringContainsString( '<meta property="og:title"', $html, 'og:title should be present' );
		$this->assertStringContainsString( '<meta property="og:url"', $html, 'og:url should be present' );
		$this->assertStringContainsString( '<meta property="og:site_name"', $html, 'og:site_name should be present' );

		// Cleanup.
		\wp_delete_post( $post_id, true );
	}
}
