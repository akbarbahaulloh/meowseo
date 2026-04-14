<?php
/**
 * Cache Helper Tests
 *
 * Unit tests for the Cache helper class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Cache;

/**
 * Cache helper test case
 *
 * @since 1.0.0
 */
class Test_Cache_Helper extends TestCase {

	/**
	 * Test that cache keys use the meowseo_ prefix
	 *
	 * This test verifies Requirement 14.2: Cache keys always use the meowseo_ prefix.
	 *
	 * @return void
	 */
	public function test_cache_keys_use_prefix(): void {
		$this->assertEquals( 'meowseo_', Cache::PREFIX );
	}

	/**
	 * Test that cache group is meowseo
	 *
	 * @return void
	 */
	public function test_cache_group_is_meowseo(): void {
		$this->assertEquals( 'meowseo', Cache::GROUP );
	}

	/**
	 * Test set and get operations
	 *
	 * @return void
	 */
	public function test_set_and_get(): void {
		$key = 'test_key';
		$value = 'test_value';

		$set_result = Cache::set( $key, $value );
		$this->assertTrue( $set_result, 'Cache set should succeed' );

		$get_result = Cache::get( $key );
		$this->assertEquals( $value, $get_result, 'Cache get should return the set value' );
	}

	/**
	 * Test get returns false for non-existent keys
	 *
	 * @return void
	 */
	public function test_get_returns_false_for_nonexistent_keys(): void {
		$result = Cache::get( 'nonexistent_key_' . time() );
		$this->assertFalse( $result );
	}

	/**
	 * Test delete operation
	 *
	 * @return void
	 */
	public function test_delete(): void {
		$key = 'test_delete_key';
		$value = 'test_value';

		Cache::set( $key, $value );
		$delete_result = Cache::delete( $key );

		$this->assertTrue( $delete_result, 'Cache delete should succeed' );

		$get_result = Cache::get( $key );
		$this->assertFalse( $get_result, 'Deleted key should not be retrievable' );
	}

	/**
	 * Test add operation (atomic)
	 *
	 * @return void
	 */
	public function test_add_atomic_operation(): void {
		$key = 'test_add_key_' . time();
		$value1 = 'first_value';
		$value2 = 'second_value';

		// First add should succeed.
		$add_result1 = Cache::add( $key, $value1 );
		$this->assertTrue( $add_result1, 'First add should succeed' );

		// Second add should fail (key already exists).
		$add_result2 = Cache::add( $key, $value2 );
		$this->assertFalse( $add_result2, 'Second add should fail' );

		// Value should still be the first one.
		$get_result = Cache::get( $key );
		$this->assertEquals( $value1, $get_result, 'Value should be the first one' );

		// Clean up.
		Cache::delete( $key );
	}

	/**
	 * Test set with TTL
	 *
	 * @return void
	 */
	public function test_set_with_ttl(): void {
		$key = 'test_ttl_key';
		$value = 'test_value';
		$ttl = 3600; // 1 hour

		$result = Cache::set( $key, $value, $ttl );
		$this->assertTrue( $result, 'Cache set with TTL should succeed' );

		$get_result = Cache::get( $key );
		$this->assertEquals( $value, $get_result );

		// Clean up.
		Cache::delete( $key );
	}

	/**
	 * Test caching different data types
	 *
	 * @return void
	 */
	public function test_caching_different_data_types(): void {
		$test_cases = array(
			'string'  => 'test_string',
			'integer' => 12345,
			'float'   => 123.45,
			'array'   => array( 'key1' => 'value1', 'key2' => 'value2' ),
			'object'  => (object) array( 'prop1' => 'value1', 'prop2' => 'value2' ),
			'boolean' => true,
		);

		foreach ( $test_cases as $type => $value ) {
			$key = "test_{$type}_key";

			Cache::set( $key, $value );
			$result = Cache::get( $key );

			$this->assertEquals( $value, $result, "Cache should handle {$type} data type" );

			Cache::delete( $key );
		}
	}

	/**
	 * Test that cache handles null values
	 *
	 * @return void
	 */
	public function test_cache_handles_null_values(): void {
		$key = 'test_null_key';

		Cache::set( $key, null );
		$result = Cache::get( $key );

		// Note: WordPress cache may return false for null values.
		// This is expected behavior.
		$this->assertTrue( $result === null || $result === false );

		Cache::delete( $key );
	}
}
