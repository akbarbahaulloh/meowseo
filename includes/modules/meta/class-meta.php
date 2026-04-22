<?php
/**
 * Meta Module
 *
 * Manages per-post SEO meta fields (title, description, robots, canonical).
 * Stores data in wp_postmeta with meowseo_ prefix.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Meta;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\Cache;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta module class
 *
 * Implements the Module interface to provide SEO meta tag management.
 *
 * @since 1.0.0
 */
class Meta implements Module {

	/**
	 * Module ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const MODULE_ID = 'meta';

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
	 * Gutenberg integration instance
	 *
	 * @since 1.0.0
	 * @var Gutenberg
	 */
	private Gutenberg $gutenberg;

	/**
	 * Webmaster Verification instance
	 *
	 * @since 2.0.0
	 * @var Webmaster_Verification
	 */
	private Webmaster_Verification $webmaster_verification;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;

		// Initialize Gutenberg integration
		$plugin_dir = dirname( dirname( dirname( __DIR__ ) ) );
		$plugin_url = plugins_url( '', $plugin_dir . '/meowseo.php' );
		$this->gutenberg = new Gutenberg( $plugin_dir, $plugin_url );

		// Initialize Webmaster Verification (Requirement 3.9)
		$this->webmaster_verification = new Webmaster_Verification( $options );
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
		// Register postmeta fields.
		add_action( 'init', array( $this, 'register_postmeta' ) );

		// Output meta tags in wp_head.
		add_action( 'wp_head', array( $this, 'output_head_tags' ), 1 );

