<?php
/**
 * Sitemap Cache
 *
 * Filesystem-based cache manager with lock pattern to prevent cache stampede.
 * Implements stale-while-revalidate pattern for high-performance sitemap serving.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Sitemap;

use MeowSEO\Helpers\Cache;
use MeowSEO\Helpers\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sitemap Cache class
 *
 * Manages filesystem-based sitemap caching with lock pattern to prevent
 * cache stampede on high-traffic sites.
 *
 * @since 1.0.0
 */
class Sitemap_Cache {

	/**
	 * Cache directory path
	 *
	 * @var string
	 */
	private string $cache_dir;

	/**
	 * Lock timeout in seconds
	 *
	 * @var int
	 */
	private int $lock_timeout = 60;

	/**
	 * Cache TTL in seconds (24 hours)
	 *
	 * @var int
	 */
	private const CACHE_TTL = 86400;

	/**
	 * Constructor
	 *
	 * Initializes cache directory and ensures it exists.
	 */
	public function __construct() {
		// Handle case where wp_upload_dir() might not be available (e.g., in tests)
		if ( function_exists( 'wp_upload_dir' ) ) {
			$upload_dir = wp_upload_dir();
			$this->cache_dir = trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';
		} else {
			// Fallback for test environments
			$this->cache_dir = sys_get_temp_dir() . '/meowseo-sitemaps';
		}
		
		// Ensure directory exists on instantiation
		$this->ensure_directory_exists();
	}

	/**
	 * Ensure cache directory exists
	 *
	 * Creates directory with proper permissions and adds .htaccess
	 * to deny direct access (Requirement 4.1).
	 * Implements Requirement 13.4 for directory creation error logging.
	 * Implements Requirement 2.14 for parent directory validation.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function ensure_directory_exists(): bool {
		if ( file_exists( $this->cache_dir ) ) {
			return true;
		}

		// Requirement 2.14: Validate parent directory is writable before creation
		// Check if parent directory is writable before attempting mkdir
		if ( is_writable( dirname( $this->cache_dir ) ) ) {
			// Parent is writable, proceed with mkdir
			// Use wp_mkdir_p if available, otherwise use mkdir
			$mkdir_func = function_exists( 'wp_mkdir_p' ) ? 'wp_mkdir_p' : 'mkdir';
			if ( ! $mkdir_func( $this->cache_dir, 0755, true ) ) {
				$parent_dir = dirname( $this->cache_dir );
				Logger::error(
					'Sitemap cache directory creation failed',
					array(
						'directory'   => $this->cache_dir,
						'error'       => $mkdir_func . '() failed',
						'permissions' => is_writable( $parent_dir ) ? 'writable' : 'not writable',
						'parent_dir'  => $parent_dir,
						'disk_space'  => disk_free_space( $parent_dir ) !== false 
							? size_format( disk_free_space( $parent_dir ) )
							: 'unknown',
					)
				);
				return false;
			}
		} else {
			// Parent is not writable
			$parent_dir = dirname( $this->cache_dir );
			
			Logger::error(
				'Sitemap cache parent directory is not writable',
				array(
					'parent_dir'  => $parent_dir,
					'cache_dir'   => $this->cache_dir,
					'error'       => 'Parent directory exists but is not writable',
					'permissions' => substr( sprintf( '%o', fileperms( $parent_dir ) ), -4 ),
					'owner'       => function_exists( 'posix_getpwuid' ) && function_exists( 'fileowner' ) 
						? posix_getpwuid( fileowner( $parent_dir ) )['name'] ?? 'unknown'
						: 'unknown',
				)
			);
			
			// Provide fallback location suggestion
			$fallback_dir = sys_get_temp_dir() . '/meowseo-sitemaps';
			Logger::info(
				'Consider using fallback cache directory',
				array(
					'fallback_dir' => $fallback_dir,
					'writable'     => is_writable( sys_get_temp_dir() ) ? 'yes' : 'no',
				)
			);
			
			return false;
		}

		// Add .htaccess to deny direct access
		$htaccess_content = "# Deny direct access to sitemap files\n";
		$htaccess_content .= "# Files are served through WordPress rewrite rules\n";
		$htaccess_content .= "Order deny,allow\n";
		$htaccess_content .= "Deny from all\n";

		$htaccess_path = $this->cache_dir . '/.htaccess';
		if ( false === file_put_contents( $htaccess_path, $htaccess_content ) ) {
			Logger::error(
				'Failed to create .htaccess in sitemap cache directory',
				array(
					'file_path'   => $htaccess_path,
					'error'       => 'file_put_contents() failed',
					'directory'   => $this->cache_dir,
					'permissions' => is_writable( $this->cache_dir ) ? 'writable' : 'not writable',
				)
			);
		}

		return true;
	}

	/**
	 * Get file path for a sitemap
	 *
	 * Helper method to construct file path from sitemap name.
	 * Security: Validates file paths to prevent directory traversal (Requirement 19.2).
	 *
	 * @param string $name Sitemap name (e.g., 'index', 'posts', 'pages').
	 * @return string Full file path.
	 */
	private function get_file_path( string $name ): string {
		// Security: Sanitize filename to prevent directory traversal (Requirement 19.2).
		$sanitized_name = sanitize_file_name( $name );
		
		// Security: Remove any path separators that might have survived sanitization.
		$sanitized_name = str_replace( array( '/', '\\', '..' ), '', $sanitized_name );
		
		// Security: Ensure the name is not empty after sanitization.
		if ( empty( $sanitized_name ) ) {
			$sanitized_name = 'sitemap';
		}

		$file_path = $this->cache_dir . '/' . $sanitized_name . '.xml';

		// Security: Verify the resolved path is within cache directory (Requirement 19.2).
		$real_cache_dir = realpath( $this->cache_dir );
		$real_file_path = realpath( dirname( $file_path ) ) . '/' . basename( $file_path );

		if ( false === $real_cache_dir || false === strpos( $real_file_path, $real_cache_dir ) ) {
			Logger::error(
				'Potential directory traversal attempt detected',
				array(
					'requested_name' => $name,
					'sanitized_name' => $sanitized_name,
					'cache_dir'      => $this->cache_dir,
					'file_path'      => $file_path,
				)
			);
			// Return safe default path.
			return $this->cache_dir . '/sitemap.xml';
		}

		return $file_path;
	}

