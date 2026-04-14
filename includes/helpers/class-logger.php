<?php
/**
 * Logger singleton class.
 *
 * Provides centralized logging with automatic error capture, database storage,
 * deduplication, and sensitive data sanitization.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Helpers
 */

namespace MeowSEO\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Logger class.
 *
 * Singleton pattern implementation for centralized logging across all modules.
 */
class Logger {

	/**
	 * Singleton instance.
	 *
	 * @var Logger|null
	 */
	private static ?Logger $instance = null;

	/**
	 * Maximum number of log entries to keep.
	 */
	private const MAX_ENTRIES = 1000;

	/**
	 * Deduplication time window in seconds (5 minutes).
	 */
	private const DEDUP_WINDOW = 300;

	/**
	 * Get singleton instance.
	 *
	 * @return Logger Singleton instance.
	 */
	public static function get_instance(): Logger {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton pattern.
	 *
	 * Registers error handlers on instantiation.
	 */
	private function __construct() {
		$this->register_error_handlers();
	}

	/**
	 * Log debug message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 * @return void
	 */
	public static function debug( string $message, array $context = [] ): void {
		self::get_instance()->log( 'DEBUG', $message, $context );
	}

	/**
	 * Log info message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 * @return void
	 */
	public static function info( string $message, array $context = [] ): void {
		self::get_instance()->log( 'INFO', $message, $context );
	}

	/**
	 * Log warning message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 * @return void
	 */
	public static function warning( string $message, array $context = [] ): void {
		self::get_instance()->log( 'WARNING', $message, $context );
	}

	/**
	 * Log error message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 * @return void
	 */
	public static function error( string $message, array $context = [] ): void {
		self::get_instance()->log( 'ERROR', $message, $context );
	}

	/**
	 * Log critical message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 * @return void
	 */
	public static function critical( string $message, array $context = [] ): void {
		self::get_instance()->log( 'CRITICAL', $message, $context );
	}

	/**
	 * Internal log method.
	 *
	 * Handles automatic field capture, sanitization, deduplication, and storage.
	 *
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 * @return void
	 */
	private function log( string $level, string $message, array $context = [] ): void {
		global $wpdb;

		// Capture automatic fields.
		$timestamp = current_time( 'mysql' );
		$module = $this->get_calling_module();
		$message_hash = hash( 'sha256', $message );

		// Sanitize sensitive data from context.
		$sanitized_context = $this->sanitize_context( $context );

		// Serialize context to JSON.
		$context_json = ! empty( $sanitized_context ) ? wp_json_encode( $sanitized_context ) : null;

		// Extract stack trace if present in context.
		$stack_trace = $sanitized_context['stack_trace'] ?? null;
		if ( $stack_trace && is_string( $stack_trace ) ) {
			unset( $sanitized_context['stack_trace'] );
			$context_json = ! empty( $sanitized_context ) ? wp_json_encode( $sanitized_context ) : null;
		}

		// Prepare log entry data.
		$data = [
			'level'        => $level,
			'module'       => $module,
			'message'      => $message,
			'message_hash' => $message_hash,
			'context'      => $context_json,
			'stack_trace'  => $stack_trace,
			'created_at'   => $timestamp,
		];

		// Try deduplication first.
		if ( $this->deduplicate_log( $data ) ) {
			return; // Duplicate found and updated.
		}

		// Store new log entry.
		$this->store_log_entry( $data );

		// Cleanup old logs if needed.
		$this->cleanup_old_logs();
	}

	/**
	 * Store log entry in database.
	 *
	 * @param array $data Log entry data.
	 * @return void
	 */
	private function store_log_entry( array $data ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_logs';

		$wpdb->insert(
			$table,
			$data,
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Check for duplicate log entry and increment hit count if found.
	 *
	 * @param array $data Log entry data.
	 * @return bool True if duplicate found and updated, false otherwise.
	 */
	private function deduplicate_log( array $data ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_logs';

		// Calculate time window (5 minutes ago).
		$time_window = gmdate( 'Y-m-d H:i:s', time() - self::DEDUP_WINDOW );

		// Find existing entry within time window.
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE level = %s AND module = %s AND message_hash = %s AND created_at >= %s ORDER BY created_at DESC LIMIT 1",
				$data['level'],
				$data['module'],
				$data['message_hash'],
				$time_window
			)
		);

		if ( ! $existing ) {
			return false;
		}

		// Increment hit count and update timestamp.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET hit_count = hit_count + 1, created_at = %s WHERE id = %d",
				$data['created_at'],
				$existing['id']
			)
		);

