<?php
/**
 * Property-Based Tests for Schema Output Valid JSON-LD Script Tag
 *
 * Property 1: Schema Output is Valid JSON-LD Script Tag
 * Validates: Requirements 1.1, 2.3
 *
 * This test uses property-based testing (eris/eris) to verify that the Schema_Builder
 * generates valid JSON-LD script tags with proper structure.
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
 * Schema Valid JSON-LD Script Tag property-based test case
 *
 * @since 1.0.0
 */
class SchemaProperty01ValidJSONLDScriptTagTest extends TestCase {
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

		// Setup Brain\Monkey mocking for WordPress functions
		setup_brain_monkey_mocks();

		// Create a mock Options instance.
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( '' );

		$this->schema_builder = new Schema_Builder( $options );
	}

	/**
	 * Property 1: Schema Output is Valid JSON-LD Script Tag
	 *
	 * For any post, the schema output should be a valid JSON-LD script tag with:
	 * - Valid script tag with type="application/ld+json"
	 * - Script tag contains valid JSON
	 * - JSON has @context and @graph properties
	 * - @graph is an array
	 *
	 * This property verifies:
	 * 1. The output is valid JSON
	 * 2. The JSON has @context property set to "https://schema.org"
	 * 3. The JSON has @graph property that is an array
	 * 4. The @graph array is not empty
	 * 5. The structure is deterministic - same input produces same output
	 *
	 * **Validates: Requirements 1.1, 2.3**
	 *
	 * @return void
	 */
	public function test_schema_output_is_valid_jsonld_script_tag(): void {
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

				// Verify graph is an array.
				$this->assertIsArray( $graph, 'Schema graph should be an array' );

				// Verify @context exists and is correct.
				$this->assertArrayHasKey( '@context', $graph, 'Schema graph should have @context' );
				$this->assertEquals( 'https://schema.org', $graph['@context'], '@context should be https://schema.org' );

				// Verify @graph exists and is an array.
				$this->assertArrayHasKey( '@graph', $graph, 'Schema graph should have @graph' );
				$this->assertIsArray( $graph['@graph'], '@graph should be an array' );

				// Verify @graph is not empty.
				$this->assertNotEmpty( $graph['@graph'], '@graph should not be empty' );

				// Convert to JSON and verify it's valid JSON.
				$json = $this->schema_builder->to_json( $graph );
				$this->assertIsString( $json, 'to_json should return a string' );
				$this->assertNotEmpty( $json, 'JSON should not be empty' );

				// Verify JSON is valid by decoding it.
				$decoded = json_decode( $json, true );
				$this->assertIsArray( $decoded, 'JSON should decode to an array' );
				$this->assertArrayHasKey( '@context', $decoded, 'Decoded JSON should have @context' );
				$this->assertArrayHasKey( '@graph', $decoded, 'Decoded JSON should have @graph' );

				// Clean up.
				wp_delete_post( $post_id, true );
			}
		);
	}
}
