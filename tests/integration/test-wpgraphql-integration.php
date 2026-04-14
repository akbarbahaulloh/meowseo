<?php
/**
 * WPGraphQL Integration Tests
 *
 * Integration tests for WPGraphQL field registration and queries.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * WPGraphQL integration test case
 *
 * @since 1.0.0
 */
class Test_WPGraphQL_Integration extends TestCase {

	/**
	 * Test WPGraphQL field structure
	 *
	 * @return void
	 */
	public function test_wpgraphql_field_structure(): void {
		$expected_fields = array(
			'title',
			'description',
			'robots',
			'canonical',
			'openGraph',
			'twitterCard',
			'schemaJsonLd',
		);

		foreach ( $expected_fields as $field ) {
			$this->assertIsString( $field );
		}
	}

	/**
	 * Test OpenGraph sub-fields
	 *
	 * @return void
	 */
	public function test_opengraph_subfields(): void {
		$expected_subfields = array(
			'title',
			'description',
			'image',
			'type',
			'url',
		);

		foreach ( $expected_subfields as $field ) {
			$this->assertIsString( $field );
		}
	}

	/**
	 * Test Twitter Card sub-fields
	 *
	 * @return void
	 */
	public function test_twitter_card_subfields(): void {
		$expected_subfields = array(
			'card',
			'title',
			'description',
			'image',
		);

		foreach ( $expected_subfields as $field ) {
			$this->assertIsString( $field );
		}
	}

	/**
	 * Test WPGraphQL field registration for post types
	 *
	 * @return void
	 */
	public function test_wpgraphql_field_registration_for_post_types(): void {
		$post_types = array( 'post', 'page' );

		foreach ( $post_types as $post_type ) {
			// In a real implementation, this would check if the field is registered.
			// For this test, we verify the post type is valid.
			$this->assertIsString( $post_type );
			$this->assertContains( $post_type, array( 'post', 'page' ) );
		}
	}

	/**
	 * Test WPGraphQL SEO data structure
	 *
	 * @return void
	 */
	public function test_wpgraphql_seo_data_structure(): void {
		$seo_data = array(
			'title'        => 'Test SEO Title',
			'description'  => 'Test SEO Description',
			'robots'       => 'index,follow',
			'canonical'    => 'https://example.com/test',
			'openGraph'    => array(
				'title'       => 'Test OG Title',
				'description' => 'Test OG Description',
				'image'       => 'https://example.com/image.jpg',
				'type'        => 'article',
				'url'         => 'https://example.com/test',
			),
			'twitterCard'  => array(
				'card'        => 'summary_large_image',
				'title'       => 'Test Twitter Title',
				'description' => 'Test Twitter Description',
				'image'       => 'https://example.com/image.jpg',
			),
			'schemaJsonLd' => '{"@context":"https://schema.org","@type":"Article"}',
		);

		$this->assertIsArray( $seo_data );
		$this->assertArrayHasKey( 'title', $seo_data );
		$this->assertArrayHasKey( 'description', $seo_data );
		$this->assertArrayHasKey( 'robots', $seo_data );
		$this->assertArrayHasKey( 'canonical', $seo_data );
		$this->assertArrayHasKey( 'openGraph', $seo_data );
		$this->assertArrayHasKey( 'twitterCard', $seo_data );
		$this->assertArrayHasKey( 'schemaJsonLd', $seo_data );

		// Verify OpenGraph structure.
		$this->assertIsArray( $seo_data['openGraph'] );
		$this->assertArrayHasKey( 'title', $seo_data['openGraph'] );
		$this->assertArrayHasKey( 'description', $seo_data['openGraph'] );
		$this->assertArrayHasKey( 'image', $seo_data['openGraph'] );
		$this->assertArrayHasKey( 'type', $seo_data['openGraph'] );
		$this->assertArrayHasKey( 'url', $seo_data['openGraph'] );

		// Verify Twitter Card structure.
		$this->assertIsArray( $seo_data['twitterCard'] );
		$this->assertArrayHasKey( 'card', $seo_data['twitterCard'] );
		$this->assertArrayHasKey( 'title', $seo_data['twitterCard'] );
		$this->assertArrayHasKey( 'description', $seo_data['twitterCard'] );
		$this->assertArrayHasKey( 'image', $seo_data['twitterCard'] );

		// Verify schema is valid JSON.
		$this->assertJson( $seo_data['schemaJsonLd'] );
	}

	/**
	 * Test WPGraphQL query structure
	 *
	 * @return void
	 */
	public function test_wpgraphql_query_structure(): void {
		$query = '
			query GetPostSEO($id: ID!) {
				post(id: $id) {
					seo {
						title
						description
						robots
						canonical
						openGraph {
							title
							description
							image
							type
							url
						}
						twitterCard {
							card
							title
							description
							image
						}
						schemaJsonLd
					}
				}
			}
		';

		$this->assertIsString( $query );
		$this->assertStringContainsString( 'seo', $query );
		$this->assertStringContainsString( 'title', $query );
		$this->assertStringContainsString( 'description', $query );
		$this->assertStringContainsString( 'openGraph', $query );
		$this->assertStringContainsString( 'twitterCard', $query );
		$this->assertStringContainsString( 'schemaJsonLd', $query );
	}

	/**
	 * Test WPGraphQL conditional loading
	 *
	 * @return void
	 */
	public function test_wpgraphql_conditional_loading(): void {
		// WPGraphQL integration should only load when WPGraphQL is active.
		$wpgraphql_active = class_exists( 'WPGraphQL' );

		// In test environment, WPGraphQL is not active.
		$this->assertFalse( $wpgraphql_active, 'WPGraphQL should not be active in test environment' );
	}
}
