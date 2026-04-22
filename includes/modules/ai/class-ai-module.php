<?php
/**
 * AI Generation Module for MeowSEO.
 *
 * Provides AI-powered SEO metadata and featured image generation.
 *
 * @package MeowSEO\Modules\AI
 */

namespace MeowSEO\Modules\AI;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;
use MeowSEO\Helpers\Logger;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI Module entry point.
 *
 * Implements the Module interface to integrate with MeowSEO's module system.
 * This class serves as the main entry point for the AI Generation Module,
 * coordinating between Provider_Manager, Generator, Settings, and REST components.
 *
 * @since 1.0.0
 */
class AI_Module implements Module {

	/**
	 * Options instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Provider Manager instance.
	 *
	 * Handles provider orchestration and fallback logic.
	 *
	 * @since 1.0.0
	 *
	 * @var AI_Provider_Manager
	 */
	private AI_Provider_Manager $provider_manager;

	/**
	 * Generator instance.
	 *
	 * Handles prompt building and content processing.
	 *
	 * @since 1.0.0
	 *
	 * @var AI_Generator
	 */
	private AI_Generator $generator;

	/**
	 * Optimizer instance.
	 *
	 * Handles AI-powered suggestions for failing SEO checks.
	 *
	 * @since 1.0.0
	 *
	 * @var AI_Optimizer
	 */
	private AI_Optimizer $optimizer;

	/**
	 * Settings instance.
	 *
	 * Handles settings page rendering and configuration.
	 *
	 * @since 1.0.0
	 *
	 * @var AI_Settings
	 */
	private AI_Settings $settings;

	/**
	 * REST instance.
	 *
	 * Handles REST API endpoint registration and callbacks.
	 *
	 * @since 1.0.0
	 *
	 * @var AI_REST
	 */
	private AI_REST $rest;

	/**
	 * Constructor.
	 *
	 * Initializes the module with its dependencies. Dependencies are instantiated
	 * here to ensure proper initialization order and dependency injection.
	 *
	 * @since 1.0.0
	 *
	 * @param Options $options Options instance.
	 * @throws \Exception If dependency instantiation fails.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;

		// Initialize dependencies with proper error handling.
		try {
			// Instantiate Provider Manager first (no dependencies).
			$this->provider_manager = new AI_Provider_Manager( $options );

			// Instantiate Generator (depends on Provider Manager).
			$this->generator = new AI_Generator( $this->provider_manager, $options );

			// Instantiate Optimizer (depends on Provider Manager).
			$this->optimizer = new AI_Optimizer( $this->provider_manager );

			// Instantiate Settings (depends on Provider Manager).
			$this->settings = new AI_Settings( $options, $this->provider_manager );

			// Instantiate REST (depends on Generator, Provider Manager, and Optimizer).
			$this->rest = new AI_REST( $this->generator, $this->provider_manager, $this->optimizer );
		} catch ( \Exception $e ) {
			// Log the error with full context.
			Logger::error(
				'Failed to initialize AI module dependencies',
				array(
					'exception_class' => get_class( $e ),
					'exception_message' => $e->getMessage(),
					'file' => $e->getFile(),
					'line' => $e->getLine(),
					'stack_trace' => $e->getTraceAsString(),
				)
			);

			// Re-throw to allow Module_Manager to handle gracefully.
			throw $e;
		}
	}

	/**
	 * Get the module identifier.
	 *
	 * Returns the unique identifier for this module used for registration
	 * and identification within the MeowSEO module system.
	 *
	 * @since 1.0.0
	 *
	 * @return string Module ID ('ai').
	 */
	public function get_id(): string {
		return 'ai';
	}

