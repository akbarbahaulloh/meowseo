<?php
/**
 * Anthropic Claude AI Provider.
 *
 * Implements the AI_Provider interface for Anthropic's Claude API.
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
 * Class Provider_Anthropic
 *
 * AI provider implementation for Anthropic Claude API.
 *
 * Uses the claude-haiku-4-5-20251001 model for text generation.
 * Does not support image generation.
 *
 * @since 1.0.0
 */
class Provider_Anthropic implements AI_Provider {

	/**
	 * Anthropic Messages API endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const API_URL = 'https://api.anthropic.com/v1/messages';

	/**
	 * Default model for generation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_MODEL = 'claude-haiku-4-5-20251001';

	/**
	 * Anthropic API version header.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const ANTHROPIC_VERSION = '2023-06-01';

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
	 * @param string $api_key The Anthropic API key.
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Get unique provider identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider slug 'anthropic'.
	 */
	public function get_slug(): string {
		return 'anthropic';
	}

	/**
	 * Get display name for UI.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider label 'Anthropic Claude'.
	 */
	public function get_label(): string {
		return 'Anthropic Claude';
	}

	/**
	 * Check if provider supports text generation.
	 *
	 * Anthropic supports text generation via Claude models.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always true for Anthropic.
	 */
	public function supports_text(): bool {
		return true;
	}

	/**
	 * Check if provider supports image generation.
	 *
	 * Anthropic does not support image generation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always false for Anthropic.
	 */
	public function supports_image(): bool {
		return false;
	}

	/**
	 * Generate text content.
	 *
	 * Sends a prompt to the Anthropic Messages API and returns the generated text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Generation prompt containing article context and instructions.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $model       Model to use (e.g., 'claude-haiku-4-5-20251001'). Default 'claude-haiku-4-5-20251001'.
	 *     @type float  $temperature Temperature for generation (0.0-1.0). Default 0.7.
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
			'model'      => $options['model'] ?? self::DEFAULT_MODEL,
			'max_tokens' => $options['max_tokens'] ?? 2048,
			'messages'   => [
				[
					'role'    => 'user',
					'content' => $prompt,
				],
			],
		];

		// Add temperature if specified (Anthropic doesn't always include this).
		if ( isset( $options['temperature'] ) ) {
			$request_body['temperature'] = (float) $options['temperature'];
		}

		$response = wp_remote_post(
			self::API_URL,
			[
				'headers' => [
					'Content-Type'      => 'application/json',
					'x-api-key'         => $this->api_key,
					'anthropic-version' => self::ANTHROPIC_VERSION,
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
	 * Anthropic does not support image generation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Image generation prompt.
	 * @param array  $options Provider-specific options.
	 * @return array Never returns, always throws exception.
	 * @throws Provider_Exception Always thrown as Anthropic does not support image generation.
	 */
	public function generate_image( string $prompt, array $options = [] ): array {
		throw new Provider_Exception(
			'Anthropic does not support image generation',
			'anthropic'
		);
	}

	/**
	 * Validate API key by making test request.
	 *
	 * Makes a minimal request to the Anthropic API to verify the API key is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key API key to validate.
	 * @return bool True if API key is valid, false otherwise.
	 */
	public function validate_api_key( string $key ): bool {
		$this->last_error = null;

		// Make a minimal request to validate the API key.
		$response = wp_remote_post(
			self::API_URL,
			[
				'headers' => [
					'Content-Type'      => 'application/json',
					'x-api-key'         => $key,
					'anthropic-version' => self::ANTHROPIC_VERSION,
				],
				'body'    => wp_json_encode( [
					'model'      => self::DEFAULT_MODEL,
					'max_tokens' => 10,
					'messages'   => [
						[
							'role'    => 'user',
							'content' => 'test',
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
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401).
	 */
	private function parse_response( $response ): array {
		// Handle WP_Error (network/timeout errors).
		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			throw new Provider_Exception(
				$this->last_error ?? 'Request failed',
				'anthropic'
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle rate limit (HTTP 429).
		if ( 429 === $code ) {
			$retry_after = $this->parse_retry_after( $response, $body );
			throw new Provider_Rate_Limit_Exception( 'anthropic', $retry_after );
		}

		// Handle authentication error (HTTP 401).
		if ( 401 === $code ) {
			throw new Provider_Auth_Exception( 'anthropic' );
		}

		// Handle permission error (HTTP 403).
		if ( 403 === $code ) {
			$error_message    = $body['error']['message'] ?? 'Permission denied';
			$this->last_error = $error_message;
			throw new Provider_Exception(
				$error_message,
				'anthropic',
				$code
			);
		}

		// Handle other error responses.
		if ( 200 !== $code ) {
			$error_message    = $body['error']['message'] ?? "HTTP {$code}";
			$this->last_error = $error_message;
			throw new Provider_Exception(
				$error_message,
				'anthropic',
				$code
			);
		}

		// Extract generated text from response.
		// Anthropic response structure: content[0].text
		if ( empty( $body['content'][0]['text'] ) ) {
			$this->last_error = 'Empty response from Anthropic API';
			throw new Provider_Exception(
				$this->last_error,
				'anthropic'
			);
		}

		return [
			'content' => $body['content'][0]['text'],
			'usage'   => [
				'input_tokens'  => $body['usage']['input_tokens'] ?? 0,
				'output_tokens' => $body['usage']['output_tokens'] ?? 0,
			],
		];
	}

	/**
	 * Parse the retry-after value from the response.
	 *
	 * Checks both the Retry-After header and the response body for retry information.
	 *
	 * @since 1.0.0
	 *
	 * @param array $response The response from wp_remote_post.
	 * @param array $body     The parsed response body.
	 * @return int Seconds to wait before retrying. Default 60 if not specified.
	 */
	private function parse_retry_after( array $response, array $body ): int {
		// Check Retry-After header first.
		$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );

		if ( ! empty( $retry_after ) && is_numeric( $retry_after ) ) {
			return (int) $retry_after;
		}

		// Check for retry_after in error body (Anthropic specific).
		if ( isset( $body['error']['retry_after'] ) ) {
			return (int) $body['error']['retry_after'];
		}

		return 60;
	}
}
