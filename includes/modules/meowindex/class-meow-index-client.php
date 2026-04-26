<?php
/**
 * MeowIndex Client
 *
 * Submits URL updates to IndexNow API and Google Indexing API.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\MeowIndex;

use MeowSEO\Options;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MeowIndexClient class
 *
 * Submits URL updates to IndexNow (Bing/Yandex) and Google Indexing API.
 *
 * @since 1.0.0
 */
class MeowIndexClient {

	/**
	 * IndexNow API endpoint
	 */
	private const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';

	/**
	 * Google Indexing API endpoint
	 */
	private const GOOGLE_ENDPOINT = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

	/**
	 * Google OAuth2 token endpoint
	 */
	private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';

	/**
	 * Maximum retry attempts
	 */
	private const MAX_RETRIES = 3;

	/**
	 * Base retry delay in seconds
	 */
	private const BASE_RETRY_DELAY = 5;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Submission queue instance
	 *
	 * @var Submission_Queue
	 */
	private Submission_Queue $queue;

	/**
	 * Submission logger instance
	 *
	 * @var Submission_Logger
	 */
	private Submission_Logger $logger;

	/**
	 * Constructor
	 *
	 * @param Options              $options Options instance.
	 * @param Submission_Queue     $queue   Submission queue instance.
	 * @param Submission_Logger    $logger  Submission logger instance.
	 */
	public function __construct( Options $options, Submission_Queue $queue, Submission_Logger $logger ) {
		$this->options = $options;
		$this->queue   = $queue;
		$this->logger  = $logger;
	}

	/**
	 * Boot the client
	 *
	 * Initializes hooks and cron events for submission.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( ! $this->is_enabled() && ! $this->is_google_enabled() ) {
			return;
		}

		// Hook into post publish/update.
		add_action( 'transition_post_status', array( $this, 'handle_post_transition' ), 10, 3 );

		// Process queued submissions.
		add_action( 'meowseo_process_meowindex_queue', array( $this, 'process_queue' ) );

		// Schedule queue processing if not already scheduled.
		if ( ! wp_next_scheduled( 'meowseo_process_meowindex_queue' ) ) {
			wp_schedule_event( time(), 'meowseo_meowindex_interval', 'meowseo_process_meowindex_queue' );
		}
	}

	/**
	 * Handle post status transition
	 *
	 * Queues URL for submission when post is published or updated.
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function handle_post_transition( string $new_status, string $old_status, \WP_Post $post ): void {
		// Only submit when post is published.
		if ( 'publish' !== $new_status ) {
			return;
		}

		// Skip if post type is not public.
		if ( ! is_post_type_viewable( $post->post_type ) ) {
			return;
		}

		$url = get_permalink( $post );

		if ( ! $url ) {
			return;
		}

		// Add to queue instead of immediate submission.
		$this->queue->add( $url );
	}

	/**
	 * Process queue
	 *
	 * Processes queued URLs by submitting batches to APIs.
	 * Called by WP-Cron event.
	 *
	 * @return void
	 */
	public function process_queue(): void {
		$result = $this->queue->process();

		// If no URLs to process, return early.
		if ( ! isset( $result['urls'] ) || empty( $result['urls'] ) ) {
			return;
		}

		// Submit the batch.
		$this->submit_urls( $result['urls'] );
	}

	/**
	 * Submit multiple URLs
	 *
	 * @param array $urls URLs to submit.
	 * @return array Submission results.
	 */
	public function submit_urls( array $urls ): array {
		if ( empty( $urls ) ) {
			return array( 'success' => false, 'error' => __( 'No URLs provided', 'meowseo' ) );
		}

		$results = array();

		// Submit to IndexNow (Bing/Yandex) if enabled.
		if ( $this->is_enabled() ) {
			$results['meowindex'] = $this->make_indexnow_request( $urls );
			$this->logger->log( $urls, $results['meowindex'], 'meowindex' );
		}

		// Submit to Google if enabled.
		if ( $this->is_google_enabled() ) {
			foreach ( $urls as $url ) {
				$res = $this->make_google_request( $url );
				$results['google'][] = $res;
				$this->logger->log( array( $url ), $res, 'google' );
			}
		}

		return $results;
	}

