<?php
/**
 * AI Provider Manager.
 *
 * Orchestrates multiple AI providers with automatic fallback, rate limit handling,
 * and API key encryption.
 *
 * @package MeowSEO\Modules\AI
 */

namespace MeowSEO\Modules\AI;

use MeowSEO\Modules\AI\Contracts\AI_Provider;
use MeowSEO\Modules\AI\Exceptions\Provider_Exception;
use MeowSEO\Modules\AI\Exceptions\Provider_Rate_Limit_Exception;
use MeowSEO\Modules\AI\Providers\Provider_Gemini;
use MeowSEO\Modules\AI\Providers\Provider_OpenAI;
use MeowSEO\Modules\AI\Providers\Provider_Anthropic;
use MeowSEO\Modules\AI\Providers\Provider_Imagen;
use MeowSEO\Modules\AI\Providers\Provider_Dalle;
use MeowSEO\Modules\AI\Providers\Provider_DeepSeek;
use MeowSEO\Modules\AI\Providers\Provider_GLM;
use MeowSEO\Modules\AI\Providers\Provider_Qwen;
use MeowSEO\Options;
use MeowSEO\Helpers\Logger;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AI_Provider_Manager
 *
 * Manages multiple AI providers with automatic fallback, rate limit caching,
 * and API key encryption.
 *
 * The Provider Manager is responsible for:
 * - Loading and instantiating providers with decrypted API keys
 * - Ordering providers by configured priority
 * - Handling rate limit caching to skip rate-limited providers
 * - Implementing fallback logic when providers fail
 * - Logging all provider attempts and results
 *
 * @since 1.0.0
 */
class AI_Provider_Manager {

	/**
	 * Options instance for accessing settings.
	 *
	 * @since 1.0.0
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Array of instantiated providers, keyed by provider slug.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, AI_Provider>
	 */
	private array $providers = [];

	/**
	 * Array of error messages from failed providers.
	 *
	 * Populated during generation attempts to collect all errors
	 * for the final WP_Error response when all providers fail.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	private array $errors = [];

	/**
	 * Cache group for rate limit status.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'meowseo';

	/**
	 * Cache key prefix for rate limit status.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const RATE_LIMIT_KEY_PREFIX = 'ai_ratelimit_';

	/**
	 * Default TTL for rate limit cache (60 seconds).
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private const DEFAULT_RATE_LIMIT_TTL = 60;

	/**
	 * Cache key for provider statuses.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const PROVIDER_STATUS_CACHE_KEY = 'ai_provider_statuses';

	/**
	 * TTL for provider status cache (5 minutes).
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private const PROVIDER_STATUS_CACHE_TTL = 300;

	/**
	 * Constructor.
	 *
	 * Initializes the manager by loading all available providers
	 * with their decrypted API keys.
	 *
	 * @since 1.0.0
	 *
	 * @param Options $options Options instance for accessing settings.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->load_providers();
	}

	/**
	 * Load all available providers.
	 *
	 * Instantiates each provider class with its decrypted API key.
	 * Only providers with valid (non-empty) API keys are loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function load_providers(): void {
		$provider_classes = [
			'gemini'    => Provider_Gemini::class,
			'openai'    => Provider_OpenAI::class,
			'anthropic' => Provider_Anthropic::class,
			'imagen'    => Provider_Imagen::class,
			'dalle'     => Provider_Dalle::class,
			'deepseek'  => Provider_DeepSeek::class,
			'glm'       => Provider_GLM::class,
			'qwen'      => Provider_Qwen::class,
		];

		foreach ( $provider_classes as $slug => $class ) {
			$api_key = $this->get_decrypted_api_key( $slug );

			if ( ! empty( $api_key ) ) {
				$this->providers[ $slug ] = new $class( $api_key );
			}
		}
	}

	/**
	 * Get providers ordered by priority.
	 *
	 * Returns providers in the configured priority order, filtered by:
	 * - Active status (from settings)
	 * - API key availability (only providers with keys)
	 *
	 * For image generation, only image-capable providers are returned.
	 * For text generation, only text-capable providers are returned.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Optional. Filter by capability: 'text', 'image', or 'all'. Default 'all'.
	 * @return array<AI_Provider> Ordered array of provider instances.
	 */
	private function get_ordered_providers( string $type = 'all' ): array {
		$order = $this->options->get( 'ai_provider_order', [] );
		$active = $this->options->get( 'ai_active_providers', [] );

		// Ensure arrays.
		$order = is_array( $order ) ? $order : [];
		$active = is_array( $active ) ? $active : [];

		$ordered = [];

		// Add providers in configured order.
		foreach ( $order as $slug ) {
			if ( ! isset( $this->providers[ $slug ] ) ) {
				continue;
			}

			if ( ! in_array( $slug, $active, true ) ) {
				continue;
			}

			$provider = $this->providers[ $slug ];

			// Filter by capability.
			if ( 'text' === $type && ! $provider->supports_text() ) {
				continue;
			}

			if ( 'image' === $type && ! $provider->supports_image() ) {
				continue;
			}

			$ordered[] = $provider;
		}

		// Add remaining active providers not in order.
		foreach ( $this->providers as $slug => $provider ) {
			if ( ! in_array( $slug, $active, true ) ) {
				continue;
			}

			if ( in_array( $provider, $ordered, true ) ) {
				continue;
			}

			// Filter by capability.
			if ( 'text' === $type && ! $provider->supports_text() ) {
				continue;
			}

			if ( 'image' === $type && ! $provider->supports_image() ) {
				continue;
			}

			$ordered[] = $provider;
		}

		return $ordered;
	}

