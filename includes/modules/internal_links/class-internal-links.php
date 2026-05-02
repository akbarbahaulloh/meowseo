<?php
/**
 * Internal Links Module
 *
 * Analyzes internal link structure and provides link health reporting.
 * Scans post content for internal links and schedules HTTP status checks via WP-Cron.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\Internal_Links
 */

namespace MeowSEO\Modules\Internal_Links;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\DB;
use MeowSEO\Options;
use DOMDocument;
use DOMXPath;

defined( 'ABSPATH' ) || exit;

/**
 * Internal Links Module class
 *
 * Implements link scanning, analysis, and suggestion system.
 */
class Internal_Links implements Module {

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
	 *
	 * Register hooks for link scanning and cron processing.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Hook into save_post to schedule link scanning (Requirement 9.1, 9.2).
		add_action( 'save_post', array( $this, 'schedule_link_scan' ), 10, 2 );

		// Register background workers.
		add_action( 'meowseo_process_link_discovery_queue', array( $this, 'process_discovery_batch' ) );
		add_action( 'meowseo_process_link_status_queue', array( $this, 'process_status_batch' ) );
		
		if ( ! wp_next_scheduled( 'meowseo_process_link_discovery_queue' ) ) {
			wp_schedule_event( time(), 'every_minute', 'meowseo_process_link_discovery_queue' );
		}
		if ( ! wp_next_scheduled( 'meowseo_process_link_status_queue' ) ) {
			wp_schedule_event( time(), 'every_minute', 'meowseo_process_link_status_queue' );
		}
		add_action( 'meowseo_broken_links_daily_summary', array( $this, 'send_daily_summary' ) );

		if ( ! wp_next_scheduled( 'meowseo_broken_links_daily_summary' ) ) {
			wp_schedule_event( time(), 'daily', 'meowseo_broken_links_daily_summary' );
		}

		// Register REST API endpoints (Requirement 9.5).
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Apply SEO protection filter (nofollow broken links).
		add_filter( 'the_content', array( $this, 'apply_link_protections' ), 20 );

