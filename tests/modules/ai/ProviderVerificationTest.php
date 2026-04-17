<?php
/**
 * Provider Verification Test Case
 *
 * Comprehensive tests verifying all AI providers implement the interface correctly.
 *
 * @package MeowSEO\Tests\Modules\AI
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\Contracts\AI_Provider;
use MeowSEO\Modules\AI\Providers\Provider_Gemini;
use MeowSEO\Modules\AI\Providers\Provider_OpenAI;
use MeowSEO\Modules\AI\Providers\Provider_Anthropic;
use MeowSEO\Modules\AI\Providers\Provider_Imagen;
use MeowSEO\Modules\AI\Providers\Provider_Dalle;
use MeowSEO\Modules\AI\Exceptions\Provider_Exception;
use MeowSEO\Modules\AI\Exceptions\Provider_Rate_Limit_Exception;
use MeowSEO\Modules\AI\Exceptions\Provider_Auth_Exception;

/**
 * Provider verification test case
 *
 * Tests that all providers correctly implement the AI_Provider interface.
 * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 17.7, 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 18.7, 19.1, 19.2, 19.3, 19.4, 19.5, 19.6, 19.7, 20.1, 20.2, 20.3, 20.4, 20.5, 20.6, 20.7, 21.1, 21.2, 21.3, 21.4, 21.5, 21.6, 21.7, 22.1, 22.2, 23.1, 23.5
 *
 * @since 1.0.0
 */
class ProviderVerificationTest extends TestCase {

	/**
	 * Data provider for all provider classes.
	 *
	 * @return array Array of provider class names and their expected capabilities.
	 */
	public function provider_classes_provider(): array {
		return [
			'Gemini'     => [
				'class'          => Provider_Gemini::class,
				'supports_text'  => true,
				'supports_image' => false,
				'slug'           => 'gemini',
				'label'          => 'Google Gemini',
			],
			'OpenAI'     => [
				'class'          => Provider_OpenAI::class,
				'supports_text'  => true,
				'supports_image' => true,
				'slug'           => 'openai',
				'label'          => 'OpenAI',
			],
			'Anthropic'  => [
				'class'          => Provider_Anthropic::class,
				'supports_text'  => true,
				'supports_image' => false,
				'slug'           => 'anthropic',
				'label'          => 'Anthropic Claude',
			],
			'Imagen'     => [
				'class'          => Provider_Imagen::class,
				'supports_text'  => false,
				'supports_image' => true,
				'slug'           => 'imagen',
				'label'          => 'Google Imagen',
			],
			'DALL-E'     => [
				'class'          => Provider_Dalle::class,
				'supports_text'  => false,
				'supports_image' => true,
				'slug'           => 'dalle',
				'label'          => 'DALL-E',
			],
		];
	}

	/**
	 * Test that all provider classes can be loaded by autoloader.
	 *
	 * @dataProvider provider_classes_provider
	 *
	 * @param string $class The provider class name.
	 *
	 * @return void
	 */
	public function test_provider_class_can_be_loaded( string $class ): void {
		$this->assertTrue(
			class_exists( $class ),
			"{$class} class should be loadable by autoloader"
		);
	}

	/**
	 * Test that all providers implement AI_Provider interface.
	 *
	 * @dataProvider provider_classes_provider
	 *
	 * @param string $class The provider class name.
	 *
	 * @return void
	 */
	public function test_provider_implements_ai_provider_interface( string $class ): void {
		$this->assertTrue(
			in_array(
				AI_Provider::class,
				class_implements( $class ),
				true
			),
			"{$class} should implement AI_Provider interface"
		);
	}

	/**
	 * Test that all providers can be instantiated.
	 *
	 * @dataProvider provider_classes_provider
	 *
	 * @param string $class The provider class name.
	 *
	 * @return void
	 */
	public function test_provider_can_be_instantiated( string $class ): void {
		$provider = new $class( 'test-api-key' );

		$this->assertInstanceOf(
			$class,
			$provider,
			"{$class} should be instantiable"
		);
	}

	/**
	 * Test that get_slug returns correct value for all providers.
	 *
	 * @dataProvider provider_classes_provider
	 *
	 * @param string $class The provider class name.
	 * @param bool   $supports_text Expected supports_text value.
	 * @param bool   $supports_image Expected supports_image value.
	 * @param string $slug Expected slug value.
	 *
	 * @return void
	 */
	public function test_get_slug_returns_correct_value(
		string $class,
		bool $supports_text,
		bool $supports_image,
		string $slug
	): void {
		$provider = new $class( 'test-api-key' );

		$this->assertEquals(
			$slug,
			$provider->get_slug(),
			"{$class}::get_slug should return '{$slug}'"
		);
	}

