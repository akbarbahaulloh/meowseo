<?php
/**
 * Social Module
 *
 * Manages Open Graph and Twitter Card meta tags for social media sharing.
 * Outputs social meta tags in wp_head with fallback logic.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Social;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\Cache;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Social module class
 *
 * Implements the Module interface to provide social meta tag management.
 *
 * @since 1.0.0
 */
class Social implements Module {

	/**
	 * Module ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const MODULE_ID = 'social';

	/**
	 * Postmeta key prefix
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const META_PREFIX = 'meowseo_';

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module
	 *
	 * Register hooks and initialize module functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		// Output social meta tags in wp_head.
		add_action( 'wp_head', array( $this, 'output_social_tags' ), 5 );

		// Register REST API routes.
		add_action( 'rest_api_init', array( 'MeowSEO\Modules\Social\Social_REST', 'register_routes' ) );

		// Invalidate cache on post save.
		add_action( 'save_post', array( $this, 'invalidate_cache' ), 10, 1 );
	}

	/**
	 * Get module ID
	 *
	 * @since 1.0.0
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return self::MODULE_ID;
	}

	/**
	 * Output social meta tags in head
	 *
	 * Outputs Open Graph and Twitter Card meta tags.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_social_tags(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Get social meta data.
		$social_data = $this->get_social_data( $post_id );

		// Output Open Graph tags.
		$this->output_open_graph_tags( $social_data );

		// Output Twitter Card tags.
		$this->output_twitter_card_tags( $social_data );
	}

	/**
	 * Get social meta data for a post
	 *
	 * Returns social title, description, image, type, and URL with fallback logic.
	 * Implements caching to eliminate DB queries (Requirement 14.1).
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Social meta data.
	 */
	public function get_social_data( int $post_id ): array {
		// Check cache first (Requirement 14.1, 14.2).
		$cache_key = "social_{$post_id}";
		$cached = Cache::get( $cache_key );

		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		// Get social title with fallback.
		$social_title = $this->get_social_title( $post_id, $post );

		// Get social description with fallback.
		$social_description = $this->get_social_description( $post_id, $post );

		// Get social image with fallback.
		$social_image = $this->get_social_image( $post_id, $post );

		// Get Open Graph type.
		$og_type = $this->get_og_type( $post );

		// Get URL.
		$url = get_permalink( $post_id );

		$data = array(
			'title'       => $social_title,
			'description' => $social_description,
			'image'       => $social_image,
			'type'        => $og_type,
			'url'         => $url,
		);

		// Cache the result for 1 hour (Requirement 14.1).
		Cache::set( $cache_key, $data, 3600 );

		return $data;
	}

	/**
	 * Get social title with fallback
	 *
	 * Falls back: per-post social title → SEO title → post title.
	 *
	 * @since 1.0.0
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return string Social title.
	 */
	private function get_social_title( int $post_id, \WP_Post $post ): string {
		// Check for per-post social title override.
		$social_title = get_post_meta( $post_id, self::META_PREFIX . 'social_title', true );

		if ( ! empty( $social_title ) ) {
			return $social_title;
		}

		// Fallback to SEO title.
		$seo_title = get_post_meta( $post_id, self::META_PREFIX . 'title', true );

		if ( ! empty( $seo_title ) ) {
			return $seo_title;
		}

		// Fallback to post title.
		return get_the_title( $post );
	}

