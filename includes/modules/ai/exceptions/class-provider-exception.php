<?php
/**
 * Base Provider Exception.
 *
 * Base exception class for AI provider errors.
 *
 * @package MeowSEO\Modules\AI\Exceptions
 */

namespace MeowSEO\Modules\AI\Exceptions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Provider_Exception
 *
 * Base exception class for all AI provider-related errors.
 *
 * This exception provides provider context through the $provider_slug property,
 * allowing error handlers to identify which provider failed.
 *
 * @since 1.0.0
 */
class Provider_Exception extends \Exception {

	/**
	 * The provider slug identifying which provider threw this exception.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $provider_slug;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message       The exception message.
	 * @param string $provider_slug The provider slug (e.g., 'gemini', 'openai').
	 * @param int    $code          Optional. The exception code. Default 0.
	 */
	public function __construct( string $message, string $provider_slug, int $code = 0 ) {
		parent::__construct( $message, $code );
		$this->provider_slug = $provider_slug;
	}

	/**
	 * Get the provider slug.
	 *
	 * Returns the identifier of the provider that threw this exception.
	 *
	 * @since 1.0.0
	 *
	 * @return string The provider slug.
	 */
	public function get_provider_slug(): string {
		return $this->provider_slug;
	}
}
