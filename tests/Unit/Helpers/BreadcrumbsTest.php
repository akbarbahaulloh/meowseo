<?php
/**
 * Tests for Breadcrumbs class.
 *
 * @package MeowSEO\Tests\Unit\Helpers
 */

namespace MeowSEO\Tests\Unit\Helpers;

use MeowSEO\Helpers\Breadcrumbs;
use MeowSEO\Options;
use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Breadcrumbs test case.
 */
class BreadcrumbsTest extends TestCase {

	/**
	 * Test that Breadcrumbs can be instantiated.
	 *
	 * @return void
	 */
	public function test_breadcrumbs_can_be_instantiated(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$options = new Options();
		$breadcrumbs = new Breadcrumbs( $options );

		$this->assertInstanceOf( 'MeowSEO\Helpers\Breadcrumbs', $breadcrumbs );
	}

	/**
	 * Test get_trail method exists.
	 *
	 * @return void
	 */
	public function test_get_trail_method_exists(): void {
		$this->assertTrue( method_exists( Breadcrumbs::class, 'get_trail' ) );
	}

	/**
	 * Test render method exists.
	 *
	 * @return void
	 */
	public function test_render_method_exists(): void {
		$this->assertTrue( method_exists( Breadcrumbs::class, 'render' ) );
	}

	/**
	 * Test get_trail returns array.
	 *
	 * @return void
	 */
	public function test_get_trail_returns_array(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'is_404' )->justReturn( false );
		Functions\when( 'is_search' )->justReturn( false );
		Functions\when( 'is_archive' )->justReturn( false );
		Functions\when( 'is_page' )->justReturn( false );
		Functions\when( 'is_single' )->justReturn( false );
		Functions\when( 'home_url' )->justReturn( 'http://example.org' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$options = new Options();
		$breadcrumbs = new Breadcrumbs( $options );
		$trail = $breadcrumbs->get_trail();

		$this->assertIsArray( $trail );
	}

	/**
	 * Test get_trail returns home for default page.
	 *
	 * @return void
	 */
	public function test_get_trail_returns_home_for_default(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'is_404' )->justReturn( false );
		Functions\when( 'is_search' )->justReturn( false );
		Functions\when( 'is_archive' )->justReturn( false );
		Functions\when( 'is_page' )->justReturn( false );
		Functions\when( 'is_single' )->justReturn( false );
		Functions\when( 'home_url' )->justReturn( 'http://example.org' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$options = new Options();
		$breadcrumbs = new Breadcrumbs( $options );
		$trail = $breadcrumbs->get_trail();

		$this->assertCount( 1, $trail );
		$this->assertArrayHasKey( 'label', $trail[0] );
		$this->assertArrayHasKey( 'url', $trail[0] );
		$this->assertEquals( 'Home', $trail[0]['label'] );
	}

	/**
	 * Test get_trail for 404 page.
	 *
	 * @return void
	 */
	public function test_get_trail_for_404_page(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'is_404' )->justReturn( true );
		Functions\when( 'home_url' )->justReturn( 'http://example.org' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$options = new Options();
		$breadcrumbs = new Breadcrumbs( $options );
		$trail = $breadcrumbs->get_trail();

		$this->assertCount( 2, $trail );
		$this->assertEquals( 'Home', $trail[0]['label'] );
		$this->assertEquals( 'Page Not Found', $trail[1]['label'] );
		$this->assertEquals( '', $trail[1]['url'] );
	}

	/**
	 * Test get_trail for search page.
	 *
	 * @return void
	 */
	public function test_get_trail_for_search_page(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'is_404' )->justReturn( false );
		Functions\when( 'is_search' )->justReturn( true );
		Functions\when( 'home_url' )->justReturn( 'http://example.org' );
		Functions\when( 'get_search_query' )->justReturn( 'test query' );
		Functions\when( 'get_search_link' )->justReturn( 'http://example.org/?s=test+query' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$options = new Options();
		$breadcrumbs = new Breadcrumbs( $options );
		$trail = $breadcrumbs->get_trail();

		$this->assertCount( 2, $trail );
		$this->assertEquals( 'Home', $trail[0]['label'] );
		$this->assertStringContainsString( 'Search Results', $trail[1]['label'] );
	}

