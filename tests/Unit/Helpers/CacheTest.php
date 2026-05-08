<?php
/**
 * Tests for Cache class.
 *
 * @package MeowSEO\Tests\Unit\Helpers
 */

namespace MeowSEO\Tests\Unit\Helpers;

use MeowSEO\Helpers\Cache;
use MeowSEO\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Cache test case.
 */
class CacheTest extends TestCase {

	/**
	 * Test cache get method with transient fallback.
	 *
	 * @return void
	 */
	public function test_get_uses_transient_fallback(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );
		Functions\when( 'get_transient' )->alias( function( $key ) {
			if ( $key === 'meowseo_test_key' ) {
				return 'test_value';
			}
			return false;
		} );

		$value = Cache::get( 'test_key' );

		$this->assertEquals( 'test_value', $value );
	}

	/**
	 * Test cache set method with transient fallback.
	 *
	 * @return void
	 */
	public function test_set_uses_transient_fallback(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );
		Functions\expect( 'set_transient' )
			->once()
			->with( 'meowseo_test_key', 'test_value', 3600 )
			->andReturn( true );

		$result = Cache::set( 'test_key', 'test_value', 3600 );

		$this->assertTrue( $result );
	}

	/**
	 * Test cache delete method with transient fallback.
	 *
	 * @return void
	 */
	public function test_delete_uses_transient_fallback(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );
		Functions\expect( 'delete_transient' )
			->once()
			->with( 'meowseo_test_key' )
			->andReturn( true );

		$result = Cache::delete( 'test_key' );

		$this->assertTrue( $result );
	}

	/**
	 * Test cache add method with transient fallback.
	 *
	 * @return void
	 */
	public function test_add_uses_transient_fallback(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'set_transient' )
			->once()
			->with( 'meowseo_test_key', 'test_value', 3600 )
			->andReturn( true );

		$result = Cache::add( 'test_key', 'test_value', 3600 );

		$this->assertTrue( $result );
	}

	/**
	 * Test cache add returns false when key exists.
	 *
	 * @return void
	 */
	public function test_add_returns_false_when_key_exists(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );
		Functions\when( 'get_transient' )->justReturn( 'existing_value' );

		$result = Cache::add( 'test_key', 'test_value', 3600 );

		$this->assertFalse( $result );
	}

	/**
	 * Test cache get with object cache.
	 *
	 * @return void
	 */
	public function test_get_uses_object_cache_when_available(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
		Functions\expect( 'wp_cache_get' )
			->once()
			->with( 'meowseo_test_key', 'meowseo' )
			->andReturn( 'cached_value' );

		$value = Cache::get( 'test_key' );

		$this->assertEquals( 'cached_value', $value );
	}

	/**
	 * Test cache set with object cache.
	 *
	 * @return void
	 */
	public function test_set_uses_object_cache_when_available(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
		Functions\expect( 'wp_cache_set' )
			->once()
			->with( 'meowseo_test_key', 'test_value', 'meowseo', 3600 )
			->andReturn( true );

		$result = Cache::set( 'test_key', 'test_value', 3600 );

		$this->assertTrue( $result );
	}

	/**
	 * Test cache delete with object cache.
	 *
	 * @return void
	 */
	public function test_delete_uses_object_cache_when_available(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
		Functions\expect( 'wp_cache_delete' )
			->once()
			->with( 'meowseo_test_key', 'meowseo' )
			->andReturn( true );

		$result = Cache::delete( 'test_key' );

		$this->assertTrue( $result );
	}

	/**
	 * Test cache add with object cache.
	 *
	 * @return void
	 */
	public function test_add_uses_object_cache_when_available(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
		Functions\expect( 'wp_cache_add' )
			->once()
			->with( 'meowseo_test_key', 'test_value', 'meowseo', 3600 )
			->andReturn( true );

		$result = Cache::add( 'test_key', 'test_value', 3600 );

		$this->assertTrue( $result );
	}

	/**
	 * Test cache prefix is applied.
	 *
	 * @return void
	 */
	public function test_cache_prefix_is_applied(): void {
		$this->assertEquals( 'meowseo_', Cache::PREFIX );
	}

	/**
	 * Test cache group is correct.
	 *
	 * @return void
	 */
	public function test_cache_group_is_correct(): void {
		$this->assertEquals( 'meowseo', Cache::GROUP );
	}
}
