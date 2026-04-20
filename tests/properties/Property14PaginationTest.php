<?php
/**
 * Property-Based Tests for Pagination
 *
 * Property 14: Pagination
 * Validates: Requirement 7.4
 *
 * This test uses property-based testing (eris/eris) to verify that for any log collection
 * with N entries and page size P, the number of pages SHALL be ceil(N/P) and each page
 * SHALL contain at most P entries.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\REST\REST_Logs;
use MeowSEO\Options;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Pagination property-based test case
 *
 * @since 1.0.0
 */
class Property14PaginationTest extends TestCase {
	use TestTrait;

	/**
	 * REST_Logs instance
	 *
	 * @var REST_Logs
	 */
	private REST_Logs $rest_logs;

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset global wpdb storage to ensure clean state between tests
		reset_wpdb_storage();

		// Create mock Options instance
		$options_mock = $this->createMock( Options::class );

		// Initialize REST_Logs
		$this->rest_logs = new REST_Logs( $options_mock );
	}

	/**
	 * Tear down test fixtures
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Clean up wpdb storage
		global $wpdb_storage;
		$table = 'wp_meowseo_logs';
		$wpdb_storage[ $table ] = array();
		
		parent::tearDown();
	}

	/**
	 * DO NOT setup mock database - use the bootstrap's existing mock
	 */

	/**
	 * Property 14: Pagination
	 *
	 * For any log collection with N entries and page size P, the number of pages
	 * SHALL be ceil(N/P) and each page SHALL contain at most P entries.
	 *
	 * This property verifies:
	 * 1. The calculated number of pages equals ceil(N/P)
	 * 2. Each page contains at most P entries
	 * 3. Edge cases: 0 entries, 1 entry, exactly P entries, P+1 entries
	 *
	 * **Validates: Requirement 7.4**
	 *
	 * @return void
	 */
	public function test_pagination_pages_calculated_correctly(): void {
		$this->forAll(
			Generators::choose( 0, 500 ),  // N: number of entries
			Generators::choose( 1, 100 )   // P: page size
		)
		->then(
			function ( int $num_entries, int $page_size ) {
				// Create test log entries
				$this->create_test_logs( $num_entries );

				// Request first page with specified page size
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', 1 );
				$request->set_param( 'per_page', $page_size );

				// Get response
				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Calculate expected number of pages
				$expected_pages = max( 1, ceil( $num_entries / $page_size ) );

				// Verify pages calculation
				$this->assertEquals(
					$expected_pages,
					$data['pages'],
					"For N=$num_entries entries and P=$page_size page size, expected $expected_pages pages but got {$data['pages']}"
				);

				// Clean up
				$this->delete_test_logs();
			}
		);
	}

	/**
	 * Property: Each page contains at most P entries
	 *
	 * For any page request with page size P, the returned logs array SHALL contain
	 * at most P entries.
	 *
	 * @return void
	 */
	public function test_each_page_contains_at_most_p_entries(): void {
		$this->forAll(
			Generators::choose( 1, 500 ),   // N: number of entries
			Generators::choose( 1, 100 )    // P: page size
		)
		->then(
			function ( int $num_entries, int $page_size ) {
				// Create test log entries
				$this->create_test_logs( $num_entries );

				// Request first page with specified page size
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', 1 );
				$request->set_param( 'per_page', $page_size );

				// Get response
				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify page contains at most P entries
				$this->assertLessThanOrEqual(
					$page_size,
					count( $data['logs'] ),
					"Page 1 with page_size=$page_size should contain at most $page_size entries, but got " . count( $data['logs'] )
				);

				// Clean up
				$this->delete_test_logs();
			}
		);
	}

	/**
	 * Property: All pages except the last contain exactly P entries
	 *
	 * For any page request except the last page, the returned logs array SHALL contain
	 * exactly P entries.
	 *
	 * @return void
	 */
	public function test_all_pages_except_last_contain_exactly_p_entries(): void {
		$this->forAll(
			Generators::choose( 50, 500 ),  // N: number of entries (at least 50 to have multiple pages)
			Generators::choose( 10, 50 )    // P: page size
		)
		->then(
			function ( int $num_entries, int $page_size ) {
				// Create test log entries
				$this->create_test_logs( $num_entries );

				// Calculate total pages
				$total_pages = ceil( $num_entries / $page_size );

				// Test all pages except the last
				for ( $page = 1; $page < $total_pages; $page++ ) {
					$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
					$request->set_param( 'page', $page );
					$request->set_param( 'per_page', $page_size );

					$response = $this->rest_logs->get_logs( $request );
					$data = $response->get_data();

					// Verify non-last pages contain exactly P entries
					$this->assertEquals(
						$page_size,
						count( $data['logs'] ),
						"Page $page (not the last) with page_size=$page_size should contain exactly $page_size entries, but got " . count( $data['logs'] )
					);
				}

				// Clean up
				$this->delete_test_logs();
			}
		);
	}

	/**
	 * Property: Last page contains at most P entries
	 *
	 * For the last page, the returned logs array SHALL contain at most P entries
	 * (and typically fewer if N is not divisible by P).
	 *
	 * @return void
	 */
	public function test_last_page_contains_at_most_p_entries(): void {
		$this->forAll(
			Generators::choose( 1, 500 ),   // N: number of entries
			Generators::choose( 1, 100 )    // P: page size
		)
		->then(
			function ( int $num_entries, int $page_size ) {
				// Create test log entries
				$this->create_test_logs( $num_entries );

				// Calculate total pages
				$total_pages = ceil( $num_entries / $page_size );

				// Request last page
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $total_pages );
				$request->set_param( 'per_page', $page_size );

				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify last page contains at most P entries
				$this->assertLessThanOrEqual(
					$page_size,
					count( $data['logs'] ),
					"Last page (page $total_pages) with page_size=$page_size should contain at most $page_size entries, but got " . count( $data['logs'] )
				);

				// Clean up
				$this->delete_test_logs();
			}
		);
	}

	/**
	 * Property: Last page contains exactly the remainder entries
	 *
	 * For the last page when N is not divisible by P, the returned logs array
	 * SHALL contain exactly N % P entries.
	 *
	 * @return void
	 */
	public function test_last_page_contains_remainder_entries(): void {
		$this->forAll(
			Generators::choose( 1, 500 ),   // N: number of entries
			Generators::choose( 1, 100 )    // P: page size
		)
		->then(
			function ( int $num_entries, int $page_size ) {
				// Skip if N is divisible by P (no remainder)
				if ( 0 === $num_entries % $page_size ) {
					return;
				}

				// Create test log entries
				$this->create_test_logs( $num_entries );

				// Calculate total pages and expected remainder
				$total_pages = ceil( $num_entries / $page_size );
				$expected_remainder = $num_entries % $page_size;

				// Request last page
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $total_pages );
				$request->set_param( 'per_page', $page_size );

				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify last page contains exactly the remainder entries
				$this->assertEquals(
					$expected_remainder,
					count( $data['logs'] ),
					"Last page (page $total_pages) with N=$num_entries and P=$page_size should contain exactly $expected_remainder entries (N % P), but got " . count( $data['logs'] )
				);

				// Clean up
				$this->delete_test_logs();
			}
		);
	}

	/**
	 * Property: Edge case - 0 entries returns 1 page
	 *
	 * When there are 0 entries, the pagination should return 1 page (empty page).
	 *
	 * @return void
	 */
	public function test_edge_case_zero_entries_returns_one_page(): void {
		$this->forAll(
			Generators::choose( 1, 100 )    // P: page size
		)
		->then(
			function ( int $page_size ) {
				// Ensure no test logs exist
				$this->delete_test_logs();

				// Request first page
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', 1 );
				$request->set_param( 'per_page', $page_size );

				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify 0 entries returns 1 page (ceil(0/P) = 0, but we expect at least 1)
				// Actually, ceil(0/P) = 0, so we should verify this behavior
				$expected_pages = max( 1, ceil( 0 / $page_size ) );
				$this->assertEquals(
					$expected_pages,
					$data['pages'],
					"With 0 entries and page_size=$page_size, expected $expected_pages pages but got {$data['pages']}"
				);

				// Verify empty logs array
				$this->assertEmpty(
					$data['logs'],
					'With 0 entries, logs array should be empty'
				);
			}
		);
	}

	/**
	 * Property: Edge case - 1 entry returns 1 page
	 *
	 * When there is 1 entry, the pagination should return 1 page.
	 *
	 * @return void
	 */
	public function test_edge_case_one_entry_returns_one_page(): void {
		$this->forAll(
			Generators::choose( 1, 100 )    // P: page size
		)
		->then(
			function ( int $page_size ) {
				// Create exactly 1 test log entry
				$this->create_test_logs( 1 );

				// Request first page
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', 1 );
				$request->set_param( 'per_page', $page_size );

				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify 1 entry returns 1 page
				$this->assertEquals(
					1,
					$data['pages'],
					"With 1 entry and page_size=$page_size, expected 1 page but got {$data['pages']}"
				);

				// Verify 1 log entry
				$this->assertCount(
					1,
					$data['logs'],
					'With 1 entry, logs array should contain exactly 1 entry'
				);

				// Clean up
				$this->delete_test_logs();
			}
		);
	}

	/**
	 * Property: Edge case - exactly P entries returns 1 page
	 *
	 * When there are exactly P entries, the pagination should return 1 page.
	 *
	 * @return void
	 */
	public function test_edge_case_exactly_p_entries_returns_one_page(): void {
		$this->forAll(
			Generators::choose( 1, 100 )    // P: page size
		)
		->then(
			function ( int $page_size ) {
				// Create exactly P test log entries
				$this->create_test_logs( $page_size );

				// Request first page
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', 1 );
				$request->set_param( 'per_page', $page_size );

				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify P entries returns 1 page
				$this->assertEquals(
					1,
					$data['pages'],
					"With $page_size entries and page_size=$page_size, expected 1 page but got {$data['pages']}"
				);

				// Verify P log entries
				$this->assertCount(
					$page_size,
					$data['logs'],
					"With $page_size entries, logs array should contain exactly $page_size entries"
				);

				// Clean up
				$this->delete_test_logs();
			}
		);
	}

	/**
	 * Property: Edge case - P+1 entries returns 2 pages
	 *
	 * When there are P+1 entries, the pagination should return 2 pages.
	 *
	 * @return void
	 */
	public function test_edge_case_p_plus_one_entries_returns_two_pages(): void {
		$this->forAll(
			Generators::choose( 1, 99 )     // P: page size (capped at 99 so P+1 <= 100)
		)
		->then(
			function ( int $page_size ) {
				// Create P+1 test log entries
				$num_entries = $page_size + 1;
				$this->create_test_logs( $num_entries );

				// Request first page
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', 1 );
				$request->set_param( 'per_page', $page_size );

				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify P+1 entries returns 2 pages
				$this->assertEquals(
					2,
					$data['pages'],
					"With " . ($page_size + 1) . " entries and page_size=$page_size, expected 2 pages but got {$data['pages']}"
				);

				// Verify first page has P entries
				$this->assertCount(
					$page_size,
					$data['logs'],
					"First page with page_size=$page_size should contain exactly $page_size entries"
				);

				// Clean up
				$this->delete_test_logs();
			}
		);
	}

	/**
	 * Helper method to create test log entries
	 *
	 * @param int $count Number of log entries to create.
	 * @return void
	 */
	private function create_test_logs( int $count ): void {
		global $wpdb, $wpdb_storage;

		$table = $wpdb->prefix . 'meowseo_logs';

		for ( $i = 0; $i < $count; $i++ ) {
			$data = array(
				'id'           => ( $wpdb->insert_id ?? 0 ) + $i + 1,
				'level'        => 'INFO',
				'module'       => 'test',
				'message'      => "Test log entry $i",
				'message_hash' => hash( 'sha256', "Test log entry $i" ),
				'context'      => wp_json_encode( array( 'index' => $i ) ),
				'stack_trace'  => null,
				'hit_count'    => 1,
				'created_at'   => current_time( 'mysql' ),
			);
			
			$wpdb->insert( $table, $data, array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ) );
		}
	}

	/**
	 * Helper method to delete all test log entries
	 *
	 * @return void
	 */
	private function delete_test_logs(): void {
		global $wpdb, $wpdb_storage;

		$table = $wpdb->prefix . 'meowseo_logs';

		// Delete all test logs
		$wpdb->query( "DELETE FROM {$table} WHERE module = 'test'" );
	}

	/**
	 * Helper method to delete ALL log entries (for test isolation)
	 *
	 * @return void
	 */
	private function delete_all_logs(): void {
		global $wpdb, $wpdb_storage;

		$table = $wpdb->prefix . 'meowseo_logs';

		// Clear the storage
		$wpdb_storage[ $table ] = array();
	}
}
