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
use MeowSEO\Helpers\Logger;
use MeowSEO\Options;
use WP_Post;

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
	 * Admin interface instance
	 *
	 * @var Redirects_Admin
	 */
	private Redirects_Admin $admin;

	/**
	 * Redirect chain tracking for loop detection
	 *
	 * @var array
	 */
	private array $redirect_chain = [];

	/**
	 * Pending hit tracking for shutdown hook
	 *
	 * @var int|null
	 */
	private ?int $pending_hit_redirect_id = null;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->rest = new Redirects_REST( $options );
		$this->admin = new Redirects_Admin( $options );
	}

	/**
	 * Boot the module
	 *
	 * Register hooks for redirect functionality.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Hook early into template_redirect to check redirects (Requirement 1.1, priority 1)
		add_action( 'template_redirect', array( $this, 'handle_redirect' ), 1 );

		// Hook to post_updated for automatic slug change redirects (Requirement 4.1)
		add_action( 'post_updated', array( $this, 'handle_post_updated' ), 10, 3 );

		// Hook to shutdown for asynchronous hit tracking (Requirement 3.3, priority 999)
		add_action( 'shutdown', array( $this, 'record_hit_async' ), 999 );

		// Register REST API endpoints
		add_action( 'rest_api_init', array( $this->rest, 'register_routes' ) );

		// Boot admin interface
		if ( is_admin() ) {
			$this->admin->boot();
		}
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
	 * Handle redirect matching and execution
	 *
	 * Implements database-level matching algorithm:
	 * 1. Exact-match query on indexed source_url (O(log n))
	 * 2. Regex fallback only if has_regex_rules flag is true
	 * 3. Never loads all redirect rules into PHP memory
	 *
	 * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4
	 *
	 * @return void
	 */
	public function handle_redirect(): void {
		// Get current request URL
		$request_url = $this->get_request_url();

		if ( empty( $request_url ) ) {
			return;
		}

		// Normalize URL (strip query string if configured)
		$normalized_url = $this->normalize_url( $request_url );

		// Check for redirect loop (Requirement 6.1)
		if ( in_array( $normalized_url, $this->redirect_chain, true ) ) {
			// Loop detected - log warning and stop (Requirement 6.2, 6.3, 6.4)
			Logger::warning(
				'Redirect loop detected',
				[
					'source_url' => $normalized_url,
					'chain'      => $this->redirect_chain,
				]
			);
			return;
		}

		// Add current URL to chain (Requirement 6.1)
		$this->redirect_chain[] = $normalized_url;

		// Step 1: Try exact match first (Requirement 1.1, 1.2)
		$redirect = DB::get_redirect_exact( $normalized_url );

		if ( $redirect ) {
			$this->execute_redirect( $redirect );
			return;
		}

		// Step 2: Check if regex rules exist (Requirement 1.3, 1.4)
		$has_regex_rules = $this->options->get( 'has_regex_rules', false );

		if ( ! $has_regex_rules ) {
			// No regex rules, skip regex matching entirely (Requirement 1.4)
			return;
		}

		// Step 3: Try to load regex rules from Object Cache (Requirement 1.5)
		$cache_key = 'meowseo_regex_rules';
		$regex_rules = wp_cache_get( $cache_key );

		if ( false === $regex_rules ) {
			// Cache miss - load from database (Requirement 1.5)
			$regex_rules = DB::get_redirect_regex_rules();
			// Cache for 5 minutes (300 seconds) (Requirement 1.5)
			wp_cache_set( $cache_key, $regex_rules, '', 300 );
		}

		// Step 4: Evaluate regex patterns (Requirement 5.1, 5.2, 5.3, 5.4)
		foreach ( $regex_rules as $rule ) {
			// Evaluate regex pattern
			$pattern = $rule['source_url'];

			// Ensure pattern has delimiters (Requirement 5.3)
			if ( ! $this->has_regex_delimiters( $pattern ) ) {
				$pattern = '#' . $pattern . '#i';
			}

			// Suppress warnings for invalid regex patterns (Requirement 5.4)
			$match = @preg_match( $pattern, $normalized_url, $matches );

			if ( $match ) {
				// Support backreferences in target URL (Requirement 5.1, 5.2)
				$target_url = $rule['target_url'];

				if ( ! empty( $matches ) ) {
					// Replace $1, $2, etc. with captured groups (Requirement 5.2)
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
	 * Supports redirect types: 301, 302, 307, 410, 451 (Requirement 2.1, 2.2, 2.3, 2.4, 2.5)
	 *
	 * @param array $redirect Redirect rule array.
	 * @return void
	 */
	private function execute_redirect( array $redirect ): void {
		$redirect_type = absint( $redirect['redirect_type'] ?? 301 );
		$target_url = $redirect['target_url'] ?? '';
		$redirect_id = absint( $redirect['id'] ?? 0 );
		$source_url = $redirect['source_url'] ?? '';

		// Normalize target URL for loop detection
		$normalized_target = $this->normalize_url( $target_url );

		// Check if target URL would create a loop (Requirement 6.2)
		if ( in_array( $normalized_target, $this->redirect_chain, true ) ) {
			// Loop detected - log warning with source, target, and chain (Requirement 6.3, 6.4)
			Logger::warning(
				'Redirect loop detected',
				[
					'source_url' => $source_url,
					'target_url' => $target_url,
					'chain'      => $this->redirect_chain,
				]
			);
			return;
		}

		// Store redirect ID for asynchronous hit tracking (Requirement 3.1, 3.2, 3.3)
		if ( $redirect_id > 0 ) {
			$this->pending_hit_redirect_id = $redirect_id;
		}

		// Handle 410 Gone status (Requirement 2.2, 2.3)
		if ( 410 === $redirect_type ) {
			status_header( 410 );
			nocache_headers();
			echo '<!DOCTYPE html><html><head><title>410 Gone</title></head><body><h1>410 Gone</h1><p>This resource is no longer available.</p></body></html>';
			exit;
		}

		// Handle 451 Unavailable For Legal Reasons status (Requirement 2.2, 2.3)
		if ( 451 === $redirect_type ) {
			status_header( 451 );
			nocache_headers();
			echo '<!DOCTYPE html><html><head><title>451 Unavailable For Legal Reasons</title></head><body><h1>451 Unavailable For Legal Reasons</h1><p>This content is unavailable for legal reasons.</p></body></html>';
			exit;
		}

		// Execute redirect for 301, 302, 307 (Requirement 2.1, 2.4, 2.5)
		if ( ! empty( $target_url ) ) {
			nocache_headers();
			wp_redirect( $target_url, $redirect_type );
			exit;
		}
	}

	/**
	 * Record hit asynchronously on shutdown hook
	 *
	 * Implements asynchronous hit tracking without blocking redirect execution.
	 * Requirements: 3.1, 3.2, 3.3, 3.4
	 *
	 * @return void
	 */
	public function record_hit_async(): void {
		if ( null === $this->pending_hit_redirect_id ) {
			return;
		}

		// Increment hit count and update last_hit timestamp (Requirement 3.1, 3.2)
		DB::increment_redirect_hit( $this->pending_hit_redirect_id );

		// Clear pending hit
		$this->pending_hit_redirect_id = null;
	}

	/**
	 * Handle post updated hook for automatic slug change redirects
	 *
	 * Creates 301 redirect when a published post's slug changes.
	 * Requirements: 4.1, 4.2, 4.3, 4.4
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object after update.
	 * @param WP_Post $old_post Post object before update.
	 * @return void
	 */
	public function handle_post_updated( int $post_id, WP_Post $post, WP_Post $old_post ): void {
		// Only process published posts (Requirement 4.4)
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Check if slug has changed (Requirement 4.1)
		if ( $post->post_name === $old_post->post_name ) {
			return;
		}

		// Get old and new permalinks
		$old_permalink = get_permalink( $old_post );
		$new_permalink = get_permalink( $post );

		if ( empty( $old_permalink ) || empty( $new_permalink ) || $old_permalink === $new_permalink ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_redirects';

		// Check if redirect already exists for old URL (Requirement 4.2)
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE source_url = %s LIMIT 1",
				$old_permalink
			)
		);

		if ( $existing ) {
			// Redirect already exists, don't create duplicate
			return;
		}

		// Check if old URL is target of another redirect to avoid chains (Requirement 4.3)
		$is_target = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE target_url = %s LIMIT 1",
				$old_permalink
			)
		);

		if ( $is_target ) {
			// Old URL is target of another redirect, would create chain
			return;
		}

		// Create 301 redirect from old permalink to new permalink (Requirement 4.1)
		$wpdb->insert(
			$table,
			[
				'source_url'    => $old_permalink,
				'target_url'    => $new_permalink,
				'redirect_type' => 301,
				'is_regex'      => 0,
				'is_active'     => 1,
			],
			[ '%s', '%s', '%d', '%d', '%d' ]
		);

		// Log the automatic redirect creation
		Logger::info(
			'Automatic redirect created for slug change',
			[
				'post_id'        => $post_id,
				'old_permalink'  => $old_permalink,
				'new_permalink'  => $new_permalink,
			]
		);
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
