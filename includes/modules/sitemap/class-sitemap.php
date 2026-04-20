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
use MeowSEO\Helpers\Logger;
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
	 * Sitemap builder instance
	 *
	 * @var Sitemap_Builder
	 */
	private Sitemap_Builder $builder;

	/**
	 * Sitemap cache instance
	 *
	 * @var Sitemap_Cache
	 */
	private Sitemap_Cache $cache;

	/**
	 * Sitemap ping instance
	 *
	 * @var Sitemap_Ping
	 */
	private Sitemap_Ping $ping;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->cache = new Sitemap_Cache();
		$this->builder = new Sitemap_Builder( $this->cache, $options );
		$this->ping = new Sitemap_Ping();
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
		add_action( 'template_redirect', array( $this, 'intercept_sitemap_request' ), 1 );
		add_action( 'save_post', array( $this, 'invalidate_cache_on_save' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'invalidate_cache_on_delete' ), 10, 1 );
		add_action( 'created_term', array( $this, 'invalidate_cache_on_term_change' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'invalidate_cache_on_term_change' ), 10, 3 );
		add_action( 'meowseo_regenerate_sitemaps', array( $this, 'regenerate_all_sitemaps' ) );
		add_action( 'transition_post_status', array( $this, 'ping_on_post_publish' ), 10, 3 );
		
		// Schedule daily regeneration
		$this->schedule_daily_regeneration();
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
		// Index sitemap (Requirement 3.1)
		add_rewrite_rule(
			'^sitemap\.xml$',
			'index.php?meowseo_sitemap=index',
			'top'
		);

		// Posts sitemap (Requirement 3.2)
		add_rewrite_rule(
			'^sitemap-posts\.xml$',
			'index.php?meowseo_sitemap=posts',
			'top'
		);

		// Pages sitemap (Requirement 3.3)
		add_rewrite_rule(
			'^sitemap-pages\.xml$',
			'index.php?meowseo_sitemap=pages',
			'top'
		);

		// Custom post type sitemaps (Requirement 3.4)
		add_rewrite_rule(
			'^sitemap-([^/]+?)\.xml$',
			'index.php?meowseo_sitemap=$matches[1]',
			'top'
		);

		// Paginated sitemaps
		add_rewrite_rule(
			'^sitemap-([^/]+?)-([0-9]+)\.xml$',
			'index.php?meowseo_sitemap=$matches[1]&meowseo_sitemap_page=$matches[2]',
			'top'
		);

		// News sitemap (Requirement 3.1, 3.5)
		add_rewrite_rule(
			'^sitemap-news\.xml$',
			'index.php?meowseo_sitemap=news',
			'top'
		);

		// Video sitemap (Requirement 3.6)
		add_rewrite_rule(
			'^sitemap-video\.xml$',
			'index.php?meowseo_sitemap=video',
			'top'
		);

		add_rewrite_tag( '%meowseo_sitemap%', '([^&]+)' );
		add_rewrite_tag( '%meowseo_sitemap_page%', '([0-9]+)' );

		// Flush rewrite rules if needed (Requirement 3.9)
		if ( ! get_option( 'meowseo_sitemap_rewrite_flushed' ) ) {
			flush_rewrite_rules();
			update_option( 'meowseo_sitemap_rewrite_flushed', true );
		}
	}

	/**
	 * Intercept sitemap request
	 *
	 * Serves sitemap files using Sitemap_Cache get_or_generate() method.
	 * Implements lock pattern with stale-while-revalidate (Requirements 3.7, 3.8, 12.7, 14.5).
	 *
	 * @return void
	 */
	public function intercept_sitemap_request(): void {
		global $wp_query;

		if ( ! isset( $wp_query->query_vars['meowseo_sitemap'] ) ) {
			return;
		}

		$sitemap_type = $wp_query->query_vars['meowseo_sitemap'];
		$page = isset( $wp_query->query_vars['meowseo_sitemap_page'] ) ? (int) $wp_query->query_vars['meowseo_sitemap_page'] : 1;

		// Generate sitemap XML using cache get_or_generate()
		$xml_content = $this->generate_sitemap( $sitemap_type, $page );

		// Set proper headers (Requirements 3.8, 14.5)
		status_header( 200 );
		header( 'Content-Type: application/xml; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, follow' );

		// Output XML content
		echo $xml_content;
		exit;
	}

	/**
	 * Generate sitemap
	 *
	 * Routes generation through Sitemap_Builder which uses Sitemap_Cache.
	 *
	 * @param string $type Sitemap type ('index', post type name, 'news', 'video').
	 * @param int    $page Page number for paginated sitemaps.
	 * @return string XML content.
	 */
	private function generate_sitemap( string $type, int $page = 1 ): string {
		try {
			if ( 'index' === $type ) {
				return $this->builder->build_index();
			}

			if ( 'news' === $type ) {
				return $this->builder->build_news();
			}

			if ( 'video' === $type ) {
				return $this->builder->build_video();
			}

			// Handle 'posts' as 'post' post type
			if ( 'posts' === $type ) {
				$type = 'post';
			}

			// Handle 'pages' as 'page' post type
			if ( 'pages' === $type ) {
				$type = 'page';
			}

			return $this->builder->build_posts( $type, $page );
		} catch ( \Exception $e ) {
			Logger::error(
				'Sitemap generation failed',
				array(
					'type' => $type,
					'page' => $page,
					'error' => $e->getMessage(),
				)
			);
			return '';
		}
	}

	/**
	 * Invalidate cache on post save
	 *
	 * Uses Sitemap_Cache invalidate() method to delete affected sitemaps.
	 * Implements stale-while-revalidate behavior (Requirements 6.1, 6.7).
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

		// Invalidate child sitemap for this post type (Requirement 6.1)
		$this->cache->invalidate( $post->post_type );
		
		// Also invalidate paginated sitemaps
		$this->invalidate_paginated_sitemaps( $post->post_type );

		// Invalidate index sitemap
		$this->cache->invalidate( 'index' );

		// Invalidate news sitemap if this is a recent post
		if ( 'post' === $post->post_type ) {
			$post_age = time() - strtotime( $post->post_date_gmt );
			if ( $post_age < 172800 ) { // 48 hours
				$this->cache->invalidate( 'news' );
			}
		}

		Logger::info(
			'Sitemap cache invalidated on post save',
			array(
				'post_id' => $post_id,
				'post_type' => $post->post_type,
			)
		);
	}

	/**
	 * Invalidate cache on post delete
	 *
	 * Uses Sitemap_Cache invalidate() method to delete affected sitemaps
	 * (Requirement 6.2).
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function invalidate_cache_on_delete( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		// Invalidate child sitemap for this post type (Requirement 6.2)
		$this->cache->invalidate( $post->post_type );
		
		// Also invalidate paginated sitemaps
		$this->invalidate_paginated_sitemaps( $post->post_type );

		// Invalidate index sitemap
		$this->cache->invalidate( 'index' );

		// Invalidate news sitemap if this was a post
		if ( 'post' === $post->post_type ) {
			$this->cache->invalidate( 'news' );
		}

		Logger::info(
			'Sitemap cache invalidated on post delete',
			array(
				'post_id' => $post_id,
				'post_type' => $post->post_type,
			)
		);
	}

	/**
	 * Invalidate cache on term change
	 *
	 * Uses Sitemap_Cache invalidate() method to delete affected sitemaps
	 * (Requirements 6.3, 6.4).
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function invalidate_cache_on_term_change( int $term_id, int $tt_id, string $taxonomy ): void {
		// Get post types associated with this taxonomy
		$taxonomy_obj = get_taxonomy( $taxonomy );

		if ( ! $taxonomy_obj || empty( $taxonomy_obj->object_type ) ) {
			return;
		}

		// Invalidate sitemaps for all post types using this taxonomy
		foreach ( $taxonomy_obj->object_type as $post_type ) {
			$this->cache->invalidate( $post_type );
			$this->invalidate_paginated_sitemaps( $post_type );
		}

		// Invalidate index sitemap
		$this->cache->invalidate( 'index' );

		Logger::info(
			'Sitemap cache invalidated on term change',
			array(
				'term_id' => $term_id,
				'taxonomy' => $taxonomy,
				'affected_post_types' => $taxonomy_obj->object_type,
			)
		);
	}

	/**
	 * Invalidate paginated sitemaps for a post type
	 *
	 * Deletes all paginated sitemap files for a given post type.
	 *
	 * @param string $post_type Post type name.
	 * @return void
	 */
	private function invalidate_paginated_sitemaps( string $post_type ): void {
		// Invalidate up to 100 pages (should be more than enough)
		for ( $page = 2; $page <= 100; $page++ ) {
			$cache_name = $post_type . '-' . $page;
			$this->cache->invalidate( $cache_name );
		}
	}

	/**
	 * Get public post types for sitemap
	 *
	 * @return array Array of post type names.
	 */
	private function get_public_post_types(): array {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);

		// Exclude attachment post type
		unset( $post_types['attachment'] );

		return array_values( $post_types );
	}

	/**
	 * Regenerate all sitemaps
	 *
	 * Pre-generates all sitemaps to ensure fresh cache.
	 * Called by WP-Cron daily event (Requirements 6.5, 6.6).
	 * Pings search engines after regeneration (Requirement 7.2).
	 *
	 * @return void
	 */
	public function regenerate_all_sitemaps(): void {
		Logger::info( 'Starting scheduled sitemap regeneration' );

		$post_types = $this->get_public_post_types();

		// Regenerate index sitemap
		$this->builder->build_index();
		Logger::info( 'Regenerated index sitemap' );

		// Regenerate sitemaps for each post type
		foreach ( $post_types as $post_type ) {
			// Get post count to determine number of pages
			$count = $this->get_post_count( $post_type );

			if ( $count === 0 ) {
				continue;
			}

			$pages = (int) ceil( $count / 1000 ); // 1000 URLs per sitemap

			// Regenerate all pages for this post type
			for ( $page = 1; $page <= $pages; $page++ ) {
				$this->builder->build_posts( $post_type, $page );
			}

			Logger::info(
				'Regenerated sitemap for post type',
				array(
					'post_type' => $post_type,
					'pages' => $pages,
					'total_posts' => $count,
					'entry_count' => $count,
				)
			);
		}

		// Regenerate news sitemap
		$this->builder->build_news();
		Logger::info( 'Regenerated news sitemap' );

		// Regenerate video sitemap
		$this->builder->build_video();
		Logger::info( 'Regenerated video sitemap' );

		Logger::info( 'Sitemap cache regenerated' );

		// Ping search engines after regeneration (Requirement 7.2)
		$this->ping_search_engines();
	}

	/**
	 * Get post count for a post type
	 *
	 * Counts only published posts that are not marked as noindex.
	 *
	 * @param string $post_type Post type name.
	 * @return int Post count.
	 */
	private function get_post_count( string $post_type ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm 
					ON p.ID = pm.post_id 
					AND pm.meta_key = '_meowseo_noindex'
				WHERE p.post_type = %s
				AND p.post_status = 'publish'
				AND (pm.meta_value IS NULL OR pm.meta_value != '1')",
				$post_type
			)
		);

		return (int) $count;
	}

	/**
	 * Schedule daily sitemap regeneration
	 *
	 * Registers WP-Cron event for daily sitemap pre-generation.
	 *
	 * @return void
	 */
	public function schedule_daily_regeneration(): void {
		if ( ! wp_next_scheduled( 'meowseo_regenerate_sitemaps' ) ) {
			wp_schedule_event( time(), 'daily', 'meowseo_regenerate_sitemaps' );
			Logger::info( 'Scheduled daily sitemap regeneration' );
		}
	}

	/**
	 * Ping search engines on post publish
	 *
	 * Hooks into transition_post_status to ping when a post is published (Requirement 7.3).
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function ping_on_post_publish( string $new_status, string $old_status, \WP_Post $post ): void {
		// Only ping when transitioning to publish status (Requirement 7.3)
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Skip autosaves and revisions
		if ( wp_is_post_autosave( $post->ID ) || wp_is_post_revision( $post->ID ) ) {
			return;
		}

		Logger::info(
			'Post published, triggering sitemap ping',
			array(
				'post_id' => $post->ID,
				'post_type' => $post->post_type,
			)
		);

		// Ping search engines (Requirement 7.3)
		$this->ping_search_engines();
	}

	/**
	 * Ping search engines with sitemap URL
	 *
	 * Notifies Google and Bing of sitemap updates (Requirements 7.1, 7.6).
	 *
	 * @return void
	 */
	private function ping_search_engines(): void {
		$sitemap_url = trailingslashit( get_site_url() ) . 'sitemap.xml';
		$this->ping->ping( $sitemap_url );
	}
}
