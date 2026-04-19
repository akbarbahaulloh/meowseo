<?php
/**
 * End-to-end integration tests for archive robots settings
 *
 * Tests the complete workflow from settings configuration to meta tag output
 * for all archive types with term-specific overrides.
 *
 * @package MeowSEO
 * @subpackage Tests\Integration
 */

use PHPUnit\Framework\TestCase;
use MeowSEO\Options;
use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Modules\Meta\Meta_Output;
use MeowSEO\Modules\Meta\Title_Patterns;

/**
 * Test archive robots settings end-to-end
 *
 * @group integration
 * @group archive-robots
 */
class ArchiveRobotsEndToEndTest extends TestCase {
	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Meta_Resolver instance
	 *
	 * @var Meta_Resolver
	 */
	private Meta_Resolver $resolver;

	/**
	 * Meta_Output instance
	 *
	 * @var Meta_Output
	 */
	private Meta_Output $output;

	/**
	 * Test author ID
	 *
	 * @var int
	 */
	private int $author_id = 1;

	/**
	 * Test category ID
	 *
	 * @var int
	 */
	private int $category_id = 1;

	/**
	 * Test tag ID
	 *
	 * @var int
	 */
	private int $tag_id = 1;

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private int $post_id = 1;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();

		// Initialize Options.
		$this->options = new Options();

		// Initialize Title_Patterns.
		$patterns = new Title_Patterns( $this->options );

		// Initialize Meta_Resolver.
		$this->resolver = new Meta_Resolver( $this->options, $patterns );

		// Initialize Meta_Output.
		$this->output = new Meta_Output( $this->resolver );

		// Reset global query.
		global $wp_query;
		$wp_query = new stdClass();
		$wp_query->is_author = false;
		$wp_query->is_date = false;
		$wp_query->is_category = false;
		$wp_query->is_tag = false;
		$wp_query->is_search = false;
		$wp_query->is_attachment = false;
		$wp_query->is_archive = false;
		$wp_query->is_singular = false;
		$wp_query->queried_object = null;
		$wp_query->queried_object_id = null;
		$wp_query->query_vars = array();

