<?php
/**
 * Link Checker Engine
 *
 * Performs robust HTTP status checks for internal and external links.
 * Inspired by the "Broken Link Checker" local engine.
 *
 * @package MeowSEO
 * @subpackage Modules\Internal_Links
 */

namespace MeowSEO\Modules\Internal_Links;

defined( 'ABSPATH' ) || exit;

/**
 * Link_Checker class.
 */
class Link_Checker {
	/**
	 * HTTP Timeout in seconds.
	 *
	 * @var int
	 */
	private int $timeout;

	/**
	 * Constructor.
	 *
	 * @param int $timeout HTTP Timeout.
	 */
	public function __construct( int $timeout = 30 ) {
		$this->timeout = $timeout;
	}


	/**
	 * Check a URL status.
	 *
	 * Uses HEAD request first, falls back to GET if needed.
	 *
	 * @param string $url URL to check.
	 * @return array Result data (http_status, is_broken, redirect_url, error_log).
	 */
	public function check( string $url ): array {
		$result = array(
			'http_status'  => 0,
			'is_broken'    => false,
			'redirect_url' => null,
			'error_log'    => '',
		);

		// 1. Try HEAD request first (efficient).
		$response = $this->make_request( $url, 'HEAD' );

		// 2. If HEAD fails with 404, 405, or other errors that might be false positives, try GET.
		if ( is_wp_error( $response ) || $this->should_retry_with_get( wp_remote_retrieve_response_code( $response ) ) ) {
			$response = $this->make_request( $url, 'GET' );
		}

		if ( is_wp_error( $response ) ) {
			$result['is_broken'] = true;
			$result['error_log'] = $response->get_error_message();
			return $result;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$result['http_status'] = $status_code;

		// Handle Redirects.
		if ( in_array( $status_code, array( 301, 302, 303, 307, 308 ), true ) ) {
			$result['redirect_url'] = wp_remote_retrieve_header( $response, 'location' );
		}

		// Determine if broken (4xx or 5xx).
		if ( $status_code >= 400 ) {
			$result['is_broken'] = true;
			$result['error_log'] = wp_remote_retrieve_response_message( $response );
		}

		return $result;
	}

	/**
	 * Make an HTTP request.
	 *
	 * @param string $url    URL.
	 * @param string $method HTTP method (HEAD or GET).
	 * @return \WP_Error|array Response or error.
	 */
	private function make_request( string $url, string $method = 'HEAD' ): \WP_Error|array {
		$args = array(
			'method'      => $method,
			'timeout'     => $this->timeout,
			'redirection' => 5,
			'user-agent'  => 'MeowSEO Link Checker/1.0 (WordPress/' . get_bloginfo( 'version' ) . ')',
			'sslverify'   => false, // Avoid SSL errors for local checking.
		);

		if ( 'GET' === $method ) {
			// Limit download size for GET requests.
			$args['headers'] = array(
				'Range' => 'bytes=0-2048',
			);
		}

		return wp_remote_request( $url, $args );
	}

	/**
	 * Check if we should retry a failed HEAD request with GET.
	 *
	 * @param int|string $status_code Status code.
	 * @return bool True if should retry.
	 */
	private function should_retry_with_get( int|string $status_code ): bool {
		$status_code = (int) $status_code;
		// 405 Method Not Allowed is a common reason HEAD fails.
		// 403 Forbidden can also sometimes be bypassed with GET.
		// 404 can sometimes be a false positive on HEAD for some servers.
		return in_array( $status_code, array( 403, 404, 405, 501, 0 ), true );
	}
}
