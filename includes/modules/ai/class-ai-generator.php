<?php
/**
 * AI Generator.
 *
 * Handles prompt building and content generation for SEO metadata
 * and featured images.
 *
 * @package MeowSEO\Modules\AI
 */

namespace MeowSEO\Modules\AI;

use MeowSEO\Options;
use MeowSEO\Helpers\Logger;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AI_Generator
 *
 * Handles the generation of SEO metadata and featured images using AI providers.
 *
 * The Generator is responsible for:
 * - Building prompts for text and image generation
 * - Validating post content before generation
 * - Parsing JSON responses from AI providers
 * - Saving generated images to the media library
 * - Applying generated content to postmeta fields
 * - Caching generation results
 *
 * @since 1.0.0
 */
class AI_Generator {

	/**
	 * Provider Manager instance for accessing AI providers.
	 *
	 * @since 1.0.0
	 *
	 * @var AI_Provider_Manager
	 */
	private AI_Provider_Manager $provider_manager;

	/**
	 * Options instance for accessing settings.
	 *
	 * @since 1.0.0
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Cache group for generation results.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'meowseo';

	/**
	 * Cache key prefix for generation results.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const CACHE_KEY_PREFIX = 'ai_gen_';

	/**
	 * Default TTL for generation cache (24 hours in seconds).
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private const CACHE_TTL = 86400;

	/**
	 * Minimum word count for generation.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private const MIN_WORD_COUNT = 300;

	/**
	 * Maximum words to include in prompt.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private const MAX_PROMPT_WORDS = 2000;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AI_Provider_Manager $provider_manager Provider Manager instance.
	 * @param Options             $options          Options instance.
	 */
	public function __construct( AI_Provider_Manager $provider_manager, Options $options ) {
		$this->provider_manager = $provider_manager;
		$this->options = $options;
	}

