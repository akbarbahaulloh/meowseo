<?php
/**
 * Redirects Module
 *
 * Handles URL redirects with database-level matching for performance.
 * Never loads all redirect rules into PHP memory.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Redirects;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\DB;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirects module class
 *
 * Implements database-level redirect matching with exact-match query first,
 * then regex fallback. Never loads all rules into memory.
 *
 * @since 1.0.0
 */
class Redirects implements Module {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * REST API handler instance
	 *
	 * @var Redirects_REST
	 */
	private Redirects_REST $rest;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->rest = new Redirects_REST( $options );
	}

	/**
	 * Boot the module
	 *
	 * Register hooks for redirect functionality.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Hook early into wp action to check redirects before template loading (Requirement 7.1)
		add_action( 'wp', array( $this, 'check_redirect' ), 1 );

		// Register REST API endpoints
		add_action( 'rest_api_init', array( $this->rest, 'register_routes' ) );
	}

	/**
	 * Get module ID
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'redirects';
	}

	/**
	 * Check for redirect match and execute if found
	 *
	 * Implements database-level matching algorithm:
	 * 1. Exact-match query on indexed source_url (O(log n))
	 * 2. Regex fallback only if meowseo_has_regex_rules flag is true
	 * 3. Never loads all redirect rules into PHP memory (Requirement 14.6)
	 *
	 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 14.6
	 *
	 * @return void
	 */
	public function check_redirect(): void {
		// Get current request URL
		$request_url = $this->get_request_url();

		if ( empty( $request_url ) ) {
			return;
		}

		// Normalize URL (strip query string if configured)
		$normalized_url = $this->normalize_url( $request_url );

		// Step 1: Try exact match first (Requirement 7.2)
		$redirect = DB::get_redirect_exact( $normalized_url );

		if ( $redirect ) {
			$this->execute_redirect( $redirect );
			return;
		}

		// Step 2: Check if regex rules exist (Requirement 7.3, 7.4)
		$has_regex_rules = $this->options->get( 'has_regex_rules', false );

		if ( ! $has_regex_rules ) {
			// No regex rules, skip regex matching entirely
			return;
		}

		// Step 3: Load only regex rules and evaluate in PHP (Requirement 7.3)
		$regex_rules = DB::get_redirect_regex_rules();

		foreach ( $regex_rules as $rule ) {
			// Evaluate regex pattern
			$pattern = $rule['source_url'];

			// Ensure pattern has delimiters
			if ( ! $this->has_regex_delimiters( $pattern ) ) {
				$pattern = '#' . $pattern . '#i';
			}

			// Suppress warnings for invalid regex patterns
			$match = @preg_match( $pattern, $normalized_url, $matches );

			if ( $match ) {
				// Support backreferences in target URL
				$target_url = $rule['target_url'];

				if ( ! empty( $matches ) ) {
					// Replace $1, $2, etc. with captured groups
					for ( $i = 1; $i < count( $matches ); $i++ ) {
						$target_url = str_replace( '$' . $i, $matches[ $i ], $target_url );
					}
				}

				// Create redirect array with resolved target URL
				$redirect = $rule;
				$redirect['target_url'] = $target_url;

				$this->execute_redirect( $redirect );
				return;
			}
		}
	}

	/**
	 * Execute redirect
	 *
	 * Issues HTTP redirect and logs hit count.
	 * Supports redirect types: 301, 302, 307, 410 (Requirement 7.5)
	 *
	 * @param array $redirect Redirect rule array.
	 * @return void
	 */
	private function execute_redirect( array $redirect ): void {
		$redirect_type = absint( $redirect['redirect_type'] ?? 301 );
		$target_url = $redirect['target_url'] ?? '';
		$redirect_id = absint( $redirect['id'] ?? 0 );

		// Log hit count asynchronously (Requirement 7.6)
		if ( $redirect_id > 0 ) {
			DB::increment_redirect_hit( $redirect_id );
		}

		// Handle 410 Gone status
		if ( 410 === $redirect_type ) {
			status_header( 410 );
			nocache_headers();
			echo '<!DOCTYPE html><html><head><title>410 Gone</title></head><body><h1>410 Gone</h1><p>This resource is no longer available.</p></body></html>';
			exit;
		}

		// Execute redirect for 301, 302, 307
		if ( ! empty( $target_url ) ) {
			nocache_headers();
			wp_redirect( $target_url, $redirect_type );
			exit;
		}
	}

	/**
	 * Get current request URL
	 *
	 * @return string Request URL.
	 */
	private function get_request_url(): string {
		$scheme = is_ssl() ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'] ?? '';
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';

		if ( empty( $host ) || empty( $request_uri ) ) {
			return '';
		}

		return $scheme . '://' . $host . $request_uri;
	}

	/**
	 * Normalize URL
	 *
	 * Strips query string if configured.
	 *
	 * @param string $url URL to normalize.
	 * @return string Normalized URL.
	 */
	private function normalize_url( string $url ): string {
		// Option to strip query strings from matching
		$strip_query = $this->options->get( 'redirect_strip_query', false );

		if ( $strip_query ) {
			$url = strtok( $url, '?' );
		}

		// Remove trailing slash for consistency
		$url = rtrim( $url, '/' );

		return $url;
	}

	/**
	 * Check if pattern has regex delimiters
	 *
	 * @param string $pattern Pattern to check.
	 * @return bool True if has delimiters, false otherwise.
	 */
	private function has_regex_delimiters( string $pattern ): bool {
		if ( empty( $pattern ) ) {
			return false;
		}

		$first_char = $pattern[0];
		$delimiters = array( '/', '#', '~', '@', ';', '%', '`' );

		return in_array( $first_char, $delimiters, true );
	}
}