	/**
	 * Generate text with automatic fallback.
	 *
	 * Iterates through ordered text providers, skipping:
	 * - Providers that don't support text generation
	 * - Providers that are currently rate-limited
	 *
	 * Returns on first successful generation. If all providers fail,
	 * returns a WP_Error with aggregated error messages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Generation prompt containing article context and instructions.
	 * @param array  $options {
	 *     Optional. Generation options passed to providers.
	 *
	 *     @type float $temperature Temperature for generation (0.0-1.0).
	 *     @type int   $max_tokens  Maximum tokens to generate.
	 * }
	 * @return array|WP_Error {
	 *     Generated content on success, or WP_Error on failure.
	 *
	 *     @type string $content  The generated text content.
	 *     @type string $provider The slug of the provider that succeeded.
	 *     @type array  $usage    Token usage information.
	 * }
	 */
	public function generate_text( string $prompt, array $options = [] ) {
		$ordered_providers = $this->get_ordered_providers( 'text' );
		$this->errors = [];

		// Check if any providers are available.
		if ( empty( $ordered_providers ) ) {
			Logger::warning(
				'No text providers available',
				[ 'module' => 'ai' ]
			);

			return new WP_Error(
				'no_providers',
				__( 'No AI providers configured. Please add API keys in settings.', 'meowseo' ),
				[ 'errors' => [] ]
			);
		}

		foreach ( $ordered_providers as $provider ) {
			$slug = $provider->get_slug();

			// Skip rate-limited providers.
			if ( $this->is_rate_limited( $slug ) ) {
				$this->log_skip( $slug, 'rate_limited' );
				continue;
			}

			try {
				$result = $provider->generate_text( $prompt, $options );

				$this->log_success( $slug, 'text' );

				return [
					'content'  => $result['content'],
					'provider' => $slug,
					'usage'    => $result['usage'] ?? [],
				];
			} catch ( Provider_Rate_Limit_Exception $e ) {
				$this->handle_rate_limit( $slug, $e );
				$this->errors[ $slug ] = $e->getMessage();
			} catch ( Provider_Exception $e ) {
				$this->errors[ $slug ] = $e->getMessage();
				$this->log_failure( $slug, $e->getMessage() );
			}
		}

		// All providers failed.
		Logger::error(
			'All text providers failed',
			[
				'module' => 'ai',
				'errors' => $this->errors,
			]
		);

		return new WP_Error(
			'all_providers_failed',
			__( 'All AI providers failed. Please check your API keys.', 'meowseo' ),
			[ 'errors' => $this->errors ]
		);
	}

