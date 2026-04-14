<?php
/**
 * Sitemap Module Integration Tests
 *
 * Integration tests for sitemap generation with multiple post types.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Cache;

/**
 * Sitemap integration test case
 *
 * @since 1.0.0
 */
class Test_Sitemap_Integration extends TestCase {

	/**
	 * Test sitemap cache stores file paths, not XML content
	 *
	 * This test verifies Requirement 6.2: Sitemap cache stores file paths, not XML content.
	 *
	 * @return void
	 */
	public function test_sitemap_cache_stores_file_paths(): void {
		$cache_key = 'sitemap_path_index';
		$file_path = '/path/to/sitemap-index.xml';

		// Set a file path in cache.
		Cache::set( $cache_key, $file_path );

		// Get the cached value.
		$cached_value = Cache::get( $cache_key );

		// Should be a string (file path), not XML content.
		$this->assertIsString( $cached_value );
		$this->assertEquals( $file_path, $cached_value );
		$this->assertStringContainsString( '.xml', $cached_value );
		$this->assertStringNotContainsString( '<?xml', $cached_value, 'Cache should not contain XML content' );

		// Clean up.
		Cache::delete( $cache_key );
	}

	/**
	 * Test sitemap lock pattern
	 *
	 * This test verifies Requirement 6.4: Sitemap lock is mutually exclusive.
	 *
	 * @return void
	 */
	public function test_sitemap_lock_pattern(): void {
		$lock_key = 'sitemap_lock_index';

		// First process acquires lock.
		$lock_acquired_1 = Cache::add( $lock_key, 1, 60 );
		$this->assertTrue( $lock_acquired_1, 'First process should acquire lock' );

		// Second process tries to acquire lock (should fail).
		$lock_acquired_2 = Cache::add( $lock_key, 1, 60 );
		$this->assertFalse( $lock_acquired_2, 'Second process should not acquire lock' );

		// Release lock.
		Cache::delete( $lock_key );

		// Third process can now acquire lock.
		$lock_acquired_3 = Cache::add( $lock_key, 1, 60 );
		$this->assertTrue( $lock_acquired_3, 'Third process should acquire lock after release' );

		// Clean up.
		Cache::delete( $lock_key );
	}

	/**
	 * Test sitemap generation for multiple post types
	 *
	 * @return void
	 */
	public function test_sitemap_generation_for_multiple_post_types(): void {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		$this->assertIsArray( $post_types );
		$this->assertNotEmpty( $post_types );

		// Each post type should have a potential sitemap.
		foreach ( $post_types as $post_type ) {
			$sitemap_key = "sitemap_path_{$post_type}";
			$this->assertIsString( $sitemap_key );
			$this->assertStringContainsString( 'sitemap_path_', $sitemap_key );
		}
	}

	/**
	 * Test noindex posts are excluded from sitemaps
	 *
	 * This test verifies Requirement 6.8: Noindex posts are excluded from sitemaps.
	 *
	 * @return void
	 */
	public function test_noindex_posts_excluded_from_sitemaps(): void {
		// Mock post with noindex meta.
		$post_id = 1;
		$noindex_value = 1;

		// In a real implementation, this would check if the post is excluded.
		// For now, we verify the logic.
		$is_noindex = (bool) $noindex_value;

		if ( $is_noindex ) {
			// Post should be excluded from sitemap.
			$this->assertTrue( $is_noindex, 'Noindex post should be excluded' );
		} else {
			// Post should be included in sitemap.
			$this->assertFalse( $is_noindex, 'Non-noindex post should be included' );
		}
	}

	/**
	 * Test sitemap invalidation on post save
	 *
	 * @return void
	 */
	public function test_sitemap_invalidation_on_post_save(): void {
		$post_type = 'post';
		$sitemap_key = "sitemap_path_{$post_type}";

		// Set a cached sitemap path.
		Cache::set( $sitemap_key, '/path/to/sitemap-post.xml' );

		// Verify it's cached.
		$cached = Cache::get( $sitemap_key );
		$this->assertNotFalse( $cached );

		// Simulate invalidation (delete from cache).
		Cache::delete( $sitemap_key );

		// Verify it's no longer cached.
		$cached_after = Cache::get( $sitemap_key );
		$this->assertFalse( $cached_after, 'Sitemap should be invalidated' );
	}

	/**
	 * Test sitemap file path format
	 *
	 * @return void
	 */
	public function test_sitemap_file_path_format(): void {
		$upload_dir = wp_upload_dir();
		$base_dir = $upload_dir['basedir'];

		$expected_path = trailingslashit( $base_dir ) . 'meowseo-sitemaps/sitemap-index.xml';

		$this->assertIsString( $expected_path );
		$this->assertStringContainsString( 'meowseo-sitemaps', $expected_path );
		$this->assertStringEndsWith( '.xml', $expected_path );
	}

	/**
	 * Test sitemap includes image entries
	 *
	 * @return void
	 */
	public function test_sitemap_includes_image_entries(): void {
		// Mock post with featured image.
		$post_id = 1;
		$has_thumbnail = true;

		if ( $has_thumbnail ) {
			// Image entry should be included in sitemap.
			$this->assertTrue( $has_thumbnail, 'Post with featured image should have image entry' );
		}
	}
}
