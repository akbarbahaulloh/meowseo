<?php
/**
 * Schema Module
 *
 * Generates and outputs structured data (JSON-LD) for posts and pages.
 * Outputs a single script tag with all schema graphs.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Schema;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\Cache;
use MeowSEO\Helpers\Schema_Builder;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema module class
 *
 * Implements the Module interface to provide structured data output.
 *
 * @since 1.0.0
 */
class Schema implements Module {

	/**
	 * Module ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const MODULE_ID = 'schema';

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Schema Builder instance
	 *
	 * @since 1.0.0
	 * @var Schema_Builder
	 */
	private Schema_Builder $schema_builder;

	/**
	 * Global Schema Generator instance
	 *
	 * @since 1.0.0
	 * @var Global_Schema_Generator
	 */
	private Global_Schema_Generator $global_schema_generator;

	/**
	 * Breadcrumbs instance
	 *
	 * @since 1.0.0
	 * @var \MeowSEO\Modules\Breadcrumbs\Breadcrumbs|null
	 */
	private $breadcrumbs = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->schema_builder = new Schema_Builder( $options );
		$this->global_schema_generator = new Global_Schema_Generator( $options );
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
		// Output schema in wp_head at priority 5 (Requirement 2.2).
		add_action( 'wp_head', array( $this, 'output_schema' ), 5 );

		// Register REST API endpoint.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Register WPGraphQL field if WPGraphQL is active.
		if ( function_exists( 'register_graphql_field' ) ) {
			add_action( 'graphql_register_types', array( $this, 'register_wpgraphql_fields' ) );
		}

		// Invalidate cache on post save (Requirement 2.7).
		add_action( 'save_post', array( $this, 'invalidate_cache' ), 10, 1 );

		// Invalidate cache on schema meta update (Requirement 2.6).
		add_action( 'update_post_meta', array( $this, 'invalidate_cache_on_meta_update' ), 10, 4 );

		// Register breadcrumb shortcode and template function (Requirements 8.8, 8.9).
		$this->register_breadcrumb_shortcode();

		// Hook global schema generator into schema output (Requirement 1.6).
		add_filter( 'meowseo_schema_graph', array( $this, 'add_global_schema' ), 10, 2 );
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
	 * Output schema in head
	 *
	 * Outputs a single script tag with all schema graphs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_schema(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		/**
		 * Action before schema output
		 *
		 * Fires before schema JSON-LD is output in wp_head.
		 *
		 * @since 1.0.0
		 * @param int $post_id Post ID.
		 */
		do_action( 'meowseo_before_schema_output', $post_id );

		// Output debug comments if WP_DEBUG is enabled (Requirements 17.5, 17.6)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			echo $this->schema_builder->get_debug_output();
		}

		$schema_json = $this->get_schema_json( $post_id );
		if ( empty( $schema_json ) ) {
			return;
		}

		echo '<script type="application/ld+json">' . "\n";
		echo $schema_json . "\n";
		echo '</script>' . "\n";

