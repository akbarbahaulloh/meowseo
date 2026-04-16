<?php
/**
 * Monitor 404 REST API
 *
 * Provides REST endpoints for accessing and managing 404 log data.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\Monitor_404
 */

namespace MeowSEO\Modules\Monitor_404;

use MeowSEO\Helpers\DB;
use MeowSEO\Helpers\Logger;
use MeowSEO\Options;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Monitor 404 REST API class
 *
 * Handles REST endpoint registration and request processing.
 * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 13.4, 13.5
 */
class Monitor_404_REST {

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
	 * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 13.4, 13.5
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /meowseo/v1/404-log - Get paginated 404 log entries (Requirement 17.1).
		register_rest_route(
			self::NAMESPACE,
			'/404-log',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_log' ),
				'permission_callback' => array( $this, 'check_manage_options' ),
				'args'                => array(
					'page'     => array(
						'type'              => 'integer',
						'default'           => 1,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'type'              => 'integer',
						'default'           => 50,
						'minimum'           => 1,
						'maximum'           => 100,
						'sanitize_callback' => 'absint',
					),
					'orderby'  => array(
						'type'              => 'string',
						'default'           => 'last_seen',
						'enum'              => array( 'id', 'url', 'hit_count', 'first_seen', 'last_seen' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'order'    => array(
						'type'              => 'string',
						'default'           => 'DESC',
						'enum'              => array( 'ASC', 'DESC' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// DELETE /meowseo/v1/404-log/{id} - Delete a 404 log entry (Requirement 17.4).
		register_rest_route(
			self::NAMESPACE,
			'/404-log/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_entry' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /meowseo/v1/404-log/ignore - Add URL to ignore list (Requirement 13.4).
		register_rest_route(
			self::NAMESPACE,
			'/404-log/ignore',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ignore_url' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
				'args'                => array(
					'url' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'entry_id' => array(
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /meowseo/v1/404-log/clear-all - Delete all 404 log entries (Requirement 13.5).
		register_rest_route(
			self::NAMESPACE,
			'/404-log/clear-all',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'clear_all' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
			)
		);
	}

	/**
	 * Get paginated 404 log entries.
	 *
	 * Requirements: 17.1, 17.2, 17.3
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_log( WP_REST_Request $request ) {
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$orderby  = $request->get_param( 'orderby' );
		$order    = $request->get_param( 'order' );

		$offset = ( $page - 1 ) * $per_page;

		$args = array(
			'limit'   => $per_page,
			'offset'  => $offset,
			'orderby' => $orderby,
			'order'   => $order,
		);

		$entries = DB::get_404_log( $args );

		// Get total count for pagination.
		global $wpdb;
		$table       = $wpdb->prefix . 'meowseo_404_log';
		$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		$response = new WP_REST_Response(
			array(
				'entries'    => $entries,
				'pagination' => array(
					'page'        => $page,
					'per_page'    => $per_page,
					'total'       => (int) $total_count,
					'total_pages' => ceil( $total_count / $per_page ),
				),
			)
		);

		// Add cache control headers for CDN/edge caching.
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Delete a 404 log entry.
	 *
	 * Requirements: 17.4, 17.5
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function delete_entry( WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_404_log';

		$deleted = $wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete 404 log entry.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		if ( 0 === $deleted ) {
			return new WP_Error(
				'not_found',
				__( '404 log entry not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( '404 log entry deleted successfully.', 'meowseo' ),
			)
		);

		// No caching for mutation endpoints.
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Add URL to ignore list.
	 *
	 * Requirement 13.4: Add URL to ignore list in plugin options.
	 * Requirement 17.5: Verify nonce and check manage_options capability.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function ignore_url( WP_REST_Request $request ) {
		$url = $request->get_param( 'url' );
		$entry_id = $request->get_param( 'entry_id' );

		// Validate URL parameter.
		if ( empty( $url ) ) {
			return new WP_Error(
				'missing_url',
				__( 'URL parameter is required.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Get current ignore list.
		$ignore_list = $this->options->get( 'monitor_404_ignore_list', array() );
		if ( ! is_array( $ignore_list ) ) {
			$ignore_list = array();
		}

		// Add URL to ignore list if not already present.
		if ( ! in_array( $url, $ignore_list, true ) ) {
			$ignore_list[] = $url;
			$this->options->set( 'monitor_404_ignore_list', $ignore_list );
			$this->options->save();
		}

		// Remove from 404 log if entry_id is provided.
		if ( $entry_id ) {
			global $wpdb;
			$log_table = $wpdb->prefix . 'meowseo_404_log';
			$wpdb->delete(
				$log_table,
				array( 'id' => $entry_id ),
				array( '%d' )
			);
		}

		// Log the action.
		Logger::info(
			'URL added to 404 ignore list via REST API',
			array(
				'url'      => $url,
				'entry_id' => $entry_id,
			)
		);

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'URL added to ignore list successfully.', 'meowseo' ),
			)
		);

		// No caching for mutation endpoints.
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Clear all 404 log entries.
	 *
	 * Requirement 13.5: Delete all rows from 404 log table.
	 * Requirement 17.5: Verify nonce and check manage_options capability.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function clear_all( WP_REST_Request $request ) {
		global $wpdb;
		$log_table = $wpdb->prefix . 'meowseo_404_log';

		// Get count before deletion for logging.
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table}" );

		// Delete all entries using TRUNCATE for performance.
		$result = $wpdb->query( "TRUNCATE TABLE {$log_table}" );

		if ( false === $result ) {
			Logger::error(
				'Failed to clear 404 log via REST API',
				array(
					'error' => $wpdb->last_error,
				)
			);

			return new WP_Error(
				'clear_failed',
				__( 'Failed to clear 404 log.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		// Log the action.
		Logger::info(
			'404 log cleared via REST API',
			array(
				'entries_deleted' => $count,
			)
		);

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: number of entries deleted */
					__( 'Successfully deleted %d 404 log entries.', 'meowseo' ),
					$count
				),
				'deleted_count' => (int) $count,
			)
		);

		// No caching for mutation endpoints.
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Check if user has manage_options capability.
	 *
	 * Requirement 17.5: Check manage_options capability.
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public function check_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if user has manage_options capability and valid nonce.
	 *
	 * Requirement 17.5: Verify nonce and check manage_options capability.
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
