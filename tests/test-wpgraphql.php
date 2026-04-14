<?php
/**
 * WPGraphQL Integration Tests
 *
 * Tests for the WPGraphQL integration layer.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;

/**
 * WPGraphQL integration test case
 *
 * @since 1.0.0
 */
class Test_WPGraphQL extends TestCase {

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Note: These tests require WordPress test framework and WPGraphQL for full functionality.
		// For now, they serve as structural tests.
		$this->markTestSkipped( 'WPGraphQL tests require WordPress test framework and WPGraphQL plugin' );
	}

	/**
	 * Test that MeowSeoData type is registered
	 *
	 * @return void
	 */
	public function test_meowseo_data_type_registered(): void {
		// Get WPGraphQL type registry.
		$type_registry = \WPGraphQL::get_type_registry();

		// Assert MeowSeoData type is registered.
		$this->assertNotNull( $type_registry->get_type( 'MeowSeoData' ) );
	}

	/**
	 * Test that MeowSeoOpenGraph type is registered
	 *
	 * @return void
	 */
	public function test_meowseo_opengraph_type_registered(): void {
		// Get WPGraphQL type registry.
		$type_registry = \WPGraphQL::get_type_registry();

		// Assert MeowSeoOpenGraph type is registered.
		$this->assertNotNull( $type_registry->get_type( 'MeowSeoOpenGraph' ) );
	}

	/**
	 * Test that MeowSeoTwitterCard type is registered
	 *
	 * @return void
	 */
	public function test_meowseo_twitter_card_type_registered(): void {
		// Get WPGraphQL type registry.
		$type_registry = \WPGraphQL::get_type_registry();

		// Assert MeowSeoTwitterCard type is registered.
		$this->assertNotNull( $type_registry->get_type( 'MeowSeoTwitterCard' ) );
	}

	/**
	 * Test that seo field is registered on Post type
	 *
	 * @return void
	 */
	public function test_seo_field_registered_on_post_type(): void {
		// Execute GraphQL query.
		$query = '
			query GetPost($id: ID!) {
				post(id: $id, idType: DATABASE_ID) {
					id
					title
					seo {
						title
						description
						robots
						canonical
					}
				}
			}
		';

		$variables = array(
			'id' => $this->post_id,
		);

		$result = graphql(
			array(
				'query'     => $query,
				'variables' => $variables,
			)
		);

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $result );

		// Assert data is returned.
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'post', $result['data'] );
		$this->assertArrayHasKey( 'seo', $result['data']['post'] );

		// Assert SEO data is correct.
		$seo = $result['data']['post']['seo'];
		$this->assertEquals( 'Test SEO Title', $seo['title'] );
		$this->assertEquals( 'Test SEO Description', $seo['description'] );
		$this->assertEquals( 'index,follow', $seo['robots'] );
		$this->assertEquals( 'https://example.com/test', $seo['canonical'] );
	}

	/**
	 * Test that seo field includes openGraph sub-field
	 *
	 * @return void
	 */
	public function test_seo_field_includes_opengraph(): void {
		// Execute GraphQL query.
		$query = '
			query GetPost($id: ID!) {
				post(id: $id, idType: DATABASE_ID) {
					id
					seo {
						openGraph {
							title
							description
							image
							type
							url
						}
					}
				}
			}
		';

		$variables = array(
			'id' => $this->post_id,
		);

		$result = graphql(
			array(
				'query'     => $query,
				'variables' => $variables,
			)
		);

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $result );

		// Assert openGraph data is returned.
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'post', $result['data'] );
		$this->assertArrayHasKey( 'seo', $result['data']['post'] );
		$this->assertArrayHasKey( 'openGraph', $result['data']['post']['seo'] );
	}

	/**
	 * Test that seo field includes twitterCard sub-field
	 *
	 * @return void
	 */
	public function test_seo_field_includes_twitter_card(): void {
		// Execute GraphQL query.
		$query = '
			query GetPost($id: ID!) {
				post(id: $id, idType: DATABASE_ID) {
					id
					seo {
						twitterCard {
							card
							title
							description
							image
						}
					}
				}
			}
		';

		$variables = array(
			'id' => $this->post_id,
		);

		$result = graphql(
			array(
				'query'     => $query,
				'variables' => $variables,
			)
		);

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $result );

		// Assert twitterCard data is returned.
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'post', $result['data'] );
		$this->assertArrayHasKey( 'seo', $result['data']['post'] );
		$this->assertArrayHasKey( 'twitterCard', $result['data']['post']['seo'] );
	}

	/**
	 * Test that seo field includes schemaJsonLd sub-field
	 *
	 * @return void
	 */
	public function test_seo_field_includes_schema_json_ld(): void {
		// Execute GraphQL query.
		$query = '
			query GetPost($id: ID!) {
				post(id: $id, idType: DATABASE_ID) {
					id
					seo {
						schemaJsonLd
					}
				}
			}
		';

		$variables = array(
			'id' => $this->post_id,
		);

		$result = graphql(
			array(
				'query'     => $query,
				'variables' => $variables,
			)
		);

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $result );

		// Assert schemaJsonLd data is returned.
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'post', $result['data'] );
		$this->assertArrayHasKey( 'seo', $result['data']['post'] );
		$this->assertArrayHasKey( 'schemaJsonLd', $result['data']['post']['seo'] );
	}
}
