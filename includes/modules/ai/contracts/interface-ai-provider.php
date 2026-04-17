<?php
/**
 * AI Provider interface.
 *
 * Defines the contract for AI provider implementations.
 *
 * @package MeowSEO\Modules\AI\Contracts
 */

namespace MeowSEO\Modules\AI\Contracts;

use MeowSEO\Modules\AI\Exceptions\Provider_Exception;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface AI_Provider
 *
 * Defines the contract for AI provider implementations.
 *
 * All AI providers (Gemini, OpenAI, Anthropic, Imagen, DALL-E) must implement
 * this interface to ensure consistent behavior across the provider layer.
 *
 * @since 1.0.0
 */
interface AI_Provider {

	/**
	 * Get unique provider identifier.
	 *
	 * Returns a machine-readable slug for the provider (e.g., 'gemini', 'openai').
	 * This slug is used for provider ordering, status tracking, and logging.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider slug.
	 */
	public function get_slug(): string;

	/**
	 * Get display name for UI.
	 *
	 * Returns a human-readable label for the provider (e.g., 'Google Gemini', 'OpenAI').
	 * This label is displayed in the settings page and generation notifications.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider label.
	 */
	public function get_label(): string;

	/**
	 * Check if provider supports text generation.
	 *
	 * Text providers (Gemini, OpenAI, Anthropic) return true.
	 * Image-only providers (Imagen, DALL-E) return false.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if provider supports text generation.
	 */
	public function supports_text(): bool;

	/**
	 * Check if provider supports image generation.
	 *
	 * Image providers (OpenAI, Imagen, DALL-E) return true.
	 * Text-only providers (Gemini, Anthropic) return false.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if provider supports image generation.
	 */
	public function supports_image(): bool;

	/**
	 * Generate text content.
	 *
	 * Sends a prompt to the AI provider and returns the generated text content.
	 * The prompt should include all necessary context for SEO metadata generation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Generation prompt containing article context and instructions.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $model       Model to use (provider-specific).
	 *     @type float  $temperature Temperature for generation (0.0-1.0).
	 *     @type int    $max_tokens  Maximum tokens to generate.
	 * }
	 * @return array {
	 *     Generated content and usage information.
	 *
	 *     @type string $content The generated text content.
	 *     @type array  $usage {
	 *         Token usage information.
	 *
	 *         @type int $input_tokens  Number of input tokens used.
	 *         @type int $output_tokens Number of output tokens generated.
	 *     }
	 * }
	 * @throws Provider_Exception When generation fails.
	 * @throws \MeowSEO\Modules\AI\Exceptions\Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws \MeowSEO\Modules\AI\Exceptions\Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	public function generate_text( string $prompt, array $options = [] ): array;

	/**
	 * Generate image.
	 *
	 * Sends a prompt to the AI provider and returns the generated image URL.
	 * The prompt should describe the desired image for the article.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Image generation prompt describing the desired image.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $model         Model to use (provider-specific).
	 *     @type string $size          Image dimensions (e.g., '1200x630').
	 *     @type string $style         Visual style (e.g., 'professional', 'modern').
	 *     @type string $color_palette Color palette hint.
	 * }
	 * @return array {
	 *     Generated image data.
	 *
	 *     @type string $url            URL of the generated image.
	 *     @type string $revised_prompt The actual prompt used by the provider (if revised).
	 * }
	 * @throws Provider_Exception When generation fails.
	 * @throws \MeowSEO\Modules\AI\Exceptions\Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws \MeowSEO\Modules\AI\Exceptions\Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	public function generate_image( string $prompt, array $options = [] ): array;

	/**
	 * Validate API key by making test request.
	 *
	 * Makes a minimal request to the provider API to verify the API key is valid.
	 * This is used by the "Test Connection" feature in settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key API key to validate.
	 * @return bool True if API key is valid, false otherwise.
	 */
	public function validate_api_key( string $key ): bool;

	/**
	 * Get last error message.
	 *
	 * Returns the error message from the most recent failed operation.
	 * Returns null if no error has occurred or if the last operation succeeded.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Error message or null if no error.
	 */
	public function get_last_error(): ?string;
}