		// Register REST API fields.
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );

		// Initialize Gutenberg integration.
		$this->gutenberg->init();
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
	 * Register postmeta fields
	 *
	 * Registers all SEO meta fields for posts.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_postmeta(): void {
		$post_types = get_post_types( array( 'public' => true ) );

		$meta_fields = array(
			'title'              => array(
				'type'         => 'string',
				'description'  => __( 'SEO title', 'meowseo' ),
				'single'       => true,
				'show_in_rest' => true,
			),
			'description'        => array(
				'type'         => 'string',
				'description'  => __( 'Meta description', 'meowseo' ),
				'single'       => true,
				'show_in_rest' => true,
			),
			'robots'             => array(
				'type'         => 'string',
				'description'  => __( 'Robots directive', 'meowseo' ),
				'single'       => true,
				'show_in_rest' => true,
				'default'      => 'index,follow',
			),
			'canonical'          => array(
				'type'         => 'string',
				'description'  => __( 'Canonical URL', 'meowseo' ),
				'single'       => true,
				'show_in_rest' => true,
			),
			'focus_keyword'      => array(
				'type'         => 'string',
				'description'  => __( 'Focus keyword', 'meowseo' ),
				'single'       => true,
				'show_in_rest' => true,
			),
			'schema_type'        => array(
				'type'         => 'string',
				'description'  => __( 'Schema type override', 'meowseo' ),
				'single'       => true,
				'show_in_rest' => true,
			),
			'social_title'       => array(
				'type'         => 'string',
				'description'  => __( 'Social media title', 'meowseo' ),
				'single'       => true,
				'show_in_rest' => true,
			),
			'social_description' => array(
				'type'         => 'string',
				'description'  => __( 'Social media description', 'meowseo' ),
				'single'       => true,
				'show_in_rest' => true,
			),
			'social_image_id'    => array(
				'type'         => 'integer',
				'description'  => __( 'Social media image ID', 'meowseo' ),
				'single'       => true,
				'show_in_rest' => true,
			),
			'noindex'            => array(
				'type'         => 'boolean',
				'description'  => __( 'Noindex flag', 'meowseo' ),
				'single'       => true,
				'show_in_rest' => true,
				'default'      => false,
			),
		);

		foreach ( $post_types as $post_type ) {
			foreach ( $meta_fields as $field => $args ) {
				register_post_meta(
					$post_type,
					self::META_PREFIX . $field,
					$args
				);
			}

			// Register cornerstone meta field (Requirement 6.1)
			register_post_meta(
				$post_type,
				'_meowseo_is_cornerstone',
				array(
					'type'         => 'string',
					'description'  => __( 'Cornerstone content flag', 'meowseo' ),
					'single'       => true,
					'show_in_rest' => true,
					'default'      => '',
				)
			);
		}
	}

	/**
	 * Output meta tags in head
	 *
	 * Outputs SEO title, meta description, robots, and canonical tags.
	 * Implements cache warming to eliminate DB queries (Requirement 14.1).
	 * Requirement 3.9: Output webmaster verification meta tags before other meta tags.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_head_tags(): void {
		// Requirement 3.9: Output webmaster verification tags first (priority 1)
		$this->webmaster_verification->output_verification_tags();

		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		// Warm cache to load all meta in one operation (Requirement 14.1)
		$this->warm_cache( $post_id );

		// Output title tag.
		$title = $this->get_title( $post_id );
		if ( ! empty( $title ) ) {
			echo '<title>' . esc_html( $title ) . '</title>' . "\n";
		}

		// Output meta description.
		$description = $this->get_description( $post_id );
		if ( ! empty( $description ) ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		}

		// Output robots meta tag.
		$robots = $this->get_robots( $post_id );
		if ( ! empty( $robots ) ) {
			echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
		}

		// Output canonical link.
		$canonical = $this->get_canonical( $post_id );
		if ( ! empty( $canonical ) ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
		}
	}

	/**
	 * Get SEO title for a post
	 *
	 * Returns custom SEO title or falls back to post title with separator.
	 * Implements comprehensive caching to eliminate DB queries (Requirement 14.1).
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string SEO title.
	 */
	public function get_title( int $post_id ): string {
		// Check cache first (Requirement 14.1, 14.2).
		$cached_meta = $this->get_cached_meta( $post_id );
		
		if ( isset( $cached_meta['title'] ) ) {
			return $cached_meta['title'];
		}

		// Get custom title from postmeta.
		$custom_title = get_post_meta( $post_id, self::META_PREFIX . 'title', true );

		if ( ! empty( $custom_title ) ) {
			$title = $custom_title;
		} else {
			// Fallback to post title + separator + site title.
			$post = get_post( $post_id );
			if ( ! $post ) {
				return '';
			}

			$post_title = get_the_title( $post );
			$site_title = get_bloginfo( 'name' );
			$separator = $this->get_separator();

			$title = $post_title . ' ' . $separator . ' ' . $site_title;
		}

		// Cache the result.
		$this->cache_meta_field( $post_id, 'title', $title );

		return $title;
	}

	/**
	 * Get meta description for a post
	 *
	 * Returns custom meta description or falls back to excerpt/content (155 chars).
	 * Implements comprehensive caching to eliminate DB queries (Requirement 14.1).
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string Meta description.
	 */
	public function get_description( int $post_id ): string {
		// Check cache first (Requirement 14.1, 14.2).
		$cached_meta = $this->get_cached_meta( $post_id );
		
		if ( isset( $cached_meta['description'] ) ) {
			return $cached_meta['description'];
		}

		// Get custom description from postmeta.
		$custom_description = get_post_meta( $post_id, self::META_PREFIX . 'description', true );

		if ( ! empty( $custom_description ) ) {
			$description = $custom_description;
		} else {
			// Fallback to excerpt or content (first 155 chars).
			$post = get_post( $post_id );
			if ( ! $post ) {
				return '';
			}

			if ( ! empty( $post->post_excerpt ) ) {
				$text = $post->post_excerpt;
			} else {
				$text = $post->post_content;
			}

			// Strip HTML and shortcodes.
			$text = wp_strip_all_tags( strip_shortcodes( $text ) );
			
			// Limit to 155 characters.
			if ( mb_strlen( $text ) > 155 ) {
				$description = mb_substr( $text, 0, 155 ) . '...';
			} else {
				$description = $text;
			}
		}

		// Cache the result.
		$this->cache_meta_field( $post_id, 'description', $description );

		return $description;
	}

	/**
	 * Get robots directive for a post
	 *
	 * Implements comprehensive caching to eliminate DB queries (Requirement 14.1).
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string Robots directive (e.g., 'index,follow').
	 */
	public function get_robots( int $post_id ): string {
		// Check cache first (Requirement 14.1, 14.2).
		$cached_meta = $this->get_cached_meta( $post_id );
		
		if ( isset( $cached_meta['robots'] ) ) {
			return $cached_meta['robots'];
		}

		// Get robots directive from postmeta.
		$robots = get_post_meta( $post_id, self::META_PREFIX . 'robots', true );

		if ( empty( $robots ) ) {
			$robots = 'index,follow';
		}

		// Cache the result.
		$this->cache_meta_field( $post_id, 'robots', $robots );

		return $robots;
	}

	/**
	 * Get canonical URL for a post
	 *
	 * Implements comprehensive caching to eliminate DB queries (Requirement 14.1).
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string Canonical URL.
	 */
	public function get_canonical( int $post_id ): string {
		// Check cache first (Requirement 14.1, 14.2).
		$cached_meta = $this->get_cached_meta( $post_id );
		
		if ( isset( $cached_meta['canonical'] ) ) {
			return $cached_meta['canonical'];
		}

		// Get custom canonical from postmeta.
		$custom_canonical = get_post_meta( $post_id, self::META_PREFIX . 'canonical', true );

		if ( ! empty( $custom_canonical ) ) {
			$canonical = $custom_canonical;
		} else {
			// Fallback to post permalink.
			$canonical = get_permalink( $post_id );
			if ( ! $canonical ) {
				$canonical = '';
			}
		}

		// Cache the result.
		$this->cache_meta_field( $post_id, 'canonical', $canonical );

		return $canonical;
	}

	/**
	 * Register REST API fields
	 *
	 * Exposes SEO meta fields via REST API.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_rest_fields(): void {
		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $post_type ) {
			register_rest_field(
				$post_type,
				'meowseo_meta',
				array(
					'get_callback'    => array( $this, 'get_rest_meta' ),
					'update_callback' => array( $this, 'update_rest_meta' ),
					'schema'          => array(
						'description' => __( 'MeowSEO meta data', 'meowseo' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
					),
				)
			);
		}

		// Register analysis REST route.
		register_rest_route(
			'meowseo/v1',
			'/analysis/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_analysis' ),
				'permission_callback' => array( $this, 'check_analysis_permission' ),
				'args'                => array(
					'post_id'       => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'content'       => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'wp_kses_post',
						'default'           => '',
					),
					'focus_keyword' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
				),
			)
		);
	}

	/**
	 * Get REST meta callback
	 *
	 * @since 1.0.0
	 * @param array $object Post object.
	 * @return array SEO meta data.
	 */
	public function get_rest_meta( array $object ): array {
		$post_id = $object['id'];

		return array(
			'title'       => $this->get_title( $post_id ),
			'description' => $this->get_description( $post_id ),
			'robots'      => $this->get_robots( $post_id ),
			'canonical'   => $this->get_canonical( $post_id ),
		);
	}

	/**
	 * Update REST meta callback
	 *
	 * @since 1.0.0
	 * @param mixed $value   New meta value.
	 * @param object $object Post object.
	 * @return bool True on success.
	 */
	public function update_rest_meta( $value, $object ): bool {
		$post_id = $object->ID;

		// Verify user has permission to edit this post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		// Update meta fields if provided.
		if ( isset( $value['title'] ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'title', sanitize_text_field( $value['title'] ) );
		}

		if ( isset( $value['description'] ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'description', sanitize_textarea_field( $value['description'] ) );
		}

		if ( isset( $value['robots'] ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'robots', sanitize_text_field( $value['robots'] ) );
		}

		if ( isset( $value['canonical'] ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'canonical', esc_url_raw( $value['canonical'] ) );
		}

		// Invalidate cache.
		Cache::delete( "meta_{$post_id}" );

		return true;
	}

	/**
	 * Get SEO analysis for a post
	 *
	 * Analyzes content against focus keyword and returns score with checks.
	 *
	 * @since 1.0.0
	 * @param int    $post_id       Post ID.
	 * @param string $content       Post content (HTML).
	 * @param string $focus_keyword Focus keyword.
	 * @return array Analysis result with score, checks, and color.
	 */
	public function get_seo_analysis( int $post_id, string $content = '', string $focus_keyword = '' ): array {
		// Get post data if not provided.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array(
				'score'  => 0,
				'checks' => array(),
				'color'  => 'red',
			);
		}

		// Use provided content or get from post.
		if ( empty( $content ) ) {
			$content = $post->post_content;
		}

		// Get focus keyword from postmeta if not provided.
		if ( empty( $focus_keyword ) ) {
			$focus_keyword = get_post_meta( $post_id, self::META_PREFIX . 'focus_keyword', true );
		}

		// Get SEO title and description.
		$title       = $this->get_title( $post_id );
		$description = $this->get_description( $post_id );
		$slug        = $post->post_name;

		// Prepare data for analyzer.
		$data = array(
			'title'         => $title,
			'description'   => $description,
			'content'       => $content,
			'slug'          => $slug,
			'focus_keyword' => $focus_keyword,
		);

		// Run analysis.
		return SEO_Analyzer::analyze( $data );
	}

	/**
	 * Get readability analysis for content
	 *
	 * Analyzes content readability and returns score with checks.
	 *
	 * @since 1.0.0
	 * @param string $content Post content (HTML).
	 * @return array Analysis result with score, checks, and color.
	 */
	public function get_readability_analysis( string $content ): array {
		if ( empty( $content ) ) {
			return array(
				'score'  => 0,
				'checks' => array(),
				'color'  => 'red',
			);
		}

		// Run readability analysis.
		return Readability::analyze( $content );
	}

	/**
	 * REST API callback for analysis endpoint
	 *
	 * Requirements 15.2, 15.3: Verify nonce and capability
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_get_analysis( $request ): \WP_REST_Response {
		// Verify nonce (Requirement 15.2).
		if ( ! $this->verify_nonce( $request ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'meowseo' ),
				),
				403
			);
		}

		$post_id       = (int) $request['post_id'];
		$content       = $request['content'];
		$focus_keyword = $request->get_param( 'focus_keyword' ) ?? '';

		// Get SEO analysis.
		$seo_analysis = $this->get_seo_analysis( $post_id, $content, $focus_keyword );

		// Get readability analysis.
		$readability_analysis = $this->get_readability_analysis( $content );

		// Combine results.
		$response = array(
			'seo'         => $seo_analysis,
			'readability' => $readability_analysis,
		);

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Permission callback for analysis endpoint
	 *
	 * Requirement 15.3: Verify user capability
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool True if user has permission.
	 */
	public function check_analysis_permission( \WP_REST_Request $request ): bool {
		$post_id = (int) $request['post_id'];
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Verify nonce from request
	 *
	 * Requirement 15.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool True if nonce is valid.
	 */
	private function verify_nonce( \WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( empty( $nonce ) ) {
			return false;
		}

		return wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Get title separator
	 *
	 * @since 1.0.0
	 * @return string Title separator.
	 */
	private function get_separator(): string {
		return $this->options->get_separator();
	}

	/**
	 * Get cached meta for a post
	 *
	 * Retrieves all cached SEO meta fields for a post in a single cache lookup.
	 * Implements cache group isolation (Requirement 14.2, 14.3).
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Cached meta array, or empty array if not cached.
	 */
	private function get_cached_meta( int $post_id ): array {
		$cache_key = "meta_{$post_id}";
		$cached = Cache::get( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		return array();
	}

	/**
	 * Cache a single meta field
	 *
	 * Stores a single meta field value in the cache, merging with existing cached data.
	 * Uses cache group isolation for meowseo data (Requirement 14.2).
	 * Falls back to transients when Object Cache unavailable (Requirement 14.3).
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key (without prefix).
	 * @param mixed  $value   Meta value.
	 * @return void
	 */
	private function cache_meta_field( int $post_id, string $key, $value ): void {
		$cache_key = "meta_{$post_id}";
		$cached = $this->get_cached_meta( $post_id );

		$cached[ $key ] = $value;

		// Cache for 1 hour (3600 seconds)
		Cache::set( $cache_key, $cached, 3600 );
	}

	/**
	 * Warm cache for a post
	 *
	 * Pre-loads all SEO meta fields into cache in a single operation.
	 * This eliminates multiple DB queries when rendering frontend.
	 * (Requirement 14.1)
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function warm_cache( int $post_id ): void {
		// Check if already cached
		$cached = $this->get_cached_meta( $post_id );
		if ( ! empty( $cached ) ) {
			return;
		}

		// Load all meta fields at once
		$meta_data = array(
			'title'       => $this->get_title( $post_id ),
			'description' => $this->get_description( $post_id ),
			'robots'      => $this->get_robots( $post_id ),
			'canonical'   => $this->get_canonical( $post_id ),
		);

		// Store in cache
		$cache_key = "meta_{$post_id}";
		Cache::set( $cache_key, $meta_data, 3600 );
	}
}
