<?php
/**
 * GSC API class
 *
 * Thin wrapper around Google Search Console and Indexing APIs.
 * Handles URL Inspection, Indexing API, and Search Analytics API calls.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\GSC
 */

namespace MeowSEO\Modules\GSC;

use MeowSEO\Helpers\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * GSC_API class
 *
 * Implements Google Search Console API wrapper with consistent response format.
 */
class GSC_API {

	/**
	 * URL Inspection API endpoint.
	 */
	private const INSPECTION_API_URL = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';

	/**
	 * Indexing API endpoint.
	 */
	private const INDEXING_API_URL = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

	/**
	 * Search Analytics API endpoint template.
	 */
	private const ANALYTICS_API_URL = 'https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/searchAnalytics/query';

	/**
	 * GSC Auth instance.
	 *
	 * @var GSC_Auth
	 */
	private GSC_Auth $auth;

	/**
	 * Constructor.
	 *
	 * @param GSC_Auth $auth GSC Auth instance.
	 */
	public function __construct( GSC_Auth $auth ) {
		$this->auth = $auth;
	}

	/**
	 * Inspect URL using URL Inspection API.
	 *
	 * Calls the URL Inspection API endpoint to retrieve indexing status,
	 * coverage state, crawled date, and issues for the given URL.
	 * Requirement 14.1: Provide inspect_url() method that calls URL Inspection API endpoint.
	 * Requirement 14.2: Return indexing status, coverage state, crawled date, and issues.
	 * Requirement 14.3: Use wp_remote_request() with Authorization Bearer header.
	 * Requirement 14.4: Return error array if get_valid_token() returns false.
	 *
	 * @param string $url URL to inspect.
	 * @return array Response array with keys: success, data, http_code.
	 */
	public function inspect_url( string $url ): array {
		// Get valid access token.
		$token = $this->auth->get_valid_token();

		if ( null === $token ) {
			Logger::error(
				'Cannot inspect URL: no valid access token',
				[
					'module' => 'gsc',
					'url'    => $url,
				]
			);

			return [
				'success'   => false,
				'data'      => null,
				'http_code' => 0,
			];
		}

		// Prepare request body.
		$body = [
			'inspectionUrl' => $url,
			'siteUrl'       => home_url( '/' ),
		];

		// Make API request.
		$response = $this->make_request( 'POST', self::INSPECTION_API_URL, $body );

		// Log the inspection request.
		if ( $response['success'] ) {
			Logger::info(
				'URL inspection completed successfully',
				[
					'module' => 'gsc',
					'url'    => $url,
				]
			);
		} else {
			Logger::error(
				'URL inspection failed',
				[
					'module'    => 'gsc',
					'url'       => $url,
					'http_code' => $response['http_code'],
				]
			);
		}

		return $response;
	}

	/**
	 * Submit URL for indexing using Indexing API.
	 *
	 * Sends a POST request to the Indexing API endpoint with URL and type='URL_UPDATED'.
	 * Requirement 11.1: Submit posts to Google for indexing automatically.
	 * Requirement 11.2: Send POST request with URL and type='URL_UPDATED'.
	 * Requirement 11.3: Use wp_remote_request() with Authorization Bearer header.
	 * Requirement 11.4: Return error array if get_valid_token() returns false.
	 *
	 * @param string $url URL to submit for indexing.
	 * @return array Response array with keys: success, data, http_code.
	 */
	public function submit_for_indexing( string $url ): array {
		// Get valid access token.
		$token = $this->auth->get_valid_token();

		if ( null === $token ) {
			Logger::error(
				'Cannot submit URL for indexing: no valid access token',
				[
					'module' => 'gsc',
					'url'    => $url,
				]
			);

			return [
				'success'   => false,
				'data'      => null,
				'http_code' => 0,
			];
		}

		// Prepare request body.
		$body = [
			'url'  => $url,
			'type' => 'URL_UPDATED',
		];

		// Make API request.
		$response = $this->make_request( 'POST', self::INDEXING_API_URL, $body );

		// Log the indexing request.
		if ( $response['success'] ) {
			Logger::info(
				'URL submitted for indexing successfully',
				[
					'module' => 'gsc',
					'url'    => $url,
				]
			);
		} else {
			Logger::error(
				'URL indexing submission failed',
				[
					'module'    => 'gsc',
					'url'       => $url,
					'http_code' => $response['http_code'],
				]
			);
		}

		return $response;
	}