	/**
	 * Generate image with automatic fallback.
	 *
	 * Iterates through ordered image providers, skipping:
	 * - Providers that don't support image generation
	 * - Providers that are currently rate-limited
	 *
	 * Returns on first successful generation. If all providers fail,
	 * returns a WP_Error with aggregated error messages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Image generation prompt describing the desired image.
	 * @param array  $options {
	 *     Optional. Generation options passed to providers.
	 *
	 *     @type string $size          Image dimensions (e.g., '1200x630').
	 *     @type string $style         Visual style (e.g., 'professional').
	 *     @type string $color_palette Color palette hint.
	 * }
	 * @return array|WP_Error {
	 *     Generated image data on success, or WP_Error on failure.
	 *
	 *     @type string $url            URL of the generated image.
	 *     @type string $provider       The slug of the provider that succeeded.
	 *     @type string $revised_prompt The actual prompt used (if revised by provider).
	 * }
	 */
	public function generate_image( string $prompt, array $options = [] ) {
		$ordered_providers = $this->get_ordered_providers( 'image' );
		$this->errors = [];

		// Check if any providers are available.
		if ( empty( $ordered_providers ) ) {
			Logger::warning(
				'No image providers available',
				[ 'module' => 'ai' ]
			);

			return new WP_Error(
				'no_image_providers',
				__( 'No image providers configured. Please add API keys in settings.', 'meowseo' ),
				[ 'errors' => [] ]
			);
		}

		foreach ( $ordered_providers as $provider ) {
			$slug = $provider->get_slug();

			// Skip rate-limited providers.
			if ( $this->is_rate_limited( $slug ) ) {
				$this->log_skip( $slug, 'rate_limited' );
				continue;
			}

			try {
				$result = $provider->generate_image( $prompt, $options );

				$this->log_success( $slug, 'image' );

				return [
					'url'            => $result['url'],
					'provider'       => $slug,
					'revised_prompt' => $result['revised_prompt'] ?? null,
				];
			} catch ( Provider_Rate_Limit_Exception $e ) {
				$this->handle_rate_limit( $slug, $e );
				$this->errors[ $slug ] = $e->getMessage();
			} catch ( Provider_Exception $e ) {
				$this->errors[ $slug ] = $e->getMessage();
				$this->log_failure( $slug, $e->getMessage() );
			}
		}

		// All providers failed.
		Logger::error(
			'All image providers failed',
			[
				'module' => 'ai',
				'errors' => $this->errors,
			]
		);

		return new WP_Error(
			'all_image_providers_failed',
			__( 'All image providers failed. Please check your API keys and try again.', 'meowseo' ),
			[ 'errors' => $this->errors ]
		);
	}

	/**
	 * Check if a provider is currently rate-limited.
	 *
	 * Checks the WordPress Object Cache for rate limit status.
	 * If a rate limit is cached, the provider is skipped without
	 * making an API request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $provider_slug The provider slug to check.
	 * @return bool True if provider is rate-limited, false otherwise.
	 */
	private function is_rate_limited( string $provider_slug ): bool {
		$cache_key = self::RATE_LIMIT_KEY_PREFIX . $provider_slug;
		$rate_limit_end = wp_cache_get( $cache_key, self::CACHE_GROUP );

		// If no cache entry, not rate limited.
		if ( false === $rate_limit_end ) {
			return false;
		}

		// Check if rate limit has expired.
		if ( time() > (int) $rate_limit_end ) {
			// Clear expired cache.
			wp_cache_delete( $cache_key, self::CACHE_GROUP );
			return false;
		}

		return true;
	}

	/**
	 * Handle rate limit by caching the status.
	 *
	 * Stores the rate limit status in Object Cache with TTL from
	 * the exception, or default 60 seconds.
	 *
	 * @since 1.0.0
	 *
	 * @param string                      $provider_slug The provider slug.
	 * @param Provider_Rate_Limit_Exception $e            The rate limit exception.
	 * @return void
	 */
	private function handle_rate_limit( string $provider_slug, Provider_Rate_Limit_Exception $e ): void {
		$cache_key = self::RATE_LIMIT_KEY_PREFIX . $provider_slug;
		$ttl = $e->get_retry_after() ?: self::DEFAULT_RATE_LIMIT_TTL;

		// Store the timestamp when rate limit expires.
		$rate_limit_end = time() + $ttl;
		wp_cache_set( $cache_key, $rate_limit_end, self::CACHE_GROUP, $ttl );

		Logger::warning(
			"AI provider rate limited: {$provider_slug}",
			[
				'module'      => 'ai',
				'provider'    => $provider_slug,
				'retry_after' => $ttl,
			]
		);
	}

	/**
	 * Get decrypted API key for a provider.
	 *
	 * Retrieves the encrypted API key from WordPress options and decrypts it.
	 * Returns null if no key is stored or decryption fails.
	 *
	 * @since 1.0.0
	 *
	 * @param string $provider_slug The provider slug.
	 * @return string|null Decrypted API key or null if not available.
	 */
	private function get_decrypted_api_key( string $provider_slug ): ?string {
		$key = 'ai_api_key_' . $provider_slug;
		$encrypted = $this->options->get( $key, '' );

		if ( empty( $encrypted ) ) {
			return null;
		}

		return $this->decrypt_key( $encrypted );
	}

