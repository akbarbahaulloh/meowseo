<?php
/**
 * News Sitemap Generator Tests
 *
 * Unit tests for the News_Sitemap_Generator class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use MeowSEO\Modules\Sitemap\News_Sitemap_Generator;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;
use WP_UnitTestCase;

/**
 * Test_News_Sitemap_Generator class
 *
 * @since 1.0.0
 */
class Test_News_Sitemap_Generator extends WP_UnitTestCase {

	/**
	 * News sitemap generator instance
	 *
	 * @var News_Sitemap_Generator
	 */
	private News_Sitemap_Generator $generator;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = new Options();
		
		// Setup Brain\Monkey mocking for WordPress functions
		setup_brain_monkey_mocks();
		
		$this->generator = new News_Sitemap_Generator( $this->options );
		
		// Clear transient cache
		delete_transient( 'meowseo_news_sitemap' );
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Clear transient cache
		delete_transient( 'meowseo_news_sitemap' );
		parent::tearDown();
	}

	/**
	 * Test News_Sitemap_Generator instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( News_Sitemap_Generator::class, $this->generator );
	}

	/**
	 * Test get_news_posts returns posts from last 2 days
	 *
	 * Validates Requirement 3.2: News posts are from last 2 days.
	 *
	 * @return void
	 */
	public function test_get_news_posts_returns_recent_posts(): void {
		// Create a post from 1 day ago
		$recent_post = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			)
		);

		// Create a post from 3 days ago (should be excluded)
		$old_post = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
			)
		);

		$posts = $this->generator->get_news_posts();

		$post_ids = wp_list_pluck( $posts, 'ID' );
		
		// Recent post should be included
		$this->assertContains( $recent_post, $post_ids );
		
		// Old post should be excluded
		$this->assertNotContains( $old_post, $post_ids );
	}

	/**
	 * Test get_news_posts excludes noindex posts
	 *
	 * Validates Requirement 3.6, 3.7: Excludes Googlebot-News noindex posts.
	 *
	 * @return void
	 */
	public function test_get_news_posts_excludes_noindex(): void {
		// Create a normal post
		$normal_post = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			)
		);

		// Create a post with general noindex
		$noindex_post = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			)
		);
		update_post_meta( $noindex_post, '_meowseo_noindex', '1' );

		// Create a post with Googlebot-News noindex
		$googlebot_noindex_post = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			)
		);
		update_post_meta( $googlebot_noindex_post, '_meowseo_googlebot_news_noindex', '1' );

		$posts = $this->generator->get_news_posts();
		$post_ids = wp_list_pluck( $posts, 'ID' );

		// Normal post should be included
		$this->assertContains( $normal_post, $post_ids );
		
		// Noindex posts should be excluded
		$this->assertNotContains( $noindex_post, $post_ids );
		$this->assertNotContains( $googlebot_noindex_post, $post_ids );
	}

	/**
	 * Test generate returns false when no news posts
	 *
	 * Validates Requirement 3.2: Returns false when no news posts.
	 *
	 * @return void
	 */
	public function test_generate_returns_false_when_no_posts(): void {
		// No posts created
		$result = $this->generator->generate();
		
		$this->assertFalse( $result );
	}

	/**
	 * Test generate returns valid XML for news posts
	 *
	 * Validates Requirements 3.3, 3.4, 3.5: Generates valid Google News XML.
	 *
	 * @return void
	 */
	public function test_generate_returns_valid_xml(): void {
		// Create a recent post
		$post_id = $this->factory->post->create(
			array(
				'post_title'  => 'Test News Article',
				'post_status' => 'publish',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			)
		);

		// Set focus keyword
		update_post_meta( $post_id, '_meowseo_focus_keyword', 'test keyword' );

		$xml = $this->generator->generate();

		// Verify XML is returned
		$this->assertIsString( $xml );
		$this->assertNotEmpty( $xml );

		// Verify XML structure
		$this->assertStringContainsString( '<?xml version="1.0" encoding="UTF-8"?>', $xml );
		$this->assertStringContainsString( '<urlset', $xml );
		$this->assertStringContainsString( 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"', $xml );

		// Verify news:news elements
		$this->assertStringContainsString( '<news:news>', $xml );
		$this->assertStringContainsString( '<news:publication>', $xml );
		$this->assertStringContainsString( '<news:name>', $xml );
		$this->assertStringContainsString( '<news:language>', $xml );
		$this->assertStringContainsString( '<news:publication_date>', $xml );
		$this->assertStringContainsString( '<news:title>Test News Article</news:title>', $xml );
		$this->assertStringContainsString( '<news:keywords>test keyword</news:keywords>', $xml );
	}

	/**
	 * Test generate caches XML for 5 minutes
	 *
	 * Validates Requirement 3.8: Caches sitemap for 5 minutes.
	 *
	 * @return void
	 */
	public function test_generate_caches_xml(): void {
		// Create a recent post
		$this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			)
		);

		// First call should generate and cache
		$xml1 = $this->generator->generate();
		
		// Verify cache exists
		$cached = get_transient( 'meowseo_news_sitemap' );
		$this->assertNotFalse( $cached );
		$this->assertEquals( $xml1, $cached );

		// Second call should return cached version
		$xml2 = $this->generator->generate();
		$this->assertEquals( $xml1, $xml2 );
	}

	/**
	 * Test get_publication_name returns configured name
	 *
	 * Validates Requirement 3.9: Uses configured publication name.
	 *
	 * @return void
	 */
	public function test_get_publication_name_returns_configured_name(): void {
		$this->options->set( 'news_sitemap_publication_name', 'Test Publication' );
		$this->options->save();

		$name = $this->generator->get_publication_name();
		
		$this->assertEquals( 'Test Publication', $name );
	}

	/**
	 * Test get_publication_name falls back to site name
	 *
	 * Validates Requirement 3.9: Falls back to site name.
	 *
	 * @return void
	 */
	public function test_get_publication_name_fallback(): void {
		// Don't set publication name
		$name = $this->generator->get_publication_name();
		
		// Should return site name
		$this->assertEquals( get_bloginfo( 'name' ), $name );
	}

	/**
	 * Test get_publication_language returns configured language
	 *
	 * Validates Requirement 3.9: Uses configured publication language.
	 *
	 * @return void
	 */
	public function test_get_publication_language_returns_configured_language(): void {
		$this->options->set( 'news_sitemap_language', 'en' );
		$this->options->save();

		$language = $this->generator->get_publication_language();
		
		$this->assertEquals( 'en', $language );
	}

	/**
	 * Test get_publication_language falls back to site language
	 *
	 * Validates Requirement 3.9: Falls back to site language.
	 *
	 * @return void
	 */
	public function test_get_publication_language_fallback(): void {
		// Don't set publication language
		$language = $this->generator->get_publication_language();
		
		// Should return site language (converted to ISO 639-1)
		$site_language = get_bloginfo( 'language' );
		if ( strpos( $site_language, '-' ) !== false ) {
			$parts = explode( '-', $site_language );
			$expected = $parts[0];
		} else {
			$expected = $site_language;
		}
		
		$this->assertEquals( $expected, $language );
	}
}
