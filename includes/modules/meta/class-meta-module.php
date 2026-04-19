<?php
/**
 * Meta Module Entry Point
 *
 * @package MeowSEO
 * @subpackage Modules\Meta
 */

namespace MeowSEO\Modules\Meta;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;
use MeowSEO\Modules\Keywords\Keyword_Manager;
use MeowSEO\Modules\Keywords\Keyword_Analyzer;

/**
 * Meta_Module class
 *
 * Main module class implementing the Module interface, responsible for
 * registering hooks and coordinating meta tag output.
 */
class Meta_Module implements Module {
	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Meta_Output instance
	 *
	 * @var Meta_Output
	 */
	private Meta_Output $output;

	/**
	 * Meta_Resolver instance
	 *
	 * @var Meta_Resolver
	 */
	private Meta_Resolver $resolver;

	/**
	 * Title_Patterns instance
	 *
	 * @var Title_Patterns
	 */
	private Title_Patterns $patterns;

	/**
	 * Meta_Postmeta instance
	 *
	 * @var Meta_Postmeta
	 */
	private Meta_Postmeta $postmeta;

	/**
	 * Global_SEO instance
	 *
	 * @var Global_SEO
	 */
	private Global_SEO $global_seo;

	/**
	 * Robots_Txt instance
	 *
	 * @var Robots_Txt
	 */
	private Robots_Txt $robots_txt;

	/**
	 * Gutenberg_Assets instance
	 *
	 * @var Gutenberg_Assets
	 */
	private Gutenberg_Assets $gutenberg_assets;

	/**
	 * Classic_Editor instance
	 *
	 * @var Classic_Editor
	 */
	private Classic_Editor $classic_editor;

	/**
	 * Keyword_Manager instance
	 *
	 * @var Keyword_Manager
	 */
	private Keyword_Manager $keyword_manager;

	/**
	 * Keyword_Analyzer instance
	 *
	 * @var Keyword_Analyzer
	 */
	private Keyword_Analyzer $keyword_analyzer;

	/**
	 * Constructor
	 *
	 * Instantiates all module components with proper dependency injection.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;

		// Initialize Title_Patterns.
		$this->patterns = new Title_Patterns( $this->options );

		// Initialize Meta_Resolver with Title_Patterns dependency.
		$this->resolver = new Meta_Resolver( $this->options, $this->patterns );

		// Initialize Global_SEO with dependencies.
		$this->global_seo = new Global_SEO( $this->options, $this->patterns, $this->resolver );

		// Initialize Meta_Output with Meta_Resolver dependency.
		$this->output = new Meta_Output( $this->resolver, $this->global_seo );

		// Initialize Meta_Postmeta for field registration.
		$this->postmeta = new Meta_Postmeta();

		// Initialize Robots_Txt for virtual robots.txt management.
		$this->robots_txt = new Robots_Txt( $this->options );

		// Initialize Gutenberg_Assets for editor integration.
		$this->gutenberg_assets = new Gutenberg_Assets();

		// Initialize Classic_Editor for classic editor meta box.
		$this->classic_editor = new Classic_Editor();

		// Initialize Keyword_Manager for keyword storage and retrieval.
		$this->keyword_manager = new Keyword_Manager( $this->options );

		// Initialize Keyword_Analyzer for per-keyword analysis.
		$this->keyword_analyzer = new Keyword_Analyzer( $this->keyword_manager );
	}

	/**
	 * Boot the module
	 *
	 * Initializes all hooks and registers components.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->remove_theme_title_tag();
		$this->register_hooks();
		
		// Register Robots_Txt filter hook.
		$this->robots_txt->register();

		// Initialize Gutenberg_Assets hooks.
		$this->gutenberg_assets->init();

		// Initialize Classic_Editor hooks.
		$this->classic_editor->init();
	}

	/**
	 * Get module ID
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'meta';
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// Register wp_head hook with priority 1 for early meta tag output.
		add_action( 'wp_head', array( $this, 'output_head_tags' ), 1 );

		// Register document_title_parts filter to control title tag output.
		add_filter( 'document_title_parts', array( $this, 'filter_document_title_parts' ) );

		// Register save_post hook for classic editor meta save handling.
		add_action( 'save_post', array( $this, 'handle_save_post' ), 10, 2 );

		// Register save_post hook for keyword analysis (priority 20 to run after meta save).
		add_action( 'save_post', array( $this, 'trigger_keyword_analysis' ), 20, 2 );

		// Register rest_api_init hook for postmeta exposure.
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );

		// Register rest_api_init hook for keyword REST endpoints.
		add_action( 'rest_api_init', array( $this, 'register_keyword_rest_routes' ) );
	}

	/**
	 * Remove theme title tag support
	 *
	 * Prevents WordPress from outputting default title tags, allowing
	 * the Meta_Module to control all title output.
	 *
	 * @return void
	 */
	private function remove_theme_title_tag(): void {
		remove_theme_support( 'title-tag' );
	}

	/**
	 * Filter document title parts
	 *
	 * Suppresses WordPress's default title generation by returning an empty array.
	 * The Meta_Module controls all title output via wp_head hook.
	 *
	 * @param array $parts Title parts.
	 * @return array Empty array to suppress WordPress's default title generation.
	 */
	public function filter_document_title_parts( array $parts ): array {
		return array();
	}

