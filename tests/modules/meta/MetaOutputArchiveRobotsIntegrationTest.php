<?php
/**
 * Integration tests for Meta_Output archive robots functionality
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
 * Test Meta_Output archive robots integration
 */
class MetaOutputArchiveRobotsIntegrationTest extends TestCase {
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
	 * Meta_Output instance
	 *
	 * @var Meta_Output
	 */
	private Meta_Output $output;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		
		$this->options  = new Options();
		$this->patterns = new Title_Patterns( $this->options );
		$this->resolver = new Meta_Resolver( $this->options, $this->patterns );
		$this->output   = new Meta_Output( $this->resolver );
	}

	/**
	 * Test that Meta_Output correctly integrates with Meta_Resolver for archives
	 */
	public function test_meta_output_integrates_with_archive_robots_resolution() {
		// Set global author archive robots setting
		$this->options->set( 'robots_author_archive', array(
			'noindex'  => true,
			'nofollow' => false,
		) );

		// Verify the resolver returns correct robots directives
		$robots = $this->resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_AUTHOR );
		$this->assertEquals( 'noindex, follow', $robots );
	}

	/**
	 * Test that all archive types have corresponding settings
	 */
	public function test_all_archive_types_have_settings() {
		$archive_types = array(
			Meta_Resolver::ARCHIVE_TYPE_AUTHOR,
			Meta_Resolver::ARCHIVE_TYPE_DATE,
			Meta_Resolver::ARCHIVE_TYPE_CATEGORY,
			Meta_Resolver::ARCHIVE_TYPE_TAG,
			Meta_Resolver::ARCHIVE_TYPE_SEARCH,
			Meta_Resolver::ARCHIVE_TYPE_ATTACHMENT,
		);

		foreach ( $archive_types as $archive_type ) {
			// Set robots setting for this archive type
			$this->options->set( 'robots_' . $archive_type, array(
				'noindex'  => true,
				'nofollow' => true,
			) );

			// Verify the resolver can retrieve it
			$robots = $this->resolver->get_archive_robots( $archive_type );
			$this->assertEquals( 'noindex, nofollow', $robots, "Failed for archive type: {$archive_type}" );
		}
	}

	/**
	 * Test that robots directives are formatted correctly
	 */
	public function test_robots_directives_formatted_correctly() {
		$test_cases = array(
			array(
				'setting' => array( 'noindex' => true, 'nofollow' => true ),
				'expected' => 'noindex, nofollow',
			),
			array(
				'setting' => array( 'noindex' => true, 'nofollow' => false ),
				'expected' => 'noindex, follow',
			),
			array(
				'setting' => array( 'noindex' => false, 'nofollow' => true ),
				'expected' => 'index, nofollow',
			),
			array(
				'setting' => array( 'noindex' => false, 'nofollow' => false ),
				'expected' => 'index, follow',
			),
		);

		foreach ( $test_cases as $test_case ) {
			$this->options->set( 'robots_author_archive', $test_case['setting'] );
			$robots = $this->resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_AUTHOR );
			$this->assertEquals( $test_case['expected'], $robots );
		}
	}

	/**
	 * Test that resolve_robots_for_archive method exists and is public
	 */
	public function test_resolve_robots_for_archive_method_exists() {
		$this->assertTrue(
			method_exists( $this->resolver, 'resolve_robots_for_archive' ),
			'resolve_robots_for_archive method should exist'
		);

		$reflection = new \ReflectionMethod( $this->resolver, 'resolve_robots_for_archive' );
		$this->assertTrue(
			$reflection->isPublic(),
			'resolve_robots_for_archive method should be public'
		);
	}

	/**
	 * Test that get_archive_robots method exists and is public
	 */
	public function test_get_archive_robots_method_exists() {
		$this->assertTrue(
			method_exists( $this->resolver, 'get_archive_robots' ),
			'get_archive_robots method should exist'
		);

		$reflection = new \ReflectionMethod( $this->resolver, 'get_archive_robots' );
		$this->assertTrue(
			$reflection->isPublic(),
			'get_archive_robots method should be public'
		);
	}
}
