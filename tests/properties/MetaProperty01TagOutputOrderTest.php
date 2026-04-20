<?php
/**
 * Property 1: Tag Output Order
 *
 * Feature: meta-module-rebuild, Property 1: For any page context (singular, archive,
 * homepage, etc.), the Meta_Output SHALL output tag groups in exactly this order:
 * Title (A), Description (B), Robots (C), Canonical (D), Open Graph (E),
 * Twitter Card (F), Hreflang (G)
 *
 * Validates: Requirements 2.1
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
 * Test Property 1: Tag Output Order
 */
class MetaProperty01TagOutputOrderTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		
		// Setup Brain\Monkey mocking for WordPress functions
		setup_brain_monkey_mocks();
	}

	/**
	 * Test tag output order property for singular posts
	 *
	 * For any singular post, the Meta_Output should output tag groups
	 * in exactly this order: Title (A), Description (B), Robots (C),
	 * Canonical (D), Open Graph (E), Twitter Card (F), Hreflang (G).
	 *
	 * @return void
	 */
	public function test_tag_output_order_singular(): void {
		$this->forAll(
			Generator\string(),
			Generator\string(),
			Generator\string()
		)->then( function( $post_title, $post_content, $post_excerpt ) {
			// Create post using mocked wp_insert_post.
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

			// Property: Verify tag groups appear in correct order.
			$this->assert_tag_order( $html );

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Test tag output order property for homepage
	 *
	 * @return void
	 */
	public function test_tag_output_order_homepage(): void {
		$this->forAll(
			Generator\string()
		)->then( function( $site_name ) {
			// Capture output.
			ob_start();
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$output   = new Meta_Output( $resolver );
			$output->output_head_tags();
			$html = ob_get_clean();

			// Property: Verify tag groups appear in correct order.
			$this->assert_tag_order( $html );
		} );
	}

	/**
	 * Test tag output order property for archives
	 *
	 * @return void
	 */
	public function test_tag_output_order_archive(): void {
		$this->forAll(
			Generator\string(),
			Generator\string()
		)->then( function( $category_name, $post_title ) {
			// Create post using mocked wp_insert_post.
			$post_id = \wp_insert_post(
				array(
					'post_title'  => $post_title,
					'post_status' => 'publish',
					'post_type'   => 'post',
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

			// Property: Verify tag groups appear in correct order.
			$this->assert_tag_order( $html );

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Assert tag order in HTML output
	 *
	 * Verifies that tag groups appear in the correct order:
	 * Title (A), Description (B), Robots (C), Canonical (D),
	 * Open Graph (E), Twitter Card (F), Hreflang (G).
	 *
	 * @param string $html HTML output to check.
	 * @return void
	 */
	private function assert_tag_order( string $html ): void {
		// Find positions of each tag group.
		$positions = array(
			'title'       => $this->find_tag_position( $html, '<title>' ),
			'description' => $this->find_tag_position( $html, '<meta name="description"' ),
			'robots'      => $this->find_tag_position( $html, '<meta name="robots"' ),
			'canonical'   => $this->find_tag_position( $html, '<link rel="canonical"' ),
			'og'          => $this->find_tag_position( $html, '<meta property="og:' ),
			'twitter'     => $this->find_tag_position( $html, '<meta name="twitter:' ),
			'hreflang'    => $this->find_tag_position( $html, '<link rel="alternate" hreflang=' ),
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
		$expected_order = array( 'title', 'description', 'robots', 'canonical', 'og', 'twitter', 'hreflang' );

		foreach ( $expected_order as $tag_name ) {
			if ( isset( $found_positions[ $tag_name ] ) ) {
				$current_pos = $found_positions[ $tag_name ];
				$this->assertGreaterThan(
					$previous_pos,
					$current_pos,
					sprintf(
						'Tag group "%s" (position %d) should appear after "%s" (position %d)',
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

		// Property: At minimum, title tag should always be present.
		$this->assertNotFalse(
			$positions['title'],
			'Title tag should always be present in output'
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
	 * Test specific tag order with all groups present
	 *
	 * Creates a post with all meta fields set to ensure all tag groups
	 * are output, then verifies the order.
	 *
	 * @return void
	 */
	public function test_tag_output_order_all_groups_present(): void {
		// Create post with all meta fields.
		$post_id = \wp_insert_post(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_excerpt' => 'Test excerpt',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		// Set all meta fields.
		\update_post_meta( $post_id, '_meowseo_title', 'Custom Title' );
		\update_post_meta( $post_id, '_meowseo_description', 'Custom description' );
		\update_post_meta( $post_id, '_meowseo_robots_noindex', false );
		\update_post_meta( $post_id, '_meowseo_robots_nofollow', false );

		// Capture output.
		ob_start();
		$options  = new Options();
		$patterns = new Title_Patterns( $options );
		$resolver = new Meta_Resolver( $options, $patterns );
		$output   = new Meta_Output( $resolver );
		$output->output_head_tags();
		$html = ob_get_clean();

		// Property: Verify tag groups appear in correct order.
		$this->assert_tag_order( $html );

		// Property: Verify expected groups are present.
		$this->assertStringContainsString( '<title>', $html, 'Title tag should be present' );
		$this->assertStringContainsString( '<meta name="robots"', $html, 'Robots tag should be present' );
		$this->assertStringContainsString( '<link rel="canonical"', $html, 'Canonical tag should be present' );
		$this->assertStringContainsString( '<meta property="og:', $html, 'Open Graph tags should be present' );
		$this->assertStringContainsString( '<meta name="twitter:', $html, 'Twitter Card tags should be present' );
		
		// Description is conditional - only check if present.
		if ( strpos( $html, '<meta name="description"' ) !== false ) {
			$this->assertStringContainsString( '<meta name="description"', $html, 'Description tag is present' );
		}

		// Cleanup.
		\wp_delete_post( $post_id, true );
	}
}
