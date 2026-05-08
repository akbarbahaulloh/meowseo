<?php
require_once __DIR__ . '/tests/bootstrap.php';

use MeowSEO\Helpers\Logger;

// Test basic logging
Logger::info( "Test message 1" );
Logger::info( "Test message 2" );

global $wpdb_storage;
echo "wpdb_storage keys: " . print_r( array_keys( $wpdb_storage ), true ) . "\n";
echo "wp_meowseo_logs count: " . count( $wpdb_storage['wp_meowseo_logs'] ?? [] ) . "\n";
echo "wp_meowseo_logs content: " . print_r( $wpdb_storage['wp_meowseo_logs'] ?? [], true ) . "\n";
