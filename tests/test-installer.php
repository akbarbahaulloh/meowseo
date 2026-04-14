<?php
/**
 * Installer Tests
 *
 * Unit tests for the Installer class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Installer;

/**
 * Installer test case
 *
 * @since 1.0.0
 */
class Test_Installer extends TestCase {

	/**
	 * Test that get_schema returns valid SQL
	 *
	 * @return void
	 */
	public function test_get_schema_returns_valid_sql(): void {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( Installer::class );
		$method = $reflection->getMethod( 'get_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( null );

		$this->assertIsString( $schema );
		$this->assertStringContainsString( 'CREATE TABLE', $schema );
		$this->assertStringContainsString( 'meowseo_redirects', $schema );
		$this->assertStringContainsString( 'meowseo_404_log', $schema );
		$this->assertStringContainsString( 'meowseo_gsc_queue', $schema );
		$this->assertStringContainsString( 'meowseo_gsc_data', $schema );
		$this->assertStringContainsString( 'meowseo_link_checks', $schema );
	}

	/**
	 * Test that schema includes all required tables
	 *
	 * @return void
	 */
	public function test_schema_includes_all_required_tables(): void {
		$reflection = new \ReflectionClass( Installer::class );
		$method = $reflection->getMethod( 'get_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( null );

		$required_tables = array(
			'meowseo_redirects',
			'meowseo_404_log',
			'meowseo_gsc_queue',
			'meowseo_gsc_data',
			'meowseo_link_checks',
		);

		foreach ( $required_tables as $table ) {
			$this->assertStringContainsString( $table, $schema, "Schema should include {$table} table" );
		}
	}

	/**
	 * Test that redirects table has required indexes
	 *
	 * @return void
	 */
	public function test_redirects_table_has_required_indexes(): void {
		$reflection = new \ReflectionClass( Installer::class );
		$method = $reflection->getMethod( 'get_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( null );

		$this->assertStringContainsString( 'idx_source_url', $schema );
		$this->assertStringContainsString( 'idx_is_regex_status', $schema );
	}

	/**
	 * Test that 404_log table has unique constraint
	 *
	 * @return void
	 */
	public function test_404_log_table_has_unique_constraint(): void {
		$reflection = new \ReflectionClass( Installer::class );
		$method = $reflection->getMethod( 'get_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( null );

		$this->assertStringContainsString( 'UNIQUE KEY idx_url_hash_date', $schema );
	}

	/**
	 * Test that gsc_queue table has status index
	 *
	 * @return void
	 */
	public function test_gsc_queue_table_has_status_index(): void {
		$reflection = new \ReflectionClass( Installer::class );
		$method = $reflection->getMethod( 'get_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( null );

		$this->assertStringContainsString( 'idx_status_retry', $schema );
	}

	/**
	 * Test that gsc_data table has unique constraint
	 *
	 * @return void
	 */
	public function test_gsc_data_table_has_unique_constraint(): void {
		$reflection = new \ReflectionClass( Installer::class );
		$method = $reflection->getMethod( 'get_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( null );

		$this->assertStringContainsString( 'UNIQUE KEY idx_url_hash_date', $schema );
	}

	/**
	 * Test that link_checks table has unique constraint
	 *
	 * @return void
	 */
	public function test_link_checks_table_has_unique_constraint(): void {
		$reflection = new \ReflectionClass( Installer::class );
		$method = $reflection->getMethod( 'get_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( null );

		$this->assertStringContainsString( 'UNIQUE KEY idx_source_target', $schema );
	}
}