	/**
	 * Get social description with fallback
	 *
	 * Falls back: per-post social description → SEO description → excerpt → content (155 chars).
	 *
	 * @since 1.0.0
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return string Social description.
	 */
	private function get_social_description( int $post_id, \WP_Post $post ): string {
		// Check for per-post social description override.
		$social_description = get_post_meta( $post_id, self::META_PREFIX . 'social_description', true );

		if ( ! empty( $social_description ) ) {
			return $social_description;
		}

		// Fallback to SEO description.
		$seo_description = get_post_meta( $post_id, self::META_PREFIX . 'description', true );

		if ( ! empty( $seo_description ) ) {
			return $seo_description;
		}

		// Fallback to excerpt or content.
		if ( ! empty( $post->post_excerpt ) ) {
			$text = $post->post_excerpt;
		} else {
			$text = $post->post_content;
		}

		// Strip HTML and shortcodes.
		$text = wp_strip_all_tags( strip_shortcodes( $text ) );

		// Limit to 155 characters.
		if ( mb_strlen( $text ) > 155 ) {
			return mb_substr( $text, 0, 155 ) . '...';
		}

		return $text;
	}

	/**
	 * Get social image with fallback
	 *
	 * Falls back: per-post social image → featured image → global default.
	 *
	 * @since 1.0.0
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return string Social image URL.
	 */
	private function get_social_image( int $post_id, \WP_Post $post ): string {
		// Check for per-post social image override.
		$social_image_id = get_post_meta( $post_id, self::META_PREFIX . 'social_image_id', true );

		if ( ! empty( $social_image_id ) ) {
			$image_url = wp_get_attachment_image_url( (int) $social_image_id, 'full' );
			if ( $image_url ) {
				return $image_url;
			}
		}

		// Fallback to featured image.
		if ( has_post_thumbnail( $post_id ) ) {
			$image_url = get_the_post_thumbnail_url( $post_id, 'full' );
			if ( $image_url ) {
				return $image_url;
			}
		}

		// Fallback to global default social image.
		return $this->options->get_default_social_image_url();
	}

	/**
	 * Get Open Graph type
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 * @return string Open Graph type.
	 */
	private function get_og_type( \WP_Post $post ): string {
		// Default to 'article' for posts, 'website' for pages.
		if ( 'post' === $post->post_type ) {
			return 'article';
		}

		return 'website';
	}

	/**
	 * Output Open Graph meta tags
	 *
	 * @since 1.0.0
	 * @param array $data Social meta data.
	 * @return void
	 */
	private function output_open_graph_tags( array $data ): void {
		if ( empty( $data ) ) {
			return;
		}

		// og:title
		if ( ! empty( $data['title'] ) ) {
			echo '<meta property="og:title" content="' . esc_attr( $data['title'] ) . '">' . "\n";
		}

		// og:description
		if ( ! empty( $data['description'] ) ) {
			echo '<meta property="og:description" content="' . esc_attr( $data['description'] ) . '">' . "\n";
		}

		// og:image
		if ( ! empty( $data['image'] ) ) {
			echo '<meta property="og:image" content="' . esc_url( $data['image'] ) . '">' . "\n";
		}

		// og:type
		if ( ! empty( $data['type'] ) ) {
			echo '<meta property="og:type" content="' . esc_attr( $data['type'] ) . '">' . "\n";
		}

		// og:url
		if ( ! empty( $data['url'] ) ) {
			echo '<meta property="og:url" content="' . esc_url( $data['url'] ) . '">' . "\n";
		}
	}

	/**
	 * Output Twitter Card meta tags
	 *
	 * @since 1.0.0
	 * @param array $data Social meta data.
	 * @return void
	 */
	private function output_twitter_card_tags( array $data ): void {
		if ( empty( $data ) ) {
			return;
		}

		// twitter:card
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";

		// twitter:title
		if ( ! empty( $data['title'] ) ) {
			echo '<meta name="twitter:title" content="' . esc_attr( $data['title'] ) . '">' . "\n";
		}

		// twitter:description
		if ( ! empty( $data['description'] ) ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $data['description'] ) . '">' . "\n";
		}

		// twitter:image
		if ( ! empty( $data['image'] ) ) {
			echo '<meta name="twitter:image" content="' . esc_url( $data['image'] ) . '">' . "\n";
		}
	}

	/**
	 * Invalidate social cache on post save
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function invalidate_cache( int $post_id ): void {
		Cache::delete( "social_{$post_id}" );
	}
}
