<?php
/**
 * Provider Authentication Exception.
 *
 * Exception thrown when an AI provider returns an authentication error (HTTP 401).
 *
 * @package MeowSEO\Modules\AI\Exceptions
 */

namespace MeowSEO\Modules\AI\Exceptions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Provider_Auth_Exception
 *
 * Thrown when an AI provider returns HTTP 401 (Unauthorized) or HTTP 403 (Forbidden).
 *
 * This indicates that the API key is invalid, expired, or lacks necessary permissions.
 * The Provider_Manager catches this exception to mark the API key as invalid and
 * attempt fallback to the next provider.
 *
 * @since 1.0.0
 */
class Provider_Auth_Exception extends Provider_Exception {

	/**
	 * Constructor.
	 *
	 * Automatically sets the HTTP code to 401 (Unauthorized) and uses
	 * a standard error message for authentication failures.
	 *
	 * @since 1.0.0
	 *
	 * @param string $provider_slug The provider slug (e.g., 'gemini', 'openai').
	 */
	public function __construct( string $provider_slug ) {
		parent::__construct(
			'Invalid API key',
			$provider_slug,
			401
		);
	}
}
