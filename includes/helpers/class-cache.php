<?php
/**
 * Cache Helper
 *
 * Wraps WordPress Object Cache with consistent key prefix and group isolation.
 * Falls back to transients when Object Cache is unavailable.
 *
 * Caching Strategy (Requirements 14.1, 14.2, 14.3):
 * - All cache keys use the meowseo_ prefix for isolation
 * - Cache group 'meowseo' provides namespace isolation
 * - Automatic fallback to WordPress transients when Object Cache unavailable
 * - SEO meta cached for 1 hour (3600s) to eliminate DB queries on frontend
 * - Sitemap paths cached persistently until invalidated
 * - 404 hits buffered in per-minute buckets for batch processing
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache helper class
 *
 * Provides a consistent interface for caching operations with automatic
 * fallback to WordPress transients when Object Cache is unavailable.
 *
 * @since 1.0.0
 */
class Cache {

	/**
	 * Cache key prefix
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PREFIX = 'meowseo_';

	/**
	 * Cache group name
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const GROUP = 'meowseo';

	/**
	 * Get a value from cache
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Cache key (without prefix).
	 * @return mixed Cached value or false if not found.
	 */
	public static function get( string $key ) {
		if ( self::is_object_cache_available() ) {
			return wp_cache_get( self::PREFIX . $key, self::GROUP );
		}

		return get_transient( self::PREFIX . $key );
	}

	/**
	 * Set a value in cache
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Cache key (without prefix).
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl   Time to live in seconds. 0 = no expiration.
	 * @return bool True on success, false on failure.
	 */
	public static function set( string $key, $value, int $ttl = 0 ): bool {
		if ( self::is_object_cache_available() ) {
			return wp_cache_set( self::PREFIX . $key, $value, self::GROUP, $ttl );
		}

		return set_transient( self::PREFIX . $key, $value, $ttl );
	}

	/**
	 * Delete a value from cache
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Cache key (without prefix).
	 * @return bool True on success, false on failure.
	 */
	public static function delete( string $key ): bool {
		if ( self::is_object_cache_available() ) {
			return wp_cache_delete( self::PREFIX . $key, self::GROUP );
		}

		return delete_transient( self::PREFIX . $key );
	}

	/**
	 * Add a value to cache (atomic operation)
	 *
	 * This method only succeeds if the key does not already exist.
	 * Used for implementing atomic locks to prevent cache stampedes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Cache key (without prefix).
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl   Time to live in seconds. 0 = no expiration.
	 * @return bool True if value was added, false if key already exists.
	 */
	public static function add( string $key, $value, int $ttl = 0 ): bool {
		if ( self::is_object_cache_available() ) {
			return wp_cache_add( self::PREFIX . $key, $value, self::GROUP, $ttl );
		}

		// Transient fallback: check if exists first (not truly atomic, but best effort).
		$transient_key = self::PREFIX . $key;
		if ( false !== get_transient( $transient_key ) ) {
			return false;
		}

		return set_transient( $transient_key, $value, $ttl );
	}

	/**
	 * Check if persistent Object Cache is available
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if Object Cache is available, false otherwise.
	 */
	private static function is_object_cache_available(): bool {
		return wp_using_ext_object_cache();
	}
}