	/**
	 * Generate all SEO metadata for a post.
	 *
	 * Validates the post exists and has sufficient content, then generates
	 * SEO metadata using the configured AI providers. Optionally generates
	 * a featured image.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $post_id        Post ID.
	 * @param bool $generate_image Optional. Whether to generate featured image. Default false.
	 * @param bool $bypass_cache   Optional. Whether to bypass cache. Default false.
	 * @return array|WP_Error {
	 *     Generated content on success, or WP_Error on failure.
	 *
	 *     @type array  $text     Generated text fields.
	 *     @type string $provider Provider slug used for text generation.
	 *     @type array  $image    Generated image data (if requested).
	 * }
	 */
	public function generate_all_meta( int $post_id, bool $generate_image = false, bool $bypass_cache = false ) {
		// Check cache first.
		if ( ! $bypass_cache ) {
			$cached = $this->get_cached_result( $post_id, 'all' );
			if ( null !== $cached ) {
				Logger::info(
					'Returning cached generation result',
					[
						'module'  => 'ai',
						'post_id' => $post_id,
					]
				);
				return $cached;
			}
		}

		// Validate post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			Logger::error(
				'Post not found for generation',
				[
					'module'  => 'ai',
					'post_id' => $post_id,
				]
			);
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'meowseo' )
			);
		}

		// Check minimum content length.
		$word_count = $this->count_words( $post->post_content );
		if ( $word_count < self::MIN_WORD_COUNT ) {
			Logger::warning(
				'Content too short for generation',
				[
					'module'     => 'ai',
					'post_id'    => $post_id,
					'word_count' => $word_count,
					'minimum'    => self::MIN_WORD_COUNT,
				]
			);
			return new WP_Error(
				'content_too_short',
				sprintf(
					/* translators: %d: minimum word count */
					__( 'Content must be at least %d words for generation.', 'meowseo' ),
					self::MIN_WORD_COUNT
				)
			);
		}

		// Build prompt.
		$prompt = $this->build_text_prompt( $post );

		// Generate text content.
		$text_result = $this->provider_manager->generate_text( $prompt );

		if ( is_wp_error( $text_result ) ) {
			return $text_result;
		}

		// Parse JSON response.
		$parsed = $this->parse_json_response( $text_result['content'] );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$result = [
			'text'     => $parsed,
			'provider' => $text_result['provider'],
			'image'    => null,
		];

		// Generate image if requested.
		if ( $generate_image ) {
			$seo_title = $parsed['seo_title'] ?? $post->post_title;
			$image_prompt = $this->build_image_prompt( $post, $seo_title );
			$image_result = $this->provider_manager->generate_image( $image_prompt );

			if ( ! is_wp_error( $image_result ) ) {
				$attachment_id = $this->save_image_to_media_library(
					$image_result['url'],
					$post_id,
					$post->post_title
				);

				if ( $attachment_id ) {
					$result['image'] = [
						'attachment_id' => $attachment_id,
						'url'           => wp_get_attachment_url( $attachment_id ),
						'provider'      => $image_result['provider'],
					];
				}
			} else {
				// Log image generation failure but don't fail the whole request.
				Logger::warning(
					'Image generation failed, continuing without image',
					[
						'module'  => 'ai',
						'post_id' => $post_id,
						'error'   => $image_result->get_error_message(),
					]
				);
			}
		}

		// Cache the result.
		$this->cache_result( $post_id, 'all', $result );

		Logger::info(
			'SEO metadata generated successfully',
			[
				'module'   => 'ai',
				'post_id'  => $post_id,
				'provider' => $result['provider'],
				'has_image' => null !== $result['image'],
			]
		);

		return $result;
	}

	/**
	 * Build text generation prompt.
	 *
	 * Constructs a comprehensive prompt for SEO metadata generation,
	 * including post content, taxonomy, and output format specifications.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Generation prompt.
	 */
	public function build_text_prompt( $post ): string {
		// Get post data.
		$title = $post->post_title;
		$content = wp_strip_all_tags( $post->post_content );
		$content = wp_trim_words( $content, self::MAX_PROMPT_WORDS );
		$excerpt = $post->post_excerpt ?: wp_trim_words( $content, 50 );

		// Get taxonomy.
		$categories = wp_get_post_categories( $post->ID, [ 'fields' => 'names' ] );
		$tags = wp_get_post_tags( $post->ID, [ 'fields' => 'names' ] );

		// Get settings.
		$language = $this->options->get( 'ai_output_language', 'auto' );
		$custom_instructions = $this->options->get( 'ai_custom_instructions', '' );

		// Build prompt.
		$prompt = "You are an SEO expert. Generate SEO metadata for the following article.\n\n";
		$prompt .= "Article Title: {$title}\n";
		$prompt .= "Article Excerpt: {$excerpt}\n";
		$prompt .= "Article Content: {$content}\n";

		// Add categories if available.
		if ( ! empty( $categories ) && is_array( $categories ) ) {
			$prompt .= 'Categories: ' . implode( ', ', $categories ) . "\n";
		}

		// Add tags if available.
		if ( ! empty( $tags ) && is_array( $tags ) ) {
			$tag_names = array_map(
				function ( $tag ) {
					return is_object( $tag ) ? $tag->name : $tag;
				},
				$tags
			);
			$prompt .= 'Tags: ' . implode( ', ', $tag_names ) . "\n";
		}

		// Add language preference.
		if ( 'auto' !== $language ) {
			$language_names = [
				'id' => 'Indonesian',
				'en' => 'English',
			];
			$lang_name = $language_names[ $language ] ?? $language;
			$prompt .= "\nLanguage: Generate all content in {$lang_name}.\n";
		}

		// Add custom instructions.
		if ( ! empty( $custom_instructions ) ) {
			$prompt .= "\nCustom Instructions: {$custom_instructions}\n";
		}

		// Add output format specification.
		$prompt .= "\nReturn ONLY a JSON object with these fields (no markdown, no explanation):\n";
		$prompt .= "{\n";
		$prompt .= '  "seo_title": "SEO-optimized title (max 60 characters)",' . "\n";
		$prompt .= '  "seo_description": "Meta description for search results (140-160 characters)",' . "\n";
		$prompt .= '  "focus_keyword": "Primary keyword for the article (single keyword or phrase)",' . "\n";
		$prompt .= '  "og_title": "Engaging title for social media sharing",' . "\n";
		$prompt .= '  "og_description": "Description for Open Graph (100-200 characters)",' . "\n";
		$prompt .= '  "twitter_title": "Title optimized for Twitter Card display",' . "\n";
		$prompt .= '  "twitter_description": "Conversational description for Twitter (max 200 characters)",' . "\n";
		$prompt .= '  "direct_answer": "Concise answer for Google AI Overviews (300-450 characters)",' . "\n";
		$prompt .= '  "schema_type": "One of: Article, FAQPage, HowTo, LocalBusiness, Product",' . "\n";
		$prompt .= '  "schema_justification": "One sentence explaining why this schema type was chosen",' . "\n";
		$prompt .= '  "slug_suggestion": "SEO-friendly URL slug (lowercase, hyphens, no special characters)",' . "\n";
		$prompt .= '  "secondary_keywords": ["keyword1", "keyword2", "keyword3"] (3-5 supporting keywords)' . "\n";
		$prompt .= "}\n";

		return $prompt;
	}

	/**
	 * Build image generation prompt.
	 *
	 * Constructs a prompt for featured image generation with style
	 * and format requirements.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post      Post object.
	 * @param string   $seo_title SEO title for context.
	 * @return string Image generation prompt.
	 */
	public function build_image_prompt( $post, string $seo_title = '' ): string {
		// Use SEO title or post title for context.
		$title = ! empty( $seo_title ) ? $seo_title : $post->post_title;

		// Get settings.
		$style = $this->options->get( 'ai_image_style', 'professional' );
		$color_palette = $this->options->get( 'ai_image_color_palette', '' );

		// Build prompt.
		$prompt = "Generate a featured image for an article about: {$title}\n\n";

		// Add style.
		$style_descriptions = [
			'professional'      => 'Clean, corporate, business-appropriate style',
			'modern'            => 'Contemporary, sleek, minimalist modern style',
			'minimal'           => 'Simple, clean, with plenty of white space',
			'illustrative'      => 'Artistic illustration style, hand-drawn feel',
			'photography-style' => 'Photorealistic, high-quality photography style',
		];

		$style_desc = $style_descriptions[ $style ] ?? $style;
		$prompt .= "Style: {$style_desc}\n";

		// Add color palette if set.
		if ( ! empty( $color_palette ) ) {
			$prompt .= "Color palette: {$color_palette}\n";
		}

		// Add format requirements.
		$prompt .= "\nRequirements:\n";
		$prompt .= "- Clean, professional, suitable for web publishing\n";
		$prompt .= "- No text overlay or watermarks\n";
		$prompt .= "- Wide format 16:9 aspect ratio (1200x630 pixels)\n";
		$prompt .= "- High resolution\n";
		$prompt .= "- PNG format preferred\n";

		return $prompt;
	}

	/**
	 * Parse JSON response from AI.
	 *
	 * Handles markdown code blocks and validates required fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Raw response content.
	 * @return array|WP_Error Parsed data or error.
	 */
	private function parse_json_response( string $content ) {
		// Remove potential markdown code blocks.
		$content = preg_replace( '/^```json\s*/m', '', $content );
		$content = preg_replace( '/^```\s*/m', '', $content );
		$content = trim( $content );

		// Parse JSON.
		$parsed = json_decode( $content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			Logger::error(
				'Failed to parse AI JSON response',
				[
					'module' => 'ai',
					'error'  => json_last_error_msg(),
				]
			);
			return new WP_Error(
				'json_parse_error',
				__( 'Failed to parse AI response. Please try again.', 'meowseo' )
			);
		}

		// Validate required fields.
		$required = [ 'seo_title', 'seo_description', 'focus_keyword' ];
		$missing = [];

		foreach ( $required as $field ) {
			if ( empty( $parsed[ $field ] ) ) {
				$missing[] = $field;
			}
		}

		if ( ! empty( $missing ) ) {
			Logger::error(
				'Missing required fields in AI response',
				[
					'module'  => 'ai',
					'missing' => $missing,
				]
			);
			return new WP_Error(
				'missing_field',
				sprintf(
					/* translators: %s: list of missing fields */
					__( 'Missing required fields: %s', 'meowseo' ),
					implode( ', ', $missing )
				)
			);
		}

		// Sanitize all values.
		return $this->sanitize_parsed_response( $parsed );
	}

	/**
	 * Sanitize parsed response values.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Parsed response data.
	 * @return array Sanitized data.
	 */
	private function sanitize_parsed_response( array $data ): array {
		$sanitized = [];

		// Define sanitization rules for each field.
		$text_fields = [
			'seo_title',
			'seo_description',
			'focus_keyword',
			'og_title',
			'og_description',
			'twitter_title',
			'twitter_description',
			'direct_answer',
			'schema_type',
			'schema_justification',
			'slug_suggestion',
		];

		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}

		// Handle secondary_keywords array.
		if ( isset( $data['secondary_keywords'] ) && is_array( $data['secondary_keywords'] ) ) {
			$sanitized['secondary_keywords'] = array_map( 'sanitize_text_field', $data['secondary_keywords'] );
		}

		return $sanitized;
	}

	/**
	 * Save generated image to media library.
	 *
	 * Downloads the image from the URL and saves it to the WordPress
	 * media library as an attachment.
	 *
	 * @since 1.0.0
	 *
	 * @param string $image_url Image URL.
	 * @param int    $post_id   Post ID to attach image to.
	 * @param string $title     Title for the image.
	 * @return int|null Attachment ID or null on failure.
	 */
	private function save_image_to_media_library( string $image_url, int $post_id, string $title ): ?int {
		// Load required files.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download image.
		$response = wp_remote_get(
			$image_url,
			[
				'timeout' => 60,
				'sslverify' => false, // Some AI providers may have SSL issues.
			]
		);

		if ( is_wp_error( $response ) ) {
			Logger::error(
				'Failed to download generated image',
				[
					'module'  => 'ai',
					'post_id' => $post_id,
					'error'   => $response->get_error_message(),
				]
			);
			return null;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			Logger::error(
				'Failed to download generated image: HTTP error',
				[
					'module'  => 'ai',
					'post_id' => $post_id,
					'code'    => $response_code,
				]
			);
			return null;
		}

		$image_data = wp_remote_retrieve_body( $response );

		if ( empty( $image_data ) ) {
			Logger::error(
				'Empty image data received',
				[
					'module'  => 'ai',
					'post_id' => $post_id,
				]
			);
			return null;
		}

		// Save to temp file.
		$temp_file = wp_tempnam( 'ai-generated-image.png' );

		if ( ! $temp_file ) {
			Logger::error(
				'Failed to create temp file for image',
				[
					'module'  => 'ai',
					'post_id' => $post_id,
				]
			);
			return null;
		}

		$bytes_written = file_put_contents( $temp_file, $image_data );

		if ( false === $bytes_written ) {
			Logger::error(
				'Failed to write image data to temp file',
				[
					'module'  => 'ai',
					'post_id' => $post_id,
				]
			);
			@unlink( $temp_file );
			return null;
		}

		// Upload to media library.
		$file_array = [
			'name'     => sanitize_file_name( $title . '-featured.png' ),
			'tmp_name' => $temp_file,
		];

		$attachment_id = media_handle_sideload( $file_array, $post_id, $title );

		if ( is_wp_error( $attachment_id ) ) {
			Logger::error(
				'Failed to upload image to media library',
				[
					'module'  => 'ai',
					'post_id' => $post_id,
					'error'   => $attachment_id->get_error_message(),
				]
			);
			@unlink( $temp_file );
			return null;
		}

		// Set as featured image.
		set_post_thumbnail( $post_id, $attachment_id );

		// Set alt text.
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $title );

		Logger::info(
			'Generated image saved to media library',
			[
				'module'        => 'ai',
				'post_id'       => $post_id,
				'attachment_id' => $attachment_id,
			]
		);

		return $attachment_id;
	}

	/**
	 * Apply generated content to postmeta.
	 *
	 * Maps generated fields to their postmeta keys and saves them.
	 * Respects the overwrite behavior setting.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $post_id Post ID.
	 * @param array $content Generated content.
	 * @param array $fields  Optional. Specific fields to apply. Default all.
	 * @return bool True on success.
	 */
	public function apply_to_postmeta( int $post_id, array $content, array $fields = [] ): bool {
		// Define field mapping.
		$field_mapping = [
			'seo_title'           => '_meowseo_title',
			'seo_description'     => '_meowseo_description',
			'focus_keyword'       => '_meowseo_focus_keyword',
			'og_title'            => '_meowseo_og_title',
			'og_description'      => '_meowseo_og_description',
			'twitter_title'       => '_meowseo_twitter_title',
			'twitter_description' => '_meowseo_twitter_description',
			'schema_type'         => '_meowseo_schema_type',
		];

		$overwrite = $this->options->get( 'ai_overwrite_behavior', 'ask' );

		foreach ( $field_mapping as $source => $target ) {
			// Skip if not in provided content.
			if ( ! isset( $content[ $source ] ) || empty( $content[ $source ] ) ) {
				continue;
			}

			// Skip if specific fields requested and this isn't one of them.
			if ( ! empty( $fields ) && ! in_array( $source, $fields, true ) ) {
				continue;
			}

			$existing = get_post_meta( $post_id, $target, true );

			// Check overwrite behavior.
			if ( 'never' === $overwrite && ! empty( $existing ) ) {
				continue;
			}

			if ( 'ask' === $overwrite && ! empty( $existing ) ) {
				// When 'ask', only overwrite if explicitly requested via fields.
				if ( empty( $fields ) || ! in_array( $source, $fields, true ) ) {
					continue;
				}
			}

			// Update postmeta.
			update_post_meta( $post_id, $target, $content[ $source ] );
		}

		// Handle secondary keywords.
		if ( isset( $content['secondary_keywords'] ) && is_array( $content['secondary_keywords'] ) ) {
			$should_update = true;
			$existing = get_post_meta( $post_id, '_meowseo_secondary_keywords', true );

			if ( 'never' === $overwrite && ! empty( $existing ) ) {
				$should_update = false;
			} elseif ( 'ask' === $overwrite && ! empty( $existing ) ) {
				if ( empty( $fields ) || ! in_array( 'secondary_keywords', $fields, true ) ) {
					$should_update = false;
				}
			}

			if ( $should_update ) {
				update_post_meta( $post_id, '_meowseo_secondary_keywords', $content['secondary_keywords'] );
			}
		}

		// Handle slug suggestion.
		if ( isset( $content['slug_suggestion'] ) && ! empty( $content['slug_suggestion'] ) ) {
			$should_update = true;
			$existing = get_post_meta( $post_id, '_meowseo_slug_suggestion', true );

			if ( 'never' === $overwrite && ! empty( $existing ) ) {
				$should_update = false;
			} elseif ( 'ask' === $overwrite && ! empty( $existing ) ) {
				if ( empty( $fields ) || ! in_array( 'slug_suggestion', $fields, true ) ) {
					$should_update = false;
				}
			}

			if ( $should_update ) {
				update_post_meta( $post_id, '_meowseo_slug_suggestion', $content['slug_suggestion'] );
			}
		}

		// Handle direct answer.
		if ( isset( $content['direct_answer'] ) && ! empty( $content['direct_answer'] ) ) {
			$should_update = true;
			$existing = get_post_meta( $post_id, '_meowseo_direct_answer', true );

			if ( 'never' === $overwrite && ! empty( $existing ) ) {
				$should_update = false;
			} elseif ( 'ask' === $overwrite && ! empty( $existing ) ) {
				if ( empty( $fields ) || ! in_array( 'direct_answer', $fields, true ) ) {
					$should_update = false;
				}
			}

			if ( $should_update ) {
				update_post_meta( $post_id, '_meowseo_direct_answer', $content['direct_answer'] );
			}
		}

		// Handle schema justification.
		if ( isset( $content['schema_justification'] ) && ! empty( $content['schema_justification'] ) ) {
			$should_update = true;
			$existing = get_post_meta( $post_id, '_meowseo_schema_justification', true );

			if ( 'never' === $overwrite && ! empty( $existing ) ) {
				$should_update = false;
			} elseif ( 'ask' === $overwrite && ! empty( $existing ) ) {
				if ( empty( $fields ) || ! in_array( 'schema_justification', $fields, true ) ) {
					$should_update = false;
				}
			}

			if ( $should_update ) {
				update_post_meta( $post_id, '_meowseo_schema_justification', $content['schema_justification'] );
			}
		}

		// Handle image fields.
		if ( ! empty( $content['image']['url'] ) ) {
			update_post_meta( $post_id, '_meowseo_og_image', $content['image']['url'] );
			update_post_meta( $post_id, '_meowseo_twitter_image', $content['image']['url'] );
		}

		Logger::info(
			'Generated content applied to postmeta',
			[
				'module'  => 'ai',
				'post_id' => $post_id,
			]
		);

		return true;
	}

	/**
	 * Cache generation result.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $type    Generation type.
	 * @param array  $result  Result to cache.
	 * @return void
	 */
	private function cache_result( int $post_id, string $type, array $result ): void {
		$cache_key = self::CACHE_KEY_PREFIX . "{$post_id}_{$type}";
		wp_cache_set( $cache_key, $result, self::CACHE_GROUP, self::CACHE_TTL );
	}

	/**
	 * Get cached generation result.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $type    Generation type.
	 * @return array|null Cached result or null if not found.
	 */
	private function get_cached_result( int $post_id, string $type ): ?array {
		$cache_key = self::CACHE_KEY_PREFIX . "{$post_id}_{$type}";
		$cached = wp_cache_get( $cache_key, self::CACHE_GROUP );

		return false !== $cached ? $cached : null;
	}

	/**
	 * Clear cached generation result.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $type    Optional. Generation type. Default 'all'.
	 * @return bool True if cache was cleared.
	 */
	public function clear_cache( int $post_id, string $type = 'all' ): bool {
		$cache_key = self::CACHE_KEY_PREFIX . "{$post_id}_{$type}";
		return wp_cache_delete( $cache_key, self::CACHE_GROUP );
	}

	/**
	 * Count words in content.
	 *
	 * Strips HTML tags and counts words using WordPress function.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Content to count.
	 * @return int Word count.
	 */
	private function count_words( string $content ): int {
		// Strip all HTML tags.
		$text = wp_strip_all_tags( $content );

		// Use WordPress word count.
		return str_word_count( $text );
	}

	/**
	 * Generate only text content for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $post_id      Post ID.
	 * @param bool $bypass_cache Optional. Whether to bypass cache. Default false.
	 * @return array|WP_Error Generated text content or error.
	 */
	public function generate_text_only( int $post_id, bool $bypass_cache = false ) {
		return $this->generate_all_meta( $post_id, false, $bypass_cache );
	}

	/**
	 * Generate only image for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int         $post_id      Post ID.
	 * @param string|null $custom_prompt Optional custom prompt for image generation.
	 * @param bool        $bypass_cache Optional. Whether to bypass cache. Default false.
	 * @return array|WP_Error Generated image data or error.
	 */
	public function generate_image_only( int $post_id, ?string $custom_prompt = null, bool $bypass_cache = false ) {
		// Check cache first.
		if ( ! $bypass_cache ) {
			$cached = $this->get_cached_result( $post_id, 'image' );
			if ( null !== $cached ) {
				return $cached;
			}
		}

		// Validate post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'meowseo' )
			);
		}

		// Build prompt.
		if ( ! empty( $custom_prompt ) ) {
			$prompt = $custom_prompt;
		} else {
			// Get SEO title if available.
			$seo_title = get_post_meta( $post_id, '_meowseo_title', true );
			$prompt = $this->build_image_prompt( $post, $seo_title ?: $post->post_title );
		}

		// Generate image.
		$image_result = $this->provider_manager->generate_image( $prompt );

		if ( is_wp_error( $image_result ) ) {
			return $image_result;
		}

		// Save to media library.
		$attachment_id = $this->save_image_to_media_library(
			$image_result['url'],
			$post_id,
			$post->post_title
		);

		if ( ! $attachment_id ) {
			return new WP_Error(
				'image_save_failed',
				__( 'Failed to save generated image.', 'meowseo' )
			);
		}

		$result = [
			'attachment_id' => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
			'provider'      => $image_result['provider'],
		];

		// Cache the result.
		$this->cache_result( $post_id, 'image', $result );

		return $result;
	}
}