	/**
	 * Check if cached file is fresh
	 *
	 * Checks file modification time against 24-hour TTL (Requirement 4.1).
	 *
	 * @param string $file_path Full file path.
	 * @return bool True if file is fresh, false otherwise.
	 */
	private function is_fresh( string $file_path ): bool {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$file_time = filemtime( $file_path );
		if ( false === $file_time ) {
			return false;
		}

		$age = time() - $file_time;
		return $age < self::CACHE_TTL;
	}

	/**
	 * Get cached sitemap XML
	 *
	 * Reads XML content from filesystem (Requirement 4.3).
	 *
	 * @param string $name Sitemap name.
	 * @return string|null XML content or null if not found.
	 */
	public function get( string $name ): ?string {
		$file_path = $this->get_file_path( $name );

		if ( ! file_exists( $file_path ) ) {
			return null;
		}

		$content = file_get_contents( $file_path );
		if ( false === $content ) {
			Logger::error(
				'Failed to read sitemap file',
				array(
					'file_path'   => $file_path,
					'sitemap_name' => $name,
					'error'       => 'file_get_contents() failed',
					'file_exists' => file_exists( $file_path ) ? 'yes' : 'no',
					'readable'    => is_readable( $file_path ) ? 'yes' : 'no',
				)
			);
			return null;
		}

		return $content;
	}

