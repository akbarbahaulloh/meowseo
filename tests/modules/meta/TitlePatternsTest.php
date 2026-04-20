<?php
/**
 * Title_Patterns Test
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Title_Patterns;
use MeowSEO\Options;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test Title_Patterns class
 */
class TitlePatternsTest extends TestCase {
	/**
	 * Title_Patterns instance
	 *
	 * @var Title_Patterns
	 */
	private Title_Patterns $patterns;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		
		// Mock WordPress functions
		Functions\when( 'get_bloginfo' )->alias( function ( $show = '' ) {
			$values = array(
				'name'        => 'Test Site',
				'description' => 'Test Description',
				'language'    => 'en-US',
				'version'     => '6.4.2',
			);
			return $values[ $show ] ?? '';
		} );
		
		$this->options  = new Options();
		$this->patterns = new Title_Patterns( $this->options );
	}

	/**
	 * Tear down test
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test default patterns
	 *
	 * @return void
	 */
	public function test_default_patterns(): void {
		$defaults = $this->patterns->get_default_patterns();

		// Check all required page types are present.
		$this->assertArrayHasKey( 'post', $defaults );
		$this->assertArrayHasKey( 'page', $defaults );
		$this->assertArrayHasKey( 'homepage', $defaults );
		$this->assertArrayHasKey( 'category', $defaults );
		$this->assertArrayHasKey( 'tag', $defaults );
		$this->assertArrayHasKey( 'author', $defaults );
		$this->assertArrayHasKey( 'date', $defaults );
		$this->assertArrayHasKey( 'search', $defaults );
		$this->assertArrayHasKey( '404', $defaults );
		$this->assertArrayHasKey( 'attachment', $defaults );

		// Check specific patterns.
		$this->assertEquals( '{title} {sep} {site_name}', $defaults['post'] );
		$this->assertEquals( '{site_name} {sep} {tagline}', $defaults['homepage'] );
		$this->assertEquals( '{term_name} Archives {sep} {site_name}', $defaults['category'] );
	}

	/**
	 * Test variable replacement with all variables
	 *
	 * @return void
	 */
	public function test_variable_replacement(): void {
		$pattern = '{title} {sep} {site_name}';
		$context = array(
			'title' => 'Test Post',
		);

		$result = $this->patterns->resolve( $pattern, $context );

		// Should contain title.
		$this->assertStringContainsString( 'Test Post', $result );
		// Should contain separator.
		$this->assertStringContainsString( '|', $result );
		// Should contain site name.
		$this->assertStringContainsString( 'Test Site', $result );
	}

	/**
	 * Test missing variable handling
	 *
	 * @return void
	 */
	public function test_missing_variable_handling(): void {
		$pattern = '{title} {sep} {author_name}';
		$context = array(
			'title' => 'Test Post',
			// author_name is missing.
		);

		$result = $this->patterns->resolve( $pattern, $context );

		// Should contain title.
		$this->assertStringContainsString( 'Test Post', $result );
		// Missing variable should be replaced with empty string.
		$this->assertStringNotContainsString( '{author_name}', $result );
	}

	/**
	 * Test pagination variable conditional
	 *
	 * @return void
	 */
	public function test_pagination_variable_conditional(): void {
		$pattern = '{title} {page} {sep} {site_name}';

		// Non-paginated context.
		$context1 = array(
			'title' => 'Test Post',
		);
		$result1  = $this->patterns->resolve( $pattern, $context1 );
		$this->assertStringNotContainsString( 'Page', $result1 );

		// Paginated context.
		$context2 = array(
			'title'       => 'Test Post',
			'page_number' => 2,
		);
		$result2  = $this->patterns->resolve( $pattern, $context2 );
		$this->assertStringContainsString( 'Page 2', $result2 );
	}

	/**
	 * Test parser with valid patterns
	 *
	 * @return void
	 */
	public function test_parser_valid(): void {
		$pattern = '{title} {sep} {site_name}';
		$parsed  = $this->patterns->parse( $pattern );

		// Should return array.
		$this->assertIsArray( $parsed );

		// Should have 5 tokens: variable, literal, variable, literal, variable.
		$this->assertCount( 5, $parsed );

		// Check first token (variable).
		$this->assertEquals( 'variable', $parsed[0]['type'] );
		$this->assertEquals( 'title', $parsed[0]['name'] );

		// Check second token (literal).
		$this->assertEquals( 'literal', $parsed[1]['type'] );
		$this->assertEquals( ' ', $parsed[1]['value'] );

		// Check third token (variable).
		$this->assertEquals( 'variable', $parsed[2]['type'] );
		$this->assertEquals( 'sep', $parsed[2]['name'] );
	}

	/**
	 * Test parser with invalid patterns - unbalanced braces
	 *
	 * @return void
	 */
	public function test_parser_invalid_unbalanced_braces(): void {
		$pattern = '{title {sep} {site_name}';
		$parsed  = $this->patterns->parse( $pattern );

		// Should return error object.
		$this->assertIsObject( $parsed );
		$this->assertTrue( $parsed->error );
		$this->assertStringContainsString( 'Unbalanced', $parsed->message );
	}

	/**
	 * Test parser with invalid patterns - unsupported variable
	 *
	 * @return void
	 */
	public function test_parser_invalid_unsupported_variable(): void {
		$pattern = '{title} {sep} {invalid_var}';
		$parsed  = $this->patterns->parse( $pattern );

		// Should return error object.
		$this->assertIsObject( $parsed );
		$this->assertTrue( $parsed->error );
		$this->assertStringContainsString( 'Unsupported variable', $parsed->message );
	}

	/**
	 * Test round-trip: parse -> print -> parse
	 *
	 * @return void
	 */
	public function test_round_trip(): void {
		$pattern = '{title} {sep} {site_name}';

		// Parse.
		$parsed1 = $this->patterns->parse( $pattern );
		$this->assertIsArray( $parsed1 );

		// Print.
		$printed = $this->patterns->print( $parsed1 );
		$this->assertEquals( $pattern, $printed );

		// Parse again.
		$parsed2 = $this->patterns->parse( $printed );
		$this->assertIsArray( $parsed2 );

		// Should be equivalent.
		$this->assertEquals( $parsed1, $parsed2 );
	}

	/**
	 * Test validate method
	 *
	 * @return void
	 */
	public function test_validate(): void {
		// Valid pattern.
		$valid_result = $this->patterns->validate( '{title} {sep} {site_name}' );
		$this->assertTrue( $valid_result );

		// Invalid pattern.
		$invalid_result = $this->patterns->validate( '{title {sep}' );
		$this->assertIsObject( $invalid_result );
		$this->assertTrue( $invalid_result->error );
	}

	/**
	 * Test get_pattern_for_post_type
	 *
	 * @return void
	 */
	public function test_get_pattern_for_post_type(): void {
		// Should return default pattern for 'post'.
		$pattern = $this->patterns->get_pattern_for_post_type( 'post' );
		$this->assertEquals( '{title} {sep} {site_name}', $pattern );

		// Should fall back to 'post' pattern for unknown post type.
		$pattern = $this->patterns->get_pattern_for_post_type( 'custom_post_type' );
		$this->assertEquals( '{title} {sep} {site_name}', $pattern );
	}

	/**
	 * Test get_pattern_for_page_type
	 *
	 * @return void
	 */
	public function test_get_pattern_for_page_type(): void {
		// Should return default pattern for 'homepage'.
		$pattern = $this->patterns->get_pattern_for_page_type( 'homepage' );
		$this->assertEquals( '{site_name} {sep} {tagline}', $pattern );

		// Should fall back to default for unknown page type.
		$pattern = $this->patterns->get_pattern_for_page_type( 'unknown' );
		$this->assertEquals( '{title} {sep} {site_name}', $pattern );
	}
}