	/**
	 * Decrypt an API key using AES-256-CBC.
	 *
	 * Uses WordPress AUTH_KEY for the encryption key.
	 * The encrypted value should be base64-encoded with IV prepended.
	 *
	 * @since 1.0.0
	 *
	 * @param string $encrypted Base64-encoded encrypted data with IV prepended.
	 * @return string|null Decrypted API key or null on failure.
	 */
	private function decrypt_key( string $encrypted ): ?string {
		// Check if AUTH_KEY is defined.
		if ( ! defined( 'AUTH_KEY' ) || empty( AUTH_KEY ) ) {
			Logger::error(
				'AUTH_KEY not defined for API key decryption',
				[ 'module' => 'ai' ]
			);
			return null;
		}

		// Decode base64.
		$raw = base64_decode( $encrypted, true );

		if ( false === $raw || strlen( $raw ) < 16 ) {
			Logger::error(
				'Failed to decode encrypted API key',
				[ 'module' => 'ai' ]
			);
			return null;
		}

		// Derive encryption key from AUTH_KEY.
		$key = hash( 'sha256', AUTH_KEY, true );

		// Extract IV (first 16 bytes) and encrypted data.
		$iv = substr( $raw, 0, 16 );
		$encrypted_data = substr( $raw, 16 );

		// Decrypt.
		$decrypted = openssl_decrypt( $encrypted_data, 'AES-256-CBC', $key, 0, $iv );

		if ( false === $decrypted ) {
			Logger::error(
				'Failed to decrypt API key',
				[ 'module' => 'ai' ]
			);
			return null;
		}

		return $decrypted;
	}

