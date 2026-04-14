<?php
/**
 * Meta_Resolver Test
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Modules\Meta\Title_Patterns;
use MeowSEO\Options;

/**
 * Test Meta_Resolver class
 */
class MetaResolverTest extends TestCase {
	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Title_Patterns instance
	 *
	 * @var Title_Patterns
	 */
	private Title_Patterns $patterns;

	/**
	 * Meta_Resolver instance
	 *
	 * @var Meta_Resolver
	 */
	private Meta_Resolver $resolver;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->options  = new Options();
		$this->patterns = new Title_Patterns( $this->options );
		$this->resolver = new Meta_Resolver( $this->options, $this->patterns );
	}

	/**
	 * Test title fallback chain with custom title
	 *
	 * @return void
	 */
	public function test_title_fallback_chain_with_custom_title(): void {
		$post_id = wp_insert_post(
			array(
				'post_title' => 'Test Post',
			)
		);

		update_post_meta( $post_id, '_meowseo_title', 'Custom SEO Title' );

		$result = $this->resolver->resolve_title( $post_id );

		$this->assertEquals( 'Custom SEO Title', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test title fallback chain without custom title
	 *
	 * @return void
	 */
	public function test_title_fallback_chain_without_custom_title(): void {
		$post_id = wp_insert_post(
			array(
				'post_title' => 'Test Post',
			)
		);

		$result = $this->resolver->resolve_title( $post_id );

		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( 'Test Post', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test title never empty
	 *
	 * @return void
	 */
	public function test_title_never_empty(): void {
		$post_id = wp_insert_post(
			array(
				'post_title' => '',
			)
		);

		$result = $this->resolver->resolve_title( $post_id );

		$this->assertNotEmpty( $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test description fallback chain with custom description
	 *
	 * @return void
	 */
	public function test_description_fallback_chain_with_custom(): void {
		$post_id = wp_insert_post( array() );

		update_post_meta( $post_id, '_meowseo_description', 'Custom meta description' );

		$result = $this->resolver->resolve_description( $post_id );

		$this->assertEquals( 'Custom meta description', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test description fallback to excerpt
	 *
	 * @return void
	 */
	public function test_description_fallback_to_excerpt(): void {
		$post_id = wp_insert_post(
			array(
				'post_excerpt' => 'This is the post excerpt.',
			)
		);

		$result = $this->resolver->resolve_description( $post_id );

		$this->assertStringContainsString( 'This is the post excerpt', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test description fallback to content
	 *
	 * @return void
	 */
	public function test_description_fallback_to_content(): void {
		$post_id = wp_insert_post(
			array(
				'post_content' => 'This is the post content with some text.',
			)
		);

		$result = $this->resolver->resolve_description( $post_id );

		$this->assertStringContainsString( 'This is the post content', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test description returns empty when no sources
	 *
	 * @return void
	 */
	public function test_description_empty_when_no_sources(): void {
		$post_id = wp_insert_post(
			array(
				'post_excerpt' => '',
				'post_content' => '',
			)
		);

		$result = $this->resolver->resolve_description( $post_id );

		$this->assertEmpty( $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test description truncation
	 *
	 * @return void
	 */
	public function test_description_truncation(): void {
		$long_content = str_repeat( 'This is a very long text. ', 50 );

		$post_id = wp_insert_post(
			array(
				'post_content' => $long_content,
			)
		);

		$result = $this->resolver->resolve_description( $post_id );

		$this->assertLessThanOrEqual( 163, mb_strlen( $result ) );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test OG image returns array
	 *
	 * @return void
	 */
	public function test_og_image_returns_array(): void {
		$post_id = wp_insert_post( array() );

		$result = $this->resolver->resolve_og_image( $post_id );

		$this->assertIsArray( $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test canonical fallback chain with custom canonical
	 *
	 * @return void
	 */
	public function test_canonical_with_custom(): void {
		$post_id = wp_insert_post( array() );

		update_post_meta( $post_id, '_meowseo_canonical', 'https://example.com/custom-url' );

		$result = $this->resolver->resolve_canonical( $post_id );

		$this->assertStringContainsString( 'example.com/custom-url', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test canonical never empty
	 *
	 * @return void
	 */
	public function test_canonical_never_empty(): void {
		$post_id = wp_insert_post( array() );

		$result = $this->resolver->resolve_canonical( $post_id );

		$this->assertNotEmpty( $result );
		$this->assertStringStartsWith( 'http', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test canonical strips pagination
	 *
	 * @return void
	 */
	public function test_canonical_strips_pagination(): void {
		$post_id = wp_insert_post( array() );

		update_post_meta( $post_id, '_meowseo_canonical', 'https://example.com/post/page/2/' );

		$result = $this->resolver->resolve_canonical( $post_id );

		$this->assertStringNotContainsString( '/page/', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test robots directive with defaults
	 *
	 * @return void
	 */
	public function test_robots_with_defaults(): void {
		$post_id = wp_insert_post( array() );

		$result = $this->resolver->resolve_robots( $post_id );

		$this->assertStringContainsString( 'index', $result );
		$this->assertStringContainsString( 'follow', $result );
		$this->assertStringContainsString( 'max-image-preview:large', $result );
		$this->assertStringContainsString( 'max-snippet:-1', $result );
		$this->assertStringContainsString( 'max-video-preview:-1', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test robots directive with noindex
	 *
	 * @return void
	 */
	public function test_robots_with_noindex(): void {
		$post_id = wp_insert_post( array() );

		update_post_meta( $post_id, '_meowseo_robots_noindex', true );

		$result = $this->resolver->resolve_robots( $post_id );

		$this->assertStringContainsString( 'noindex', $result );
		// Should not contain "index" as a standalone directive (only "noindex").
		$this->assertMatchesRegularExpression( '/^noindex,/', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test robots directive with nofollow
	 *
	 * @return void
	 */
	public function test_robots_with_nofollow(): void {
		$post_id = wp_insert_post( array() );

		update_post_meta( $post_id, '_meowseo_robots_nofollow', true );

		$result = $this->resolver->resolve_robots( $post_id );

		$this->assertStringContainsString( 'nofollow', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test Twitter title independence
	 *
	 * @return void
	 */
	public function test_twitter_title_independence(): void {
		$post_id = wp_insert_post(
			array(
				'post_title' => 'Regular Title',
			)
		);

		update_post_meta( $post_id, '_meowseo_twitter_title', 'Twitter Specific Title' );

		$result = $this->resolver->resolve_twitter_title( $post_id );

		$this->assertEquals( 'Twitter Specific Title', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test Twitter description independence
	 *
	 * @return void
	 */
	public function test_twitter_description_independence(): void {
		$post_id = wp_insert_post( array() );

		update_post_meta( $post_id, '_meowseo_twitter_description', 'Twitter Specific Description' );

		$result = $this->resolver->resolve_twitter_description( $post_id );

		$this->assertEquals( 'Twitter Specific Description', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test hreflang returns array
	 *
	 * @return void
	 */
	public function test_hreflang_returns_array(): void {
		$result = $this->resolver->get_hreflang_alternates();

		$this->assertIsArray( $result );
	}
}
