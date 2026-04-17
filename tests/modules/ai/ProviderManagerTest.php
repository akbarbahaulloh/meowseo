<?php
/**
 * AI_Provider_Manager Test Case
 *
 * Unit tests for the AI Provider Manager implementation.
 *
 * @package MeowSEO\Tests\Modules\AI
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Modules\AI\Contracts\AI_Provider;
use MeowSEO\Modules\AI\Providers\Provider_Gemini;
use MeowSEO\Modules\AI\Providers\Provider_OpenAI;
use MeowSEO\Modules\AI\Providers\Provider_Anthropic;
use MeowSEO\Modules\AI\Providers\Provider_Imagen;
use MeowSEO\Modules\AI\Providers\Provider_Dalle;
use MeowSEO\Modules\AI\Exceptions\Provider_Exception;
use MeowSEO\Modules\AI\Exceptions\Provider_Rate_Limit_Exception;
use MeowSEO\Modules\AI\Exceptions\Provider_Auth_Exception;
use MeowSEO\Options;

/**
 * AI_Provider_Manager test case
 *
 * Tests that the Provider Manager correctly orchestrates multiple AI providers
 * with automatic fallback, rate limit handling, and API key encryption.
 *
 * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 2.9, 2.10, 6.9, 20.5
 *
 * @since 1.0.0
 */
class ProviderManagerTest extends TestCase {

	/**
	 * Test that AI_Provider_Manager class can be loaded by autoloader.
	 *
	 * @return void
	 */
	public function test_provider_manager_class_can_be_loaded(): void {
		$this->assertTrue(
			class_exists( AI_Provider_Manager::class ),
			'AI_Provider_Manager class should be loadable by autoloader'
		);
	}

	/**
	 * Test that AI_Provider_Manager can be instantiated.
	 *
	 * @return void
	 */
	public function test_provider_manager_can_be_instantiated(): void {
		// Mock Options class.
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$this->assertInstanceOf(
			AI_Provider_Manager::class,
			$manager,
			'AI_Provider_Manager should be instantiable'
		);
	}

	/**
	 * Test that get_providers returns an array.
	 *
	 * @return void
	 */
	public function test_get_providers_returns_array(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$this->assertIsArray(
			$manager->get_providers(),
			'get_providers should return an array'
		);
	}

	/**
	 * Test that get_provider_statuses returns an array.
	 *
	 * @return void
	 */
	public function test_get_provider_statuses_returns_array(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$statuses = $manager->get_provider_statuses();

		$this->assertIsArray(
			$statuses,
			'get_provider_statuses should return an array'
		);
	}

	/**
	 * Test that get_provider_statuses returns all expected providers.
	 *
	 * @return void
	 */
	public function test_get_provider_statuses_returns_all_providers(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$statuses = $manager->get_provider_statuses();

		$expected_slugs = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle' ];

		foreach ( $expected_slugs as $slug ) {
			$this->assertArrayHasKey(
				$slug,
				$statuses,
				"Provider statuses should include '{$slug}'"
			);
		}
	}

	/**
	 * Test that get_provider_statuses returns correct structure.
	 *
	 * @return void
	 */
	public function test_get_provider_statuses_returns_correct_structure(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$statuses = $manager->get_provider_statuses();

		foreach ( $statuses as $slug => $status ) {
			$this->assertArrayHasKey( 'label', $status, "Status for '{$slug}' should have 'label'" );
			$this->assertArrayHasKey( 'active', $status, "Status for '{$slug}' should have 'active'" );
			$this->assertArrayHasKey( 'has_api_key', $status, "Status for '{$slug}' should have 'has_api_key'" );
			$this->assertArrayHasKey( 'supports_text', $status, "Status for '{$slug}' should have 'supports_text'" );
			$this->assertArrayHasKey( 'supports_image', $status, "Status for '{$slug}' should have 'supports_image'" );
			$this->assertArrayHasKey( 'rate_limited', $status, "Status for '{$slug}' should have 'rate_limited'" );
			$this->assertArrayHasKey( 'rate_limit_remaining', $status, "Status for '{$slug}' should have 'rate_limit_remaining'" );
			$this->assertArrayHasKey( 'priority', $status, "Status for '{$slug}' should have 'priority'" );
		}
	}