	/**
	 * Set cached sitemap XML
	 *
	 * Writes XML content to filesystem and stores file path in Object Cache
	 * (Requirements 4.2, 4.4).
	 * Implements Requirement 13.2 for file write error logging.
	 *
	 * @param string $name        Sitemap name.
	 * @param string $xml_content XML content to cache.
	 * @return bool True on success, false on failure.
	 */
	public function set( string $name, string $xml_content ): bool {
		if ( ! $this->ensure_directory_exists() ) {
			return false;
		}

		$file_path = $this->get_file_path( $name );

		// Write XML to filesystem (Requirement 13.2).
		if ( false === file_put_contents( $file_path, $xml_content ) ) {
			Logger::error(
				'Sitemap file write failed',
				array(
					'file_path'    => $file_path,
					'sitemap_name' => $name,
					'error'        => 'file_put_contents() failed',
					'directory'    => $this->cache_dir,
					'writable'     => is_writable( $this->cache_dir ) ? 'yes' : 'no',
					'content_size' => strlen( $xml_content ),
				)
			);
			return false;
		}

		// Store file path in Object Cache, not content (Requirement 4.2)
		$cache_key = 'sitemap_path_' . $name;
		Cache::set( $cache_key, $file_path, 0 ); // 0 = persistent until invalidated

		return true;
	}

	/**
	 * Invalidate specific sitemap
	 *
	 * Deletes sitemap file and removes path from Object Cache (Requirement 4.5).
	 *
	 * @param string $name Sitemap name.
	 * @return bool True on success, false on failure.
	 */
	public function invalidate( string $name ): bool {
		$file_path = $this->get_file_path( $name );

		// Delete file if it exists
		if ( file_exists( $file_path ) ) {
			if ( ! unlink( $file_path ) ) {
				Logger::error(
					'Failed to delete sitemap file',
					array(
						'file_path'    => $file_path,
						'sitemap_name' => $name,
						'error'        => 'unlink() failed',
						'writable'     => is_writable( $file_path ) ? 'yes' : 'no',
					)
				);
				return false;
			}
		}

		// Remove path from Object Cache
		$cache_key = 'sitemap_path_' . $name;
		Cache::delete( $cache_key );

		/**
		 * Action when sitemap cache is invalidated
		 *
		 * Fires after a sitemap file is deleted from cache.
		 *
		 * @since 1.0.0
		 * @param string $name Sitemap name.
		 */
		do_action( 'meowseo_sitemap_cache_invalidated', $name );

		return true;
	}

	/**
	 * Invalidate all sitemaps
	 *
	 * Deletes all sitemap files in cache directory (Requirement 4.6).
	 *
	 * @return bool True on success, false on failure.
	 */
	public function invalidate_all(): bool {
		if ( ! file_exists( $this->cache_dir ) ) {
			return true;
		}

		$files = glob( $this->cache_dir . '/*.xml' );
		if ( false === $files ) {
			return false;
		}

		$success = true;
		foreach ( $files as $file ) {
			if ( ! unlink( $file ) ) {
				Logger::error(
					'Failed to delete sitemap file during invalidate_all',
					array(
						'file_path' => $file,
						'error'     => 'unlink() failed',
						'writable'  => is_writable( $file ) ? 'yes' : 'no',
					)
				);
				$success = false;
			}
		}

		// Clear all sitemap path cache keys
		// Note: We can't enumerate all keys, so we rely on TTL or manual invalidation
		// For common sitemaps, clear known keys
		$common_types = array( 'index', 'post', 'page', 'posts', 'pages' );
		foreach ( $common_types as $type ) {
			Cache::delete( 'sitemap_path_' . $type );
		}

		return $success;
	}

	/**
	 * Acquire lock for sitemap generation
	 *
	 * Uses atomic Cache::add() operation to acquire lock (Requirement 4.7).
	 * Logs warnings on lock acquisition failure (Requirement 13.1).
	 *
	 * @param string $name Sitemap name.
	 * @return bool True if lock acquired, false otherwise.
	 */
	private function acquire_lock( string $name ): bool {
		$lock_key = 'sitemap_lock_' . $name;
		$acquired = Cache::add( $lock_key, true, $this->lock_timeout );

		if ( ! $acquired ) {
			Logger::warning(
				'Failed to acquire sitemap generation lock',
				array(
					'sitemap_name' => $name,
					'lock_key'     => $lock_key,
					'timeout'      => $this->lock_timeout,
				)
			);
		}

		return $acquired;
	}

