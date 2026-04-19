<?php
/**
 * End-to-End Test for Archive Title Patterns
 *
 * @package MeowSEO
 * @subpackage Tests\E2E
 */

namespace MeowSEO\Tests\E2E;

use WP_UnitTestCase;
use MeowSEO\Options;
use MeowSEO\Modules\Meta\Title_Patterns;
use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Modules\Meta\Meta_Output;
use WP_Query;

/**
 * Test archive title patterns end-to-end
 *
 * Validates Requirements 5.1-5.35
 */
class ArchiveTitlePatternsE2ETest extends WP_UnitTestCase {
	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Title_Patterns instance
	 *
	 * @var Title_Patterns
	 */
	private Title_Patterns $patterns;

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
	 * Test posts created
	 *
	 * @var array
	 */
	private array $test_posts = array();

	/**
	 * Test terms created
	 *
	 * @var array
	 */
	private array $test_terms = array();

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Initialize components.
		$this->options  = new Options();
		$this->patterns = new Title_Patterns( $this->options );
		$this->resolver = new Meta_Resolver( $this->options, $this->patterns );
		$this->output   = new Meta_Output( $this->resolver );

		// Configure title patterns for all archive types.
		$this->configure_title_patterns();
	}

	/**
	 * Configure title patterns for testing
	 */
	private function configure_title_patterns(): void {
		$patterns = array(
			'category_archive'       => '{category} Archives {sep} {site_name}',
			'tag_archive'            => '{tag} Tag {sep} {site_name}',
			'custom_taxonomy_archive' => '{term} {sep} {site_name}',
			'author_page'            => '{name} {sep} {site_name}',
			'search_results'         => 'Search Results for {searchphrase} {sep} {site_name}',
			'date_archive'           => '{date} Archives {sep} {site_name}',
			'404_page'               => 'Page Not Found {sep} {site_name}',
			'homepage'               => '{site_name} {sep} {tagline}',
		);

		$this->options->set( 'title_patterns', $patterns );
		$this->options->set( 'separator', '|' );
	}

	/**
	 * Test category archive title with variable substitution
	 *
	 * Validates Requirements 5.1, 5.19, 5.28, 5.35
	 */
	public function test_category_archive_title(): void {
		// Create a category.
		$result = \wp_insert_term( 'Technology', 'category' );
		if ( \is_wp_error( $result ) ) {
			$this->markTestSkipped( 'Could not create category' );
		}
		$category_id = $result['term_id'];
		$this->test_terms[] = $category_id;

		// Create a post in the category.
		$post_id = \wp_insert_post( array(
			'post_title'    => 'Test Post',
			'post_status'   => 'publish',
			'post_category' => array( $category_id ),
		) );
		$this->test_posts[] = $post_id;

		// Get the pattern for category archives.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'category_archive' );
		$this->assertEquals( '{category} Archives {sep} {site_name}', $pattern, 'Category pattern should be configured' );

		// Build context manually.
		$term = \get_term( $category_id, 'category' );
		$context = array(
			'category' => $term->name,
			'term'     => $term->name,
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "Technology Archives | Site Name"
		$expected = 'Technology Archives | ' . \get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, 'Category archive title should match pattern with variable substitution' );
	}

	/**
	 * Test tag archive title with variable substitution
	 *
	 * Validates Requirements 5.3, 5.20, 5.29, 5.35
	 */
	public function test_tag_archive_title(): void {
		// Create a tag.
		$result = \wp_insert_term( 'WordPress', 'post_tag' );
		if ( \is_wp_error( $result ) ) {
			$this->markTestSkipped( 'Could not create tag' );
		}
		$tag_id = $result['term_id'];
		$this->test_terms[] = $tag_id;

		// Get the pattern for tag archives.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'tag_archive' );
		$this->assertEquals( '{tag} Tag {sep} {site_name}', $pattern, 'Tag pattern should be configured' );

		// Build context manually.
		$term = \get_term( $tag_id, 'post_tag' );
		$context = array(
			'tag'  => $term->name,
			'term' => $term->name,
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "WordPress Tag | Site Name"
		$expected = 'WordPress Tag | ' . \get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, 'Tag archive title should match pattern with variable substitution' );
	}

	/**
	 * Test author page title with variable substitution
	 *
	 * Validates Requirements 5.7, 5.23, 5.30, 5.35
	 */
	public function test_author_page_title(): void {
		// Create a user.
		$user_id = \wp_insert_user( array(
			'user_login'   => 'johndoe_' . time(),
			'user_pass'    => 'password',
			'display_name' => 'John Doe',
		) );

		if ( \is_wp_error( $user_id ) ) {
			$this->markTestSkipped( 'Could not create user' );
		}

		// Get the pattern for author pages.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'author_page' );
		$this->assertEquals( '{name} {sep} {site_name}', $pattern, 'Author pattern should be configured' );

		// Build context manually.
		$user = \get_userdata( $user_id );
		$context = array(
			'name' => $user->display_name,
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "John Doe | Site Name"
		$expected = 'John Doe | ' . \get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, 'Author page title should match pattern with variable substitution' );

		// Clean up.
		\wp_delete_user( $user_id );
	}

	/**
	 * Test search results page title with variable substitution
	 *
	 * Validates Requirements 5.9, 5.24, 5.31, 5.35
	 */
	public function test_search_results_title(): void {
		// Get the pattern for search results.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'search_results' );
		$this->assertEquals( 'Search Results for {searchphrase} {sep} {site_name}', $pattern, 'Search pattern should be configured' );

		// Build context manually.
		$context = array(
			'searchphrase' => 'WordPress',
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "Search Results for WordPress | Site Name"
		$expected = 'Search Results for WordPress | ' . get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, 'Search results title should match pattern with variable substitution' );
	}

	/**
	 * Test date archive title with variable substitution
	 *
	 * Validates Requirements 5.11, 5.22, 5.32, 5.35
	 */
	public function test_date_archive_title(): void {
		// Get the pattern for date archives.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'date_archive' );
		$this->assertEquals( '{date} Archives {sep} {site_name}', $pattern, 'Date pattern should be configured' );

		// Build context manually.
		$context = array(
			'date' => 'January 2024',
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "January 2024 Archives | Site Name"
		$expected = 'January 2024 Archives | ' . get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, 'Date archive title should match pattern with variable substitution' );
	}

	/**
	 * Test 404 page title
	 *
	 * Validates Requirements 5.13, 5.33
	 */
	public function test_404_page_title(): void {
		// Get the pattern for 404 pages.
		$pattern = $this->patterns->get_pattern_for_archive_type( '404_page' );
		$this->assertEquals( 'Page Not Found {sep} {site_name}', $pattern, '404 pattern should be configured' );

		// Build context manually (no variables needed for 404).
		$context = array();

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "Page Not Found | Site Name"
		$expected = 'Page Not Found | ' . get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, '404 page title should match pattern' );
	}

	/**
	 * Test pagination variable on paginated archives
	 *
	 * Validates Requirement 5.27
	 */
	public function test_pagination_variable(): void {
		// Update pattern to include page variable.
		$patterns = $this->options->get( 'title_patterns', array() );
		$patterns['category_archive'] = '{category} Archives {page} {sep} {site_name}';
		$this->options->set( 'title_patterns', $patterns );

		// Get the pattern.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'category_archive' );

		// Build context with pagination.
		$context = array(
			'category'    => 'News',
			'term'        => 'News',
			'page_number' => 2,
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "News Archives Page 2 | Site Name"
		$expected = 'News Archives Page 2 | ' . get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, 'Paginated archive title should include page number' );
	}

	/**
	 * Test all supported variables are replaced
	 *
	 * Validates Requirements 5.17-5.27
	 */
	public function test_all_variables_replaced(): void {
		// Test separator variable.
		$this->options->set( 'separator', '-' );
		$patterns = $this->options->get( 'title_patterns', array() );
		$patterns['category_archive'] = '{category} {sep} Test';
		$this->options->set( 'title_patterns', $patterns );

		// Get the pattern.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'category_archive' );

		// Build context.
		$context = array(
			'category' => 'Tech',
			'term'     => 'Tech',
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Verify variables are replaced.
		$this->assertStringContainsString( '-', $title, 'Separator variable should be replaced' );
		$this->assertStringContainsString( 'Tech', $title, 'Category variable should be replaced' );
		$this->assertStringContainsString( 'Test', $title, 'Literal text should be present' );
	}

	/**
	 * Test title output structure
	 *
	 * Validates that titles are properly formatted
	 */
	public function test_title_output_structure(): void {
		// Create a category.
		$result = \wp_insert_term( 'Science', 'category' );
		if ( \is_wp_error( $result ) ) {
			$this->markTestSkipped( 'Could not create category' );
		}
		$category_id = $result['term_id'];
		$this->test_terms[] = $category_id;

		// Get the pattern.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'category_archive' );

		// Build context.
		$term = \get_term( $category_id, 'category' );
		$context = array(
			'category' => $term->name,
			'term'     => $term->name,
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Verify title structure.
		$this->assertStringContainsString( 'Science Archives', $title, 'Title should contain category name' );
		$this->assertStringContainsString( \get_bloginfo( 'name' ), $title, 'Title should contain site name' );
		$this->assertStringContainsString( '|', $title, 'Title should contain separator' );
	}

	/**
	 * Test custom taxonomy archive title
	 *
	 * Validates Requirements 5.5, 5.21
	 */
	public function test_custom_taxonomy_archive_title(): void {
		// Get the pattern for custom taxonomy archives.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'custom_taxonomy_archive' );
		$this->assertEquals( '{term} {sep} {site_name}', $pattern, 'Custom taxonomy pattern should be configured' );

		// Build context.
		$context = array(
			'term' => 'Fiction',
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "Fiction | Site Name"
		$expected = 'Fiction | ' . get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, 'Custom taxonomy archive title should match pattern' );
	}

	/**
	 * Test year-only date archive
	 *
	 * Validates date formatting for year archives
	 */
	public function test_year_date_archive_title(): void {
		// Get the pattern.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'date_archive' );

		// Build context with year only.
		$context = array(
			'date' => '2024',
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "2024 Archives | Site Name"
		$expected = '2024 Archives | ' . get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, 'Year archive title should show year only' );
	}

	/**
	 * Test day date archive
	 *
	 * Validates date formatting for day archives
	 */
	public function test_day_date_archive_title(): void {
		// Get the pattern.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'date_archive' );

		// Build context with full date.
		$context = array(
			'date' => 'January 15, 2024',
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "January 15, 2024 Archives | Site Name"
		$expected = 'January 15, 2024 Archives | ' . get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, 'Day archive title should show full date' );
	}

	/**
	 * Test empty search query handling
	 *
	 * Validates that empty search queries are handled gracefully
	 */
	public function test_empty_search_query(): void {
		// Get the pattern.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'search_results' );

		// Build context with empty search phrase.
		$context = array(
			'searchphrase' => '',
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Verify title structure.
		$this->assertStringContainsString( 'Search Results for', $title, 'Search title should be present' );
		$this->assertStringContainsString( get_bloginfo( 'name' ), $title, 'Site name should be present' );
	}

	/**
	 * Test pattern with multiple variables
	 *
	 * Validates that patterns with multiple variables work correctly
	 */
	public function test_pattern_with_multiple_variables(): void {
		// Update pattern to use multiple variables.
		$patterns = $this->options->get( 'title_patterns', array() );
		$patterns['category_archive'] = '{category} {sep} {site_name} {sep} Category Archive';
		$this->options->set( 'title_patterns', $patterns );

		// Get the pattern.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'category_archive' );

		// Build context.
		$context = array(
			'category' => 'Sports',
			'term'     => 'Sports',
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Verify all variables are replaced.
		$this->assertStringContainsString( 'Sports', $title, 'Category name should be present' );
		$this->assertStringContainsString( '|', $title, 'Separator should be present' );
		$this->assertStringContainsString( get_bloginfo( 'name' ), $title, 'Site name should be present' );
		$this->assertStringContainsString( 'Category Archive', $title, 'Literal text should be present' );
	}

	/**
	 * Test homepage title pattern
	 *
	 * Validates Requirements 5.15, 5.34
	 */
	public function test_homepage_title(): void {
		// Get the pattern for homepage.
		$pattern = $this->patterns->get_pattern_for_page_type( 'homepage' );
		$this->assertEquals( '{site_name} {sep} {tagline}', $pattern, 'Homepage pattern should be configured' );

		// Build context (no variables needed, they're auto-resolved).
		$context = array();

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "Site Name | Tagline"
		$site_name = get_bloginfo( 'name' );
		$tagline = get_bloginfo( 'description' );
		$expected = $site_name . ' | ' . $tagline;
		$this->assertEquals( $expected, $title, 'Homepage title should match pattern' );
	}

	/**
	 * Test post type variable
	 *
	 * Validates Requirement 5.25
	 */
	public function test_posttype_variable(): void {
		// Create a custom pattern with posttype variable.
		$patterns = $this->options->get( 'title_patterns', array() );
		$patterns['custom_taxonomy_archive'] = '{posttype} Archive {sep} {site_name}';
		$this->options->set( 'title_patterns', $patterns );

		// Get the pattern.
		$pattern = $this->patterns->get_pattern_for_archive_type( 'custom_taxonomy_archive' );

		// Build context.
		$context = array(
			'posttype' => 'Products',
		);

		// Resolve pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Expected: "Products Archive | Site Name"
		$expected = 'Products Archive | ' . get_bloginfo( 'name' );
		$this->assertEquals( $expected, $title, 'Post type variable should be replaced' );
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		// Delete test posts.
		foreach ( $this->test_posts as $post_id ) {
			\wp_delete_post( $post_id, true );
		}

		// Delete test terms.
		foreach ( $this->test_terms as $term_id ) {
			\wp_delete_term( $term_id, 'category' );
		}

		parent::tearDown();
	}
}
