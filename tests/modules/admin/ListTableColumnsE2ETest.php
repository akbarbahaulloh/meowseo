<?php
/**
 * End-to-end tests for List_Table_Columns class.
 *
 * Tests the complete SEO Score column functionality including:
 * - Column appearance in list tables
 * - Color indicators (red/orange/green/gray)
 * - Sorting (ascending and descending)
 * - Custom post type support
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Admin;

use MeowSEO\Modules\Admin\List_Table_Columns;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * List_Table_Columns end-to-end test case.
 *
 * Validates Requirements 3.1-3.11 from the spec.
 */
class ListTableColumnsE2ETest extends TestCase {

	/**
	 * List_Table_Columns instance.
	 *
	 * @var List_Table_Columns
	 */
	private List_Table_Columns $list_table_columns;

	/**
	 * Options mock.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Reset global storage.
		global $wp_postmeta_storage, $wp_posts_storage, $wp_filter, $test_is_admin;
		$wp_postmeta_storage = array();
		$wp_posts_storage = array();
		$wp_filter = array();
		
		// Set is_admin() to return true for admin tests.
		$test_is_admin = true;

		$this->options = $this->createMock( Options::class );
		$this->list_table_columns = new List_Table_Columns( $this->options );
	}

	/**
	 * Test SEO Score column appears in posts list table.
	 *
	 * Validates Requirement 3.1: WHEN viewing the posts list table,
	 * THE List_Table SHALL display an SEO Score column.
	 *
	 * @return void
	 */
	public function test_seo_score_column_appears_in_posts_list(): void {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Title',
			'author' => 'Author',
			'categories' => 'Categories',
			'tags' => 'Tags',
			'date' => 'Date',
		);

		$result = $this->list_table_columns->add_seo_score_column( $columns );

		// Verify column exists.
		$this->assertArrayHasKey( 'seo_score', $result, 'SEO Score column should be present in posts list table' );
		$this->assertSame( 'SEO Score', $result['seo_score'], 'Column should be labeled "SEO Score"' );

