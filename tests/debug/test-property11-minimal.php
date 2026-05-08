<?php
/**
 * Minimal test to reproduce Property11 issue
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/tests/bootstrap.php';

use MeowSEO\Helpers\Logger;
use Eris\Generators;
use Eris\TestTrait;

class MinimalProperty11Test extends \PHPUnit\Framework\TestCase {
	use TestTrait;
	
	public function test_minimal(): void {
		$this->forAll(
			Generators::int( 1, 10 )
		)
		->then(
			function ( int $num_logs ) {
				echo "\n\nIteration with num_logs = $num_logs\n";
				
				// Reset wpdb storage for this iteration
				reset_wpdb_storage();
				reset_logger_singleton();
				
				// Get reference to wpdb storage
				global $wpdb_storage;
				
				echo "Before logging: wpdb_storage keys = " . implode(', ', array_keys($wpdb_storage)) . "\n";
				
				// Log entries
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i" );
				}
				
				echo "After logging: wpdb_storage keys = " . implode(', ', array_keys($wpdb_storage)) . "\n";
				
				// Get log entries from wpdb storage
				$log_entries = $wpdb_storage['wp_meowseo_logs'] ?? [];
				
				echo "Log entries count: " . count( $log_entries ) . "\n";
				
				// Verify the entry count matches the number of logs
				$this->assertEquals(
					$num_logs,
					count( $log_entries ),
					"Expected $num_logs entries, got " . count( $log_entries )
				);
			}
		);
	}
}

// Run the test
$test = new MinimalProperty11Test('test_minimal');
$result = $test->run();

echo "\n\nTest result:\n";
echo "Tests run: " . $result->count() . "\n";
echo "Failures: " . $result->failureCount() . "\n";
echo "Errors: " . $result->errorCount() . "\n";
