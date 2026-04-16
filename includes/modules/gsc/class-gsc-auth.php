<?php
/**
 * GSC Authentication class
 *
 * Handles OAuth 2.0 authentication flow for Google Search Console API.
 * Manages token encryption, storage, refresh, and validation.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\GSC
 */

namespace MeowSEO\Modules\GSC;

use MeowSEO\Options;
use MeowSEO\Helpers\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * GSC_Auth class
 *
 * Implements OAuth 2.0 authentication and token management for Google Search Console.
 */
class GSC_Auth {

	/**
	 * Google OAuth authorization URL.
	 */
	private const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

	/**
	 * Google OAuth token URL.
	 */
	private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';

	/**
	 * Required OAuth scopes for GSC API access.
	 */
	private const SCOPES = [
		'https://www.googleapis.com/auth/webmasters',
		'https://www.googleapis.com/auth/indexing',
	];

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Generate OAuth consent URL.
	 *
	 * Creates the Google OAuth authorization URL with required parameters.
	 * Requirement 9.5: Provide get_auth_url() method that generates Google OAuth consent URL.
	 *
	 * @param string $redirect_uri OAuth callback URL.
	 * @return string Authorization URL.
	 */
	public function get_auth_url( string $redirect_uri ): string {
		$client_id = get_option( 'meowseo_gsc_client_id', '' );

		if ( empty( $client_id ) ) {
			Logger::warning(
				'Client ID not configured for GSC OAuth',
				[
					'module' => 'gsc',
				]
			);
			return '';
		}

		$params = [
			'client_id'     => $client_id,
			'redirect_uri'  => $redirect_uri,
			'response_type' => 'code',
			'scope'         => implode( ' ', self::SCOPES ),
			'access_type'   => 'offline',
			'prompt'        => 'consent',
		];

		return self::GOOGLE_AUTH_URL . '?' . http_build_query( $params );
	}

	/**
	 * Handle OAuth callback.
	 *
	 * Exchanges authorization code for access and refresh tokens.
	 * Requirement 9.6: Handle OAuth callback at admin.php?page=meowseo-gsc&action=oauth_callback.
	 *
	 * @param string $code Authorization code from Google.
	 * @return bool True on success, false on failure.
	 */
	public function handle_callback( string $code ): bool {
		$client_id     = get_option( 'meowseo_gsc_client_id', '' );
		$client_secret = get_option( 'meowseo_gsc_client_secret', '' );
		$redirect_uri  = admin_url( 'admin.php?page=meowseo-gsc&action=oauth_callback' );

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			Logger::error(
				'Client credentials not configured for GSC OAuth',
				[
					'module' => 'gsc',
				]
			);
			return false;
		}

