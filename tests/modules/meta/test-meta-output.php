<?php
/**
 * Tests for Meta_Output class
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use MeowSEO\Modules\Meta\Meta_Output;
use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Modules\Meta\Title_Patterns;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Test_Meta_Output class
 */
class Test_Meta_Output extends TestCase {
	/**
	 * Test output_head_tags method structure
	 *
	 * @return void
	 */
	public function test_output_head_tags_structure(): void {
		// Create mock resolver.
		$options  = $this->createMock( Options::class );
		$patterns = $this->createMock( Title_Patterns::class );
		$resolver = $this->createMock( Meta_Resolver::class );

		// Configure resolver to return test values.
		$resolver->method( 'resolve_title' )->willReturn( 'Test Title' );
		$resolver->method( 'resolve_description' )->willReturn( 'Test Description' );
		$resolver->method( 'resolve_robots' )->willReturn( 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' );
		$resolver->method( 'resolve_canonical' )->willReturn( 'https://example.com/test' );
		$resolver->method( 'resolve_og_image' )->willReturn( array(
			'url'    => 'https://example.com/image.jpg',
			'width'  => 1200,
			'height' => 630,
		) );
		$resolver->method( 'resolve_twitter_title' )->willReturn( 'Test Twitter Title' );
		$resolver->method( 'resolve_twitter_description' )->willReturn( 'Test Twitter Description' );
		$resolver->method( 'resolve_twitter_image' )->willReturn( 'https://example.com/twitter.jpg' );
		$resolver->method( 'get_hreflang_alternates' )->willReturn( array() );

		// Create Meta_Output instance.
		$output = new Meta_Output( $resolver );

		// Capture output.
		ob_start();
		$output->output_head_tags();
		$html = ob_get_clean();

		// Verify title tag is present.
		$this->assertStringContainsString( '<title>Test Title</title>', $html );

		// Verify meta description is present.
		$this->assertStringContainsString( '<meta name="description" content="Test Description">', $html );

		// Verify robots meta tag is present.
		$this->assertStringContainsString( '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">', $html );

		// Verify canonical link is present.
		$this->assertStringContainsString( '<link rel="canonical" href="https://example.com/test">', $html );

		// Verify Open Graph tags are present.
		$this->assertStringContainsString( '<meta property="og:type" content="website">', $html );
		$this->assertStringContainsString( '<meta property="og:title" content="Test Title">', $html );
		$this->assertStringContainsString( '<meta property="og:description" content="Test Description">', $html );
		$this->assertStringContainsString( '<meta property="og:url" content="https://example.com/test">', $html );
		$this->assertStringContainsString( '<meta property="og:image" content="https://example.com/image.jpg">', $html );
		$this->assertStringContainsString( '<meta property="og:image:width" content="1200">', $html );
		$this->assertStringContainsString( '<meta property="og:image:height" content="630">', $html );

		// Verify Twitter Card tags are present.
		$this->assertStringContainsString( '<meta name="twitter:card" content="summary_large_image">', $html );
		$this->assertStringContainsString( '<meta name="twitter:title" content="Test Twitter Title">', $html );
		$this->assertStringContainsString( '<meta name="twitter:description" content="Test Twitter Description">', $html );
		$this->assertStringContainsString( '<meta name="twitter:image" content="https://example.com/twitter.jpg">', $html );
	}

	/**
	 * Test conditional description output
	 *
	 * @return void
	 */
	public function test_conditional_description_output(): void {
		// Create mock resolver that returns empty description.
		$resolver = $this->createMock( Meta_Resolver::class );
		$resolver->method( 'resolve_title' )->willReturn( 'Test Title' );
		$resolver->method( 'resolve_description' )->willReturn( '' );
		$resolver->method( 'resolve_robots' )->willReturn( 'index, follow' );
		$resolver->method( 'resolve_canonical' )->willReturn( 'https://example.com/test' );
		$resolver->method( 'resolve_og_image' )->willReturn( array() );
		$resolver->method( 'resolve_twitter_title' )->willReturn( '' );
		$resolver->method( 'resolve_twitter_description' )->willReturn( '' );
		$resolver->method( 'resolve_twitter_image' )->willReturn( '' );
		$resolver->method( 'get_hreflang_alternates' )->willReturn( array() );

		// Create Meta_Output instance.
		$output = new Meta_Output( $resolver );

		// Capture output.
		ob_start();
		$output->output_head_tags();
		$html = ob_get_clean();

		// Verify meta description is NOT present.
		$this->assertStringNotContainsString( '<meta name="description"', $html );
	}

	/**
	 * Test format_iso8601 helper
	 *
	 * @return void
	 */
	public function test_format_iso8601(): void {
		// Create mock resolver.
		$resolver = $this->createMock( Meta_Resolver::class );

		// Create Meta_Output instance.
		$output = new Meta_Output( $resolver );

		// Use reflection to test private method.
		$reflection = new \ReflectionClass( $output );
		$method     = $reflection->getMethod( 'format_iso8601' );
		$method->setAccessible( true );

		// Test valid date.
		$result = $method->invoke( $output, '2024-01-15 10:30:00' );
		$this->assertEquals( '2024-01-15T10:30:00+00:00', $result );

		// Test another valid date.
		$result = $method->invoke( $output, '2023-12-25 00:00:00' );
		$this->assertEquals( '2023-12-25T00:00:00+00:00', $result );
	}

	/**
	 * Test hreflang output when alternates exist
	 *
	 * @return void
	 */
	public function test_hreflang_output_with_alternates(): void {
		// Create mock resolver with hreflang alternates.
		$resolver = $this->createMock( Meta_Resolver::class );
		$resolver->method( 'resolve_title' )->willReturn( 'Test' );
		$resolver->method( 'resolve_description' )->willReturn( '' );
		$resolver->method( 'resolve_robots' )->willReturn( 'index' );
		$resolver->method( 'resolve_canonical' )->willReturn( 'https://example.com' );
		$resolver->method( 'resolve_og_image' )->willReturn( array() );
		$resolver->method( 'resolve_twitter_title' )->willReturn( '' );
		$resolver->method( 'resolve_twitter_description' )->willReturn( '' );
		$resolver->method( 'resolve_twitter_image' )->willReturn( '' );
		$resolver->method( 'get_hreflang_alternates' )->willReturn( array(
			'en' => 'https://example.com/en',
			'es' => 'https://example.com/es',
			'fr' => 'https://example.com/fr',
		) );

		// Create Meta_Output instance.
		$output = new Meta_Output( $resolver );

		// Capture output.
		ob_start();
		$output->output_head_tags();
		$html = ob_get_clean();

		// Verify hreflang links are present.
		$this->assertStringContainsString( '<link rel="alternate" hreflang="en" href="https://example.com/en">', $html );
		$this->assertStringContainsString( '<link rel="alternate" hreflang="es" href="https://example.com/es">', $html );
		$this->assertStringContainsString( '<link rel="alternate" hreflang="fr" href="https://example.com/fr">', $html );
	}

	/**
	 * Test hreflang not output when no alternates
	 *
	 * @return void
	 */
	public function test_hreflang_not_output_without_alternates(): void {
		// Create mock resolver with no hreflang alternates.
		$resolver = $this->createMock( Meta_Resolver::class );
		$resolver->method( 'resolve_title' )->willReturn( 'Test' );
		$resolver->method( 'resolve_description' )->willReturn( '' );
		$resolver->method( 'resolve_robots' )->willReturn( 'index' );
		$resolver->method( 'resolve_canonical' )->willReturn( 'https://example.com' );
		$resolver->method( 'resolve_og_image' )->willReturn( array() );
		$resolver->method( 'resolve_twitter_title' )->willReturn( '' );
		$resolver->method( 'resolve_twitter_description' )->willReturn( '' );
		$resolver->method( 'resolve_twitter_image' )->willReturn( '' );
		$resolver->method( 'get_hreflang_alternates' )->willReturn( array() );

		// Create Meta_Output instance.
		$output = new Meta_Output( $resolver );

		// Capture output.
		ob_start();
		$output->output_head_tags();
		$html = ob_get_clean();

		// Verify hreflang links are NOT present.
		$this->assertStringNotContainsString( '<link rel="alternate" hreflang=', $html );
	}
}
