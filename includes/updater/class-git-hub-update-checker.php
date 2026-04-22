<?php
/**
 * GitHub Update Checker Class
 *
 * Core class that integrates with WordPress plugin update system to check for
 * updates from GitHub repository using commit IDs for versioning.
 *
 * @package MeowSEO
 * @subpackage Updater
 * @since 1.0.0
 */

namespace MeowSEO\Updater;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GitHub_Update_Checker
 *
 * Integrates with WordPress plugin update system to provide automatic updates
 * from GitHub repository. Uses Git commit IDs for version tracking instead of
 * traditional semantic versioning.
 *
 * @since 1.0.0
 */
class GitHub_Update_Checker {

	/**
	 * Plugin file path
	 *
	 * Full path to the main plugin file (e.g., /path/to/wp-content/plugins/meowseo/meowseo.php)
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Plugin slug
	 *
	 * Extracted from plugin file path (e.g., "meowseo" from "meowseo/meowseo.php")
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $plugin_slug;

	/**
	 * Configuration instance
	 *
	 * Manages update configuration settings (repository, branch, frequency, etc.)
	 *
	 * @since 1.0.0
	 * @var Update_Config
	 */
	private Update_Config $config;

	/**
	 * Logger instance
	 *
	 * Handles logging of update events, API requests, and errors
	 *
	 * @since 1.0.0
	 * @var Update_Logger
	 */
	private Update_Logger $logger;

	/**
	 * Constructor
	 *
	 * Initializes the update checker with required dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $plugin_file Full path to the main plugin file.
	 * @param Update_Config $config      Configuration instance.
	 * @param Update_Logger $logger      Logger instance.
	 */
	public function __construct( string $plugin_file, Update_Config $config, Update_Logger $logger ) {
		$this->plugin_file = $plugin_file;
		$this->config      = $config;
		$this->logger      = $logger;

		// Extract plugin slug from plugin file path.
		// Example: "meowseo/meowseo.php" -> "meowseo"
		$this->plugin_slug = $this->extract_plugin_slug( $plugin_file );
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * Registers all WordPress hooks and filters needed for the update system.
	 * This method should be called after the object is constructed to activate
	 * the update checker.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Hook into WordPress plugin update check system.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );

		// Hook into WordPress plugin information API for changelog display.
		add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );

		// Hook into package download to modify the download URL.
		add_filter( 'upgrader_pre_download', array( $this, 'modify_package_url' ), 10, 3 );
		
		// Hook to rename the downloaded folder. GitHub appends commit hashes to the zipball root folder.
		add_filter( 'upgrader_source_selection', array( $this, 'rename_github_folder' ), 10, 4 );
		
		// Hook into upgrader completion to save the new commit ID.
		add_action( 'upgrader_process_complete', array( $this, 'update_installed_commit' ), 10, 2 );
	}

	/**
	 * Check for plugin updates
	 *
	 * Integrates with WordPress plugin update system by checking for new commits
	 * on GitHub and adding update information to the update_plugins transient.
	 *
	 * This method is called by WordPress when checking for plugin updates (typically
	 * every 12 hours or when user clicks "Check for updates").
	 *
	 * @since 1.0.0
	 *
	 * @param object $transient The update_plugins transient object.
	 * @return object Modified transient object with update information.
	 */
	public function check_for_update( $transient ) {
		// If transient is empty or not an object, return it unmodified.
		if ( empty( $transient ) || ! is_object( $transient ) ) {
			return $transient;
		}

		// Check if we should perform an update check based on frequency.
		if ( ! $this->should_check_for_update() ) {
			return $transient;
		}

		try {
			// Get current installed commit ID.
			$current_commit = $this->get_current_commit_id();

			// Get latest commit from GitHub.
			$latest_commit_data = $this->get_latest_commit();

			// If we couldn't get latest commit data, return unmodified transient.
			if ( null === $latest_commit_data ) {
				$this->logger->log_check( false, 'Failed to fetch latest commit from GitHub' );
				return $transient;
			}

			// Extract latest commit ID.
			$latest_commit = $latest_commit_data['short_sha'] ?? '';

			// Check if update is available.
			if ( ! $this->is_update_available( $current_commit, $latest_commit ) ) {
				// No update available - log success and return unmodified transient.
				$this->logger->log_check( true, 'No update available' );
				update_option( 'meowseo_github_last_check', time() );
				return $transient;
			}

			// Update is available - prepare update information.
			$plugin_basename = plugin_basename( $this->plugin_file );
			$owner           = $this->config->get_repo_owner();
			$repo            = $this->config->get_repo_name();
			$full_commit_sha = $latest_commit_data['sha'] ?? '';

			// Build the package URL (GitHub archive endpoint).
			$package_url = sprintf(
				'https://github.com/%s/%s/archive/%s.zip',
				$owner,
				$repo,
				$full_commit_sha
			);

			// Create update object with required fields.
			// Spoof the version by combining the base version with the short sha.
			// This forces WordPress to always show the 'Update Now' inline button correctly.
			$base_version = defined( 'MEOWSEO_VERSION' ) ? MEOWSEO_VERSION : '1.0.0';
			// Strip any existing commit hash from MEOWSEO_VERSION if present.
			if ( strpos( $base_version, '-' ) !== false ) {
				$base_version = explode( '-', $base_version )[0];
			}
			$display_version = $base_version . '.' . $latest_commit;
			
			$update_object = (object) array(
				'id'          => $plugin_basename,
				'slug'        => $this->plugin_slug,
				'plugin'      => $plugin_basename,
				'new_version' => $display_version,
				'url'         => sprintf( 'https://github.com/%s/%s', $owner, $repo ),
				'package'     => $package_url,
				'icons'       => array(
					'1x' => 'https://avatars.githubusercontent.com/u/1041913?s=200&v=4', // Using a generic placeholder icon or we can omit it.
				),
			);

			// Add update information to the transient.
			$transient->response[ $plugin_basename ] = $update_object;

			// Log successful update check.
			$this->logger->log_check(
				true,
				sprintf(
					'Update available: %s -> %s',
					$current_commit,
					$latest_commit
				)
			);

			// Update last check time.
			update_option( 'meowseo_github_last_check', time() );

		} catch ( \Exception $e ) {
			// Handle any unexpected errors gracefully.
			$this->logger->log_check( false, 'Exception during update check: ' . $e->getMessage() );
			// Return unmodified transient on error.
			return $transient;
		}

		// Return the modified transient.
		return $transient;
	}

	/**
	 * Get plugin information for update details
	 *
	 * Integrates with WordPress plugin information API to provide changelog
	 * and plugin details when user clicks "View details" on the update notification.
	 *
	 * @since 1.0.0
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string             $action The type of information being requested from the Plugin Installation API.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object Modified result object with plugin information, or false if not our plugin.
	 */
	public function get_plugin_info( $result, $action, $args ) {
		// Only handle 'plugin_information' action.
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		// Check if the request is for this plugin.
		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		// Get plugin data from the plugin file header.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( $this->plugin_file, false, false );

		// Get commit history for changelog.
		$commit_history = $this->get_commit_history();

		// Format changelog as HTML.
		$changelog_html = $this->format_changelog( $commit_history );

		// Get repository information.
		$owner = $this->config->get_repo_owner();
		$repo  = $this->config->get_repo_name();

		// Build plugin information object.
		$base_version = defined( 'MEOWSEO_VERSION' ) ? MEOWSEO_VERSION : '1.0.0';
		if ( strpos( $base_version, '-' ) !== false ) {
			$base_version = explode( '-', $base_version )[0];
		}
		$display_version = $base_version;
		$latest_commit = $this->get_latest_commit();
		if ( $latest_commit && ! empty( $latest_commit['short_sha'] ) ) {
			$display_version .= '.' . $latest_commit['short_sha'];
		} else {
			$display_version = $plugin_data['Version'] ?? '1.0.0';
		}

		$plugin_info = (object) array(
			'name'          => $plugin_data['Name'] ?? 'MeowSEO',
			'slug'          => $this->plugin_slug,
			'version'       => $display_version,
			'author'        => $plugin_data['Author'] ?? '',
			'homepage'      => sprintf( 'https://github.com/%s/%s', $owner, $repo ),
			'requires'      => '6.0',
			'tested'        => '6.4',
			'requires_php'  => '8.0',
			'last_updated'  => current_time( 'mysql' ),
			'sections'      => array(
				'changelog' => $changelog_html,
			),
			'download_link' => '', // Will be set by WordPress from the update transient.
		);

		return $plugin_info;
	}

	/**
	 * Modify package download URL
	 *
	 * Integrates with WordPress plugin updater to modify the download URL
	 * before the update package is downloaded. This ensures the correct
	 * GitHub archive URL is used for downloading the plugin update.
	 *
	 * This method is called by WordPress immediately before downloading
	 * the update package when user clicks "Update Now".
	 *
	 * @since 1.0.0
	 *
	 * @param bool|WP_Error  $reply   Whether to bail without returning the package. Default false.
	 * @param string         $package The package file name or URL.
	 * @param object         $updater The WP_Upgrader instance.
	 * @return bool|WP_Error|string Modified package URL, or original $reply if not applicable.
	 */
	public function modify_package_url( $reply, $package, $updater ) {
		// Verify $package is a string and not empty.
		if ( ! is_string( $package ) || empty( $package ) ) {
			return $reply;
		}

		// Get repository information.
		$owner = $this->config->get_repo_owner();
		$repo  = $this->config->get_repo_name();

		// Check if this package URL is for this plugin.
		// The package URL should contain the repository owner and name.
		$is_our_plugin = false;

		// Check if URL contains our repository owner/name.
		if ( false !== strpos( $package, $owner ) && false !== strpos( $package, $repo ) ) {
			$is_our_plugin = true;
		}

		// Check if URL matches our plugin slug pattern.
		if ( false !== strpos( $package, $this->plugin_slug ) ) {
			$is_our_plugin = true;
		}

		// If not for this plugin, return unchanged.
		if ( ! $is_our_plugin ) {
			return $reply;
		}

		// Extract commit SHA from the package URL or use the latest commit.
		$commit_sha = null;

		// Try to extract commit SHA from the URL.
		// GitHub archive URLs have format: /archive/{commit_sha}.zip
		if ( preg_match( '/\/archive\/([a-f0-9]{7,40})\.zip$/i', $package, $matches ) ) {
			$commit_sha = $matches[1];
		}

		// If we couldn't extract commit SHA from URL, get the latest commit.
		if ( null === $commit_sha ) {
			$latest_commit_data = $this->get_latest_commit();
			if ( null !== $latest_commit_data && ! empty( $latest_commit_data['sha'] ) ) {
				$commit_sha = $latest_commit_data['sha'];
			}
		}

		// If we still don't have a commit SHA, return the original package URL.
		if ( null === $commit_sha ) {
			$this->logger->log_check( false, 'Could not determine commit SHA for package download' );
			return $package;
		}

		// Build the correct GitHub archive URL.
		$archive_url = sprintf(
			'https://github.com/%s/%s/archive/%s.zip',
			$owner,
			$repo,
			$commit_sha
		);

		// Log the download attempt.
		$this->logger->log_check(
			true,
			sprintf(
				'Package download initiated for commit %s from URL: %s',
				substr( $commit_sha, 0, 7 ),
				$archive_url
			)
		);

		// Return the modified package URL.
		// WordPress will handle the nested directory structure automatically.
		return $archive_url;
	}

	/**
	 * Rename the extracted folder.
	 * GitHub appends arbitrary commit hashes to the root folder of the downloaded zip.
	 */
	public function rename_github_folder( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;

		$plugin_basename = plugin_basename( $this->plugin_file );

		// Make sure it's updating this specific plugin.
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $plugin_basename ) {
			return $source;
		}

		$new_source = trailingslashit( $remote_source ) . $this->plugin_slug;
		$wp_filesystem->move( $source, $new_source );

		return trailingslashit( $new_source );
	}

	/**
	 * Get cached data
	 *
	 * Retrieves cached data from WordPress transients. This is a centralized
	 * method for accessing all update-related cached data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Cache key to retrieve.
	 * @return mixed Cached data if found, false otherwise.
	 */
	private function get_cache( string $key ) {
		return get_transient( $key );
	}

	/**
	 * Set cached data
	 *
	 * Stores data in WordPress transients with a specified expiration time.
	 * This is a centralized method for caching all update-related data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key        Cache key to store data under.
	 * @param mixed  $data       Data to cache.
	 * @param int    $expiration Expiration time in seconds (default: 43200 = 12 hours).
	 * @return void
	 */
	private function set_cache( string $key, $data, int $expiration = 43200 ): void {
		set_transient( $key, $data, $expiration );
	}

	/**
	 * Get commit history from GitHub
	 *
	 * Fetches recent commits from the configured GitHub repository and branch.
	 * Results are cached for 12 hours to minimize API calls.
	 *
	 * @since 1.0.0
	 *
	 * @param int $limit Maximum number of commits to fetch (default: 20).
	 * @return array Array of commit data. Each commit contains:
	 *               - sha: Full commit SHA (40 chars)
	 *               - short_sha: Short commit SHA (first 7 chars)
	 *               - message: Commit message
	 *               - author: Commit author name
	 *               - date: Commit date (ISO 8601 format)
	 *               - url: URL to view commit on GitHub
	 */
	private function get_commit_history( int $limit = 20 ): array {
		// Check cache first.
		$cache_key   = 'meowseo_github_changelog';
		$cached_data = $this->get_cache( $cache_key );

		if ( false !== $cached_data && is_array( $cached_data ) ) {
			return $cached_data;
		}

		// Build API endpoint.
		$owner    = $this->config->get_repo_owner();
		$repo     = $this->config->get_repo_name();
		$branch   = $this->config->get_branch();
		$endpoint = "/repos/{$owner}/{$repo}/commits?sha={$branch}&per_page={$limit}";

		// Make API request.
		$response = $this->github_api_request( $endpoint );

		// Return empty array on error.
		if ( null === $response || ! is_array( $response ) ) {
			$this->logger->log_check( false, 'Failed to fetch commit history from GitHub' );
			return array();
		}

		// Parse commit data.
		$commits = array();
		foreach ( $response as $commit_data ) {
			if ( ! isset( $commit_data['sha'] ) || ! isset( $commit_data['commit'] ) ) {
				continue;
			}

			$commits[] = array(
				'sha'       => $commit_data['sha'],
				'short_sha' => substr( $commit_data['sha'], 0, 7 ),
				'message'   => $commit_data['commit']['message'] ?? '',
				'author'    => $commit_data['commit']['author']['name'] ?? '',
				'date'      => $commit_data['commit']['author']['date'] ?? '',
				'url'       => $commit_data['html_url'] ?? '',
			);
		}

		// Cache the result for 12 hours (43200 seconds).
		$this->set_cache( $cache_key, $commits );

		return $commits;
	}

	/**
	 * Format changelog as HTML
	 *
	 * Converts an array of commit data into formatted HTML for display
	 * in the plugin information modal.
	 *
	 * @since 1.0.0
	 *
	 * @param array $commits Array of commit data from get_commit_history().
	 * @return string HTML formatted changelog.
	 */
	private function format_changelog( array $commits ): string {
		// If no commits, return a message.
		if ( empty( $commits ) ) {
			return '<p>No changelog available.</p>';
		}

		// Build HTML list of commits.
		$html = '<div class="meowseo-changelog">';
		$html .= '<h3>Recent Changes</h3>';
		$html .= '<ul>';

		foreach ( $commits as $commit ) {
			$commit_date = ! empty( $commit['date'] ) ? date( 'Y-m-d H:i', strtotime( $commit['date'] ) ) : 'Unknown date';
			$commit_url  = esc_url( $commit['url'] );
			$short_sha   = esc_html( $commit['short_sha'] );
			$message     = esc_html( $commit['message'] );
			$author      = esc_html( $commit['author'] );

			$html .= '<li>';
			$html .= sprintf(
				'<strong><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></strong> - %s',
				$commit_url,
				$short_sha,
				$commit_date
			);
			$html .= '<br>';
			$html .= sprintf( '<em>%s</em>', $message );
			$html .= '<br>';
			$html .= sprintf( '<small>by %s</small>', $author );
			$html .= '</li>';
		}

		$html .= '</ul>';

		// Add link to view full commit history on GitHub.
		$owner = $this->config->get_repo_owner();
		$repo  = $this->config->get_repo_name();
		$html .= sprintf(
			'<p><a href="https://github.com/%s/%s/commits" target="_blank" rel="noopener noreferrer">View full commit history on GitHub &rarr;</a></p>',
			esc_attr( $owner ),
			esc_attr( $repo )
		);

		$html .= '</div>';

		return $html;
	}

	/**
	 * Get latest commit from GitHub
	 *
	 * Fetches the latest commit information from the configured GitHub repository
	 * and branch. Results are cached for 12 hours to minimize API calls.
	 *
	 * @since 1.0.0
	 *
	 * @return array|null Array with commit info on success, null on error.
	 *                    Array structure:
	 *                    - sha: Full commit SHA (40 chars)
	 *                    - short_sha: Short commit SHA (first 7 chars)
	 *                    - message: Commit message
	 *                    - author: Commit author name
	 *                    - date: Commit date (ISO 8601 format)
	 *                    - url: URL to view commit on GitHub
	 */
	public function get_latest_commit(): ?array {
		// Check cache first.
		$cache_key   = 'meowseo_github_update_info';
		$cached_data = $this->get_cache( $cache_key );

		if ( false !== $cached_data && is_array( $cached_data ) ) {
			return $cached_data;
		}

		// Build API endpoint.
		$owner    = $this->config->get_repo_owner();
		$repo     = $this->config->get_repo_name();
		$branch   = $this->config->get_branch();
		$endpoint = "/repos/{$owner}/{$repo}/commits/{$branch}";

		// Make API request.
		$response = $this->github_api_request( $endpoint );

		// Return null on error.
		if ( null === $response ) {
			return null;
		}

		// Extract commit information.
		$commit_data = array(
			'sha'       => $response['sha'] ?? '',
			'short_sha' => substr( $response['sha'] ?? '', 0, 7 ),
			'message'   => $response['commit']['message'] ?? '',
			'author'    => $response['commit']['author']['name'] ?? '',
			'date'      => $response['commit']['author']['date'] ?? '',
			'url'       => $response['html_url'] ?? '',
		);

		// Validate that we have the essential data.
		if ( empty( $commit_data['sha'] ) ) {
			$this->logger->log_check( false, 'Invalid commit data received from GitHub' );
			return null;
		}

		// Cache the result for 12 hours (43200 seconds).
		$this->set_cache( $cache_key, $commit_data );

		return $commit_data;
	}

	/**
	 * Check if update check is needed
	 *
	 * Determines whether an update check should be performed based on:
	 * - Configured check frequency
	 * - Time since last check
	 * - Cache status
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if update check should be performed, false otherwise.
	 */
	private function should_check_for_update(): bool {
		// Check if cache exists.
		$cache_key   = 'meowseo_github_update_info';
		$cached_data = $this->get_cache( $cache_key );

		// If cache is empty, force check.
		if ( false === $cached_data ) {
			return true;
		}

		// Get last check time from option.
		$last_check = get_option( 'meowseo_github_last_check', 0 );

		// Get configured check frequency (in seconds).
		$frequency = $this->config->get_check_frequency();

		// Calculate time since last check.
		$time_since_last_check = time() - $last_check;

		// Return true if enough time has passed.
		return $time_since_last_check >= $frequency;
	}

	/**
	 * Make GitHub API request
	 *
	 * Makes an HTTP request to the GitHub API using WordPress's wp_remote_get().
	 * Handles errors, rate limiting, and response parsing.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint GitHub API endpoint (e.g., '/repos/owner/repo/commits/main').
	 * @param array  $args     Optional. Additional arguments for the request.
	 * @return array|null Parsed response array on success, null on error.
	 */
	private function github_api_request( string $endpoint, array $args = [] ): ?array {
		// Check if currently rate limited.
		$rate_limit_status = $this->check_rate_limit();
		if ( $rate_limit_status['is_limited'] ) {
			$this->logger->log_check(
				false,
				sprintf(
					'Skipping API request due to rate limit. Retry after: %d seconds',
					$rate_limit_status['retry_after']
				)
			);
			return null;
		}

		// Build the full API URL.
		$url = 'https://api.github.com' . $endpoint;

		// Set up request arguments.
		$request_args = wp_parse_args(
			$args,
			array(
				'timeout'    => 10, // 10 second timeout.
				'user-agent' => 'MeowSEO-Updater/1.0 (WordPress Plugin)',
				'headers'    => array(),
				'sslverify'  => true, // Always verify SSL for security.
			)
		);

		// Make the API request.
		$response = wp_remote_get( $url, $request_args );

		// Check for WP_Error (network error, timeout, etc.).
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->handle_api_error( 0, $error_message );
			return null;
		}

		// Get response code.
		$response_code = wp_remote_retrieve_response_code( $response );

		// Extract rate limit information from headers.
		$headers    = wp_remote_retrieve_headers( $response );
		$rate_limit = array(
			'limit'     => isset( $headers['x-ratelimit-limit'] ) ? (int) $headers['x-ratelimit-limit'] : 60,
			'remaining' => isset( $headers['x-ratelimit-remaining'] ) ? (int) $headers['x-ratelimit-remaining'] : 60,
			'reset'     => isset( $headers['x-ratelimit-reset'] ) ? (int) $headers['x-ratelimit-reset'] : time() + 3600,
		);

		// Cache rate limit information for 1 hour.
		$this->set_cache( 'meowseo_github_rate_limit', $rate_limit, HOUR_IN_SECONDS );

		// Log the API request.
		$this->logger->log_api_request( $endpoint, $response_code, $rate_limit );

		// Handle error response codes.
		if ( $response_code !== 200 ) {
			$body = wp_remote_retrieve_body( $response );
			$error_data = json_decode( $body, true );
			$error_message = $error_data['message'] ?? null;
			$this->handle_api_error( $response_code, $error_message, $rate_limit );
			return null;
		}

		// Get response body.
		$body = wp_remote_retrieve_body( $response );

		// Parse JSON response.
		$data = json_decode( $body, true );

		// Check if JSON parsing was successful.
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->logger->log_check( false, 'Failed to parse API response: ' . json_last_error_msg() );
			return null;
		}

		// Return parsed data.
		return $data;
	}

	/**
	 * Get current installed commit ID
	 *
	 * Extracts the commit ID from the current plugin version string.
	 * The version format is expected to be: {semantic_version}-{commit_id}
	 * Example: "1.0.0-abc1234"
	 *
	 * @since 1.0.0
	 *
	 * @return string Commit ID if found, empty string otherwise.
	 */
	private function get_current_commit_id(): string {
		// First try to get it from the database option.
		$installed_commit = get_option( 'meowseo_installed_commit', '' );
		if ( ! empty( $installed_commit ) ) {
			return $installed_commit;
		}

		// Fallback: Get plugin data from the plugin file header.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( $this->plugin_file, false, false );
		
		// Extract version string from plugin header.
		$version = $plugin_data['Version'] ?? '';
		
		// Parse the commit ID from the version string.
		$commit_id = $this->extract_commit_id( $version );
		
		if ( ! empty( $commit_id ) ) {
			update_option( 'meowseo_installed_commit', $commit_id );
			return $commit_id;
		}
		
		return '';
	}

	/**
	 * Logic fired after processing a plugin update.
	 * Saves the new SHA to the database to stop the update loop and properly reflect the current version.
	 *
	 * @param \WP_Upgrader $upgrader_object WP_Upgrader instance.
	 * @param array        $options         Array of bulk item update data.
	 */
	public function update_installed_commit( $upgrader_object, $options ) {
		$plugin_basename = plugin_basename( $this->plugin_file );
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && ! empty( $options['plugins'] ) ) {
			if ( in_array( $plugin_basename, $options['plugins'], true ) ) {
				// Clear the cache to force a fresh fetch on next check.
				$this->clear_cache();

				// Fetch the absolute newest commit (which we just installed via the main branch zip).
				// We force bypassing the cache.
				$cache_key   = 'meowseo_github_update_info';
				delete_transient( $cache_key );
				
				$latest = $this->get_latest_commit();

				if ( $latest && ! empty( $latest['short_sha'] ) ) {
					// Save to database so we know what is currently installed.
					update_option( 'meowseo_installed_commit', sanitize_text_field( $latest['short_sha'] ) );
				}
			}
		}
	}

	/**
	 * Extract commit ID from version string
	 *
	 * Parses a version string in the format {semantic_version}-{commit_id}
	 * and extracts the commit ID portion. The commit ID must be 7-40
	 * hexadecimal characters.
	 *
	 * Examples:
	 * - "1.0.0-abc1234" -> "abc1234"
	 * - "2.1.5-def567890abcdef" -> "def567890abcdef"
	 * - "1.0.0" -> null (no commit ID)
	 *
	 * @since 1.0.0
	 *
	 * @param string $version Version string to parse.
	 * @return string|null Commit ID if found, null otherwise.
	 */
	private function extract_commit_id( string $version ): ?string {
		// Regex pattern to match version format: {digits.digits.digits}-{hex_string}
		// The commit ID must be exactly 7-40 hexadecimal characters (a-f, 0-9).
		// Using word boundary \b at the end ensures we don't match more than 40 chars.
		$pattern = '/^[\d.]+-([a-f0-9]{7,40})$/';

		// Attempt to match the pattern.
		if ( preg_match( $pattern, $version, $matches ) ) {
			// Return the captured commit ID (first capture group).
			return $matches[1];
		}

		// No commit ID found in version string.
		return null;
	}

	/**
	 * Check if update is available
	 *
	 * Compares two commit IDs to determine if an update is available.
	 * An update is considered available if:
	 * - Both commit IDs are non-empty (after trimming whitespace)
	 * - The commit IDs are different
	 *
	 * @since 1.0.0
	 *
	 * @param string $current Current installed commit ID.
	 * @param string $latest  Latest available commit ID from GitHub.
	 * @return bool True if update is available, false otherwise.
	 */
	private function is_update_available( string $current, string $latest ): bool {
		// Trim whitespace from both commit IDs.
		$current = trim( $current );
		$latest  = trim( $latest );

		// If either commit ID is empty, no update is available.
		if ( empty( $current ) || empty( $latest ) ) {
			return false;
		}

		// Update is available if commit IDs are different.
		return $current !== $latest;
	}

	/**
	 * Extract plugin slug from plugin file path
	 *
	 * Converts a plugin file path like "meowseo/meowseo.php" or "meowseo.php"
	 * into just the plugin slug "meowseo".
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Full path to the plugin file.
	 * @return string Plugin slug.
	 */
	private function extract_plugin_slug( string $plugin_file ): string {
		// Get the plugin basename (e.g., "meowseo/meowseo.php").
		$plugin_basename = plugin_basename( $plugin_file );

		// Extract the directory name (first part before the slash).
		// If there's no slash, use the filename without extension.
		if ( false !== strpos( $plugin_basename, '/' ) ) {
			// Multi-file plugin: "meowseo/meowseo.php" -> "meowseo".
			$slug = dirname( $plugin_basename );
		} else {
			// Single-file plugin: "meowseo.php" -> "meowseo".
			$slug = basename( $plugin_basename, '.php' );
		}

		return $slug;
	}

	/**
	 * Clear all update caches
	 *
	 * Deletes all transients related to GitHub updates, forcing fresh data
	 * to be fetched on the next update check. This is useful when:
	 * - User manually triggers an update check
	 * - Settings are changed
	 * - Troubleshooting update issues
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		// Delete update info cache.
		delete_transient( 'meowseo_github_update_info' );

		// Delete changelog cache.
		delete_transient( 'meowseo_github_changelog' );

		// Delete rate limit cache.
		delete_transient( 'meowseo_github_rate_limit' );

		// Reset last check time to force immediate check.
		delete_option( 'meowseo_github_last_check' );

		// Log cache clear action.
		$this->logger->log_check( true, 'Update caches cleared' );
	}

	/**
	 * Handle API errors
	 *
	 * Processes API errors and maps HTTP status codes to user-friendly messages.
	 * Handles rate limit errors with retry-after time, network timeouts, authentication
	 * errors, and repository not found errors. Logs all errors with context and
	 * displays admin notices for critical errors.
	 *
	 * @since 1.0.0
	 *
	 * @param int         $response_code HTTP response code from GitHub API.
	 * @param string|null $error_message Optional error message from the API or WP_Error.
	 * @param array       $rate_limit    Optional rate limit information from response headers.
	 * @return array Array with keys:
	 *               - 'user_message': User-friendly error message
	 *               - 'log_message': Detailed message for logging
	 *               - 'is_rate_limited': Boolean indicating if rate limited
	 *               - 'retry_after': Seconds to wait before retry (for rate limit errors)
	 */
	public function handle_api_error( int $response_code, ?string $error_message = null, array $rate_limit = [] ): array {
		$user_message = '';
		$log_message  = '';
		$is_rate_limited = false;
		$retry_after  = 0;

		// Map HTTP status codes to user-friendly messages.
		switch ( $response_code ) {
			case 0:
				// Network error or timeout.
				$user_message = __( 'Unable to connect to GitHub. Please check your internet connection and try again later.', 'meowseo' );
				$log_message  = 'Network error or timeout: ' . ( $error_message ?? 'Unknown error' );
				break;

			case 403:
				// Rate limit exceeded or access forbidden.
				if ( ! empty( $rate_limit['reset'] ) ) {
					$is_rate_limited = true;
					$retry_after     = max( 0, $rate_limit['reset'] - time() );
					$reset_time      = date( 'H:i:s', $rate_limit['reset'] );
					$user_message    = sprintf(
						__( 'GitHub rate limit exceeded. Updates will resume at %s. Remaining requests: %d/%d', 'meowseo' ),
						$reset_time,
						$rate_limit['remaining'] ?? 0,
						$rate_limit['limit'] ?? 60
					);
				} else {
					$user_message = __( 'Access to GitHub API is forbidden. Please check your repository settings.', 'meowseo' );
				}
				$log_message = sprintf(
					'Rate limit exceeded or access forbidden. Remaining: %d/%d, Reset: %s',
					$rate_limit['remaining'] ?? 0,
					$rate_limit['limit'] ?? 60,
					$rate_limit['reset'] ? date( 'Y-m-d H:i:s', $rate_limit['reset'] ) : 'Unknown'
				);
				break;

			case 404:
				// Repository not found.
				$user_message = __( 'GitHub repository not found. Please check your repository settings.', 'meowseo' );
				$log_message  = 'Repository not found: ' . ( $error_message ?? 'Unknown repository' );
				break;

			case 401:
				// Authentication error.
				$user_message = __( 'Authentication failed. Please check your GitHub credentials.', 'meowseo' );
				$log_message  = 'Authentication error: ' . ( $error_message ?? 'Invalid credentials' );
				break;

			case 500:
			case 502:
			case 503:
			case 504:
				// GitHub server error.
				$user_message = __( 'GitHub is experiencing issues. Please try again later.', 'meowseo' );
				$log_message  = sprintf( 'GitHub server error (HTTP %d): %s', $response_code, $error_message ?? 'Unknown error' );
				break;

			default:
				// Generic error.
				$user_message = sprintf(
					__( 'Update check failed (HTTP %d). Please try again later.', 'meowseo' ),
					$response_code
				);
				$log_message  = sprintf( 'API request failed with HTTP %d: %s', $response_code, $error_message ?? 'Unknown error' );
		}

		// Log the error with context.
		$this->logger->log_check(
			false,
			$log_message,
			array(
				'response_code'   => $response_code,
				'error_message'   => $error_message,
				'rate_limit'      => $rate_limit,
				'is_rate_limited' => $is_rate_limited,
				'retry_after'     => $retry_after,
			)
		);

		// Display admin notice for critical errors (only if user can manage options).
		if ( current_user_can( 'manage_options' ) && ! $is_rate_limited ) {
			// Store error notice in transient for display on next page load.
			set_transient( 'meowseo_update_error_notice', $user_message, HOUR_IN_SECONDS );
		}

		return array(
			'user_message'    => $user_message,
			'log_message'     => $log_message,
			'is_rate_limited' => $is_rate_limited,
			'retry_after'     => $retry_after,
		);
	}

	/**
	 * Check if rate limited
	 *
	 * Determines if the API is currently rate limited based on cached rate limit
	 * information. If rate limited, returns the time until the limit resets.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array with keys:
	 *               - 'is_limited': Boolean indicating if rate limited
	 *               - 'retry_after': Seconds to wait before retry
	 *               - 'reset_time': Unix timestamp when limit resets
	 */
	public function check_rate_limit(): array {
		// Get cached rate limit information.
		$cache_key   = 'meowseo_github_rate_limit';
		$rate_limit  = $this->get_cache( $cache_key );

		// If no cached rate limit, assume not limited.
		if ( false === $rate_limit || ! is_array( $rate_limit ) ) {
			return array(
				'is_limited'  => false,
				'retry_after' => 0,
				'reset_time'  => 0,
			);
		}

		// Check if currently rate limited.
		$current_time = time();
		$reset_time   = $rate_limit['reset'] ?? 0;
		$remaining    = $rate_limit['remaining'] ?? 0;

		// Rate limited if remaining is 0 and reset time is in the future.
		$is_limited = ( 0 === $remaining && $reset_time > $current_time );

		return array(
			'is_limited'  => $is_limited,
			'retry_after' => max( 0, $reset_time - $current_time ),
			'reset_time'  => $reset_time,
		);
	}

	/**
	 * Is rate limited
	 *
	 * Convenience method to check if the API is currently rate limited.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if rate limited, false otherwise.
	 */
	public function is_rate_limited(): bool {
		$rate_limit = $this->check_rate_limit();
		return $rate_limit['is_limited'];
	}

	/**
	 * Validate ZIP file
	 *
	 * Verifies that a downloaded ZIP file is valid and contains expected plugin files.
	 * Checks:
	 * - File exists and is readable
	 * - File is a valid ZIP archive
	 * - ZIP contains expected plugin files (meowseo.php)
	 * - ZIP structure matches expected format (nested directory)
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_path Path to the ZIP file to validate.
	 * @return bool|\WP_Error True if valid, WP_Error on failure.
	 */
	public function validate_zip_file( string $file_path ) {
		// Check if file exists and is readable.
		if ( ! file_exists( $file_path ) ) {
			$this->logger->log_check( false, 'ZIP file does not exist: ' . $file_path );
			return new \WP_Error(
				'zip_not_found',
				__( 'Downloaded update file not found.', 'meowseo' )
			);
		}

		if ( ! is_readable( $file_path ) ) {
			$this->logger->log_check( false, 'ZIP file is not readable: ' . $file_path );
			return new \WP_Error(
				'zip_not_readable',
				__( 'Downloaded update file is not readable.', 'meowseo' )
			);
		}

		// Check if file is a valid ZIP archive.
		$zip = new \ZipArchive();
		$result = $zip->open( $file_path );

		if ( true !== $result ) {
			$this->logger->log_check( false, 'ZIP file is invalid: ' . $result );
			return new \WP_Error(
				'invalid_zip',
				__( 'Downloaded update file is not a valid ZIP archive.', 'meowseo' )
			);
		}

		// Check if ZIP contains expected plugin files (meowseo.php).
		$has_plugin_file = false;
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$filename = $zip->getNameIndex( $i );
			if ( false !== strpos( $filename, 'meowseo.php' ) ) {
				$has_plugin_file = true;
				break;
			}
		}

		if ( ! $has_plugin_file ) {
			$zip->close();
			$this->logger->log_check( false, 'ZIP file does not contain meowseo.php' );
			return new \WP_Error(
				'missing_plugin_file',
				__( 'Downloaded update file does not contain the plugin file.', 'meowseo' )
			);
		}

		// Validate ZIP structure (nested directory).
		$root_dir = null;
		$valid_structure = true;

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$filename = $zip->getNameIndex( $i );

			// Skip empty entries.
			if ( empty( $filename ) ) {
				continue;
			}

			// Extract the root directory name.
			$parts = explode( '/', $filename );
			$current_root = $parts[0];

			if ( null === $root_dir ) {
				$root_dir = $current_root;
			} elseif ( $root_dir !== $current_root ) {
				// Files are not all in the same root directory.
				$valid_structure = false;
				break;
			}
		}

		$zip->close();

		if ( ! $valid_structure || null === $root_dir ) {
			$this->logger->log_check( false, 'ZIP file structure is invalid' );
			return new \WP_Error(
				'invalid_zip_structure',
				__( 'Downloaded update file has an invalid structure.', 'meowseo' )
			);
		}

		// Log successful validation.
		$this->logger->log_check(
			true,
			null,
			array(
				'action'    => 'zip_validation_successful',
				'file_path' => $file_path,
				'root_dir'  => $root_dir,
			)
		);

		return true;
	}

	/**
	 * Detect current commit for installations without commit ID
	 *
	 * For backward compatibility with installations that don't have a commit ID
	 * in the version string, this method attempts to detect the current commit
	 * by querying GitHub API and matching based on file hashes or dates.
	 *
	 * Strategy:
	 * 1. Get the plugin file modification time
	 * 2. Query GitHub API for commits around that date
	 * 3. Match commits based on file hashes if possible
	 * 4. Return the best matching commit ID
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Detected commit ID if found, null otherwise.
	 */
	public function detect_current_commit(): ?string {
		// Get the plugin file modification time.
		$plugin_mtime = filemtime( $this->plugin_file );
		if ( false === $plugin_mtime ) {
			$this->logger->log_check( false, 'Could not get plugin file modification time' );
			return null;
		}

		// Convert to ISO 8601 format for GitHub API.
		$since_date = gmdate( 'Y-m-d\TH:i:s\Z', $plugin_mtime - 86400 ); // 1 day before mtime.
		$until_date = gmdate( 'Y-m-d\TH:i:s\Z', $plugin_mtime + 86400 ); // 1 day after mtime.

		// Build API endpoint to get commits in the date range.
		$owner    = $this->config->get_repo_owner();
		$repo     = $this->config->get_repo_name();
		$branch   = $this->config->get_branch();
		$endpoint = "/repos/{$owner}/{$repo}/commits?sha={$branch}&since={$since_date}&until={$until_date}&per_page=100";

		// Make API request.
		$response = $this->github_api_request( $endpoint );

		// Return null on error.
		if ( null === $response || ! is_array( $response ) ) {
			$this->logger->log_check( false, 'Failed to fetch commits for detection' );
			return null;
		}

		// If no commits found, return null.
		if ( empty( $response ) ) {
			$this->logger->log_check( false, 'No commits found in date range for detection' );
			return null;
		}

		// Try to match commits based on file hashes.
		// For now, we'll use a simple heuristic: return the commit closest to the file mtime.
		$closest_commit = null;
		$closest_diff   = PHP_INT_MAX;

		foreach ( $response as $commit_data ) {
			if ( ! isset( $commit_data['sha'] ) || ! isset( $commit_data['commit']['author']['date'] ) ) {
				continue;
			}

			// Parse commit date.
			$commit_time = strtotime( $commit_data['commit']['author']['date'] );
			if ( false === $commit_time ) {
				continue;
			}

			// Calculate time difference.
			$diff = abs( $commit_time - $plugin_mtime );

			// Keep track of the closest commit.
			if ( $diff < $closest_diff ) {
				$closest_diff   = $diff;
				$closest_commit = $commit_data['sha'];
			}
		}

		// If we found a close commit, return it.
		if ( null !== $closest_commit ) {
			$this->logger->log_check(
				true,
				sprintf(
					'Detected current commit: %s (time diff: %d seconds)',
					substr( $closest_commit, 0, 7 ),
					$closest_diff
				)
			);
			return $closest_commit;
		}

		// No suitable commit found.
		$this->logger->log_check( false, 'Could not detect current commit from GitHub' );
		return null;
	}

	/**
	 * Initialize commit ID for first-time installations
	 *
	 * For installations without a commit ID in the version string, this method
	 * attempts to detect and initialize the commit ID. This is called during
	 * plugin activation or on first update check.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if commit ID was initialized, false otherwise.
	 */
	public function initialize_commit_id(): bool {
		// Get current commit ID from version string.
		$current_commit = $this->get_current_commit_id();

		// If commit ID already exists, no need to initialize.
		if ( ! empty( $current_commit ) ) {
			return true;
		}

		// Try to detect the current commit.
		$detected_commit = $this->detect_current_commit();

		// If detection failed, log and return false.
		if ( null === $detected_commit ) {
			$this->logger->log_check( false, 'Failed to initialize commit ID' );
			return false;
		}

		// Store the detected commit ID in an option for reference.
		update_option( 'meowseo_detected_commit_id', $detected_commit );

		// Log successful initialization.
		$this->logger->log_check(
			true,
			sprintf(
				'Initialized commit ID: %s',
				substr( $detected_commit, 0, 7 )
			)
		);

		return true;
	}
}