	/**
	 * Get search analytics data using Search Analytics API.
	 *
	 * Calls the Search Analytics query endpoint to retrieve clicks, impressions,
	 * CTR, and position data for the specified parameters.
	 * Requirement 15.1: Provide get_search_analytics() method that calls Search Analytics query endpoint.
	 * Requirement 15.2: Accept parameters for site_url, start_date, end_date, dimensions, data_state.
	 * Requirement 15.3: Include Google Discover data when data_state='all'.
	 * Requirement 15.4: Return consistent array shape with keys for success, data, and http_code.
	 * Requirement 15.5: Store search analytics data in meowseo_gsc_data table.
	 *
	 * @param string $site_url   Site URL (e.g., 'https://example.com/').
	 * @param string $start_date Start date in YYYY-MM-DD format.
	 * @param string $end_date   End date in YYYY-MM-DD format.
	 * @param array  $dimensions Dimensions to group by (e.g., ['page', 'query']).
	 * @param string $data_state Data state: 'final' or 'all' (default: 'final').
	 * @return array Response array with keys: success, data, http_code.
	 */
	public function get_search_analytics( string $site_url, string $start_date, string $end_date, array $dimensions, string $data_state = 'final' ): array {
		// Get valid access token.
		$token = $this->auth->get_valid_token();

		if ( null === $token ) {
			Logger::error(
				'Cannot get search analytics: no valid access token',
				[
					'module'     => 'gsc',
					'site_url'   => $site_url,
					'start_date' => $start_date,
					'end_date'   => $end_date,
				]
			);

			return [
				'success'   => false,
				'data'      => null,
				'http_code' => 0,
			];
		}

		// Build API URL with site URL.
		$api_url = str_replace( '{siteUrl}', rawurlencode( $site_url ), self::ANALYTICS_API_URL );

		// Prepare request body.
		$body = [
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => $dimensions,
		];

		// Include Google Discover data when data_state='all'.
		if ( 'all' === $data_state ) {
			$body['dataState'] = 'all';
		}

		// Make API request.
		$response = $this->make_request( 'POST', $api_url, $body );

		// Store analytics data in database if successful.
		if ( $response['success'] && ! empty( $response['data'] ) ) {
			$this->store_analytics_data( $site_url, $start_date, $end_date, $response['data'] );

			Logger::info(
				'Search analytics data retrieved successfully',
				[
					'module'     => 'gsc',
					'site_url'   => $site_url,
					'start_date' => $start_date,
					'end_date'   => $end_date,
					'row_count'  => count( $response['data']['rows'] ?? [] ),
				]
			);
		} else {
			Logger::error(
				'Search analytics retrieval failed',
				[
					'module'     => 'gsc',
					'site_url'   => $site_url,
					'start_date' => $start_date,
					'end_date'   => $end_date,
					'http_code'  => $response['http_code'],
				]
			);
		}

		return $response;
	}

	/**
	 * Make HTTP request to Google API.
	 *
	 * Sends an HTTP request with Authorization Bearer header and handles the response.
	 * Requirement 14.3: Use wp_remote_request() with Authorization Bearer header.
	 *
	 * @param string $method HTTP method (GET, POST, PUT, DELETE).
	 * @param string $url    API endpoint URL.
	 * @param array  $body   Request body (optional).
	 * @return array Response array with keys: success, data, http_code.
	 */
	private function make_request( string $method, string $url, array $body = [] ): array {
		// Get valid access token.
		$token = $this->auth->get_valid_token();

		if ( null === $token ) {
			return [
				'success'   => false,
				'data'      => null,
				'http_code' => 0,
			];
		}

		// Prepare request arguments.
		$args = [
			'method'  => strtoupper( $method ),
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'timeout' => 30,
		];

		// Add body if provided.
		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		// Make HTTP request.
		$response = wp_remote_request( $url, $args );

		// Handle response.
		return $this->handle_response( $response );
	}

	/**
	 * Handle HTTP response from Google API.
	 *
	 * Parses the response and returns a consistent array shape.
	 * Requirement 15.4: Return consistent array shape with keys for success, data, and http_code.
	 *
	 * @param array|\WP_Error $response HTTP response from wp_remote_request().
	 * @return array Response array with keys: success, data, http_code.
	 */
	private function handle_response( $response ): array {
		// Handle WP_Error.
		if ( is_wp_error( $response ) ) {
			Logger::error(
				'HTTP request failed',
				[
					'module' => 'gsc',
					'error'  => $response->get_error_message(),
				]
			);

			return [
				'success'   => false,
				'data'      => null,
				'http_code' => 0,
			];
		}

		// Get HTTP status code.
		$http_code = wp_remote_retrieve_response_code( $response );

		// Get response body.
		$body = wp_remote_retrieve_body( $response );

		// Decode JSON response.
		$data = json_decode( $body, true );

		// Determine success based on HTTP status code.
		$success = $http_code >= 200 && $http_code < 300;

		return [
			'success'   => $success,
			'data'      => $data,
			'http_code' => $http_code,
		];
	}

	/**
	 * Store search analytics data in database.
	 *
	 * Inserts or updates search analytics data in the meowseo_gsc_data table.
	 * Requirement 15.5: Store search analytics data in meowseo_gsc_data table.
	 *
	 * @param string $site_url   Site URL.
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @param array  $data       Analytics data from API response.
	 * @return void
	 */
	private function store_analytics_data( string $site_url, string $start_date, string $end_date, array $data ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_data';

		// Check if table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( ! $table_exists ) {
			Logger::warning(
				'GSC data table does not exist, skipping storage',
				[
					'module' => 'gsc',
					'table'  => $table,
				]
			);
			return;
		}

		// Process each row in the analytics data.
		if ( ! empty( $data['rows'] ) ) {
			foreach ( $data['rows'] as $row ) {
				// Extract dimensions.
				$page  = $row['keys'][0] ?? null;
				$query = $row['keys'][1] ?? null;

				// Extract metrics.
				$clicks      = $row['clicks'] ?? 0;
				$impressions = $row['impressions'] ?? 0;
				$ctr         = $row['ctr'] ?? 0.0;
				$position    = $row['position'] ?? 0.0;

				// Prepare data for insertion.
				$insert_data = [
					'site_url'    => $site_url,
					'page'        => $page,
					'query'       => $query,
					'clicks'      => $clicks,
					'impressions' => $impressions,
					'ctr'         => $ctr,
					'position'    => $position,
					'date'        => $start_date,
					'created_at'  => current_time( 'mysql' ),
				];

				// Insert or update data.
				$wpdb->replace(
					$table,
					$insert_data,
					[ '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%s' ]
				);
			}

			Logger::debug(
				'Stored search analytics data',
				[
					'module'    => 'gsc',
					'site_url'  => $site_url,
					'row_count' => count( $data['rows'] ),
				]
			);
		}
	}
}
