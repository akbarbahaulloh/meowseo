<?php
/**
 * Tests for List_Table_Columns class.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Admin;

use MeowSEO\Modules\Admin\List_Table_Columns;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * List_Table_Columns test case.
 */
class ListTableColumnsTest extends TestCase {

	/**
	 * List_Table_Columns instance.
	 *
	 * @var List_Table_Columns
	 */
	private List_Table_Columns $list_table_columns;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$options = $this->createMock( Options::class );
		$this->list_table_columns = new List_Table_Columns( $options );
	}

	/**
	 * Test add_seo_score_column adds column after title.
	 *
	 * @return void
	 */
	public function test_add_seo_score_column(): void {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Title',
			'author' => 'Author',
			'date' => 'Date',
		);

		$result = $this->list_table_columns->add_seo_score_column( $columns );

		$this->assertArrayHasKey( 'seo_score', $result );
		$this->assertSame( 'SEO Score', $result['seo_score'] );

		// Verify column is after title.
		$keys = array_keys( $result );
		$title_index = array_search( 'title', $keys, true );
		$score_index = array_search( 'seo_score', $keys, true );

		$this->assertSame( $title_index + 1, $score_index );
	}

	/**
	 * Test register_sortable_column adds seo_score to sortable columns.
	 *
	 * @return void
	 */
	public function test_register_sortable_column(): void {
		$columns = array(
			'title' => 'title',
			'date' => 'date',
		);

		$result = $this->list_table_columns->register_sortable_column( $columns );

		$this->assertArrayHasKey( 'seo_score', $result );
		$this->assertSame( 'seo_score', $result['seo_score'] );
	}

	/**
	 * Test render_seo_score_column outputs correct HTML for good score.
	 *
	 * @return void
	 */
	public function test_render_seo_score_column_good_score(): void {
		// Mock get_post_meta to return a good score.
		global $wp_postmeta_storage;
		$wp_postmeta_storage = array();
		$wp_postmeta_storage[123]['_meowseo_seo_score'] = array( 85 );

		ob_start();
		$this->list_table_columns->render_seo_score_column( 'seo_score', 123 );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'meowseo-score-good', $output );
		$this->assertStringContainsString( '85', $output );
		$this->assertStringContainsString( 'SEO Score: 85/100', $output );
	}

	/**
	 * Test render_seo_score_column outputs correct HTML for ok score.
	 *
	 * @return void
	 */
	public function test_render_seo_score_column_ok_score(): void {
		// Mock get_post_meta to return an ok score.
		global $wp_postmeta_storage;
		$wp_postmeta_storage = array();
		$wp_postmeta_storage[123]['_meowseo_seo_score'] = array( 55 );

		ob_start();
		$this->list_table_columns->render_seo_score_column( 'seo_score', 123 );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'meowseo-score-ok', $output );
		$this->assertStringContainsString( '55', $output );
	}

	/**
	 * Test render_seo_score_column outputs correct HTML for poor score.
	 *
	 * @return void
	 */
	public function test_render_seo_score_column_poor_score(): void {
		// Mock get_post_meta to return a poor score.
		global $wp_postmeta_storage;
		$wp_postmeta_storage = array();
		$wp_postmeta_storage[123]['_meowseo_seo_score'] = array( 30 );

		ob_start();
		$this->list_table_columns->render_seo_score_column( 'seo_score', 123 );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'meowseo-score-poor', $output );
		$this->assertStringContainsString( '30', $output );
	}

	/**
	 * Test render_seo_score_column outputs dash for no score.
	 *
	 * @return void
	 */
	public function test_render_seo_score_column_no_score(): void {
		// Mock get_post_meta to return empty.
		global $wp_postmeta_storage;
		$wp_postmeta_storage = array();

		ob_start();
		$this->list_table_columns->render_seo_score_column( 'seo_score', 123 );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'meowseo-score-none', $output );
		$this->assertStringContainsString( '—', $output );
		$this->assertStringContainsString( 'No SEO Score', $output );
	}

	/**
	 * Test render_seo_score_column does nothing for other columns.
	 *
	 * @return void
	 */
	public function test_render_seo_score_column_ignores_other_columns(): void {
		ob_start();
		$this->list_table_columns->render_seo_score_column( 'title', 123 );
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}
}
