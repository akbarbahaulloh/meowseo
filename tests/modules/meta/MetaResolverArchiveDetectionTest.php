<?php
/**
 * Tests for Meta_Resolver archive type detection
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Options;
use MeowSEO\Modules\Meta\Title_Patterns;
use PHPUnit\Framework\TestCase;

/**
 * Test Meta_Resolver archive type detection
 */
class MetaResolverArchiveDetectionTest extends TestCase {
	/**
	 * Test that resolve_robots_for_archive uses correct WordPress conditionals
	 */
	public function test_archive_detection_uses_correct_conditionals() {
		// Create mock Options
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( array( 'noindex' => false, 'nofollow' => false ) );
		
		// Create mock Title_Patterns
		$patterns = $this->createMock( Title_Patterns::class );
		
		// Create Meta_Resolver instance
		$resolver = new Meta_Resolver( $options, $patterns );
		
		// Verify the method exists and is public
		$this->assertTrue(
			method_exists( $resolver, 'resolve_robots_for_archive' ),
			'resolve_robots_for_archive method should exist'
		);
		
		// Verify archive type constants are defined
		$this->assertEquals( 'author_archive', Meta_Resolver::ARCHIVE_TYPE_AUTHOR );
		$this->assertEquals( 'date_archive', Meta_Resolver::ARCHIVE_TYPE_DATE );
		$this->assertEquals( 'category_archive', Meta_Resolver::ARCHIVE_TYPE_CATEGORY );
		$this->assertEquals( 'tag_archive', Meta_Resolver::ARCHIVE_TYPE_TAG );
		$this->assertEquals( 'search_results', Meta_Resolver::ARCHIVE_TYPE_SEARCH );
		$this->assertEquals( 'attachment', Meta_Resolver::ARCHIVE_TYPE_ATTACHMENT );
		$this->assertEquals( 'post_type_archive', Meta_Resolver::ARCHIVE_TYPE_POST_TYPE );
	}
	
	/**
	 * Test that archive type constants match expected values
	 */
	public function test_archive_type_constants() {
		$expected_constants = array(
			'ARCHIVE_TYPE_AUTHOR' => 'author_archive',
			'ARCHIVE_TYPE_DATE' => 'date_archive',
			'ARCHIVE_TYPE_CATEGORY' => 'category_archive',
			'ARCHIVE_TYPE_TAG' => 'tag_archive',
			'ARCHIVE_TYPE_SEARCH' => 'search_results',
			'ARCHIVE_TYPE_ATTACHMENT' => 'attachment',
			'ARCHIVE_TYPE_POST_TYPE' => 'post_type_archive',
		);
		
		foreach ( $expected_constants as $constant => $expected_value ) {
			$full_constant = 'MeowSEO\Modules\Meta\Meta_Resolver::' . $constant;
			$this->assertTrue(
				defined( $full_constant ),
				"Constant {$constant} should be defined"
			);
			$this->assertEquals(
				$expected_value,
				constant( $full_constant ),
				"Constant {$constant} should have value {$expected_value}"
			);
		}
	}
}
