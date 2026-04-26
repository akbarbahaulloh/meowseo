<?php
/**
 * MeowIndex Verification Handler
 *
 * Serves the API key verification file virtualy for IndexNow protocol.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\MeowIndex;

use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verification_Handler class
 */
class Verification_Handler {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'handle_verification_request' ) );
	}

	/**
	 * Handle verification file request
	 *
	 * Intercepts requests for {key}.txt and echoes the key.
	 *
	 * @return void
	 */
	public function handle_verification_request(): void {
		$request_uri = $_SERVER['REQUEST_URI'];
		$path        = parse_url( $request_uri, PHP_URL_PATH );
		$filename    = basename( $path );

		// Check if it's a .txt file request.
		if ( ! str_ends_with( $filename, '.txt' ) ) {
			return;
		}

		$api_key = $this->options->get( 'meowindex_api_key', '' );
		if ( empty( $api_key ) ) {
			return;
		}

		// Check if requested filename matches {key}.txt.
		if ( $filename === $api_key . '.txt' ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo esc_html( $api_key );
			exit;
		}
	}
}