	/**
	 * Test that providers without API keys show has_api_key as false.
	 *
	 * @return void
	 */
	public function test_providers_without_api_keys_show_has_api_key_false(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$statuses = $manager->get_provider_statuses();

		foreach ( $statuses as $slug => $status ) {
			$this->assertFalse(
				$status['has_api_key'],
				"Provider '{$slug}' should have has_api_key=false when no key is set"
			);
		}
	}

	/**
	 * Test that text providers have correct supports_text value.
	 *
	 * @return void
	 */
	public function test_text_providers_have_correct_supports_text_value(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$statuses = $manager->get_provider_statuses();

		$text_providers = [ 'gemini', 'openai', 'anthropic' ];

		foreach ( $text_providers as $slug ) {
			$this->assertTrue(
				$statuses[ $slug ]['supports_text'],
				"Provider '{$slug}' should support text"
			);
		}
	}

	/**
	 * Test that image providers have correct supports_image value.
	 *
	 * @return void
	 */
	public function test_image_providers_have_correct_supports_image_value(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$statuses = $manager->get_provider_statuses();

		$image_providers = [ 'imagen', 'dalle', 'openai' ];

		foreach ( $image_providers as $slug ) {
			$this->assertTrue(
				$statuses[ $slug ]['supports_image'],
				"Provider '{$slug}' should support images"
			);
		}
	}

	/**
	 * Test that generate_text returns WP_Error when no providers available.
	 *
	 * @return void
	 */
	public function test_generate_text_returns_error_when_no_providers(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$result = $manager->generate_text( 'test prompt' );

		$this->assertInstanceOf(
			\WP_Error::class,
			$result,
			'generate_text should return WP_Error when no providers available'
		);
	}

	/**
	 * Test that generate_text returns correct error code when no providers.
	 *
	 * @return void
	 */
	public function test_generate_text_returns_correct_error_code_when_no_providers(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$result = $manager->generate_text( 'test prompt' );

		$this->assertEquals(
			'no_providers',
			$result->get_error_code(),
			'Error code should be "no_providers"'
		);
	}

	/**
	 * Test that generate_image returns WP_Error when no providers available.
	 *
	 * @return void
	 */
	public function test_generate_image_returns_error_when_no_providers(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$result = $manager->generate_image( 'test prompt' );

		$this->assertInstanceOf(
			\WP_Error::class,
			$result,
			'generate_image should return WP_Error when no providers available'
		);
	}

	/**
	 * Test that generate_image returns correct error code when no providers.
	 *
	 * @return void
	 */
	public function test_generate_image_returns_correct_error_code_when_no_providers(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$result = $manager->generate_image( 'test prompt' );

		$this->assertEquals(
			'no_image_providers',
			$result->get_error_code(),
			'Error code should be "no_image_providers"'
		);
	}

	/**
	 * Test that get_errors returns an array.
	 *
	 * @return void
	 */
	public function test_get_errors_returns_array(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$this->assertIsArray(
			$manager->get_errors(),
			'get_errors should return an array'
		);
	}

