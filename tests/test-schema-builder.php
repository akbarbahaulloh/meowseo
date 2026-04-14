<?php
/**
 * Schema Builder Tests
 *
 * Unit tests for the Schema_Builder helper class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Schema_Builder;
use MeowSEO\Options;

/**
 * Schema Builder test case
 *
 * @since 1.0.0
 */
class Test_Schema_Builder extends TestCase {

	/**
	 * Schema Builder instance
	 *
	 * @var Schema_Builder
	 */
	private Schema_Builder $builder;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$options = new Options();
		$this->builder = new Schema_Builder( $options );
	}

	/**
	 * Test Schema_Builder instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( Schema_Builder::class, $this->builder );
	}

	/**
	 * Test build_website returns valid schema
	 *
	 * @return void
	 */
	public function test_build_website_returns_valid_schema(): void {
		$schema = $this->builder->build_website();

		$this->assertIsArray( $schema );
		$this->assertEquals( 'WebSite', $schema['@type'] );
		$this->assertArrayHasKey( '@id', $schema );
		$this->assertArrayHasKey( 'url', $schema );
		$this->assertArrayHasKey( 'name', $schema );
		$this->assertArrayHasKey( 'publisher', $schema );
		$this->assertArrayHasKey( 'potentialAction', $schema );
	}

	/**
	 * Test build_organization returns valid schema
	 *
	 * @return void
	 */
	public function test_build_organization_returns_valid_schema(): void {
		$schema = $this->builder->build_organization();

		$this->assertIsArray( $schema );
		$this->assertEquals( 'Organization', $schema['@type'] );
		$this->assertArrayHasKey( '@id', $schema );
		$this->assertArrayHasKey( 'name', $schema );
		$this->assertArrayHasKey( 'url', $schema );
	}

	/**
	 * Test build_webpage returns valid schema
	 *
	 * @return void
	 */
	public function test_build_webpage_returns_valid_schema(): void {
		$post = $this->create_mock_post();
		$schema = $this->builder->build_webpage( $post );

		$this->assertIsArray( $schema );
		$this->assertEquals( 'WebPage', $schema['@type'] );
		$this->assertArrayHasKey( '@id', $schema );
		$this->assertArrayHasKey( 'url', $schema );
		$this->assertArrayHasKey( 'name', $schema );
		$this->assertArrayHasKey( 'isPartOf', $schema );
		$this->assertArrayHasKey( 'datePublished', $schema );
		$this->assertArrayHasKey( 'dateModified', $schema );
		$this->assertArrayHasKey( 'breadcrumb', $schema );
		$this->assertArrayHasKey( 'inLanguage', $schema );
	}

	/**
	 * Test build_article returns valid schema
	 *
	 * @return void
	 */
	public function test_build_article_returns_valid_schema(): void {
		$post = $this->create_mock_post();
		$schema = $this->builder->build_article( $post );

		$this->assertIsArray( $schema );
		$this->assertEquals( 'Article', $schema['@type'] );
		$this->assertArrayHasKey( '@id', $schema );
		$this->assertArrayHasKey( 'isPartOf', $schema );
		$this->assertArrayHasKey( 'author', $schema );
		$this->assertArrayHasKey( 'headline', $schema );
		$this->assertArrayHasKey( 'datePublished', $schema );
		$this->assertArrayHasKey( 'dateModified', $schema );
		$this->assertArrayHasKey( 'mainEntityOfPage', $schema );
		$this->assertArrayHasKey( 'publisher', $schema );
		$this->assertArrayHasKey( 'inLanguage', $schema );
	}

	/**
	 * Test build_breadcrumb returns valid schema
	 *
	 * @return void
	 */
	public function test_build_breadcrumb_returns_valid_schema(): void {
		$post = $this->create_mock_post();
		$schema = $this->builder->build_breadcrumb( $post );

		$this->assertIsArray( $schema );
		$this->assertEquals( 'BreadcrumbList', $schema['@type'] );
		$this->assertArrayHasKey( '@id', $schema );
		$this->assertArrayHasKey( 'itemListElement', $schema );
		$this->assertIsArray( $schema['itemListElement'] );
		$this->assertNotEmpty( $schema['itemListElement'] );

		// Check first item (home).
		$first_item = $schema['itemListElement'][0];
		$this->assertEquals( 'ListItem', $first_item['@type'] );
		$this->assertEquals( 1, $first_item['position'] );
		$this->assertArrayHasKey( 'name', $first_item );
		$this->assertArrayHasKey( 'item', $first_item );
	}

	/**
	 * Test build_faq returns valid schema
	 *
	 * @return void
	 */
	public function test_build_faq_returns_valid_schema(): void {
		$items = array(
			array(
				'question' => 'What is MeowSEO?',
				'answer'   => 'MeowSEO is a WordPress SEO plugin.',
			),
			array(
				'question' => 'How do I install it?',
				'answer'   => 'Install it from the WordPress plugin directory.',
			),
		);

		$schema = $this->builder->build_faq( $items );

		$this->assertIsArray( $schema );
		$this->assertEquals( 'FAQPage', $schema['@type'] );
		$this->assertArrayHasKey( 'mainEntity', $schema );
		$this->assertIsArray( $schema['mainEntity'] );
		$this->assertCount( 2, $schema['mainEntity'] );

		// Check first question.
		$first_question = $schema['mainEntity'][0];
		$this->assertEquals( 'Question', $first_question['@type'] );
		$this->assertEquals( 'What is MeowSEO?', $first_question['name'] );
		$this->assertArrayHasKey( 'acceptedAnswer', $first_question );
		$this->assertEquals( 'Answer', $first_question['acceptedAnswer']['@type'] );
		$this->assertEquals( 'MeowSEO is a WordPress SEO plugin.', $first_question['acceptedAnswer']['text'] );
	}

	/**
	 * Test build_faq returns empty array for empty items
	 *
	 * @return void
	 */
	public function test_build_faq_returns_empty_array_for_empty_items(): void {
		$schema = $this->builder->build_faq( array() );
		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}

	/**
	 * Test build_faq skips invalid items
	 *
	 * @return void
	 */
	public function test_build_faq_skips_invalid_items(): void {
		$items = array(
			array(
				'question' => 'Valid question?',
				'answer'   => 'Valid answer.',
			),
			array(
				'question' => 'Missing answer?',
			),
			array(
				'answer' => 'Missing question.',
			),
			array(
				'question' => '',
				'answer'   => 'Empty question.',
			),
		);

		$schema = $this->builder->build_faq( $items );

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'mainEntity', $schema );
		$this->assertCount( 1, $schema['mainEntity'], 'Only valid items should be included' );
	}

	/**
	 * Test to_json returns valid JSON string
	 *
	 * @return void
	 */
	public function test_to_json_returns_valid_json_string(): void {
		$graph = array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				$this->builder->build_website(),
				$this->builder->build_organization(),
			),
		);

		$json = $this->builder->to_json( $graph );

		$this->assertIsString( $json );
		$this->assertJson( $json );

		$decoded = json_decode( $json, true );
		$this->assertIsArray( $decoded );
		$this->assertEquals( 'https://schema.org', $decoded['@context'] );
		$this->assertArrayHasKey( '@graph', $decoded );
	}

	/**
	 * Test to_json uses correct flags
	 *
	 * @return void
	 */
	public function test_to_json_uses_correct_flags(): void {
		$graph = array(
			'url' => 'https://example.com/test',
			'unicode' => 'Tëst Ünïcödé',
		);

		$json = $this->builder->to_json( $graph );

		// Check that slashes are not escaped.
		$this->assertStringContainsString( 'https://example.com/test', $json );
		$this->assertStringNotContainsString( 'https:\/\/', $json );

		// Check that unicode is not escaped.
		$this->assertStringContainsString( 'Tëst Ünïcödé', $json );
	}

	/**
	 * Create a mock post object for testing
	 *
	 * @return \WP_Post Mock post object.
	 */
	private function create_mock_post(): \WP_Post {
		$post = new \stdClass();
		$post->ID = 1;
		$post->post_title = 'Test Post';
		$post->post_content = 'Test content';
		$post->post_excerpt = 'Test excerpt';
		$post->post_type = 'post';
		$post->post_status = 'publish';
		$post->post_author = 1;
		$post->post_date = '2024-01-01 12:00:00';
		$post->post_modified = '2024-01-02 12:00:00';

		return (object) $post;
	}
}
