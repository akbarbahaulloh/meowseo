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
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function update_settings( \WP_REST_Request $request ): \WP_REST_Response {
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

		// Update settings (excluding sensitive fields).
		foreach ( $settings as $key => $value ) {
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
	 * Permission callback for GET meta requests
	 *
	 * Requirement: 13.2
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool True if user has permission.
	 */
	public function get_meta_permission( \WP_REST_Request $request ): bool {
		$post_id = (int) $request['post_id'];
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		// Allow public access to publicly viewable posts.
		return is_post_publicly_viewable( $post );
	}

	/**
	 * Permission callback for POST meta requests
	 *
	 * Requirement: 13.1, 15.3
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool True if user has permission.
	 */
	public function update_meta_permission( \WP_REST_Request $request ): bool {
		$post_id = (int) $request['post_id'];

		// Verify user can edit this post (Requirement 15.3).
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Permission callback for settings endpoints
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool True if user has permission.
	 */
	public function manage_options_permission( \WP_REST_Request $request ): bool {
		// Verify user has manage_options capability (Requirement 15.3).
		return current_user_can( 'manage_options' );
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
}
