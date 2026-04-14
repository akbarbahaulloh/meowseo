<?php
/**
 * Tests for Meta module SEO analysis integration
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Meta;
use MeowSEO\Options;

/**
 * Meta analysis integration test class
 */
class MetaAnalysisTest extends TestCase {

	/**
	 * Set up test
	 */
	public function setUp(): void {
		parent::setUp();

		// Note: These tests require WordPress test framework for full functionality.
		// For now, they serve as structural tests.
		$this->markTestSkipped( 'Meta analysis tests require WordPress test framework' );
	}

	/**
	 * Test get_seo_analysis returns valid structure
	 */
	public function test_get_seo_analysis_returns_valid_structure() {
		$result = $this->meta->get_seo_analysis( $this->post_id );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertArrayHasKey( 'checks', $result );
		$this->assertArrayHasKey( 'color', $result );
	}

	/**
	 * Test SEO analysis score is between 0 and 100
	 */
	public function test_seo_analysis_score_is_bounded() {
		$result = $this->meta->get_seo_analysis( $this->post_id );

		$this->assertGreaterThanOrEqual( 0, $result['score'] );
		$this->assertLessThanOrEqual( 100, $result['score'] );
	}

	/**
	 * Test SEO analysis checks is an array
	 */
	public function test_seo_analysis_checks_is_array() {
		$result = $this->meta->get_seo_analysis( $this->post_id );

		$this->assertIsArray( $result['checks'] );
		$this->assertNotEmpty( $result['checks'] );
	}

	/**
	 * Test SEO analysis color indicator is valid
	 */
	public function test_seo_analysis_color_is_valid() {
		$result = $this->meta->get_seo_analysis( $this->post_id );

		$this->assertContains( $result['color'], array( 'red', 'orange', 'green' ) );
	}

	/**
	 * Test get_readability_analysis returns valid structure
	 */
	public function test_get_readability_analysis_returns_valid_structure() {
		$content = '<p>This is a test paragraph. It has multiple sentences. Each sentence is short.</p>';
		$result  = $this->meta->get_readability_analysis( $content );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertArrayHasKey( 'checks', $result );
		$this->assertArrayHasKey( 'color', $result );
	}

	/**
	 * Test readability analysis score is between 0 and 100
	 */
	public function test_readability_analysis_score_is_bounded() {
		$content = '<p>This is a test paragraph. It has multiple sentences. Each sentence is short.</p>';
		$result  = $this->meta->get_readability_analysis( $content );

		$this->assertGreaterThanOrEqual( 0, $result['score'] );
		$this->assertLessThanOrEqual( 100, $result['score'] );
	}

	/**
	 * Test readability analysis with empty content
	 */
	public function test_readability_analysis_with_empty_content() {
		$result = $this->meta->get_readability_analysis( '' );

		$this->assertEquals( 0, $result['score'] );
		$this->assertEquals( 'red', $result['color'] );
	}

	/**
	 * Test SEO analysis with custom content and keyword
	 */
	public function test_seo_analysis_with_custom_content_and_keyword() {
		$content       = '<p>WordPress is great for SEO optimization.</p><h2>SEO Best Practices</h2>';
		$focus_keyword = 'WordPress';

		$result = $this->meta->get_seo_analysis( $this->post_id, $content, $focus_keyword );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertGreaterThan( 0, $result['score'] );
	}

	/**
	 * Test SEO analysis uses postmeta focus keyword when not provided
	 */
	public function test_seo_analysis_uses_postmeta_focus_keyword() {
		$result = $this->meta->get_seo_analysis( $this->post_id );

		// Should use 'SEO' from postmeta.
		$this->assertGreaterThan( 0, $result['score'] );
	}

	/**
	 * Test analysis with non-existent post
	 */
	public function test_seo_analysis_with_invalid_post() {
		$result = $this->meta->get_seo_analysis( 999999 );

		$this->assertEquals( 0, $result['score'] );
		$this->assertEquals( 'red', $result['color'] );
	}
}
