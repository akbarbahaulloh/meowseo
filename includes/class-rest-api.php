<?php
/**
 * REST API Layer
 *
 * Centralized REST API registration for all MeowSEO endpoints under meowseo/v1 namespace.
 * Provides meta CRUD, schema access, and coordinates with module-specific REST classes.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO;

use MeowSEO\Helpers\Cache;
use MeowSEO\REST\REST_Logs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API class
 *
 * Centralizes REST endpoint registration under meowseo/v1 namespace.
 *
 * @since 1.0.0
 */
class REST_API {

	/**
	 * REST namespace
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const NAMESPACE = 'meowseo/v1';

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
	 * Module Manager instance
	 *
	 * @since 1.0.0
	 * @var Module_Manager
	 */
	private Module_Manager $module_manager;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options        $options        Options instance.
	 * @param Module_Manager $module_manager Module Manager instance.
	 */
	public function __construct( Options $options, Module_Manager $module_manager ) {
		$this->options        = $options;
		$this->module_manager = $module_manager;
	}

	/**
	 * Register REST API routes
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes(): void {
		// Register meta CRUD endpoints.
		$this->register_meta_routes();

		// Register settings endpoints.
		$this->register_settings_routes();

		// Register dashboard widget endpoints.
		$this->register_dashboard_routes();

		// Register suggestion endpoint (Requirement 15.1).
		$this->register_suggestion_routes();

		// Register keyword management endpoint (Requirements 2.1, 2.2, 2.9, 2.10, 2.11).
		$this->register_keyword_routes();

		// Register public SEO endpoints (Requirements 17.1-17.7, 18.1-18.6, 27.1-27.5).
		$this->register_public_seo_routes();

		// Register Sprint 4 endpoints (Task 16.3).
		$this->register_sprint4_routes();

		// Register REST_Logs routes (Requirement 14.1).
		$rest_logs = new REST_Logs( $this->options );
		$rest_logs->register_routes();
	}

	/**
	 * Register meta CRUD endpoints
	 *
	 * Provides comprehensive meta access for headless deployments.
	 * Requirements: 13.1, 13.2, 13.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_meta_routes(): void {
		// GET endpoint for retrieving all SEO meta for a post.
		register_rest_route(
			self::NAMESPACE,
			'/meta/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_meta' ),
				'permission_callback' => array( $this, 'get_meta_permission' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
				),
			)
		);

		// POST endpoint for updating SEO meta for a post.
		register_rest_route(
			self::NAMESPACE,
			'/meta/(?P<post_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_meta' ),
				'permission_callback' => array( $this, 'update_meta_permission' ),
				'args'                => array(
					'post_id'     => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
					'title'       => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'description' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'robots'      => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'canonical'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);
	}

	/**
	 * Register settings endpoints
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_settings_routes(): void {
		// GET endpoint for retrieving all plugin settings.
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'manage_options_permission' ),
			)
		);

		// POST endpoint for saving plugin settings.
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_settings' ),
				'permission_callback' => array( $this, 'manage_options_permission' ),
				'args'                => $this->get_settings_schema(),
			)
		);
	}

	/**
	 * Register dashboard widget endpoints
	 *
	 * Provides async data loading for dashboard widgets.
	 * Requirements: 3.1, 3.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_dashboard_routes(): void {
		// Content Health widget endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/dashboard/content-health',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_content_health' ),
				'permission_callback' => array( $this, 'dashboard_permission' ),
			)
		);

		// Sitemap Status widget endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/dashboard/sitemap-status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_sitemap_status' ),
				'permission_callback' => array( $this, 'dashboard_permission' ),
			)
		);

		// Top 404s widget endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/dashboard/top-404s',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_top_404s' ),
				'permission_callback' => array( $this, 'dashboard_permission' ),
			)
		);

		// GSC Summary widget endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/dashboard/gsc-summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_gsc_summary' ),
				'permission_callback' => array( $this, 'dashboard_permission' ),
			)
		);

		// Discover Performance widget endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/dashboard/discover-performance',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_discover_performance' ),
				'permission_callback' => array( $this, 'dashboard_permission' ),
			)
		);

		// Index Queue widget endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/dashboard/index-queue',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_index_queue' ),
				'permission_callback' => array( $this, 'dashboard_permission' ),
			)
		);

		// Cornerstone Content widget endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/dashboard/cornerstone-content',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_cornerstone_content' ),
				'permission_callback' => array( $this, 'dashboard_permission' ),
			)
		);
	}

	/**
	 * Register suggestion endpoint
	 *
	 * Provides internal link suggestions with rate limiting.
	 * Requirements: 15.1, 15.2, 15.3, 15.4, 15.5, 15.6
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_suggestion_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/internal-links/suggest',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_suggestions' ),
				'permission_callback' => array( $this, 'edit_posts_permission' ),
				'args'                => array(
					'content' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Register keyword management endpoint
	 *
	 * Provides keyword update and analysis triggering.
	 * Requirements: 2.1, 2.2, 2.9, 2.10, 2.11
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_keyword_routes(): void {
		// POST /meowseo/v1/keywords/{post_id} - Update keywords and trigger analysis.
		register_rest_route(
			self::NAMESPACE,
			'/keywords/(?P<post_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_keywords' ),
				'permission_callback' => array( $this, 'update_meta_permission' ),
				'args'                => array(
					'post_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
					'primary'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'secondary' => array(
						'required'          => false,
						'type'              => 'array',
						'items'             => array(
							'type' => 'string',
						),
						'sanitize_callback' => array( $this, 'sanitize_keyword_array' ),
					),
				),
			)
		);
	}

	/**
	 * Get meta for a post
	 *
	 * Returns all SEO meta fields for headless consumption.
	 * Requirement: 13.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_meta( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];

		// Get meta module if active.
		$meta_module = $this->module_manager->get_module( 'meta' );
		$social_module = $this->module_manager->get_module( 'social' );
		$schema_module = $this->module_manager->get_module( 'schema' );

		$data = array(
			'post_id' => $post_id,
		);

		// Get SEO meta if meta module is active.
		if ( $meta_module ) {
			$data['title']       = $meta_module->get_title( $post_id );
			$data['description'] = $meta_module->get_description( $post_id );
			$data['robots']      = $meta_module->get_robots( $post_id );
			$data['canonical']   = $meta_module->get_canonical( $post_id );
		}

		// Get social meta if social module is active.
		if ( $social_module ) {
			$social_data = $social_module->get_social_data( $post_id );
			$data['openGraph'] = array(
				'title'       => $social_data['title'] ?? '',
				'description' => $social_data['description'] ?? '',
				'image'       => $social_data['image'] ?? '',
				'type'        => $social_data['type'] ?? '',
				'url'         => $social_data['url'] ?? '',
			);
			$data['twitterCard'] = array(
				'card'        => 'summary_large_image',
				'title'       => $social_data['title'] ?? '',
				'description' => $social_data['description'] ?? '',
				'image'       => $social_data['image'] ?? '',
			);
		}

		// Get schema JSON-LD if schema module is active.
		if ( $schema_module ) {
			$data['schemaJsonLd'] = $schema_module->get_schema_json( $post_id );
		}

		$response = new \WP_REST_Response( $data, 200 );

		// Add cache headers for CDN/edge caching (Requirement 13.6).
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Update meta for a post
	 *
	 * Updates SEO meta fields with proper authentication.
	 * Requirement: 13.1, 13.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function update_meta( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];

		// Verify nonce (Requirement 15.2, 2.19).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification (Requirement 2.19).
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: invalid nonce',
				array(
					'endpoint' => 'meta/update',
					'post_id'  => $post_id,
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		// Update meta fields if provided.
		if ( $request->has_param( 'title' ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'title', $request->get_param( 'title' ) );
		}

		if ( $request->has_param( 'description' ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'description', $request->get_param( 'description' ) );
		}

		if ( $request->has_param( 'robots' ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'robots', $request->get_param( 'robots' ) );
		}

		if ( $request->has_param( 'canonical' ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'canonical', $request->get_param( 'canonical' ) );
		}

		// Invalidate cache.
		Cache::delete( "meta_{$post_id}" );

		$response = new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Meta updated successfully.', 'meowseo' ),
				'post_id' => $post_id,
			),
			200
		);

		// No cache for mutations.
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Get all plugin settings
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$settings = $this->options->get_all();

		// Remove sensitive data.
		unset( $settings['gsc_credentials'] );

		$response = new \WP_REST_Response(
			array(
				'success'  => true,
				'settings' => $settings,
			),
			200
		);

		// Add cache headers.
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Update plugin settings
	 *
	 * Validates settings via REST API with nonce checks.
	 * Requirements: 2.3, 2.5, 15.2, 15.3
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function update_settings( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce (Requirement 15.2, 2.19).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification (Requirement 2.19).
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: invalid nonce',
				array(
					'endpoint' => 'settings/update',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		$settings = $request->get_json_params();

		if ( ! is_array( $settings ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid settings format.', 'meowseo' ),
				),
				400
			);
		}

		// Validate and sanitize settings (Requirement 2.3).
		$validated_settings = $this->validate_settings( $settings );

		if ( is_wp_error( $validated_settings ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $validated_settings->get_error_message(),
				),
				400
			);
		}

		// Update settings (excluding sensitive fields).
		foreach ( $validated_settings as $key => $value ) {
			// Skip sensitive fields.
			if ( in_array( $key, array( 'gsc_credentials' ), true ) ) {
				continue;
			}

			$this->options->set( $key, $value );
		}

		$this->options->save();

		$response = new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Settings updated successfully.', 'meowseo' ),
			),
			200
		);

		// No cache for mutations.
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Get content health widget data
	 *
	 * Requirements: 3.1, 3.4, 32.1, 32.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_content_health( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce (Requirement 3.2).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification (Requirement 32.1).
			\MeowSEO\Helpers\Logger::warning(
				'ReST request failed: invalid nonce',
				array(
					'endpoint' => 'dashboard/content-health',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		try {
			// Get Dashboard_Widgets instance.
			$dashboard_widgets = new \MeowSEO\Admin\Dashboard_Widgets( $this->options, $this->module_manager );
			$data = $dashboard_widgets->get_content_health_data();

			$response = new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $data,
				),
				200
			);

			// Add cache headers (Requirement 3.4).
			$response->header( 'Cache-Control', 'public, max-age=300' );

			return $response;
		} catch ( \Exception $e ) {
			// Log the error with context (Requirement 32.2).
			\MeowSEO\Helpers\Logger::error(
				'Failed to retrieve content health data',
				array(
					'endpoint'  => 'dashboard/content-health',
					'user_id'   => get_current_user_id(),
					'error_msg' => $e->getMessage(),
				)
			);

			// Return user-friendly error message without exposing internals (Requirement 32.2, 32.5).
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'An error occurred while loading content health data. Please try again.', 'meowseo' ),
					'code'    => 'database_error',
				),
				500
			);
		}
	}

	/**
	 * Get sitemap status widget data
	 *
	 * Requirements: 3.1, 3.4, 32.1, 32.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_sitemap_status( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce (Requirement 3.2).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification (Requirement 32.1).
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: invalid nonce',
				array(
					'endpoint' => 'dashboard/sitemap-status',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		try {
			// Get Dashboard_Widgets instance.
			$dashboard_widgets = new \MeowSEO\Admin\Dashboard_Widgets( $this->options, $this->module_manager );
			$data = $dashboard_widgets->get_sitemap_status_data();

			$response = new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $data,
				),
				200
			);

			// Add cache headers (Requirement 3.4).
			$response->header( 'Cache-Control', 'public, max-age=300' );

			return $response;
		} catch ( \Exception $e ) {
			// Log the error with context (Requirement 32.2).
			\MeowSEO\Helpers\Logger::error(
				'Failed to retrieve sitemap status data',
				array(
					'endpoint'  => 'dashboard/sitemap-status',
					'user_id'   => get_current_user_id(),
					'error_msg' => $e->getMessage(),
				)
			);

			// Return user-friendly error message without exposing internals (Requirement 32.2, 32.5).
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'An error occurred while loading sitemap status. Please try again.', 'meowseo' ),
					'code'    => 'database_error',
				),
				500
			);
		}
	}

	/**
	 * Get top 404s widget data
	 *
	 * Requirements: 3.1, 3.4, 32.1, 32.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_top_404s( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce (Requirement 3.2).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification (Requirement 32.1).
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: invalid nonce',
				array(
					'endpoint' => 'dashboard/top-404s',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		try {
			// Get Dashboard_Widgets instance.
			$dashboard_widgets = new \MeowSEO\Admin\Dashboard_Widgets( $this->options, $this->module_manager );
			$data = $dashboard_widgets->get_top_404s_data();

			$response = new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $data,
				),
				200
			);

			// Add cache headers (Requirement 3.4).
			$response->header( 'Cache-Control', 'public, max-age=300' );

			return $response;
		} catch ( \Exception $e ) {
			// Log the error with context (Requirement 32.2).
			\MeowSEO\Helpers\Logger::error(
				'Failed to retrieve top 404s data',
				array(
					'endpoint'  => 'dashboard/top-404s',
					'user_id'   => get_current_user_id(),
					'error_msg' => $e->getMessage(),
				)
			);

			// Return user-friendly error message without exposing internals (Requirement 32.2, 32.5).
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'An error occurred while loading 404 data. Please try again.', 'meowseo' ),
					'code'    => 'database_error',
				),
				500
			);
		}
	}

	/**
	 * Get GSC summary widget data
	 *
	 * Requirements: 3.1, 3.4, 32.1, 32.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_gsc_summary( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce (Requirement 3.2).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification (Requirement 32.1).
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: invalid nonce',
				array(
					'endpoint' => 'dashboard/gsc-summary',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		try {
			// Get Dashboard_Widgets instance.
			$dashboard_widgets = new \MeowSEO\Admin\Dashboard_Widgets( $this->options, $this->module_manager );
			$data = $dashboard_widgets->get_gsc_summary_data();

			$response = new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $data,
				),
				200
			);

			// Add cache headers (Requirement 3.4).
			$response->header( 'Cache-Control', 'public, max-age=300' );

			return $response;
		} catch ( \Exception $e ) {
			// Log the error with context (Requirement 32.2).
			\MeowSEO\Helpers\Logger::error(
				'Failed to retrieve GSC summary data',
				array(
					'endpoint'  => 'dashboard/gsc-summary',
					'user_id'   => get_current_user_id(),
					'error_msg' => $e->getMessage(),
				)
			);

			// Return user-friendly error message without exposing internals (Requirement 32.2, 32.5).
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'An error occurred while loading Search Console data. Please try again.', 'meowseo' ),
					'code'    => 'database_error',
				),
				500
			);
		}
	}

	/**
	 * Get Discover performance widget data
	 *
	 * Requirements: 3.1, 3.4
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_discover_performance( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce (Requirement 3.2, 2.19).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification (Requirement 2.19).
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: invalid nonce',
				array(
					'endpoint' => 'dashboard/discover-performance',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		// Get Dashboard_Widgets instance.
		$dashboard_widgets = new \MeowSEO\Admin\Dashboard_Widgets( $this->options, $this->module_manager );
		$data = $dashboard_widgets->get_discover_performance_data();

		$response = new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);

		// Add cache headers (Requirement 3.4).
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Get index queue widget data
	 *
	 * Requirements: 3.1, 3.4
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_index_queue( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce (Requirement 3.2, 2.19).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification (Requirement 2.19).
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: invalid nonce',
				array(
					'endpoint' => 'dashboard/index-queue',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		// Get Dashboard_Widgets instance.
		$dashboard_widgets = new \MeowSEO\Admin\Dashboard_Widgets( $this->options, $this->module_manager );
		$data = $dashboard_widgets->get_index_queue_data();

		$response = new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);

		// Add cache headers (Requirement 3.4).
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Get cornerstone content widget data
	 *
	 * Requirements: 3.1, 3.4, 6.10
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_cornerstone_content( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce (Requirement 3.2, 2.19).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification (Requirement 2.19).
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: invalid nonce',
				array(
					'endpoint' => 'dashboard/cornerstone-content',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		try {
			// Get Dashboard_Widgets instance.
			$dashboard_widgets = new \MeowSEO\Admin\Dashboard_Widgets( $this->options, $this->module_manager );
			$data = $dashboard_widgets->get_cornerstone_content_data();

			$response = new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $data,
				),
				200
			);

			// Add cache headers (Requirement 3.4).
			$response->header( 'Cache-Control', 'public, max-age=300' );

			return $response;
		} catch ( \Exception $e ) {
			// Log the error with context (Requirement 32.2).
			\MeowSEO\Helpers\Logger::error(
				'Failed to retrieve cornerstone content data',
				array(
					'endpoint'  => 'dashboard/cornerstone-content',
					'user_id'   => get_current_user_id(),
					'error_msg' => $e->getMessage(),
				)
			);

			// Return user-friendly error message without exposing internals (Requirement 32.2, 32.5).
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'An error occurred while loading cornerstone content. Please try again.', 'meowseo' ),
					'code'    => 'database_error',
				),
				500
			);
		}
	}

	/**
	 * Get internal link suggestions
	 *
	 * Provides suggestions for internal linking based on content analysis.
	 * Requirements: 15.1, 15.2, 15.3, 15.4, 15.5, 15.6
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_suggestions( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce (Requirement 15.2).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification (Requirement 32.1).
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: invalid nonce',
				array(
					'endpoint' => 'internal-links/suggest',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		// Check rate limit (Requirement 15.4, 15.5).
		$engine = new \MeowSEO\Admin\Suggestion_Engine();
		$user_id = get_current_user_id();

		if ( ! $engine->check_rate_limit( $user_id ) ) {
			// Log rate limit exceeded (Requirement 32.1).
			\MeowSEO\Helpers\Logger::warning(
				'Rate limit exceeded for suggestions endpoint',
				array(
					'endpoint' => 'internal-links/suggest',
					'user_id'  => $user_id,
				)
			);

			$response = new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Too many requests. Please wait before trying again.', 'meowseo' ),
					'code'    => 'rate_limit_exceeded',
				),
				429
			);
			$response->header( 'Retry-After', '2' );
			return $response;
		}

		// Get parameters.
		$content = $request->get_param( 'content' );
		$post_id = (int) $request->get_param( 'post_id' );

		if ( empty( $content ) || empty( $post_id ) ) {
			// Log invalid parameters (Requirement 32.1).
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: missing parameters',
				array(
					'endpoint' => 'internal-links/suggest',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Missing required parameters.', 'meowseo' ),
					'code'    => 'invalid_parameter',
				),
				400
			);
		}

		try {
			// Get suggestions.
			$suggestions = $engine->get_suggestions( $content, $post_id );

			$response = new \WP_REST_Response(
				array(
					'success'      => true,
					'suggestions'  => $suggestions,
				),
				200
			);

			// Add cache headers.
			$response->header( 'Cache-Control', 'no-store' );

			return $response;
		} catch ( \Exception $e ) {
			// Log the error with context (Requirement 32.2).
			\MeowSEO\Helpers\Logger::error(
				'Failed to retrieve suggestions',
				array(
					'endpoint'  => 'internal-links/suggest',
					'user_id'   => get_current_user_id(),
					'post_id'   => $post_id,
					'error_msg' => $e->getMessage(),
				)
			);

			// Return user-friendly error message without exposing internals (Requirement 32.2, 32.5).
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'An error occurred while retrieving suggestions. Please try again.', 'meowseo' ),
					'code'    => 'database_error',
				),
				500
			);
		}
	}

	/**
	 * Update keywords for a post
	 *
	 * Updates primary and secondary keywords, validates count, and triggers analysis.
	 * Requirements: 2.1, 2.2, 2.9, 2.10, 2.11
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function update_keywords( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce (Requirement 15.2).
		if ( ! $this->verify_nonce( $request ) ) {
			// Log the failed nonce verification.
			\MeowSEO\Helpers\Logger::warning(
				'REST request failed: invalid nonce',
				array(
					'endpoint' => 'keywords/update',
					'user_id'  => get_current_user_id(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		$post_id = (int) $request['post_id'];
		$primary = $request->get_param( 'primary' );
		$secondary = $request->get_param( 'secondary' );

		// Get Keyword_Manager instance.
		$keyword_manager = new \MeowSEO\Modules\Keywords\Keyword_Manager( $this->options );

		try {
			// Update primary keyword if provided.
			if ( null !== $primary ) {
				$result = $keyword_manager->set_primary_keyword( $post_id, $primary );
				if ( ! $result ) {
					return new \WP_REST_Response(
						array(
							'success' => false,
							'message' => __( 'Failed to update primary keyword.', 'meowseo' ),
							'code'    => 'update_failed',
						),
						500
					);
				}
			}

			// Update secondary keywords if provided.
			if ( null !== $secondary && is_array( $secondary ) ) {
				// Validate keyword count (Requirement 2.2).
				$current_keywords = $keyword_manager->get_keywords( $post_id );
				$primary_count = ! empty( $current_keywords['primary'] ) ? 1 : 0;
				$total_count = $primary_count + count( $secondary );

				if ( $total_count > 5 ) {
					return new \WP_REST_Response(
						array(
							'success' => false,
							'message' => __( 'Maximum of 5 keywords allowed (1 primary + 4 secondary).', 'meowseo' ),
							'code'    => 'keyword_count_exceeded',
						),
						400
					);
				}

				// Update secondary keywords by replacing the entire array.
				update_post_meta( $post_id, '_meowseo_secondary_keywords', wp_json_encode( $secondary ) );
			}

			// Trigger analysis on keyword change (Requirement 2.9).
			$post = get_post( $post_id );
			if ( $post ) {
				$keyword_analyzer = new \MeowSEO\Modules\Keywords\Keyword_Analyzer( $keyword_manager );
				
				// Get post content and context.
				$content = $post->post_content;
				$context = array(
					'title'       => get_post_meta( $post_id, '_meowseo_title', true ) ?: $post->post_title,
					'description' => get_post_meta( $post_id, '_meowseo_description', true ) ?: '',
					'slug'        => $post->post_name,
				);

				// Run analysis for all keywords.
				$analysis_results = $keyword_analyzer->analyze_all_keywords( $post_id, $content, $context );

				// Return updated analysis results (Requirement 2.9).
				$response = new \WP_REST_Response(
					array(
						'success'  => true,
						'message'  => __( 'Keywords updated successfully.', 'meowseo' ),
						'keywords' => $keyword_manager->get_keywords( $post_id ),
						'analysis' => $analysis_results,
					),
					200
				);

				// No cache for mutations.
				$response->header( 'Cache-Control', 'no-store' );

				return $response;
			}

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post not found.', 'meowseo' ),
					'code'    => 'post_not_found',
				),
				404
			);
		} catch ( \Exception $e ) {
			// Log the error with context.
			\MeowSEO\Helpers\Logger::error(
				'Failed to update keywords',
				array(
					'endpoint'  => 'keywords/update',
					'user_id'   => get_current_user_id(),
					'post_id'   => $post_id,
					'error_msg' => $e->getMessage(),
				)
			);

			// Return user-friendly error message.
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'An error occurred while updating keywords. Please try again.', 'meowseo' ),
					'code'    => 'database_error',
				),
				500
			);
		}
	}

	/**
	 * Permission callback for GET meta requests
	 *
	 * Requirement: 13.2, 29.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool|\WP_Error True if user has permission, WP_Error otherwise.
	 */
	public function get_meta_permission( \WP_REST_Request $request ) {
		$post_id = (int) $request['post_id'];
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error(
				'rest_post_not_found',
				__( 'Post not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		// Allow public access to publicly viewable posts.
		if ( ! is_post_publicly_viewable( $post ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have sufficient permissions to access this post.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Permission callback for POST meta requests
	 *
	 * Requirement: 13.1, 15.3, 29.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool|\WP_Error True if user has permission, WP_Error otherwise.
	 */
	public function update_meta_permission( \WP_REST_Request $request ) {
		$post_id = (int) $request['post_id'];

		// Verify user can edit this post (Requirement 15.3, 29.2).
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have sufficient permissions to edit this post.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Permission callback for settings endpoints
	 *
	 * Requirements: 29.1
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool|\WP_Error True if user has permission, WP_Error otherwise.
	 */
	public function manage_options_permission( \WP_REST_Request $request ) {
		// Verify user has manage_options capability (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have sufficient permissions to access this endpoint.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Permission callback for dashboard widget endpoints
	 *
	 * Requirements: 3.2, 3.3, 3.5, 3.6, 28.3, 29.4
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool|\WP_Error True if user has permission, WP_Error otherwise.
	 */
	public function dashboard_permission( \WP_REST_Request $request ) {
		// Verify user has manage_options capability (Requirement 3.2, 29.4).
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have sufficient permissions to access this endpoint.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Permission callback for edit_posts endpoints
	 *
	 * Requirements: 15.2, 15.3, 29.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool|\WP_Error True if user has permission, WP_Error otherwise.
	 */
	public function edit_posts_permission( \WP_REST_Request $request ) {
		// Verify user has edit_posts capability (Requirement 15.2, 15.3, 29.2).
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have sufficient permissions to access this endpoint.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Validate post ID
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return bool True if valid.
	 */
	public function validate_post_id( int $post_id ): bool {
		return get_post( $post_id ) !== null;
	}

	/**
	 * Verify nonce from request
	 *
	 * Requirement: 15.2
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
	 * Get settings schema for validation
	 *
	 * Defines validation rules for all settings.
	 * Requirement: 2.3
	 *
	 * @since 1.0.0
	 * @return array Settings schema.
	 */
	private function get_settings_schema(): array {
		$schema = array(
			'enabled_modules' => array(
				'type'              => 'array',
				'items'             => array(
					'type' => 'string',
					'enum' => array(
						'meta',
						'schema',
						'sitemap',
						'redirects',
						'monitor_404',
						'internal_links',
						'gsc',
						'social',
						'woocommerce',
						'ai',
						'import',
						'image_seo',
						'indexnow',
						'roles',
						'multilingual',
						'multisite',
						'locations',
						'bulk',
						'analytics',
						'admin-bar',
						'orphaned',
						'synonyms',
					),
				),
				'sanitize_callback' => array( $this, 'sanitize_enabled_modules' ),
			),
			'separator' => array(
				'type'              => 'string',
				'enum'              => array( '|', '-', '–', '—', '·', '•' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'default_social_image' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'delete_on_uninstall' => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'has_regex_rules' => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			// Schema settings (Requirement 19.1).
			'meowseo_schema_organization_name' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'meowseo_schema_organization_logo' => array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			),
			'meowseo_schema_organization_logo_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'meowseo_schema_social_profiles' => array(
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'sanitize_social_profiles' ),
			),
			// Sitemap settings (Requirement 19.1).
			'meowseo_sitemap_enabled' => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'meowseo_sitemap_post_types' => array(
				'type'              => 'array',
				'items'             => array(
					'type' => 'string',
				),
				'sanitize_callback' => array( $this, 'sanitize_post_types' ),
			),
			'meowseo_sitemap_news_enabled' => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'meowseo_sitemap_video_enabled' => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'meowseo_sitemap_max_urls' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'meowseo_sitemap_cache_ttl' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		);

		// Add WooCommerce-specific settings if WooCommerce is active (Requirement 2.5).
		if ( class_exists( 'WooCommerce' ) ) {
			$schema['woocommerce_exclude_out_of_stock'] = array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			);
		}

		return $schema;
	}

	/**
	 * Validate settings
	 *
	 * Validates all settings against schema.
	 * Requirement: 2.3
	 *
	 * @since 1.0.0
	 * @param array $settings Settings to validate.
	 * @return array|\WP_Error Validated settings or error.
	 */
	private function validate_settings( array $settings ) {
		$schema = $this->get_settings_schema();
		$validated = array();

		foreach ( $settings as $key => $value ) {
			// Skip unknown settings.
			if ( ! isset( $schema[ $key ] ) ) {
				continue;
			}

			$field_schema = $schema[ $key ];

			// Validate type.
			if ( isset( $field_schema['type'] ) ) {
				$valid = $this->validate_type( $value, $field_schema['type'] );
				if ( ! $valid ) {
					return new \WP_Error(
						'invalid_type',
						sprintf(
							/* translators: %s: setting key */
							__( 'Invalid type for setting: %s', 'meowseo' ),
							$key
						)
					);
				}
			}

			// Validate enum.
			if ( isset( $field_schema['enum'] ) && ! in_array( $value, $field_schema['enum'], true ) ) {
				return new \WP_Error(
					'invalid_enum',
					sprintf(
						/* translators: %s: setting key */
						__( 'Invalid value for setting: %s', 'meowseo' ),
						$key
					)
				);
			}

			// Validate array items.
			if ( 'array' === $field_schema['type'] && isset( $field_schema['items'] ) ) {
				foreach ( $value as $item ) {
					if ( isset( $field_schema['items']['enum'] ) && ! in_array( $item, $field_schema['items']['enum'], true ) ) {
						return new \WP_Error(
							'invalid_array_item',
							sprintf(
								/* translators: %s: setting key */
								__( 'Invalid array item for setting: %s', 'meowseo' ),
								$key
							)
						);
					}
				}
			}

			// Sanitize value.
			if ( isset( $field_schema['sanitize_callback'] ) && is_callable( $field_schema['sanitize_callback'] ) ) {
				$value = call_user_func( $field_schema['sanitize_callback'], $value );
			}

			$validated[ $key ] = $value;
		}

		return $validated;
	}

	/**
	 * Validate type
	 *
	 * @since 1.0.0
	 * @param mixed  $value Value to validate.
	 * @param string $type  Expected type.
	 * @return bool True if valid.
	 */
	private function validate_type( $value, string $type ): bool {
		switch ( $type ) {
			case 'string':
				return is_string( $value );
			case 'integer':
				return is_int( $value );
			case 'boolean':
				return is_bool( $value );
			case 'array':
				return is_array( $value );
			default:
				return false;
		}
	}

	/**
	 * Sanitize enabled modules
	 *
	 * Ensures only valid module IDs are enabled.
	 * Requirement: 2.3
	 *
	 * @since 1.0.0
	 * @param array $modules Module IDs.
	 * @return array Sanitized module IDs.
	 */
	public function sanitize_enabled_modules( $modules ): array {
		if ( ! is_array( $modules ) ) {
			return array();
		}

		$valid_modules = array(
			'meta',
			'schema',
			'sitemap',
			'redirects',
			'monitor_404',
			'internal_links',
			'gsc',
			'social',
			'woocommerce',
			'ai',
			'import',
			'image_seo',
			'indexnow',
			'roles',
			'multilingual',
			'multisite',
			'locations',
			'bulk',
			'analytics',
			'admin-bar',
			'orphaned',
			'synonyms',
		);

		return array_values( array_intersect( $modules, $valid_modules ) );
	}

	/**
	 * Sanitize social profiles
	 *
	 * Security: Sanitizes all user input (Requirement 19.2).
	 *
	 * @since 1.0.0
	 * @param array|object $profiles Social profile URLs.
	 * @return array Sanitized social profiles.
	 */
	public function sanitize_social_profiles( $profiles ): array {
		if ( is_object( $profiles ) ) {
			$profiles = (array) $profiles;
		}

		if ( ! is_array( $profiles ) ) {
			return array();
		}

		$sanitized = array();
		$valid_keys = array( 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube' );

		foreach ( $profiles as $key => $url ) {
			$key = sanitize_key( $key );
			
			// Only allow valid social network keys.
			if ( ! in_array( $key, $valid_keys, true ) ) {
				continue;
			}

			// Sanitize URL.
			$url = esc_url_raw( $url );
			if ( ! empty( $url ) ) {
				$sanitized[ $key ] = $url;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize post types array
	 *
	 * Security: Validates post types exist (Requirement 19.2).
	 *
	 * @since 1.0.0
	 * @param array $post_types Post type names.
	 * @return array Sanitized post types.
	 */
	public function sanitize_post_types( $post_types ): array {
		if ( ! is_array( $post_types ) ) {
			return array();
		}

		$sanitized = array();
		$valid_post_types = get_post_types( array( 'public' => true ), 'names' );

		foreach ( $post_types as $post_type ) {
			$post_type = sanitize_key( $post_type );
			
			// Only allow valid public post types.
			if ( isset( $valid_post_types[ $post_type ] ) ) {
				$sanitized[] = $post_type;
			}
		}

		return array_values( $sanitized );
	}

	/**
	 * Sanitize keyword array
	 *
	 * Security: Sanitizes keyword strings and removes empty values.
	 * Requirements: 2.1, 2.2
	 *
	 * @since 1.0.0
	 * @param array $keywords Keyword array.
	 * @return array Sanitized keywords.
	 */
	public function sanitize_keyword_array( $keywords ): array {
		if ( ! is_array( $keywords ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $keywords as $keyword ) {
			$keyword = sanitize_text_field( $keyword );
			$keyword = trim( $keyword );
			
			// Only include non-empty keywords.
			if ( ! empty( $keyword ) ) {
				$sanitized[] = $keyword;
			}
		}

		return array_values( $sanitized );
	}

	/**
	 * Register public SEO endpoints
	 *
	 * Provides public access to SEO data for headless deployments.
	 * Requirements: 17.1-17.7, 18.1-18.6, 27.1-27.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_public_seo_routes(): void {
		// GET /seo/post/{id} - Get all SEO data for a post (Requirement 17.1).
		register_rest_route(
			self::NAMESPACE,
			'/seo/post/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_seo_data_by_post_id' ),
				'permission_callback' => array( $this, 'public_seo_permission' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET /seo?url={url} - Get SEO data by URL (Requirement 17.2).
		register_rest_route(
			self::NAMESPACE,
			'/seo',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_seo_data_by_url' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'url' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => array( $this, 'validate_url' ),
					),
				),
			)
		);

		// GET /schema/post/{id} - Get schema @graph array (Requirement 17.3).
		register_rest_route(
			self::NAMESPACE,
			'/schema/post/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_schema_by_post_id' ),
				'permission_callback' => array( $this, 'public_seo_permission' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET /breadcrumbs?url={url} - Get breadcrumb trail (Requirement 17.4).
		register_rest_route(
			self::NAMESPACE,
			'/breadcrumbs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_breadcrumbs_by_url' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'url' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => array( $this, 'validate_url' ),
					),
				),
			)
		);

		// GET /redirects/check?url={url} - Check for redirects (Requirement 17.5).
		register_rest_route(
			self::NAMESPACE,
			'/redirects/check',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'check_redirect_by_url' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'url' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => array( $this, 'validate_url' ),
					),
				),
			)
		);
	}

	/**
	 * Get SEO data by post ID
	 *
	 * Returns all SEO data for a post including meta, social, and schema.
	 * Requirements: 17.1, 18.1, 18.2, 27.1, 27.2, 27.3
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_seo_data_by_post_id( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];
		$post = get_post( $post_id );

		// Return 404 for non-existent posts (Requirement 17.7).
		if ( ! $post ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post not found.', 'meowseo' ),
					'code'    => 'post_not_found',
				),
				404
			);
		}

		// Return 404 for unpublished posts (Requirement 17.7).
		if ( 'publish' !== $post->post_status ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post not found.', 'meowseo' ),
					'code'    => 'post_not_found',
				),
				404
			);
		}

		// Build SEO data response (Requirement 18.1).
		$data = $this->build_seo_response( $post_id );

		// Generate ETag from response content (Requirement 27.2).
		$etag = md5( wp_json_encode( $data ) );

		// Check If-None-Match header (Requirement 27.3).
		$if_none_match = $request->get_header( 'If-None-Match' );
		if ( $if_none_match === $etag ) {
			// Return 304 Not Modified (Requirement 27.3).
			$response = new \WP_REST_Response( null, 304 );
			$response->header( 'ETag', $etag );
			return $response;
		}

		$response = new \WP_REST_Response( $data, 200 );

		// Add cache headers (Requirements 27.1, 27.2, 27.4).
		$response->header( 'Cache-Control', 'public, max-age=300' );
		$response->header( 'ETag', $etag );
		$response->header( 'Vary', 'Accept' );

		return $response;
	}

	/**
	 * Get SEO data by URL
	 *
	 * Resolves URL to post and returns SEO data.
	 * Requirements: 17.2, 18.1, 18.2, 27.1, 27.2, 27.3
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_seo_data_by_url( \WP_REST_Request $request ): \WP_REST_Response {
		$url = $request->get_param( 'url' );

		if ( empty( $url ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'URL parameter is required.', 'meowseo' ),
					'code'    => 'missing_url',
				),
				400
			);
		}

		// Resolve URL to post ID.
		$post_id = url_to_postid( $url );

		if ( ! $post_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post not found for URL.', 'meowseo' ),
					'code'    => 'post_not_found',
				),
				404
			);
		}

		// Get post and verify it's published.
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post not found.', 'meowseo' ),
					'code'    => 'post_not_found',
				),
				404
			);
		}

		// Build SEO data response.
		$data = $this->build_seo_response( $post_id );

		// Generate ETag from response content.
		$etag = md5( wp_json_encode( $data ) );

		// Check If-None-Match header.
		$if_none_match = $request->get_header( 'If-None-Match' );
		if ( $if_none_match === $etag ) {
			$response = new \WP_REST_Response( null, 304 );
			$response->header( 'ETag', $etag );
			return $response;
		}

		$response = new \WP_REST_Response( $data, 200 );

		// Add cache headers.
		$response->header( 'Cache-Control', 'public, max-age=300' );
		$response->header( 'ETag', $etag );
		$response->header( 'Vary', 'Accept' );

		return $response;
	}

	/**
	 * Get schema by post ID
	 *
	 * Returns only the schema @graph array.
	 * Requirements: 17.3, 18.2, 27.1, 27.2, 27.3
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_schema_by_post_id( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];
		$post = get_post( $post_id );

		// Return 404 for non-existent or unpublished posts.
		if ( ! $post || 'publish' !== $post->post_status ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post not found.', 'meowseo' ),
					'code'    => 'post_not_found',
				),
				404
			);
		}

		// Get schema module.
		$schema_module = $this->module_manager->get_module( 'schema' );

		if ( ! $schema_module ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Schema module not available.', 'meowseo' ),
					'code'    => 'schema_unavailable',
				),
				400
			);
		}

		// Get schema JSON and parse it.
		$schema_json = $schema_module->get_schema_json( $post_id );
		$schema_data = ! empty( $schema_json ) ? json_decode( $schema_json, true ) : array();

		// Extract @graph array if present.
		$graph = isset( $schema_data['@graph'] ) ? $schema_data['@graph'] : array();

		// Generate ETag.
		$etag = md5( wp_json_encode( $graph ) );

		// Check If-None-Match header.
		$if_none_match = $request->get_header( 'If-None-Match' );
		if ( $if_none_match === $etag ) {
			$response = new \WP_REST_Response( null, 304 );
			$response->header( 'ETag', $etag );
			return $response;
		}

		$response = new \WP_REST_Response( $graph, 200 );

		// Add cache headers.
		$response->header( 'Cache-Control', 'public, max-age=300' );
		$response->header( 'ETag', $etag );
		$response->header( 'Vary', 'Accept' );

		return $response;
	}

	/**
	 * Get breadcrumbs by URL
	 *
	 * Returns breadcrumb trail for a URL.
	 * Requirements: 17.4, 27.1, 27.2, 27.3
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_breadcrumbs_by_url( \WP_REST_Request $request ): \WP_REST_Response {
		$url = $request->get_param( 'url' );

		if ( empty( $url ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'URL parameter is required.', 'meowseo' ),
					'code'    => 'missing_url',
				),
				400
			);
		}

		// Get breadcrumbs helper.
		$breadcrumbs = new \MeowSEO\Helpers\Breadcrumbs( $this->options );

		// Build breadcrumb trail for URL.
		// Note: Breadcrumbs class uses global query, so we need to simulate the URL context.
		$trail = $breadcrumbs->get_trail();

		// Generate ETag.
		$etag = md5( wp_json_encode( $trail ) );

		// Check If-None-Match header.
		$if_none_match = $request->get_header( 'If-None-Match' );
		if ( $if_none_match === $etag ) {
			$response = new \WP_REST_Response( null, 304 );
			$response->header( 'ETag', $etag );
			return $response;
		}

		$response = new \WP_REST_Response( $trail, 200 );

		// Add cache headers.
		$response->header( 'Cache-Control', 'public, max-age=300' );
		$response->header( 'ETag', $etag );
		$response->header( 'Vary', 'Accept' );

		return $response;
	}

	/**
	 * Check for redirects by URL
	 *
	 * Checks if a URL has a redirect configured.
	 * Requirements: 17.5, 27.1, 27.2, 27.3
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function check_redirect_by_url( \WP_REST_Request $request ): \WP_REST_Response {
		$url = $request->get_param( 'url' );

		if ( empty( $url ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'URL parameter is required.', 'meowseo' ),
					'code'    => 'missing_url',
				),
				400
			);
		}

		// Check for exact redirect match.
		$redirect = \MeowSEO\Helpers\DB::get_redirect_exact( $url );

		$data = array(
			'url'        => $url,
			'has_redirect' => ! empty( $redirect ),
			'redirect'   => $redirect ? array(
				'target_url' => $redirect['target_url'] ?? '',
				'status_code' => $redirect['status_code'] ?? 301,
				'is_regex'   => $redirect['is_regex'] ?? false,
			) : null,
		);

		// Generate ETag.
		$etag = md5( wp_json_encode( $data ) );

		// Check If-None-Match header.
		$if_none_match = $request->get_header( 'If-None-Match' );
		if ( $if_none_match === $etag ) {
			$response = new \WP_REST_Response( null, 304 );
			$response->header( 'ETag', $etag );
			return $response;
		}

		$response = new \WP_REST_Response( $data, 200 );

		// Add cache headers.
		$response->header( 'Cache-Control', 'public, max-age=300' );
		$response->header( 'ETag', $etag );
		$response->header( 'Vary', 'Accept' );

		return $response;
	}

	/**
	 * Build SEO response data
	 *
	 * Constructs the complete SEO data response with all fields.
	 * Requirements: 18.1, 18.2
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array SEO response data.
	 */
	private function build_seo_response( int $post_id ): array {
		$data = array(
			'post_id' => $post_id,
		);

		// Get meta module for SEO data.
		$meta_module = $this->module_manager->get_module( 'meta' );
		if ( $meta_module ) {
			$data['title']       = $meta_module->get_title( $post_id );
			$data['description'] = $meta_module->get_description( $post_id );
			$data['robots']      = $meta_module->get_robots( $post_id );
			$data['canonical']   = $meta_module->get_canonical( $post_id );
		} else {
			$data['title']       = '';
			$data['description'] = '';
			$data['robots']      = '';
			$data['canonical']   = '';
		}

		// Get social module for social data.
		$social_module = $this->module_manager->get_module( 'social' );
		if ( $social_module ) {
			$social_data = $social_module->get_social_data( $post_id );
			$data['og_title']       = $social_data['title'] ?? '';
			$data['og_description'] = $social_data['description'] ?? '';
			$data['og_image']       = $social_data['image'] ?? '';
			$data['twitter_card']   = 'summary_large_image';
			$data['twitter_title']  = $social_data['title'] ?? '';
			$data['twitter_description'] = $social_data['description'] ?? '';
			$data['twitter_image']  = $social_data['image'] ?? '';
		} else {
			$data['og_title']       = '';
			$data['og_description'] = '';
			$data['og_image']       = '';
			$data['twitter_card']   = '';
			$data['twitter_title']  = '';
			$data['twitter_description'] = '';
			$data['twitter_image']  = '';
		}

		// Get schema module for schema data (Requirement 18.2 - parse as JSON object).
		$schema_module = $this->module_manager->get_module( 'schema' );
		if ( $schema_module ) {
			$schema_json = $schema_module->get_schema_json( $post_id );
			$data['schema_json'] = ! empty( $schema_json ) ? json_decode( $schema_json, true ) : array();
		} else {
			$data['schema_json'] = array();
		}

		return $data;
	}

	/**
	 * Register Sprint 4 REST API routes
	 *
	 * Provides endpoints for bulk operations, orphaned content, and AI suggestions.
	 * Task 16.3 - Requirements: 1.9, 5.1, 8.4, 10.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_sprint4_routes(): void {
		// Bulk operations endpoint (Requirement 5.1).
		register_rest_route(
			self::NAMESPACE,
			'/bulk/operations',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_bulk_operation' ),
				'permission_callback' => array( $this, 'bulk_edit_permission' ),
				'args'                => array(
					'action'   => array(
						'required'          => true,
						'type'              => 'string',
						'enum'              => array( 'set_noindex', 'set_index', 'set_nofollow', 'set_follow', 'remove_canonical', 'set_schema_article', 'set_schema_none' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'post_ids' => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array(
							'type' => 'integer',
						),
						'sanitize_callback' => array( $this, 'sanitize_post_ids' ),
					),
				),
			)
		);

		// CSV export endpoint (Requirement 5.1).
		register_rest_route(
			self::NAMESPACE,
			'/bulk/export',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'export_bulk_csv' ),
				'permission_callback' => array( $this, 'bulk_edit_permission' ),
				'args'                => array(
					'post_ids' => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array(
							'type' => 'integer',
						),
						'sanitize_callback' => array( $this, 'sanitize_post_ids' ),
					),
				),
			)
		);

		// Orphaned content list endpoint (Requirement 8.4).
		register_rest_route(
			self::NAMESPACE,
			'/orphaned/list',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_orphaned_content' ),
				'permission_callback' => array( $this, 'edit_posts_permission' ),
				'args'                => array(
					'post_type' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'per_page'  => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 20,
						'sanitize_callback' => 'absint',
					),
					'page'      => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Orphaned content suggestions endpoint (Requirement 8.4).
		register_rest_route(
			self::NAMESPACE,
			'/orphaned/suggestions/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_orphaned_suggestions' ),
				'permission_callback' => array( $this, 'edit_posts_permission' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// AI optimizer suggestion endpoint (Requirement 10.1).
		register_rest_route(
			self::NAMESPACE,
			'/ai/suggestion',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_ai_suggestion' ),
				'permission_callback' => array( $this, 'ai_optimizer_permission' ),
				'args'                => array(
					'post_id'    => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'check_name' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'content'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'wp_kses_post',
					),
					'keyword'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Handle bulk operation
	 *
	 * Applies bulk SEO operation to multiple posts.
	 * Requirement: 5.1
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function handle_bulk_operation( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce.
		if ( ! $this->verify_nonce( $request ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		$action   = $request->get_param( 'action' );
		$post_ids = $request->get_param( 'post_ids' );

		// Get bulk editor module.
		$bulk_module = $this->module_manager->get_module( 'bulk' );

		if ( ! $bulk_module ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Bulk editor module not available.', 'meowseo' ),
					'code'    => 'module_unavailable',
				),
				400
			);
		}

		try {
			$count = $bulk_module->handle_bulk_action( $action, $post_ids );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => sprintf(
						/* translators: %d: number of posts modified */
						__( '%d posts modified successfully.', 'meowseo' ),
						$count
					),
					'count'   => $count,
				),
				200
			);
		} catch ( \Exception $e ) {
			\MeowSEO\Helpers\Logger::error(
				'Bulk operation failed',
				array(
					'action'   => $action,
					'post_ids' => $post_ids,
					'error'    => $e->getMessage(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Bulk operation failed. Please try again.', 'meowseo' ),
					'code'    => 'bulk_operation_failed',
				),
				500
			);
		}
	}

	/**
	 * Export bulk CSV
	 *
	 * Exports SEO data for selected posts to CSV.
	 * Requirement: 5.1
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function export_bulk_csv( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce.
		if ( ! $this->verify_nonce( $request ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		$post_ids = $request->get_param( 'post_ids' );

		// Get bulk editor module.
		$bulk_module = $this->module_manager->get_module( 'bulk' );

		if ( ! $bulk_module ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Bulk editor module not available.', 'meowseo' ),
					'code'    => 'module_unavailable',
				),
				400
			);
		}

		try {
			$csv_content = $bulk_module->export_to_csv( $post_ids );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'csv'     => $csv_content,
				),
				200
			);
		} catch ( \Exception $e ) {
			\MeowSEO\Helpers\Logger::error(
				'CSV export failed',
				array(
					'post_ids' => $post_ids,
					'error'    => $e->getMessage(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'CSV export failed. Please try again.', 'meowseo' ),
					'code'    => 'csv_export_failed',
				),
				500
			);
		}
	}

	/**
	 * Get orphaned content
	 *
	 * Returns list of orphaned posts with no internal links.
	 * Requirement: 8.4
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_orphaned_content( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce.
		if ( ! $this->verify_nonce( $request ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		$post_type = $request->get_param( 'post_type' );
		$per_page  = $request->get_param( 'per_page' );
		$page      = $request->get_param( 'page' );

		// Get orphaned detector module.
		$orphaned_module = $this->module_manager->get_module( 'orphaned' );

		if ( ! $orphaned_module ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Orphaned content module not available.', 'meowseo' ),
					'code'    => 'module_unavailable',
				),
				400
			);
		}

		try {
			$filters = array();
			if ( $post_type ) {
				$filters['post_type'] = $post_type;
			}
			if ( $per_page ) {
				$filters['per_page'] = $per_page;
			}
			if ( $page ) {
				$filters['page'] = $page;
			}

			$orphaned_posts = $orphaned_module->get_orphaned_posts( $filters );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'posts'   => $orphaned_posts,
				),
				200
			);
		} catch ( \Exception $e ) {
			\MeowSEO\Helpers\Logger::error(
				'Failed to retrieve orphaned content',
				array(
					'filters' => $filters,
					'error'   => $e->getMessage(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to retrieve orphaned content. Please try again.', 'meowseo' ),
					'code'    => 'orphaned_content_failed',
				),
				500
			);
		}
	}

	/**
	 * Get orphaned suggestions
	 *
	 * Returns linking suggestions for an orphaned post.
	 * Requirement: 8.4
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_orphaned_suggestions( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce.
		if ( ! $this->verify_nonce( $request ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		$post_id = (int) $request['post_id'];

		// Get orphaned detector module.
		$orphaned_module = $this->module_manager->get_module( 'orphaned' );

		if ( ! $orphaned_module ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Orphaned content module not available.', 'meowseo' ),
					'code'    => 'module_unavailable',
				),
				400
			);
		}

		try {
			$suggestions = $orphaned_module->suggest_linking_opportunities( $post_id );

			return new \WP_REST_Response(
				array(
					'success'     => true,
					'suggestions' => $suggestions,
				),
				200
			);
		} catch ( \Exception $e ) {
			\MeowSEO\Helpers\Logger::error(
				'Failed to retrieve orphaned suggestions',
				array(
					'post_id' => $post_id,
					'error'   => $e->getMessage(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to retrieve suggestions. Please try again.', 'meowseo' ),
					'code'    => 'orphaned_suggestions_failed',
				),
				500
			);
		}
	}

	/**
	 * Get AI suggestion
	 *
	 * Returns AI-powered suggestion for fixing a failing SEO check.
	 * Requirement: 10.1
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_ai_suggestion( \WP_REST_Request $request ): \WP_REST_Response {
		// Verify nonce.
		if ( ! $this->verify_nonce( $request ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed.', 'meowseo' ),
					'code'    => 'rest_invalid_nonce',
				),
				403
			);
		}

		$post_id    = (int) $request->get_param( 'post_id' );
		$check_name = $request->get_param( 'check_name' );
		$content    = $request->get_param( 'content' );
		$keyword    = $request->get_param( 'keyword' );

		// Get AI module.
		$ai_module = $this->module_manager->get_module( 'ai' );

		if ( ! $ai_module ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'AI module not available.', 'meowseo' ),
					'code'    => 'module_unavailable',
				),
				400
			);
		}

		try {
			// Check if AI optimizer is available.
			if ( ! method_exists( $ai_module, 'get_optimizer' ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'AI optimizer not available.', 'meowseo' ),
						'code'    => 'optimizer_unavailable',
					),
					400
				);
			}

			$optimizer  = $ai_module->get_optimizer();
			$suggestion = $optimizer->get_suggestion( $check_name, $content, $keyword );

			return new \WP_REST_Response(
				array(
					'success'    => true,
					'suggestion' => $suggestion,
				),
				200
			);
		} catch ( \Exception $e ) {
			\MeowSEO\Helpers\Logger::error(
				'Failed to retrieve AI suggestion',
				array(
					'post_id'    => $post_id,
					'check_name' => $check_name,
					'error'      => $e->getMessage(),
				)
			);

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to retrieve AI suggestion. Please try again.', 'meowseo' ),
					'code'    => 'ai_suggestion_failed',
				),
				500
			);
		}
	}

	/**
	 * Permission callback for bulk edit endpoints
	 *
	 * Requirement: 1.9
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool|\WP_Error True if user has permission, WP_Error otherwise.
	 */
	public function bulk_edit_permission( \WP_REST_Request $request ) {
		// Check if roles module is active.
		$roles_module = $this->module_manager->get_module( 'roles' );

		if ( $roles_module && method_exists( $roles_module, 'user_can' ) ) {
			// Use role-based capability check.
			if ( ! $roles_module->user_can( 'meowseo_bulk_edit' ) ) {
				return new \WP_Error(
					'rest_forbidden',
					__( 'You do not have sufficient permissions to perform bulk operations.', 'meowseo' ),
					array( 'status' => 403 )
				);
			}
		} else {
			// Fallback to edit_posts capability.
			if ( ! current_user_can( 'edit_posts' ) ) {
				return new \WP_Error(
					'rest_forbidden',
					__( 'You do not have sufficient permissions to perform bulk operations.', 'meowseo' ),
					array( 'status' => 403 )
				);
			}
		}

		return true;
	}

	/**
	 * Permission callback for AI optimizer endpoints
	 *
	 * Requirement: 1.9
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool|\WP_Error True if user has permission, WP_Error otherwise.
	 */
	public function ai_optimizer_permission( \WP_REST_Request $request ) {
		// Check if roles module is active.
		$roles_module = $this->module_manager->get_module( 'roles' );

		if ( $roles_module && method_exists( $roles_module, 'user_can' ) ) {
			// Use role-based capability check.
			if ( ! $roles_module->user_can( 'meowseo_use_ai_optimizer' ) ) {
				return new \WP_Error(
					'rest_forbidden',
					__( 'You do not have sufficient permissions to use AI optimizer.', 'meowseo' ),
					array( 'status' => 403 )
				);
			}
		} else {
			// Fallback to edit_posts capability.
			if ( ! current_user_can( 'edit_posts' ) ) {
				return new \WP_Error(
					'rest_forbidden',
					__( 'You do not have sufficient permissions to use AI optimizer.', 'meowseo' ),
					array( 'status' => 403 )
				);
			}
		}

		return true;
	}

	/**
	 * Sanitize post IDs array
	 *
	 * @since 1.0.0
	 * @param array $post_ids Post IDs.
	 * @return array Sanitized post IDs.
	 */
	public function sanitize_post_ids( $post_ids ): array {
		if ( ! is_array( $post_ids ) ) {
			return array();
		}

		return array_map( 'absint', $post_ids );
	}

	/**
	 * Permission callback for public SEO endpoints
	 *
	 * Allows public access to published posts only.
	 * Requirements: 17.6, 17.7, 29.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool|\WP_Error True if user has permission, WP_Error otherwise.
	 */
	public function public_seo_permission( \WP_REST_Request $request ) {
		$post_id = (int) $request['post_id'];
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error(
				'rest_post_not_found',
				__( 'Post not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		// Allow public access to published posts.
		if ( 'publish' !== $post->post_status || ! is_post_publicly_viewable( $post ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have sufficient permissions to access this post.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Validate URL parameter
	 *
	 * @since 1.0.0
	 * @param string $url URL to validate.
	 * @return bool True if valid URL.
	 */
	public function validate_url( string $url ): bool {
		return ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL );
	}
}