		// Initialize term meta storage.
		global $wp_termmeta_storage;
		if ( ! isset( $wp_termmeta_storage ) ) {
			$wp_termmeta_storage = array();
		}
	}

	/**
	 * Tear down test environment
	 */
	protected function tearDown(): void {
		// Reset global query.
		global $wp_query;
		$wp_query = new stdClass();

		// Clear term meta storage.
		global $wp_termmeta_storage;
		$wp_termmeta_storage = array();

		parent::tearDown();
	}

	/**
	 * Test author archive robots settings end-to-end
	 *
	 * Requirements: 4.1, 4.8
	 */
	public function test_author_archive_robots_end_to_end() {
		// Configure global robots settings for author archives.
		$this->options->set(
			'robots_author_archive',
			array(
				'noindex'  => true,
				'nofollow' => false,
			)
		);

		// Simulate author archive page.
		global $wp_query;
		$wp_query->is_author = true;
		$wp_query->is_archive = true;
		$wp_query->queried_object = (object) array(
			'ID' => $this->author_id,
			'display_name' => 'Test Author',
		);
		$wp_query->queried_object_id = $this->author_id;

		// Resolve robots meta tag.
		$robots = $this->resolver->resolve_robots_for_archive();

		// Verify robots meta tag output.
		$this->assertEquals( 'noindex, follow', $robots, 'Author archive should output noindex, follow' );
	}

	/**
	 * Test date archive robots settings end-to-end
	 *
	 * Requirements: 4.2, 4.9
	 */
	public function test_date_archive_robots_end_to_end() {
		// Configure global robots settings for date archives.
		$this->options->set(
			'robots_date_archive',
			array(
				'noindex'  => true,
				'nofollow' => true,
			)
		);

		// Simulate date archive page.
		global $wp_query;
		$wp_query->is_date = true;
		$wp_query->is_archive = true;

		// Resolve robots meta tag.
		$robots = $this->resolver->resolve_robots_for_archive();

		// Verify robots meta tag output.
		$this->assertEquals( 'noindex, nofollow', $robots, 'Date archive should output noindex, nofollow' );
	}

	/**
	 * Test category archive robots settings end-to-end
	 *
	 * Requirements: 4.3, 4.10
	 */
	public function test_category_archive_robots_end_to_end() {
		// Configure global robots settings for category archives.
		$this->options->set(
			'robots_category_archive',
			array(
				'noindex'  => false,
				'nofollow' => false,
			)
		);

		// Simulate category archive page.
		global $wp_query;
		$wp_query->is_category = true;
		$wp_query->is_archive = true;
		$wp_query->queried_object = (object) array(
			'term_id' => $this->category_id,
			'name' => 'Test Category',
			'taxonomy' => 'category',
		);
		$wp_query->queried_object_id = $this->category_id;

		// Resolve robots meta tag.
		$robots = $this->resolver->resolve_robots_for_archive();

		// Verify robots meta tag output.
		$this->assertEquals( 'index, follow', $robots, 'Category archive should output index, follow' );
	}

	/**
	 * Test tag archive robots settings end-to-end
	 *
	 * Requirements: 4.4, 4.11
	 */
	public function test_tag_archive_robots_end_to_end() {
		// Configure global robots settings for tag archives.
		$this->options->set(
			'robots_tag_archive',
			array(
				'noindex'  => false,
				'nofollow' => true,
			)
		);

		// Simulate tag archive page.
		global $wp_query;
		$wp_query->is_tag = true;
		$wp_query->is_archive = true;
		$wp_query->queried_object = (object) array(
			'term_id' => $this->tag_id,
			'name' => 'Test Tag',
			'taxonomy' => 'post_tag',
		);
		$wp_query->queried_object_id = $this->tag_id;

		// Resolve robots meta tag.
		$robots = $this->resolver->resolve_robots_for_archive();

		// Verify robots meta tag output.
		$this->assertEquals( 'index, nofollow', $robots, 'Tag archive should output index, nofollow' );
	}

	/**
	 * Test search results page robots settings end-to-end
	 *
	 * Requirements: 4.5, 4.12
	 */
	public function test_search_results_robots_end_to_end() {
		// Configure global robots settings for search results.
		$this->options->set(
			'robots_search_results',
			array(
				'noindex'  => true,
				'nofollow' => false,
			)
		);

		// Simulate search results page.
		global $wp_query;
		$wp_query->is_search = true;
		$wp_query->query_vars['s'] = 'test search';

		// Resolve robots meta tag.
		$robots = $this->resolver->resolve_robots_for_archive();

		// Verify robots meta tag output.
		$this->assertEquals( 'noindex, follow', $robots, 'Search results should output noindex, follow' );
	}

	/**
	 * Test attachment page robots settings end-to-end
	 *
	 * Requirements: 4.6, 4.13
	 */
	public function test_attachment_robots_end_to_end() {
		// Configure global robots settings for attachment pages.
		$this->options->set(
			'robots_attachment',
			array(
				'noindex'  => true,
				'nofollow' => true,
			)
		);

		// Simulate attachment page.
		global $wp_query;
		$wp_query->is_attachment = true;
		$wp_query->is_singular = true;
		$wp_query->queried_object = (object) array(
			'ID' => 999,
			'post_title' => 'Test Attachment',
			'post_mime_type' => 'image/jpeg',
		);
		$wp_query->queried_object_id = 999;

		// Resolve robots meta tag.
		$robots = $this->resolver->resolve_robots_for_archive();

		// Verify robots meta tag output.
		$this->assertEquals( 'noindex, nofollow', $robots, 'Attachment page should output noindex, nofollow' );
	}

	/**
	 * Test term-specific override takes precedence over global settings
	 *
	 * Requirements: 4.15, 4.16
	 */
	public function test_term_specific_override_takes_precedence() {
		// Configure global robots settings for category archives (index, follow).
		$this->options->set(
			'robots_category_archive',
			array(
				'noindex'  => false,
				'nofollow' => false,
			)
		);

		// Set term-specific override for this category (noindex, nofollow).
		global $wp_termmeta_storage;
		$wp_termmeta_storage[ $this->category_id ] = array(
			'_meowseo_robots_noindex' => array( true ),
			'_meowseo_robots_nofollow' => array( true ),
		);

		// Simulate category archive page.
		global $wp_query;
		$wp_query->is_category = true;
		$wp_query->is_archive = true;
		$wp_query->queried_object = (object) array(
			'term_id' => $this->category_id,
			'name' => 'Test Category',
			'taxonomy' => 'category',
		);
		$wp_query->queried_object_id = $this->category_id;

		// Resolve robots meta tag.
		$robots = $this->resolver->resolve_robots_for_archive();

		// Verify term-specific override takes precedence.
		$this->assertEquals( 'noindex, nofollow', $robots, 'Term-specific override should take precedence over global setting' );
	}

	/**
	 * Test term-specific override with partial settings
	 *
	 * Requirements: 4.15, 4.16
	 */
	public function test_term_specific_override_partial_settings() {
		// Configure global robots settings for tag archives (index, follow).
		$this->options->set(
			'robots_tag_archive',
			array(
				'noindex'  => false,
				'nofollow' => false,
			)
		);

		// Set only noindex override for this tag (leave nofollow as global).
		global $wp_termmeta_storage;
		$wp_termmeta_storage[ $this->tag_id ] = array(
			'_meowseo_robots_noindex' => array( true ),
		);

		// Simulate tag archive page.
		global $wp_query;
		$wp_query->is_tag = true;
		$wp_query->is_archive = true;
		$wp_query->queried_object = (object) array(
			'term_id' => $this->tag_id,
			'name' => 'Test Tag',
			'taxonomy' => 'post_tag',
		);
		$wp_query->queried_object_id = $this->tag_id;

		// Resolve robots meta tag.
		$robots = $this->resolver->resolve_robots_for_archive();

		// Verify term-specific override applies.
		$this->assertEquals( 'noindex, follow', $robots, 'Term-specific noindex should override global setting' );
	}

	/**
	 * Test all archive types with different configurations
	 *
	 * Requirements: 4.1-4.16
	 */
	public function test_all_archive_types_with_different_configurations() {
		// Configure different settings for each archive type.
		$archive_configs = array(
			'robots_author_archive'   => array( 'noindex' => true, 'nofollow' => false ),
			'robots_date_archive'     => array( 'noindex' => true, 'nofollow' => true ),
			'robots_category_archive' => array( 'noindex' => false, 'nofollow' => false ),
			'robots_tag_archive'      => array( 'noindex' => false, 'nofollow' => true ),
			'robots_search_results'   => array( 'noindex' => true, 'nofollow' => false ),
			'robots_attachment'       => array( 'noindex' => true, 'nofollow' => true ),
		);

		foreach ( $archive_configs as $key => $config ) {
			$this->options->set( $key, $config );
		}

		// Test each archive type.
		$test_cases = array(
			array(
				'type'     => 'author',
				'expected' => 'noindex, follow',
			),
			array(
				'type'     => 'date',
				'expected' => 'noindex, nofollow',
			),
			array(
				'type'     => 'category',
				'expected' => 'index, follow',
			),
			array(
				'type'     => 'tag',
				'expected' => 'index, nofollow',
			),
			array(
				'type'     => 'search',
				'expected' => 'noindex, follow',
			),
		);

		foreach ( $test_cases as $test_case ) {
			// Reset query.
			global $wp_query;
			$wp_query->is_author = false;
			$wp_query->is_date = false;
			$wp_query->is_category = false;
			$wp_query->is_tag = false;
			$wp_query->is_search = false;
			$wp_query->is_archive = false;

			// Set up archive type.
			if ( $test_case['type'] === 'author' ) {
				$wp_query->is_author = true;
				$wp_query->is_archive = true;
				$wp_query->queried_object = (object) array( 'ID' => $this->author_id );
				$wp_query->queried_object_id = $this->author_id;
			} elseif ( $test_case['type'] === 'date' ) {
				$wp_query->is_date = true;
				$wp_query->is_archive = true;
			} elseif ( $test_case['type'] === 'category' ) {
				$wp_query->is_category = true;
				$wp_query->is_archive = true;
				$wp_query->queried_object = (object) array( 'term_id' => $this->category_id );
				$wp_query->queried_object_id = $this->category_id;
			} elseif ( $test_case['type'] === 'tag' ) {
				$wp_query->is_tag = true;
				$wp_query->is_archive = true;
				$wp_query->queried_object = (object) array( 'term_id' => $this->tag_id );
				$wp_query->queried_object_id = $this->tag_id;
			} elseif ( $test_case['type'] === 'search' ) {
				$wp_query->is_search = true;
				$wp_query->query_vars['s'] = 'test';
			}

			// Resolve robots.
			$robots = $this->resolver->resolve_robots_for_archive();

			// Verify output.
			$this->assertEquals(
				$test_case['expected'],
				$robots,
				sprintf( '%s archive should output %s', ucfirst( $test_case['type'] ), $test_case['expected'] )
			);
		}
	}

	/**
	 * Test meta output integration with archive robots
	 *
	 * Requirements: 4.8, 4.9, 4.10, 4.11, 4.12, 4.13
	 */
	public function test_meta_output_integration_with_archive_robots() {
		// Configure robots settings.
		$this->options->set(
			'robots_category_archive',
			array(
				'noindex'  => true,
				'nofollow' => false,
			)
		);

		// Simulate category archive page.
		global $wp_query;
		$wp_query->is_category = true;
		$wp_query->is_archive = true;
		$wp_query->queried_object = (object) array(
			'term_id' => $this->category_id,
			'name' => 'Test Category',
			'taxonomy' => 'category',
		);
		$wp_query->queried_object_id = $this->category_id;

		// Capture output.
		ob_start();
		$this->output->output_head_tags();
		$output = ob_get_clean();

		// Verify robots meta tag is present in output.
		$this->assertStringContainsString(
			'<meta name="robots" content="noindex, follow">',
			$output,
			'Meta output should include robots meta tag for category archive'
		);
	}
}