		/**
		 * Action after schema output
		 *
		 * Fires after schema JSON-LD is output in wp_head.
		 *
		 * @since 1.0.0
		 * @param int $post_id Post ID.
		 */
		do_action( 'meowseo_after_schema_output', $post_id );
	}

	/**
	 * Get schema JSON for a post
	 *
	 * Returns cached schema JSON or generates it.
	 * Implements caching to eliminate DB queries (Requirement 14.1).
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string Schema JSON string.
	 */
	public function get_schema_json( int $post_id ): string {
		// Check cache first (Requirement 14.1, 14.2).
		$cache_key = "schema_{$post_id}";
		$cached = Cache::get( $cache_key );

		if ( is_string( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		// Build schema graph.
		$graph = $this->schema_builder->build( $post_id );
		if ( empty( $graph ) ) {
			return '';
		}

		// Convert to JSON.
		$json = $this->schema_builder->to_json( $graph );

		// Cache the result for 1 hour (Requirement 14.1).
		Cache::set( $cache_key, $json, 3600 );

		return $json;
	}

	/**
	 * Invalidate schema cache on post save
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function invalidate_cache( int $post_id ): void {
		Cache::delete( "schema_{$post_id}" );

		/**
		 * Action when schema cache is invalidated
		 *
		 * Fires after schema cache is cleared for a post.
		 *
		 * @since 1.0.0
		 * @param int $post_id Post ID.
		 */
		do_action( 'meowseo_schema_cache_invalidated', $post_id );
	}

	/**
	 * Invalidate schema cache on postmeta update
	 *
	 * Invalidates cache when schema-related postmeta is updated.
	 * Requirement 2.6: Invalidate on post save and meta update.
	 *
	 * @since 1.0.0
	 * @param int    $meta_id    Meta ID.
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return void
	 */
	public function invalidate_cache_on_meta_update( int $meta_id, int $post_id, string $meta_key, $meta_value ): void {
		// Only invalidate cache for schema-related meta keys.
		if ( in_array( $meta_key, array( '_meowseo_schema_type', '_meowseo_schema_config' ), true ) ) {
			$this->invalidate_cache( $post_id );
		}
	}

	/**
	 * Register REST API routes
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'meowseo/v1',
			'/schema/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_schema' ),
				'permission_callback' => function ( $request ) {
					$post_id = (int) $request['post_id'];
					$post = get_post( $post_id );
					if ( ! $post ) {
						return false;
					}
					// Security: Verify post is publicly viewable (Requirement 19.2).
					return is_post_publicly_viewable( $post );
				},
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Register schema configuration endpoint.
		register_rest_route(
			'meowseo/v1',
			'/schema/config/(?P<post_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_update_schema_config' ),
				'permission_callback' => function ( $request ) {
					$post_id = (int) $request['post_id'];
					// Security: Check user capabilities (Requirement 19.2).
					return current_user_can( 'edit_post', $post_id );
				},
				'args'                => array(
					'post_id'      => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'schema_type'  => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_schema_type' ),
					),
					'schema_config' => array(
						'required'          => false,
						'type'              => 'object',
						'validate_callback' => array( $this, 'validate_schema_config' ),
					),
				),
			)
		);
	}

	/**
	 * REST API callback for schema endpoint
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_get_schema( $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];

		$schema_json = $this->get_schema_json( $post_id );

		$response = new \WP_REST_Response(
			array(
				'post_id'      => $post_id,
				'schema_jsonld' => $schema_json,
			),
			200
		);

		// Add cache headers for CDN/edge caching.
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * REST API callback for updating schema configuration
	 *
	 * Security: Validates JSON structure and sanitizes all input (Requirement 19.2).
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_update_schema_config( $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];

		// Verify nonce for security (Requirement 19.2).
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'meowseo' ),
				),
				403
			);
		}

		// Update schema type if provided.
		if ( $request->has_param( 'schema_type' ) ) {
			$schema_type = sanitize_text_field( $request->get_param( 'schema_type' ) );
			update_post_meta( $post_id, '_meowseo_schema_type', $schema_type );
		}

		// Update schema config if provided.
		if ( $request->has_param( 'schema_config' ) ) {
			$schema_config = $request->get_param( 'schema_config' );
			
			// Sanitize the configuration (Requirement 19.2).
			$sanitized_config = $this->sanitize_schema_config( $schema_config );
			
			// Validate JSON structure before saving (Requirement 19.2).
			$json_encoded = wp_json_encode( $sanitized_config );
			if ( false === $json_encoded ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Invalid JSON structure in schema configuration.', 'meowseo' ),
					),
					400
				);
			}

			update_post_meta( $post_id, '_meowseo_schema_config', $json_encoded );
		}

		// Invalidate cache.
		$this->invalidate_cache( $post_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Schema configuration updated successfully.', 'meowseo' ),
				'post_id' => $post_id,
			),
			200
		);
	}

	/**
	 * Validate schema type
	 *
	 * Security: Ensures only valid schema types are accepted (Requirement 19.2).
	 *
	 * @since 1.0.0
	 * @param string $schema_type Schema type to validate.
	 * @return bool True if valid.
	 */
	public function validate_schema_type( string $schema_type ): bool {
		$valid_types = array(
			'Article',
			'WebPage',
			'FAQPage',
			'HowTo',
			'LocalBusiness',
			'Product',
			'Recipe',
			'Event',
			'VideoObject',
			'Course',
			'JobPosting',
			'Book',
			'Person',
		);

		return in_array( $schema_type, $valid_types, true );
	}

	/**
	 * Validate schema configuration
	 *
	 * Security: Validates JSON structure (Requirement 19.2).
	 *
	 * @since 1.0.0
	 * @param mixed $config Configuration to validate.
	 * @return bool True if valid.
	 */
	public function validate_schema_config( $config ): bool {
		// Must be an object/array.
		if ( ! is_array( $config ) && ! is_object( $config ) ) {
			return false;
		}

		// Validate it can be JSON encoded.
		$json = wp_json_encode( $config );
		return false !== $json;
	}

	/**
	 * Sanitize schema configuration
	 *
	 * Security: Sanitizes all user input in schema configuration (Requirement 19.2).
	 *
	 * @since 1.0.0
	 * @param array|object $config Configuration to sanitize.
	 * @return array Sanitized configuration.
	 */
	private function sanitize_schema_config( $config ): array {
		if ( is_object( $config ) ) {
			$config = (array) $config;
		}

		if ( ! is_array( $config ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $config as $key => $value ) {
			$key = sanitize_key( $key );

			if ( is_array( $value ) || is_object( $value ) ) {
				// Recursively sanitize nested arrays/objects.
				$sanitized[ $key ] = $this->sanitize_schema_config( $value );
			} elseif ( is_string( $value ) ) {
				// Sanitize strings - use wp_kses for HTML content in answers/descriptions.
				if ( in_array( $key, array( 'answer', 'text', 'description' ), true ) ) {
					// Allow basic HTML in content fields.
					$sanitized[ $key ] = wp_kses_post( $value );
				} else {
					// Plain text for other fields.
					$sanitized[ $key ] = sanitize_text_field( $value );
				}
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $key ] = $value;
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Register WPGraphQL fields
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_wpgraphql_fields(): void {
		// Get all public post types.
		$post_types = \WPGraphQL::get_allowed_post_types();

		foreach ( $post_types as $post_type ) {
			register_graphql_field(
				$post_type,
				'schemaJsonLd',
				array(
					'type'        => 'String',
					'description' => __( 'JSON-LD structured data for this post', 'meowseo' ),
					'resolve'     => function ( $post ) {
						if ( ! isset( $post->ID ) ) {
							return null;
						}
						return $this->get_schema_json( $post->ID );
					},
				)
			);
		}
	}

	/**
	 * Register breadcrumb shortcode and template function
	 *
	 * Registers [meowseo_breadcrumbs] shortcode and meowseo_breadcrumbs() template function
	 * for theme developers to display breadcrumbs.
	 *
	 * Requirements 8.8, 8.9: Register shortcode and template function that call Breadcrumbs render()
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_breadcrumb_shortcode(): void {
		// Get or create Breadcrumbs instance if not already created.
		if ( null === $this->breadcrumbs ) {
			$this->breadcrumbs = new \MeowSEO\Helpers\Breadcrumbs( $this->options );
		}

		// Register shortcode [meowseo_breadcrumbs].
		\add_shortcode( 'meowseo_breadcrumbs', array( $this, 'breadcrumb_shortcode_callback' ) );

		// Register global template function meowseo_breadcrumbs() - declared at global scope to avoid redeclaration errors.
		// See meowseo_breadcrumbs() function declaration at end of file.
	}

	/**
	 * Breadcrumb shortcode callback
	 *
	 * Callback for [meowseo_breadcrumbs] shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered breadcrumbs HTML.
	 */
	public function breadcrumb_shortcode_callback( array $atts ): string {
		$atts = \shortcode_atts(
			array(
				'class'     => '',
				'separator' => ' › ',
			),
			$atts,
			'meowseo_breadcrumbs'
		);

		return $this->render_breadcrumbs( $atts['class'], $atts['separator'] );
	}

	/**
	 * Add global schema to graph
	 *
	 * Hooks into meowseo_schema_graph filter to add WebSite and Organization schema.
	 * Requirement 1.6: Hook into existing Schema_Output class via meowseo_schema_output filter.
	 *
	 * @since 1.0.0
	 * @param array $nodes   Array of schema nodes.
	 * @param int   $post_id Post ID.
	 * @return array Modified array of schema nodes.
	 */
	public function add_global_schema( array $nodes, int $post_id ): array {
		// Generate global schema (WebSite and Organization).
		$global_schemas = $this->global_schema_generator->generate_global_schema();

		// Prepend global schemas to the beginning of the graph.
		// This ensures WebSite and Organization appear first in the @graph array.
		return array_merge( $global_schemas, $nodes );
	}

	/**
	 * Render breadcrumbs
	 *
	 * Renders breadcrumbs using the Breadcrumbs helper class.
	 *
	 * @since 1.0.0
	 * @param string $css_class Optional CSS class for the nav element.
	 * @param string $separator Optional separator between breadcrumbs (default: ' › ').
	 * @return string Rendered breadcrumbs HTML.
	 */
	public function render_breadcrumbs( string $css_class = '', string $separator = ' › ' ): string {
		// Get or create Breadcrumbs instance if not already created.
		if ( null === $this->breadcrumbs ) {
			$this->breadcrumbs = new \MeowSEO\Helpers\Breadcrumbs( $this->options );
		}

		return $this->breadcrumbs->render( $css_class, $separator );
	}
}

/**
 * Display breadcrumbs in theme templates
 *
 * Global template function for displaying breadcrumbs.
 * Requirement 8.9: THE Breadcrumbs SHALL provide template function meowseo_breadcrumbs()
 *
 * @since 1.0.0
 * @param string $css_class Optional CSS class for the nav element.
 * @param string $separator Optional separator between breadcrumbs (default: ' › ').
 * @return void
 */
if ( ! function_exists( 'meowseo_breadcrumbs' ) ) {
	function meowseo_breadcrumbs( string $css_class = '', string $separator = ' › ' ): void {
		// Get the Schema module instance to access breadcrumbs.
		$plugin = \MeowSEO\Plugin::instance();
		$module_manager = $plugin->get_module_manager();
		
		if ( ! $module_manager ) {
			return;
		}

		// Get the Schema module.
		$schema_module = $module_manager->get_module( 'schema' );
		
		if ( ! $schema_module ) {
			return;
		}

		// Call the breadcrumb rendering method.
		echo $schema_module->render_breadcrumbs( $css_class, $separator ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
