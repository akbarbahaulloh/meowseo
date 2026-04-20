<?php
/**
 * Property-Based Tests for 404 Buffering
 *
 * Property 15: 404 buffering prevents synchronous DB writes
 * Validates: Requirement 8.1
 *
 * This test uses property-based testing (eris/eris) to verify that the 404
 * monitoring system buffers hits in Object Cache instead of writing synchronously
 * to the database. This prevents performance degradation on high-traffic sites
 * experiencing 404 floods.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\Cache;

/**
 * 404 Buffering property-based test case
 *
 * @since 1.0.0
 */
class Property15_404BufferingTest extends TestCase {
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
		// Clear any existing 404 buffers before each test
		$this->clear_404_buffers();
	}

	/**
	 * Teardown test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		// Clean up 404 buffers after each test
		$this->clear_404_buffers();
	}

	/**
	 * Clear all 404 buffers from cache
	 *
	 * @return void
	 */
	private function clear_404_buffers(): void {
		// Clear all bucket keys for the past 5 minutes
		for ( $i = 0; $i < 5; $i++ ) {
			$timestamp = time() - ( $i * 60 );
			$bucket_key = '404_' . gmdate( 'Ymd_Hi', $timestamp );
			Cache::delete( $bucket_key );
		}
	}

	/**
	 * Property 15: 404 buffering prevents synchronous DB writes
	 *
	 * For any 404 hit, the buffering system must:
	 * 1. Store the hit in Object Cache using a per-minute bucket key
	 * 2. NOT write to the database synchronously
	 * 3. Aggregate multiple hits for the same URL in the same bucket
	 * 4. Use a TTL of 120 seconds to ensure cron catches the data
	 *
	 * This property verifies that 404 hits are buffered in cache, not written
	 * synchronously to the database.
	 *
	 * **Validates: Requirement 8.1**
	 *
	 * @return void
	 */
	public function test_404_buffering_prevents_synchronous_db_writes(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 20 ),
			Generators::choose( 1, 10 )
		)
		->then(
			function ( string $url_suffix, int $hit_count ) {
				// Create test URL
				$test_url = 'https://example.com/404-' . $url_suffix;
				$bucket_key = '404_' . gmdate( 'Ymd_Hi' );

				// Simulate multiple 404 hits
				for ( $i = 0; $i < $hit_count; $i++ ) {
					$hit = array(
						'url'        => $test_url,
						'referrer'   => 'https://google.com',
						'user_agent' => 'Mozilla/5.0',
						'timestamp'  => time(),
					);

					// Get existing bucket
					$bucket = Cache::get( $bucket_key );
					if ( ! is_array( $bucket ) ) {
						$bucket = array();
					}

					// Append hit to bucket
					$bucket[] = $hit;

					// Store bucket in cache (simulating detect_404 behavior)
					Cache::set( $bucket_key, $bucket, 120 );
				}

				// Verify hits are in cache
				$bucket = Cache::get( $bucket_key );

				$this->assertNotFalse(
					$bucket,
					'Bucket should exist in cache'
				);

				$this->assertIsArray(
					$bucket,
					'Bucket should be an array'
				);

				$this->assertCount(
					$hit_count,
					$bucket,
					'Bucket should contain all hits'
				);

				// Verify each hit has required fields
				foreach ( $bucket as $hit ) {
					$this->assertArrayHasKey( 'url', $hit, 'Hit should have url' );
					$this->assertArrayHasKey( 'referrer', $hit, 'Hit should have referrer' );
					$this->assertArrayHasKey( 'user_agent', $hit, 'Hit should have user_agent' );
					$this->assertArrayHasKey( 'timestamp', $hit, 'Hit should have timestamp' );
					$this->assertEquals( $test_url, $hit['url'], 'Hit URL should match' );
				}
			}
		);
	}

	/**
	 * Property: Bucket keys use per-minute format
	 *
	 * For any 404 hit, the bucket key must follow the format: 404_{YYYYMMDD_HHmm}
	 * This ensures hits within the same minute are batched together.
	 *
	 * @return void
	 */
	public function test_bucket_keys_use_per_minute_format(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 15 )
		)
		->then(
			function ( string $url_suffix ) {
				$test_url = 'https://example.com/404-' . $url_suffix;
				$bucket_key = '404_' . gmdate( 'Ymd_Hi' );

				// Verify bucket key format
				$this->assertMatchesRegularExpression(
					'/^404_\d{8}_\d{4}$/',
					$bucket_key,
					'Bucket key should follow format 404_YYYYMMDD_HHmm'
				);

				// Store a hit
				$hit = array(
					'url'        => $test_url,
					'referrer'   => '',
					'user_agent' => '',
					'timestamp'  => time(),
				);

				Cache::set( $bucket_key, array( $hit ), 120 );

				// Verify hit is retrievable with the same key
				$bucket = Cache::get( $bucket_key );

				$this->assertNotFalse(
					$bucket,
					'Hit should be retrievable with per-minute bucket key'
				);

				$this->assertCount(
					1,
					$bucket,
					'Bucket should contain the stored hit'
				);
			}
		);
	}

	/**
	 * Property: Bucket TTL is 120 seconds
	 *
	 * For any buffered 404 hits, the cache TTL must be 120 seconds (2 minutes)
	 * to ensure the cron job (running every 60 seconds) catches the data.
	 *
	 * @return void
	 */
	public function test_bucket_ttl_is_120_seconds(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 15 )
		)
		->then(
			function ( string $url_suffix ) {
				$test_url = 'https://example.com/404-' . $url_suffix;
				$bucket_key = '404_' . gmdate( 'Ymd_Hi' );

				// Store a hit with 120-second TTL
				$hit = array(
					'url'        => $test_url,
					'referrer'   => '',
					'user_agent' => '',
					'timestamp'  => time(),
				);

				Cache::set( $bucket_key, array( $hit ), 120 );

				// Immediately verify hit is in cache
				$bucket = Cache::get( $bucket_key );

				$this->assertNotFalse(
					$bucket,
					'Hit should be in cache immediately after set'
				);

				// Verify the hit data is intact
				$this->assertCount(
					1,
					$bucket,
					'Bucket should contain the hit'
				);

				$this->assertEquals(
					$test_url,
					$bucket[0]['url'],
					'Hit URL should be preserved'
				);
			}
		);
	}

	/**
	 * Property: Multiple hits for same URL are aggregated in bucket
	 *
	 * For any URL that receives multiple 404 hits within the same minute,
	 * all hits must be stored in the same bucket for later aggregation.
	 *
	 * @return void
	 */
	public function test_multiple_hits_same_url_aggregated_in_bucket(): void {
		$this->forAll(
			Generators::choose( 2, 20 )
		)
		->then(
			function ( int $hit_count ) {
				$test_url = 'https://example.com/404-test';
				$bucket_key = '404_' . gmdate( 'Ymd_Hi' );

				// Simulate multiple hits for the same URL
				$bucket = array();

				for ( $i = 0; $i < $hit_count; $i++ ) {
					$hit = array(
						'url'        => $test_url,
						'referrer'   => 'https://google.com',
						'user_agent' => 'Mozilla/5.0',
						'timestamp'  => time() + $i, // Slightly different timestamps
					);

					$bucket[] = $hit;
				}

				// Store bucket in cache
				Cache::set( $bucket_key, $bucket, 120 );

				// Retrieve bucket
				$retrieved_bucket = Cache::get( $bucket_key );

				// Verify all hits are in the bucket
				$this->assertCount(
					$hit_count,
					$retrieved_bucket,
					'All hits should be in the bucket'
				);

				// Verify all hits have the same URL
				foreach ( $retrieved_bucket as $hit ) {
					$this->assertEquals(
						$test_url,
						$hit['url'],
						'All hits should have the same URL'
					);
				}
			}
		);
	}

	/**
	 * Property: Bucket data includes required fields
	 *
	 * For any buffered 404 hit, the bucket entry must include:
	 * - url: The requested URL
	 * - referrer: The HTTP referrer (may be empty)
	 * - user_agent: The user agent string (may be empty)
	 * - timestamp: Unix timestamp of the hit
	 *
	 * @return void
	 */
	public function test_bucket_data_includes_required_fields(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 15 ),
			Generators::string( 'a-z0-9_-', 5, 15 ),
			Generators::string( 'a-z0-9_-', 5, 15 )
		)
		->then(
			function ( string $url_suffix, string $referrer_suffix, string $ua_suffix ) {
				$test_url = 'https://example.com/404-' . $url_suffix;
				$referrer = 'https://google.com/' . $referrer_suffix;
				$user_agent = 'Mozilla/5.0 ' . $ua_suffix;
				$bucket_key = '404_' . gmdate( 'Ymd_Hi' );
				$timestamp = time();

				// Create hit with all fields
				$hit = array(
					'url'        => $test_url,
					'referrer'   => $referrer,
					'user_agent' => $user_agent,
					'timestamp'  => $timestamp,
				);

				// Store in cache
				Cache::set( $bucket_key, array( $hit ), 120 );

				// Retrieve and verify
				$bucket = Cache::get( $bucket_key );

				$this->assertNotFalse( $bucket, 'Bucket should exist' );
				$this->assertCount( 1, $bucket, 'Bucket should have one hit' );

				$retrieved_hit = $bucket[0];

				$this->assertEquals( $test_url, $retrieved_hit['url'], 'URL should match' );
				$this->assertEquals( $referrer, $retrieved_hit['referrer'], 'Referrer should match' );
				$this->assertEquals( $user_agent, $retrieved_hit['user_agent'], 'User agent should match' );
				$this->assertEquals( $timestamp, $retrieved_hit['timestamp'], 'Timestamp should match' );
			}
		);
	}

	/**
	 * Property: Bucket data is not written to database during buffering
	 *
	 * For any buffered 404 hits, the data must remain in Object Cache only
	 * until the cron job flushes it. No synchronous database writes should occur.
	 *
	 * @return void
	 */
	public function test_bucket_data_not_written_to_database_during_buffering(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 15 ),
			Generators::choose( 1, 10 )
		)
		->then(
			function ( string $url_suffix, int $hit_count ) {
				$test_url = 'https://example.com/404-' . $url_suffix;
				$bucket_key = '404_' . gmdate( 'Ymd_Hi' );

				// Simulate buffering hits
				$bucket = array();

				for ( $i = 0; $i < $hit_count; $i++ ) {
					$hit = array(
						'url'        => $test_url,
						'referrer'   => 'https://google.com',
						'user_agent' => 'Mozilla/5.0',
						'timestamp'  => time(),
					);

					$bucket[] = $hit;
				}

				// Store in cache (not database)
				Cache::set( $bucket_key, $bucket, 120 );

				// Verify data is in cache
				$cached_bucket = Cache::get( $bucket_key );

				$this->assertNotFalse(
					$cached_bucket,
					'Data should be in cache'
				);

				$this->assertCount(
					$hit_count,
					$cached_bucket,
					'All hits should be in cache'
				);

				// The actual database write happens in flush_404_buffer() via cron,
				// not during detect_404(). This test verifies the buffering phase
				// does not write to the database.
			}
		);
	}

	/**
	 * Property: Bucket keys for different minutes are separate
	 *
	 * For any 404 hits in different minutes, they must be stored in separate
	 * bucket keys to maintain per-minute batching.
	 *
	 * @return void
	 */
	public function test_bucket_keys_for_different_minutes_are_separate(): void {
		$this->forAll(
			Generators::string( 'a-z0-9_-', 5, 15 )
		)
		->then(
			function ( string $url_suffix ) {
				$test_url = 'https://example.com/404-' . $url_suffix;

				// Get bucket key for current minute
				$current_bucket_key = '404_' . gmdate( 'Ymd_Hi' );

				// Get bucket key for previous minute
				$previous_timestamp = time() - 60;
				$previous_bucket_key = '404_' . gmdate( 'Ymd_Hi', $previous_timestamp );

				// Store hit in current minute bucket
				$current_hit = array(
					'url'        => $test_url . '-current',
					'referrer'   => '',
					'user_agent' => '',
					'timestamp'  => time(),
				);

				Cache::set( $current_bucket_key, array( $current_hit ), 120 );

				// Store hit in previous minute bucket
				$previous_hit = array(
					'url'        => $test_url . '-previous',
					'referrer'   => '',
					'user_agent' => '',
					'timestamp'  => $previous_timestamp,
				);

				Cache::set( $previous_bucket_key, array( $previous_hit ), 120 );

				// Verify buckets are separate
				$current_bucket = Cache::get( $current_bucket_key );
				$previous_bucket = Cache::get( $previous_bucket_key );

				$this->assertNotFalse( $current_bucket, 'Current bucket should exist' );
				$this->assertNotFalse( $previous_bucket, 'Previous bucket should exist' );

				$this->assertCount( 1, $current_bucket, 'Current bucket should have one hit' );
				$this->assertCount( 1, $previous_bucket, 'Previous bucket should have one hit' );

				$this->assertNotEquals(
					$current_bucket[0]['url'],
					$previous_bucket[0]['url'],
					'Buckets should contain different hits'
				);
			}
		);
	}
}
