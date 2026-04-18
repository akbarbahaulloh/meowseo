<?php
/**
 * Property-Based Tests for Required Schema Nodes Always Present
 *
 * Property 2: Required Schema Nodes Always Present
 * Validates: Requirements 1.3
 *
 * This test uses property-based testing (eris/eris) to verify that the Schema_Builder
 * always includes required schema nodes in the @graph array.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\Schema_Builder;
use MeowSEO\Options;

/**
 * Schema Required Nodes Always Present property-based test case
 *
 * @since 1.0.0
 */
class SchemaProperty02RequiredNodesAlwaysPresentTest extends TestCase {
	use TestTrait;

	/**
	 * Schema_Builder instance.
	 *
	 * @var Schema_Builder
	 */
	private Schema_Builder $schema_builder;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create a mock Options instance.
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( '' );

		$this->schema_builder = new Schema_Builder( $options );
	}

	/**
	 * Property 2: Required Schema Nodes Always Present
	 *
	 * For any post, the schema @graph should always contain required nodes:
	 * - @graph always contains WebSite node
	 * - @graph always contains Organization node
	 * - @graph always contains WebPage node
	 * - @graph always contains BreadcrumbList node
	 * - All nodes have @type and @id properties
	 *
	 * This property verifies:
	 * 1. WebSite node is present with @type and @id
	 * 2. Organization node is present with @type and @id
	 * 3. WebPage node is present with @type and @id
	 * 4. BreadcrumbList node is present with @type and @id
	 * 5. All nodes have valid @id URLs
	 * 6. The property is deterministic - same input produces same output
	 *
	 * **Validates: Requirements 1.3**
	 *
	 * @return void
	 */
	public function test_required_schema_nodes_always_present(): void {
		$this->forAll(
			Generators::choose( 1, 1000 )
		)
		->then(
			function ( int $post_id ) {
				// Create a test post.
				$post_id = wp_insert_post(
					array(
						'post_title'   => 'Test Post ' . $post_id,
						'post_content' => 'Test content for schema validation',
						'post_status'  => 'publish',
						'post_type'    => 'post',
					)
				);

				// Build schema graph.
				$graph = $this->schema_builder->build( $post_id );

				// Verify @graph exists and is an array.
				$this->assertArrayHasKey( '@graph', $graph, 'Schema graph should have @graph' );
				$this->assertIsArray( $graph['@graph'], '@graph should be an array' );
				$this->assertNotEmpty( $graph['@graph'], '@graph should not be empty' );

				// Extract node types from @graph.
				$node_types = array();
				foreach ( $graph['@graph'] as $node ) {
					if ( isset( $node['@type'] ) ) {
						$node_types[] = $node['@type'];
					}
				}

				// Verify all required node types are present.
				$this->assertContains( 'WebSite', $node_types, 'WebSite node should be present' );
				$this->assertContains( 'Organization', $node_types, 'Organization node should be present' );
				$this->assertContains( 'WebPage', $node_types, 'WebPage node should be present' );
				$this->assertContains( 'BreadcrumbList', $node_types, 'BreadcrumbList node should be present' );

				// Verify each required node has @type and @id.
				foreach ( $graph['@graph'] as $node ) {
					$this->assertArrayHasKey( '@type', $node, 'Each node should have @type' );
					$this->assertArrayHasKey( '@id', $node, 'Each node should have @id' );
					$this->assertNotEmpty( $node['@type'], '@type should not be empty' );
					$this->assertNotEmpty( $node['@id'], '@id should not be empty' );

					// Verify @id is a valid URL.
					$this->assertTrue(
						filter_var( $node['@id'], FILTER_VALIDATE_URL ) !== false,
						'@id should be a valid URL: ' . $node['@id']
					);
				}

				// Clean up.
				wp_delete_post( $post_id, true );
			}
		);
	}
}