	/**
	 * Release lock for sitemap generation
	 *
	 * Deletes lock from cache (Requirement 4.7).
	 *
	 * @param string $name Sitemap name.
	 * @return void
	 */
	private function release_lock( string $name ): void {
		$lock_key = 'sitemap_lock_' . $name;
		Cache::delete( $lock_key );
	}

	/**
	 * Get stale file for stale-while-revalidate pattern
	 *
	 * Returns stale file content even if expired (Requirement 4.8).
	 *
	 * @param string $name Sitemap name.
	 * @return string|null Stale XML content or null if not found.
	 */
	private function get_stale_file( string $name ): ?string {
		$file_path = $this->get_file_path( $name );

		if ( ! file_exists( $file_path ) ) {
			Logger::error(
				'No stale sitemap file available',
				array(
					'sitemap_name' => $name,
					'file_path'    => $file_path,
					'error'        => 'File does not exist',
				)
			);
			return null;
		}

		$content = file_get_contents( $file_path );
		if ( false === $content ) {
			Logger::error(
				'Failed to read stale sitemap file',
				array(
					'sitemap_name' => $name,
					'file_path'    => $file_path,
					'error'        => 'file_get_contents() failed',
				)
			);
			return null;
		}

		return $content;
	}

	/**
	 * Get or generate sitemap with lock pattern
	 *
	 * Implements lock pattern with stale-while-revalidate to prevent
	 * cache stampede (Requirements 4.7, 4.8, 4.9).
	 *
	 * @param string   $name      Sitemap name.
	 * @param callable $generator Generator callable that returns XML content.
	 * @return string XML content or empty string on failure.
	 */
	public function get_or_generate( string $name, callable $generator ): string {
		$file_path = $this->get_file_path( $name );

		// Check if fresh file exists
		if ( file_exists( $file_path ) && $this->is_fresh( $file_path ) ) {
			$content = file_get_contents( $file_path );
			if ( false !== $content ) {
				return $content;
			}
		}

		// Try to acquire lock
		if ( ! $this->acquire_lock( $name ) ) {
			// Lock acquisition failed - another process is generating
			// Serve stale file if available (Requirement 4.8)
			$stale = $this->get_stale_file( $name );
			if ( null !== $stale ) {
				Logger::info(
					'Serving stale sitemap during regeneration',
					array(
						'sitemap_name' => $name,
						'file_path' => $file_path,
					)
				);
				return $stale;
			}

			// No stale file available, return 503 (Requirement 4.9)
			Logger::error(
				'No stale sitemap file available during lock contention',
				array(
					'sitemap_name' => $name,
					'file_path' => $file_path,
					'error' => 'Lock acquisition failed and no stale file exists',
				)
			);

			status_header( 503 );
			header( 'Retry-After: 60' );
			return '';
		}

		/**
		 * Action before sitemap generation
		 *
		 * Fires before a sitemap is generated.
		 *
		 * @since 1.0.0
		 * @param string $name Sitemap name.
		 */
		do_action( 'meowseo_before_sitemap_generation', $name );

		// Lock acquired - generate new content
		try {
			$content = $generator();

			if ( empty( $content ) ) {
				Logger::error(
					'Sitemap generator returned empty content',
					array(
						'sitemap_name' => $name,
					)
				);
				return '';
			}

			// Save to filesystem
			if ( ! $this->set( $name, $content ) ) {
				Logger::error(
					'Failed to save generated sitemap',
					array(
						'sitemap_name' => $name,
						'file_path' => $file_path,
					)
				);
				return '';
			}

			/**
			 * Action after sitemap generation
			 *
			 * Fires after a sitemap is generated and saved.
			 *
			 * @since 1.0.0
			 * @param string $name Sitemap name.
			 * @param string $xml  Generated XML content.
			 */
			do_action( 'meowseo_after_sitemap_generation', $name, $content );

			return $content;
		} finally {
			// Always release lock
			$this->release_lock( $name );
		}
	}
}
