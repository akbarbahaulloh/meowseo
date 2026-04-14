<?php
/**
 * Options Tests
 *
 * Unit tests for the Options class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Options;

/**
 * Options test case
 *
 * @since 1.0.0
 */
class Test_Options extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = new Options();
	}

	/**
	 * Test Options instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( Options::class, $this->options );
	}

	/**
	 * Test default values are set
	 *
	 * @return void
	 */
	public function test_default_values_are_set(): void {
		$enabled_modules = $this->options->get_enabled_modules();
		$separator = $this->options->get_separator();
		$delete_on_uninstall = $this->options->is_delete_on_uninstall();

		$this->assertIsArray( $enabled_modules );
		$this->assertContains( 'meta', $enabled_modules, 'Meta module should be enabled by default' );
		$this->assertEquals( '|', $separator, 'Default separator should be |' );
		$this->assertFalse( $delete_on_uninstall, 'Delete on uninstall should be false by default' );
	}

	/**
	 * Test get method returns correct values
	 *
	 * @return void
	 */
	public function test_get_returns_correct_values(): void {
		$this->options->set( 'test_key', 'test_value' );

		$value = $this->options->get( 'test_key' );
		$this->assertEquals( 'test_value', $value );
	}

	/**
	 * Test get method returns default for non-existent keys
	 *
	 * @return void
	 */
	public function test_get_returns_default_for_nonexistent_keys(): void {
		$value = $this->options->get( 'nonexistent_key', 'default_value' );
		$this->assertEquals( 'default_value', $value );
	}

	/**
	 * Test set method updates values
	 *
	 * @return void
	 */
	public function test_set_updates_values(): void {
		$this->options->set( 'separator', '-' );
		$separator = $this->options->get_separator();

		$this->assertEquals( '-', $separator );
	}

	/**
	 * Test get_enabled_modules returns array
	 *
	 * @return void
	 */
	public function test_get_enabled_modules_returns_array(): void {
		$modules = $this->options->get_enabled_modules();

		$this->assertIsArray( $modules );
	}

	/**
	 * Test get_enabled_modules returns empty array for invalid data
	 *
	 * @return void
	 */
	public function test_get_enabled_modules_returns_empty_array_for_invalid_data(): void {
		$this->options->set( 'enabled_modules', 'invalid_string' );
		$modules = $this->options->get_enabled_modules();

		$this->assertIsArray( $modules );
		$this->assertEmpty( $modules );
	}

	/**
	 * Test get_separator returns string
	 *
	 * @return void
	 */
	public function test_get_separator_returns_string(): void {
		$separator = $this->options->get_separator();

		$this->assertIsString( $separator );
	}

	/**
	 * Test is_delete_on_uninstall returns boolean
	 *
	 * @return void
	 */
	public function test_is_delete_on_uninstall_returns_boolean(): void {
		$delete = $this->options->is_delete_on_uninstall();

		$this->assertIsBool( $delete );
	}

	/**
	 * Test get_all returns all options
	 *
	 * @return void
	 */
	public function test_get_all_returns_all_options(): void {
		$this->options->set( 'test_key', 'test_value' );
		$all = $this->options->get_all();

		$this->assertIsArray( $all );
		$this->assertArrayHasKey( 'test_key', $all );
		$this->assertEquals( 'test_value', $all['test_key'] );
	}

	/**
	 * Test save persists options
	 *
	 * @return void
	 */
	public function test_save_persists_options(): void {
		$this->options->set( 'test_key', 'test_value' );
		$result = $this->options->save();

		$this->assertTrue( $result );
	}

	/**
	 * Test delete removes all options
	 *
	 * @return void
	 */
	public function test_delete_removes_all_options(): void {
		$this->options->set( 'test_key', 'test_value' );
		$result = $this->options->delete();

		$this->assertTrue( $result );

		$all = $this->options->get_all();
		$this->assertEmpty( $all );
	}

	/**
	 * Test credential encryption and decryption round-trip
	 *
	 * This test verifies that credentials can be encrypted and decrypted correctly.
	 *
	 * @return void
	 */
	public function test_credential_encryption_round_trip(): void {
		// Skip if WordPress constants are not defined.
		if ( ! defined( 'AUTH_KEY' ) || ! defined( 'SECURE_AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'test-auth-key-for-encryption' );
			define( 'SECURE_AUTH_KEY', 'test-secure-auth-key-for-encryption' );
		}

		$credentials = array(
			'client_id'     => 'test_client_id',
			'client_secret' => 'test_client_secret',
			'access_token'  => 'test_access_token',
			'refresh_token' => 'test_refresh_token',
		);

		// Set credentials.
		$result = $this->options->set_gsc_credentials( $credentials );
		$this->assertTrue( $result, 'Setting credentials should succeed' );

		// Get credentials.
		$retrieved = $this->options->get_gsc_credentials();

		$this->assertIsArray( $retrieved );
		$this->assertEquals( $credentials['client_id'], $retrieved['client_id'] );
		$this->assertEquals( $credentials['client_secret'], $retrieved['client_secret'] );
		$this->assertEquals( $credentials['access_token'], $retrieved['access_token'] );
		$this->assertEquals( $credentials['refresh_token'], $retrieved['refresh_token'] );
	}

	/**
	 * Test get_gsc_credentials returns null when not set
	 *
	 * @return void
	 */
	public function test_get_gsc_credentials_returns_null_when_not_set(): void {
		$credentials = $this->options->get_gsc_credentials();
		$this->assertNull( $credentials );
	}

	/**
	 * Test delete_gsc_credentials removes credentials
	 *
	 * @return void
	 */
	public function test_delete_gsc_credentials_removes_credentials(): void {
		if ( ! defined( 'AUTH_KEY' ) || ! defined( 'SECURE_AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'test-auth-key-for-encryption' );
			define( 'SECURE_AUTH_KEY', 'test-secure-auth-key-for-encryption' );
		}

		$credentials = array(
			'client_id' => 'test_client_id',
		);

		$this->options->set_gsc_credentials( $credentials );
		$result = $this->options->delete_gsc_credentials();

		$this->assertTrue( $result );

		$retrieved = $this->options->get_gsc_credentials();
		$this->assertNull( $retrieved );
	}
}