	/**
	 * Test render returns HTML string.
	 *
	 * @return void
	 */
	public function test_render_returns_html_string(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'is_404' )->justReturn( false );
		Functions\when( 'is_search' )->justReturn( false );
		Functions\when( 'is_archive' )->justReturn( false );
		Functions\when( 'is_page' )->justReturn( false );
		Functions\when( 'is_single' )->justReturn( false );
		Functions\when( 'home_url' )->justReturn( 'http://example.org' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'sanitize_html_class' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();

		$options = new Options();
		$breadcrumbs = new Breadcrumbs( $options );
		$html = $breadcrumbs->render();

		$this->assertIsString( $html );
		$this->assertStringContainsString( '<nav', $html );
		$this->assertStringContainsString( 'aria-label="Breadcrumb"', $html );
	}

	/**
	 * Test render includes Schema.org microdata.
	 *
	 * @return void
	 */
	public function test_render_includes_schema_microdata(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'is_404' )->justReturn( false );
		Functions\when( 'is_search' )->justReturn( false );
		Functions\when( 'is_archive' )->justReturn( false );
		Functions\when( 'is_page' )->justReturn( false );
		Functions\when( 'is_single' )->justReturn( false );
		Functions\when( 'home_url' )->justReturn( 'http://example.org' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'sanitize_html_class' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();

		$options = new Options();
		$breadcrumbs = new Breadcrumbs( $options );
		$html = $breadcrumbs->render();

		$this->assertStringContainsString( 'itemscope', $html );
		$this->assertStringContainsString( 'itemtype="https://schema.org/BreadcrumbList"', $html );
		$this->assertStringContainsString( 'itemprop="itemListElement"', $html );
		$this->assertStringContainsString( 'itemprop="name"', $html );
		$this->assertStringContainsString( 'itemprop="position"', $html );
	}

	/**
	 * Test render accepts custom CSS class.
	 *
	 * @return void
	 */
	public function test_render_accepts_custom_css_class(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'is_404' )->justReturn( false );
		Functions\when( 'is_search' )->justReturn( false );
		Functions\when( 'is_archive' )->justReturn( false );
		Functions\when( 'is_page' )->justReturn( false );
		Functions\when( 'is_single' )->justReturn( false );
		Functions\when( 'home_url' )->justReturn( 'http://example.org' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'sanitize_html_class' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();

		$options = new Options();
		$breadcrumbs = new Breadcrumbs( $options );
		$html = $breadcrumbs->render( 'custom-class' );

		$this->assertStringContainsString( 'custom-class', $html );
	}

	/**
	 * Test render accepts custom separator.
	 *
	 * @return void
	 */
	public function test_render_accepts_custom_separator(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'is_404' )->justReturn( true );
		Functions\when( 'home_url' )->justReturn( 'http://example.org' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'sanitize_html_class' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();

		$options = new Options();
		$breadcrumbs = new Breadcrumbs( $options );
		$html = $breadcrumbs->render( '', ' / ' );

		$this->assertStringContainsString( ' / ', $html );
	}

	/**
	 * Test render returns empty string for empty trail.
	 *
	 * @return void
	 */
	public function test_render_returns_empty_for_empty_trail(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'is_404' )->justReturn( false );
		Functions\when( 'is_search' )->justReturn( false );
		Functions\when( 'is_archive' )->justReturn( false );
		Functions\when( 'is_page' )->justReturn( false );
		Functions\when( 'is_single' )->justReturn( false );
		Functions\when( 'home_url' )->justReturn( 'http://example.org' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'apply_filters' )->justReturn( array() ); // Return empty trail

		$options = new Options();
		$breadcrumbs = new Breadcrumbs( $options );
		$html = $breadcrumbs->render();

		$this->assertEquals( '', $html );
	}
}
