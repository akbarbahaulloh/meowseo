<?php
/**
 * DALL-E AI Provider.
 *
 * Implements the AI_Provider interface for OpenAI's DALL-E image generation API.
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
 * Class Provider_DALL_E
 *
 * AI provider implementation for OpenAI DALL-E API.
 *
 * Uses the dall-e-3 model for image generation.
 * Does not support text generation.
 *
 * This is a standalone image-only provider separate from OpenAI for fallback ordering purposes.
 * It uses the same OpenAI API key as the OpenAI provider.
 *
 * @since 1.0.0
 */
class Provider_Dalle implements AI_Provider {

	/**
	 * OpenAI Image Generations API endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const API_URL = 'https://api.openai.com/v1/images/generations';

	/**
	 * OpenAI Models API endpoint URL for validation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const MODELS_API_URL = 'https://api.openai.com/v1/models';

	/**
	 * Default image model for generation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_MODEL = 'dall-e-3';

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
	 * @return string Provider slug 'dalle'.
	 */
	public function get_slug(): string {
		return 'dalle';
	}

	/**
	 * Get display name for UI.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider label 'DALL-E'.
	 */
	public function get_label(): string {
		return 'DALL-E';
	}

	/**
	 * Check if provider supports text generation.
	 *
	 * DALL-E does not support text generation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always false for DALL-E.
	 */
	public function supports_text(): bool {
		return false;
	}

	/**
	 * Check if provider supports image generation.
	 *
	 * DALL-E supports image generation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always true for DALL-E.
	 */
	public function supports_image(): bool {
		return true;
	}

	/**
	 * Generate text content.
	 *
	 * DALL-E does not support text generation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Generation prompt.
	 * @param array  $options Provider-specific options.
	 * @return array Never returns, always throws exception.
	 * @throws Provider_Exception Always thrown as DALL-E does not support text generation.
	 */
	public function generate_text( string $prompt, array $options = [] ): array {
		throw new Provider_Exception(
			'DALL-E does not support text generation',
			'dalle'
		);
	}

	/**
	 * Generate image.
	 *
	 * Sends a prompt to the OpenAI DALL-E API and returns the generated image URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Image generation prompt describing the desired image.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $model   Model to use. Default 'dall-e-3'.
	 *     @type string $size    Image dimensions. Default '1792x1024' (16:9 landscape).
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
			'model'           => $options['model'] ?? self::DEFAULT_MODEL,
			'prompt'          => $prompt,
			'n'               => 1,
			'size'            => $options['size'] ?? '1792x1024',
			'quality'         => $options['quality'] ?? 'standard',
			'response_format' => 'url',
		];

		$response = wp_remote_post(
			self::API_URL,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				],
				'body'    => wp_json_encode( $request_body ),
				'timeout' => self::TIMEOUT,
			]
		);

		return $this->parse_response( $response );
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
	 * Parse the API response.
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
	private function parse_response( $response ): array {
		// Handle WP_Error (network/timeout errors).
		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			throw new Provider_Exception(
				$this->last_error ?? 'Request failed',
				'dalle'
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle rate limit (HTTP 429).
		if ( 429 === $code ) {
			$retry_after = $this->parse_retry_after( $response, $body );
			throw new Provider_Rate_Limit_Exception( 'dalle', $retry_after );
		}

		// Handle authentication error (HTTP 401).
		if ( 401 === $code ) {
			throw new Provider_Auth_Exception( 'dalle' );
		}

		// Handle other error responses.
		if ( 200 !== $code ) {
			$error_message    = $body['error']['message'] ?? "HTTP {$code}";
			$this->last_error = $error_message;
			throw new Provider_Exception(
				$error_message,
				'dalle',
				$code
			);
		}

		// Extract generated image URL from response.
		if ( empty( $body['data'][0]['url'] ) ) {
			$this->last_error = 'Empty response from DALL-E API';
			throw new Provider_Exception(
				$this->last_error,
				'dalle'
			);
		}

		return [
			'url'            => $body['data'][0]['url'],
			'revised_prompt' => $body['data'][0]['revised_prompt'] ?? null,
		];
	}

	/**
	 * Parse the retry-after value from the response.
	 *
	 * Checks both the HTTP header and the response body for retry-after information.
	 *
	 * @since 1.0.0
	 *
	 * @param array $response The response from wp_remote_post.
	 * @param array $body     The parsed response body.
	 * @return int Seconds to wait before retrying. Default 60 if not present.
	 */
	private function parse_retry_after( array $response, array $body ): int {
		// Check for retry-after header.
		$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );

		if ( ! empty( $retry_after ) && is_numeric( $retry_after ) ) {
			return (int) $retry_after;
		}

		// Check for retry_after in error body (OpenAI format).
		if ( isset( $body['error']['retry_after'] ) ) {
			return (int) $body['error']['retry_after'];
		}

		return 60;
	}
}
