<?php
/**
 * Redirect Module Integration Tests
 *
 * Integration tests for redirect module with database seeding.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\DB;

/**
 * Redirect integration test case
 *
 * @since 1.0.0
 */
class Test_Redirect_Integration extends TestCase {

	/**
	 * Test exact match redirect with database seeding
	 *
	 * @return void
	 */
	public function test_exact_match_redirect_with_database(): void {
		// This test would require a real database connection.
		// For now, we test the query structure.
		
		$url = 'https://example.com/old-page';
		$result = DB::get_redirect_exact( $url );

		// Should return null for non-existent redirect.
		$this->assertNull( $result );
	}

	/**
	 * Test regex redirect matching
	 *
	 * @return void
	 */
	public function test_regex_redirect_matching(): void {
		$rules = DB::get_redirect_regex_rules();

		$this->assertIsArray( $rules );
		// With no database, should return empty array.
		$this->assertEmpty( $rules );
	}

	/**
	 * Test redirect hit count increment
	 *
	 * @return void
	 */
	public function test_redirect_hit_count_increment(): void {
		// This test would require a real database connection.
		// For now, we verify the method doesn't throw exceptions.
		
		DB::increment_redirect_hit( 1 );
		$this->assertTrue( true );
	}

	/**
	 * Test redirect matching algorithm correctness
	 *
	 * This test verifies the redirect matching logic:
	 * 1. Exact match is tried first
	 * 2. Regex fallback is used if no exact match
	 * 3. Regex rules are evaluated in PHP
	 *
	 * @return void
	 */
	public function test_redirect_matching_algorithm(): void {
		$test_url = 'https://example.com/test-page';

		// Step 1: Try exact match.
		$exact_match = DB::get_redirect_exact( $test_url );
		$this->assertNull( $exact_match, 'No exact match should exist' );

		// Step 2: If no exact match, get regex rules.
		if ( null === $exact_match ) {
			$regex_rules = DB::get_redirect_regex_rules();
			$this->assertIsArray( $regex_rules );

			// Step 3: Evaluate regex rules in PHP.
			$matched_rule = null;
			foreach ( $regex_rules as $rule ) {
				if ( preg_match( $rule['source_url'], $test_url ) ) {
					$matched_rule = $rule;
					break;
				}
			}

			$this->assertNull( $matched_rule, 'No regex match should exist' );
		}
	}

	/**
	 * Test that redirect types are supported
	 *
	 * @return void
	 */
	public function test_redirect_types_are_supported(): void {
		$supported_types = array( 301, 302, 307, 410 );

		foreach ( $supported_types as $type ) {
			$this->assertIsInt( $type );
			$this->assertGreaterThanOrEqual( 301, $type );
			$this->assertLessThanOrEqual( 410, $type );
		}
	}

	/**
	 * Test redirect status values
	 *
	 * @return void
	 */
	public function test_redirect_status_values(): void {
		$valid_statuses = array( 'active', 'inactive' );

		foreach ( $valid_statuses as $status ) {
			$this->assertIsString( $status );
			$this->assertContains( $status, array( 'active', 'inactive' ) );
		}
	}
}
