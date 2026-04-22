<?php
/**
 * AI REST API
 *
 * Provides REST endpoints for AI generation functionality.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\AI
 */

namespace MeowSEO\Modules\AI;

use MeowSEO\Helpers\Logger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * AI REST API class
 *
 * Handles REST endpoint registration and request processing for AI generation.
 * Requirements: 28.1, 28.2, 28.3, 28.4, 28.5, 28.6, 28.8, 25.1, 25.2, 25.3, 25.4, 25.5, 25.6, 26.1, 26.2, 26.3, 26.4, 26.5
 */
class AI_REST {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'meowseo/v1';

	/**
	 * Generator instance.
	 *
	 * @var AI_Generator
	 */
	private AI_Generator $generator;

	/**
	 * Provider Manager instance.
	 *
	 * @var AI_Provider_Manager
	 */
	private AI_Provider_Manager $provider_manager;

	/**
	 * AI Optimizer instance.
	 *
	 * @var AI_Optimizer
	 */
	private AI_Optimizer $optimizer;

	/**
	 * Valid provider slugs.
	 *
	 * @var array
	 */
	private array $valid_providers = array( 'gemini', 'openai', 'anthropic', 'imagen', 'dalle', 'deepseek', 'glm', 'qwen' );

	/**
	 * Valid generation types.
	 *
	 * @var array
	 */
	private array $valid_types = array( 'text', 'image', 'all' );

	/**
	 * Constructor.
	 *
	 * @param AI_Generator        $generator         Generator instance.
	 * @param AI_Provider_Manager $provider_manager  Provider Manager instance.
	 * @param AI_Optimizer        $optimizer         AI Optimizer instance.
	 */
	public function __construct( AI_Generator $generator, AI_Provider_Manager $provider_manager, AI_Optimizer $optimizer ) {
		$this->generator         = $generator;
		$this->provider_manager  = $provider_manager;
		$this->optimizer         = $optimizer;
	}

