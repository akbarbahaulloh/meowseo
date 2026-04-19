<?php
/**
 * End-to-End Keyword Functionality Test
 *
 * Tests the complete keyword workflow from storage to analysis to REST API.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Keywords\Keyword_Manager;
use MeowSEO\Modules\Keywords\Keyword_Analyzer;
use MeowSEO\Options;

/**
 * Keyword End-to-End Test Case
 *
 * @since 1.0.0
 */
class KeywordEndToEndTest extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Keyword Manager instance
	 *
	 * @var Keyword_Manager
	 */
	private Keyword_Manager $keyword_manager;

	/**
	 * Keyword Analyzer instance
	 *
	 * @var Keyword_Analyzer
	 */
	private Keyword_Analyzer $keyword_analyzer;

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Reset global storage.
		global $wp_postmeta_storage, $wp_posts_storage;
		$wp_postmeta_storage = array();
		$wp_posts_storage = array();

		// Create test post.
		$this->post_id = wp_insert_post(
			array(
				'post_title'   => 'WordPress SEO Guide',
				'post_content' => '<p>WordPress is a powerful platform for SEO optimization. This guide covers SEO best practices.</p><h2>SEO Basics</h2><p>Learn about search engine optimization.</p>',
				'post_name'    => 'wordpress-seo-guide',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		// Initialize components.
		$this->options = new Options();
		$this->keyword_manager = new Keyword_Manager( $this->options );
		$this->keyword_analyzer = new Keyword_Analyzer( $this->keyword_manager );
	}

	/**
	 * Test complete keyword workflow: storage → analysis → retrieval
	 *
	 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9
	 *
	 * @return void
	 */
	public function test_complete_keyword_workflow(): void {
		// Step 1: Set primary keyword.
		$result = $this->keyword_manager->set_primary_keyword( $this->post_id, 'wordpress seo' );
		$this->assertTrue( $result, 'Primary keyword should be set successfully' );

		// Step 2: Add secondary keywords.
		$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, 'seo optimization' );
		$this->assertTrue( $result, 'First secondary keyword should be added' );

		$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, 'search engine' );
		$this->assertTrue( $result, 'Second secondary keyword should be added' );

		// Step 3: Verify keyword storage.
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertEquals( 'wordpress seo', $keywords['primary'], 'Primary keyword should match' );
		$this->assertCount( 2, $keywords['secondary'], 'Should have 2 secondary keywords' );
		$this->assertContains( 'seo optimization', $keywords['secondary'], 'Should contain first secondary keyword' );
		$this->assertContains( 'search engine', $keywords['secondary'], 'Should contain second secondary keyword' );

		// Step 4: Run analysis for all keywords.
		$post = get_post( $this->post_id );
		$context = array(
			'title'       => $post->post_title,
			'description' => 'A comprehensive guide to WordPress SEO optimization',
			'slug'        => $post->post_name,
		);

		$analysis_results = $this->keyword_analyzer->analyze_all_keywords(
			$this->post_id,
			$post->post_content,
			$context
		);

		// Step 5: Verify analysis results structure.
		$this->assertIsArray( $analysis_results, 'Analysis results should be an array' );
		$this->assertArrayHasKey( 'wordpress seo', $analysis_results, 'Should have analysis for primary keyword' );
		$this->assertArrayHasKey( 'seo optimization', $analysis_results, 'Should have analysis for first secondary keyword' );
		$this->assertArrayHasKey( 'search engine', $analysis_results, 'Should have analysis for second secondary keyword' );

		// Step 6: Verify analysis result structure for primary keyword.
		$primary_analysis = $analysis_results['wordpress seo'];
		$this->assertArrayHasKey( 'density', $primary_analysis, 'Should have density check' );
		$this->assertArrayHasKey( 'in_title', $primary_analysis, 'Should have in_title check' );
		$this->assertArrayHasKey( 'in_headings', $primary_analysis, 'Should have in_headings check' );
		$this->assertArrayHasKey( 'in_slug', $primary_analysis, 'Should have in_slug check' );
		$this->assertArrayHasKey( 'in_first_paragraph', $primary_analysis, 'Should have in_first_paragraph check' );
		$this->assertArrayHasKey( 'in_meta_description', $primary_analysis, 'Should have in_meta_description check' );
		$this->assertArrayHasKey( 'overall_score', $primary_analysis, 'Should have overall_score' );

		// Step 7: Verify each check has score and status.
		foreach ( array( 'density', 'in_title', 'in_headings', 'in_slug', 'in_first_paragraph', 'in_meta_description' ) as $check ) {
			$this->assertArrayHasKey( 'score', $primary_analysis[ $check ], "Check {$check} should have score" );
			$this->assertArrayHasKey( 'status', $primary_analysis[ $check ], "Check {$check} should have status" );
			$this->assertIsInt( $primary_analysis[ $check ]['score'], "Check {$check} score should be integer" );
			$this->assertContains(
				$primary_analysis[ $check ]['status'],
				array( 'good', 'ok', 'poor' ),
				"Check {$check} status should be valid"
			);
		}

		// Step 8: Verify overall score is within valid range.
		$this->assertIsInt( $primary_analysis['overall_score'], 'Overall score should be integer' );
		$this->assertGreaterThanOrEqual( 0, $primary_analysis['overall_score'], 'Overall score should be >= 0' );
		$this->assertLessThanOrEqual( 100, $primary_analysis['overall_score'], 'Overall score should be <= 100' );

		// Step 9: Verify keyword in title check passes (keyword is in title).
		$this->assertEquals( 100, $primary_analysis['in_title']['score'], 'Keyword should be found in title' );
		$this->assertEquals( 'good', $primary_analysis['in_title']['status'], 'In title status should be good' );

		// Step 10: Verify keyword in slug check passes (keyword is in slug).
		$this->assertEquals( 100, $primary_analysis['in_slug']['score'], 'Keyword should be found in slug' );
		$this->assertEquals( 'good', $primary_analysis['in_slug']['status'], 'In slug status should be good' );
	}

	/**
	 * Test keyword count validation (max 5 keywords)
	 *
	 * Requirement: 2.2
	 *
	 * @return void
	 */
	public function test_keyword_count_validation(): void {
		// Set primary keyword.
		$this->keyword_manager->set_primary_keyword( $this->post_id, 'primary keyword' );

		// Add 4 secondary keywords (total 5).
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword 1' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword 2' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword 3' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword 4' );

		// Verify we have 5 keywords.
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$total_count = ( ! empty( $keywords['primary'] ) ? 1 : 0 ) + count( $keywords['secondary'] );
		$this->assertEquals( 5, $total_count, 'Should have exactly 5 keywords' );

		// Try to add 6th keyword - should fail.
		$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword 5' );
		$this->assertIsArray( $result, 'Adding 6th keyword should return error array' );
		$this->assertArrayHasKey( 'error', $result, 'Error array should have error key' );
		$this->assertTrue( $result['error'], 'Error flag should be true' );

		// Verify still have 5 keywords.
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$total_count = ( ! empty( $keywords['primary'] ) ? 1 : 0 ) + count( $keywords['secondary'] );
		$this->assertEquals( 5, $total_count, 'Should still have exactly 5 keywords' );
	}

	/**
	 * Test per-keyword analysis execution
	 *
	 * Requirements: 2.3, 2.4, 2.5, 2.6, 2.7, 2.8
	 *
	 * @return void
	 */
	public function test_per_keyword_analysis_execution(): void {
		// Set up multiple keywords.
		$this->keyword_manager->set_primary_keyword( $this->post_id, 'wordpress' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'seo' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'optimization' );

		// Run analysis.
		$post = get_post( $this->post_id );
		$context = array(
			'title'       => $post->post_title,
			'description' => 'WordPress SEO optimization guide',
			'slug'        => $post->post_name,
		);

		$analysis_results = $this->keyword_analyzer->analyze_all_keywords(
			$this->post_id,
			$post->post_content,
			$context
		);

		// Verify each keyword has its own analysis.
		$this->assertArrayHasKey( 'wordpress', $analysis_results, 'Should have analysis for wordpress' );
		$this->assertArrayHasKey( 'seo', $analysis_results, 'Should have analysis for seo' );
		$this->assertArrayHasKey( 'optimization', $analysis_results, 'Should have analysis for optimization' );

		// Verify each keyword has all 6 checks.
		foreach ( array( 'wordpress', 'seo', 'optimization' ) as $keyword ) {
			$this->assertArrayHasKey( 'density', $analysis_results[ $keyword ], "{$keyword} should have density check" );
			$this->assertArrayHasKey( 'in_title', $analysis_results[ $keyword ], "{$keyword} should have in_title check" );
			$this->assertArrayHasKey( 'in_headings', $analysis_results[ $keyword ], "{$keyword} should have in_headings check" );
			$this->assertArrayHasKey( 'in_slug', $analysis_results[ $keyword ], "{$keyword} should have in_slug check" );
			$this->assertArrayHasKey( 'in_first_paragraph', $analysis_results[ $keyword ], "{$keyword} should have in_first_paragraph check" );
			$this->assertArrayHasKey( 'in_meta_description', $analysis_results[ $keyword ], "{$keyword} should have in_meta_description check" );
		}

		// Verify different keywords have different scores (they appear differently in content).
		$this->assertNotEquals(
			$analysis_results['wordpress']['overall_score'],
			$analysis_results['optimization']['overall_score'],
			'Different keywords should have different scores'
		);
	}

	/**
	 * Test creating post with 5 focus keywords
	 *
	 * Requirements: 2.1, 2.2, 2.9
	 *
	 * @return void
	 */
	public function test_create_post_with_five_keywords(): void {
		// Set primary keyword.
		$result = $this->keyword_manager->set_primary_keyword( $this->post_id, 'wordpress seo' );
		$this->assertTrue( $result, 'Primary keyword should be set' );

		// Add 4 secondary keywords to reach maximum of 5.
		$secondary_keywords = array(
			'seo plugin',
			'search optimization',
			'meta tags',
			'wordpress plugin',
		);

		foreach ( $secondary_keywords as $keyword ) {
			$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, $keyword );
			$this->assertTrue( $result, "Secondary keyword '{$keyword}' should be added" );
		}

		// Verify we have exactly 5 keywords.
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertEquals( 'wordpress seo', $keywords['primary'], 'Primary keyword should match' );
		$this->assertCount( 4, $keywords['secondary'], 'Should have 4 secondary keywords' );
		$this->assertEquals( 5, $this->keyword_manager->get_keyword_count( $this->post_id ), 'Total count should be 5' );

		// Run analysis for all 5 keywords.
		$post = get_post( $this->post_id );
		$context = array(
			'title'       => $post->post_title,
			'description' => 'WordPress SEO plugin guide with meta tags and search optimization',
			'slug'        => $post->post_name,
		);

		$analysis_results = $this->keyword_analyzer->analyze_all_keywords(
			$this->post_id,
			$post->post_content,
			$context
		);

		// Verify analysis runs for each keyword.
		$this->assertCount( 5, $analysis_results, 'Should have analysis for all 5 keywords' );
		$this->assertArrayHasKey( 'wordpress seo', $analysis_results, 'Should have analysis for primary keyword' );

		foreach ( $secondary_keywords as $keyword ) {
			$this->assertArrayHasKey( $keyword, $analysis_results, "Should have analysis for '{$keyword}'" );
			$this->assertArrayHasKey( 'overall_score', $analysis_results[ $keyword ], "'{$keyword}' should have overall score" );
			$this->assertIsInt( $analysis_results[ $keyword ]['overall_score'], "'{$keyword}' score should be integer" );
		}

		// Verify per-keyword scores are stored in postmeta.
		$stored_analysis = get_post_meta( $this->post_id, '_meowseo_keyword_analysis', true );
		$this->assertNotEmpty( $stored_analysis, 'Analysis should be stored in postmeta' );

		if ( is_string( $stored_analysis ) ) {
			$stored_analysis = json_decode( $stored_analysis, true );
		}

		$this->assertIsArray( $stored_analysis, 'Stored analysis should be an array' );
		$this->assertCount( 5, $stored_analysis, 'Stored analysis should have 5 keywords' );
	}

	/**
	 * Test keyword removal
	 *
	 * Requirements: 2.10
	 *
	 * @return void
	 */
	public function test_keyword_removal(): void {
		// Set up keywords.
		$this->keyword_manager->set_primary_keyword( $this->post_id, 'primary keyword' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword 1' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword 2' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword 3' );

		// Verify initial state (4 keywords).
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertCount( 3, $keywords['secondary'], 'Should have 3 secondary keywords initially' );
		$this->assertEquals( 4, $this->keyword_manager->get_keyword_count( $this->post_id ), 'Should have 4 total keywords' );

		// Remove a secondary keyword.
		$result = $this->keyword_manager->remove_secondary_keyword( $this->post_id, 'keyword 2' );
		$this->assertTrue( $result, 'Keyword removal should succeed' );

		// Verify keyword was removed.
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertCount( 2, $keywords['secondary'], 'Should have 2 secondary keywords after removal' );
		$this->assertNotContains( 'keyword 2', $keywords['secondary'], 'Removed keyword should not be present' );
		$this->assertContains( 'keyword 1', $keywords['secondary'], 'Other keywords should remain' );
		$this->assertContains( 'keyword 3', $keywords['secondary'], 'Other keywords should remain' );

		// Verify we can now add another keyword (since we're under the limit).
		$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, 'new keyword' );
		$this->assertTrue( $result, 'Should be able to add keyword after removal' );

		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertCount( 3, $keywords['secondary'], 'Should have 3 secondary keywords after adding' );
		$this->assertContains( 'new keyword', $keywords['secondary'], 'New keyword should be present' );
	}

	/**
	 * Test keyword reordering
	 *
	 * Requirements: 2.11
	 *
	 * @return void
	 */
	public function test_keyword_reordering(): void {
		// Set up keywords.
		$this->keyword_manager->set_primary_keyword( $this->post_id, 'primary keyword' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword A' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword B' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword C' );

		// Verify initial order.
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertEquals( array( 'keyword A', 'keyword B', 'keyword C' ), $keywords['secondary'], 'Initial order should match' );

		// Reorder keywords.
		$new_order = array( 'keyword C', 'keyword A', 'keyword B' );
		$result = $this->keyword_manager->reorder_secondary_keywords( $this->post_id, $new_order );
		$this->assertTrue( $result, 'Reordering should succeed' );

		// Verify new order.
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertEquals( $new_order, $keywords['secondary'], 'Keywords should be in new order' );

		// Test invalid reorder (missing keyword).
		$invalid_order = array( 'keyword C', 'keyword A' ); // Missing keyword B.
		$result = $this->keyword_manager->reorder_secondary_keywords( $this->post_id, $invalid_order );
		$this->assertIsArray( $result, 'Invalid reorder should return error array' );
		$this->assertArrayHasKey( 'error', $result, 'Error array should have error key' );
		$this->assertTrue( $result['error'], 'Error flag should be true' );

		// Verify order unchanged after invalid reorder.
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertEquals( $new_order, $keywords['secondary'], 'Order should remain unchanged after invalid reorder' );

		// Test invalid reorder (extra keyword).
		$invalid_order = array( 'keyword C', 'keyword A', 'keyword B', 'keyword D' );
		$result = $this->keyword_manager->reorder_secondary_keywords( $this->post_id, $invalid_order );
		$this->assertIsArray( $result, 'Invalid reorder with extra keyword should return error array' );
		$this->assertArrayHasKey( 'error', $result, 'Error array should have error key' );
	}

	/**
	 * Test validation prevents exceeding 5 keywords
	 *
	 * Requirements: 2.2, 2.12
	 *
	 * @return void
	 */
	public function test_validation_prevents_exceeding_five_keywords(): void {
		// Set primary keyword.
		$this->keyword_manager->set_primary_keyword( $this->post_id, 'primary keyword' );

		// Add 4 secondary keywords (total 5).
		for ( $i = 1; $i <= 4; $i++ ) {
			$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, "keyword {$i}" );
			$this->assertTrue( $result, "Should be able to add keyword {$i}" );
		}

		// Verify we have exactly 5 keywords.
		$this->assertEquals( 5, $this->keyword_manager->get_keyword_count( $this->post_id ), 'Should have 5 keywords' );
		$this->assertTrue( $this->keyword_manager->validate_keyword_count( $this->post_id ), 'Keyword count should be valid' );

		// Try to add 6th keyword - should fail with error.
		$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword 5' );
		$this->assertIsArray( $result, 'Adding 6th keyword should return error array' );
		$this->assertArrayHasKey( 'error', $result, 'Should have error key' );
		$this->assertTrue( $result['error'], 'Error flag should be true' );
		$this->assertArrayHasKey( 'message', $result, 'Should have error message' );
		$this->assertStringContainsString( '5', $result['message'], 'Error message should mention limit of 5' );

		// Verify keyword count unchanged.
		$this->assertEquals( 5, $this->keyword_manager->get_keyword_count( $this->post_id ), 'Should still have 5 keywords' );

		// Verify the 6th keyword was not added.
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertNotContains( 'keyword 5', $keywords['secondary'], '6th keyword should not be added' );
	}

	/**
	 * Test adding and removing keywords then running analysis
	 *
	 * Requirements: 2.1, 2.2, 2.9, 2.10, 2.12
	 *
	 * @return void
	 */
	public function test_keyword_addition_removal_reordering_with_analysis(): void {
		// Set primary keyword.
		$this->keyword_manager->set_primary_keyword( $this->post_id, 'wordpress seo' );

		// Add 3 secondary keywords.
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'seo plugin' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'search optimization' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'meta tags' );

		// Run analysis with 4 keywords.
		$post = get_post( $this->post_id );
		$context = array(
			'title'       => $post->post_title,
			'description' => 'WordPress SEO plugin guide',
			'slug'        => $post->post_name,
		);

		$analysis_results = $this->keyword_analyzer->analyze_all_keywords(
			$this->post_id,
			$post->post_content,
			$context
		);

		$this->assertCount( 4, $analysis_results, 'Should have analysis for 4 keywords' );

		// Add another keyword (total 5).
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'wordpress plugin' );

		// Run analysis again with 5 keywords.
		$analysis_results = $this->keyword_analyzer->analyze_all_keywords(
			$this->post_id,
			$post->post_content,
			$context
		);

		$this->assertCount( 5, $analysis_results, 'Should have analysis for 5 keywords after adding' );
		$this->assertArrayHasKey( 'wordpress plugin', $analysis_results, 'Should have analysis for new keyword' );

		// Remove a keyword.
		$this->keyword_manager->remove_secondary_keyword( $this->post_id, 'meta tags' );

		// Run analysis again with 4 keywords.
		$analysis_results = $this->keyword_analyzer->analyze_all_keywords(
			$this->post_id,
			$post->post_content,
			$context
		);

		$this->assertCount( 4, $analysis_results, 'Should have analysis for 4 keywords after removal' );
		$this->assertArrayNotHasKey( 'meta tags', $analysis_results, 'Should not have analysis for removed keyword' );

		// Reorder keywords.
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$new_order = array_reverse( $keywords['secondary'] );
		$this->keyword_manager->reorder_secondary_keywords( $this->post_id, $new_order );

		// Run analysis again - order shouldn't affect analysis.
		$analysis_results = $this->keyword_analyzer->analyze_all_keywords(
			$this->post_id,
			$post->post_content,
			$context
		);

		$this->assertCount( 4, $analysis_results, 'Should still have analysis for 4 keywords after reordering' );

		// Verify all keywords still have analysis.
		foreach ( $new_order as $keyword ) {
			$this->assertArrayHasKey( $keyword, $analysis_results, "Should have analysis for '{$keyword}' after reordering" );
		}
	}

	/**
	 * Clean up test environment
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Clean up test post.
		wp_delete_post( $this->post_id, true );

		// Reset global storage.
		global $wp_postmeta_storage, $wp_posts_storage;
		$wp_postmeta_storage = array();
		$wp_posts_storage = array();

		parent::tearDown();
	}
}
