<?php
/**
 * Log_Formatter class.
 *
 * Formats log entries for AI editor consumption with markdown output.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Helpers
 */

namespace MeowSEO\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Log_Formatter class.
 *
 * Provides static methods for formatting log entries into AI-friendly markdown.
 */
class Log_Formatter {

	/**
	 * Format multiple log entries for AI editor.
	 *
	 * Includes system context, active modules, and formatted entries.
	 *
	 * @param array $log_entries Array of log entry arrays from database.
	 * @return string Markdown-formatted output.
	 */
	public static function format_for_ai( array $log_entries ): string {
		$output = "# MeowSEO Debug Log Export\n\n";

		// Add system information.
		$system_context = self::get_system_context();
		$output .= "## System Information\n";
		$output .= sprintf( "- Plugin Version: %s\n", $system_context['plugin_version'] );
		$output .= sprintf( "- WordPress Version: %s\n", $system_context['wordpress_version'] );
		$output .= sprintf( "- PHP Version: %s\n", $system_context['php_version'] );

		// Add active modules.
		$active_modules = self::get_active_modules();
		$output .= sprintf( "- Active Modules: %s\n\n", implode( ', ', $active_modules ) );

		// Add log entries.
		$output .= "## Log Entries\n\n";

		$entry_number = 1;
		foreach ( $log_entries as $entry ) {
			$output .= self::format_single_entry( $entry, $entry_number );
			$entry_number++;
		}

		return $output;
	}

	/**
	 * Format a single log entry.
	 *
	 * @param array $entry        Log entry array from database.
	 * @param int   $entry_number Optional entry number for display.
	 * @return string Markdown-formatted entry.
	 */
	public static function format_single_entry( array $entry, int $entry_number = 1 ): string {
		$output = '';

		// Entry header.
		$level = $entry['level'] ?? 'UNKNOWN';
		$module = $entry['module'] ?? 'unknown';
		$output .= sprintf( "### Entry %d: %s - %s Module\n", $entry_number, $level, ucfirst( $module ) );

		// Timestamp.
		$timestamp = $entry['created_at'] ?? 'Unknown';
		$output .= sprintf( "**Timestamp**: %s\n", $timestamp );

		// Module.
		$output .= sprintf( "**Module**: %s\n", $module );

		// Message.
		$message = $entry['message'] ?? 'No message';
		$output .= sprintf( "**Message**: %s\n", $message );

		// Hit count if greater than 1.
		$hit_count = $entry['hit_count'] ?? 1;
		if ( $hit_count > 1 ) {
			$output .= sprintf( "**Hit Count**: %d\n", $hit_count );
		}

		// Context.
		if ( ! empty( $entry['context'] ) ) {
			$context = self::parse_context( $entry['context'] );
			if ( ! empty( $context ) ) {
				$output .= "\n**Context**:\n";
				$output .= "```json\n";
				$output .= wp_json_encode( $context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
				$output .= "\n```\n";
			}
		}

		// Stack trace.
		if ( ! empty( $entry['stack_trace'] ) ) {
			$formatted_trace = self::format_stack_trace( $entry['stack_trace'] );
			$output .= "\n**Stack Trace**:\n";
			$output .= "```\n";
			$output .= $formatted_trace;
			$output .= "\n```\n";
		}

		$output .= "\n";

		return $output;
	}

	/**
	 * Parse JSON context string into array.
	 *
	 * Returns empty array on failure and logs parsing error.
	 *
	 * @param string $json_context JSON-encoded context string.
	 * @return array Parsed context array or empty array on failure.
	 */
	public static function parse_context( string $json_context ): array {
		if ( empty( $json_context ) ) {
			return [];
		}

		$context = json_decode( $json_context, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			// Log parsing error via Logger if available.
			if ( class_exists( 'MeowSEO\Helpers\Logger' ) ) {
				Logger::error(
					'Failed to parse log context JSON',
					[
						'json_error' => json_last_error_msg(),
						'raw_context' => substr( $json_context, 0, 200 ), // First 200 chars only.
					]
				);
			}
			return [];
		}

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Get system context information.
	 *
	 * @return array System context with plugin, WordPress, and PHP versions.
	 */
	private static function get_system_context(): array {
		return [
			'plugin_version'    => defined( 'MEOWSEO_VERSION' ) ? MEOWSEO_VERSION : 'Unknown',
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version'       => phpversion(),
		];
	}

	/**
	 * Get list of active module names.
	 *
	 * @return array Array of active module names.
	 */
	private static function get_active_modules(): array {
		// Try to get enabled modules from Options.
		if ( class_exists( 'MeowSEO\Options' ) ) {
			try {
				$options = new \MeowSEO\Options();
				$enabled_modules = $options->get_enabled_modules();
				return is_array( $enabled_modules ) ? $enabled_modules : [];
			} catch ( \Exception $e ) {
				// Fall through to default.
			}
		}

		// Fallback: return empty array.
		return [];
	}

	/**
	 * Format stack trace with file paths and line numbers.
	 *
	 * Handles malformed stack traces by including raw trace.
	 *
	 * @param string $stack_trace Raw stack trace string.
	 * @return string Formatted stack trace.
	 */
	private static function format_stack_trace( string $stack_trace ): string {
		if ( empty( $stack_trace ) ) {
			return '';
		}

		// Stack trace is already formatted as string from Exception->getTraceAsString()
		// or debug_backtrace. We just need to ensure it's readable.
		
		// Split into lines.
		$lines = explode( "\n", $stack_trace );
		$formatted_lines = [];

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			// Try to parse and enhance the line format.
			// Expected format: #0 /path/to/file.php(123): function()
			if ( preg_match( '/^#(\d+)\s+(.+?)(\(\d+\)):\s*(.+)$/', $line, $matches ) ) {
				$frame_number = $matches[1];
				$file_path = $matches[2];
				$line_number = $matches[3];
				$function_call = $matches[4];

				// Format: #0 /path/to/file.php(123): function()
				$formatted_lines[] = sprintf(
					'#%s %s%s: %s',
					$frame_number,
					$file_path,
					$line_number,
					$function_call
				);
			} else {
				// Keep original line if parsing fails.
				$formatted_lines[] = $line;
			}
		}

		return implode( "\n", $formatted_lines );
	}
}