	/**
	 * Register REST API routes.
	 *
	 * Requirements: 28.1, 25.1, 25.3, 25.4
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// POST /meowseo/v1/ai/generate - Generate SEO metadata (Requirement 28.1, 28.2, 28.3, 28.4, 28.5, 28.6, 28.8).
		register_rest_route(
			self::NAMESPACE,
			'/ai/generate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate' ),
				'permission_callback' => array( $this, 'check_permission_and_nonce' ),
				'args'                => array(
					'post_id'        => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'type'           => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'bypass_cache'   => array(
						'type'              => 'boolean',
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'profile_id'     => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'style_id'       => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'image_style_id' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		// POST /meowseo/v1/ai/generate-all - Bulk generate SEO metadata.
		register_rest_route(
			self::NAMESPACE,
			'/ai/generate-all',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_all' ),
				'permission_callback' => array( $this, 'check_permission_and_nonce' ),
				'args'                => array(
					'post_id'  => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'provider' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'profile_id' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'style_id' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'image_style_id' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		// POST /meowseo/v1/ai/generate-image - Generate featured image only (Requirement 9.2, 9.3, 9.4).
		register_rest_route(
			self::NAMESPACE,
			'/ai/generate-image',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_image' ),
				'permission_callback' => array( $this, 'check_permission_and_nonce' ),
				'args'                => array(
					'post_id'       => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'custom_prompt' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'profile_id' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'image_style_id' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		// GET /meowseo/v1/ai/provider-status - Get provider statuses (Requirement 3.1, 3.2, 3.3, 3.4, 3.5, 3.6).
		register_rest_route(
			self::NAMESPACE,
			'/ai/provider-status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_provider_status' ),
				'permission_callback' => array( $this, 'check_manage_options_capability' ),
			)
		);

		// POST /meowseo/v1/ai/apply - Apply generated content to postmeta (Requirement 8.6, 27.1-27.10).
		register_rest_route(
			self::NAMESPACE,
			'/ai/apply',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'apply' ),
				'permission_callback' => array( $this, 'check_permission_and_nonce' ),
				'args'                => array(
					'post_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'content' => array(
						'type'              => 'object',
						'sanitize_callback' => array( $this, 'sanitize_content_object' ),
					),
					'image'   => array(
						'type'              => 'object',
						'sanitize_callback' => array( $this, 'sanitize_image_object' ),
					),
					'fields'  => array(
						'type'              => 'array',
						'sanitize_callback' => array( $this, 'sanitize_fields_array' ),
					),
				),
			)
		);

		// POST /meowseo/v1/ai/test-provider - Test provider connection (Requirement 2.4, 2.5, 2.6, 2.7).
		register_rest_route(
			self::NAMESPACE,
			'/ai/test-provider',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'test_provider' ),
				'permission_callback' => array( $this, 'check_permission_and_nonce_for_settings' ),
				'args'                => array(
					'provider' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'api_key' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'profile_id' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		// POST /meowseo/v1/ai/suggestion - Get AI suggestion for failing SEO check (Requirement 10.1, 10.2, 10.4).
		register_rest_route(
			self::NAMESPACE,
			'/ai/suggestion',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_suggestion' ),
				'permission_callback' => array( $this, 'check_permission_and_nonce' ),
				'args'                => array(
					'post_id'    => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'check_name' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'content'    => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'wp_kses_post',
					),
					'keyword'    => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Generate SEO metadata for a post.
	 *
	 * POST /meowseo/v1/ai/generate
	 * Requirements: 28.1, 28.2, 28.3, 28.4, 28.5, 28.6, 28.8
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function generate( WP_REST_Request $request ) {
		$post_id        = $request->get_param( 'post_id' );
		$type           = $request->get_param( 'type' ) ?: 'all';
		$generate_image = $request->get_param( 'generate_image' ) ?: false;
		$bypass_cache   = $request->get_param( 'bypass_cache' ) ?: false;
		$profile_id     = $request->get_param( 'profile_id' );
		$style_id       = $request->get_param( 'style_id' );
		$image_style_id = $request->get_param( 'image_style_id' );

		$gen_options = array(
			'bypass_cache'   => $bypass_cache,
			'profile_id'     => $profile_id,
			'style_id'       => $style_id,
			'image_style_id' => $image_style_id,
		);

		// Validate post_id as integer (Requirement 28.2).
		if ( ! is_int( $post_id ) || $post_id <= 0 ) {
			return new WP_Error(
				'invalid_post_id',
				__( 'Invalid post ID.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Validate post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		// Validate type against whitelist (Requirement 28.3).
		if ( ! in_array( $type, $this->valid_types, true ) ) {
			return new WP_Error(
				'invalid_type',
				__( 'Invalid generation type. Must be: text, image, or all.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		try {
			// Call Generator with appropriate parameters (Requirement 28.4, 28.5).
			if ( 'text' === $type ) {
				$result = $this->generator->generate_text_only( $post_id, $gen_options );
			} elseif ( 'image' === $type ) {
				$result = $this->generator->generate_image_only( $post_id, null, $gen_options );
			} else {
				// 'all' type
				$result = $this->generator->generate_all_meta( $post_id, $generate_image, $gen_options );
			}

			// Handle errors from generator.
			if ( is_wp_error( $result ) ) {
				Logger::error(
					'AI generation failed',
					array(
						'module'   => 'ai',
						'post_id'  => $post_id,
						'type'     => $type,
						'error'    => $result->get_error_message(),
					)
				);

				return new WP_Error(
					$result->get_error_code(),
					$result->get_error_message(),
					array(
						'status' => 500,
						'data'   => $result->get_error_data(),
					)
				);
			}

			// Log successful generation (Requirement 28.8).
			Logger::info(
				'AI generation successful',
				array(
					'module'   => 'ai',
					'post_id'  => $post_id,
					'type'     => $type,
					'provider' => $result['provider'] ?? 'unknown',
				)
			);

			// Return JSON response with success (Requirement 28.6).
			return new WP_REST_Response(
				array(
					'success' => true,
					'result'  => $result['text'][ $type ] ?? ( $result['result'] ?? '' ),
					'data'    => $result,
				),
				200
			);
		} catch ( \Exception $e ) {
			Logger::error(
				'AI generation exception',
				array(
					'module'  => 'ai',
					'post_id' => $post_id,
					'error'   => $e->getMessage(),
				)
			);

			return new WP_Error(
				'generation_exception',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Bulk generate SEO metadata for a post.
	 *
	 * POST /meowseo/v1/ai/generate-all
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function generate_all( WP_REST_Request $request ) {
		$post_id  = $request->get_param( 'post_id' );
		$provider = $request->get_param( 'provider' );

		// Validate post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'post_not_found', __( 'Post not found.', 'meowseo' ), array( 'status' => 404 ) );
		}

		try {
			$profile_id = $request->get_param( 'profile_id' );
			$image_style_id = $request->get_param( 'image_style_id' );

			$gen_options = array(
				'provider'       => $provider,
				'profile_id'     => $profile_id,
				'style_id'       => $style_id,
				'image_style_id' => $image_style_id,
				'bypass_cache'   => true,
			);

			$result = $this->generator->generate_all_meta( $post_id, false, $gen_options );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $result,
				),
				200
			);
		} catch ( \Exception $e ) {
			return new WP_Error( 'generation_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Generate featured image only.
	 *
	 * POST /meowseo/v1/ai/generate-image
	 * Requirements: 9.2, 9.3, 9.4
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function generate_image( WP_REST_Request $request ) {
		$post_id       = $request->get_param( 'post_id' );
		$custom_prompt = $request->get_param( 'custom_prompt' );

		// Validate post_id.
		if ( ! is_int( $post_id ) || $post_id <= 0 ) {
			return new WP_Error(
				'invalid_post_id',
				__( 'Invalid post ID.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Validate post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		try {
			$image_style_id = $request->get_param( 'image_style_id' );
			$gen_options = array(
				'profile_id'     => $profile_id,
				'image_style_id' => $image_style_id,
			);

			// Generate only featured image (Requirement 9.2).
			$result = $this->generator->generate_image_only( $post_id, $custom_prompt, $gen_options );

			if ( is_wp_error( $result ) ) {
				return new WP_Error(
					$result->get_error_code(),
					$result->get_error_message(),
					array( 'status' => 500 )
				);
			}

			// Return attachment ID and URL (Requirement 9.3, 9.4).
			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => array(
						'attachment_id' => $result['image']['attachment_id'] ?? null,
						'url'           => $result['image']['url'] ?? null,
						'provider'      => $result['provider'] ?? 'unknown',
					),
				),
				200
			);
		} catch ( \Exception $e ) {
			Logger::error(
				'AI image generation exception',
				array(
					'module'   => 'ai',
					'post_id'  => $post_id,
					'error'    => $e->getMessage(),
				)
			);

			return new WP_Error(
				'image_generation_exception',
				__( 'An error occurred during image generation.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get provider status.
	 *
	 * GET /meowseo/v1/ai/provider-status
	 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_provider_status( WP_REST_Request $request ) {
		// Return all provider statuses from Manager (Requirement 3.1, 3.2, 3.3, 3.4).
		$statuses = $this->provider_manager->get_provider_statuses();

		// Include rate limit countdown (Requirement 3.5, 3.6).
		foreach ( $statuses as $slug => &$status ) {
			if ( $status['rate_limited'] ) {
				$status['rate_limit_countdown'] = $status['rate_limit_remaining'];
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $statuses,
			),
			200
		);
	}

	/**
	 * Apply generated content to postmeta.
	 *
	 * POST /meowseo/v1/ai/apply
	 * Requirements: 8.6, 27.1-27.10
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function apply( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );
		$content = $request->get_param( 'content' );
		$image   = $request->get_param( 'image' );
		$fields  = $request->get_param( 'fields' );

		// Validate post_id.
		if ( ! is_int( $post_id ) || $post_id <= 0 ) {
			return new WP_Error(
				'invalid_post_id',
				__( 'Invalid post ID.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Validate post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		try {
			// Prepare content array with image if provided.
			$apply_content = (array) $content;
			if ( ! empty( $image ) ) {
				$apply_content['image'] = (array) $image;
			}

			// Call Generator's apply_to_postmeta method (Requirement 8.6, 27.1-27.10).
			$result = $this->generator->apply_to_postmeta( $post_id, $apply_content, (array) $fields );

			if ( ! $result ) {
				return new WP_Error(
					'apply_failed',
					__( 'Failed to apply content to post.', 'meowseo' ),
					array( 'status' => 500 )
				);
			}

			Logger::info(
				'AI content applied to post',
				array(
					'module'  => 'ai',
					'post_id' => $post_id,
				)
			);

			// Return success/error response.
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Content applied successfully.', 'meowseo' ),
				),
				200
			);
		} catch ( \Exception $e ) {
			Logger::error(
				'AI apply exception',
				array(
					'module'   => 'ai',
					'post_id'  => $post_id,
					'error'    => $e->getMessage(),
				)
			);

			return new WP_Error(
				'apply_exception',
				__( 'An error occurred while applying content.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Test provider connection.
	 *
	 * POST /meowseo/v1/ai/test-provider
	 * Requirements: 2.4, 2.5, 2.6, 2.7
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function test_provider( WP_REST_Request $request ) {
		$provider   = $request->get_param( 'provider' );
		$api_key    = $request->get_param( 'api_key' );
		$profile_id = $request->get_param( 'profile_id' );

		// Validate provider against whitelist (Requirement 2.4, 2.5).
		if ( ! in_array( $provider, $this->valid_providers, true ) ) {
			return new WP_Error(
				'invalid_provider',
				__( 'Invalid provider.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Validate API key is not empty (if not using saved profile).
		if ( empty( $api_key ) && empty( $profile_id ) ) {
			return new WP_Error(
				'empty_api_key',
				__( 'API key is required.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Handle masked key for existing profile.
		if ( ! empty( $profile_id ) && ( empty( $api_key ) || strpos( $api_key, '...' ) !== false ) ) {
			$profiles = $this->provider_manager->get_options()->get( 'ai_profiles', array() );
			foreach ( $profiles as $profile ) {
				if ( $profile['id'] === $profile_id ) {
					$api_key = $this->provider_manager->get_decrypted_profile_key( $profile );
					$provider = $profile['provider'];
					break;
				}
			}
		}

		try {
			// Get provider instance.
			$provider_instance = $this->get_provider_instance( $provider, $api_key );

			if ( ! $provider_instance ) {
				return new WP_Error(
					'provider_not_found',
					__( 'Provider not found.', 'meowseo' ),
					array( 'status' => 400 )
				);
			}

			// Call provider's validate_api_key method (Requirement 2.6, 2.7).
			$is_valid = $provider_instance->validate_api_key( $api_key );

			if ( $is_valid ) {
				Logger::info(
					'AI provider test successful',
					array(
						'module'   => 'ai',
						'provider' => $provider,
					)
				);

				// Return connection status (Requirement 2.6).
				return new WP_REST_Response(
					array(
						'success' => true,
						'data'    => array(
							'valid'   => true,
							'status'  => 'connected',
							'message' => __( 'Connection successful.', 'meowseo' ),
						),
					),
					200
				);
			} else {
				$error = $provider_instance->get_last_error();

				Logger::warning(
					'AI provider test failed',
					array(
						'module'   => 'ai',
						'provider' => $provider,
						'error'    => $error,
					)
				);

				// Return error status (Requirement 2.7).
				return new WP_REST_Response(
					array(
						'success' => false,
						'data'    => array(
							'valid'   => false,
							'status'  => 'error',
							'message' => $error ?: __( 'Connection failed.', 'meowseo' ),
						),
					),
					200
				);
			}
		} catch ( \Exception $e ) {
			Logger::error(
				'AI provider test exception',
				array(
					'module'   => 'ai',
					'provider' => $provider,
					'error'    => $e->getMessage(),
				)
			);

			return new WP_Error(
				'test_exception',
				__( 'An error occurred during provider test.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get AI suggestion for failing SEO check.
	 *
	 * POST /meowseo/v1/ai/suggestion
	 * Requirements: 10.1, 10.2, 10.4
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_suggestion( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );
		$check_name = $request->get_param( 'check_name' );
		$content = $request->get_param( 'content' );
		$keyword = $request->get_param( 'keyword' );

		// Verify post exists and user can edit it
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Invalid post ID.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'unauthorized',
				__( 'You do not have permission to edit this post.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		// Get suggestion from AI Optimizer
		$suggestion = $this->optimizer->get_suggestion( $check_name, $content, $keyword, $post_id );

		if ( is_wp_error( $suggestion ) ) {
			Logger::error(
				'AI suggestion generation failed',
				array(
					'module'     => 'ai',
					'post_id'    => $post_id,
					'check_name' => $check_name,
					'error'      => $suggestion->get_error_message(),
				)
			);

			return $suggestion;
		}

		Logger::info(
			'AI suggestion generated successfully',
			array(
				'module'     => 'ai',
				'post_id'    => $post_id,
				'check_name' => $check_name,
			)
		);

		return new WP_REST_Response(
			array(
				'success'    => true,
				'suggestion' => $suggestion,
				'check_name' => $check_name,
			),
			200
		);
	}

	/**
	 * Get provider instance with given API key.
	 *
	 * @param string $provider Provider slug.
	 * @param string $api_key  API key.
	 * @return AI_Provider|null Provider instance or null.
	 */
	private function get_provider_instance( string $provider, string $api_key ): ?Contracts\AI_Provider {
		$provider_classes = array(
			'gemini'    => Providers\Provider_Gemini::class,
			'openai'    => Providers\Provider_OpenAI::class,
			'anthropic' => Providers\Provider_Anthropic::class,
			'imagen'    => Providers\Provider_Imagen::class,
			'dalle'     => Providers\Provider_DALL_E::class,
			'deepseek'  => Providers\Provider_DeepSeek::class,
			'glm'       => Providers\Provider_GLM::class,
			'qwen'      => Providers\Provider_Qwen::class,
		);

		if ( ! isset( $provider_classes[ $provider ] ) ) {
			return null;
		}

		$class = $provider_classes[ $provider ];
		return new $class( $api_key );
	}

