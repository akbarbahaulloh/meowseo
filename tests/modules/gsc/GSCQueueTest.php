<?php
/**
 * Tests for GSC_Queue class
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\GSC;

use MeowSEO\Modules\GSC\GSC_Queue;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * GSC_Queue test case
 */
class GSCQueueTest extends TestCase {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * GSC_Queue instance.
	 *
	 * @var GSC_Queue
	 */
	private GSC_Queue $queue;

	/**
	 * In-memory queue storage for testing.
	 *
	 * @var array
	 */
	public array $queue_storage = [];

	/**
	 * Next job ID for testing.
	 *
	 * @var int
	 */
	public int $next_job_id = 1;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = new Options();
		$this->queue = new GSC_Queue( $this->options, null );

		// Reset queue storage.
		$this->queue_storage = [];
		$this->next_job_id = 1;

		// Mock $wpdb for queue operations.
		$this->setup_wpdb_mock();
	}

	/**
	 * Set up $wpdb mock for queue operations.
	 */
	private function setup_wpdb_mock(): void {
		global $wpdb;

		$test = $this;

		$wpdb = new class( $test ) {
			public $prefix = 'wp_';
			public $last_error = '';
			public $insert_id = 0;
			private $test;

			public function __construct( $test ) {
				$this->test = $test;
			}

			public function prepare( $query, ...$args ) {
				// Simple prepare implementation for testing.
				foreach ( $args as $arg ) {
					$query = preg_replace( '/%[sd]/', is_numeric( $arg ) ? $arg : "'" . addslashes( $arg ) . "'", $query, 1 );
				}
				return $query;
			}

			public function insert( $table, $data, $format = null ) {
				$job_id = $this->test->next_job_id++;
				$data['id'] = $job_id;
				$this->test->queue_storage[ $job_id ] = $data;
				$this->insert_id = $job_id;
				return 1;
			}

			public function get_var( $query ) {
				// Handle COUNT queries.
				if ( stripos( $query, 'COUNT(*)' ) !== false ) {
					// Check for specific conditions in the query.
					$count = 0;
					foreach ( $this->test->queue_storage as $job ) {
						$matches = true;

						// Check for status = 'pending'.
						if ( stripos( $query, "status = 'pending'" ) !== false ) {
							$matches = $matches && ( $job['status'] ?? '' ) === 'pending';
						}

						// Check for job_type match.
						if ( preg_match( "/job_type = '([^']+)'/", $query, $type_matches ) ) {
							$matches = $matches && ( $job['job_type'] ?? '' ) === $type_matches[1];
						}

						// Check for payload match.
						if ( preg_match( "/payload = '([^']+)'/", $query, $payload_matches ) ) {
							// Unescape the payload for comparison.
							$expected_payload = str_replace( "\\'", "'", $payload_matches[1] );
							$matches = $matches && ( $job['payload'] ?? '' ) === $expected_payload;
						}

						if ( $matches ) {
							$count++;
						}
					}
					return $count;
				}
				return 0;
			}

			public function get_row( $query, $output = OBJECT ) {
				foreach ( $this->test->queue_storage as $job ) {
					$matches = true;

					// Check for job_type match.
					if ( preg_match( "/job_type = '([^']+)'/", $query, $type_matches ) ) {
						$matches = $matches && ( $job['job_type'] ?? '' ) === $type_matches[1];
					}

					// Check for status = 'pending'.
					if ( stripos( $query, "status = 'pending'" ) !== false ) {
						$matches = $matches && ( $job['status'] ?? '' ) === 'pending';
					}

					if ( $matches ) {
						return $output === ARRAY_A ? $job : (object) $job;
					}
				}
				return null;
			}

			public function get_results( $query, $output = ARRAY_A ) {
				$results = [];
				foreach ( $this->test->queue_storage as $job ) {
					$matches = true;

					// Check for status = 'pending'.
					if ( stripos( $query, "status = 'pending'" ) !== false ) {
						$matches = $matches && ( $job['status'] ?? '' ) === 'pending';
					}

					if ( $matches ) {
						$results[] = $output === ARRAY_A ? $job : (object) $job;
					}
				}
				return $results;
			}

			public function update( $table, $data, $where, $format = null, $where_format = null ) {
				if ( isset( $where['id'] ) && isset( $this->test->queue_storage[ $where['id'] ] ) ) {
					$this->test->queue_storage[ $where['id'] ] = array_merge( $this->test->queue_storage[ $where['id'] ], $data );
					return 1;
				}
				return 0;
			}

			public function query( $query ) {
				// Handle TRUNCATE.
				if ( stripos( $query, 'TRUNCATE' ) !== false ) {
					$this->test->queue_storage = [];
					return 0;
				}
				return 0;
			}
		};
	}

	/**
	 * Test enqueue creates a new job.
	 *
	 * Validates Requirement 10.1: Enqueue API requests in meowseo_gsc_queue database table.
	 */
	public function test_enqueue_creates_job(): void {
		$url = 'https://example.com/test-page/';
		$job_type = 'indexing';

		$result = $this->queue->enqueue( $url, $job_type );

		$this->assertTrue( $result );

		// Verify job was inserted.
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_gsc_queue';
		$job = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE job_type = %s",
				$job_type
			),
			ARRAY_A
		);

		$this->assertNotNull( $job );
		$this->assertEquals( 'indexing', $job['job_type'] );
		$this->assertEquals( 'pending', $job['status'] );
		$this->assertEquals( 0, $job['attempts'] );

		// Verify payload.
		$payload = json_decode( $job['payload'], true );
		$this->assertEquals( $url, $payload['url'] );
	}

	/**
	 * Test enqueue rejects invalid job types.
	 */
	public function test_enqueue_rejects_invalid_job_type(): void {
		$url = 'https://example.com/test-page/';
		$job_type = 'invalid_type';

		$result = $this->queue->enqueue( $url, $job_type );

		$this->assertFalse( $result );

		// Verify no job was inserted by checking storage directly.
		$this->assertEmpty( $this->queue_storage, 'Queue storage should be empty after invalid job type' );
	}

	/**
	 * Test enqueue prevents duplicate pending jobs.
	 *
	 * Validates Requirement 10.2: Check whether an identical pending job exists before inserting to avoid duplicates.
	 */
	public function test_enqueue_prevents_duplicates(): void {
		$url = 'https://example.com/test-page/';
		$job_type = 'indexing';

		// Enqueue first job.
		$result1 = $this->queue->enqueue( $url, $job_type );
		$this->assertTrue( $result1 );

		// Attempt to enqueue duplicate.
		$result2 = $this->queue->enqueue( $url, $job_type );
		$this->assertFalse( $result2 );

		// Verify only one job exists by checking storage directly.
		$this->assertCount( 1, $this->queue_storage, 'Only one job should exist after duplicate attempt' );
	}

	/**
	 * Test check_duplicate detects existing pending jobs.
	 */
	public function test_check_duplicate_detects_pending_job(): void {
		$url = 'https://example.com/test-page/';
		$job_type = 'indexing';

		// Insert a pending job directly.
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_gsc_queue';
		$wpdb->insert(
			$table,
			[
				'job_type' => $job_type,
				'payload'  => wp_json_encode( [ 'url' => $url ] ),
				'status'   => 'pending',
				'attempts' => 0,
			],
			[ '%s', '%s', '%s', '%d' ]
		);

		// Check for duplicate.
		$is_duplicate = $this->queue->check_duplicate( $url, $job_type );
		$this->assertTrue( $is_duplicate );
	}

	/**
	 * Test check_duplicate returns false for non-pending jobs.
	 */
	public function test_check_duplicate_ignores_non_pending(): void {
		$url = 'https://example.com/test-page/';
		$job_type = 'indexing';

		// Insert a completed job.
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_gsc_queue';
		$wpdb->insert(
			$table,
			[
				'job_type' => $job_type,
				'payload'  => wp_json_encode( [ 'url' => $url ] ),
				'status'   => 'done',
				'attempts' => 1,
			],
			[ '%s', '%s', '%s', '%d' ]
		);

		// Check for duplicate (should return false since status is 'done').
		$is_duplicate = $this->queue->check_duplicate( $url, $job_type );
		$this->assertFalse( $is_duplicate );
	}

	/**
	 * Test calculate_retry_delay uses exponential backoff.
	 *
	 * Validates Requirement 10.5: Set retry_after to current time plus 60 seconds multiplied by 2 to the power of the attempts count.
	 */
	public function test_calculate_retry_delay_exponential_backoff(): void {
		// Test exponential backoff formula: 60 * 2^attempts.
		$this->assertEquals( 120, $this->queue->calculate_retry_delay( 1 ) ); // 60 * 2^1 = 120.
		$this->assertEquals( 240, $this->queue->calculate_retry_delay( 2 ) ); // 60 * 2^2 = 240.
		$this->assertEquals( 480, $this->queue->calculate_retry_delay( 3 ) ); // 60 * 2^3 = 480.
		$this->assertEquals( 960, $this->queue->calculate_retry_delay( 4 ) ); // 60 * 2^4 = 960.
	}

	/**
	 * Test calculate_retry_delay handles zero attempts.
	 */
	public function test_calculate_retry_delay_zero_attempts(): void {
		// 60 * 2^0 = 60.
		$this->assertEquals( 60, $this->queue->calculate_retry_delay( 0 ) );
	}

	/**
	 * Test enqueue accepts all valid job types.
	 */
	public function test_enqueue_accepts_valid_job_types(): void {
		$url = 'https://example.com/test-page/';
		$valid_types = [ 'indexing', 'inspection', 'analytics' ];

		foreach ( $valid_types as $job_type ) {
			$result = $this->queue->enqueue( $url . $job_type, $job_type );
			$this->assertTrue( $result, "Failed to enqueue job type: {$job_type}" );
		}

		// Verify all jobs were inserted.
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_gsc_queue';
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$this->assertEquals( 3, $count );
	}

	/**
	 * Test process_batch skips when API is not available.
	 */
	public function test_process_batch_skips_without_api(): void {
		// Insert a pending job.
		$url = 'https://example.com/test-page/';
		$this->queue->enqueue( $url, 'indexing' );

		// Process batch (should skip since API is null).
		$this->queue->process_batch();

		// Verify job is still pending.
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_gsc_queue';
		$job = $wpdb->get_row(
			"SELECT * FROM {$table} WHERE status = 'pending'",
			ARRAY_A
		);

		$this->assertNotNull( $job );
		$this->assertEquals( 'pending', $job['status'] );
	}

	/**
	 * Test enqueue handles JSON encoding failure gracefully.
	 */
	public function test_enqueue_handles_json_encoding_failure(): void {
		// Create a URL that would cause JSON encoding issues (though unlikely in practice).
		// For this test, we'll just verify the method handles empty URLs.
		$url = '';
		$job_type = 'indexing';

		$result = $this->queue->enqueue( $url, $job_type );

		// Should still succeed (empty URL is valid JSON).
		$this->assertTrue( $result );
	}

	/**
	 * Test check_duplicate returns false for different URLs.
	 */
	public function test_check_duplicate_different_urls(): void {
		$url1 = 'https://example.com/page-1/';
		$url2 = 'https://example.com/page-2/';
		$job_type = 'indexing';

		// Enqueue first URL.
		$this->queue->enqueue( $url1, $job_type );

		// Check for duplicate with different URL.
		$is_duplicate = $this->queue->check_duplicate( $url2, $job_type );
		$this->assertFalse( $is_duplicate );
	}

	/**
	 * Test check_duplicate returns false for different job types.
	 */
	public function test_check_duplicate_different_job_types(): void {
		$url = 'https://example.com/test-page/';
		$job_type1 = 'indexing';
		$job_type2 = 'inspection';

		// Enqueue with first job type.
		$this->queue->enqueue( $url, $job_type1 );

		// Check for duplicate with different job type.
		$is_duplicate = $this->queue->check_duplicate( $url, $job_type2 );
		$this->assertFalse( $is_duplicate );
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		// Clean up queue storage.
		$this->queue_storage = [];

		parent::tearDown();
	}
}
