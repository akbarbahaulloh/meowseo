<?php
require_once __DIR__ . '/tests/bootstrap.php';

use MeowSEO\Helpers\Logger;

global $meowseo_test_logs, $wpdb_storage;

echo "Before logging:\n";
echo "meowseo_test_logs: " . var_export( $meowseo_test_logs ?? 'not set', true ) . "\n";
echo "wpdb_storage keys: " . print_r( array_keys( $wpdb_storage ?? [] ), true ) . "\n\n";

// Test basic logging
Logger::info( "Test message 1" );

echo "After logging:\n";
echo "meowseo_test_logs: " . var_export( $meowseo_test_logs ?? 'not set', true ) . "\n";
echo "wpdb_storage keys: " . print_r( array_keys( $wpdb_storage ?? [] ), true ) . "\n";
echo "wpdb_storage['wp_meowseo_logs'] count: " . count( $wpdb_storage['wp_meowseo_logs'] ?? [] ) . "\n";