	/**
	 * Boot the module.
	 *
	 * Registers all WordPress hooks and initializes module functionality.
	 * This method is called by the Module Manager when the plugin is loaded.
	 *
	 * Hooks registered:
	 * - rest_api_init: Register REST API endpoints
	 * - admin_enqueue_scripts: Enqueue admin scripts and styles
	 * - enqueue_block_editor_assets: Enqueue Gutenberg sidebar assets
	 * - save_post: Handle auto-generation on post save
	 * - meowseo_settings_tabs: Add AI settings tab
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register REST API endpoints.
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// Enqueue admin scripts and styles.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

		// Enqueue Gutenberg sidebar assets.
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_gutenberg_assets' ] );

		// Handle auto-generation on post save.
		add_action( 'save_post', [ $this, 'handle_auto_generation' ], 10, 3 );

		// Add AI settings tab.
		add_filter( 'meowseo_settings_tabs', [ $this, 'add_settings_tab' ] );
	}

	/**
	 * Register REST API routes.
	 *
	 * Registers all REST API endpoints for the AI module under the meowseo/v1 namespace.
	 * Delegates actual route registration to the AI_REST class if available.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		if ( isset( $this->rest ) ) {
			$this->rest->register_routes();
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * Enqueues JavaScript and CSS assets for the WordPress admin area.
	 * Only loads on MeowSEO admin pages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_scripts( string $hook_suffix ): void {
		// Only load on MeowSEO admin pages.
		if ( ! $this->is_meowseo_admin_page( $hook_suffix ) ) {
			return;
		}

		if ( isset( $this->settings ) ) {
			$this->settings->enqueue_admin_assets();
		}
	}

	/**
	 * Enqueue Gutenberg editor assets.
	 *
	 * Enqueues JavaScript and CSS assets for the Gutenberg block editor.
	 * Loads the AI Generator sidebar panel component.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_gutenberg_assets(): void {
		// Only load for post types that support the editor.
		$post_type = get_post_type();
		if ( ! post_type_supports( $post_type, 'editor' ) ) {
			return;
		}

		$this->enqueue_sidebar_assets();
	}

	/**
	 * Handle auto-generation on post save.
	 *
	 * Triggers automatic SEO content generation when a post is saved,
	 * if auto-generation is enabled in settings and conditions are met.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an existing post being updated.
	 * @return void
	 */
	public function handle_auto_generation( int $post_id, \WP_Post $post, bool $update ): void {
		// Skip if auto-generation is disabled.
		if ( ! $this->is_auto_generation_enabled() ) {
			return;
		}

		// Skip if not a valid post type for generation.
		if ( ! $this->is_valid_post_type( $post->post_type ) ) {
			return;
		}

		// Skip if this is an auto-save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Skip if user cannot edit the post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Clear generation cache on post update (Requirement 31.5).
		if ( $update ) {
			$this->generator->clear_cache( $post_id, 'all' );
		}

		// Check if this is first draft save (not an update).
		$auto_generate_on_first_save = $this->options->get( 'ai_auto_generate', false );
		if ( $auto_generate_on_first_save && ! $update ) {
			$this->trigger_auto_generation( $post_id, $post );
		}

		// Check if auto-generate image is enabled and post has no featured image.
		$auto_generate_image = $this->options->get( 'ai_auto_generate_image', false );
		if ( $auto_generate_image && ! has_post_thumbnail( $post_id ) ) {
			$this->trigger_image_generation( $post_id, $post );
		}
	}

	/**
	 * Add AI settings tab to MeowSEO settings.
	 *
	 * Adds the AI tab to the MeowSEO settings page tabs array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tabs Existing settings tabs.
	 * @return array Modified settings tabs with AI tab added.
	 */
	public function add_settings_tab( array $tabs ): array {
		$tabs['ai'] = [
			'title'    => __( 'AI', 'meowseo' ),
			'callback' => [ $this, 'render_settings_tab' ],
			'icon'     => 'dashicons-sparkles',
		];

		return $tabs;
	}