		// Exchange authorization code for tokens.
		$response = wp_remote_post(
			self::GOOGLE_TOKEN_URL,
			[
				'body' => [
					'code'          => $code,
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri'  => $redirect_uri,
					'grant_type'    => 'authorization_code',
				],
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			Logger::error(
				'Failed to exchange authorization code for tokens',
				[
					'module' => 'gsc',
					'error'  => $response->get_error_message(),
				]
			);
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( 200 !== $status_code || empty( $data['access_token'] ) ) {
			Logger::error(
				'Invalid response from Google token endpoint',
				[
					'module'      => 'gsc',
					'status_code' => $status_code,
					'error'       => $data['error'] ?? 'unknown',
				]
			);
			return false;
		}

		// Store credentials with encryption.
		$credentials = [
			'access_token'  => $data['access_token'],
			'refresh_token' => $data['refresh_token'] ?? '',
			'expires_in'    => $data['expires_in'] ?? 3600,
			'token_expiry'  => time() + ( $data['expires_in'] ?? 3600 ),
		];

		$this->store_credentials( $credentials );

		// Set auth status to authenticated.
		update_option( 'meowseo_gsc_auth_status', 'authenticated' );

		Logger::info(
			'GSC OAuth authentication successful',
			[
				'module' => 'gsc',
			]
		);

		return true;
	}

	/**
	 * Get valid access token.
	 *
	 * Returns a valid access token, refreshing if expired.
	 * Requirement 9.3: Use refresh token to request new access token when expired.
	 *
	 * @return string|null Access token or null if unavailable.
	 */
	public function get_valid_token(): ?string {
		$access_token = $this->decrypt_token( get_option( 'meowseo_gsc_access_token', '' ) );
		$token_expiry = (int) get_option( 'meowseo_gsc_token_expiry', 0 );

		if ( empty( $access_token ) ) {
			return null;
		}

		// Check if token is expired (with 5 minute buffer).
		if ( time() >= ( $token_expiry - 300 ) ) {
			// Token expired, attempt refresh.
			if ( ! $this->refresh_token() ) {
				return null;
			}

			// Get refreshed token.
			$access_token = $this->decrypt_token( get_option( 'meowseo_gsc_access_token', '' ) );
		}

		return $access_token ?: null;
	}

	/**
	 * Refresh access token using refresh token.
	 *
	 * Requests a new access token from Google using the refresh token.
	 * Requirement 9.3: Use refresh token to request new access token when expired.
	 * Requirement 9.4: Set meowseo_gsc_auth_status to 'revoked' on refresh failure.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function refresh_token(): bool {
		$client_id     = get_option( 'meowseo_gsc_client_id', '' );
		$client_secret = get_option( 'meowseo_gsc_client_secret', '' );
		$refresh_token = $this->decrypt_token( get_option( 'meowseo_gsc_refresh_token', '' ) );

		if ( empty( $client_id ) || empty( $client_secret ) || empty( $refresh_token ) ) {
			Logger::error(
				'Cannot refresh token: missing credentials',
				[
					'module' => 'gsc',
				]
			);
			update_option( 'meowseo_gsc_auth_status', 'revoked' );
			return false;
		}

		// Request new access token.
		$response = wp_remote_post(
			self::GOOGLE_TOKEN_URL,
			[
				'body' => [
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
				],
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			Logger::error(
				'Failed to refresh access token',
				[
					'module' => 'gsc',
					'error'  => $response->get_error_message(),
				]
			);
			update_option( 'meowseo_gsc_auth_status', 'revoked' );
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( 200 !== $status_code || empty( $data['access_token'] ) ) {
			Logger::error(
				'Invalid response from Google token refresh',
				[
					'module'      => 'gsc',
					'status_code' => $status_code,
					'error'       => $data['error'] ?? 'unknown',
				]
			);
			update_option( 'meowseo_gsc_auth_status', 'revoked' );
			return false;
		}

		// Update access token and expiry.
		$encrypted_token = $this->encrypt_token( $data['access_token'] );
		update_option( 'meowseo_gsc_access_token', $encrypted_token );
		update_option( 'meowseo_gsc_token_expiry', time() + ( $data['expires_in'] ?? 3600 ) );

		Logger::info(
			'GSC access token refreshed successfully',
			[
				'module' => 'gsc',
			]
		);

		return true;
	}

	/**
	 * Encrypt token using AES-256-CBC.
	 *
	 * Uses WordPress AUTH_KEY constant for encryption.
	 * Requirement 9.2: Encrypt Access Token, Refresh Token, and Token Expiry using openssl_encrypt with AUTH_KEY.
	 *
	 * @param string $token Token to encrypt.
	 * @return string Encrypted token (base64 encoded).
	 */
	public function encrypt_token( string $token ): string {
		if ( empty( $token ) ) {
			return '';
		}

		if ( ! defined( 'AUTH_KEY' ) || empty( AUTH_KEY ) ) {
			Logger::error(
				'AUTH_KEY not defined, cannot encrypt token',
				[
					'module' => 'gsc',
				]
			);
			return '';
		}

		// Use first 32 bytes of AUTH_KEY as encryption key.
		$key = substr( hash( 'sha256', AUTH_KEY, true ), 0, 32 );

		// Use first 16 bytes of AUTH_KEY as IV.
		$iv = substr( hash( 'sha256', AUTH_KEY, true ), 0, 16 );

		$encrypted = openssl_encrypt( $token, 'AES-256-CBC', $key, 0, $iv );

		if ( false === $encrypted ) {
			Logger::error(
				'Failed to encrypt token',
				[
					'module' => 'gsc',
				]
			);
			return '';
		}

		return base64_encode( $encrypted );
	}

	/**
	 * Decrypt token using AES-256-CBC.
	 *
	 * Uses WordPress AUTH_KEY constant for decryption.
	 * Requirement 9.2: Encrypt Access Token, Refresh Token, and Token Expiry using openssl_encrypt with AUTH_KEY.
	 *
	 * @param string $encrypted Encrypted token (base64 encoded).
	 * @return string Decrypted token.
	 */
	public function decrypt_token( string $encrypted ): string {
		if ( empty( $encrypted ) ) {
			return '';
		}

		if ( ! defined( 'AUTH_KEY' ) || empty( AUTH_KEY ) ) {
			Logger::error(
				'AUTH_KEY not defined, cannot decrypt token',
				[
					'module' => 'gsc',
				]
			);
			return '';
		}

		// Use first 32 bytes of AUTH_KEY as encryption key.
		$key = substr( hash( 'sha256', AUTH_KEY, true ), 0, 32 );

		// Use first 16 bytes of AUTH_KEY as IV.
		$iv = substr( hash( 'sha256', AUTH_KEY, true ), 0, 16 );

		$decoded = base64_decode( $encrypted, true );

		if ( false === $decoded ) {
			Logger::error(
				'Failed to decode encrypted token',
				[
					'module' => 'gsc',
				]
			);
			return '';
		}

		$decrypted = openssl_decrypt( $decoded, 'AES-256-CBC', $key, 0, $iv );

		if ( false === $decrypted ) {
			Logger::error(
				'Failed to decrypt token',
				[
					'module' => 'gsc',
				]
			);
			return '';
		}

		return $decrypted;
	}

	/**
	 * Store OAuth credentials.
	 *
	 * Encrypts and stores access token, refresh token, and token expiry.
	 * Requirement 9.1: Store Client ID and Client Secret in plugin options.
	 * Requirement 9.2: Encrypt Access Token, Refresh Token, and Token Expiry.
	 *
	 * @param array $credentials Credentials array with access_token, refresh_token, and token_expiry.
	 * @return void
	 */
	public function store_credentials( array $credentials ): void {
		// Encrypt tokens before storage.
		if ( ! empty( $credentials['access_token'] ) ) {
			$encrypted_access = $this->encrypt_token( $credentials['access_token'] );
			update_option( 'meowseo_gsc_access_token', $encrypted_access );
		}

		if ( ! empty( $credentials['refresh_token'] ) ) {
			$encrypted_refresh = $this->encrypt_token( $credentials['refresh_token'] );
			update_option( 'meowseo_gsc_refresh_token', $encrypted_refresh );
		}

		if ( ! empty( $credentials['token_expiry'] ) ) {
			update_option( 'meowseo_gsc_token_expiry', (int) $credentials['token_expiry'] );
		}

		Logger::info(
			'GSC credentials stored successfully',
			[
				'module' => 'gsc',
			]
		);
	}
}
