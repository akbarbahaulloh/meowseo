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
use MeowSEO\Helpers\Logger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * GSC REST API class
 *
 * Handles REST endpoint registration and request processing.
 * Requirements: 18.1, 18.2, 18.3, 18.4, 18.5, 18.6
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
	 * GSC Auth instance.
	 *
	 * @var GSC_Auth
	 */
	private GSC_Auth $auth;

	/**
	 * Constructor.
	 *
	 * @param Options  $options Options instance.
	 * @param GSC_Auth $auth    GSC Auth instance.
	 */
	public function __construct( Options $options, GSC_Auth $auth ) {
		$this->options = $options;
		$this->auth    = $auth;
	}

	/**
	 * Register REST API routes.
	 *
	 * Requirements: 18.1, 18.2, 18.3, 18.4, 18.5, 18.6
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /meowseo/v1/gsc/status - Get connection status (Requirement 18.5).
		register_rest_route(
			self::NAMESPACE,
			'/gsc/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'check_manage_options' ),
			)
		);

		// POST /meowseo/v1/gsc/auth - Save OAuth credentials (Requirement 18.3).
		register_rest_route(
			self::NAMESPACE,
			'/gsc/auth',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_auth' ),
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

		// DELETE /meowseo/v1/gsc/auth - Remove OAuth credentials (Requirement 18.4).
		register_rest_route(
			self::NAMESPACE,
			'/gsc/auth',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'remove_auth' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
			)
		);

		// GET /meowseo/v1/gsc/data - Get GSC performance data (Requirement 18.1, 18.2).
		register_rest_route(
			self::NAMESPACE,
			'/gsc/data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_data' ),
				'permission_callback' => array( $this, 'check_manage_options' ),
				'args'                => array(
					'url'        => array(
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
					'start_date' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'end_date'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Get connection status.
	 *
	 * Returns connection status and auth state.
	 * Requirement 18.5: Return connection status and auth state.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_status( WP_REST_Request $request ): WP_REST_Response {
		// Get auth status.
		$auth_status = get_option( 'meowseo_gsc_auth_status', 'not_authenticated' );

		// Check if credentials exist.
		$access_token  = get_option( 'meowseo_gsc_access_token', '' );
		$refresh_token = get_option( 'meowseo_gsc_refresh_token', '' );
		$token_expiry  = (int) get_option( 'meowseo_gsc_token_expiry', 0 );

		$has_credentials = ! empty( $access_token ) && ! empty( $refresh_token );
		$is_expired      = $has_credentials && time() >= $token_expiry;

		// Determine connection status.
		$connected = $has_credentials && 'authenticated' === $auth_status && ! $is_expired;

		$response = new WP_REST_Response(
			array(
				'connected'   => $connected,
				'auth_status' => $auth_status,
				'has_credentials' => $has_credentials,
				'is_expired'  => $is_expired,
			)
		);

		$response->header( 'Cache-Control', 'public, max-age=60' );

		return $response;
	}

	/**
	 * Save OAuth credentials.
	 *
	 * Encrypts and stores OAuth credentials with nonce and capability check.
	 * Requirement 18.3: Save OAuth credentials.
	 * Requirement 18.6: Verify nonce and check manage_options capability.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function save_auth( WP_REST_Request $request ) {
		$access_token  = $request->get_param( 'access_token' );
		$refresh_token = $request->get_param( 'refresh_token' );
		$expires_in    = $request->get_param( 'expires_in' ) ?? 3600;

		// Validate required parameters.
		if ( empty( $access_token ) ) {
			return new WP_Error(
				'missing_access_token',
				__( 'Access token is required.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Prepare credentials array.
		$credentials = array(
			'access_token'  => $access_token,
			'refresh_token' => $refresh_token ?? '',
			'expires_in'    => $expires_in,
			'token_expiry'  => time() + $expires_in,
		);

		// Store credentials using GSC_Auth.
		$this->auth->store_credentials( $credentials );

		// Set auth status to authenticated.
		update_option( 'meowseo_gsc_auth_status', 'authenticated' );

		// Log the action.
		Logger::info(
			'GSC OAuth credentials saved via REST API',
			array(
				'module' => 'gsc',
			)
		);

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
	 * Remove OAuth credentials.
	 *
	 * Deletes OAuth credentials with nonce and capability check.
	 * Requirement 18.4: Remove OAuth credentials.
	 * Requirement 18.6: Verify nonce and check manage_options capability.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function remove_auth( WP_REST_Request $request ) {
		// Delete all GSC credentials.
		delete_option( 'meowseo_gsc_access_token' );
		delete_option( 'meowseo_gsc_refresh_token' );
		delete_option( 'meowseo_gsc_token_expiry' );
		delete_option( 'meowseo_gsc_auth_status' );
		delete_option( 'meowseo_gsc_client_id' );
		delete_option( 'meowseo_gsc_client_secret' );

		// Log the action.
		Logger::info(
			'GSC OAuth credentials removed via REST API',
			array(
				'module' => 'gsc',
			)
		);

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'GSC credentials removed successfully.', 'meowseo' ),
			)
		);

		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Get GSC performance data.
	 *
	 * Returns GSC performance data with filtering support.
	 * Requirement 18.1: Return GSC performance data.
	 * Requirement 18.2: Support filtering by URL, start date, and end date.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_data( WP_REST_Request $request ) {
		$url        = $request->get_param( 'url' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_gsc_data';

		// Check if table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( ! $table_exists ) {
			return new WP_Error(
				'table_not_found',
				__( 'GSC data table does not exist.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		// Build query with WHERE clauses (Requirement 18.2).
		$where_clauses = array();
		$where_values  = array();

		if ( ! empty( $url ) ) {
			$where_clauses[] = 'page = %s';
			$where_values[]  = $url;
		}

		if ( ! empty( $start_date ) ) {
			$where_clauses[] = 'date >= %s';
			$where_values[]  = $start_date;
		}

		if ( ! empty( $end_date ) ) {
			$where_clauses[] = 'date <= %s';
			$where_values[]  = $end_date;
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		$query = "SELECT * FROM {$table} {$where_sql} ORDER BY date DESC LIMIT 100";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		// Log the request.
		Logger::debug(
			'GSC data retrieved via REST API',
			array(
				'module'      => 'gsc',
				'url'         => $url,
				'start_date'  => $start_date,
				'end_date'    => $end_date,
				'result_count' => count( $results ),
			)
		);

		$response = new WP_REST_Response(
			array(
				'data'    => $results ?: array(),
				'filters' => array(
					'url'        => $url,
					'start_date' => $start_date,
					'end_date'   => $end_date,
				),
			)
		);

		// Add cache control headers.
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Check if user has manage_options capability.
	 *
	 * Requirement 18.6: Check manage_options capability.
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public function check_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if user has manage_options capability and valid nonce.
	 *
	 * Requirement 18.6: Verify nonce and check manage_options capability before mutation.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function check_manage_options_and_nonce( WP_REST_Request $request ) {
		// Check capability first (Requirement 18.6).
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		// Verify nonce from request header (Requirement 18.6).
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