	/**
	 * Encrypt an API key using AES-256-CBC.
	 *
	 * Uses WordPress AUTH_KEY for the encryption key.
	 * Returns base64-encoded encrypted data with IV prepended.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key The API key to encrypt.
	 * @return string|false Base64-encoded encrypted data or false on failure.
	 */
	public function encrypt_key( string $api_key ) {
		// Check if AUTH_KEY is defined.
		if ( ! defined( 'AUTH_KEY' ) || empty( AUTH_KEY ) ) {
			Logger::error(
				'AUTH_KEY not defined for API key encryption',
				[ 'module' => 'ai' ]
			);
			return false;
		}

		// Derive encryption key from AUTH_KEY.
		$key = hash( 'sha256', AUTH_KEY, true );

		// Generate random IV.
		$iv = openssl_random_pseudo_bytes( 16 );

		if ( false === $iv ) {
			Logger::error(
				'Failed to generate IV for API key encryption',
				[ 'module' => 'ai' ]
			);
			return false;
		}

		// Encrypt.
		$encrypted = openssl_encrypt( $api_key, 'AES-256-CBC', $key, 0, $iv );

		if ( false === $encrypted ) {
			Logger::error(
				'Failed to encrypt API key',
				[ 'module' => 'ai' ]
			);
			return false;
		}

		// Return base64-encoded IV + encrypted data.
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Get all provider statuses.
	 *
	 * Returns an array of status information for all providers,
	 * including those without API keys.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{
	 *     label: string,
	 *     active: bool,
	 *     has_api_key: bool,
	 *     supports_text: bool,
	 *     supports_image: bool,
	 *     rate_limited: bool,
	 *     rate_limit_remaining: int,
	 *     priority: int
	 * }> Provider statuses keyed by slug.
	 */
	public function get_provider_statuses(): array {
		// Check cache first (Requirement 3.6).
		$cached = wp_cache_get( self::PROVIDER_STATUS_CACHE_KEY, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$statuses = [];
		$order = $this->options->get( 'ai_provider_order', [] );
		$active = $this->options->get( 'ai_active_providers', [] );

		// Ensure arrays.
		$order = is_array( $order ) ? $order : [];
		$active = is_array( $active ) ? $active : [];

		// Get status for loaded providers.
		foreach ( $this->providers as $slug => $provider ) {
			$rate_limit_end = wp_cache_get(
				self::RATE_LIMIT_KEY_PREFIX . $slug,
				self::CACHE_GROUP
			);

			$rate_limited = false !== $rate_limit_end && time() < (int) $rate_limit_end;
			$rate_limit_remaining = $rate_limited ? max( 0, (int) $rate_limit_end - time() ) : 0;

			$priority = array_search( $slug, $order, true );
			$priority = false !== $priority ? (int) $priority : 999;

			$statuses[ $slug ] = [
				'label'               => $provider->get_label(),
				'active'              => in_array( $slug, $active, true ),
				'has_api_key'         => true,
				'supports_text'       => $provider->supports_text(),
				'supports_image'      => $provider->supports_image(),
				'rate_limited'        => $rate_limited,
				'rate_limit_remaining' => $rate_limit_remaining,
				'priority'            => $priority,
			];
		}

		// Add providers without API keys.
		$all_slugs = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle', 'deepseek', 'glm', 'qwen' ];

		foreach ( $all_slugs as $slug ) {
			if ( isset( $statuses[ $slug ] ) ) {
				continue;
			}

			$priority = array_search( $slug, $order, true );
			$priority = false !== $priority ? (int) $priority : 999;

			$statuses[ $slug ] = [
				'label'               => $this->get_provider_label( $slug ),
				'active'              => in_array( $slug, $active, true ),
				'has_api_key'         => false,
				'supports_text'       => in_array( $slug, [ 'gemini', 'openai', 'anthropic', 'deepseek', 'glm', 'qwen' ], true ),
				'supports_image'      => in_array( $slug, [ 'gemini', 'imagen', 'dalle', 'openai', 'deepseek', 'glm', 'qwen' ], true ),
				'rate_limited'        => false,
				'rate_limit_remaining' => 0,
				'priority'            => $priority,
			];
		}

		// Cache the statuses (Requirement 3.6).
		wp_cache_set( self::PROVIDER_STATUS_CACHE_KEY, $statuses, self::CACHE_GROUP, self::PROVIDER_STATUS_CACHE_TTL );

		return $statuses;
	}

	/**
	 * Get provider label by slug.
	 *
	 * Used for providers that don't have API keys (not instantiated).
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Provider slug.
	 * @return string Provider label.
	 */
	private function get_provider_label( string $slug ): string {
		$labels = [
			'gemini'    => 'Google Gemini',
			'openai'    => 'OpenAI',
			'anthropic' => 'Anthropic Claude',
			'imagen'    => 'Google Imagen',
			'dalle'     => 'OpenAI DALL-E',
			'deepseek'  => 'DeepSeek',
			'glm'       => 'Zhipu AI GLM',
			'qwen'      => 'Alibaba Qwen',
		];

		return $labels[ $slug ] ?? ucfirst( $slug );
	}

	/**
	 * Log a skipped provider attempt.
	 *
	 * @since 1.0.0
	 *
	 * @param string $provider_slug Provider slug.
	 * @param string $reason        Skip reason.
	 * @return void
	 */
	private function log_skip( string $provider_slug, string $reason ): void {
		Logger::info(
			"AI provider skipped: {$provider_slug}",
			[
				'module'   => 'ai',
				'provider' => $provider_slug,
				'reason'   => $reason,
			]
		);
	}

	/**
	 * Log a successful provider attempt.
	 *
	 * @since 1.0.0
	 *
	 * @param string $provider_slug Provider slug.
	 * @param string $type          Generation type ('text' or 'image').
	 * @return void
	 */
	private function log_success( string $provider_slug, string $type ): void {
		Logger::info(
			"AI provider succeeded: {$provider_slug}",
			[
				'module'   => 'ai',
				'provider' => $provider_slug,
				'type'     => $type,
			]
		);
	}

	/**
	 * Log a failed provider attempt.
	 *
	 * @since 1.0.0
	 *
	 * @param string $provider_slug Provider slug.
	 * @param string $error         Error message.
	 * @return void
	 */
	private function log_failure( string $provider_slug, string $error ): void {
		Logger::warning(
			"AI provider failed: {$provider_slug}",
			[
				'module'   => 'ai',
				'provider' => $provider_slug,
				'error'    => $error,
			]
		);
	}

	/**
	 * Get a specific provider instance by slug.
	 *
	 * Returns the provider instance if loaded, or null if not available.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Provider slug.
	 * @return AI_Provider|null Provider instance or null if not loaded.
	 */
	public function get_provider( string $slug ): ?AI_Provider {
		return $this->providers[ $slug ] ?? null;
	}

	/**
	 * Get all loaded provider instances.
	 *
	 * Returns all providers that have been instantiated with API keys.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, AI_Provider> Providers keyed by slug.
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/**
	 * Get errors from the last generation attempt.
	 *
	 * Returns the array of error messages collected during the last
	 * generate_text() or generate_image() call.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Errors keyed by provider slug.
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Clear provider status cache.
	 *
	 * Called when provider settings change to invalidate cached statuses.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if cache was cleared.
	 */
	public function clear_provider_status_cache(): bool {
		return wp_cache_delete( self::PROVIDER_STATUS_CACHE_KEY, self::CACHE_GROUP );
	}
}
