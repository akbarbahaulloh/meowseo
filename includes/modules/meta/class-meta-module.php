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
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;

		// Initialize Title_Patterns.
		$this->patterns = new Title_Patterns( $this->options );

		// Initialize Meta_Resolver.
		$this->resolver = new Meta_Resolver( $this->options, $this->patterns );

		// Initialize Meta_Output.
		$this->output = new Meta_Output( $this->resolver );
	}

	/**
	 * Boot the module
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->remove_theme_title_tag();
		$this->register_hooks();
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

		// Register rest_api_init hook for postmeta exposure.
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );

		// Register enqueue_block_editor_assets hook for Gutenberg sidebar.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
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
		// TODO: Delegate to Meta_Postmeta instance
	}

	/**
	 * Register REST fields
	 *
	 * Registers postmeta fields for REST API. This is a placeholder that will be
	 * implemented by Meta_Postmeta class.
	 *
	 * @return void
	 */
	public function register_rest_fields(): void {
		// TODO: Delegate to Meta_Postmeta instance
	}

	/**
	 * Enqueue block editor assets
	 *
	 * Enqueues scripts and styles for Gutenberg sidebar. This is a placeholder
	 * that will be implemented by Gutenberg class.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets(): void {
		// TODO: Delegate to Gutenberg instance
	}
}
