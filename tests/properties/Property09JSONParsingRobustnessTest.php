<?php
/**
 * Property-Based Tests for JSON Parsing Robustness
 *
 * Property 9: JSON Parsing Robustness
 * Validates: Requirements 33.1, 33.2, 33.3, 33.4, 33.5
 *
 * This test uses property-based testing (eris/eris) to verify that the JSON parsing
 * mechanism correctly:
 * 1. Parses valid JSON with required SEO fields
 * 2. Returns WP_Error for invalid JSON without crashing
 * 3. Handles missing required fields appropriately
 * 4. Sanitizes all extracted values
 * 5. Removes markdown code blocks if present
 *
 * @package MeowSEO\Tests
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Modules\AI\AI_Generator;
use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Options;

/**
 * JSON Parsing Robustness property-based test case
 *
 * @since 1.0.0
 */
class Property09JSONParsingRobustnessTest extends TestCase {
	use TestTrait;

	/**
	 * Generator instance for testing.
	 *
	 * @var AI_Generator|null
	 */
	private $generator;

	/**
	 * Reflection for accessing private parse_json_response method.
	 *
	 * @var \ReflectionMethod|null
	 */
	private $parse_method;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Ensure AUTH_KEY is defined for encryption.
		if ( ! defined( 'AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'test-auth-key-for-unit-tests-32-chars!' );
		}

		// Create Options mock.
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturnCallback( function ( $key, $default = null ) {
			$defaults = [
				'ai_output_language'      => 'auto',
				'ai_custom_instructions'  => '',
				'ai_image_style'          => 'professional',
				'ai_image_color_palette'  => '',
				'ai_overwrite_behavior'   => 'ask',
			];
			return $defaults[ $key ] ?? $default;
		} );

		// Create Provider Manager mock.
		$provider_manager = $this->createMock( AI_Provider_Manager::class );

		// Create Generator instance.
		$this->generator = new AI_Generator( $provider_manager, $options );

		// Use reflection to access private parse_json_response method.
		$reflection = new \ReflectionClass( AI_Generator::class );
		$this->parse_method = $reflection->getMethod( 'parse_json_response' );
		$this->parse_method->setAccessible( true );
	}

	/**
	 * Property 9: JSON Parsing Robustness - Valid JSON parses correctly
	 *
	 * For any valid JSON object with required SEO fields, parsing SHALL succeed
	 * and return all fields.
	 *
	 * **Validates: Requirements 33.1, 33.3, 33.5**
	 *
	 * @return void
	 */
	public function test_valid_json_parses_correctly(): void {
		$this->forAll(
			Generators::string(),    // seo_title
			Generators::string(),    // seo_description
			Generators::string()     // focus_keyword
		)
		->then(
			function ( string $seo_title, string $seo_description, string $focus_keyword ) {
				// Skip empty strings as they would fail required field validation.
				if ( empty( $seo_title ) || empty( $seo_description ) || empty( $focus_keyword ) ) {
					return;
				}

				// Create valid JSON with required fields.
				$json = json_encode( [
					'seo_title'       => $seo_title,
					'seo_description' => $seo_description,
					'focus_keyword'   => $focus_keyword,
				] );

				// Parse the JSON.
				$result = $this->parse_method->invoke( $this->generator, $json );

				// Verify parsing succeeded (not WP_Error).
				$this->assertNotInstanceOf(
					\WP_Error::class,
					$result,
					'Valid JSON should parse successfully'
				);

				// Verify all required fields are present.
				$this->assertArrayHasKey( 'seo_title', $result );
				$this->assertArrayHasKey( 'seo_description', $result );
				$this->assertArrayHasKey( 'focus_keyword', $result );

				// Verify values are sanitized (sanitize_text_field removes HTML tags).
				$this->assertEquals(
					sanitize_text_field( $seo_title ),
					$result['seo_title'],
					'Values should be sanitized'
				);
			}
		);
	}