	/**
	 * Test that get_label returns correct value for all providers.
	 *
	 * @dataProvider provider_classes_provider
	 *
	 * @param string $class The provider class name.
	 * @param bool   $supports_text Expected supports_text value.
	 * @param bool   $supports_image Expected supports_image value.
	 * @param string $slug Expected slug value.
	 * @param string $label Expected label value.
	 *
	 * @return void
	 */
	public function test_get_label_returns_correct_value(
		string $class,
		bool $supports_text,
		bool $supports_image,
		string $slug,
		string $label
	): void {
		$provider = new $class( 'test-api-key' );

		$this->assertEquals(
			$label,
			$provider->get_label(),
			"{$class}::get_label should return '{$label}'"
		);
	}

	/**
	 * Test that supports_text returns correct value for all providers.
	 *
	 * @dataProvider provider_classes_provider
	 *
	 * @param string $class The provider class name.
	 * @param bool   $supports_text Expected supports_text value.
	 *
	 * @return void
	 */
	public function test_supports_text_returns_correct_value(
		string $class,
		bool $supports_text
	): void {
		$provider = new $class( 'test-api-key' );

		$this->assertSame(
			$supports_text,
			$provider->supports_text(),
			"{$class}::supports_text should return " . ( $supports_text ? 'true' : 'false' )
		);
	}

	/**
	 * Test that supports_image returns correct value for all providers.
	 *
	 * @dataProvider provider_classes_provider
	 *
	 * @param string $class The provider class name.
	 * @param bool   $supports_text Expected supports_text value.
	 * @param bool   $supports_image Expected supports_image value.
	 *
	 * @return void
	 */
	public function test_supports_image_returns_correct_value(
		string $class,
		bool $supports_text,
		bool $supports_image
	): void {
		$provider = new $class( 'test-api-key' );

		$this->assertSame(
			$supports_image,
			$provider->supports_image(),
			"{$class}::supports_image should return " . ( $supports_image ? 'true' : 'false' )
		);
	}

	/**
	 * Test that get_last_error returns null initially for all providers.
	 *
	 * @dataProvider provider_classes_provider
	 *
	 * @param string $class The provider class name.
	 *
	 * @return void
	 */
	public function test_get_last_error_returns_null_initially( string $class ): void {
		$provider = new $class( 'test-api-key' );

		$this->assertNull(
			$provider->get_last_error(),
			"{$class}::get_last_error should return null initially"
		);
	}

	/**
	 * Data provider for text-only providers.
	 *
	 * @return array Array of text-only provider class names.
	 */
	public function text_only_providers_provider(): array {
		return [
			'Gemini'    => [ Provider_Gemini::class, 'gemini' ],
			'Anthropic' => [ Provider_Anthropic::class, 'anthropic' ],
		];
	}

	/**
	 * Test that text-only providers throw exception for generate_image.
	 *
	 * @dataProvider text_only_providers_provider
	 *
	 * @param string $class The provider class name.
	 * @param string $slug The provider slug.
	 *
	 * @return void
	 */
	public function test_text_only_providers_throw_exception_for_generate_image(
		string $class,
		string $slug
	): void {
		$this->expectException( Provider_Exception::class );

		$provider = new $class( 'test-api-key' );
		$provider->generate_image( 'test prompt' );
	}

	/**
	 * Test that text-only providers throw exception with correct provider slug.
	 *
	 * @dataProvider text_only_providers_provider
	 *
	 * @param string $class The provider class name.
	 * @param string $slug The provider slug.
	 *
	 * @return void
	 */
	public function test_text_only_providers_exception_has_correct_slug(
		string $class,
		string $slug
	): void {
		$provider = new $class( 'test-api-key' );

		try {
			$provider->generate_image( 'test prompt' );
		} catch ( Provider_Exception $e ) {
			$this->assertEquals(
				$slug,
				$e->get_provider_slug(),
				"Exception should have correct provider slug '{$slug}'"
			);
		}
	}

	/**
	 * Data provider for image-only providers.
	 *
	 * @return array Array of image-only provider class names.
	 */
	public function image_only_providers_provider(): array {
		return [
			'Imagen' => [ Provider_Imagen::class, 'imagen' ],
			'DALL-E' => [ Provider_Dalle::class, 'dalle' ],
		];
	}

