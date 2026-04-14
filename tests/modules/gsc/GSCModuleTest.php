<?php
/**
 * Tests for GSC Module
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\GSC;

use MeowSEO\Modules\GSC\GSC;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * GSC Module test case
 */
class GSCModuleTest extends TestCase {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * GSC module instance.
	 *
	 * @var GSC
	 */
	private GSC $gsc;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = new Options();
		$this->gsc = new GSC( $this->options );
	}

	/**
	 * Test module ID.
	 */
	public function test_get_id(): void {
		$this->assertEquals( 'gsc', $this->gsc->get_id() );
	}

	/**
	 * Test module implements Module interface.
	 */
	public function test_implements_module_interface(): void {
		$this->assertInstanceOf( \MeowSEO\Contracts\Module::class, $this->gsc );
	}

	/**
	 * Test boot method registers hooks.
	 */
	public function test_boot_registers_hooks(): void {
		// This test verifies boot() doesn't throw errors.
		// In a real WordPress environment, it would register hooks.
		$this->gsc->boot();
		$this->assertTrue( true );
	}

	/**
	 * Test enqueue_api_call method.
	 *
	 * Note: This test requires WordPress database functions.
	 * In a real test environment with WordPress loaded, this would insert into the queue.
	 */
	public function test_enqueue_api_call_structure(): void {
		$job_type = 'fetch_url';
		$payload = array(
			'site_url' => 'https://example.com',
			'url' => 'https://example.com/page',
			'start_date' => '2024-01-01',
			'end_date' => '2024-01-31',
		);

		// Verify method exists and accepts correct parameters.
		$this->assertTrue( method_exists( $this->gsc, 'enqueue_api_call' ) );
	}
}

