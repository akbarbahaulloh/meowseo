<?php
/**
 * OpenAI AI Provider.
 *
 * Implements the AI_Provider interface for OpenAI's GPT and DALL-E APIs.
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
 * Class Provider_OpenAI
 *
 * AI provider implementation for OpenAI API.
 *
 * Supports both text generation (GPT-4o-mini) and image generation (DALL-E-3).
 *
 * @since 1.0.0
 */
class Provider_OpenAI implements AI_Provider {

	/**
	 * OpenAI Chat Completions API endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const TEXT_API_URL = 'https://api.openai.com/v1/chat/completions';

	/**
	 * OpenAI Image Generations API endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const IMAGE_API_URL = 'https://api.openai.com/v1/images/generations';

	/**
	 * OpenAI Models API endpoint URL for validation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const MODELS_API_URL = 'https://api.openai.com/v1/models';

	/**
	 * Default text model for generation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_TEXT_MODEL = 'gpt-4o-mini';

	/**
	 * Default image model for generation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_IMAGE_MODEL = 'dall-e-3';

	/**
	 * Request timeout in seconds.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private const TIMEOUT = 60;

	/**
	 * The API key for authentication.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * The last error message.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	private ?string $last_error = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key The OpenAI API key.
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Get unique provider identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider slug 'openai'.
	 */
	public function get_slug(): string {
		return 'openai';
	}

	/**
	 * Get display name for UI.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider label 'OpenAI'.
	 */
	public function get_label(): string {
		return 'OpenAI';
	}

	/**
	 * Check if provider supports text generation.
	 *
	 * OpenAI supports text generation via GPT models.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always true for OpenAI.
	 */
	public function supports_text(): bool {
		return true;
	}

	/**
	 * Check if provider supports image generation.
	 *
	 * OpenAI supports image generation via DALL-E.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always true for OpenAI.
	 */
	public function supports_image(): bool {
		return true;
	}

	/**
	 * Generate text content.
	 *
	 * Sends a prompt to the OpenAI Chat Completions API and returns the generated text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Generation prompt containing article context and instructions.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $model       Model to use (e.g., 'gpt-4o-mini', 'gpt-4o'). Default 'gpt-4o-mini'.
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
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401).
	 */
	public function generate_text( string $prompt, array $options = [] ): array {
		$this->last_error = null;

		$request_body = [
			'model'      => $options['model'] ?? self::DEFAULT_TEXT_MODEL,
			'messages'   => [
				[
					'role'    => 'user',
					'content' => $prompt,
				],
			],
			'temperature' => $options['temperature'] ?? 0.7,
			'max_tokens'  => $options['max_tokens'] ?? 2048,
		];

		$response = wp_remote_post(
			self::TEXT_API_URL,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				],
				'body'    => wp_json_encode( $request_body ),
				'timeout' => self::TIMEOUT,
			]
		);

		return $this->parse_text_response( $response );
	}

	/**
	 * Generate image.
	 *
	 * Sends a prompt to the OpenAI Image Generations API and returns the generated image URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Image generation prompt describing the desired image.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $model  Model to use. Default 'dall-e-3'.
	 *     @type string $size   Image dimensions. Default '1792x1024' (16:9 landscape).
	 *     @type string $quality Image quality ('standard' or 'hd'). Default 'standard'.
	 * }
	 * @return array {
	 *     Generated image data.
	 *
	 *     @type string $url            URL of the generated image.
	 *     @type string $revised_prompt The actual prompt used by DALL-E (if revised).
	 * }
	 * @throws Provider_Exception When generation fails.
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401).
	 */
	public function generate_image( string $prompt, array $options = [] ): array {
		$this->last_error = null;

		$request_body = [
			'model'           => $options['model'] ?? self::DEFAULT_IMAGE_MODEL,
			'prompt'          => $prompt,
			'n'               => 1,
			'size'            => $options['size'] ?? '1792x1024',
			'quality'         => $options['quality'] ?? 'standard',
			'response_format' => 'url',
		];

		$response = wp_remote_post(
			self::IMAGE_API_URL,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				],
				'body'    => wp_json_encode( $request_body ),
				'timeout' => self::TIMEOUT,
			]
		);

		return $this->parse_image_response( $response );
	}

	/**
	 * Validate API key by making test request.
	 *
	 * Makes a minimal request to the OpenAI Models API to verify the API key is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key API key to validate.
	 * @return bool True if API key is valid, false otherwise.
	 */
	public function validate_api_key( string $key ): bool {
		$this->last_error = null;

		$response = wp_remote_get(
			self::MODELS_API_URL,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $key,
				],
				'timeout' => 10,
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );

		// 200 indicates valid API key.
		if ( 200 === $code ) {
			return true;
		}

		// 401 indicates invalid API key.
		if ( 401 === $code ) {
			$body             = json_decode( wp_remote_retrieve_body( $response ), true );
			$this->last_error = $body['error']['message'] ?? 'Invalid API key';
			return false;
		}

		// Any other response (including 429 rate limit) means the key is valid.
		return true;
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
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401).
	 */
	private function parse_text_response( $response ): array {
		// Handle WP_Error (network/timeout errors).
		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			throw new Provider_Exception(
				$this->last_error ?? 'Request failed',
				'openai'
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
				'openai',
				$code
			);
		}

		// Extract generated text from response.
		if ( empty( $body['choices'][0]['message']['content'] ) ) {
			$this->last_error = 'Empty response from OpenAI API';
			throw new Provider_Exception(
				$this->last_error,
				'openai'
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
	 *     @type string $revised_prompt The actual prompt used by DALL-E (if revised).
	 * }
	 * @throws Provider_Exception When generation fails.
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401).
	 */
	private function parse_image_response( $response ): array {
		// Handle WP_Error (network/timeout errors).
		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			throw new Provider_Exception(
				$this->last_error ?? 'Request failed',
				'openai'
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
				'openai',
				$code
			);
		}

		// Extract generated image URL from response.
		if ( empty( $body['data'][0]['url'] ) ) {
			$this->last_error = 'Empty response from OpenAI Image API';
			throw new Provider_Exception(
				$this->last_error,
				'openai'
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
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401).
	 */
	private function handle_error_codes( int $code, array $body ): void {
		// Handle rate limit (HTTP 429).
		if ( 429 === $code ) {
			// OpenAI may include retry_after in the error response.
			$retry_after = 60;

			// Check for retry-after in error body.
			if ( isset( $body['error']['retry_after'] ) ) {
				$retry_after = (int) $body['error']['retry_after'];
			}

			throw new Provider_Rate_Limit_Exception( 'openai', $retry_after );
		}

		// Handle authentication error (HTTP 401).
		if ( 401 === $code ) {
			throw new Provider_Auth_Exception( 'openai' );
		}
	}
}