	/**
	 * Property 9: JSON Parsing Robustness - Invalid JSON returns WP_Error
	 *
	 * For any invalid JSON string, parsing SHALL return a WP_Error without crashing.
	 *
	 * **Validates: Requirements 33.1, 33.2**
	 *
	 * @return void
	 */
	public function test_invalid_json_returns_wp_error(): void {
		$this->forAll(
			Generators::oneOf(
				Generators::constant( '' ),
				Generators::constant( '{invalid}' ),
				Generators::constant( 'not json at all' ),
				Generators::constant( '{"unclosed": ' ),
				Generators::constant( 'random text' ),
				Generators::constant( 'null' ),
				Generators::constant( '[]' ),
				Generators::constant( '"just a string"' )
			)
		)
		->then(
			function ( string $invalid_json ) {
				// Parse the invalid JSON.
				$result = $this->parse_method->invoke( $this->generator, $invalid_json );

				// Verify WP_Error is returned.
				$this->assertInstanceOf(
					\WP_Error::class,
					$result,
					"Invalid JSON should return WP_Error: '{$invalid_json}'"
				);

				// Verify error code.
				$this->assertContains(
					$result->get_error_code(),
					[ 'json_parse_error', 'missing_field' ],
					'Error should have appropriate error code'
				);
			}
		);
	}

	/**
	 * Property 9: JSON Parsing Robustness - Missing required fields are handled
	 *
	 * For any JSON missing required fields, parsing SHALL return a WP_Error
	 * indicating which fields are missing.
	 *
	 * **Validates: Requirements 33.3**
	 *
	 * @return void
	 */
	public function test_missing_required_fields_are_handled(): void {
		$this->forAll(
			Generators::elements( [
				// Missing seo_title.
				[ 'seo_description' => 'Test description', 'focus_keyword' => 'test' ],
				// Missing seo_description.
				[ 'seo_title' => 'Test Title', 'focus_keyword' => 'test' ],
				// Missing focus_keyword.
				[ 'seo_title' => 'Test Title', 'seo_description' => 'Test description' ],
				// Missing all required fields.
				[ 'og_title' => 'OG Title', 'og_description' => 'OG Description' ],
				// Empty required fields.
				[ 'seo_title' => '', 'seo_description' => '', 'focus_keyword' => '' ],
			] )
		)
		->then(
			function ( array $incomplete_data ) {
				$json = json_encode( $incomplete_data );

				// Parse the incomplete JSON.
				$result = $this->parse_method->invoke( $this->generator, $json );

				// Verify WP_Error is returned.
				$this->assertInstanceOf(
					\WP_Error::class,
					$result,
					'JSON with missing required fields should return WP_Error'
				);

				// Verify error code is missing_field.
				$this->assertEquals(
					'missing_field',
					$result->get_error_code(),
					'Error code should be missing_field'
				);

				// Verify error message mentions missing fields.
				$error_message = $result->get_error_message();
				$this->assertStringContainsString(
					'Missing required fields',
					$error_message,
					'Error message should mention missing fields'
				);
			}
		);
	}

	/**
	 * Property 9: JSON Parsing Robustness - Markdown code blocks are removed
	 *
	 * For any valid JSON wrapped in markdown code blocks, parsing SHALL
	 * remove the code blocks and parse the JSON correctly.
	 *
	 * **Validates: Requirements 33.1**
	 *
	 * @return void
	 */
	public function test_markdown_code_blocks_are_removed(): void {
		$this->forAll(
			Generators::elements( [
				'Test Title',
				'Sample SEO Title',
				'Another Test',
				'Unique Title 123',
				'My Article Title',
			] ),    // seo_title
			Generators::elements( [
				'Test description that is long enough to meet requirements',
				'Sample SEO description for testing purposes here',
				'Another test description with sufficient length',
			] ),    // seo_description
			Generators::elements( [
				'test',
				'sample',
				'keyword',
				'seo',
			] )     // focus_keyword
		)
		->then(
			function ( string $seo_title, string $seo_description, string $focus_keyword ) {
				// Create valid JSON.
				$json = json_encode( [
					'seo_title'       => $seo_title,
					'seo_description' => $seo_description,
					'focus_keyword'   => $focus_keyword,
				] );

				// Wrap in various markdown code block formats that AI providers typically return.
				$markdown_variants = [
					"```json\n{$json}\n```",
					"```\n{$json}\n```",
					"```json\r\n{$json}\r\n```",
					"```json\n\n{$json}\n\n```",
				];

				foreach ( $markdown_variants as $markdown_json ) {
					// Parse the markdown-wrapped JSON.
					$result = $this->parse_method->invoke( $this->generator, $markdown_json );

					// Verify parsing succeeded.
					$this->assertNotInstanceOf(
						\WP_Error::class,
						$result,
						"JSON wrapped in markdown should parse successfully"
					);

					// Verify fields are present.
					$this->assertArrayHasKey( 'seo_title', $result );
					$this->assertArrayHasKey( 'seo_description', $result );
					$this->assertArrayHasKey( 'focus_keyword', $result );
				}
			}
		);
	}

