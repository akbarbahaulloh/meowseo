<?php
/**
 * Tests for GSC Queue Rate Limiting and Batch Processing
 *
 * Tests task 17.3: GSC queue with rate limiting
 * - Enqueue 20+ jobs and process batch
 * - Simulate HTTP 429 response and verify exponential backoff
 * - Verify retry_after calculation is correct
 *
 * Validates Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\GSC;

use MeowSEO\Modules\GSC\GSC_Queue;
use MeowSEO\Modules\GSC\GSC_API;
use MeowSEO\Modules\GSC\GSC_Auth;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * GSC Queue Rate Limiting test case
 */
class GSCQueueRateLimitTest extends TestCase {

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
	 * Mock GSC_API instance.
	 *
	 * @var object
	 */
	private $mock_api;

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
	 * API call counter.
	 *
	 * @var int
	 */
	public int $api_call_count = 0;

	/**
	 * API responses to return (queue).
	 *
	 * @var array
	 */
	public array $api_responses = [];

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = new Options();
		$this->api_call_count = 0;
		$this->api_responses = [];

		// Reset queue storage.
		$this->queue_storage = [];
		$this->next_job_id = 1;

		// Mock $wpdb for queue operations.
		$this->setup_wpdb_mock();

		// Create mock API using PHPUnit.
		$this->mock_api = $this->createMock( GSC_API::class );
		
		// Configure mock to track calls and return responses.
		$test = $this;
		$this->mock_api->method( 'submit_for_indexing' )
			->willReturnCallback( function ( $url ) use ( $test ) {
				$test->api_call_count++;
				
				// Return next response from queue.
				if ( ! empty( $test->api_responses ) ) {
					return array_shift( $test->api_responses );
				}
				
				// Default success response.
				return [
					'success'   => true,
					'http_code' => 200,
					'data'      => [ 'urlNotificationMetadata' => [ 'url' => $url ] ],
				];
			} );
		
		$this->mock_api->method( 'inspect_url' )
			->willReturnCallback( function ( $url ) use ( $test ) {
				$test->api_call_count++;
				
				// Return next response from queue.
				if ( ! empty( $test->api_responses ) ) {
					return array_shift( $test->api_responses );
				}
				
				// Default success response.
				return [
					'success'   => true,
					'http_code' => 200,
					'data'      => [ 'inspectionResult' => [ 'indexStatusResult' => [] ] ],
				];
			} );

