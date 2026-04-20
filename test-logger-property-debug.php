<?php
/**
 * Debug script to test Logger with wpdb mock in property test scenario
 */

require_once __DIR__ . '/tests/bootstrap.php';

use MeowSEO\Helpers\Logger;

echo "Test 1: Simple logging\n";
echo "======================\n";

// Reset wpdb storage
reset_wpdb_storage();
reset_logger_singleton();

// Get reference to wpdb storage
global $wpdb_storage, $wpdb;

echo "Before logging:\n";
echo "wpdb_storage keys: " . implode(', ', array_keys($wpdb_storage)) . "\n\n";

// Log a message
Logger::info('Test message 1');

echo "After logging:\n";
echo "wpdb_storage keys: " . implode(', ', array_keys($wpdb_storage)) . "\n";
if (isset($wpdb_storage['wp_meowseo_logs'])) {
	echo "wp_meowseo_logs entries: " . count($wpdb_storage['wp_meowseo_logs']) . "\n\n";
}

echo "\nTest 2: Multiple iterations (simulating property test)\n";
echo "=======================================================\n";

for ($iteration = 1; $iteration <= 3; $iteration++) {
	echo "\nIteration $iteration:\n";
	echo "-------------------\n";
	
	// Reset wpdb storage for this iteration
	reset_wpdb_storage();
	reset_logger_singleton();
	
	// Get reference to wpdb storage
	global $wpdb_storage;
	
	echo "Before logging: wpdb_storage keys = " . implode(', ', array_keys($wpdb_storage)) . "\n";
	
	// Log messages
	for ($i = 0; $i < 5; $i++) {
		Logger::info("Iteration $iteration Message $i");
	}
	
	// Get log entries from wpdb storage
	$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];
	
	echo "After logging: wpdb_storage keys = " . implode(', ', array_keys($wpdb_storage)) . "\n";
	echo "Log entries count: " . count($log_entries) . "\n";
	
	if (count($log_entries) !== 5) {
		echo "ERROR: Expected 5 entries, got " . count($log_entries) . "\n";
	} else {
		echo "SUCCESS: Got expected 5 entries\n";
	}
}
