<?php
/**
 * Tests for Keyword_Manager class.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Keywords;

use MeowSEO\Modules\Keywords\Keyword_Manager;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Keyword_Manager test case.
 */
class KeywordManagerTest extends TestCase {

	/**
	 * Keyword Manager instance.
	 *
	 * @var Keyword_Manager
	 */
	private Keyword_Manager $keyword_manager;

	/**
	 * Test post ID.
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Clear postmeta storage.
		global $wp_postmeta_storage;
		$wp_postmeta_storage = array();

		$options = $this->createMock( Options::class );
		$this->keyword_manager = new Keyword_Manager( $options );

		// Use a test post ID.
		$this->post_id = 123;
	}

	/**
	 * Test get_keywords returns empty array for new post.
	 *
	 * @return void
	 */
	public function test_get_keywords_empty(): void {
		$keywords = $this->keyword_manager->get_keywords( $this->post_id );

		$this->assertIsArray( $keywords );
		$this->assertArrayHasKey( 'primary', $keywords );
		$this->assertArrayHasKey( 'secondary', $keywords );
		$this->assertSame( '', $keywords['primary'] );
		$this->assertSame( array(), $keywords['secondary'] );
	}

	/**
	 * Test set_primary_keyword stores keyword correctly.
	 *
	 * @return void
	 */
	public function test_set_primary_keyword(): void {
		$result = $this->keyword_manager->set_primary_keyword( $this->post_id, 'wordpress seo' );

		$this->assertTrue( $result );

		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertSame( 'wordpress seo', $keywords['primary'] );
	}

	/**
	 * Test set_primary_keyword trims whitespace.
	 *
	 * @return void
	 */
	public function test_set_primary_keyword_trims_whitespace(): void {
		$this->keyword_manager->set_primary_keyword( $this->post_id, '  wordpress seo  ' );

		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertSame( 'wordpress seo', $keywords['primary'] );
	}

	/**
	 * Test set_primary_keyword with empty string deletes meta.
	 *
	 * @return void
	 */
	public function test_set_primary_keyword_empty_deletes(): void {
		// Set a keyword first.
		$this->keyword_manager->set_primary_keyword( $this->post_id, 'wordpress seo' );

		// Clear it.
		$result = $this->keyword_manager->set_primary_keyword( $this->post_id, '' );

		$this->assertTrue( $result );

		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertSame( '', $keywords['primary'] );
	}

	/**
	 * Test add_secondary_keyword adds keyword correctly.
	 *
	 * @return void
	 */
	public function test_add_secondary_keyword(): void {
		$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, 'seo plugin' );

		$this->assertTrue( $result );

		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertContains( 'seo plugin', $keywords['secondary'] );
	}

	/**
	 * Test add_secondary_keyword rejects empty keyword.
	 *
	 * @return void
	 */
	public function test_add_secondary_keyword_rejects_empty(): void {
		$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, '' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertTrue( $result['error'] );
	}

	/**
	 * Test add_secondary_keyword rejects duplicate.
	 *
	 * @return void
	 */
	public function test_add_secondary_keyword_rejects_duplicate(): void {
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'seo plugin' );
		$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, 'seo plugin' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertTrue( $result['error'] );
	}

	/**
	 * Test add_secondary_keyword rejects when primary keyword matches.
	 *
	 * @return void
	 */
	public function test_add_secondary_keyword_rejects_primary_match(): void {
		$this->keyword_manager->set_primary_keyword( $this->post_id, 'wordpress seo' );
		$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, 'wordpress seo' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertTrue( $result['error'] );
	}

	/**
	 * Test validate_keyword_count enforces 5 keyword maximum.
	 *
	 * @return void
	 */
	public function test_validate_keyword_count_maximum(): void {
		// Set primary keyword.
		$this->keyword_manager->set_primary_keyword( $this->post_id, 'wordpress seo' );

		// Add 4 secondary keywords.
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'seo plugin' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'search optimization' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'meta tags' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'wordpress plugin' );

		// Try to add 5th secondary keyword (6th total).
		$result = $this->keyword_manager->add_secondary_keyword( $this->post_id, 'seo optimization' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertTrue( $result['error'] );
	}

	/**
	 * Test remove_secondary_keyword removes keyword correctly.
	 *
	 * @return void
	 */
	public function test_remove_secondary_keyword(): void {
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'seo plugin' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'search optimization' );

		$result = $this->keyword_manager->remove_secondary_keyword( $this->post_id, 'seo plugin' );

		$this->assertTrue( $result );

		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertNotContains( 'seo plugin', $keywords['secondary'] );
		$this->assertContains( 'search optimization', $keywords['secondary'] );
	}

	/**
	 * Test remove_secondary_keyword returns false for non-existent keyword.
	 *
	 * @return void
	 */
	public function test_remove_secondary_keyword_nonexistent(): void {
		$result = $this->keyword_manager->remove_secondary_keyword( $this->post_id, 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test reorder_secondary_keywords updates order correctly.
	 *
	 * @return void
	 */
	public function test_reorder_secondary_keywords(): void {
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword1' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword2' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword3' );

		$result = $this->keyword_manager->reorder_secondary_keywords(
			$this->post_id,
			array( 'keyword3', 'keyword1', 'keyword2' )
		);

		$this->assertTrue( $result );

		$keywords = $this->keyword_manager->get_keywords( $this->post_id );
		$this->assertSame( array( 'keyword3', 'keyword1', 'keyword2' ), $keywords['secondary'] );
	}

	/**
	 * Test reorder_secondary_keywords rejects invalid keyword.
	 *
	 * @return void
	 */
	public function test_reorder_secondary_keywords_rejects_invalid(): void {
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword1' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'keyword2' );

		$result = $this->keyword_manager->reorder_secondary_keywords(
			$this->post_id,
			array( 'keyword1', 'invalid' )
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertTrue( $result['error'] );
	}

	/**
	 * Test get_keyword_count returns correct count.
	 *
	 * @return void
	 */
	public function test_get_keyword_count(): void {
		$this->keyword_manager->set_primary_keyword( $this->post_id, 'wordpress seo' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'seo plugin' );
		$this->keyword_manager->add_secondary_keyword( $this->post_id, 'search optimization' );

		$count = $this->keyword_manager->get_keyword_count( $this->post_id );

		$this->assertSame( 3, $count );
	}
}
