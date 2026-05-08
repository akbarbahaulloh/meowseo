<?php
/**
 * Test reset_logger_singleton function
 */

require_once __DIR__ . '/tests/bootstrap.php';

use MeowSEO\Helpers\Logger;

echo "Test: reset_logger_singleton function\n";
echo "======================================\n\n";

// Get the Logger instance
$logger1 = Logger::get_instance();
echo "Logger instance 1: " . spl_object_id($logger1) . "\n";

// Reset the singleton
reset_logger_singleton();

// Get the Logger instance again
$logger2 = Logger::get_instance();
echo "Logger instance 2: " . spl_object_id($logger2) . "\n";

if (spl_object_id($logger1) === spl_object_id($logger2)) {
	echo "ERROR: Logger instances are the same (reset didn't work)\n";
} else {
	echo "SUCCESS: Logger instances are different (reset worked)\n";
}

// Test that logging works after reset
reset_wpdb_storage();
reset_logger_singleton();

global $wpdb_storage;

Logger::info('Test message after reset');

$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];
echo "\nLog entries after reset: " . count($log_entries) . "\n";

if (count($log_entries) === 1) {
	echo "SUCCESS: Logging works after reset\n";
} else {
	echo "ERROR: Logging doesn't work after reset\n";
}
