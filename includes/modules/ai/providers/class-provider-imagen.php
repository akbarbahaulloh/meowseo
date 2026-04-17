<?php
/**
 * Google Imagen AI Provider.
 *
 * Implements the AI_Provider interface for Google's Imagen API.
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
 * Class Provider_Imagen
 *
 * AI provider implementation for Google Imagen API.
 *
 * Uses the imagen-3.0-generate-002 model for image generation.
 * Does not support text generation.
 *
 * @since 1.0.0
 */
class Provider_Imagen implements AI_Provider {

	/**
	 * Imagen API endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-002:predict';

	/**
	 * Request timeout in seconds.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private const TIMEOUT = 60;

	/**
	 * Default image aspect ratio.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_ASPECT_RATIO = '16:9';

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
	 * @param string $api_key The Imagen API key (Google API key).
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Get unique provider identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider slug 'imagen'.
	 */
	public function get_slug(): string {
		return 'imagen';
	}

	/**
	 * Get display name for UI.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider label 'Google Imagen'.
	 */
	public function get_label(): string {
		return 'Google Imagen';
	}

	/**
	 * Check if provider supports text generation.
	 *
	 * Imagen does not support text generation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always false for Imagen.
	 */
	public function supports_text(): bool {
		return false;
	}

	/**
	 * Check if provider supports image generation.
	 *
	 * Imagen supports image generation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always true for Imagen.
	 */
	public function supports_image(): bool {
		return true;
	}

	/**
	 * Generate text content.
	 *
	 * Imagen does not support text generation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Generation prompt.
	 * @param array  $options Provider-specific options.
	 * @return array Never returns, always throws exception.
	 * @throws Provider_Exception Always thrown as Imagen does not support text generation.
	 */
	public function generate_text( string $prompt, array $options = [] ): array {
		throw new Provider_Exception(
			'Imagen does not support text generation',
			'imagen'
		);
	}

	/**
	 * Generate image.
	 *
	 * Sends a prompt to the Imagen API and returns the generated image URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Image generation prompt describing the desired image.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $aspect_ratio Image aspect ratio (e.g., '16:9', '1:1'). Default '16:9'.
	 *     @type string $sample_count Number of images to generate. Default 1.
	 * }
	 * @return array {
	 *     Generated image data.
	 *
	 *     @type string $url            URL of the generated image.
	 *     @type string $revised_prompt The actual prompt used (null for Imagen).
	 * }
	 * @throws Provider_Exception When generation fails.
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	public function generate_image( string $prompt, array $options = [] ): array {
		$this->last_error = null;

		$request_body = [
			'instances' => [
				[
					'prompt' => $prompt,
				],
			],
			'parameters' => [
				'sampleCount'   => $options['sample_count'] ?? 1,
				'aspectRatio'   => $options['aspect_ratio'] ?? self::DEFAULT_ASPECT_RATIO,
				'safetyFilter'  => 'block_few',
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
	 * Validate API key by making test request.
	 *
	 * Makes a minimal request to the Imagen API to verify the API key is valid.
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
					'Content-Type'    => 'application/json',
					'x-goog-api-key'  => $key,
				],
				'body'    => wp_json_encode( [
					'instances' => [
						[
							'prompt' => 'test',
						],
					],
					'parameters' => [
						'sampleCount' => 1,
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
	 *     @type string $revised_prompt The actual prompt used (null for Imagen).
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
				'imagen'
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle rate limit (HTTP 429).
		if ( 429 === $code ) {
			$retry_after = $this->parse_retry_after( $response );
			throw new Provider_Rate_Limit_Exception( 'imagen', $retry_after );
		}

		// Handle authentication errors (HTTP 401/403).
		if ( 401 === $code || 403 === $code ) {
			throw new Provider_Auth_Exception( 'imagen' );
		}

		// Handle other error responses.
		if ( 200 !== $code ) {
			$error_message    = $body['error']['message'] ?? "HTTP {$code}";
			$this->last_error = $error_message;
			throw new Provider_Exception(
				$error_message,
				'imagen',
				$code
			);
		}

		// Extract generated image from response.
		// Imagen response structure: predictions[0].bytesBase64Encoded
		if ( empty( $body['predictions'][0]['bytesBase64Encoded'] ) ) {
			// Check if there's an error message in the response.
			if ( isset( $body['error']['message'] ) ) {
				$this->last_error = $body['error']['message'];
			} else {
				$this->last_error = 'Empty response from Imagen API';
			}
			throw new Provider_Exception(
				$this->last_error,
				'imagen'
			);
		}

		// Imagen returns base64-encoded image data.
		// We need to save it to a temporary file and return a data URL or upload it.
		$image_data = $body['predictions'][0]['bytesBase64Encoded'];

		// Return the base64 data URL for the image.
		// The caller (AI_Generator) will handle saving to media library.
		return [
			'url'            => 'data:image/png;base64,' . $image_data,
			'revised_prompt' => null,
			'is_base64'      => true,
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
