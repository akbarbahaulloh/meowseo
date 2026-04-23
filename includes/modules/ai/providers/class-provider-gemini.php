<?php
/**
 * Google Gemini AI Provider.
 *
 * Implements the AI_Provider interface for Google's Gemini API.
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
 * Class Provider_Gemini
 *
 * AI provider implementation for Google Gemini API.
 *
 * Uses the gemini-2.0-flash model for text generation.
 * Uses the gemini-3.1-flash-image-preview model (Nano Banana 2) for image generation.
 *
 * @since 1.0.0
 */
class Provider_Gemini implements AI_Provider {

	/**
	 * Gemini API base URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

	/**
	 * Default text generation model.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_TEXT_MODEL = 'gemini-2.0-flash';

	/**
	 * Default image generation model.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_IMAGE_MODEL = 'gemini-3.1-flash-image-preview';

	/**
	 * Current text model.
	 *
	 * @var string
	 */
	private string $text_model;

	/**
	 * Current image model.
	 *
	 * @var string
	 */
	private string $image_model;

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
	 * @param string $api_key     The Gemini API key.
	 * @param string $text_model  Optional. Text model name.
	 * @param string $image_model Optional. Image model name.
	 */
	public function __construct( string $api_key, string $text_model = '', string $image_model = '' ) {
		$this->api_key = $api_key;
		$this->text_model = ! empty( $text_model ) ? $text_model : self::DEFAULT_TEXT_MODEL;
		$this->image_model = ! empty( $image_model ) ? $image_model : self::DEFAULT_IMAGE_MODEL;
	}

	/**
	 * Get unique provider identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider slug 'gemini'.
	 */
	public function get_slug(): string {
		return 'gemini';
	}

	/**
	 * Get display name for UI.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider label 'Google Gemini'.
	 */
	public function get_label(): string {
		return 'Google Gemini';
	}

	/**
	 * Check if provider supports text generation.
	 *
	 * Gemini supports text generation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always true for Gemini.
	 */
	public function supports_text(): bool {
		return true;
	}

	/**
	 * Check if provider supports image generation.
	 *
	 * Gemini supports image generation via Nano Banana 2 (gemini-3.1-flash-image-preview).
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always true for Gemini.
	 */
	public function supports_image(): bool {
		return true;
	}

