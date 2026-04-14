<?php
/**
 * Sitemap Module
 *
 * Handles XML sitemap generation and serving with performance optimization.
 * Implements lock pattern to prevent cache stampede.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Sitemap;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\Cache;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sitemap module class
 *
 * Intercepts sitemap requests and serves files directly from filesystem.
 *
 * @since 1.0.0
 */
class Sitemap implements Module {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Sitemap generator instance
	 *
	 * @var Sitemap_Generator
	 */
	private Sitemap_Generator $generator;

	/**
	 * Lock TTL in seconds
	 *
	 * @var int
	 */
	private const LOCK_TTL = 60;

	/**
	 * Cache TTL for sitemap paths (24 hours)
	 *
	 * @var int
	 */
	private const CACHE_TTL = 86400;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->generator = new Sitemap_Generator( $options );
	}

	/**
	 * Boot the module
	 *
	 * Register hooks for sitemap functionality.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'register_rewrite_rule' ) );
		add_action( 'parse_request', array( $this, 'intercept_sitemap_request' ), 1 );
		add_action( 'save_post', array( $this, 'invalidate_cache_on_save' ), 10, 2 );
		add_action( 'meowseo_regenerate_sitemap', array( $this, 'regenerate_sitemap_async' ), 10, 1 );
	}

	/**
	 * Get module ID
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'sitemap';
	}

	/**
	 * Register rewrite rule for sitemap URLs
	 *
	 * @return void
	 */
	public function register_rewrite_rule(): void {
		// Index sitemap
		add_rewrite_rule(
			'^meowseo-sitemap\.xml$',
			'index.php?meowseo_sitemap=index',
			'top'
		);

		// Child sitemaps
		add_rewrite_rule(
			'^meowseo-sitemap-([^/]+)\.xml$',
			'index.php?meowseo_sitemap=$matches[1]',
			'top'
		);

		add_rewrite_tag( '%meowseo_sitemap%', '([^&]+)' );
	}

	/**
	 * Intercept sitemap request early
	 *
	 * Serves sitemap files directly, bypassing WordPress template loading.
	 * Implements lock pattern to prevent cache stampede (Requirement 6.4).
	 *
	 * @param \WP $wp WordPress environment object.
	 * @return void
	 */
	public function intercept_sitemap_request( \WP $wp ): void {
		if ( ! isset( $wp->query_vars['meowseo_sitemap'] ) ) {
			return;
		}

		$sitemap_type = $wp->query_vars['meowseo_sitemap'];

		// Check cache for file path (Requirement 6.2)
		$cache_key = 'sitemap_path_' . $sitemap_type;
		$file_path = Cache::get( $cache_key );

		// If cached and file exists, serve it directly (Requirement 6.3)
		if ( $file_path && file_exists( $file_path ) ) {
			$this->serve_sitemap_file( $file_path );
			exit;
		}

		// Try to acquire lock (Requirement 6.4)
		$lock_key = 'sitemap_lock_' . $sitemap_type;
		$lock_acquired = Cache::add( $lock_key, 1, self::LOCK_TTL );

		if ( ! $lock_acquired ) {
			// Another process is generating the sitemap
			// Try to serve stale file if it exists (Requirement 6.5)
			if ( $file_path && file_exists( $file_path ) ) {
				$this->serve_sitemap_file( $file_path );
				exit;
			}

			// Check if there's any existing sitemap file in the directory as fallback
			$sitemap_dir = wp_upload_dir()['basedir'] . '/meowseo-sitemaps/';
			$fallback_file = $sitemap_dir . 'meowseo-sitemap-' . $sitemap_type . '.xml';
			
			if ( file_exists( $fallback_file ) ) {
				// Serve stale file even if not in cache
				$this->serve_sitemap_file( $fallback_file );
				exit;
			}

			// No stale file available, return 503 with retry header
			status_header( 503 );
			header( 'Retry-After: 60' );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'Sitemap is being generated. Please try again in a moment.';
			exit;
		}

		// Lock acquired, generate sitemap
		try {
			$file_path = $this->generate_sitemap( $sitemap_type );

			if ( $file_path && file_exists( $file_path ) ) {
				// Store file path in cache (Requirement 6.2)
				Cache::set( $cache_key, $file_path, self::CACHE_TTL );

				// Serve the generated file
				$this->serve_sitemap_file( $file_path );
			} else {
				// Generation failed
				status_header( 500 );
				header( 'Content-Type: text/plain; charset=utf-8' );
				echo 'Failed to generate sitemap.';
				
				// Log error in debug mode
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'MeowSEO: Sitemap generation failed for type: ' . $sitemap_type );
				}
			}
		} catch ( \Exception $e ) {
			// Log exception
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'MeowSEO: Sitemap generation exception: ' . $e->getMessage() );
			}
			
			status_header( 500 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'An error occurred while generating the sitemap.';
		} finally {
			// Always release the lock
			Cache::delete( $lock_key );
		}

		exit;
	}

	/**
	 * Generate sitemap
	 *
	 * @param string $type Sitemap type ('index' or post type name).
	 * @return string|false File path on success, false on failure.
	 */
	private function generate_sitemap( string $type ): string|false {
		if ( 'index' === $type ) {
			return $this->generator->generate_index();
		}

		return $this->generator->generate_child( $type );
	}

	/**
	 * Serve sitemap file
	 *
	 * Outputs XML file with appropriate headers and exits.
	 * Serves directly from filesystem, bypassing WordPress template loading (Requirement 14.5).
	 *
	 * @param string $file_path Path to sitemap file.
	 * @return void
	 */
	private function serve_sitemap_file( string $file_path ): void {
		// Set XML headers
		header( 'Content-Type: application/xml; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, follow' );

		// Read and output file directly from filesystem (Requirement 14.5)
		readfile( $file_path );
	}

	/**
	 * Invalidate cache on post save
	 *
	 * Deletes affected child sitemap from cache and schedules regeneration.
	 * (Requirement 6.6)
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function invalidate_cache_on_save( int $post_id, \WP_Post $post ): void {
		// Skip autosaves and revisions
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only invalidate for published posts
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Delete child sitemap cache for this post type
		$cache_key = 'sitemap_path_' . $post->post_type;
		Cache::delete( $cache_key );

		// Also invalidate index sitemap
		Cache::delete( 'sitemap_path_index' );

		// Schedule async regeneration via WP-Cron
		if ( ! wp_next_scheduled( 'meowseo_regenerate_sitemap', array( $post->post_type ) ) ) {
			wp_schedule_single_event( time() + 60, 'meowseo_regenerate_sitemap', array( $post->post_type ) );
		}
	}

	/**
	 * Regenerate sitemap asynchronously
	 *
	 * Called by WP-Cron to regenerate sitemap in background.
	 *
	 * @param string $post_type Post type name.
	 * @return void
	 */
	public function regenerate_sitemap_async( string $post_type ): void {
		// Acquire lock
		$lock_key = 'sitemap_lock_' . $post_type;
		$lock_acquired = Cache::add( $lock_key, 1, self::LOCK_TTL );

		if ( ! $lock_acquired ) {
			// Another process is already regenerating
			return;
		}

		try {
			// Generate sitemap
			$file_path = $this->generator->generate_child( $post_type );

			if ( $file_path ) {
				// Store file path in cache
				$cache_key = 'sitemap_path_' . $post_type;
				Cache::set( $cache_key, $file_path, self::CACHE_TTL );
			}

			// Also regenerate index
			$index_path = $this->generator->generate_index();
			if ( $index_path ) {
				Cache::set( 'sitemap_path_index', $index_path, self::CACHE_TTL );
			}
		} finally {
			// Release lock
			Cache::delete( $lock_key );
		}
	}
}