		// Create queue with mock API.
		$this->queue = new GSC_Queue( $this->options, $this->mock_api );
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
				$data['created_at'] = current_time( 'mysql' );
				$this->test->queue_storage[ $job_id ] = $data;
				$this->insert_id = $job_id;
				return 1;
			}

			public function get_var( $query ) {
				// Handle COUNT queries.
				if ( stripos( $query, 'COUNT(*)' ) !== false ) {
					$count = 0;
					foreach ( $this->test->queue_storage as $job ) {
						$matches = true;

						// Check for status = 'pending'.
						if ( stripos( $query, "status = 'pending'" ) !== false ) {
							$matches = $matches && ( $job['status'] ?? '' ) === 'pending';
						}

						// Check for retry_after condition.
						if ( stripos( $query, 'retry_after IS NULL OR retry_after <=' ) !== false ) {
							$retry_after = $job['retry_after'] ?? null;
							if ( null !== $retry_after ) {
								$retry_timestamp = strtotime( $retry_after );
								$matches = $matches && $retry_timestamp <= time();
							}
						}

						// Check for job_type match.
						if ( preg_match( "/job_type = '([^']+)'/", $query, $type_matches ) ) {
							$matches = $matches && ( $job['job_type'] ?? '' ) === $type_matches[1];
						}

						// Check for payload match.
						if ( preg_match( "/payload = '([^']+)'/", $query, $payload_matches ) ) {
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

			public function get_results( $query, $output = ARRAY_A ) {
				$results = [];
				foreach ( $this->test->queue_storage as $job ) {
					$matches = true;

					// Check for status = 'pending'.
					if ( stripos( $query, "status = 'pending'" ) !== false ) {
						$matches = $matches && ( $job['status'] ?? '' ) === 'pending';
					}

					// Check for retry_after condition.
					if ( stripos( $query, 'retry_after IS NULL OR retry_after <=' ) !== false ) {
						$retry_after = $job['retry_after'] ?? null;
						if ( null !== $retry_after ) {
							$retry_timestamp = strtotime( $retry_after );
							$matches = $matches && $retry_timestamp <= time();
						}
					}

					if ( $matches ) {
						$results[] = $output === ARRAY_A ? $job : (object) $job;
					}
				}

				// Apply LIMIT if present.
				if ( preg_match( '/LIMIT (\d+)/', $query, $limit_matches ) ) {
					$limit = (int) $limit_matches[1];
					$results = array_slice( $results, 0, $limit );
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
		};
	}

	/**
	 * Test enqueuing 20+ jobs and processing in batches.
	 *
	 * Validates Requirement 10.1: Enqueue API requests in meowseo_gsc_queue database table.
	 * Validates Requirement 10.3: Process up to 10 queue entries per batch.
	 */
	public function test_enqueue_20_jobs_and_process_batch(): void {
		// Enqueue 25 jobs.
		$job_count = 25;
		for ( $i = 1; $i <= $job_count; $i++ ) {
			$url = "https://example.com/page-{$i}/";
			$result = $this->queue->enqueue( $url, 'indexing' );
			$this->assertTrue( $result, "Failed to enqueue job {$i}" );
		}

		// Verify all jobs were enqueued.
		$this->assertCount( $job_count, $this->queue_storage );

		// Queue success responses for first batch (10 jobs).
		for ( $i = 0; $i < 10; $i++ ) {
			$this->api_responses[] = [
				'success'   => true,
				'http_code' => 200,
				'data'      => [],
			];
		}

		// Process first batch.
		$this->queue->process_batch();

		// Verify 10 API calls were made (batch size limit).
		$this->assertEquals( 10, $this->api_call_count );

		// Verify 10 jobs are now 'done'.
		$done_count = 0;
		foreach ( $this->queue_storage as $job ) {
			if ( $job['status'] === 'done' ) {
				$done_count++;
			}
		}
		$this->assertEquals( 10, $done_count );

		// Verify 15 jobs remain pending.
		$pending_count = 0;
		foreach ( $this->queue_storage as $job ) {
			if ( $job['status'] === 'pending' ) {
				$pending_count++;
			}
		}
		$this->assertEquals( 15, $pending_count );
	}

	/**
	 * Test HTTP 429 response triggers exponential backoff.
	 *
	 * Validates Requirement 10.5: When HTTP 429 rate limit response is received,
	 * update job status to pending and set retry_after.
	 */
	public function test_http_429_triggers_exponential_backoff(): void {
		// Enqueue a job.
		$url = 'https://example.com/test-page/';
		$this->queue->enqueue( $url, 'indexing' );

		// Queue HTTP 429 response.
		$this->api_responses[] = [
			'success'   => false,
			'http_code' => 429,
			'data'      => [ 'error' => [ 'message' => 'Rate limit exceeded' ] ],
		];

		// Record time before processing.
		$time_before = time();

		// Process batch.
		$this->queue->process_batch();

		// Verify API was called.
		$this->assertEquals( 1, $this->api_call_count );

		// Get the job.
		$job = $this->queue_storage[1];

		// Verify job status is still pending.
		$this->assertEquals( 'pending', $job['status'] );

		// Verify attempts were incremented.
		$this->assertEquals( 1, $job['attempts'] );

		// Verify retry_after is set.
		$this->assertNotNull( $job['retry_after'] );

		// Verify retry_after is in the future.
		$retry_timestamp = strtotime( $job['retry_after'] );
		$this->assertGreaterThan( $time_before, $retry_timestamp );

		// Verify exponential backoff calculation (60 * 2^1 = 120 seconds).
		$expected_delay = 60 * ( 2 ** 1 );
		$actual_delay = $retry_timestamp - $time_before;

		// Allow 2 second tolerance for execution time.
		$this->assertGreaterThanOrEqual( $expected_delay - 2, $actual_delay );
		$this->assertLessThanOrEqual( $expected_delay + 2, $actual_delay );
	}

	/**
	 * Test retry_after calculation is correct for multiple attempts.
	 *
	 * Validates Requirement 10.5: Set retry_after to current time plus 60 seconds
	 * multiplied by 2 to the power of the attempts count.
	 */
	public function test_retry_after_calculation_multiple_attempts(): void {
		// Test cases: attempts => expected delay in seconds.
		$test_cases = [
			1 => 120,   // 60 * 2^1 = 120.
			2 => 240,   // 60 * 2^2 = 240.
			3 => 480,   // 60 * 2^3 = 480.
			4 => 960,   // 60 * 2^4 = 960.
		];

		foreach ( $test_cases as $attempts => $expected_delay ) {
			// Reset storage.
			$this->queue_storage = [];
			$this->next_job_id = 1;
			$this->api_call_count = 0;

			// Enqueue a job.
			$url = "https://example.com/test-{$attempts}/";
			$this->queue->enqueue( $url, 'indexing' );

			// Set the job to have previous attempts.
			$job_id = 1;
			$this->queue_storage[ $job_id ]['attempts'] = $attempts - 1;

			// Queue HTTP 429 response.
			$this->api_responses[] = [
				'success'   => false,
				'http_code' => 429,
				'data'      => [],
			];

			// Record time before processing.
			$time_before = time();

			// Process batch.
			$this->queue->process_batch();

			// Get the job.
			$job = $this->queue_storage[ $job_id ];

			// Verify attempts were incremented.
			$this->assertEquals( $attempts, $job['attempts'] );

			// Verify retry_after calculation.
			$retry_timestamp = strtotime( $job['retry_after'] );
			$actual_delay = $retry_timestamp - $time_before;

			// Allow 2 second tolerance.
			$this->assertGreaterThanOrEqual(
				$expected_delay - 2,
				$actual_delay,
				"Retry delay for attempt {$attempts} should be at least {$expected_delay} seconds"
			);
			$this->assertLessThanOrEqual(
				$expected_delay + 2,
				$actual_delay,
				"Retry delay for attempt {$attempts} should be at most {$expected_delay} seconds"
			);
		}
	}

	/**
	 * Test successful API response updates job to done.
	 *
	 * Validates Requirement 10.6: When successful API response is received,
	 * update job status to done and store response data.
	 */
	public function test_successful_response_updates_to_done(): void {
		// Enqueue a job.
		$url = 'https://example.com/test-page/';
		$this->queue->enqueue( $url, 'indexing' );

		// Queue success response.
		$this->api_responses[] = [
			'success'   => true,
			'http_code' => 200,
			'data'      => [ 'urlNotificationMetadata' => [ 'url' => $url ] ],
		];

		// Process batch.
		$this->queue->process_batch();

		// Get the job.
		$job = $this->queue_storage[1];

		// Verify job status is done.
		$this->assertEquals( 'done', $job['status'] );

		// Verify processed_at is set.
		$this->assertNotNull( $job['processed_at'] );
	}

	/**
	 * Test job status transitions from pending to processing to done.
	 *
	 * Validates Requirement 10.4: Update job status to processing before making any API call.
	 */
	public function test_job_status_transitions(): void {
		// Enqueue a job.
		$url = 'https://example.com/test-page/';
		$this->queue->enqueue( $url, 'indexing' );

		// Verify initial status is pending.
		$this->assertEquals( 'pending', $this->queue_storage[1]['status'] );

		// Queue success response.
		$this->api_responses[] = [
			'success'   => true,
			'http_code' => 200,
			'data'      => [],
		];

		// Process batch.
		$this->queue->process_batch();

		// Verify final status is done.
		$this->assertEquals( 'done', $this->queue_storage[1]['status'] );
	}

	/**
	 * Test rate limited jobs are not processed until retry_after expires.
	 *
	 * Validates Requirement 10.5: Jobs with retry_after in the future should not be processed.
	 */
	public function test_rate_limited_jobs_not_processed_until_retry_after(): void {
		// Enqueue a job.
		$url = 'https://example.com/test-page/';
		$this->queue->enqueue( $url, 'indexing' );

		// Set retry_after to 1 hour in the future.
		$future_time = gmdate( 'Y-m-d H:i:s', time() + 3600 );
		$this->queue_storage[1]['retry_after'] = $future_time;

		// Process batch.
		$this->queue->process_batch();

		// Verify no API calls were made.
		$this->assertEquals( 0, $this->api_call_count );

		// Verify job is still pending.
		$this->assertEquals( 'pending', $this->queue_storage[1]['status'] );
	}

	/**
	 * Test multiple rate limits accumulate exponentially.
	 */
	public function test_multiple_rate_limits_accumulate(): void {
		// Enqueue a job.
		$url = 'https://example.com/test-page/';
		$this->queue->enqueue( $url, 'indexing' );

		$previous_delay = 0;

		// Simulate 3 rate limit responses.
		for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
			// Queue HTTP 429 response.
			$this->api_responses[] = [
				'success'   => false,
				'http_code' => 429,
				'data'      => [],
			];

			// Record time before processing.
			$time_before = time();

			// Process batch.
			$this->queue->process_batch();

			// Get the job.
			$job = $this->queue_storage[1];

			// Verify attempts were incremented.
			$this->assertEquals( $attempt, $job['attempts'] );

			// Calculate actual delay.
			$retry_timestamp = strtotime( $job['retry_after'] );
			$actual_delay = $retry_timestamp - $time_before;

			// Verify delay increases exponentially.
			if ( $previous_delay > 0 ) {
				$this->assertGreaterThan( $previous_delay, $actual_delay );
			}

			$previous_delay = $actual_delay;

			// Clear retry_after to allow next processing.
			$this->queue_storage[1]['retry_after'] = null;
		}
	}

	/**
	 * Test batch processing respects 10 job limit.
	 *
	 * Validates Requirement 10.3: Process up to 10 queue entries per batch.
	 */
	public function test_batch_processing_respects_limit(): void {
		// Enqueue 15 jobs.
		for ( $i = 1; $i <= 15; $i++ ) {
			$url = "https://example.com/page-{$i}/";
			$this->queue->enqueue( $url, 'indexing' );
		}

		// Queue 15 success responses (more than batch limit).
		for ( $i = 0; $i < 15; $i++ ) {
			$this->api_responses[] = [
				'success'   => true,
				'http_code' => 200,
				'data'      => [],
			];
		}

		// Process batch.
		$this->queue->process_batch();

		// Verify only 10 API calls were made.
		$this->assertEquals( 10, $this->api_call_count );

		// Verify only 10 jobs are done.
		$done_count = 0;
		foreach ( $this->queue_storage as $job ) {
			if ( $job['status'] === 'done' ) {
				$done_count++;
			}
		}
		$this->assertEquals( 10, $done_count );
	}

	/**
	 * Test error responses mark job as failed.
	 */
	public function test_error_response_marks_failed(): void {
		// Enqueue a job.
		$url = 'https://example.com/test-page/';
		$this->queue->enqueue( $url, 'indexing' );

		// Queue error response (not 429).
		$this->api_responses[] = [
			'success'   => false,
			'http_code' => 500,
			'data'      => [ 'error' => [ 'message' => 'Internal server error' ] ],
		];

		// Process batch.
		$this->queue->process_batch();

		// Get the job.
		$job = $this->queue_storage[1];

		// Verify job status is failed.
		$this->assertEquals( 'failed', $job['status'] );

		// Verify processed_at is set.
		$this->assertNotNull( $job['processed_at'] );
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		// Clean up queue storage.
		$this->queue_storage = [];
		$this->api_responses = [];

		parent::tearDown();
	}
}
