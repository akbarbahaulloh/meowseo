<?php
/**
 * Provider_Gemini Test Case
 *
 * Unit tests for the Gemini AI provider implementation.
 *
 * @package MeowSEO\Tests\Modules\AI
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\Contracts\AI_Provider;
use MeowSEO\Modules\AI\Providers\Provider_Gemini;
use MeowSEO\Modules\AI\Exceptions\Provider_Exception;
use MeowSEO\Modules\AI\Exceptions\Provider_Rate_Limit_Exception;
use MeowSEO\Modules\AI\Exceptions\Provider_Auth_Exception;

/**
 * Provider_Gemini test case
 *
 * Tests that the Gemini provider correctly implements the AI_Provider interface.
 * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 17.7, 22.1, 22.2, 23.1, 23.5
 *
 * @since 1.0.0
 */
class ProviderGeminiTest extends TestCase {

	/**
	 * Test that Provider_Gemini class can be loaded by autoloader.
	 *
	 * @return void
	 */
	public function test_provider_gemini_class_can_be_loaded(): void {
		$this->assertTrue(
			class_exists( Provider_Gemini::class ),
			'Provider_Gemini class should be loadable by autoloader'
		);
	}

	/**
	 * Test that Provider_Gemini implements AI_Provider interface.
	 *
	 * @return void
	 */
	public function test_provider_gemini_implements_ai_provider_interface(): void {
		$this->assertTrue(
			in_array(
				AI_Provider::class,
				class_implements( Provider_Gemini::class ),
				true
			),
			'Provider_Gemini should implement AI_Provider interface'
		);
	}

	/**
	 * Test that Provider_Gemini can be instantiated.
	 *
	 * @return void
	 */
	public function test_provider_gemini_can_be_instantiated(): void {
		$provider = new Provider_Gemini( 'test-api-key' );

		$this->assertInstanceOf(
			Provider_Gemini::class,
			$provider,
			'Provider_Gemini should be instantiable'
		);
	}

	/**
	 * Test that get_slug returns correct value.
	 *
	 * @return void
	 */
	public function test_get_slug_returns_correct_value(): void {
		$provider = new Provider_Gemini( 'test-api-key' );

		$this->assertEquals(
			'gemini',
			$provider->get_slug(),
			'get_slug should return "gemini"'
		);
	}

	/**
	 * Test that get_label returns correct value.
	 *
	 * @return void
	 */
	public function test_get_label_returns_correct_value(): void {
		$provider = new Provider_Gemini( 'test-api-key' );

		$this->assertEquals(
			'Google Gemini',
			$provider->get_label(),
			'get_label should return "Google Gemini"'
		);
	}

	/**
	 * Test that supports_text returns true.
	 *
	 * @return void
	 */
	public function test_supports_text_returns_true(): void {
		$provider = new Provider_Gemini( 'test-api-key' );

		$this->assertTrue(
			$provider->supports_text(),
			'supports_text should return true for Gemini'
		);
	}

	/**
	 * Test that supports_image returns false.
	 *
	 * @return void
	 */
	public function test_supports_image_returns_false(): void {
		$provider = new Provider_Gemini( 'test-api-key' );

		$this->assertFalse(
			$provider->supports_image(),
			'supports_image should return false for Gemini'
		);
	}

	/**
	 * Test that generate_image throws Provider_Exception.
	 *
	 * @return void
	 */
	public function test_generate_image_throws_provider_exception(): void {
		$this->expectException( Provider_Exception::class );

		$provider = new Provider_Gemini( 'test-api-key' );
		$provider->generate_image( 'test prompt' );
	}

	/**
	 * Test that generate_image exception has correct provider slug.
	 *
	 * @return void
	 */
	public function test_generate_image_exception_has_correct_provider_slug(): void {
		$provider = new Provider_Gemini( 'test-api-key' );

		try {
			$provider->generate_image( 'test prompt' );
		} catch ( Provider_Exception $e ) {
			$this->assertEquals(
				'gemini',
				$e->get_provider_slug(),
				'Exception should have correct provider slug'
			);
		}
	}

	/**
	 * Test that get_last_error returns null initially.
	 *
	 * @return void
	 */
	public function test_get_last_error_returns_null_initially(): void {
		$provider = new Provider_Gemini( 'test-api-key' );

		$this->assertNull(
			$provider->get_last_error(),
			'get_last_error should return null initially'
		);
	}
}
