<?php
/**
 * Abstract OpenAI-Compatible AI Provider.
 *
 * Provides a base implementation for AI providers that use OpenAI-compatible APIs.
 *
 * @package MeowSEO\Modules\AI\Providers
 */

namespace MeowSEO\Modules\AI\Providers;

use MeowSEO\Modules\AI\Contracts\AI_Provider;
use MeowSEO\Modules\AI\Exceptions\Provider_Exception;
use MeowSEO\Modules\AI\Exceptions\Provider_Rate_Limit_Exception;
use MeowSEO\Modules\AI\Exceptions\Provider_Auth_Exception;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Class Provider_OpenAI_Compatible
 *
 * Base implementation for AI providers that follow the OpenAI API format.
 *
 * This abstract class provides shared functionality for providers like DeepSeek,
 * GLM (Zhipu AI), and Qwen (Alibaba) that use OpenAI-compatible chat completions
 * and image generation APIs.
 *
 * Subclasses must implement the abstract methods to provide provider-specific
 * configuration (API URLs, model names, provider identity).
 *
 * @since 1.0.0
 */
abstract class Provider_OpenAI_Compatible implements AI_Provider {

	/**
	 * Request timeout in seconds for text generation.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const TEXT_TIMEOUT = 60;

	/**
	 * Request timeout in seconds for image generation.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const IMAGE_TIMEOUT = 90;

	/**
	 * Request timeout in seconds for API key validation.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const VALIDATION_TIMEOUT = 10;

	/**
	 * Default temperature for text generation.
	 *
	 * @since 1.0.0
	 *
	 * @var float
	 */
	protected const DEFAULT_TEMPERATURE = 0.7;

	/**
	 * Default maximum tokens for text generation.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected const DEFAULT_MAX_TOKENS = 2048;

	/**
	 * Default image size for image generation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected const DEFAULT_IMAGE_SIZE = '1024x1024';

	/**
	 * The API key for authentication.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $api_key;

	/**
	 * The last error message.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	protected ?string $last_error = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key The API key for the provider.
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Get the API base URL for chat completions.
	 *
	 * Must return the base URL for the provider's OpenAI-compatible chat completions API.
	 * Example: 'https://api.deepseek.com/v1'
	 *
	 * @since 1.0.0
	 *
	 * @return string The API base URL.
	 */
	abstract protected function get_api_base_url(): string;

	/**
	 * Get the default text model for generation.
	 *
	 * Must return the model identifier for text generation.
	 * Example: 'deepseek-chat', 'glm-4.7-flash', 'qwen-plus'
	 *
	 * @since 1.0.0
	 *
	 * @return string The text model identifier.
	 */
	abstract protected function get_text_model(): string;

	/**
	 * Get the default image model for generation.
	 *
	 * Must return the model identifier for image generation.
	 * Example: 'janus-pro-7b', 'glm-image', 'qwen-image'
	 *
	 * @since 1.0.0
	 *
	 * @return string The image model identifier.
	 */
	abstract protected function get_image_model(): string;

	/**
	 * Get the image generation API URL.
	 *
	 * Must return the full URL for the image generation endpoint.
	 * Defaults to get_api_base_url() . '/images/generations'.
	 *
	 * @since 1.0.0
	 *
	 * @return string The image API URL.
	 */
	protected function get_image_api_url(): string {
		return $this->get_api_base_url() . '/images/generations';
	}

	/**
	 * Get authentication headers for API requests.
	 *
	 * Can be overridden by subclasses to use different header formats.
	 * Default is standard Bearer token authentication.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array of header names to values.
	 */
	protected function get_auth_headers(): array {
		return [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->api_key,
		];
	}

	/**
	 * Check if provider supports text generation.
	 *
	 * Default implementation returns true. Override in subclasses if needed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if provider supports text generation.
	 */
	public function supports_text(): bool {
		return true;
	}

	/**
	 * Check if provider supports image generation.
	 *
	 * Default implementation returns true. Override in subclasses if needed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if provider supports image generation.
	 */
	public function supports_image(): bool {
		return true;
	}

	/**
	 * Generate text content.
	 *
	 * Sends a prompt to the provider's chat completions API and returns the generated text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Generation prompt containing article context and instructions.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $model       Model to use. Default from get_text_model().
	 *     @type float  $temperature Temperature for generation (0.0-2.0). Default 0.7.
	 *     @type int    $max_tokens  Maximum tokens to generate. Default 2048.
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
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	public function generate_text( string $prompt, array $options = [] ): array {
		$this->last_error = null;

		$request_body = [
			'model'       => $options['model'] ?? $this->get_text_model(),
			'messages'    => [
				[
					'role'    => 'user',
					'content' => $prompt,
				],
			],
			'temperature' => $options['temperature'] ?? self::DEFAULT_TEMPERATURE,
			'max_tokens'  => $options['max_tokens'] ?? self::DEFAULT_MAX_TOKENS,
		];

		$response = wp_remote_post(
			$this->get_api_base_url() . '/chat/completions',
			[
				'headers' => $this->get_auth_headers(),
				'body'    => wp_json_encode( $request_body ),
				'timeout' => self::TEXT_TIMEOUT,
			]
		);

		return $this->parse_text_response( $response );
	}

	/**
	 * Generate image.
	 *
	 * Sends a prompt to the provider's image generation API and returns the generated image URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Image generation prompt describing the desired image.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $model Model to use. Default from get_image_model().
	 *     @type string $size  Image dimensions. Default '1024x1024'.
	 * }
	 * @return array {
	 *     Generated image data.
	 *
	 *     @type string $url            URL of the generated image.
	 *     @type string $revised_prompt The actual prompt used by the provider (if revised).
	 * }
	 * @throws Provider_Exception When generation fails.
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	public function generate_image( string $prompt, array $options = [] ): array {
		$this->last_error = null;

		$request_body = [
			'model'           => $options['model'] ?? $this->get_image_model(),
			'prompt'          => $prompt,
			'n'               => 1,
			'size'            => $options['size'] ?? self::DEFAULT_IMAGE_SIZE,
			'response_format' => 'url',
		];

		$response = wp_remote_post(
			$this->get_image_api_url(),
			[
				'headers' => $this->get_auth_headers(),
				'body'    => wp_json_encode( $request_body ),
				'timeout' => self::IMAGE_TIMEOUT,
			]
		);

		return $this->parse_image_response( $response );
	}

	/**
	 * Validate API key by making test request.
	 *
	 * Makes a minimal request to the provider's API to verify the API key is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key API key to validate.
	 * @return bool True if API key is valid, false otherwise.
	 */
	public function validate_api_key( string $key ): bool {
		$this->last_error = null;

		// Temporarily set the API key for validation.
		$original_key   = $this->api_key;
		$this->api_key  = $key;

		// Make a minimal request to validate the key.
		$response = wp_remote_post(
			$this->get_api_base_url() . '/chat/completions',
			[
				'headers' => $this->get_auth_headers(),
				'body'    => wp_json_encode( [
					'model'      => $this->get_text_model(),
					'messages'   => [
						[
							'role'    => 'user',
							'content' => 'test',
						],
					],
					'max_tokens' => 5,
				] ),
				'timeout' => self::VALIDATION_TIMEOUT,
			]
		);

		// Restore original API key.
		$this->api_key = $original_key;

		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );

		// 200 indicates valid API key.
		if ( 200 === $code ) {
			return true;
		}

		// 429 indicates rate limit, but key is valid.
		if ( 429 === $code ) {
			return true;
		}

		// Otherwise, it's an error (400, 401, 403, etc).
		$body             = json_decode( wp_remote_retrieve_body( $response ), true );
		$this->last_error = $body['error']['message'] ?? "HTTP Error {$code}";
		
		return false;
	}

	/**
	 * Get last error message.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Error message or null if no error.
	 */
	public function get_last_error(): ?string {
		return $this->last_error;
	}

	/**
	 * Parse the text API response.
	 *
	 * Handles error cases and extracts the generated content from successful responses.
	 *
	 * @since 1.0.0
	 *
	 * @param array|\WP_Error $response The response from wp_remote_post.
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
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	protected function parse_text_response( $response ): array {
		// Handle WP_Error (network/timeout errors).
		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			throw new Provider_Exception(
				$this->last_error ?? 'Request failed',
				$this->get_slug()
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle error status codes.
		$this->handle_error_codes( $code, $body );

		// Handle other non-200 responses.
		if ( 200 !== $code ) {
			$error_message    = $body['error']['message'] ?? "HTTP {$code}";
			$this->last_error = $error_message;
			throw new Provider_Exception(
				$error_message,
				$this->get_slug(),
				$code
			);
		}

		// Extract generated text from response.
		if ( empty( $body['choices'][0]['message']['content'] ) ) {
			$this->last_error = 'Empty response from API';
			throw new Provider_Exception(
				$this->last_error,
				$this->get_slug()
			);
		}

		return [
			'content' => $body['choices'][0]['message']['content'],
			'usage'   => [
				'input_tokens'  => $body['usage']['prompt_tokens'] ?? 0,
				'output_tokens' => $body['usage']['completion_tokens'] ?? 0,
			],
		];
	}

	/**
	 * Parse the image API response.
	 *
	 * Handles error cases and extracts the generated image URL from successful responses.
	 *
	 * @since 1.0.0
	 *
	 * @param array|\WP_Error $response The response from wp_remote_post.
	 * @return array {
	 *     Generated image data.
	 *
	 *     @type string $url            URL of the generated image.
	 *     @type string $revised_prompt The actual prompt used by the provider (if revised).
	 * }
	 * @throws Provider_Exception When generation fails.
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	protected function parse_image_response( $response ): array {
		// Handle WP_Error (network/timeout errors).
		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			throw new Provider_Exception(
				$this->last_error ?? 'Request failed',
				$this->get_slug()
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle error status codes.
		$this->handle_error_codes( $code, $body );

		// Handle other non-200 responses.
		if ( 200 !== $code ) {
			$error_message    = $body['error']['message'] ?? "HTTP {$code}";
			$this->last_error = $error_message;
			throw new Provider_Exception(
				$error_message,
				$this->get_slug(),
				$code
			);
		}

		// Extract generated image URL from response.
		if ( empty( $body['data'][0]['url'] ) ) {
			$this->last_error = 'Empty response from Image API';
			throw new Provider_Exception(
				$this->last_error,
				$this->get_slug()
			);
		}

		return [
			'url'            => $body['data'][0]['url'],
			'revised_prompt' => $body['data'][0]['revised_prompt'] ?? null,
		];
	}

	/**
	 * Handle common error status codes.
	 *
	 * Checks for rate limit and authentication errors and throws appropriate exceptions.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $code The HTTP response code.
	 * @param array $body The parsed response body.
	 *
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	protected function handle_error_codes( int $code, array $body ): void {
		// Handle rate limit (HTTP 429).
		if ( 429 === $code ) {
			// Default retry-after value.
			$retry_after = 60;

			// Check for retry-after in error body.
			if ( isset( $body['error']['retry_after'] ) ) {
				$retry_after = (int) $body['error']['retry_after'];
			}

			throw new Provider_Rate_Limit_Exception( $this->get_slug(), $retry_after );
		}

		// Handle authentication error (HTTP 401 or 403).
		if ( 401 === $code || 403 === $code ) {
			throw new Provider_Auth_Exception( $this->get_slug() );
		}
	}
}