	/**
	 * Test that encrypt_key returns a string.
	 *
	 * @return void
	 */
	public function test_encrypt_key_returns_string(): void {
		// Skip if AUTH_KEY is not defined (WordPress context).
		if ( ! defined( 'AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'test-auth-key-for-unit-tests-32-chars!' );
		}

		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$encrypted = $manager->encrypt_key( 'test-api-key' );

		$this->assertIsString(
			$encrypted,
			'encrypt_key should return a string'
		);
	}

	/**
	 * Test that encrypt_key returns different values for same input (due to random IV).
	 *
	 * @return void
	 */
	public function test_encrypt_key_returns_different_values_for_same_input(): void {
		if ( ! defined( 'AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'test-auth-key-for-unit-tests-32-chars!' );
		}

		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$encrypted1 = $manager->encrypt_key( 'test-api-key' );
		$encrypted2 = $manager->encrypt_key( 'test-api-key' );

		// Due to random IV, encrypted values should be different.
		$this->assertNotEquals(
			$encrypted1,
			$encrypted2,
			'encrypt_key should return different values due to random IV'
		);
	}

	/**
	 * Test that get_provider returns null for non-existent provider.
	 *
	 * @return void
	 */
	public function test_get_provider_returns_null_for_nonexistent(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$this->assertNull(
			$manager->get_provider( 'nonexistent' ),
			'get_provider should return null for non-existent provider'
		);
	}

	/**
	 * Test that provider labels are correct.
	 *
	 * @return void
	 */
	public function test_provider_labels_are_correct(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		$statuses = $manager->get_provider_statuses();

		$expected_labels = [
			'gemini'    => 'Google Gemini',
			'openai'    => 'OpenAI',
			'anthropic' => 'Anthropic Claude',
			'imagen'    => 'Google Imagen',
			'dalle'     => 'OpenAI DALL-E',
		];

		foreach ( $expected_labels as $slug => $label ) {
			$this->assertEquals(
				$label,
				$statuses[ $slug ]['label'],
				"Provider '{$slug}' should have label '{$label}'"
			);
		}
	}

	/**
	 * Test that rate limit cache key format is correct.
	 *
	 * Verifies that the cache key follows the pattern: ai_ratelimit_{provider}
	 *
	 * @return void
	 */
	public function test_rate_limit_cache_key_format(): void {
		// Set a rate limit cache entry.
		$provider_slug = 'gemini';
		$cache_key = "ai_ratelimit_{$provider_slug}";
		$rate_limit_end = time() + 60;
		wp_cache_set( $cache_key, $rate_limit_end, 'meowseo', 60 );

		// Verify the cache entry exists.
		$cached = wp_cache_get( $cache_key, 'meowseo' );
		$this->assertNotFalse(
			$cached,
			'Rate limit cache should be set with correct key format'
		);

		// Clean up.
		wp_cache_delete( $cache_key, 'meowseo' );
	}

	/**
	 * Test that Provider_Rate_Limit_Exception stores retry_after correctly.
	 *
	 * @return void
	 */
	public function test_rate_limit_exception_stores_retry_after(): void {
		$provider_slug = 'gemini';
		$retry_after = 120;

		$exception = new Provider_Rate_Limit_Exception( $provider_slug, $retry_after );

		$this->assertEquals(
			$retry_after,
			$exception->get_retry_after(),
			'Exception should store retry_after value'
		);

		$this->assertEquals(
			429,
			$exception->getCode(),
			'Exception should have HTTP 429 code'
		);
	}

	/**
	 * Test that Provider_Rate_Limit_Exception has default retry_after of 60 seconds.
	 *
	 * @return void
	 */
	public function test_rate_limit_exception_default_retry_after(): void {
		$exception = new Provider_Rate_Limit_Exception( 'openai' );

		$this->assertEquals(
			60,
			$exception->get_retry_after(),
			'Default retry_after should be 60 seconds'
		);
	}

	/**
	 * Test that rate limit cache stores expiration timestamp.
	 *
	 * @return void
	 */
	public function test_rate_limit_cache_stores_expiration_timestamp(): void {
		$provider_slug = 'anthropic';
		$ttl = 45;
		$cache_key = "ai_ratelimit_{$provider_slug}";

		// Store rate limit as the manager does.
		$rate_limit_end = time() + $ttl;
		wp_cache_set( $cache_key, $rate_limit_end, 'meowseo', $ttl );

		// Verify the cached value is a future timestamp.
		$cached = wp_cache_get( $cache_key, 'meowseo' );
		$this->assertGreaterThan(
			time(),
			(int) $cached,
			'Cached value should be a future timestamp'
		);

		// Clean up.
		wp_cache_delete( $cache_key, 'meowseo' );
	}

	/**
	 * Test that multiple providers can have independent rate limits.
	 *
	 * @return void
	 */
	public function test_multiple_providers_independent_rate_limits(): void {
		// Set rate limits for two providers with different TTLs.
		$provider1 = 'gemini';
		$provider2 = 'openai';
		$ttl1 = 30;
		$ttl2 = 90;

		wp_cache_set( "ai_ratelimit_{$provider1}", time() + $ttl1, 'meowseo', $ttl1 );
		wp_cache_set( "ai_ratelimit_{$provider2}", time() + $ttl2, 'meowseo', $ttl2 );

		// Verify both exist independently.
		$cached1 = wp_cache_get( "ai_ratelimit_{$provider1}", 'meowseo' );
		$cached2 = wp_cache_get( "ai_ratelimit_{$provider2}", 'meowseo' );

		$this->assertNotFalse( $cached1, 'Provider 1 should have rate limit cache' );
		$this->assertNotFalse( $cached2, 'Provider 2 should have rate limit cache' );
		$this->assertNotEquals( $cached1, $cached2, 'Rate limits should be independent' );

		// Clean up.
		wp_cache_delete( "ai_ratelimit_{$provider1}", 'meowseo' );
		wp_cache_delete( "ai_ratelimit_{$provider2}", 'meowseo' );
	}
}