		// Output custom CSS for broken links.
		add_action( 'wp_head', array( $this, 'output_broken_links_css' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$rest = new Internal_Links_REST();
		$rest->register_routes();
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'internal_links';
	}

	/**
	 * Schedule link scan for a post.
	 *
	 * Triggered on save_post. Instead of scheduling a cron event per post, we mark the post for discovery.
	 *
	 * @param int     $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function schedule_link_scan( int $post_id, \WP_Post $post ): void {
		// Skip autosaves and revisions.
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only scan published posts.
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Check if post type is enabled for scanning.
		$allowed_post_types = $this->options->get( 'broken_links_post_types', array( 'post', 'page' ) );
		if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
			return;
		}

		// Check if post status is enabled for scanning.
		$allowed_post_statuses = $this->options->get( 'broken_links_post_statuses', array( 'publish' ) );
		if ( ! in_array( $post->post_status, $allowed_post_statuses, true ) ) {
			return;
		}

		update_post_meta( $post_id, '_meowseo_link_scan_pending', 1 );
	}

	/**
	 * Scan post content for internal links.
	 *
	 * Parses HTML with DOMDocument to extract <a href> elements.
	 * Filters to internal URLs only (same host as site_url).
	 * Stores link data in meowseo_link_checks table (Requirement 9.1).
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function scan_post_links( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return;
		}

		// Get post content.
		$content = apply_filters( 'the_content', $post->post_content );
		
		// Add Custom Fields content.
		$custom_field_keys = array_filter( array_map( 'trim', explode( ',', $this->options->get( 'broken_links_custom_fields', '' ) ) ) );
		foreach ( $custom_field_keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( ! empty( $value ) && is_string( $value ) ) {
				$content .= ' ' . $value;
			}
		}

		if ( empty( $content ) ) {
			return;
		}

		// Parse HTML and extract links based on settings.
		$links = $this->extract_links_from_html( $content );

		if ( empty( $links ) ) {
			return;
		}

		// Store link data in database.
		$excluded_domains = array_filter( array_map( 'trim', explode( "\n", $this->options->get( 'broken_links_excluded_domains', '' ) ) ) );

		foreach ( $links as $link ) {
			$is_internal = $this->is_internal_link( $link['url'] );

			// Check for excluded domains.
			if ( ! $is_internal ) {
				$host = wp_parse_url( $link['url'], PHP_URL_HOST );
				if ( $host && in_array( $host, $excluded_domains, true ) ) {
					continue;
				}
			}

			// If it's a relative URL, make it absolute if it's internal.
			if ( $is_internal && str_starts_with( $link['url'], '/' ) ) {
				$link['url'] = site_url( $link['url'] );
			}

			DB::upsert_link_check(
				array(
					'source_post_id' => $post_id,
					'target_url'     => $link['url'],
					'anchor_text'    => $link['anchor'],
					'is_external'    => ! $is_internal,
					'http_status'    => null,
					'last_checked'   => null,
				)
			);
		}

		// Scan comments if enabled.
		if ( $this->options->get( 'broken_links_scan_comments', false ) ) {
			$this->scan_post_comments( $post_id );
		}
		
		// Scan widgets if enabled (only once per full scan or periodically).
		if ( $this->options->get( 'broken_links_scan_widgets', false ) ) {
			$this->scan_widgets();
		}
	}

	/**
	 * Scan all active widgets for links.
	 *
	 * @return void
	 */
	public function scan_widgets(): void {
		global $wp_registered_widgets;
		$sidebars_widgets = get_option( 'sidebars_widgets' );

		if ( ! $sidebars_widgets ) {
			return;
		}

		foreach ( $sidebars_widgets as $sidebar_id => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar_id || ! is_array( $widgets ) ) {
				continue;
			}

			foreach ( $widgets as $widget_id ) {
				// Get widget base ID and settings.
				$base_id = _get_widget_id_base( $widget_id );
				$settings = get_option( 'widget_' . $base_id );

				if ( ! $settings ) continue;

				// Find the specific instance.
				preg_match( '/-([0-9]+)$/', $widget_id, $matches );
				$instance_id = $matches[1] ?? null;

				if ( $instance_id && isset( $settings[ $instance_id ] ) ) {
					$instance = $settings[ $instance_id ];
					
					// Scan fields like 'text', 'content', 'title'.
					$content = '';
					foreach ( array( 'text', 'content', 'title', 'url' ) as $key ) {
						if ( ! empty( $instance[ $key ] ) && is_string( $instance[ $key ] ) ) {
							$content .= ' ' . $instance[ $key ];
						}
					}

					if ( ! empty( $content ) ) {
						$links = $this->extract_links_from_html( $content );
						foreach ( $links as $link ) {
							DB::upsert_link_check(
								array(
									'source_post_id' => 0, // 0 indicates a widget.
									'target_url'     => $link['url'],
									'anchor_text'    => $link['anchor'] . ' (' . __( 'Widget', 'meowseo' ) . ')',
									'is_external'    => ! $this->is_internal_link( $link['url'] ),
									'http_status'    => null,
									'last_checked'   => null,
								)
							);
						}
					}
				}
			}
		}
	}

	/**
	 * Scan comments for a specific post.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function scan_post_comments( int $post_id ): void {
		$comments = get_comments( array( 'post_id' => $post_id, 'status' => 'approve' ) );
		foreach ( $comments as $comment ) {
			$links = $this->extract_links_from_html( $comment->comment_content );
			foreach ( $links as $link ) {
				DB::upsert_link_check(
					array(
						'source_post_id' => $post_id, // We link comment issues to the post for easier management.
						'target_url'     => $link['url'],
						'anchor_text'    => $link['anchor'] . ' (' . __( 'Comment', 'meowseo' ) . ')',
						'is_external'    => ! $this->is_internal_link( $link['url'] ),
						'http_status'    => null,
						'last_checked'   => null,
					)
				);
			}
		}
	}

	/**
	 * Extract links from HTML content.
	 *
	 * Uses DOMDocument to parse HTML and extract <a href> elements.
	 *
	 * @param string $html HTML content.
	 * @return array Array of link data with 'url' and 'anchor' keys.
	 */
	private function extract_links_from_html( string $html ): array {
		$links = array();

		// Suppress DOMDocument warnings for malformed HTML.
		libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );

