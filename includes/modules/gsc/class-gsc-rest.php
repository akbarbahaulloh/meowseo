<?php
/**
 * GSC REST API
 *
 * Provides REST endpoints for accessing Google Search Console data.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\GSC
 */

namespace MeowSEO\Modules\GSC;

use MeowSEO\Options;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * GSC REST API class
 *
 * Handles REST endpoint registration and request processing.
 */
class GSC_REST {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'meowseo/v1';

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
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /meowseo/v1/gsc - Get GSC performance data (Requirement 10.6).
		register_rest_route(
			self::NAMESPACE,
			'/gsc',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_gsc_data' ),
				'permission_callback' => array( $this, 'check_manage_options' ),
				'args'                => array(
					'url'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
					'start' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'end'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// POST /meowseo/v1/gsc/auth - Save OAuth credentials (Requirement 10.1, 15.6).
		register_rest_route(
			self::NAMESPACE,
			'/gsc/auth',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_credentials' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
				'args'                => array(
					'access_token'  => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'refresh_token' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'expires_in'    => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// DELETE /meowseo/v1/gsc/auth - Remove OAuth credentials.
		register_rest_route(
			self::NAMESPACE,
			'/gsc/auth',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_credentials' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
			)
		);

		// GET /meowseo/v1/gsc/status - Check connection status.
		register_rest_route(
			self::NAMESPACE,
			'/gsc/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_connection_status' ),
				'permission_callback' => array( $this, 'check_manage_options' ),
			)
		);
	}

	/**
	 * Get GSC performance data.
	 *
	 * Supports filtering by URL or date range (Requirement 10.6).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_gsc_data( WP_REST_Request $request ) {
		$url   = $request->get_param( 'url' );
		$start = $request->get_param( 'start' );
		$end   = $request->get_param( 'end' );

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_gsc_data';

		// Build query based on parameters.
		$where_clauses = array();
		$where_values  = array();

		if ( ! empty( $url ) ) {
			$url_hash        = hash( 'sha256', $url );
			$where_clauses[] = 'url_hash = %s';
			$where_values[]  = $url_hash;
		}

		if ( ! empty( $start ) ) {
			$where_clauses[] = 'date >= %s';
			$where_values[]  = $start;
		}

		if ( ! empty( $end ) ) {
			$where_clauses[] = 'date <= %s';
			$where_values[]  = $end;
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		$query = "SELECT * FROM {$table} {$where_sql} ORDER BY date DESC LIMIT 100";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		$response = new WP_REST_Response(
			array(
				'data'    => $results ?: array(),
				'filters' => array(
					'url'   => $url,
					'start' => $start,
					'end'   => $end,
				),
			)
		);

		// Add cache control headers (Requirement 13.6).
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Save OAuth credentials.
	 *
	 * Encrypts and stores credentials (Requirement 10.1, 15.6).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function save_credentials( WP_REST_Request $request ) {
		$access_token  = $request->get_param( 'access_token' );
		$refresh_token = $request->get_param( 'refresh_token' );
		$expires_in    = $request->get_param( 'expires_in' );

		$credentials = array(
			'access_token'  => $access_token,
			'refresh_token' => $refresh_token,
			'expires_in'    => $expires_in,
			'expires_at'    => time() + ( $expires_in ?: 3600 ),
		);

		// Save encrypted credentials (Requirement 15.6).
		$saved = $this->options->set_gsc_credentials( $credentials );

		if ( ! $saved ) {
			return new WP_Error(
				'save_failed',
				__( 'Failed to save GSC credentials.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'GSC credentials saved successfully.', 'meowseo' ),
			)
		);

		// No caching for mutation endpoints.
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Delete OAuth credentials.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function delete_credentials( WP_REST_Request $request ) {
		$deleted = $this->options->delete_gsc_credentials();

		if ( ! $deleted ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete GSC credentials.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'GSC credentials deleted successfully.', 'meowseo' ),
			)
		);

		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Get connection status.
	 *
	 * Returns boolean indicating if credentials are configured (Requirement 15.6).
	 * Never exposes raw credentials.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_connection_status( WP_REST_Request $request ) {
		$credentials = $this->options->get_gsc_credentials();
		$connected   = ! empty( $credentials ) && ! empty( $credentials['access_token'] );

		$response = new WP_REST_Response(
			array(
				'connected' => $connected,
			)
		);

		$response->header( 'Cache-Control', 'public, max-age=60' );

		return $response;
	}

	/**
	 * Check if user has manage_options capability.
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public function check_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if user has manage_options capability and valid nonce.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function check_manage_options_and_nonce( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		// Verify nonce from request header.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_cookie_invalid_nonce',
				__( 'Invalid nonce.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}