	/**
	 * Render the AI settings tab content.
	 *
	 * Delegates rendering to the AI_Settings class if available.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_settings_tab(): void {
		if ( isset( $this->settings ) ) {
			$this->settings->render_tab();
		} else {
			echo '<p>' . esc_html__( 'AI settings will be available after completing module setup.', 'meowseo' ) . '</p>';
		}
	}

	/**
	 * Check if current page is a MeowSEO admin page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return bool True if MeowSEO admin page.
	 */
	private function is_meowseo_admin_page( string $hook_suffix ): bool {
		// Check for MeowSEO settings page.
		if ( false !== strpos( $hook_suffix, 'meowseo' ) ) {
			return true;
		}

		// Check for MeowSEO admin pages.
		$screen = get_current_screen();
		if ( $screen && false !== strpos( $screen->id, 'meowseo' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if auto-generation is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if auto-generation is enabled.
	 */
	private function is_auto_generation_enabled(): bool {
		return (bool) $this->options->get( 'ai_auto_generate', false )
			|| (bool) $this->options->get( 'ai_auto_generate_image', false );
	}

	/**
	 * Check if post type is valid for generation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type Post type to check.
	 * @return bool True if valid for generation.
	 */
	private function is_valid_post_type( string $post_type ): bool {
		$valid_post_types = (array) apply_filters(
			'meowseo_ai_valid_post_types',
			[ 'post', 'page' ]
		);

		return in_array( $post_type, $valid_post_types, true );
	}

	/**
	 * Trigger auto-generation for a post.
	 *
	 * Runs generation in background without blocking the save operation.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	private function trigger_auto_generation( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $this->generator ) ) {
			return;
		}

		// Check minimum content length (300 words).
		$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
		if ( $word_count < 300 ) {
			Logger::info(
				'Auto-generation skipped: content too short',
				[
					'module'     => 'ai',
					'post_id'    => $post_id,
					'word_count' => $word_count,
					'minimum'    => 300,
				]
			);
			return;
		}

		// Log auto-generation attempt.
		Logger::info(
			'Auto-generation triggered',
			[
				'module'   => 'ai',
				'post_id'  => $post_id,
				'user_id'  => get_current_user_id(),
				'type'     => 'auto',
			]
		);

		// Run generation (non-blocking via async action if available).
		// For now, we'll use a direct call but log errors without blocking.
		try {
			$generate_image = $this->options->get( 'ai_auto_generate_image', false );
			$result = $this->generator->generate_all_meta( $post_id, $generate_image );

			if ( ! is_wp_error( $result ) ) {
				// Apply generated content to postmeta.
				$this->generator->apply_to_postmeta( $post_id, $result );

				Logger::info(
					'Auto-generation completed successfully',
					[
						'module'   => 'ai',
						'post_id'  => $post_id,
						'provider' => $result['provider'] ?? 'unknown',
						'has_image' => null !== $result['image'],
					]
				);
			} else {
				Logger::warning(
					'Auto-generation failed',
					[
						'module'   => 'ai',
						'post_id'  => $post_id,
						'error'    => $result->get_error_message(),
					]
				);
			}
		} catch ( \Exception $e ) {
			// Log error but don't block post save.
			Logger::error(
				'Auto-generation exception',
				[
					'module'   => 'ai',
					'post_id'  => $post_id,
					'error'    => $e->getMessage(),
				]
			);
		}
	}

	/**
	 * Trigger image generation for a post.
	 *
	 * Generates a featured image if the post doesn't have one.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	private function trigger_image_generation( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $this->generator ) ) {
			return;
		}

		// Check minimum content length (300 words).
		$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
		if ( $word_count < 300 ) {
			Logger::info(
				'Auto image generation skipped: content too short',
				[
					'module'     => 'ai',
					'post_id'    => $post_id,
					'word_count' => $word_count,
					'minimum'    => 300,
				]
			);
			return;
		}

		// Log auto image generation attempt.
		Logger::info(
			'Auto image generation triggered',
			[
				'module'   => 'ai',
				'post_id'  => $post_id,
				'user_id'  => get_current_user_id(),
				'type'     => 'auto_image',
			]
		);

		try {
			$result = $this->generator->generate_all_meta( $post_id, true );

			if ( ! is_wp_error( $result ) && ! empty( $result['image'] ) ) {
				$this->generator->apply_to_postmeta( $post_id, $result );

				Logger::info(
					'Auto image generation completed successfully',
					[
						'module'        => 'ai',
						'post_id'       => $post_id,
						'attachment_id' => $result['image']['attachment_id'] ?? null,
						'provider'      => $result['image']['provider'] ?? 'unknown',
					]
				);
			} else {
				Logger::warning(
					'Auto image generation failed',
					[
						'module'   => 'ai',
						'post_id'  => $post_id,
						'error'    => is_wp_error( $result ) ? $result->get_error_message() : 'No image generated',
					]
				);
			}
		} catch ( \Exception $e ) {
			// Log error but don't block post save.
			Logger::error(
				'Auto image generation exception',
				[
					'module'   => 'ai',
					'post_id'  => $post_id,
					'error'    => $e->getMessage(),
				]
			);
		}
	}

	/**
	 * Enqueue Gutenberg sidebar assets.
	 *
	 * Enqueues the JavaScript and CSS for the AI Generator sidebar panel.
	 * Uses wp-scripts generated asset files for proper dependency management.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function enqueue_sidebar_assets(): void {
		$asset_file = MEOWSEO_PATH . 'build/ai-sidebar.asset.php';
		$asset_data = file_exists( $asset_file ) ? require $asset_file : [
			'dependencies' => [
				'wp-plugins',
				'wp-edit-post',
				'wp-element',
				'wp-components',
				'wp-data',
				'wp-api-fetch',
				'wp-i18n',
			],
			'version'      => MEOWSEO_VERSION,
		];

		// Enqueue the AI sidebar script.
		wp_enqueue_script(
			'meowseo-ai-sidebar',
			MEOWSEO_URL . 'build/ai-sidebar.js',
			$asset_data['dependencies'],
			$asset_data['version'],
			true
		);

		// Enqueue the AI sidebar styles.
		wp_enqueue_style(
			'meowseo-ai-sidebar',
			MEOWSEO_URL . 'build/ai-sidebar.css',
			[],
			$asset_data['version']
		);

		// Localize script with data for the sidebar.
		// This data is passed to the JavaScript via the window.meowseoAiData object.
		wp_localize_script(
			'meowseo-ai-sidebar',
			'meowseoAiData',
			[
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'restUrl'      => rest_url( 'meowseo/v1' ),
				'postId'       => get_the_ID(),
				'postType'     => get_post_type(),
				'isConfigured' => $this->is_module_configured(),
				'settingsUrl'  => admin_url( 'admin.php?page=meowseo-settings&tab=ai' ),
			]
		);
	}

	/**
	 * Check if the AI module is properly configured.
	 *
	 * Returns true if at least one provider has a valid API key configured.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if module is configured.
	 */
	private function is_module_configured(): bool {
		$providers = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle' ];

		foreach ( $providers as $provider ) {
			$api_key = get_option( "meowseo_ai_{$provider}_api_key", '' );
			if ( ! empty( $api_key ) ) {
				return true;
			}
		}

		return false;
	}
}
