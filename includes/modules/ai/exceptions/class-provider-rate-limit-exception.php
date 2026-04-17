<?php
/**
 * Provider Rate Limit Exception.
 *
 * Exception thrown when an AI provider returns a rate limit error (HTTP 429).
 *
 * @package MeowSEO\Modules\AI\Exceptions
 */

namespace MeowSEO\Modules\AI\Exceptions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Provider_Rate_Limit_Exception
 *
 * Thrown when an AI provider returns HTTP 429 (Too Many Requests).
 *
 * This exception includes a $retry_after property that indicates how long
 * to wait before retrying the provider. The Provider_Manager uses this
 * to cache the rate limit status and skip the provider during the retry period.
 *
 * @since 1.0.0
 */
class Provider_Rate_Limit_Exception extends Provider_Exception {

	/**
	 * The number of seconds to wait before retrying.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private int $retry_after;

	/**
	 * Constructor.
	 *
	 * Automatically sets the HTTP code to 429 (Too Many Requests).
	 *
	 * @since 1.0.0
	 *
	 * @param string $provider_slug The provider slug (e.g., 'gemini', 'openai').
	 * @param int    $retry_after   Optional. Seconds to wait before retrying. Default 60.
	 */
	public function __construct( string $provider_slug, int $retry_after = 60 ) {
		parent::__construct(
			'Rate limit exceeded',
			$provider_slug,
			429
		);
		$this->retry_after = $retry_after;
	}

	/**
	 * Get the retry-after duration.
	 *
	 * Returns the number of seconds to wait before retrying the provider.
	 *
	 * @since 1.0.0
	 *
	 * @return int Seconds to wait before retrying.
	 */
	public function get_retry_after(): int {
		return $this->retry_after;
	}
}
