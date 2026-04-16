<?php
/**
 * Tests for Monitor 404 Module Core Functionality
 *
 * Tests the implementation of Requirements 7.1-7.6 and 8.1-8.6
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Monitor_404;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Monitor_404\Monitor_404;
use MeowSEO\Options;
use ReflectionClass;
use ReflectionMethod;

/**
 * Monitor 404 Core Functionality test case
 */
class Monitor404CoreFunctionalityTest extends TestCase {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Monitor_404 instance.
	 *
	 * @var Monitor_404
	 */
	private Monitor_404 $module;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = new Options();
		$this->module  = new Monitor_404( $this->options );
	}

	/**
	 * Test that ASSET_EXTENSIONS constant is defined correctly.
	 *
	 * Requirement 7.3: Skip static assets.
	 */
	public function test_asset_extensions_constant(): void {
		$reflection = new ReflectionClass( Monitor_404::class );
		$constant   = $reflection->getConstant( 'ASSET_EXTENSIONS' );

		$this->assertIsArray( $constant );
		$this->assertContains( 'jpg', $constant );
		$this->assertContains( 'jpeg', $constant );
		$this->assertContains( 'png', $constant );
		$this->assertContains( 'gif', $constant );
		$this->assertContains( 'css', $constant );
		$this->assertContains( 'js', $constant );
		$this->assertContains( 'ico', $constant );
		$this->assertContains( 'woff', $constant );
		$this->assertContains( 'woff2', $constant );
		$this->assertContains( 'svg', $constant );
		$this->assertContains( 'pdf', $constant );
	}

	/**
	 * Test is_static_asset method.
	 *
	 * Requirement 7.3: Skip requests where the URL path has a file extension
	 * indicating a static asset.
	 */
	public function test_is_static_asset(): void {
		$reflection = new ReflectionClass( Monitor_404::class );
		$method     = $reflection->getMethod( 'is_static_asset' );
		$method->setAccessible( true );

		// Test static assets (should return true)
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/image.jpg' ) );
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/style.css' ) );
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/script.js' ) );
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/icon.ico' ) );
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/font.woff2' ) );
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/doc.pdf' ) );

		// Test non-static assets (should return false)
		$this->assertFalse( $method->invoke( $this->module, 'http://example.com/page' ) );
		$this->assertFalse( $method->invoke( $this->module, 'http://example.com/page.html' ) );
		$this->assertFalse( $method->invoke( $this->module, 'http://example.com/page.php' ) );
		$this->assertFalse( $method->invoke( $this->module, 'http://example.com/' ) );
	}

	/**
	 * Test is_ignored_url method with exact match.
	 *
	 * Requirement 7.4: Skip URLs on ignore list.
	 */
	public function test_is_ignored_url_exact_match(): void {
		// Set up ignore list
		$this->options->set( 'monitor_404_ignore_list', array(
			'http://example.com/admin',
			'http://example.com/wp-admin',
		) );

		$reflection = new ReflectionClass( Monitor_404::class );
		$method     = $reflection->getMethod( 'is_ignored_url' );
		$method->setAccessible( true );

		// Test exact matches (should return true)
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/admin' ) );
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/wp-admin' ) );

		// Test non-matches (should return false)
		$this->assertFalse( $method->invoke( $this->module, 'http://example.com/page' ) );
		$this->assertFalse( $method->invoke( $this->module, 'http://example.com/other' ) );
	}

	/**
	 * Test is_ignored_url method with wildcard patterns.
	 *
	 * Requirement 7.4: Skip URLs on ignore list with pattern matching.
	 */
	public function test_is_ignored_url_wildcard_pattern(): void {
		// Set up ignore list with wildcards
		$this->options->set( 'monitor_404_ignore_list', array(
			'http://example.com/admin/*',
			'*/wp-json/*',
		) );

		$reflection = new ReflectionClass( Monitor_404::class );
		$method     = $reflection->getMethod( 'is_ignored_url' );
		$method->setAccessible( true );

		// Test wildcard matches (should return true)
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/admin/settings' ) );
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/admin/users' ) );
		$this->assertTrue( $method->invoke( $this->module, 'http://example.com/wp-json/v1/posts' ) );

		// Test non-matches (should return false)
		$this->assertFalse( $method->invoke( $this->module, 'http://example.com/page' ) );
	}

	/**
	 * Test bucket key format.
	 *
	 * Requirement 7.5: Use bucket key format 404_YYYYMMDD_HHmm.
	 */
	public function test_bucket_key_format(): void {
		$reflection = new ReflectionClass( Monitor_404::class );
		$method     = $reflection->getMethod( 'get_bucket_key' );
		$method->setAccessible( true );

		$bucket_key = $method->invoke( $this->module );

		// Verify format: 404_YYYYMMDD_HHmm
		$this->assertMatchesRegularExpression( '/^404_\d{8}_\d{4}$/', $bucket_key );

		// Verify it matches current time
		$expected = '404_' . gmdate( 'Ymd_Hi' );
		$this->assertEquals( $expected, $bucket_key );
	}

	/**
	 * Test get_recent_bucket_keys method.
	 *
	 * Requirement 8.2: Retrieve buckets for -1 and -2 minutes.
	 */
	public function test_get_recent_bucket_keys(): void {
		$reflection = new ReflectionClass( Monitor_404::class );
		$method     = $reflection->getMethod( 'get_recent_bucket_keys' );
		$method->setAccessible( true );

		$keys = $method->invoke( $this->module, 2 );

		// Should return 2 keys
		$this->assertCount( 2, $keys );

		// Verify format of each key
		foreach ( $keys as $key ) {
			$this->assertMatchesRegularExpression( '/^404_\d{8}_\d{4}$/', $key );
		}

		// Verify keys are for -1 and -2 minutes
		$expected_keys = array(
			'404_' . gmdate( 'Ymd_Hi', time() - 60 ),
			'404_' . gmdate( 'Ymd_Hi', time() - 120 ),
		);

		$this->assertEquals( $expected_keys, $keys );
	}

	/**
	 * Test aggregate_hits method.
	 *
	 * Requirement 8.3: Aggregate URLs by counting occurrences.
	 */
	public function test_aggregate_hits(): void {
		$reflection = new ReflectionClass( Monitor_404::class );
		$method     = $reflection->getMethod( 'aggregate_hits' );
		$method->setAccessible( true );

		$timestamp = time();
		$hits      = array(
			array(
				'url'        => 'http://example.com/page1',
				'referrer'   => '',
				'user_agent' => 'Agent 1',
				'timestamp'  => $timestamp,
			),
			array(
				'url'        => 'http://example.com/page1',
				'referrer'   => 'http://example.com/ref',
				'user_agent' => 'Agent 2',
				'timestamp'  => $timestamp + 10,
			),
			array(
				'url'        => 'http://example.com/page1',
				'referrer'   => '',
				'user_agent' => 'Agent 3',
				'timestamp'  => $timestamp + 20,
			),
			array(
				'url'        => 'http://example.com/page2',
				'referrer'   => '',
				'user_agent' => 'Agent 4',
				'timestamp'  => $timestamp,
			),
		);

		$aggregated = $method->invoke( $this->module, $hits );

		// Should have 2 unique URLs
		$this->assertCount( 2, $aggregated );

		// First URL should have 3 hits
		$this->assertEquals( 'http://example.com/page1', $aggregated[0]['url'] );
		$this->assertEquals( 3, $aggregated[0]['hit_count'] );

		// Second URL should have 1 hit
		$this->assertEquals( 'http://example.com/page2', $aggregated[1]['url'] );
		$this->assertEquals( 1, $aggregated[1]['hit_count'] );

		// Verify date fields
		$this->assertEquals( gmdate( 'Y-m-d', $timestamp ), $aggregated[0]['first_seen'] );
		$this->assertEquals( gmdate( 'Y-m-d', $timestamp + 20 ), $aggregated[0]['last_seen'] );
	}

	/**
	 * Test module implements Module interface correctly.
	 */
	public function test_module_interface_implementation(): void {
		$this->assertInstanceOf( \MeowSEO\Contracts\Module::class, $this->module );
		$this->assertEquals( 'monitor_404', $this->module->get_id() );
	}

	/**
	 * Test cron interval registration.
	 *
	 * Requirement 8.1: Schedule cron event to run every 60 seconds.
	 */
	public function test_cron_interval_registration(): void {
		$schedules = array();
		$result    = $this->module->register_cron_interval( $schedules );

		$this->assertArrayHasKey( 'meowseo_60s', $result );
		$this->assertEquals( 60, $result['meowseo_60s']['interval'] );
	}
}