	/**
	 * Check if user has edit_posts capability.
	 *
	 * Permission callback for generation endpoints.
	 * Requirements: 25.2, 25.5
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public function check_edit_posts_capability(): bool {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if user has manage_options capability.
	 *
	 * Permission callback for settings endpoints.
	 * Requirements: 25.2, 25.5
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public function check_manage_options_capability(): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check permission and nonce for generation endpoints.
	 *
	 * Permission callback for POST generation endpoints.
	 * Requirements: 25.2, 25.3, 25.4, 25.5
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function check_permission_and_nonce( WP_REST_Request $request ) {
		// Check capability (Requirement 25.2, 25.5).
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		// Verify nonce (Requirement 25.3, 25.4).
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_invalid_nonce',
				__( 'Nonce verification failed.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check permission and nonce for settings endpoints.
	 *
	 * Permission callback for POST settings endpoints.
	 * Requirements: 25.2, 25.3, 25.4, 25.5
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function check_permission_and_nonce_for_settings( WP_REST_Request $request ) {
		// Check capability (Requirement 25.2, 25.5).
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		// Verify nonce (Requirement 25.3, 25.4).
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_invalid_nonce',
				__( 'Nonce verification failed.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Sanitize content object.
	 *
	 * Requirements: 26.1, 26.2, 26.3
	 *
	 * @param mixed $value Value to sanitize.
	 * @return array Sanitized array.
	 */
	public function sanitize_content_object( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $key => $val ) {
			// Sanitize key.
			$key = sanitize_key( $key );

			// Sanitize value based on type.
			if ( is_array( $val ) ) {
				$sanitized[ $key ] = array_map( 'sanitize_text_field', (array) $val );
			} else {
				$sanitized[ $key ] = sanitize_textarea_field( $val );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize image object.
	 *
	 * Requirements: 26.1, 26.2, 26.3
	 *
	 * @param mixed $value Value to sanitize.
	 * @return array Sanitized array.
	 */
	public function sanitize_image_object( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();

		if ( isset( $value['attachment_id'] ) ) {
			$sanitized['attachment_id'] = absint( $value['attachment_id'] );
		}

		if ( isset( $value['url'] ) ) {
			$sanitized['url'] = esc_url_raw( $value['url'] );
		}

		if ( isset( $value['provider'] ) ) {
			$sanitized['provider'] = sanitize_text_field( $value['provider'] );
		}

		return $sanitized;
	}

	/**
	 * Sanitize fields array.
	 *
	 * Requirements: 26.1, 26.2, 26.3
	 *
	 * @param mixed $value Value to sanitize.
	 * @return array Sanitized array.
	 */
	public function sanitize_fields_array( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_map( 'sanitize_text_field', $value );
	}
}
