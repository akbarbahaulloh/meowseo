<?php
/**
 * Debug script to test Logger deduplication
 */

require_once __DIR__ . '/tests/bootstrap.php';

use MeowSEO\Helpers\Logger;

echo "Test: Logger Deduplication\n";
echo "==========================\n\n";

// Reset wpdb storage
reset_wpdb_storage();
reset_logger_singleton();

// Get reference to wpdb storage
global $wpdb_storage;

// Log the same message multiple times
echo "Logging 'Test message' 5 times...\n";
for ($i = 0; $i < 5; $i++) {
	Logger::info('Test message');
}

// Get log entries from wpdb storage
$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];

echo "\nResults:\n";
echo "--------\n";
echo "Number of log entries: " . count($log_entries) . "\n";

if (count($log_entries) === 1) {
	echo "SUCCESS: Only 1 entry created (deduplication working)\n";
	$entry = reset($log_entries);
	echo "Hit count: " . $entry['hit_count'] . "\n";
	if ($entry['hit_count'] === 5) {
		echo "SUCCESS: Hit count is 5 (correct)\n";
	} else {
		echo "ERROR: Hit count is " . $entry['hit_count'] . ", expected 5\n";
	}
} else {
	echo "ERROR: Expected 1 entry, got " . count($log_entries) . "\n";
	echo "Entries:\n";
	print_r($log_entries);
}
