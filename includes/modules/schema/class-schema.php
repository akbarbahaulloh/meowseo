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
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->schema_builder = new Schema_Builder( $options );
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
		// Output schema in wp_head.
		add_action( 'wp_head', array( $this, 'output_schema' ), 2 );

		// Register REST API endpoint.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Register WPGraphQL field if WPGraphQL is active.
		if ( function_exists( 'register_graphql_field' ) ) {
			add_action( 'graphql_register_types', array( $this, 'register_wpgraphql_fields' ) );
		}

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

		$schema_json = $this->get_schema_json( $post_id );
		if ( empty( $schema_json ) ) {
			return;
		}

		echo '<script type="application/ld+json">' . "\n";
		echo $schema_json . "\n";
		echo '</script>' . "\n";
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
}
