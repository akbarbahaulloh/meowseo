<?php
/**
 * Options class for centralized settings management.
 *
 * Stores all settings as a single serialized array under meowseo_options.
 *
 * @package MeowSEO
 */

namespace MeowSEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options class.
 */
class Options {

	/**
	 * Option key in wp_options table.
	 */
	private const OPTION_KEY = 'meowseo_options';

	/**
	 * Cached options array.
	 *
	 * @var array
	 */
	private array $options = array();

	/**
	 * Constructor.
	 *
	 * Loads options from database.
	 */
	public function __construct() {
		$this->load();
	}

	/**
	 * Load options from database.
	 *
	 * @return void
	 */
	private function load(): void {
		$options = get_option( self::OPTION_KEY, array() );
		$this->options = is_array( $options ) ? $options : array();

		// Set defaults if not already set.
		$this->set_defaults();
	}

	/**
	 * Set default values for options.
	 *
	 * @return void
	 */
	private function set_defaults(): void {
		$defaults = array(
			'enabled_modules'        => array( 'meta', 'schema', 'sitemap', 'redirects', 'monitor_404', 'internal_links', 'gsc', 'social', 'ai', 'import', 'image_seo', 'indexnow', 'roles', 'multilingual', 'multisite', 'locations', 'bulk', 'analytics', 'admin-bar', 'orphaned', 'synonyms' ), // Core modules enabled by default.
			'separator'              => '|',
			'default_social_image'   => '',
			'delete_on_uninstall'    => false,
			'has_regex_rules'        => false, // Performance flag for redirect module.
			'ai_profiles'            => array(),
			'writing_styles'         => array(),
			'image_styles'           => array(),
		);

		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if key doesn't exist.
	 * @return mixed Setting value.
	 */
	public function get( string $key, mixed $default = null ): mixed {
		return $this->options[ $key ] ?? $default;
	}

	/**
	 * Set a setting value.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return void
	 */
	public function set( string $key, mixed $value ): void {
		$this->options[ $key ] = $value;
	}

	/**
	 * Save options to database.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save(): bool {
		return update_option( self::OPTION_KEY, $this->options );
	}

	/**
	 * Get enabled modules.
	 *
	 * @return array Array of enabled module IDs.
	 */
	public function get_enabled_modules(): array {
		return array(
			'meta',
			'schema',
			'sitemap',
			'redirects',
			'monitor_404',
			'internal_links',
			'gsc',
			'social',
			'woocommerce',
			'ai',
			'import',
			'image_seo',
			'indexnow',
			'roles',
			'multilingual',
			'multisite',
			'locations',
			'bulk',
			'analytics',
			'admin-bar',
			'orphaned',
			'synonyms',
		);
	}

	/**
	 * Get default enabled modules.
	 *
	 * These modules are enabled by default on fresh installation.
	 *
	 * @return array Array of default module IDs.
	 */
	private function get_default_modules(): array {
		return array(
			'meta',          // SEO meta tags (required for Gutenberg sidebar)
			'schema',        // Schema.org structured data
			'sitemap',       // XML sitemaps
			'social',        // Open Graph and Twitter Cards
		);
	}

	/**
	 * Get title separator.
	 *
	 * @return string Title separator character.
	 */
	public function get_separator(): string {
		return (string) $this->get( 'separator', '|' );
	}

	/**
	 * Get default social image URL.
	 *
	 * @return string Default social image URL.
	 */
	public function get_default_social_image_url(): string {
		$image_id = $this->get( 'default_social_image', '' );
		
		if ( empty( $image_id ) ) {
			return '';
		}

		$image_url = wp_get_attachment_image_url( (int) $image_id, 'full' );
		return $image_url ? $image_url : '';
	}

	/**
	 * Check if data should be deleted on uninstall.
	 *
	 * @return bool True if data should be deleted, false otherwise.
	 */
	public function is_delete_on_uninstall(): bool {
		return (bool) $this->get( 'delete_on_uninstall', false );
	}

	/**
	 * Get all options.
	 *
	 * @return array All options.
	 */
	public function get_all(): array {
		return $this->options;
	}

	/**
	 * Delete all options from database.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete(): bool {
		$this->options = array();
		return delete_option( self::OPTION_KEY );
	}

	/**
	 * Get GSC credentials (decrypted).
	 *
	 * Returns decrypted OAuth credentials for Google Search Console.
	 * Never expose raw credentials via REST endpoints (Requirement 15.6).
	 *
	 * @return array|null Credentials array or null if not set.
	 */
	public function get_gsc_credentials(): ?array {
		$encrypted = get_option( 'meowseo_gsc_credentials', '' );

		if ( empty( $encrypted ) ) {
			return null;
		}

		// Decrypt credentials using WordPress secret keys.
		$decrypted = $this->decrypt_credentials( $encrypted );

		if ( ! $decrypted ) {
			return null;
		}

		return json_decode( $decrypted, true );
	}

	/**
	 * Set GSC credentials (encrypted).
	 *
	 * Encrypts and stores OAuth credentials using AES-256-CBC (Requirement 15.6).
	 *
	 * @param array $credentials Credentials array.
	 * @return bool True on success, false on failure.
	 */
	public function set_gsc_credentials( array $credentials ): bool {
		$json = wp_json_encode( $credentials );

		if ( ! $json ) {
			return false;
		}

		// Encrypt credentials using WordPress secret keys.
		$encrypted = $this->encrypt_credentials( $json );

		if ( ! $encrypted ) {
			return false;
		}

		return update_option( 'meowseo_gsc_credentials', $encrypted );
	}

	/**
	 * Delete GSC credentials.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_gsc_credentials(): bool {
		return delete_option( 'meowseo_gsc_credentials' );
	}

	/**
	 * Encrypt credentials using AES-256-CBC.
	 *
	 * Uses AUTH_KEY + SECURE_AUTH_KEY as encryption key (Requirement 15.6).
	 *
	 * @param string $data Data to encrypt.
	 * @return string|false Encrypted data (base64 encoded) or false on failure.
	 */
	private function encrypt_credentials( string $data ) {
		if ( ! defined( 'AUTH_KEY' ) || ! defined( 'SECURE_AUTH_KEY' ) ) {
			return false;
		}

		// Derive encryption key from WordPress secret keys.
		$key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true );

		// Generate random IV.
		$iv = openssl_random_pseudo_bytes( 16 );

		if ( false === $iv ) {
			return false;
		}

		// Encrypt data.
		$encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );

		if ( false === $encrypted ) {
			return false;
		}

		// Return base64-encoded IV + encrypted data.
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt credentials using AES-256-CBC.
	 *
	 * @param string $encrypted_data Encrypted data (base64 encoded).
	 * @return string|false Decrypted data or false on failure.
	 */
	private function decrypt_credentials( string $encrypted_data ) {
		if ( ! defined( 'AUTH_KEY' ) || ! defined( 'SECURE_AUTH_KEY' ) ) {
			return false;
		}

		// Derive encryption key from WordPress secret keys.
		$key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true );

		// Decode base64.
		$raw = base64_decode( $encrypted_data, true );

		if ( false === $raw || strlen( $raw ) < 16 ) {
			return false;
		}

		// Extract IV and encrypted data.
		$iv        = substr( $raw, 0, 16 );
		$encrypted = substr( $raw, 16 );

		// Decrypt data.
		$decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );

		return $decrypted;
	}
}