	/**
	 * Make request to IndexNow API (Bing/Yandex)
	 *
	 * @param array $urls URLs to submit.
	 * @return bool|WP_Error
	 */
	private function make_indexnow_request( array $urls ) {
		$api_key = $this->get_api_key();
		$host    = parse_url( home_url(), PHP_URL_HOST );

		if ( ! $host || empty( $api_key ) ) {
			return new WP_Error( 'meowindex_config_error', __( 'MeowIndex (IndexNow) configuration missing', 'meowseo' ) );
		}

		$body = array(
			'host'    => $host,
			'key'     => $api_key,
			'urlList' => $urls,
		);

		$response = wp_remote_post(
			self::INDEXNOW_ENDPOINT,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( ! in_array( $status_code, array( 200, 202 ), true ) ) {
			return new WP_Error( 'meowindex_failed', sprintf( __( 'IndexNow failed: %d', 'meowseo' ), $status_code ) );
		}

		return true;
	}

	/**
	 * Make request to Google Indexing API
	 *
	 * @param string $url URL to submit.
	 * @return bool|WP_Error
	 */
	private function make_google_request( string $url ) {
		$token = $this->get_google_access_token();

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$body = array(
			'url'  => $url,
			'type' => 'URL_UPDATED',
		);

		$response = wp_remote_post(
			self::GOOGLE_ENDPOINT,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			$msg  = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown error';
			return new WP_Error( 'google_indexing_failed', sprintf( __( 'Google Indexing failed (%d): %s', 'meowseo' ), $status_code, $msg ) );
		}

		return true;
	}

	/**
	 * Get Google Access Token using Service Account JSON
	 *
	 * @return string|WP_Error
	 */
	private function get_google_access_token() {
		// Check cache first.
		$token = get_transient( 'meowseo_google_indexing_token' );
		if ( $token ) {
			return $token;
		}

		$json_key = $this->options->get( 'meowindex_google_json_key', '' );
		if ( empty( $json_key ) ) {
			return new WP_Error( 'google_config_missing', __( 'Google Service Account JSON key is missing', 'meowseo' ) );
		}

		$key_data = json_decode( $json_key, true );
		if ( ! $key_data || ! isset( $key_data['private_key'], $key_data['client_email'] ) ) {
			return new WP_Error( 'google_config_invalid', __( 'Invalid Google Service Account JSON key', 'meowseo' ) );
		}

		// Generate JWT.
		$jwt = $this->generate_google_jwt( $key_data );
		if ( is_wp_error( $jwt ) ) {
			return $jwt;
		}

		// Exchange JWT for access token.
		$response = wp_remote_post(
			self::GOOGLE_TOKEN_URL,
			array(
				'body' => array(
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $jwt,
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( 200 !== $status_code || empty( $data['access_token'] ) ) {
			return new WP_Error( 'google_auth_failed', __( 'Failed to authenticate with Google APIs', 'meowseo' ) );
		}

		// Cache token (valid for 1 hour, cache for 50 mins).
		set_transient( 'meowseo_google_indexing_token', $data['access_token'], 50 * MINUTE_IN_SECONDS );

		return $data['access_token'];
	}

	/**
	 * Generate Signed JWT for Google OAuth2
	 *
	 * @param array $key_data Service account key data.
	 * @return string|WP_Error
	 */
	private function generate_google_jwt( array $key_data ) {
		$header = base64_encode( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) );
		
		$now = time();
		$payload = base64_encode( wp_json_encode( array(
			'iss'   => $key_data['client_email'],
			'scope' => 'https://www.googleapis.com/auth/indexing',
			'aud'   => self::GOOGLE_TOKEN_URL,
			'exp'   => $now + 3600,
			'iat'   => $now,
		) ) );

		$signature = '';
		$success = openssl_sign(
			"$header.$payload",
			$signature,
			$key_data['private_key'],
			'SHA256'
		);

		if ( ! $success ) {
			return new WP_Error( 'jwt_signing_failed', __( 'Failed to sign JWT for Google auth', 'meowseo' ) );
		}

		$signature = base64_encode( $signature );

		// Clean base64 for JWT format (urlsafe).
		$header    = str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), $header );
		$payload   = str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), $payload );
		$signature = str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), $signature );

		return "$header.$payload.$signature";
	}

	/**
	 * Get API key for MeowIndex (IndexNow protocol)
	 *
	 * @return string API key.
	 */
	public function get_api_key(): string {
		$api_key = $this->options->get( 'meowindex_api_key', '' );

		if ( empty( $api_key ) ) {
			$api_key = bin2hex( random_bytes( 16 ) );
			$this->options->set( 'meowindex_api_key', $api_key );
			$this->options->save();
		}

		return $api_key;
	}

	/**
	 * Check if IndexNow is enabled
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) $this->options->get( 'meowindex_enabled', false );
	}

	/**
	 * Check if Google Indexing is enabled
	 *
	 * @return bool
	 */
	public function is_google_enabled(): bool {
		return (bool) $this->options->get( 'meowindex_google_enabled', false );
	}
}