		$link_types = $this->options->get( 'broken_links_types', array( 'html_link', 'html_image', 'plain_url' ) );
		
		// Extract CSS URLs from <style> tags and inline styles.
		if ( in_array( 'css_url', $link_types, true ) ) {
			// Find <style> tags.
			$styles = $xpath->query( '//style' );
			if ( $styles ) {
				foreach ( $styles as $style ) {
					$links = array_merge( $links, $this->extract_css_urls( $style->textContent ) );
				}
			}
			// Find inline styles.
			$elements_with_style = $xpath->query( '//*[@style]' );
			if ( $elements_with_style ) {
				foreach ( $elements_with_style as $el ) {
					$links = array_merge( $links, $this->extract_css_urls( $el->getAttribute( 'style' ) ) );
				}
			}
		}

		// Extract anchors.
		if ( in_array( 'html_link', $link_types, true ) ) {
			$anchors = $xpath->query( '//a[@href]' );
			if ( $anchors ) {
				foreach ( $anchors as $anchor ) {
					$href = $anchor->getAttribute( 'href' );
					$text = $anchor->textContent;

					if ( ! empty( $href ) && ! str_starts_with( $href, '#' ) && ! str_starts_with( $href, 'mailto:' ) && ! str_starts_with( $href, 'tel:' ) ) {
						$links[] = array(
							'url'    => $href,
							'anchor' => mb_substr( trim( $text ), 0, 512 ),
						);
					}
				}
			}
		}

		// Extract images.
		if ( in_array( 'html_image', $link_types, true ) ) {
			$images = $xpath->query( '//img[@src]' );
			if ( $images ) {
				foreach ( $images as $img ) {
					$src = $img->getAttribute( 'src' );
					$alt = $img->getAttribute( 'alt' );

					if ( ! empty( $src ) ) {
						$links[] = array(
							'url'    => $src,
							'anchor' => ! empty( $alt ) ? mb_substr( trim( $alt ), 0, 512 ) : __( 'Image', 'meowseo' ),
						);
					}
				}
			}
		}

		// Extract Embedded Media (YouTube/Vimeo/etc.).
		$media_types = array(
			'yt_video'    => 'youtube.com',
			'gv_video'    => 'video.google.com',
			'dm_video'    => 'dailymotion.com',
			'vimeo_video' => 'vimeo.com'
		);

		foreach ( $media_types as $type => $domain ) {
			if ( in_array( $type, $link_types, true ) ) {
				$iframes = $xpath->query( '//iframe[contains(@src, "' . $domain . '")]' );
				if ( $iframes ) {
					foreach ( $iframes as $iframe ) {
						$links[] = array(
							'url'    => $iframe->getAttribute( 'src' ),
							'anchor' => sprintf( __( 'Embedded %s Video', 'meowseo' ), ucfirst( str_replace( '_video', '', $type ) ) ),
						);
					}
				}
			}
		}

