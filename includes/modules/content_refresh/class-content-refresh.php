<?php
/**
 * Content Refresh Module
 *
 * Safely re-publishes old content to improve "freshness" SEO while protecting permalinks.
 *
 * @package MeowSEO
 * @subpackage Modules\Content_Refresh
 */

namespace MeowSEO\Modules\Content_Refresh;

use MeowSEO\Options;
use MeowSEO\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Content_Refresh class.
 */
class Content_Refresh implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module.
	 */
	public function boot(): void {
		if ( ! $this->options->get( 'content_refresh_enabled', false ) ) {
			return;
		}

		// Register background heartbeat worker.
		add_action( 'meowseo_content_refresh_heartbeat', array( $this, 'process_refresh_queue' ) );

		if ( ! wp_next_scheduled( 'meowseo_content_refresh_heartbeat' ) ) {
			wp_schedule_event( time(), 'hourly', 'meowseo_content_refresh_heartbeat' );
		}

		// Add "Updated" notice to content if enabled.
		if ( $this->options->get( 'content_refresh_add_notice', true ) ) {
			add_filter( 'the_content', array( $this, 'add_updated_notice' ) );
		}
	}

	/**
	 * Get module ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'content_refresh';
	}

	/**
	 * Process the refresh queue.
	 */
	public function process_refresh_queue(): void {
		if ( ! $this->options->get( 'content_refresh_enabled', false ) ) {
			return;
		}

		$interval = (int) $this->options->get( 'content_refresh_interval', 24 );
		$min_age  = (int) $this->options->get( 'content_refresh_min_age', 30 );
		$post_types = $this->options->get( 'content_refresh_post_types', array( 'post' ) );
		$method   = $this->options->get( 'content_refresh_method', 'modified' );
		$included_cats = $this->options->get( 'content_refresh_included_categories', array() );
		$included_tags = $this->options->get( 'content_refresh_included_tags', array() );

		// Check if we should run now based on interval.
		$last_run = get_option( 'meowseo_content_refresh_last_run', 0 );
		if ( ( time() - $last_run ) < ( $interval * HOUR_IN_SECONDS ) ) {
			return;
		}

		// Build Tax Query (Requirement: Safe Inclusion).
		$tax_query = array();
		if ( ! empty( $included_cats ) ) {
			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $included_cats,
				'operator' => 'IN',
			);
		}
		if ( ! empty( $included_tags ) ) {
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $included_tags,
				'operator' => 'IN',
			);
		}

		// If inclusion filters are enabled but none selected, don't refresh anything (safest).
		if ( empty( $included_cats ) && empty( $included_tags ) ) {
			return;
		}

		// Find the oldest post that meets the age criteria.
		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'tax_query'      => $tax_query,
			'date_query'     => array(
				array(
					'before' => date( 'Y-m-d', strtotime( "-$min_age days" ) ),
				),
			),
		);

		$posts = get_posts( $args );

		if ( ! empty( $posts ) ) {
			$post = $posts[0];
			$this->refresh_post( $post->ID, $method );
		}

		update_option( 'meowseo_content_refresh_last_run', time() );
	}

	/**
	 * Refresh a specific post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $method  'republish' or 'modified'.
	 * @return bool True on success.
	 */
	public function refresh_post( int $post_id, string $method = 'modified' ): bool {
		$new_time = current_time( 'mysql' );
		$gmt_time = get_gmt_from_date( $new_time );

		if ( 'republish' === $method ) {
			// Check for permalink changes before updating date.
			$old_link = get_permalink( $post_id );
			
			$update_data = array(
				'ID'                => $post_id,
				'post_date'         => $new_time,
				'post_date_gmt'     => $gmt_time,
				'post_modified'     => $new_time,
				'post_modified_gmt' => $gmt_time,
			);
			
			wp_update_post( $update_data );
			
			$new_permalink = get_permalink( $post_id );

			// Auto-redirect if URL changed (e.g. /2022/ link becomes /2026/).
			if ( $this->options->get( 'content_refresh_auto_redirect', true ) && $old_link !== $new_permalink ) {
				$this->create_301_redirect( $old_link, $new_permalink );
			}

			// Auto-Submit to Search Engines (Requirement: Immediate SEO impact).
			if ( $this->options->get( 'content_refresh_auto_index', true ) ) {
				$this->submit_to_index( $new_permalink );
			}

		} else {
			// Only update modified date.
			$update_data = array(
				'ID'                => $post_id,
				'post_modified'     => $new_time,
				'post_modified_gmt' => $gmt_time,
			);
			wp_update_post( $update_data );

			// Also submit to index for modified date updates.
			if ( $this->options->get( 'content_refresh_auto_index', true ) ) {
				$this->submit_to_index( get_permalink( $post_id ) );
			}
		}

		// Store refresh meta.
		update_post_meta( $post_id, '_meowseo_last_refreshed', time() );

		return true;
	}

	/**
	 * Create a 301 redirect.
	 *
	 * @param string $old_url Old URL.
	 * @param string $new_url New URL.
	 */
	private function create_301_redirect( string $old_url, string $new_url ): void {
		// Use MeowSEO's internal redirection if available, otherwise use a simple option-based fallback.
		// For now, we'll use a simple option-based redirect map.
		$redirects = get_option( 'meowseo_content_refresh_redirects', array() );
		$path = wp_make_link_relative( $old_url );
		$redirects[ $path ] = $new_url;
		update_option( 'meowseo_content_refresh_redirects', $redirects );
	}

	/**
	 * Submit URL to search engines via MeowIndex.
	 *
	 * @param string $url The URL to submit.
	 */
	private function submit_to_index( string $url ): void {
		// Trigger the MeowIndex hook.
		do_action( 'meowseo_submit_to_index', $url );
	}

	/**
	 * Add "Updated on" notice to content.
	 *
	 * @param string $content Post content.
	 * @return string Filtered content.
	 */
	public function add_updated_notice( string $content ): string {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		global $post;
		$last_refreshed = get_post_meta( $post->ID, '_meowseo_last_refreshed', true );
		
		if ( empty( $last_refreshed ) ) {
			return $content;
		}

		$date = date_i18n( get_option( 'date_format' ), (int) $last_refreshed );
		$notice = '<div class="meowseo-refresh-notice" style="margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-left: 4px solid #007bff; font-style: italic;">';
		$notice .= sprintf( __( 'Last updated on %s', 'meowseo' ), $date );
		$notice .= '</div>';

		return $notice . $content;
	}
}