	/**
	 * Output head tags
	 *
	 * Delegates to Meta_Output instance to output all meta tags in wp_head.
	 *
	 * @return void
	 */
	public function output_head_tags(): void {
		$this->output->output_head_tags();
	}

	/**
	 * Handle save_post hook
	 *
	 * Handles classic editor meta save. This is a placeholder that will be
	 * implemented by Meta_Postmeta class.
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post    Post object.
	 * @return void
	 */
	public function handle_save_post( int $post_id, object $post ): void {
		$this->classic_editor->save_meta( $post_id, $post );
	}

	/**
	 * Register REST fields
	 *
	 * Delegates to Meta_Postmeta instance to register all postmeta fields
	 * for REST API access.
	 *
	 * @return void
	 */
	public function register_rest_fields(): void {
		$this->postmeta->register();
	}

	/**
	 * Trigger keyword analysis on post save
	 *
	 * Runs keyword analysis for all focus keywords when a post is saved.
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post    Post object.
	 * @return void
	 */
	public function trigger_keyword_analysis( int $post_id, object $post ): void {
		// Skip autosaves and revisions.
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Skip if post is not published or scheduled.
		if ( ! in_array( $post->post_status, array( 'publish', 'future', 'draft', 'pending' ), true ) ) {
			return;
		}

		// Get post content and context.
		$content = $post->post_content;
		$context = array(
			'title'       => get_post_meta( $post_id, '_meowseo_title', true ) ?: $post->post_title,
			'description' => get_post_meta( $post_id, '_meowseo_description', true ) ?: '',
			'slug'        => $post->post_name,
		);

		// Run keyword analysis.
		$this->keyword_analyzer->analyze_all_keywords( $post_id, $content, $context );
	}

	/**
	 * Register keyword REST routes
	 *
	 * Registers REST API endpoints for keyword management.
	 *
	 * @return void
	 */
	public function register_keyword_rest_routes(): void {
		// Register endpoint for updating keywords.
		register_rest_route(
			'meowseo/v1',
			'/keywords/(?P<post_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_update_keywords' ),
				'permission_callback' => function ( $request ) {
					$post_id = (int) $request->get_param( 'post_id' );
					return current_user_can( 'edit_post', $post_id );
				},
				'args'                => array(
					'post_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'primary'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'secondary' => array(
						'required'          => false,
						'type'              => 'array',
						'sanitize_callback' => function ( $value ) {
							return array_map( 'sanitize_text_field', (array) $value );
						},
					),
				),
			)
		);

		// Register endpoint for triggering keyword analysis.
		register_rest_route(
			'meowseo/v1',
			'/keywords/(?P<post_id>\d+)/analyze',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_analyze_keywords' ),
				'permission_callback' => function ( $request ) {
					$post_id = (int) $request->get_param( 'post_id' );
					return current_user_can( 'edit_post', $post_id );
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
	 * REST endpoint callback for updating keywords
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_update_keywords( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id   = (int) $request->get_param( 'post_id' );
		$primary   = $request->get_param( 'primary' );
		$secondary = $request->get_param( 'secondary' );

		// Update primary keyword if provided.
		if ( null !== $primary ) {
			$this->keyword_manager->set_primary_keyword( $post_id, $primary );
		}

		// Update secondary keywords if provided.
		if ( null !== $secondary && is_array( $secondary ) ) {
			// Clear existing secondary keywords.
			delete_post_meta( $post_id, '_meowseo_secondary_keywords' );

			// Add new secondary keywords.
			foreach ( $secondary as $keyword ) {
				if ( ! empty( $keyword ) ) {
					$result = $this->keyword_manager->add_secondary_keyword( $post_id, $keyword );
					if ( is_array( $result ) && isset( $result['error'] ) ) {
						return new \WP_REST_Response(
							array(
								'success' => false,
								'message' => $result['message'],
							),
							400
						);
					}
				}
			}
		}

		// Get updated keywords.
		$keywords = $this->keyword_manager->get_keywords( $post_id );

		// Trigger analysis.
		$post = get_post( $post_id );
		if ( $post ) {
			$this->trigger_keyword_analysis( $post_id, $post );
		}

		// Get analysis results.
		$analysis = get_post_meta( $post_id, '_meowseo_keyword_analysis', true );
		if ( is_string( $analysis ) ) {
			$analysis = json_decode( $analysis, true );
		}

		return new \WP_REST_Response(
			array(
				'success'  => true,
				'keywords' => $keywords,
				'analysis' => $analysis ?: array(),
			),
			200
		);
	}

	/**
	 * REST endpoint callback for analyzing keywords
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_analyze_keywords( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post not found.', 'meowseo' ),
				),
				404
			);
		}

		// Trigger analysis.
		$this->trigger_keyword_analysis( $post_id, $post );

		// Get analysis results.
		$analysis = get_post_meta( $post_id, '_meowseo_keyword_analysis', true );
		if ( is_string( $analysis ) ) {
			$analysis = json_decode( $analysis, true );
		}

		return new \WP_REST_Response(
			array(
				'success'  => true,
				'analysis' => $analysis ?: array(),
			),
			200
		);
	}


}