	/**
	 * Property 9: JSON Parsing Robustness - All values are sanitized
	 *
	 * For any valid JSON with potentially unsafe values, parsing SHALL
	 * sanitize all extracted text values.
	 *
	 * **Validates: Requirements 33.5**
	 *
	 * @return void
	 */
	public function test_all_values_are_sanitized(): void {
		$this->forAll(
			Generators::oneOf(
				Generators::constant( '<script>alert("xss")</script>Title' ),
				Generators::constant( 'Title with <b>HTML</b>' ),
				Generators::constant( "Title with\nnewlines" ),
				Generators::constant( 'Title with   extra   spaces' ),
				Generators::constant( 'Normal Title' ),
				Generators::string()
			),
			Generators::oneOf(
				Generators::constant( '<p>Description</p>' ),
				Generators::constant( 'Normal description' ),
				Generators::string()
			),
			Generators::oneOf(
				Generators::constant( '<a href="evil">keyword</a>' ),
				Generators::constant( 'normal-keyword' ),
				Generators::string()
			)
		)
		->then(
			function ( string $seo_title, string $seo_description, string $focus_keyword ) {
				// Skip empty strings as they would fail required field validation.
				if ( empty( $seo_title ) || empty( $seo_description ) || empty( $focus_keyword ) ) {
					return;
				}

				// Create JSON with potentially unsafe values.
				$json = json_encode( [
					'seo_title'       => $seo_title,
					'seo_description' => $seo_description,
					'focus_keyword'   => $focus_keyword,
				] );

				// Parse the JSON.
				$result = $this->parse_method->invoke( $this->generator, $json );

				// Skip if parsing failed (empty values).
				if ( is_wp_error( $result ) ) {
					return;
				}

				// Verify all values are sanitized.
				$this->assertEquals(
					sanitize_text_field( $seo_title ),
					$result['seo_title'],
					'seo_title should be sanitized'
				);

				$this->assertEquals(
					sanitize_text_field( $seo_description ),
					$result['seo_description'],
					'seo_description should be sanitized'
				);

				$this->assertEquals(
					sanitize_text_field( $focus_keyword ),
					$result['focus_keyword'],
					'focus_keyword should be sanitized'
				);

				// Verify no HTML tags remain (sanitize_text_field strips them).
				$this->assertStringNotContainsString(
					'<script>',
					$result['seo_title'],
					'Script tags should be removed'
				);
			}
		);
	}

	/**
	 * Property 9: JSON Parsing Robustness - Optional fields are preserved
	 *
	 * For any valid JSON with optional fields, parsing SHALL preserve
	 * all optional fields in the result.
	 *
	 * **Validates: Requirements 33.1, 33.5**
	 *
	 * @return void
	 */
	public function test_optional_fields_are_preserved(): void {
		$this->forAll(
			Generators::string(),    // seo_title
			Generators::string(),    // seo_description
			Generators::string(),    // focus_keyword
			Generators::string(),    // og_title
			Generators::string(),    // og_description
			Generators::string(),    // twitter_title
			Generators::elements( [ 'Article', 'FAQPage', 'HowTo', 'LocalBusiness', 'Product' ] ) // schema_type
		)
		->then(
			function (
				string $seo_title,
				string $seo_description,
				string $focus_keyword,
				string $og_title,
				string $og_description,
				string $twitter_title,
				string $schema_type
			) {
				// Skip empty strings as they would fail required field validation.
				if ( empty( $seo_title ) || empty( $seo_description ) || empty( $focus_keyword ) ) {
					return;
				}

				// Create JSON with required and optional fields.
				$json = json_encode( [
					'seo_title'          => $seo_title,
					'seo_description'    => $seo_description,
					'focus_keyword'      => $focus_keyword,
					'og_title'           => $og_title,
					'og_description'     => $og_description,
					'twitter_title'      => $twitter_title,
					'schema_type'        => $schema_type,
					'secondary_keywords' => [ 'keyword1', 'keyword2', 'keyword3' ],
				] );

				// Parse the JSON.
				$result = $this->parse_method->invoke( $this->generator, $json );

				// Verify parsing succeeded.
				$this->assertNotInstanceOf(
					\WP_Error::class,
					$result,
					'Valid JSON with optional fields should parse successfully'
				);

				// Verify required fields are present.
				$this->assertArrayHasKey( 'seo_title', $result );
				$this->assertArrayHasKey( 'seo_description', $result );
				$this->assertArrayHasKey( 'focus_keyword', $result );

				// Verify optional fields are preserved.
				$this->assertArrayHasKey( 'og_title', $result );
				$this->assertArrayHasKey( 'og_description', $result );
				$this->assertArrayHasKey( 'twitter_title', $result );
				$this->assertArrayHasKey( 'schema_type', $result );
				$this->assertArrayHasKey( 'secondary_keywords', $result );

				// Verify secondary_keywords is an array.
				$this->assertIsArray(
					$result['secondary_keywords'],
					'secondary_keywords should be an array'
				);

				// Verify all values are sanitized.
				$this->assertEquals(
					sanitize_text_field( $og_title ),
					$result['og_title'],
					'og_title should be sanitized'
				);

				$this->assertEquals(
					sanitize_text_field( $schema_type ),
					$result['schema_type'],
					'schema_type should be sanitized'
				);
			}
		);
	}

