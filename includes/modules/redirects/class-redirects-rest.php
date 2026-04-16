<?php
/**
 * Redirects REST API Handler
 *
 * Provides REST endpoints for redirect CRUD operations.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Redirects;

use MeowSEO\Helpers\Logger;
use MeowSEO\Options;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirects REST API class
 *
 * Handles REST endpoints for redirect management.
 * All endpoints require manage_options capability and nonce verification.
 *
 * @since 1.0.0
 */
class Redirects_REST {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * REST namespace
	 *
	 * @var string
	 */
	private const NAMESPACE = 'meowseo/v1';

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Register REST routes
	 *
	 * Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /meowseo/v1/redirects - List redirects
		register_rest_route(
			self::NAMESPACE,
			'/redirects',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_redirects' ),
				'permission_callback' => array( $this, 'check_manage_options' ),
			)
		);

		// POST /meowseo/v1/redirects - Create redirect (Requirement 16.1)
		register_rest_route(
			self::NAMESPACE,
			'/redirects',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_redirect' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
				'args'                => $this->get_redirect_schema(),
			)
		);

		// PUT /meowseo/v1/redirects/{id} - Update redirect (Requirement 16.2)
		register_rest_route(
			self::NAMESPACE,
			'/redirects/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_redirect' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
				'args'                => array_merge(
					array(
						'id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
					$this->get_redirect_schema()
				),
			)
		);

		// DELETE /meowseo/v1/redirects/{id} - Delete redirect (Requirement 16.3)
		register_rest_route(
			self::NAMESPACE,
			'/redirects/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_redirect' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /meowseo/v1/redirects/import - Import redirects from CSV (Requirement 16.4)
		register_rest_route(
			self::NAMESPACE,
			'/redirects/import',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_redirects' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
			)
		);

		// GET /meowseo/v1/redirects/export - Export redirects to CSV (Requirement 16.5)
		register_rest_route(
			self::NAMESPACE,
			'/redirects/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_redirects' ),
				'permission_callback' => array( $this, 'check_manage_options' ),
			)
		);
	}

	/**
	 * Get redirect schema for validation
	 *
	 * @return array Schema definition.
	 */
	private function get_redirect_schema(): array {
		return array(
			'source_url'    => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function( $value ) {
					return ! empty( $value );
				},
			),
			'target_url'    => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => function( $value ) {
					return ! empty( $value );
				},
			),
			'redirect_type' => array(
				'required'          => false,
				'type'              => 'integer',
				'default'           => 301,
				'sanitize_callback' => 'absint',
				'validate_callback' => function( $value ) {
					return in_array( (int) $value, array( 301, 302, 307, 410, 451 ), true );
				},
			),
			'is_regex'      => array(
				'required'          => false,
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'status'        => array(
				'required'          => false,
				'type'              => 'string',
				'default'           => 'active',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function( $value ) {
					return in_array( $value, array( 'active', 'inactive' ), true );
				},
			),
		);
	}

	/**
	 * Check manage_options capability
	 *
	 * Requirement 15.3: Verify user capability
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public function check_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check manage_options capability and nonce
	 *
	 * Requirement 16.6: Verify nonce and capability
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function check_manage_options_and_nonce( WP_REST_Request $request ) {
		// Check capability first (Requirement 16.6)
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage redirects.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		// Verify nonce (Requirement 16.6)
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_cookie_invalid_nonce',
				__( 'Cookie nonce is invalid.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get redirects list
	 *
	 * Supports pagination via page and per_page query parameters.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_redirects( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		// Pagination parameters
		$page = max( 1, $request->get_param( 'page' ) ?? 1 );
		$per_page = max( 1, min( 100, $request->get_param( 'per_page' ) ?? 50 ) );
		$offset = ( $page - 1 ) * $per_page;

		// Get total count
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		// Get redirects with pagination
		$redirects = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		// Calculate total pages
		$total_pages = ceil( $total / $per_page );

		$response = new WP_REST_Response( $redirects );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Create redirect
	 *
	 * Requirements: 16.1, 16.6, 6.1, 6.2, 6.3, 6.4
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function create_redirect( WP_REST_Request $request ) {
		// Prepare data
		$data = array(
			'source_url'    => $request->get_param( 'source_url' ),
			'target_url'    => $request->get_param( 'target_url' ),
			'redirect_type' => $request->get_param( 'redirect_type' ) ?? 301,
			'is_regex'      => $request->get_param( 'is_regex' ) ? 1 : 0,
			'is_active'     => 1,
		);

		// Validate redirect data (Requirement 6.1, 6.2, 6.3, 6.4)
		$validation_error = $this->validate_redirect_data( $data );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		// Check for redirect chains (Requirement 6.1, 6.2, 6.3, 6.4)
		$chain_error = $this->check_redirect_chain( $data['target_url'], $data['source_url'] );
		if ( is_wp_error( $chain_error ) ) {
			return $chain_error;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_redirects';

		$format = array( '%s', '%s', '%d', '%d', '%d' );

		// Insert redirect
		$result = $wpdb->insert( $table, $data, $format );

		if ( false === $result ) {
			return new WP_Error(
				'db_insert_error',
				__( 'Failed to create redirect.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		// Update has_regex_rules flag if this is a regex rule
		if ( $data['is_regex'] ) {
			$this->update_regex_rules_flag();
		}

		// Get created redirect
		$redirect_id = $wpdb->insert_id;
		$redirect = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $redirect_id ),
			ARRAY_A
		);

		// Log creation
		Logger::info(
			'Redirect created via REST API',
			array(
				'redirect_id' => $redirect_id,
				'source_url'  => $data['source_url'],
				'target_url'  => $data['target_url'],
			)
		);

		$response = new WP_REST_Response( $redirect, 201 );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Update redirect
	 *
	 * Requirements: 16.2, 16.6, 6.1, 6.2, 6.3, 6.4
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function update_redirect( WP_REST_Request $request ) {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';
		$redirect_id = $request->get_param( 'id' );

		// Check if redirect exists (Requirement 16.6)
		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $redirect_id ),
			ARRAY_A
		);

		if ( ! $existing ) {
			return new WP_Error(
				'redirect_not_found',
				__( 'Redirect not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		// Prepare data
		$data = array();
		$format = array();

		if ( $request->has_param( 'source_url' ) ) {
			$data['source_url'] = $request->get_param( 'source_url' );
			$format[] = '%s';
		}

		if ( $request->has_param( 'target_url' ) ) {
			$data['target_url'] = $request->get_param( 'target_url' );
			$format[] = '%s';
		}

		if ( $request->has_param( 'redirect_type' ) ) {
			$data['redirect_type'] = $request->get_param( 'redirect_type' );
			$format[] = '%d';
		}

		if ( $request->has_param( 'is_regex' ) ) {
			$data['is_regex'] = $request->get_param( 'is_regex' ) ? 1 : 0;
			$format[] = '%d';
		}

		// Merge with existing data for validation
		$full_data = array_merge( $existing, $data );

		// Validate redirect data (Requirement 6.1, 6.2, 6.3, 6.4)
		$validation_error = $this->validate_redirect_data( $full_data );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		// Check for redirect chains if target URL is being updated (Requirement 6.1, 6.2, 6.3, 6.4)
		if ( isset( $data['target_url'] ) ) {
			$source_url = $data['source_url'] ?? $existing['source_url'];
			$chain_error = $this->check_redirect_chain( $data['target_url'], $source_url, $redirect_id );
			if ( is_wp_error( $chain_error ) ) {
				return $chain_error;
			}
		}

		// Update redirect
		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => $redirect_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_update_error',
				__( 'Failed to update redirect.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		// Update has_regex_rules flag
		$this->update_regex_rules_flag();

		// Get updated redirect
		$redirect = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $redirect_id ),
			ARRAY_A
		);

		// Log update
		Logger::info(
			'Redirect updated via REST API',
			array(
				'redirect_id' => $redirect_id,
				'changes'     => array_keys( $data ),
			)
		);

		$response = new WP_REST_Response( $redirect );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Delete redirect
	 *
	 * Requirements: 16.3, 16.6
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function delete_redirect( WP_REST_Request $request ) {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';
		$redirect_id = $request->get_param( 'id' );

		// Check if redirect exists (Requirement 16.6)
		$redirect = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $redirect_id ),
			ARRAY_A
		);

		if ( ! $redirect ) {
			return new WP_Error(
				'redirect_not_found',
				__( 'Redirect not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		// Delete redirect
		$result = $wpdb->delete(
			$table,
			array( 'id' => $redirect_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_delete_error',
				__( 'Failed to delete redirect.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		// Update has_regex_rules flag
		$this->update_regex_rules_flag();

		// Log deletion
		Logger::info(
			'Redirect deleted via REST API',
			array(
				'redirect_id' => $redirect_id,
				'source_url'  => $redirect['source_url'],
			)
		);

		$response = new WP_REST_Response(
			array(
				'deleted'  => true,
				'redirect' => $redirect,
			)
		);
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Validate redirect data
	 *
	 * Checks required fields and valid redirect types.
	 * Requirements: 6.1, 6.2, 6.3, 6.4, 16.6
	 *
	 * @param array $data Redirect data to validate.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_redirect_data( array $data ) {
		// Check required fields (Requirement 16.6)
		if ( empty( $data['source_url'] ) ) {
			return new WP_Error(
				'missing_source_url',
				__( 'Source URL is required.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $data['target_url'] ) ) {
			return new WP_Error(
				'missing_target_url',
				__( 'Target URL is required.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Validate redirect type (Requirement 16.6)
		$valid_types = array( 301, 302, 307, 410, 451 );
		$redirect_type = isset( $data['redirect_type'] ) ? absint( $data['redirect_type'] ) : 301;

		if ( ! in_array( $redirect_type, $valid_types, true ) ) {
			return new WP_Error(
				'invalid_redirect_type',
				sprintf(
					__( 'Invalid redirect type. Must be one of: %s', 'meowseo' ),
					implode( ', ', $valid_types )
				),
				array( 'status' => 400 )
			);
		}

		// Validate source and target are not the same
		if ( $data['source_url'] === $data['target_url'] ) {
			return new WP_Error(
				'same_source_target',
				__( 'Source URL and target URL cannot be the same.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Check for redirect chains
	 *
	 * Prevents creating redirect loops by checking if the target URL
	 * is already a source URL in another redirect.
	 * Requirements: 6.1, 6.2, 6.3, 6.4
	 *
	 * @param string   $target_url Target URL to check.
	 * @param string   $source_url Source URL of the redirect being created/updated.
	 * @param int|null $exclude_id Redirect ID to exclude from check (for updates).
	 * @return true|WP_Error True if no chain detected, WP_Error otherwise.
	 */
	private function check_redirect_chain( string $target_url, string $source_url, ?int $exclude_id = null ) {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		// Check if target URL is a source URL in another redirect (Requirement 6.1, 6.2)
		$query = $wpdb->prepare(
			"SELECT id, source_url, target_url FROM {$table} WHERE source_url = %s AND is_active = 1",
			$target_url
		);

		// Exclude current redirect if updating
		if ( $exclude_id ) {
			$query .= $wpdb->prepare( ' AND id != %d', $exclude_id );
		}

		$existing_redirect = $wpdb->get_row( $query, ARRAY_A );

		if ( $existing_redirect ) {
			// Target URL is already a source URL - this would create a chain (Requirement 6.2, 6.3)
			Logger::warning(
				'Redirect chain detected',
				array(
					'source_url'           => $source_url,
					'target_url'           => $target_url,
					'existing_redirect_id' => $existing_redirect['id'],
					'chain'                => array( $source_url, $target_url, $existing_redirect['target_url'] ),
				)
			);

			return new WP_Error(
				'redirect_chain_detected',
				sprintf(
					__( 'Redirect chain detected: %s redirects to %s, which already redirects to %s. Please update the existing redirect or choose a different target.', 'meowseo' ),
					$source_url,
					$target_url,
					$existing_redirect['target_url']
				),
				array( 'status' => 400 )
			);
		}

		// Check if source URL is a target URL in another redirect (would create reverse chain)
		$query = $wpdb->prepare(
			"SELECT id, source_url, target_url FROM {$table} WHERE target_url = %s AND is_active = 1",
			$source_url
		);

		// Exclude current redirect if updating
		if ( $exclude_id ) {
			$query .= $wpdb->prepare( ' AND id != %d', $exclude_id );
		}

		$reverse_redirect = $wpdb->get_row( $query, ARRAY_A );

		if ( $reverse_redirect ) {
			// Source URL is already a target URL - this would create a reverse chain (Requirement 6.2, 6.3)
			Logger::warning(
				'Reverse redirect chain detected',
				array(
					'source_url'           => $source_url,
					'target_url'           => $target_url,
					'existing_redirect_id' => $reverse_redirect['id'],
					'chain'                => array( $reverse_redirect['source_url'], $source_url, $target_url ),
				)
			);

			return new WP_Error(
				'reverse_redirect_chain_detected',
				sprintf(
					__( 'Reverse redirect chain detected: %s already redirects to %s. Creating this redirect would form a chain. Please update the existing redirect instead.', 'meowseo' ),
					$reverse_redirect['source_url'],
					$source_url
				),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Update has_regex_rules option flag
	 *
	 * Checks if any active regex rules exist and updates the option flag.
	 * This flag is used to skip regex matching when no regex rules exist.
	 *
	 * @return void
	 */
	private function update_regex_rules_flag(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		// Check if any active regex rules exist
		$has_regex = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE is_regex = 1 AND is_active = 1"
		);

		// Update option flag
		$this->options->set( 'has_regex_rules', $has_regex > 0 );
		$this->options->save();
	}

	/**
	 * Import redirects from CSV data
	 *
	 * Expected CSV format: source_url,target_url,redirect_type,is_regex
	 * Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 16.4
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function import_redirects( WP_REST_Request $request ) {
		$files = $request->get_file_params();

		if ( empty( $files['file'] ) ) {
			Logger::error(
				'CSV import failed: No file provided',
				[
					'file_name' => '',
					'error'     => 'No file uploaded',
				]
			);

			return new WP_Error(
				'no_file',
				__( 'No file provided for import.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		$file = $files['file'];
		$file_name = $file['name'];

		// Validate file type
		$file_ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		if ( 'csv' !== $file_ext ) {
			Logger::error(
				'CSV import failed: Invalid file type',
				[
					'file_name' => $file_name,
					'error'     => 'File must be CSV format',
				]
			);

			return new WP_Error(
				'invalid_file_type',
				__( 'File must be in CSV format.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Read CSV file
		$file_path = $file['tmp_name'];
		$handle = fopen( $file_path, 'r' );

		if ( false === $handle ) {
			Logger::error(
				'CSV import failed: Could not open file',
				[
					'file_name' => $file_name,
					'error'     => 'Failed to open file for reading',
				]
			);

			return new WP_Error(
				'file_read_error',
				__( 'Could not read the uploaded file.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_redirects';

		$imported_count = 0;
		$skipped_count = 0;
		$row_number = 0;
		$errors = [];

		// Skip header row if present
		$first_row = fgetcsv( $handle );
		if ( $first_row && ( 'source_url' === strtolower( $first_row[0] ) || 'source' === strtolower( $first_row[0] ) ) ) {
			// Header row detected, continue to next row
			$row_number++;
		} else {
			// No header, rewind to start
			rewind( $handle );
		}

		// Process each row
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_number++;

			// Skip empty rows
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			// Validate row has at least 2 columns (source and target)
			if ( count( $row ) < 2 ) {
				$skipped_count++;
				$errors[] = sprintf( 'Row %d: Missing required columns', $row_number );
				continue;
			}

			$source_url = trim( $row[0] );
			$target_url = trim( $row[1] );
			$redirect_type = isset( $row[2] ) ? absint( $row[2] ) : 301;
			$is_regex = isset( $row[3] ) ? (bool) $row[3] : false;

			// Validate required fields
			if ( empty( $source_url ) || empty( $target_url ) ) {
				$skipped_count++;
				$errors[] = sprintf( 'Row %d: Empty source or target URL', $row_number );
				continue;
			}

			// Validate redirect type
			if ( ! in_array( $redirect_type, array( 301, 302, 307, 410, 451 ), true ) ) {
				$redirect_type = 301; // Default to 301
			}

			// Insert redirect
			$result = $wpdb->insert(
				$table,
				[
					'source_url'    => $source_url,
					'target_url'    => $target_url,
					'redirect_type' => $redirect_type,
					'is_regex'      => $is_regex ? 1 : 0,
					'is_active'     => 1,
				],
				[ '%s', '%s', '%d', '%d', '%d' ]
			);

			if ( false === $result ) {
				$skipped_count++;
				$errors[] = sprintf( 'Row %d: Database insert failed', $row_number );
			} else {
				$imported_count++;
			}
		}

		fclose( $handle );

		// Update regex rules flag
		$this->update_regex_rules_flag();

		// Log result
		if ( $imported_count > 0 ) {
			Logger::info(
				'CSV import completed successfully',
				[
					'file_name'      => $file_name,
					'row_count'      => $imported_count,
					'skipped_count'  => $skipped_count,
				]
			);
		} else {
			Logger::error(
				'CSV import failed: No rows imported',
				[
					'file_name' => $file_name,
					'error'     => 'All rows were skipped or invalid',
					'errors'    => $errors,
				]
			);

			return new WP_Error(
				'import_failed',
				__( 'No redirects were imported. Please check the CSV format.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		$response = new WP_REST_Response(
			[
				'success'        => true,
				'imported_count' => $imported_count,
				'skipped_count'  => $skipped_count,
				'errors'         => $errors,
			]
		);
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Export redirects to CSV format
	 *
	 * Returns all redirect rules in CSV format with proper Content-Type headers.
	 * Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 16.5
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object with CSV data.
	 */
	public function export_redirects( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		// Get all redirects (Requirement 12.5, 16.5)
		$redirects = $wpdb->get_results(
			"SELECT source_url, target_url, redirect_type, is_regex FROM {$table} ORDER BY id ASC",
			ARRAY_A
		);

		// Build CSV content
		$csv_lines = array();

		// Add header row (Requirement 12.1, 12.2)
		$csv_lines[] = 'source_url,target_url,redirect_type,is_regex';

		// Add data rows (Requirement 12.3, 12.4)
		foreach ( $redirects as $redirect ) {
			$csv_lines[] = sprintf(
				'"%s","%s",%d,%d',
				str_replace( '"', '""', $redirect['source_url'] ),
				str_replace( '"', '""', $redirect['target_url'] ),
				$redirect['redirect_type'],
				$redirect['is_regex']
			);
		}

		$csv_content = implode( "\n", $csv_lines );

		// Log export (Requirement 12.6)
		Logger::info(
			'CSV export completed via REST API',
			array(
				'redirect_count' => count( $redirects ),
			)
		);

		// Create response with proper headers (Requirement 16.5)
		$response = new WP_REST_Response( $csv_content );
		$response->header( 'Content-Type', 'text/csv; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename=meowseo-redirects-' . gmdate( 'Y-m-d' ) . '.csv' );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}
}
