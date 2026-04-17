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
 * Does not support image generation.
 *
 * @since 1.0.0
 */
class Provider_Gemini implements AI_Provider {

	/**
	 * Gemini API endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

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
	 * @param string $api_key The Gemini API key.
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
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
	 * Gemini does not support image generation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always false for Gemini.
	 */
	public function supports_image(): bool {
		return false;
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

		$response = wp_remote_post(
			self::API_URL,
			[
				'headers' => [
					'Content-Type'    => 'application/json',
					'x-goog-api-key'  => $this->api_key,
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
	 * Gemini does not support image generation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Image generation prompt.
	 * @param array  $options Provider-specific options.
	 * @return array Never returns, always throws exception.
	 * @throws Provider_Exception Always thrown as Gemini does not support image generation.
	 */
	public function generate_image( string $prompt, array $options = [] ): array {
		throw new Provider_Exception(
			'Gemini does not support image generation',
			'gemini'
		);
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

		$response = wp_remote_post(
			self::API_URL,
			[
				'headers' => [
					'Content-Type'   => 'application/json',
					'x-goog-api-key' => $key,
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

		// 401 and 403 indicate invalid API key.
		if ( 401 === $code || 403 === $code ) {
			$body    = json_decode( wp_remote_retrieve_body( $response ), true );
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
}