	/**
	 * Test that image-only providers throw exception for generate_text.
	 *
	 * @dataProvider image_only_providers_provider
	 *
	 * @param string $class The provider class name.
	 * @param string $slug The provider slug.
	 *
	 * @return void
	 */
	public function test_image_only_providers_throw_exception_for_generate_text(
		string $class,
		string $slug
	): void {
		$this->expectException( Provider_Exception::class );

		$provider = new $class( 'test-api-key' );
		$provider->generate_text( 'test prompt' );
	}

	/**
	 * Test that image-only providers throw exception with correct provider slug.
	 *
	 * @dataProvider image_only_providers_provider
	 *
	 * @param string $class The provider class name.
	 * @param string $slug The provider slug.
	 *
	 * @return void
	 */
	public function test_image_only_providers_exception_has_correct_slug(
		string $class,
		string $slug
	): void {
		$provider = new $class( 'test-api-key' );

		try {
			$provider->generate_text( 'test prompt' );
		} catch ( Provider_Exception $e ) {
			$this->assertEquals(
				$slug,
				$e->get_provider_slug(),
				"Exception should have correct provider slug '{$slug}'"
			);
		}
	}

	/**
	 * Test that OpenAI supports both text and image.
	 *
	 * @return void
	 */
	public function test_openai_supports_both_text_and_image(): void {
		$provider = new Provider_OpenAI( 'test-api-key' );

		$this->assertTrue(
			$provider->supports_text(),
			'OpenAI should support text generation'
		);

		$this->assertTrue(
			$provider->supports_image(),
			'OpenAI should support image generation'
		);
	}

	/**
	 * Test that all exception classes can be instantiated.
	 *
	 * @return void
	 */
	public function test_exception_classes_can_be_instantiated(): void {
		$base_exception = new Provider_Exception( 'Test message', 'test-provider' );
		$this->assertInstanceOf( Provider_Exception::class, $base_exception );

		$auth_exception = new Provider_Auth_Exception( 'test-provider' );
		$this->assertInstanceOf( Provider_Auth_Exception::class, $auth_exception );
		$this->assertInstanceOf( Provider_Exception::class, $auth_exception );

		$rate_limit_exception = new Provider_Rate_Limit_Exception( 'test-provider', 120 );
		$this->assertInstanceOf( Provider_Rate_Limit_Exception::class, $rate_limit_exception );
		$this->assertInstanceOf( Provider_Exception::class, $rate_limit_exception );
	}

	/**
	 * Test that Provider_Auth_Exception has correct HTTP code.
	 *
	 * @return void
	 */
	public function test_auth_exception_has_correct_http_code(): void {
		$exception = new Provider_Auth_Exception( 'test-provider' );

		$this->assertEquals(
			401,
			$exception->getCode(),
			'Auth exception should have HTTP code 401'
		);
	}

	/**
	 * Test that Provider_Rate_Limit_Exception has correct HTTP code.
	 *
	 * @return void
	 */
	public function test_rate_limit_exception_has_correct_http_code(): void {
		$exception = new Provider_Rate_Limit_Exception( 'test-provider', 120 );

		$this->assertEquals(
			429,
			$exception->getCode(),
			'Rate limit exception should have HTTP code 429'
		);
	}

	/**
	 * Test that Provider_Rate_Limit_Exception stores retry_after value.
	 *
	 * @return void
	 */
	public function test_rate_limit_exception_stores_retry_after(): void {
		$exception = new Provider_Rate_Limit_Exception( 'test-provider', 120 );

		$this->assertEquals(
			120,
			$exception->get_retry_after(),
			'Rate limit exception should store retry_after value'
		);
	}

	/**
	 * Test that Provider_Rate_Limit_Exception has default retry_after.
	 *
	 * @return void
	 */
	public function test_rate_limit_exception_has_default_retry_after(): void {
		$exception = new Provider_Rate_Limit_Exception( 'test-provider' );

		$this->assertEquals(
			60,
			$exception->get_retry_after(),
			'Rate limit exception should have default retry_after of 60'
		);
	}

	/**
	 * Test that all exceptions have provider slug.
	 *
	 * @return void
	 */
	public function test_all_exceptions_have_provider_slug(): void {
		$base_exception = new Provider_Exception( 'Test', 'test-provider' );
		$this->assertEquals( 'test-provider', $base_exception->get_provider_slug() );

		$auth_exception = new Provider_Auth_Exception( 'auth-provider' );
		$this->assertEquals( 'auth-provider', $auth_exception->get_provider_slug() );

		$rate_limit_exception = new Provider_Rate_Limit_Exception( 'rate-provider' );
		$this->assertEquals( 'rate-provider', $rate_limit_exception->get_provider_slug() );
	}
}