		return true;
	}

	/**
	 * Cleanup old log entries when limit is exceeded.
	 *
	 * Maintains maximum of 1000 entries by deleting oldest entries.
	 *
	 * @return void
	 */
	private function cleanup_old_logs(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_logs';

		// Get current entry count.
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		if ( $count <= self::MAX_ENTRIES ) {
			return;
		}

		// Calculate how many entries to delete.
		$to_delete = $count - self::MAX_ENTRIES;

		// Delete oldest entries.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} ORDER BY created_at ASC LIMIT %d",
				$to_delete
			)
		);
	}

	/**
	 * Sanitize context data to remove sensitive information.
	 *
	 * @param array $context Context data.
	 * @return array Sanitized context data.
	 */
	private function sanitize_context( array $context ): array {
		$sensitive_patterns = [
			'token',
			'key',
			'password',
			'secret',
			'api_key',
			'access_token',
			'refresh_token',
			'client_secret',
		];

		return $this->sanitize_recursive( $context, $sensitive_patterns );
	}

	/**
	 * Recursively sanitize context data.
	 *
	 * @param array $data     Data to sanitize.
	 * @param array $patterns Sensitive key patterns.
	 * @return array Sanitized data.
	 */
	private function sanitize_recursive( array $data, array $patterns ): array {
		foreach ( $data as $key => $value ) {
			// Check if key matches sensitive pattern.
			foreach ( $patterns as $pattern ) {
				if ( false !== stripos( $key, $pattern ) ) {
					$data[ $key ] = '[REDACTED]';
					continue 2;
				}
			}

			// Recursively sanitize nested arrays.
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->sanitize_recursive( $value, $patterns );
			}
		}

		return $data;
	}

	/**
	 * Get calling module name from backtrace.
	 *
	 * @return string Module name or 'core' if not detected.
	 */
	private function get_calling_module(): string {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );

		foreach ( $backtrace as $frame ) {
			if ( ! isset( $frame['file'] ) ) {
				continue;
			}

			$file = $frame['file'];

			// Check if file is in modules directory.
			if ( false !== strpos( $file, '/modules/' ) ) {
				// Extract module name from path.
				// Example: /path/to/includes/modules/gsc/class-gsc.php -> gsc
				if ( preg_match( '#/modules/([^/]+)/#', $file, $matches ) ) {
					return $matches[1];
				}
			}
		}

		return 'core';
	}

	/**
	 * Register error handlers.
	 *
	 * Sets up PHP error handler and shutdown function for fatal errors.
	 *
	 * @return void
	 */
	private function register_error_handlers(): void {
		// Register custom error handler.
		set_error_handler( [ $this, 'error_handler' ], E_ALL );

		// Register shutdown function for fatal errors.
		register_shutdown_function( [ $this, 'shutdown_handler' ] );
	}

	/**
	 * Custom error handler.
	 *
	 * Captures PHP errors and logs them via Logger.
	 *
	 * @param int    $errno   Error level.
	 * @param string $errstr  Error message.
	 * @param string $errfile Error file.
	 * @param int    $errline Error line.
	 * @return bool True to prevent default error handler.
	 */
	public function error_handler( int $errno, string $errstr, string $errfile, int $errline ): bool {
		// Only capture errors from MeowSEO namespace.
		if ( false === strpos( $errfile, 'meowseo' ) ) {
			return false; // Let default handler process it.
		}

		// Map PHP error level to log level.
		$log_level = $this->map_error_level( $errno );

		// Log the error.
		$this->log(
			$log_level,
			$errstr,
			[
				'error_level' => $errno,
				'file'        => $errfile,
				'line'        => $errline,
			]
		);

		// Return false to allow default error handler to run as well.
		return false;
	}

	/**
	 * Shutdown handler for fatal errors.
	 *
	 * Captures fatal errors that cannot be caught by error_handler.
	 *
	 * @return void
	 */
	public function shutdown_handler(): void {
		$error = error_get_last();

		if ( null === $error ) {
			return;
		}

		// Only capture fatal errors.
		$fatal_errors = [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ];
		if ( ! in_array( $error['type'], $fatal_errors, true ) ) {
			return;
		}

		// Only capture errors from MeowSEO namespace.
		if ( false === strpos( $error['file'], 'meowseo' ) ) {
			return;
		}

		// Log the fatal error.
		$this->log(
			'CRITICAL',
			$error['message'],
			[
				'error_level' => $error['type'],
				'file'        => $error['file'],
				'line'        => $error['line'],
			]
		);
	}

	/**
	 * Map PHP error level to log level.
	 *
	 * @param int $errno PHP error level.
	 * @return string Log level.
	 */
	private function map_error_level( int $errno ): string {
		switch ( $errno ) {
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				return 'CRITICAL';

			case E_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
			case E_USER_WARNING:
				return 'WARNING';

			case E_NOTICE:
			case E_USER_NOTICE:
				return 'INFO';

			case E_DEPRECATED:
			case E_USER_DEPRECATED:
			case E_STRICT:
				return 'DEBUG';

			default:
				return 'ERROR';
		}
	}
}
