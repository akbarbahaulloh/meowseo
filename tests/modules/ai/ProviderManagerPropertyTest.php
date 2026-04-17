<?php
/**
 * AI_Provider_Manager Property-Based Tests
 *
 * Property-based tests for the AI Provider Manager encryption implementation.
 *
 * @package MeowSEO\Tests\Modules\AI
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;
use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Options;

/**
 * AI_Provider_Manager property-based test case
 *
 * Property-based tests that verify encryption round-trip properties hold
 * across all possible inputs.
 *
 * **Validates: Requirements 2.3, 24.1, 24.2, 24.3, 24.4, 24.5**
 *
 * @since 1.0.0
 */
class ProviderManagerPropertyTest extends TestCase {

	use TestTrait;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Ensure AUTH_KEY is defined for encryption tests.
		if ( ! defined( 'AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'test-auth-key-for-unit-tests-32-chars!' );
		}
	}

	/**
	 * Property 2: Encryption Round-Trip
	 *
	 * Test that any string encrypts and decrypts to the same value.
	 * This property must hold for all possible API key strings.
	 *
	 * **Validates: Requirements 2.3, 24.1, 24.2, 24.3, 24.4, 24.5**
	 *
	 * @return void
	 */
	public function test_encryption_round_trip_property() {
		$this->forAll(
			Generator\string()
		)->then(
			function ( $api_key ) {
				// Skip empty strings as they're not valid API keys.
				if ( empty( $api_key ) ) {
					return;
				}

				$options = $this->createMock( Options::class );
				$options->method( 'get' )->willReturn( [] );

				$manager = new AI_Provider_Manager( $options );

				// Encrypt the API key.
				$encrypted = $manager->encrypt_key( $api_key );

				// Encryption should succeed.
				$this->assertNotFalse(
					$encrypted,
					'encrypt_key should succeed for any non-empty string'
				);

				// Use reflection to access the private decrypt_key method.
				$reflection = new \ReflectionClass( $manager );
				$decrypt_method = $reflection->getMethod( 'decrypt_key' );
				$decrypt_method->setAccessible( true );

				// Decrypt the encrypted value.
				$decrypted = $decrypt_method->invoke( $manager, $encrypted );

				// The round-trip should produce the original value.
				$this->assertEquals(
					$api_key,
					$decrypted,
					'Encryption round-trip should preserve the original API key'
				);
			}
		);
	}

	/**
	 * Property: Encryption produces different outputs for same input.
	 *
	 * Test that encrypting the same value twice produces different results
	 * due to the random IV generation.
	 *
	 * **Validates: Requirements 24.3**
	 *
	 * @return void
	 */
	public function test_encryption_produces_different_outputs_for_same_input() {
		$this->forAll(
			Generator\string()
		)->then(
			function ( $api_key ) {
				// Skip empty strings.
				if ( empty( $api_key ) ) {
					return;
				}

				$options = $this->createMock( Options::class );
				$options->method( 'get' )->willReturn( [] );

				$manager = new AI_Provider_Manager( $options );

				$encrypted1 = $manager->encrypt_key( $api_key );
				$encrypted2 = $manager->encrypt_key( $api_key );

				// Both encryptions should succeed.
				$this->assertNotFalse( $encrypted1, 'First encryption should succeed' );
				$this->assertNotFalse( $encrypted2, 'Second encryption should succeed' );

				// Due to random IV, encrypted values should be different.
				$this->assertNotEquals(
					$encrypted1,
					$encrypted2,
					'encrypt_key should return different values due to random IV'
				);
			}
		);
	}

	/**
	 * Property: Encrypted output is base64-encoded.
	 *
	 * Test that the encrypted output is always valid base64.
	 *
	 * **Validates: Requirements 24.4**
	 *
	 * @return void
	 */
	public function test_encrypted_output_is_base64_encoded() {
		$this->forAll(
			Generator\string()
		)->then(
			function ( $api_key ) {
				// Skip empty strings.
				if ( empty( $api_key ) ) {
					return;
				}

				$options = $this->createMock( Options::class );
				$options->method( 'get' )->willReturn( [] );

				$manager = new AI_Provider_Manager( $options );

				$encrypted = $manager->encrypt_key( $api_key );

				$this->assertNotFalse( $encrypted, 'Encryption should succeed' );

				// Verify it's valid base64.
				$decoded = base64_decode( $encrypted, true );
				$this->assertNotFalse(
					$decoded,
					'Encrypted output should be valid base64'
				);

				// Verify the decoded output is at least 16 bytes (IV) + some data.
				$this->assertGreaterThanOrEqual(
					16,
					strlen( $decoded ),
					'Decoded output should contain at least IV (16 bytes)'
				);
			}
		);
	}

	/**
	 * Property: Encryption with various key lengths.
	 *
	 * Test that encryption works correctly for API keys of various lengths.
	 *
	 * **Validates: Requirements 24.1, 24.2, 24.3, 24.4, 24.5**
	 *
	 * @return void
	 */
	public function test_encryption_with_various_key_lengths() {
		// Generate strings of various lengths.
		$this->forAll(
			Generator\bind(
				Generator\choose( 1, 200 ),
				function ( $length ) {
					return Generator\vector( $length, Generator\elements( ...range( 'a', 'z' ) ) );
				}
			)
		)->then(
			function ( $chars ) {
				$api_key = implode( '', $chars );

				$options = $this->createMock( Options::class );
				$options->method( 'get' )->willReturn( [] );

				$manager = new AI_Provider_Manager( $options );

				$encrypted = $manager->encrypt_key( $api_key );

				$this->assertNotFalse(
					$encrypted,
					"Encryption should succeed for key of length " . strlen( $api_key )
				);

				// Verify round-trip.
				$reflection = new \ReflectionClass( $manager );
				$decrypt_method = $reflection->getMethod( 'decrypt_key' );
				$decrypt_method->setAccessible( true );

				$decrypted = $decrypt_method->invoke( $manager, $encrypted );

				$this->assertEquals(
					$api_key,
					$decrypted,
					'Round-trip should preserve API key of length ' . strlen( $api_key )
				);
			}
		);
	}

	/**
	 * Property: Encryption with special characters.
	 *
	 * Test that encryption handles special characters correctly.
	 *
	 * **Validates: Requirements 24.1, 24.2, 24.3, 24.4, 24.5**
	 *
	 * @return void
	 */
	public function test_encryption_with_special_characters() {
		$special_chars = '!@#$%^&*()_+-=[]{}|;:\'",.<>?/~`';

		$this->forAll(
			Generator\string( Generator\elements( ...str_split( $special_chars ) ) )
		)->then(
			function ( $api_key ) {
				// Skip empty strings.
				if ( empty( $api_key ) ) {
					return;
				}

				$options = $this->createMock( Options::class );
				$options->method( 'get' )->willReturn( [] );

				$manager = new AI_Provider_Manager( $options );

				$encrypted = $manager->encrypt_key( $api_key );

				$this->assertNotFalse(
					$encrypted,
					'Encryption should succeed for special character strings'
				);

				// Verify round-trip.
				$reflection = new \ReflectionClass( $manager );
				$decrypt_method = $reflection->getMethod( 'decrypt_key' );
				$decrypt_method->setAccessible( true );

				$decrypted = $decrypt_method->invoke( $manager, $encrypted );

				$this->assertEquals(
					$api_key,
					$decrypted,
					'Round-trip should preserve special characters'
				);
			}
		);
	}
}