		// Handle legacy embeds (YouTube old code).
		if ( in_array( 'yt_video_old', $link_types, true ) || in_array( 'yt_playlist_old', $link_types, true ) ) {
			$embeds = $xpath->query( '//embed[contains(@src, "youtube.com")] | //object[contains(@data, "youtube.com")]' );
			if ( $embeds ) {
				foreach ( $embeds as $embed ) {
					$url = $embed->hasAttribute( 'src' ) ? $embed->getAttribute( 'src' ) : $embed->getAttribute( 'data' );
					if ( ! empty( $url ) ) {
						$is_playlist = str_contains( $url, 'list=' );
						if ( ( $is_playlist && in_array( 'yt_playlist_old', $link_types, true ) ) || ( ! $is_playlist && in_array( 'yt_video_old', $link_types, true ) ) ) {
							$links[] = array(
								'url'    => $url,
								'anchor' => $is_playlist ? __( 'YouTube Playlist (Old)', 'meowseo' ) : __( 'YouTube Video (Old)', 'meowseo' ),
							);
						}
					}
				}
			}
		}

		// Extract Plain Text URLs and Smart YouTube URLs.
		if ( in_array( 'plain_url', $link_types, true ) || in_array( 'yt_smart_url', $link_types, true ) ) {
			$plain_text = strip_tags( $html );
			
			// Standard URLs.
			if ( in_array( 'plain_url', $link_types, true ) ) {
				$pattern = '/\bhttps?:\/\/[^\s\'"<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/))/i';
				if ( preg_match_all( $pattern, $plain_text, $matches ) ) {
					foreach ( $matches[0] as $url ) {
						$links[] = array( 'url' => $url, 'anchor' => __( 'Plaintext URL', 'meowseo' ) );
					}
				}
			}

			// Smart YouTube httpv:// URLs.
			if ( in_array( 'yt_smart_url', $link_types, true ) ) {
				if ( preg_match_all( '/\bhttpv?:\/\/[^\s\'"<>]+/i', $plain_text, $matches ) ) {
					foreach ( $matches[0] as $url ) {
						if ( str_starts_with( $url, 'httpv' ) ) {
							$links[] = array( 'url' => $url, 'anchor' => __( 'Smart YouTube URL', 'meowseo' ) );
						}
					}
				}
			}
		}

