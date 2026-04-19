<?php
/**
 * Tests for Meta_Resolver archive robots resolution
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Modules\Meta\Meta_Output;
use MeowSEO\Options;
use MeowSEO\Modules\Meta\Title_Patterns;
use PHPUnit\Framework\TestCase;

/**
 * Test Meta_Resolver archive robots resolution
 */
class MetaResolverArchiveRobotsTest extends TestCase {
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
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		
		$this->options  = new Options();
		$this->patterns = new Title_Patterns( $this->options );
		$this->resolver = new Meta_Resolver( $this->options, $this->patterns );
	}

	/**
	 * Test get_archive_robots returns global setting for author archives
	 */
	public function test_get_archive_robots_returns_global_setting_for_author() {
		// Set global author archive robots setting
		$this->options->set( 'robots_author_archive', array(
			'noindex'  => true,
			'nofollow' => false,
		) );

		$result = $this->resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_AUTHOR );

		$this->assertEquals( 'noindex, follow', $result );
	}

	/**
	 * Test get_archive_robots returns global setting for date archives
	 */
	public function test_get_archive_robots_returns_global_setting_for_date() {
		// Set global date archive robots setting
		$this->options->set( 'robots_date_archive', array(
			'noindex'  => true,
			'nofollow' => true,
		) );

		$result = $this->resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_DATE );

		$this->assertEquals( 'noindex, nofollow', $result );
	}

	/**
	 * Test get_archive_robots returns global setting for category archives
	 */
	public function test_get_archive_robots_returns_global_setting_for_category() {
		// Set global category archive robots setting
		$this->options->set( 'robots_category_archive', array(
			'noindex'  => false,
			'nofollow' => false,
		) );

		$result = $this->resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_CATEGORY );

		$this->assertEquals( 'index, follow', $result );
	}

	/**
	 * Test get_archive_robots returns global setting for tag archives
	 */
	public function test_get_archive_robots_returns_global_setting_for_tag() {
		// Set global tag archive robots setting
		$this->options->set( 'robots_tag_archive', array(
			'noindex'  => false,
			'nofollow' => true,
		) );

		$result = $this->resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_TAG );

		$this->assertEquals( 'index, nofollow', $result );
	}

	/**
	 * Test get_archive_robots returns global setting for search results
	 */
	public function test_get_archive_robots_returns_global_setting_for_search() {
		// Set global search results robots setting
		$this->options->set( 'robots_search_results', array(
			'noindex'  => true,
			'nofollow' => false,
		) );

		$result = $this->resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_SEARCH );

		$this->assertEquals( 'noindex, follow', $result );
	}

	/**
	 * Test get_archive_robots returns global setting for attachment pages
	 */
	public function test_get_archive_robots_returns_global_setting_for_attachment() {
		// Set global attachment robots setting
		$this->options->set( 'robots_attachment', array(
			'noindex'  => true,
			'nofollow' => true,
		) );

		$result = $this->resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_ATTACHMENT );

		$this->assertEquals( 'noindex, nofollow', $result );
	}

	/**
	 * Test get_archive_robots formats output as comma-separated string
	 */
	public function test_get_archive_robots_formats_as_comma_separated_string() {
		// Set global author archive robots setting
		$this->options->set( 'robots_author_archive', array(
			'noindex'  => false,
			'nofollow' => false,
		) );

		$result = $this->resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_AUTHOR );

		// Verify format: "directive, directive"
		$this->assertMatchesRegularExpression( '/^[a-z]+, [a-z]+$/', $result );
		$this->assertEquals( 'index, follow', $result );
	}

	/**
	 * Test get_archive_robots uses default values when setting not found
	 */
	public function test_get_archive_robots_uses_defaults_when_setting_not_found() {
		// Don't set any robots setting for author archives
		// Should use defaults: noindex=false, nofollow=false

		$result = $this->resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_AUTHOR );

		$this->assertEquals( 'index, follow', $result );
	}
}
