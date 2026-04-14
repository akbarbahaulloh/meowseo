<?php
/**
 * WPGraphQL Integration
 *
 * Registers SEO fields on WPGraphQL queryable post types for headless deployments.
 * Only loaded when WPGraphQL plugin is active.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPGraphQL integration class
 *
 * Extends WPGraphQL with SEO fields for headless WordPress.
 * Requirement: 13.5
 *
 * @since 1.0.0
 */
class WPGraphQL {

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
	 * @param Module_Manager $module_manager Module Manager instance.
	 */
	public function __construct( Module_Manager $module_manager ) {
		$this->module_manager = $module_manager;
	}

	/**
	 * Register WPGraphQL fields
	 *
	 * Registers seo field on all queryable post types.
	 * Requirement: 13.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_fields(): void {
		// Check if WPGraphQL is active.
		if ( ! function_exists( 'register_graphql_field' ) ) {
			return;
		}

		// Register SEO object type.
		$this->register_seo_type();

		// Register OpenGraph object type.
		$this->register_opengraph_type();

		// Register TwitterCard object type.
		$this->register_twitter_card_type();

		// Register seo field on all queryable post types.
		$this->register_post_type_fields();
	}

	/**
	 * Register SEO object type
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_seo_type(): void {
		register_graphql_object_type(
			'MeowSeoData',
			array(
				'description' => __( 'SEO meta data for a post', 'meowseo' ),
				'fields'      => array(
					'title'        => array(
						'type'        => 'String',
						'description' => __( 'SEO title', 'meowseo' ),
					),
					'description'  => array(
						'type'        => 'String',
						'description' => __( 'Meta description', 'meowseo' ),
					),
					'robots'       => array(
						'type'        => 'String',
						'description' => __( 'Robots directive', 'meowseo' ),
					),
					'canonical'    => array(
						'type'        => 'String',
						'description' => __( 'Canonical URL', 'meowseo' ),
					),
					'openGraph'    => array(
						'type'        => 'MeowSeoOpenGraph',
						'description' => __( 'Open Graph meta data', 'meowseo' ),
					),
					'twitterCard'  => array(
						'type'        => 'MeowSeoTwitterCard',
						'description' => __( 'Twitter Card meta data', 'meowseo' ),
					),
					'schemaJsonLd' => array(
						'type'        => 'String',
						'description' => __( 'JSON-LD structured data', 'meowseo' ),
					),
				),
			)
		);
	}

	/**
	 * Register OpenGraph object type
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_opengraph_type(): void {
		register_graphql_object_type(
			'MeowSeoOpenGraph',
			array(
				'description' => __( 'Open Graph meta data', 'meowseo' ),
				'fields'      => array(
					'title'       => array(
						'type'        => 'String',
						'description' => __( 'Open Graph title', 'meowseo' ),
					),
					'description' => array(
						'type'        => 'String',
						'description' => __( 'Open Graph description', 'meowseo' ),
					),
					'image'       => array(
						'type'        => 'String',
						'description' => __( 'Open Graph image URL', 'meowseo' ),
					),
					'type'        => array(
						'type'        => 'String',
						'description' => __( 'Open Graph type', 'meowseo' ),
					),
					'url'         => array(
						'type'        => 'String',
						'description' => __( 'Open Graph URL', 'meowseo' ),
					),
				),
			)
		);
	}

	/**
	 * Register TwitterCard object type
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_twitter_card_type(): void {
		register_graphql_object_type(
			'MeowSeoTwitterCard',
			array(
				'description' => __( 'Twitter Card meta data', 'meowseo' ),
				'fields'      => array(
					'card'        => array(
						'type'        => 'String',
						'description' => __( 'Twitter Card type', 'meowseo' ),
					),
					'title'       => array(
						'type'        => 'String',
						'description' => __( 'Twitter Card title', 'meowseo' ),
					),
					'description' => array(
						'type'        => 'String',
						'description' => __( 'Twitter Card description', 'meowseo' ),
					),
					'image'       => array(
						'type'        => 'String',
						'description' => __( 'Twitter Card image URL', 'meowseo' ),
					),
				),
			)
		);
	}

	/**
	 * Register seo field on all queryable post types
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_post_type_fields(): void {
		// Get all WPGraphQL allowed post types.
		$post_types = \WPGraphQL::get_allowed_post_types();

		foreach ( $post_types as $post_type ) {
			register_graphql_field(
				$post_type,
				'seo',
				array(
					'type'        => 'MeowSeoData',
					'description' => __( 'SEO meta data for this post', 'meowseo' ),
					'resolve'     => array( $this, 'resolve_seo_field' ),
				)
			);
		}
	}

	/**
	 * Resolve seo field
	 *
	 * Returns all SEO data for a post.
	 * Requirement: 13.5
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 * @return array|null SEO data array or null.
	 */
	public function resolve_seo_field( $post ): ?array {
		if ( ! isset( $post->ID ) ) {
			return null;
		}

		$post_id = $post->ID;

		// Get meta module if active.
		$meta_module = $this->module_manager->get_module( 'meta' );
		$social_module = $this->module_manager->get_module( 'social' );
		$schema_module = $this->module_manager->get_module( 'schema' );

		$data = array();

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

		return $data;
	}
}