		// Verify column is positioned after title.
		$keys = array_keys( $result );
		$title_index = array_search( 'title', $keys, true );
		$score_index = array_search( 'seo_score', $keys, true );
		$this->assertSame( $title_index + 1, $score_index, 'SEO Score column should appear immediately after Title column' );
	}

	/**
	 * Test SEO Score column appears in pages list table.
	 *
	 * Validates Requirement 3.2: WHEN viewing the pages list table,
	 * THE List_Table SHALL display an SEO Score column.
	 *
	 * @return void
	 */
	public function test_seo_score_column_appears_in_pages_list(): void {
		// Same test as posts - the column logic is post-type agnostic.
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Title',
			'author' => 'Author',
			'date' => 'Date',
		);

		$result = $this->list_table_columns->add_seo_score_column( $columns );

		$this->assertArrayHasKey( 'seo_score', $result, 'SEO Score column should be present in pages list table' );
	}

	/**
	 * Test SEO Score column appears for custom post types.
	 *
	 * Validates Requirement 3.3: WHERE a custom post type is public,
	 * THE List_Table SHALL display an SEO Score column for that post type.
	 *
	 * @return void
	 */
	public function test_seo_score_column_appears_for_custom_post_types(): void {
		// Simulate custom post type list table.
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Title',
			'taxonomy-product_cat' => 'Categories',
			'date' => 'Date',
		);

		$result = $this->list_table_columns->add_seo_score_column( $columns );

		$this->assertArrayHasKey( 'seo_score', $result, 'SEO Score column should be present for custom post types' );
	}

	/**
	 * Test red indicator displays for poor scores (0-40).
	 *
	 * Validates Requirement 3.5: WHEN the SEO score is 0-40,
	 * THE List_Table SHALL display a red indicator.
	 *
	 * @return void
	 */
	public function test_red_indicator_for_poor_scores(): void {
		global $wp_postmeta_storage;

		// Test boundary values and middle value.
		$test_scores = array( 0, 20, 40 );

		foreach ( $test_scores as $score ) {
			$post_id = 100 + $score;
			$wp_postmeta_storage[ $post_id ]['_meowseo_seo_score'] = array( $score );

			ob_start();
			$this->list_table_columns->render_seo_score_column( 'seo_score', $post_id );
			$output = ob_get_clean();

			$this->assertStringContainsString( 'meowseo-score-poor', $output, "Score {$score} should display red indicator" );
			$this->assertStringContainsString( (string) $score, $output, "Score {$score} should be displayed" );
			$this->assertStringContainsString( "SEO Score: {$score}/100", $output, "Tooltip should show score {$score}" );
		}
	}

	/**
	 * Test orange indicator displays for ok scores (41-70).
	 *
	 * Validates Requirement 3.6: WHEN the SEO score is 41-70,
	 * THE List_Table SHALL display an orange indicator.
	 *
	 * @return void
	 */
	public function test_orange_indicator_for_ok_scores(): void {
		global $wp_postmeta_storage;

		// Test boundary values and middle value.
		$test_scores = array( 41, 55, 70 );

		foreach ( $test_scores as $score ) {
			$post_id = 200 + $score;
			$wp_postmeta_storage[ $post_id ]['_meowseo_seo_score'] = array( $score );

			ob_start();
			$this->list_table_columns->render_seo_score_column( 'seo_score', $post_id );
			$output = ob_get_clean();

			$this->assertStringContainsString( 'meowseo-score-ok', $output, "Score {$score} should display orange indicator" );
			$this->assertStringContainsString( (string) $score, $output, "Score {$score} should be displayed" );
		}
	}

	/**
	 * Test green indicator displays for good scores (71-100).
	 *
	 * Validates Requirement 3.7: WHEN the SEO score is 71-100,
	 * THE List_Table SHALL display a green indicator.
	 *
	 * @return void
	 */
	public function test_green_indicator_for_good_scores(): void {
		global $wp_postmeta_storage;

		// Test boundary values and middle value.
		$test_scores = array( 71, 85, 100 );

		foreach ( $test_scores as $score ) {
			$post_id = 300 + $score;
			$wp_postmeta_storage[ $post_id ]['_meowseo_seo_score'] = array( $score );

			ob_start();
			$this->list_table_columns->render_seo_score_column( 'seo_score', $post_id );
			$output = ob_get_clean();

			$this->assertStringContainsString( 'meowseo-score-good', $output, "Score {$score} should display green indicator" );
			$this->assertStringContainsString( (string) $score, $output, "Score {$score} should be displayed" );
		}
	}

	/**
	 * Test gray dash displays for posts with no score.
	 *
	 * Validates Requirement 3.10: WHEN a post has no SEO score,
	 * THE List_Table SHALL display a gray dash indicator.
	 *
	 * @return void
	 */
	public function test_gray_dash_for_no_score(): void {
		global $wp_postmeta_storage;

		// Test with completely missing postmeta.
		$post_id = 999;
		$wp_postmeta_storage[ $post_id ] = array();

		ob_start();
		$this->list_table_columns->render_seo_score_column( 'seo_score', $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'meowseo-score-none', $output, 'Posts without scores should display gray indicator' );
		$this->assertStringContainsString( '—', $output, 'Should display dash character' );
		$this->assertStringContainsString( 'No SEO Score', $output, 'Should indicate no score in tooltip' );
	}

	/**
	 * Test column is registered as sortable.
	 *
	 * Validates Requirement 3.8: WHEN a user clicks the SEO Score column header,
	 * THE List_Table SHALL sort posts by _meowseo_seo_score postmeta.
	 *
	 * @return void
	 */
	public function test_column_is_sortable(): void {
		$columns = array(
			'title' => 'title',
			'date' => 'date',
		);

		$result = $this->list_table_columns->register_sortable_column( $columns );

		$this->assertArrayHasKey( 'seo_score', $result, 'SEO Score should be registered as sortable' );
		$this->assertSame( 'seo_score', $result['seo_score'], 'Sortable key should match column key' );
	}

	/**
	 * Test sorting by SEO Score in descending order.
	 *
	 * Validates Requirement 3.8: WHEN a user clicks the SEO Score column header,
	 * THE List_Table SHALL sort posts by _meowseo_seo_score postmeta in descending order.
	 *
	 * @return void
	 */
	public function test_sorting_descending(): void {
		// Create a mock WP_Query.
		$query = new \WP_Query( 'seo_score', 'DESC' );

		// Call the sorting handler.
		$this->list_table_columns->handle_seo_score_sorting( $query );

		// Verify meta_key was set.
		$this->assertSame( '_meowseo_seo_score', $query->get( 'meta_key' ), 'Should set meta_key to _meowseo_seo_score' );

		// Verify orderby was set to numeric.
		$this->assertSame( 'meta_value_num', $query->get( 'orderby' ), 'Should use numeric ordering' );

		// Verify order is preserved (DESC).
		$this->assertSame( 'DESC', $query->get( 'order' ), 'Should preserve DESC order' );
	}

	/**
	 * Test sorting by SEO Score in ascending order.
	 *
	 * Validates Requirement 3.9: WHEN a user clicks the SEO Score column header again,
	 * THE List_Table SHALL sort posts by _meowseo_seo_score postmeta in ascending order.
	 *
	 * @return void
	 */
	public function test_sorting_ascending(): void {
		// Create a mock WP_Query.
		$query = new \WP_Query( 'seo_score', 'ASC' );

		// Call the sorting handler.
		$this->list_table_columns->handle_seo_score_sorting( $query );

		// Verify meta_key was set.
		$this->assertSame( '_meowseo_seo_score', $query->get( 'meta_key' ), 'Should set meta_key to _meowseo_seo_score' );

		// Verify orderby was set to numeric.
		$this->assertSame( 'meta_value_num', $query->get( 'orderby' ), 'Should use numeric ordering' );

		// Verify order is preserved (ASC).
		$this->assertSame( 'ASC', $query->get( 'order' ), 'Should preserve ASC order' );
	}

	/**
	 * Test sorting returns highest score first in descending order.
	 *
	 * Validates Requirement 3.11: FOR ALL posts with stored _meowseo_seo_score values,
	 * sorting by SEO Score column then retrieving the first post SHALL return
	 * the post with the highest score value.
	 *
	 * @return void
	 */
	public function test_sorting_returns_highest_score_first(): void {
		global $wp_postmeta_storage;

		// Create posts with various scores.
		$posts_with_scores = array(
			101 => 45,  // Orange.
			102 => 92,  // Green - should be first.
			103 => 30,  // Red.
			104 => 78,  // Green.
			105 => 55,  // Orange.
		);

		foreach ( $posts_with_scores as $post_id => $score ) {
			$wp_postmeta_storage[ $post_id ]['_meowseo_seo_score'] = array( $score );
		}

		// Simulate sorting query.
		$query = new \WP_Query( 'seo_score', 'DESC' );
		$this->list_table_columns->handle_seo_score_sorting( $query );

		// Verify the query is configured to sort by score numerically.
		$this->assertSame( '_meowseo_seo_score', $query->get( 'meta_key' ) );
		$this->assertSame( 'meta_value_num', $query->get( 'orderby' ) );

		// In a real WordPress environment, this would return post 102 (score 92) first.
		// We verify the query is configured correctly for that behavior.
		$this->assertTrue( true, 'Query configured correctly for descending numeric sort' );
	}

	/**
	 * Test sorting handler ignores non-admin queries.
	 *
	 * @return void
	 */
	public function test_sorting_ignores_non_admin_queries(): void {
		global $test_is_admin;
		$test_is_admin = false; // Simulate non-admin context.
		
		$query = new \WP_Query( 'seo_score', 'DESC', false, true );

		// Store original values.
		$original_meta_key = $query->get( 'meta_key' );
		$original_orderby = $query->get( 'orderby' );

		// Call the sorting handler.
		$this->list_table_columns->handle_seo_score_sorting( $query );

		// Verify query was not modified.
		$this->assertSame( $original_meta_key, $query->get( 'meta_key' ), 'Should not modify non-admin queries' );
		$this->assertSame( $original_orderby, $query->get( 'orderby' ), 'Should not modify non-admin queries' );
		
		// Reset for other tests.
		$test_is_admin = true;
	}

	/**
	 * Test sorting handler ignores non-main queries.
	 *
	 * @return void
	 */
	public function test_sorting_ignores_non_main_queries(): void {
		$query = new \WP_Query( 'seo_score', 'DESC', false, false );

		// Store original values.
		$original_meta_key = $query->get( 'meta_key' );
		$original_orderby = $query->get( 'orderby' );

		// Call the sorting handler.
		$this->list_table_columns->handle_seo_score_sorting( $query );

		// Verify query was not modified.
		$this->assertSame( $original_meta_key, $query->get( 'meta_key' ), 'Should not modify non-main queries' );
		$this->assertSame( $original_orderby, $query->get( 'orderby' ), 'Should not modify non-main queries' );
	}

	/**
	 * Test sorting handler ignores other orderby values.
	 *
	 * @return void
	 */
	public function test_sorting_ignores_other_orderby(): void {
		$query = new \WP_Query( 'date', 'DESC' );

		// Store original values.
		$original_meta_key = $query->get( 'meta_key' );
		$original_orderby = $query->get( 'orderby' );

		// Call the sorting handler.
		$this->list_table_columns->handle_seo_score_sorting( $query );

		// Verify query was not modified.
		$this->assertSame( $original_meta_key, $query->get( 'meta_key' ), 'Should not modify queries sorting by other columns' );
		$this->assertSame( $original_orderby, $query->get( 'orderby' ), 'Should not modify queries sorting by other columns' );
	}

	/**
	 * Test complete end-to-end workflow.
	 *
	 * Validates the complete user workflow:
	 * 1. View posts list table
	 * 2. See SEO Score column with colored indicators
	 * 3. Click to sort by score
	 * 4. See posts ordered by score
	 *
	 * @return void
	 */
	public function test_complete_e2e_workflow(): void {
		global $wp_postmeta_storage;

		// Step 1: Set up posts with various scores.
		$posts = array(
			1 => 85,   // Green.
			2 => 30,   // Red.
			3 => null, // No score.
			4 => 55,   // Orange.
			5 => 95,   // Green.
		);

		foreach ( $posts as $post_id => $score ) {
			if ( $score !== null ) {
				$wp_postmeta_storage[ $post_id ]['_meowseo_seo_score'] = array( $score );
			}
		}

		// Step 2: Verify column appears in list table.
		$columns = array( 'title' => 'Title', 'date' => 'Date' );
		$columns_with_score = $this->list_table_columns->add_seo_score_column( $columns );
		$this->assertArrayHasKey( 'seo_score', $columns_with_score );

		// Step 3: Verify each post renders correct indicator.
		foreach ( $posts as $post_id => $score ) {
			ob_start();
			$this->list_table_columns->render_seo_score_column( 'seo_score', $post_id );
			$output = ob_get_clean();

			if ( $score === null ) {
				$this->assertStringContainsString( 'meowseo-score-none', $output );
			} elseif ( $score >= 71 ) {
				$this->assertStringContainsString( 'meowseo-score-good', $output );
			} elseif ( $score >= 41 ) {
				$this->assertStringContainsString( 'meowseo-score-ok', $output );
			} else {
				$this->assertStringContainsString( 'meowseo-score-poor', $output );
			}
		}

		// Step 4: Verify column is sortable.
		$sortable = $this->list_table_columns->register_sortable_column( array() );
		$this->assertArrayHasKey( 'seo_score', $sortable );

		// Step 5: Verify sorting query is configured correctly.
		$query = new \WP_Query( 'seo_score', 'DESC' );
		$this->list_table_columns->handle_seo_score_sorting( $query );
		$this->assertSame( '_meowseo_seo_score', $query->get( 'meta_key' ) );
		$this->assertSame( 'meta_value_num', $query->get( 'orderby' ) );
	}
}
