<?php
/**
 * Global_SEO Test
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Global_SEO;
use MeowSEO\Modules\Meta\Title_Patterns;
use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Options;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test Global_SEO class
 */
class GlobalSEOTest extends TestCase {
	/**
	 * Global_SEO instance
	 *
	 * @var Global_SEO
	 */
	private $global_seo;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Title_Patterns instance
	 *
	 * @var Title_Patterns
	 */
	private $patterns;

	/**
	 * Meta_Resolver instance
	 *
	 * @var Meta_Resolver
	 */
	private $resolver;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Skip these tests - they require WordPress test framework with proper query context.
		$this->markTestSkipped( 'Global_SEO tests require WordPress test framework with query context' );

		$this->options    = new Options();
		$this->patterns   = new Title_Patterns( $this->options );
		$this->resolver   = new Meta_Resolver( $this->options, $this->patterns );
		$this->global_seo = new Global_SEO( $this->options, $this->patterns, $this->resolver );
	}

	/**
	 * Test get_current_page_type returns string
	 *
	 * @return void
	 */
	public function test_get_current_page_type_returns_string(): void {
		$page_type = $this->global_seo->get_current_page_type();

		$this->assertIsString( $page_type );
	}

	/**
	 * Test get_current_page_type returns valid page type
	 *
	 * @return void
	 */
	public function test_get_current_page_type_returns_valid_type(): void {
		$page_type = $this->global_seo->get_current_page_type();

		$valid_types = array(
			'homepage',
			'blog_index',
			'category',
			'tag',
			'custom_taxonomy',
			'author',
			'date_archive',
			'search',
			'404',
			'post_type_archive',
			'unknown',
		);

		$this->assertContains( $page_type, $valid_types );
	}

	/**
	 * Test get_title returns string
	 *
	 * @return void
	 */
	public function test_get_title_returns_string(): void {
		$title = $this->global_seo->get_title();

		$this->assertIsString( $title );
	}

	/**
	 * Test get_description returns string
	 *
	 * @return void
	 */
	public function test_get_description_returns_string(): void {
		$description = $this->global_seo->get_description();

		$this->assertIsString( $description );
	}

	/**
	 * Test get_robots returns string
	 *
	 * @return void
	 */
	public function test_get_robots_returns_string(): void {
		$robots = $this->global_seo->get_robots();

		$this->assertIsString( $robots );
	}

	/**
	 * Test get_robots contains Google Discover directives
	 *
	 * @return void
	 */
	public function test_get_robots_contains_google_discover_directives(): void {
		$robots = $this->global_seo->get_robots();

		$this->assertStringContainsString( 'max-image-preview:large', $robots );
		$this->assertStringContainsString( 'max-snippet:-1', $robots );
		$this->assertStringContainsString( 'max-video-preview:-1', $robots );
	}

	/**
	 * Test get_canonical returns string
	 *
	 * @return void
	 */
	public function test_get_canonical_returns_string(): void {
		$canonical = $this->global_seo->get_canonical();

		$this->assertIsString( $canonical );
	}

	/**
	 * Test get_canonical returns non-empty string
	 *
	 * @return void
	 */
	public function test_get_canonical_returns_non_empty(): void {
		$canonical = $this->global_seo->get_canonical();

		$this->assertNotEmpty( $canonical );
	}

	/**
	 * Test constructor accepts dependencies
	 *
	 * @return void
	 */
	public function test_constructor_accepts_dependencies(): void {
		$options    = new Options();
		$patterns   = new Title_Patterns( $options );
		$resolver   = new Meta_Resolver( $options, $patterns );
		$global_seo = new Global_SEO( $options, $patterns, $resolver );

		$this->assertInstanceOf( Global_SEO::class, $global_seo );
	}

	/**
	 * Test all public methods exist
	 *
	 * @return void
	 */
	public function test_all_public_methods_exist(): void {
		$this->assertTrue( method_exists( $this->global_seo, 'get_current_page_type' ) );
		$this->assertTrue( method_exists( $this->global_seo, 'get_title' ) );
		$this->assertTrue( method_exists( $this->global_seo, 'get_description' ) );
		$this->assertTrue( method_exists( $this->global_seo, 'get_robots' ) );
		$this->assertTrue( method_exists( $this->global_seo, 'get_canonical' ) );
	}
}
