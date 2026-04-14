<?php
/**
 * DB Helper Tests
 *
 * Unit tests for the DB helper class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\DB;

/**
 * DB helper test case
 *
 * @since 1.0.0
 */
class Test_DB_Helper extends TestCase {

	/**
	 * Test get_redirect_exact returns null for non-existent URL
	 *
	 * @return void
	 */
	public function test_get_redirect_exact_returns_null_for_nonexistent_url(): void {
		$result = DB::get_redirect_exact( 'https://example.com/nonexistent' );
		$this->assertNull( $result );
	}

	/**
	 * Test get_redirect_regex_rules returns array
	 *
	 * @return void
	 */
	public function test_get_redirect_regex_rules_returns_array(): void {
		$result = DB::get_redirect_regex_rules();
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_404_log returns array
	 *
	 * @return void
	 */
	public function test_get_404_log_returns_array(): void {
		$result = DB::get_404_log( array() );
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_404_log respects limit parameter
	 *
	 * @return void
	 */
	public function test_get_404_log_respects_limit(): void {
		$result = DB::get_404_log( array( 'limit' => 10 ) );
		$this->assertIsArray( $result );
		$this->assertLessThanOrEqual( 10, count( $result ) );
	}

	/**
	 * Test get_404_log uses default limit
	 *
	 * @return void
	 */
	public function test_get_404_log_uses_default_limit(): void {
		$result = DB::get_404_log( array() );
		$this->assertIsArray( $result );
		$this->assertLessThanOrEqual( 50, count( $result ) );
	}

	/**
	 * Test get_gsc_queue returns array
	 *
	 * @return void
	 */
	public function test_get_gsc_queue_returns_array(): void {
		$result = DB::get_gsc_queue();
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_gsc_queue respects limit parameter
	 *
	 * @return void
	 */
	public function test_get_gsc_queue_respects_limit(): void {
		$result = DB::get_gsc_queue( 5 );
		$this->assertIsArray( $result );
		$this->assertLessThanOrEqual( 5, count( $result ) );
	}

	/**
	 * Test get_link_checks returns array
	 *
	 * @return void
	 */
	public function test_get_link_checks_returns_array(): void {
		$result = DB::get_link_checks( 1 );
		$this->assertIsArray( $result );
	}

	/**
	 * Test bulk_upsert_404 handles empty array
	 *
	 * @return void
	 */
	public function test_bulk_upsert_404_handles_empty_array(): void {
		// Should not throw an exception.
		DB::bulk_upsert_404( array() );
		$this->assertTrue( true );
	}

	/**
	 * Test upsert_gsc_data handles empty array
	 *
	 * @return void
	 */
	public function test_upsert_gsc_data_handles_empty_array(): void {
		// Should not throw an exception.
		DB::upsert_gsc_data( array() );
		$this->assertTrue( true );
	}

	/**
	 * Test upsert_link_check handles missing required fields
	 *
	 * @return void
	 */
	public function test_upsert_link_check_handles_missing_required_fields(): void {
		// Should not throw an exception.
		DB::upsert_link_check( array() );
		$this->assertTrue( true );

		DB::upsert_link_check( array( 'source_post_id' => 1 ) );
		$this->assertTrue( true );

		DB::upsert_link_check( array( 'target_url' => 'https://example.com' ) );
		$this->assertTrue( true );
	}

	/**
	 * Test that all DB methods use prepared statements
	 *
	 * This test verifies Requirement 15.1: All database queries use $wpdb->prepare().
	 *
	 * @return void
	 */
	public function test_all_methods_use_prepared_statements(): void {
		// Read the DB helper class file.
		$file_path = __DIR__ . '/../includes/helpers/class-db.php';
		$this->assertFileExists( $file_path );

		$content = file_get_contents( $file_path );

		// Check that all query methods use $wpdb->prepare().
		$this->assertStringContainsString( '$wpdb->prepare(', $content );

		// Check that there are no direct query executions without prepare.
		// This is a basic check - a more thorough check would use AST parsing.
		$lines = explode( "\n", $content );
		$query_lines = array_filter( $lines, function( $line ) {
			return strpos( $line, '$wpdb->query(' ) !== false ||
			       strpos( $line, '$wpdb->get_row(' ) !== false ||
			       strpos( $line, '$wpdb->get_results(' ) !== false ||
			       strpos( $line, '$wpdb->get_var(' ) !== false;
		} );

		foreach ( $query_lines as $line_num => $line ) {
			// Skip lines that are comments.
			if ( strpos( trim( $line ), '//' ) === 0 || strpos( trim( $line ), '*' ) === 0 ) {
				continue;
			}

			// Check if the line uses a prepared query.
			$this->assertTrue(
				strpos( $line, '$wpdb->prepare(' ) !== false || strpos( $line, '$prepared' ) !== false,
				"Line {$line_num} should use prepared statement: {$line}"
			);
		}
	}
}
