<?php
/**
 * Link Throttler class.
 *
 * Manages rate limiting for link checks on a per-domain basis.
 *
 * @package MeowSEO\Modules\Internal_Links
 */

namespace MeowSEO\Modules\Internal_Links;

defined( 'ABSPATH' ) || exit;

/**
 * Link_Throttler class.
 */
class Link_Throttler {

	/**
	 * Transient prefix.
	 */
	private const PREFIX = 'meowseo_throttle_';

	/**
	 * Default delay in seconds between requests to the same domain.
	 *
	 * @var int
	 */
	private int $default_delay = 5;

	/**
	 * Check if a domain is currently throttled.
	 *
	 * @param string $url URL to check.
	 * @return bool True if throttled, false otherwise.
	 */
	public function is_throttled( string $url ): bool {
		$domain = $this->get_domain( $url );
		if ( ! $domain ) {
			return false;
		}

		$next_allowed = get_transient( self::PREFIX . $domain );
		if ( ! $next_allowed ) {
			return false;
		}

		return time() < (int) $next_allowed;
	}

	/**
	 * Apply throttling to a domain.
	 *
	 * Sets a timestamp for when the next request is allowed.
	 *
	 * @param string $url   URL that was just checked.
	 * @param int    $delay Optional custom delay in seconds.
	 * @return void
	 */
	public function throttle( string $url, int $delay = 0 ): void {
		$domain = $this->get_domain( $url );
		if ( ! $domain ) {
			return;
		}

		$delay = $delay > 0 ? $delay : $this->default_delay;
		set_transient( self::PREFIX . $domain, time() + $delay, $delay + 60 );
	}

	/**
	 * Extract domain from URL.
	 *
	 * @param string $url URL.
	 * @return string|null Domain or null if invalid.
	 */
	private function get_domain( string $url ): ?string {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		return $host ? strtolower( $host ) : null;
	}

	/**
	 * Get remaining wait time for a domain in seconds.
	 *
	 * @param string $url URL.
	 * @return int Seconds to wait.
	 */
	public function get_delay_remaining( string $url ): int {
		$domain = $this->get_domain( $url );
		if ( ! $domain ) {
			return 0;
		}

		$next_allowed = get_transient( self::PREFIX . $domain );
		if ( ! $next_allowed ) {
			return 0;
		}

		return max( 0, (int) $next_allowed - time() );
	}
}