	/**
	 * Property 9: JSON Parsing Robustness - Empty JSON returns error
	 *
	 * For any empty or whitespace-only string, parsing SHALL return a WP_Error.
	 *
	 * **Validates: Requirements 33.2**
	 *
	 * @return void
	 */
	public function test_empty_json_returns_error(): void {
		$this->forAll(
			Generators::oneOf(
				Generators::constant( '' ),
				Generators::constant( '   ' ),
				Generators::constant( "\n" ),
				Generators::constant( "\t" ),
				Generators::constant( "  \n  \t  " )
			)
		)
		->then(
			function ( string $empty_input ) {
				// Parse the empty input.
				$result = $this->parse_method->invoke( $this->generator, $empty_input );

				// Verify WP_Error is returned.
				$this->assertInstanceOf(
					\WP_Error::class,
					$result,
					'Empty input should return WP_Error'
				);

				// Verify error code.
				$this->assertEquals(
					'json_parse_error',
					$result->get_error_code(),
					'Error code should be json_parse_error'
				);
			}
		);
	}

	/**
	 * Property 9: JSON Parsing Robustness - Unicode is handled correctly
	 *
	 * For any valid JSON with Unicode characters, parsing SHALL handle
	 * the Unicode correctly without corruption.
	 *
	 * **Validates: Requirements 33.1, 33.5**
	 *
	 * @return void
	 */
	public function test_unicode_is_handled_correctly(): void {
		$this->forAll(
			Generators::oneOf(
				Generators::constant( '日本語タイトル' ),
				Generators::constant( 'العنوان العربي' ),
				Generators::constant( 'Заголовок на русском' ),
				Generators::constant( 'Título en español' ),
				Generators::constant( '中文标题' ),
				Generators::constant( 'Indonesian title' ),
				Generators::string()
			),
			Generators::oneOf(
				Generators::constant( '日本語の説明文です。' ),
				Generators::constant( 'وصف باللغة العربية' ),
				Generators::constant( 'Описание на русском языке' ),
				Generators::string()
			),
			Generators::string()
		)
		->then(
			function ( string $seo_title, string $seo_description, string $focus_keyword ) {
				// Skip empty strings as they would fail required field validation.
				if ( empty( $seo_title ) || empty( $seo_description ) || empty( $focus_keyword ) ) {
					return;
				}

				// Create JSON with Unicode.
				$json = json_encode( [
					'seo_title'       => $seo_title,
					'seo_description' => $seo_description,
					'focus_keyword'   => $focus_keyword,
				] );

				// Parse the JSON.
				$result = $this->parse_method->invoke( $this->generator, $json );

				// Verify parsing succeeded.
				$this->assertNotInstanceOf(
					\WP_Error::class,
					$result,
					'JSON with Unicode should parse successfully'
				);

				// Verify Unicode is preserved (after sanitization).
				$this->assertEquals(
					sanitize_text_field( $seo_title ),
					$result['seo_title'],
					'Unicode should be preserved in seo_title'
				);

				$this->assertEquals(
					sanitize_text_field( $seo_description ),
					$result['seo_description'],
					'Unicode should be preserved in seo_description'
				);
			}
		);
	}
}
