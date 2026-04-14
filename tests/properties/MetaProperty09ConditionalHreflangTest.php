<?php
/**
 * Property 9: Conditional Hreflang Output
 *
 * Feature: meta-module-rebuild, Property 9: For any page, hreflang
 * alternate links SHALL only be output when WPML or Polylang is active.
 *
 * Validates: Requirements 2.9
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use WP_UnitTestCase;
use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Modules\Meta\Title_Patterns;
use MeowSEO\Options;

/**
 * Test Property 9: Conditional Hreflang Output
 */
class MetaProperty09ConditionalHreflangTest extends WP_UnitTestCase {

	/**
	 * Test hreflang returns empty when no multilingual plugin active
	 *
	 * @return void
	 */
	public function test_hreflang_empty_when_no_plugin_active(): void {
		// Resolve hreflang alternates.
		$options  = new Options();
		$patterns = new Title_Patterns( $options );
		$resolver = new Meta_Resolver( $options, $patterns );
		$result   = $resolver->get_hreflang_alternates();

		// Property: Should return empty array when no multilingual plugin active.
		$this->assertIsArray( $result, 'Should return array' );
		$this->assertEmpty( $result, 'Should be empty when no multilingual plugin active' );
	}

	/**
	 * Test hreflang returns array structure
	 *
	 * @return void
	 */
	public function test_hreflang_returns_array(): void {
		// Resolve hreflang alternates.
		$options  = new Options();
		$patterns = new Title_Patterns( $options );
		$resolver = new Meta_Resolver( $options, $patterns );
		$result   = $resolver->get_hreflang_alternates();

		// Property: Should always return array.
		$this->assertIsArray( $result, 'Should always return array' );
	}
}