	/**
	 * Generate text content.
	 *
	 * Sends a prompt to the Gemini API and returns the generated text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Generation prompt containing article context and instructions.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type float $temperature Temperature for generation (0.0-1.0). Default 0.7.
	 *     @type int   $max_tokens  Maximum tokens to generate. Default 2048.
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
			'contents'        => [
				[
					'parts' => [
						[ 'text' => $prompt ],
					],
				],
			],
			'generationConfig' => [
				'temperature'     => $options['temperature'] ?? 0.7,
				'maxOutputTokens' => $options['max_tokens'] ?? 2048,
			],
		];

		$url = add_query_arg( 'key', $this->api_key, self::BASE_URL . $this->text_model . ':generateContent' );
		$response = wp_remote_post(
			$url,
			[
				'headers' => [
					'Content-Type'    => 'application/json',
				],
				'body'    => wp_json_encode( $request_body ),
				'timeout' => self::TIMEOUT,
			]
		);

		return $this->parse_response( $response );
	}

	/**
	 * Generate image.
	 *
	 * Sends a prompt to the Gemini Image API (Nano Banana 2) and returns the generated image URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Image generation prompt.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $size Image size in format 'WIDTHxHEIGHT'. Supports 512px to 4096x4096. Default '1024x1024'.
	 * }
	 * @return array {
	 *     Generated image information.
	 *
	 *     @type string      $url            The URL of the generated image.
	 *     @type string|null $revised_prompt The revised prompt used for generation (if available).
	 * }
	 * @throws Provider_Exception When generation fails.
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	public function generate_image( string $prompt, array $options = [] ): array {
		$this->last_error = null;

		// Build Gemini-specific request body.
		$request_body = [
			'prompt' => [
				'text' => $prompt,
			],
			'generationConfig' => [
				'outputOptions' => [
					'mimeType' => 'image/png',
				],
			],
		];

		// Support size options from 512px to 4096x4096.
		if ( isset( $options['size'] ) ) {
			$dimensions = explode( 'x', $options['size'] );
			if ( 2 === count( $dimensions ) ) {
				$request_body['generationConfig']['width']  = (int) $dimensions[0];
				$request_body['generationConfig']['height'] = (int) $dimensions[1];
			}
		}

		$url = add_query_arg( 'key', $this->api_key, self::BASE_URL . $this->image_model . ':generateImage' );
		// Make request to Gemini Image API with 90-second timeout.
		$response = wp_remote_post(
			$url,
			[
				'headers' => [
					'Content-Type'   => 'application/json',
				],
				'body'    => wp_json_encode( $request_body ),
				'timeout' => 90,
			]
		);

		return $this->parse_image_response( $response );
	}

	/**
	 * Validate API key by making test request.
	 *
	 * Makes a minimal request to the Gemini API to verify the API key is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key API key to validate.
	 * @return bool True if API key is valid, false otherwise.
	 */
	public function validate_api_key( string $key ): bool {
		$this->last_error = null;

		$url = add_query_arg( 'key', $key, self::BASE_URL . $this->text_model . ':generateContent' );
		$response = wp_remote_post(
			$url,
			[
				'headers' => [
					'Content-Type'   => 'application/json',
				],
				'body'    => wp_json_encode( [
					'contents' => [
						[
							'parts' => [
								[ 'text' => 'test' ],
							],
						],
					],
				] ),
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
	 * Parse the API response.
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
	private function parse_response( $response ): array {
		// Handle WP_Error (network/timeout errors).
		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			throw new Provider_Exception(
				$this->last_error ?? 'Request failed',
				'gemini'
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle rate limit (HTTP 429).
		if ( 429 === $code ) {
			$retry_after = $this->parse_retry_after( $response );
			throw new Provider_Rate_Limit_Exception( 'gemini', $retry_after );
		}

		// Handle authentication errors (HTTP 401/403).
		if ( 401 === $code || 403 === $code ) {
			throw new Provider_Auth_Exception( 'gemini' );
		}

		// Handle other error responses.
		if ( 200 !== $code ) {
			$error_message = $body['error']['message'] ?? "HTTP {$code}";
			$this->last_error = $error_message;
			throw new Provider_Exception(
				$error_message,
				'gemini',
				$code
			);
		}

		// Extract generated text from response.
		if ( empty( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$this->last_error = 'Empty response from Gemini API';
			throw new Provider_Exception(
				$this->last_error,
				'gemini'
			);
		}

		return [
			'content' => $body['candidates'][0]['content']['parts'][0]['text'],
			'usage'   => [
				'input_tokens'  => $body['usageMetadata']['promptTokenCount'] ?? 0,
				'output_tokens' => $body['usageMetadata']['candidatesTokenCount'] ?? 0,
			],
		];
	}

	/**
	 * Parse the Retry-After header from the response.
	 *
	 * @since 1.0.0
	 *
	 * @param array $response The response from wp_remote_post.
	 * @return int Seconds to wait before retrying. Default 60 if header not present.
	 */
	private function parse_retry_after( array $response ): int {
		$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );

		if ( ! empty( $retry_after ) && is_numeric( $retry_after ) ) {
			return (int) $retry_after;
		}

		return 60;
	}

	/**
	 * Parse the image generation API response.
	 *
	 * Handles error cases and extracts the generated image URL from successful responses.
	 *
	 * @since 1.0.0
	 *
	 * @param array|\WP_Error $response The response from wp_remote_post.
	 * @return array {
	 *     Generated image information.
	 *
	 *     @type string      $url            The URL of the generated image.
	 *     @type string|null $revised_prompt The revised prompt used for generation (if available).
	 * }
	 * @throws Provider_Exception When generation fails.
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	private function parse_image_response( $response ): array {
		// Handle WP_Error (network/timeout errors).
		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			throw new Provider_Exception(
				$this->last_error ?? 'Request failed',
				'gemini'
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle rate limit (HTTP 429).
		if ( 429 === $code ) {
			$retry_after = $this->parse_retry_after( $response );
			throw new Provider_Rate_Limit_Exception( 'gemini', $retry_after );
		}

		// Handle authentication errors (HTTP 401/403).
		if ( 401 === $code || 403 === $code ) {
			throw new Provider_Auth_Exception( 'gemini' );
		}

		// Handle other error responses.
		if ( 200 !== $code ) {
			$error_message = $body['error']['message'] ?? "HTTP {$code}";
			$this->last_error = $error_message;
			throw new Provider_Exception(
				$error_message,
				'gemini',
				$code
			);
		}

		// Extract image URL from Gemini response.
		// Try both possible response formats.
		$url = null;
		if ( ! empty( $body['images'][0]['url'] ) ) {
			$url = $body['images'][0]['url'];
		} elseif ( ! empty( $body['generatedImages'][0]['image']['url'] ) ) {
			$url = $body['generatedImages'][0]['image']['url'];
		}

		if ( empty( $url ) ) {
			$this->last_error = 'Empty response from Gemini Image API';
			throw new Provider_Exception(
				$this->last_error,
				'gemini'
			);
		}

		return [
			'url'            => $url,
			'revised_prompt' => $body['prompt'] ?? null,
		];
	}
}