		return $links;
	}

	/**
	 * Extract URLs from CSS content using regex.
	 *
	 * @param string $css CSS content.
	 * @return array Array of link data.
	 */
	private function extract_css_urls( string $css ): array {
		$links = array();
		$pattern = '/url\s*\(\s*["\']?([^"\')\s]+)["\']?\s*\)/i';
		if ( preg_match_all( $pattern, $css, $matches ) ) {
			foreach ( $matches[1] as $url ) {
				if ( ! str_starts_with( $url, 'data:' ) ) {
					$links[] = array(
						'url'    => $url,
						'anchor' => __( 'CSS Asset', 'meowseo' ),
					);
				}
			}
		}
		return $links;
	}

	/**
	 * Check if a URL is internal to the site.
	 *
	 * @param string $url URL to check.
	 * @return bool True if internal, false otherwise.
	 */
	private function is_internal_link( string $url ): bool {
		if ( empty( $url ) ) {
			return false;
		}

		// Relative URLs are internal.
		if ( '/' === $url[0] ) {
			return true;
		}

		$site_host = $this->get_site_host();
		$parsed    = wp_parse_url( $url );

		return isset( $parsed['host'] ) && $parsed['host'] === $site_host;
	}

	/**
	 * Get site host from site_url().
	 *
	 * @return string Site host.
	 */
	private function get_site_host(): string {
		$site_url = site_url();
		$parsed = wp_parse_url( $site_url );

		return $parsed['host'] ?? '';
	}

	/**
	 * Check HTTP status of a link.
	 *
	 * Performs HTTP HEAD request to check link status.
	 * Updates http_status in meowseo_link_checks table (Requirement 9.3).
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $url_hash URL hash.
	 * @return void
	 */
	public function check_link_status( int $post_id, string $url_hash ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_link_checks';

		// Get link data from database.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE source_post_id = %d AND target_url_hash = %s LIMIT 1",
				$post_id,
				$url_hash
			),
			ARRAY_A
		);

		if ( ! $link ) {
			return;
		}

		$target_url = $link['target_url'];
		$timeout    = (int) $this->options->get( 'broken_links_timeout', 30 );

		// Check for throttling (Requirement: Rate Limiting).
		$throttler = new Link_Throttler();
		if ( $throttler->is_throttled( $target_url ) ) {
			return;
		}

		// Check for Server Load Limit (Requirement: Resource Protection).
		$load_limit = $this->options->get( 'broken_links_load_limit', '' );
		if ( ! empty( $load_limit ) && function_exists( 'sys_getloadavg' ) ) {
			$load = sys_getloadavg();
			if ( is_array( $load ) && isset( $load[0] ) && $load[0] > (float) $load_limit ) {
				$this->log( sprintf( 'Server load too high (%s > %s). Pausing check.', $load[0], $load_limit ) );
				return;
			}
		}

		// Use the new Link_Checker engine.
		$checker = new Link_Checker( $timeout );
		$this->log( sprintf( 'Checking URL: %s', $target_url ) );
		$result = $checker->check( $target_url );

		// Update link check record.
		$wpdb->update(
			$table,
			array(
				'http_status'  => $result['http_status'],
				'is_broken'    => $result['is_broken'] ? 1 : 0,
				'last_checked' => current_time( 'mysql' ),
				'error_log'    => $result['error_log'],
			),
			array( 'id' => $link['id'] ),
			array( '%d', '%d', '%s', '%s' ),
			array( '%d' )
		);

		// Apply throttling after check.
		$throttler->throttle( $target_url );

		// Trigger immediate notification if broken.
		if ( $result['is_broken'] ) {
			$notifier = new Broken_Links_Notifier( $this->options );
			$notifier->send_immediate_notification( $post_id, $target_url );
		}
	}

	/**
	 * Send daily summary of broken links.
	 *
	 * @return void
	 */
	public function send_daily_summary(): void {
		$notifier = new Broken_Links_Notifier( $this->options );
		$notifier->send_daily_summary();
	}

	/**
	 * Process a batch of posts for link discovery.
	 * Designed for high-volume sites.
	 */
	public function process_discovery_batch(): void {
		$posts = get_posts( array(
			'post_type'      => 'any',
			'posts_per_page' => 20, // Process 20 posts per minute.
			'meta_key'       => '_meowseo_link_scan_pending',
			'meta_value'     => 1,
			'fields'         => 'ids',
		) );

		if ( empty( $posts ) ) {
			return;
		}

		foreach ( $posts as $post_id ) {
			$this->scan_post_links( $post_id );
			delete_post_meta( $post_id, '_meowseo_link_scan_pending' );
		}
	}

	/**
	 * Process a batch of links for status checking.
	 * Designed for high-volume sites.
	 */
	public function process_status_batch(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_link_checks';
		
		// Find links that haven't been checked yet or need re-checking.
		$frequency = (int) $this->options->get( 'broken_links_check_frequency', 72 );
		$cutoff = date( 'Y-m-d H:i:s', time() - ( $frequency * 3600 ) );

		$links = $wpdb->get_results( $wpdb->prepare(
			"SELECT source_post_id, target_url_hash FROM {$table} 
			 WHERE last_checked IS NULL OR last_checked < %s 
			 LIMIT 15", // Check 15 links per minute.
			$cutoff
		), ARRAY_A );

		if ( empty( $links ) ) {
			return;
		}

		foreach ( $links as $link ) {
			$this->check_link_status( (int) $link['source_post_id'], $link['target_url_hash'] );
			
			// Handle Resource Usage ( Requirement: Resource Protection / Potato Hosting ).
			$usage = (int) $this->options->get( 'broken_links_resource_usage', 25 );
			if ( $usage < 100 ) {
				// Calculate delay: Lower usage = longer sleep.
				// e.g., 25% usage = (100 - 25) * 5000 = 375,000 microseconds (0.375s sleep).
				$sleep_time = ( 100 - $usage ) * 10000; 
				usleep( $sleep_time );
			}
		}
	}

	/**
	 * Get link suggestions for a post.
	 *
	 * Suggests internal links based on keyword overlap between the current post's
	 * focus keyword and other published posts' titles and meta descriptions (Requirement 9.4).
	 *
	 * @param int $post_id Post ID.
	 * @return array Array of suggested posts with relevance scores.
	 */
	public function get_link_suggestions( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array();
		}

		// Get focus keyword for current post.
		$focus_keyword = get_post_meta( $post_id, 'meowseo_focus_keyword', true );

		if ( empty( $focus_keyword ) ) {
			return array();
		}

		// Query other published posts.
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'post__not_in'   => array( $post_id ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new \WP_Query( $args );
		$suggestions = array();

		if ( ! $query->have_posts() ) {
			return $suggestions;
		}

		foreach ( $query->posts as $suggested_post ) {
			$relevance = $this->calculate_keyword_overlap( $focus_keyword, $suggested_post );

			if ( $relevance > 0 ) {
				$suggestions[] = array(
					'post_id'   => $suggested_post->ID,
					'title'     => $suggested_post->post_title,
					'url'       => get_permalink( $suggested_post->ID ),
					'relevance' => $relevance,
				);
			}
		}

		// Sort by relevance score (highest first).
		usort(
			$suggestions,
			function ( $a, $b ) {
				return $b['relevance'] <=> $a['relevance'];
			}
		);

		return array_slice( $suggestions, 0, 5 ); // Return top 5 suggestions.
	}

	/**
	 * Calculate keyword overlap between focus keyword and post content.
	 *
	 * Checks for keyword presence in title and meta description.
	 *
	 * @param string   $focus_keyword Focus keyword.
	 * @param \WP_Post $post          Post object.
	 * @return int Relevance score (0-100).
	 */
	private function calculate_keyword_overlap( string $focus_keyword, \WP_Post $post ): int {
		$score = 0;
		$keyword_lower = mb_strtolower( $focus_keyword );

		// Check title.
		$title_lower = mb_strtolower( $post->post_title );
		if ( str_contains( $title_lower, $keyword_lower ) ) {
			$score += 50;
		}

		// Check meta description.
		$meta_description = get_post_meta( $post->ID, 'meowseo_description', true );
		if ( ! empty( $meta_description ) ) {
			$description_lower = mb_strtolower( $meta_description );
			if ( str_contains( $description_lower, $keyword_lower ) ) {
				$score += 30;
			}
		}

		// Check excerpt as fallback.
		if ( 0 === $score && ! empty( $post->post_excerpt ) ) {
			$excerpt_lower = mb_strtolower( $post->post_excerpt );
			if ( str_contains( $excerpt_lower, $keyword_lower ) ) {
				$score += 20;
			}
		}

		return $score;
	}

	/**
	 * Update a specific link URL in a post's content.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $old_url Old URL to find.
	 * @param string $new_url New URL to replace with.
	 * @return bool True on success, false on failure.
	 */
	public function edit_link( int $post_id, string $old_url, string $new_url ): bool {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$content = $post->post_content;
		
		// Use a precise regex to replace only the href attribute.
		// We use preg_quote to handle special characters in the URL.
		$quoted_old_url = preg_quote( $old_url, '/' );
		$pattern = '/<a\s+([^>]*?)href=["\']' . $quoted_old_url . '["\']([^>]*?)>/i';
		$replacement = '<a $1href="' . esc_url( $new_url ) . '"$2>';
		
		$new_content = preg_replace( $pattern, $replacement, $content );

		if ( $new_content !== $content ) {
			wp_update_post( array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			) );

			// Trigger a re-scan.
			$this->scan_post_links( $post_id );
			return true;
		}

		return false;
	}

	/**
	 * Remove a specific link tag but keep its text content.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     URL of the link to remove.
	 * @return bool True on success, false on failure.
	 */
	public function unlink_link( int $post_id, string $url ): bool {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$content = $post->post_content;
		$quoted_url = preg_quote( $url, '/' );
		
		// Regex to find the <a> tag with this URL and capture its inner content.
		$pattern = '/<a\s+[^>]*?href=["\']' . $quoted_url . '["\'][^>]*?>(.*?)<\/a>/is';
		$replacement = '$1';
		
		$new_content = preg_replace( $pattern, $replacement, $content );

		if ( $new_content !== $content ) {
			wp_update_post( array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			) );

			// Trigger a re-scan.
			$this->scan_post_links( $post_id );
			return true;
		}

		return false;
	}

	/**
	 * Apply protections (nofollow, CSS classes) to broken links in content.
	 *
	 * @param string $content Post content.
	 * @return string Filtered content.
	 */
	public function apply_link_protections( string $content ): string {
		$is_nofollow = $this->options->get( 'broken_links_nofollow', false );
		$is_css      = $this->options->get( 'broken_links_css_enabled', false );

		if ( ! $is_nofollow && ! $is_css ) {
			return $content;
		}

		global $wpdb, $post;
		if ( ! $post ) {
			return $content;
		}

		$table = $wpdb->prefix . 'meowseo_link_checks';
		$broken_links = $wpdb->get_col( $wpdb->prepare( "SELECT target_url FROM {$table} WHERE source_post_id = %d AND is_broken = 1", $post->ID ) );

		if ( empty( $broken_links ) ) {
			return $content;
		}

		foreach ( $broken_links as $url ) {
			$quoted_url = preg_quote( $url, '/' );
			// Pattern to find <a> tag with this URL.
			$pattern = '/<a\s+([^>]*?)href=["\']' . $quoted_url . '["\']([^>]*?)>/i';
			
			$content = preg_replace_callback( $pattern, function( $matches ) use ( $url, $is_nofollow, $is_css ) {
				$attrs = $matches[1] . $matches[2];
				
				// 1. Add CSS class if enabled.
				if ( $is_css ) {
					if ( stripos( $attrs, 'class=' ) !== false ) {
						// Append class.
						$attrs = str_ireplace( 'class="', 'class="broken_link ', $attrs );
					} else {
						$attrs .= ' class="broken_link"';
					}
				}

				// 2. Add nofollow if enabled.
				if ( $is_nofollow ) {
					if ( stripos( $attrs, 'rel=' ) !== false ) {
						if ( stripos( $attrs, 'nofollow' ) === false ) {
							$attrs = str_ireplace( 'rel="', 'rel="nofollow ', $attrs );
						}
					} else {
						$attrs .= ' rel="nofollow"';
					}
				}

				return '<a ' . trim( $attrs ) . ' href="' . esc_url( $url ) . '">';
			}, $content );
		}

		return $content;
	}

	/**
	 * Output custom CSS for broken links in site header.
	 *
	 * @return void
	 */
	public function output_broken_links_css(): void {
		if ( ! $this->options->get( 'broken_links_css_enabled', false ) ) {
			return;
		}

		$css = $this->options->get( 'broken_links_css', '.broken_link { text-decoration: line-through; }' );
		if ( ! empty( $css ) ) {
			echo "\n<!-- MeowSEO Broken Links CSS -->\n";
			echo "<style type=\"text/css\">\n";
			echo wp_strip_all_tags( $css ) . "\n";
			echo "</style>\n";
		}
	}

	/**
	 * Log a message to the link checker log file.
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	public function log( string $message ): void {
		if ( ! $this->options->get( 'broken_links_logging_enabled', false ) ) {
			return;
		}

		$log_type = $this->options->get( 'broken_links_log_location_type', 'default' );
		$log_path = ( 'custom' === $log_type ) ? $this->options->get( 'broken_links_log_custom_path', '' ) : WP_CONTENT_DIR . '/meowseo-link-checker.log';

		if ( empty( $log_path ) ) {
			return;
		}

		$entry = sprintf( "[%s] %s\n", date( 'Y-m-d H:i:s' ), $message );
		error_log( $entry, 3, $log_path );
	}
}
