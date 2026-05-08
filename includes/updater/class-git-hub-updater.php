<?php
/**
 * GitHub Updater Class.
 * Handles automatic updates for MeowSEO directly from a private GitHub repository.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Updater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GitHub_Updater {

	private $username   = 'pustekno';
	private $repository = 'meowseo';
	private $slug       = 'meowseo';
	private $basename   = 'meowseo/meowseo.php';

	/**
	 * GitHub Personal Access Token for private repository access.
	 * Stored encrypted in the database; retrieved at runtime.
	 * @var string
	 */
	private $access_token = '';

	/** @var object|null */
	private $github_response = null;

	public function __construct() {
		$this->access_token = $this->get_stored_token();

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'rename_github_folder' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'update_installed_sha' ), 10, 2 );

		// Inject Authorization header for every request to this private repo.
		add_filter( 'http_request_args', array( $this, 'inject_auth_header' ), 10, 2 );
	}

	/**
	 * Retrieve the stored GitHub token from the database.
	 */
	private function get_stored_token(): string {
		$raw = get_option( 'meowseo_github_token', '' );
		if ( empty( $raw ) ) {
			return '';
		}
		// Decrypt if it looks like it was stored encrypted (base64-encoded payload).
		$decoded = base64_decode( $raw, true );
		if ( false !== $decoded && substr_count( $decoded, ':' ) >= 2 ) {
			return $this->decrypt( $raw );
		}
		return sanitize_text_field( $raw );
	}

	/**
	 * Encrypt a token for storage.
	 */
	public static function encrypt_token( string $token ): string {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return $token;
		}
		$key    = wp_salt( 'auth' );
		$iv     = openssl_random_pseudo_bytes( 16 );
		$cipher = openssl_encrypt( $token, 'AES-256-CBC', $key, 0, $iv );
		return base64_encode( $iv . ':' . $cipher );
	}

	/**
	 * Decrypt a stored token.
	 */
	private function decrypt( string $stored ): string {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return sanitize_text_field( base64_decode( $stored ) );
		}
		$key  = wp_salt( 'auth' );
		$data = base64_decode( $stored );
		$pos  = strpos( $data, ':' );
		if ( false === $pos ) {
			return '';
		}
		$iv     = substr( $data, 0, $pos );
		$cipher = substr( $data, $pos + 1 );
		$plain  = openssl_decrypt( $cipher, 'AES-256-CBC', $key, 0, $iv );
		return false !== $plain ? sanitize_text_field( $plain ) : '';
	}

	/**
	 * Build Authorization header array if a token is configured.
	 */
	private function auth_headers(): array {
		$headers = array(
			'Accept'     => 'application/vnd.github.v3+json',
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		);
		if ( ! empty( $this->access_token ) ) {
			$headers['Authorization'] = 'Bearer ' . $this->access_token;
		}
		return $headers;
	}

	/**
	 * Inject the Authorization header for every outbound request that targets this repo.
	 *
	 * Covers three URL patterns:
	 *  1. api.github.com/repos/… — commit checks and zipball endpoint
	 *  2. codeload.github.com/…  — redirect target GitHub uses for archive downloads
	 *
	 * GitHub's private-repo zipball endpoint returns a 302 to codeload.github.com with a
	 * short-lived token embedded in the URL, so auth is usually not needed there. However
	 * some server/cURL configurations strip the redirect token, so we send the header
	 * to codeload as well as a safety net.
	 *
	 * @param array  $args Request arguments.
	 * @param string $url  Request URL.
	 * @return array
	 */
	public function inject_auth_header( array $args, string $url ): array {
		if ( empty( $this->access_token ) ) {
			return $args;
		}

		$repo_slug    = $this->username . '/' . $this->repository;
		$is_api       = false !== strpos( $url, 'api.github.com/repos/' . $repo_slug );
		$is_codeload  = false !== strpos( $url, 'codeload.github.com/' . $repo_slug );

		if ( $is_api || $is_codeload ) {
			if ( ! isset( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
				$args['headers'] = array();
			}
			$args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
		}

		return $args;
	}

	/**
	 * Fetch the latest commit from the tracked branch.
	 * Results cached for 1 hour.
	 *
	 * @param bool $force Bypass transient cache.
	 * @return object|false
	 */
	public function get_latest_commit( bool $force = false ) {
		if ( ! $force && null !== $this->github_response ) {
			return $this->github_response;
		}

		$transient_key = 'meowseo_gh_commit_data';
		if ( ! $force ) {
			$cached = get_site_transient( $transient_key );
			if ( false !== $cached ) {
				$this->github_response = $cached;
				return $cached;
			}
		}

		$branch = get_option( 'meowseo_update_branch', 'main' );
		$url    = "https://api.github.com/repos/{$this->username}/{$this->repository}/commits/{$branch}";

		$response = wp_remote_get( $url, array(
			'timeout' => 10,
			'headers' => $this->auth_headers(),
		) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $data ) || ! isset( $data->sha ) ) {
			return false;
		}

		$this->github_response = $data;
		set_site_transient( $transient_key, $data, HOUR_IN_SECONDS );

		return $this->github_response;
	}

	/**
	 * Push update info into the plugins transient so WordPress shows the update notice.
	 *
	 * @param object $transient
	 * @return object
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$latest_commit = $this->get_latest_commit();
		if ( ! $latest_commit || empty( $latest_commit->sha ) ) {
			return $transient;
		}

		$remote_sha = sanitize_text_field( $latest_commit->sha );
		$local_sha  = get_option( 'meowseo_installed_sha', '' );

		if ( $remote_sha !== $local_sha ) {
			$branch          = get_option( 'meowseo_update_branch', 'main' );
			$display_version = ( defined( 'MEOWSEO_VERSION' ) ? MEOWSEO_VERSION : '1.0.0' ) . '.' . substr( $remote_sha, 0, 7 );

			$transient->response[ $this->basename ] = (object) array(
				'slug'        => $this->slug,
				'plugin'      => $this->basename,
				'new_version' => $display_version,
				'url'         => $latest_commit->html_url,
				'package'     => "https://api.github.com/repos/{$this->username}/{$this->repository}/zipball/{$branch}",
				'icons'       => array(
					'1x' => 'https://avatars.githubusercontent.com/u/' . $this->get_org_id() . '?v=4',
				),
			);
		}

		return $transient;
	}

	/**
	 * Populate the "View details" popup.
	 */
	public function get_plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || $args->slug !== $this->slug ) {
			return $result;
		}

		$latest_commit = $this->get_latest_commit();
		if ( ! $latest_commit ) {
			return $result;
		}

		$branch      = get_option( 'meowseo_update_branch', 'main' );
		$remote_sha  = sanitize_text_field( $latest_commit->sha );
		$message     = isset( $latest_commit->commit->message ) ? esc_html( $latest_commit->commit->message ) : 'Latest update.';
		$date        = isset( $latest_commit->commit->committer->date )
			? gmdate( 'Y-m-d H:i:s', strtotime( $latest_commit->commit->committer->date ) )
			: '';

		$plugin_info                = new \stdClass();
		$plugin_info->name          = 'MeowSEO';
		$plugin_info->slug          = $this->slug;
		$plugin_info->version       = ( defined( 'MEOWSEO_VERSION' ) ? MEOWSEO_VERSION : '1.0.0' ) . '.' . substr( $remote_sha, 0, 7 );
		$plugin_info->author        = '<a href="https://github.com/' . esc_attr( $this->username ) . '">Pustekno</a>';
		$plugin_info->homepage      = 'https://github.com/' . $this->username . '/' . $this->repository;
		$plugin_info->requires      = '6.0';
		$plugin_info->tested        = '6.7';
		$plugin_info->downloaded    = 0;
		$plugin_info->last_updated  = $date;
		$plugin_info->sections      = array(
			'description' => 'A modular WordPress SEO plugin optimized for Google Discover, AI Overviews, and headless WordPress deployments.',
			'changelog'   => '<strong>Commit (' . substr( $remote_sha, 0, 7 ) . '):</strong><br><br>' . nl2br( $message ),
		);
		$plugin_info->download_link = "https://api.github.com/repos/{$this->username}/{$this->repository}/zipball/{$branch}";

		return $plugin_info;
	}

	/**
	 * Rename the extracted folder after download.
	 * GitHub appends a commit hash to the root folder of the zipball.
	 */
	public function rename_github_folder( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
			return $source;
		}

		$new_source = trailingslashit( $remote_source ) . $this->slug;
		$wp_filesystem->move( $source, $new_source );

		return trailingslashit( $new_source );
	}

	/**
	 * Save the new SHA to the database after a successful update to prevent an update loop.
	 */
	public function update_installed_sha( $upgrader_object, $options ) {
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && ! empty( $options['plugins'] ) ) {
			if ( in_array( $this->basename, $options['plugins'], true ) ) {
				delete_site_transient( 'meowseo_gh_commit_data' );
				$latest = $this->get_latest_commit( true );
				if ( $latest && ! empty( $latest->sha ) ) {
					update_option( 'meowseo_installed_sha', sanitize_text_field( $latest->sha ) );
				}
			}
		}
	}

	/**
	 * Clear the cached commit data.
	 */
	public function clear_cache(): void {
		delete_site_transient( 'meowseo_gh_commit_data' );
		$this->github_response = null;
	}

	/**
	 * Returns true if a GitHub token is configured.
	 */
	public function has_token(): bool {
		return ! empty( $this->access_token );
	}

	/**
	 * GitHub org/user ID used for the avatar URL — resolved lazily from the API.
	 */
	private function get_org_id(): string {
		$cached = get_site_transient( 'meowseo_gh_org_id' );
		if ( $cached ) {
			return $cached;
		}
		$response = wp_remote_get( "https://api.github.com/orgs/{$this->username}", array(
			'timeout' => 5,
			'headers' => $this->auth_headers(),
		) );
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( ! empty( $data->id ) ) {
				$id = (string) $data->id;
				set_site_transient( 'meowseo_gh_org_id', $id, DAY_IN_SECONDS );
				return $id;
			}
		}
		return '0';
	}
}
