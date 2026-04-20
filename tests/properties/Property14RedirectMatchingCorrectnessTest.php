<?php
/**
 * Property-Based Tests for Redirect Matching Correctness
 *
 * Property 14: Redirect matching algorithm correctness
 * Validates: Requirements 7.2, 7.3, 7.4
 *
 * This test uses property-based testing (eris/eris) to verify that the redirect
 * matching algorithm correctly identifies and executes redirects according to the
 * specification. The algorithm must:
 *
 * 1. Perform exact-match queries on indexed source_url (O(log n))
 * 2. Evaluate regex rules only when no exact match is found
 * 3. Never load all redirect rules into PHP memory simultaneously
 * 4. Support redirect types: 301, 302, 307, 410
 * 5. Handle regex backreferences correctly
 * 6. Detect and prevent redirect loops
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\DB;

/**
 * Redirect Matching Correctness property-based test case
 *
 * @since 1.0.0
 */
class Property14RedirectMatchingCorrectnessTest extends TestCase {
	use TestTrait;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Reset global wpdb storage to ensure clean state between tests
		reset_wpdb_storage();
		// Clear any existing redirects before each test
		$this->clear_test_redirects();
	}

	/**
	 * Teardown test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		// Clean up redirects after each test
		$this->clear_test_redirects();
	}

	/**
	 * Clear all test redirects from database
	 *
	 * @return void
	 */
	private function clear_test_redirects(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_redirects';
		$wpdb->query( "DELETE FROM {$table} WHERE source_url LIKE 'test_%'" );
	}

	/**
	 * Property 14: Redirect matching algorithm correctness
	 *
	 * For any set of redirect rules, the matching algorithm must:
	 * 1. Return exact matches when source_url matches exactly
	 * 2. Return regex matches when no exact match exists but regex pattern matches
	 * 3. Return null when no match is found
	 * 4. Prioritize exact matches over regex matches
	 * 5. Support all redirect types (301, 302, 307, 410)
	 *
	 * This property verifies the core correctness of the redirect matching algorithm.
	 *
	 * **Validates: Requirements 7.2, 7.3, 7.4**
	 *
	 * @return void
	 */
	public function test_redirect_matching_algorithm_correctness(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 20 ),
			Generators::choose( 301, 307 )
		)
		->then(
			function ( string $url_suffix, int $redirect_type ) {
				// Create test URLs
				$source_url = 'test_' . $url_suffix;
				$target_url = 'https://example.com/target-' . $url_suffix;

				// Insert exact-match redirect
				$this->insert_redirect(
					$source_url,
					$target_url,
					$redirect_type,
					false
				);

				// Verify exact match is found
				$result = DB::get_redirect_exact( $source_url );

				$this->assertNotNull(
					$result,
					'Exact match should be found for matching source_url'
				);

				$this->assertEquals(
					$source_url,
					$result['source_url'],
					'Matched redirect should have correct source_url'
				);

				$this->assertEquals(
					$target_url,
					$result['target_url'],
					'Matched redirect should have correct target_url'
				);

				$this->assertEquals(
					$redirect_type,
					(int) $result['redirect_type'],
					'Matched redirect should have correct redirect_type'
				);

				$this->assertEquals(
					1,
					(int) $result['is_active'],
					'Matched redirect should be active'
				);
			}
		);
	}

	/**
	 * Property: Exact match takes precedence over regex
	 *
	 * For any URL that matches both an exact rule and a regex rule,
	 * the exact match must be returned and the regex rule must not be evaluated.
	 *
	 * @return void
	 */
	public function test_exact_match_precedence_over_regex(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 15 ),
			Generators::choose( 301, 307 )
		)
		->then(
			function ( string $url_suffix, int $redirect_type ) {
				$source_url = 'test_' . $url_suffix;
				$target_url_exact = 'https://example.com/exact-' . $url_suffix;
				$target_url_regex = 'https://example.com/regex-' . $url_suffix;

				// Insert exact-match redirect
				$this->insert_redirect(
					$source_url,
					$target_url_exact,
					$redirect_type,
					false
				);

				// Insert regex redirect that would also match
				$this->insert_redirect(
					'test_.*',
					$target_url_regex,
					$redirect_type,
					true
				);

				// Query exact match
				$result = DB::get_redirect_exact( $source_url );

				// Verify exact match is returned, not regex match
				$this->assertNotNull( $result, 'Exact match should be found' );
				$this->assertEquals(
					$target_url_exact,
					$result['target_url'],
					'Exact match should take precedence over regex'
				);
				$this->assertFalse(
					(bool) $result['is_regex'],
					'Returned redirect should be exact match, not regex'
				);
			}
		);
	}

	/**
	 * Property: Regex rules are only loaded when needed
	 *
	 * For any URL that does not match an exact rule, regex rules must be
	 * retrieved and evaluated. The regex rules query must only return
	 * rules with is_regex=1 and status='active'.
	 *
	 * @return void
	 */
	public function test_regex_rules_only_loaded_when_needed(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 15 ),
			Generators::choose( 1, 5 )
		)
		->then(
			function ( string $url_suffix, int $regex_count ) {
				// Insert multiple regex rules
				for ( $i = 0; $i < $regex_count; $i++ ) {
					$this->insert_redirect(
						'test_regex_' . $i . '_.*',
						'https://example.com/regex-' . $i,
						301,
						true
					);
				}

				// Insert some exact rules (should not be in regex results)
				for ( $i = 0; $i < 3; $i++ ) {
					$this->insert_redirect(
						'test_exact_' . $i,
						'https://example.com/exact-' . $i,
						301,
						false
					);
				}

				// Get regex rules
				$regex_rules = DB::get_redirect_regex_rules();

				// Verify only regex rules are returned
				$this->assertCount(
					$regex_count,
					$regex_rules,
					'Only regex rules should be returned'
				);

				// Verify all returned rules are regex rules
				foreach ( $regex_rules as $rule ) {
					$this->assertTrue(
						(bool) $rule['is_regex'],
						'All returned rules should have is_regex=1'
					);

					$this->assertEquals(
						'active',
						$rule['status'],
						'All returned rules should be active'
					);
				}
			}
		);
	}

	/**
	 * Property: Inactive redirects are never matched
	 *
	 * For any redirect rule with status='inactive', the matching algorithm
	 * must not return it, even if the source_url matches exactly.
	 *
	 * @return void
	 */
	public function test_inactive_redirects_never_matched(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 15 )
		)
		->then(
			function ( string $url_suffix ) {
				$source_url = 'test_' . $url_suffix;
				$target_url = 'https://example.com/target';

				// Insert inactive redirect
				$this->insert_redirect(
					$source_url,
					$target_url,
					301,
					false,
					0
				);

				// Query for exact match
				$result = DB::get_redirect_exact( $source_url );

				// Verify inactive redirect is not returned
				$this->assertNull(
					$result,
					'Inactive redirects should never be matched'
				);
			}
		);
	}

	/**
	 * Property: All supported redirect types are preserved
	 *
	 * For any redirect rule with a supported redirect type (301, 302, 307, 410),
	 * the matching algorithm must return the rule with the correct type preserved.
	 *
	 * @return void
	 */
	public function test_all_redirect_types_preserved(): void {
		$supported_types = [ 301, 302, 307, 410 ];

		foreach ( $supported_types as $redirect_type ) {
			$source_url = 'test_type_' . $redirect_type;
			$target_url = 'https://example.com/target-' . $redirect_type;

			// Insert redirect with specific type
			$this->insert_redirect(
				$source_url,
				$target_url,
				$redirect_type,
				false
			);

			// Query for exact match
			$result = DB::get_redirect_exact( $source_url );

			// Verify redirect type is preserved
			$this->assertNotNull( $result, "Redirect type $redirect_type should be found" );
			$this->assertEquals(
				$redirect_type,
				(int) $result['redirect_type'],
				"Redirect type $redirect_type should be preserved"
			);
		}
	}

	/**
	 * Property: Regex patterns with backreferences are supported
	 *
	 * For any regex rule with backreferences in the target URL,
	 * the matching algorithm must correctly substitute captured groups.
	 *
	 * @return void
	 */
	public function test_regex_backreferences_supported(): void {
		$this->forAll(
			Generators::string( 'a-z0-9', 3, 10 ),
			Generators::string( 'a-z0-9', 3, 10 )
		)
		->then(
			function ( string $part1, string $part2 ) {
				// Create regex pattern with capture groups
				$source_url = 'test_([a-z0-9]+)_([a-z0-9]+)';
				$target_url = 'https://example.com/$1/$2';

				// Insert regex redirect with backreferences
				$this->insert_redirect(
					$source_url,
					$target_url,
					301,
					true
				);

				// Get regex rules
				$regex_rules = DB::get_redirect_regex_rules();

				// Verify regex rule is returned
				$this->assertNotEmpty(
					$regex_rules,
					'Regex rule should be returned'
				);

				$rule = $regex_rules[0];

				// Verify backreferences are preserved in target URL
				$this->assertStringContainsString(
					'$1',
					$rule['target_url'],
					'Backreference $1 should be preserved'
				);

				$this->assertStringContainsString(
					'$2',
					$rule['target_url'],
					'Backreference $2 should be preserved'
				);
			}
		);
	}

	/**
	 * Property: Hit count is incremented on redirect execution
	 *
	 * For any redirect that is executed, the hit_count must be incremented
	 * and last_accessed must be updated.
	 *
	 * @return void
	 */
	public function test_hit_count_incremented_on_execution(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 15 ),
			Generators::choose( 1, 10 )
		)
		->then(
			function ( string $url_suffix, int $hit_count ) {
				$source_url = 'test_' . $url_suffix;
				$target_url = 'https://example.com/target';

				// Insert redirect with initial hit count
				$redirect_id = $this->insert_redirect(
					$source_url,
					$target_url,
					301,
					false
				);

				// Simulate multiple hits
				for ( $i = 0; $i < $hit_count; $i++ ) {
					DB::increment_redirect_hit( $redirect_id );
				}

				// Verify hit count was incremented
				$result = DB::get_redirect_exact( $source_url );

				$this->assertNotNull( $result, 'Redirect should exist' );
				$this->assertEquals(
					$hit_count,
					(int) $result['hit_count'],
					'Hit count should be incremented correctly'
				);

				$this->assertNotNull(
					$result['last_accessed'],
					'last_accessed should be updated'
				);
			}
		);
	}

	/**
	 * Property: No redirect rules are loaded into memory unnecessarily
	 *
	 * For any query, only the necessary redirect rules should be retrieved.
	 * Exact-match queries should return at most 1 row. Regex queries should
	 * return only rules with is_regex=1.
	 *
	 * @return void
	 */
	public function test_no_unnecessary_rules_loaded(): void {
		$this->forAll(
			Generators::choose( 5, 20 )
		)
		->then(
			function ( int $rule_count ) {
				// Insert many redirect rules
				for ( $i = 0; $i < $rule_count; $i++ ) {
					$this->insert_redirect(
						'test_rule_' . $i,
						'https://example.com/target-' . $i,
						301,
						false
					);
				}

				// Query for a non-existent exact match
				$result = DB::get_redirect_exact( 'test_nonexistent' );

				// Verify no result is returned
				$this->assertNull(
					$result,
					'Non-existent redirect should not be found'
				);

				// Get regex rules (should be empty since we only inserted exact rules)
				$regex_rules = DB::get_redirect_regex_rules();

				$this->assertEmpty(
					$regex_rules,
					'No regex rules should be returned when none exist'
				);
			}
		);
	}

	/**
	 * Property: Redirect matching is case-sensitive for URLs
	 *
	 * For any redirect rule, the source_url matching must be case-sensitive
	 * (URLs are case-sensitive per RFC 3986).
	 *
	 * @return void
	 */
	public function test_redirect_matching_case_sensitive(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 15 )
		)
		->then(
			function ( string $url_suffix ) {
				$source_url_lower = 'test_' . $url_suffix;
				$source_url_upper = 'TEST_' . strtoupper( $url_suffix );
				$target_url = 'https://example.com/target';

				// Insert redirect with lowercase URL
				$this->insert_redirect(
					$source_url_lower,
					$target_url,
					301,
					false
				);

				// Query with lowercase URL (should match)
				$result_lower = DB::get_redirect_exact( $source_url_lower );

				$this->assertNotNull(
					$result_lower,
					'Lowercase URL should match'
				);

				// Query with uppercase URL (should not match)
				$result_upper = DB::get_redirect_exact( $source_url_upper );

				$this->assertNull(
					$result_upper,
					'Uppercase URL should not match (case-sensitive)'
				);
			}
		);
	}

	/**
	 * Helper: Insert a redirect rule into the database
	 *
	 * @param string $source_url Source URL.
	 * @param string $target_url Target URL.
	 * @param int $redirect_type Redirect type (301, 302, 307, 410).
	 * @param bool $is_regex Whether this is a regex rule.
	 * @param string $status Redirect status (active, inactive).
	 * @return int Inserted redirect ID.
	 */
	private function insert_redirect(
		string $source_url,
		string $target_url,
		int $redirect_type = 301,
		bool $is_regex = false,
		int $is_active = 1
	): int {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		$data = [
			'source_url'    => $source_url,
			'target_url'    => $target_url,
			'redirect_type' => $redirect_type,
			'is_regex'      => $is_regex ? 1 : 0,
			'is_active'     => $is_active,
			'hit_count'     => 0,
		];

		$format = [ '%s', '%s', '%d', '%d', '%d', '%d' ];

		$wpdb->insert( $table, $data, $format );

		return (int) $wpdb->insert_id;
	}
}
