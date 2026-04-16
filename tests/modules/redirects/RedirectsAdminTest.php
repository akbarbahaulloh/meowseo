<?php
/**
 * Redirects Admin Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Redirects;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Redirects\Redirects_Admin;
use MeowSEO\Options;

/**
 * Redirects Admin test case
 */
class RedirectsAdminTest extends TestCase {

	/**
	 * Admin instance
	 *
	 * @var Redirects_Admin
	 */
	private Redirects_Admin $admin;

	/**
	 * Options mock
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Set up test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( Options::class );
		$this->admin = new Redirects_Admin( $this->options );
	}

	/**
	 * Test admin boots without errors
	 */
	public function test_boot(): void {
		// Boot should not throw any exceptions
		$this->expectNotToPerformAssertions();
		$this->admin->boot();
	}

	/**
	 * Test register_menu adds submenu page
	 */
	public function test_register_menu(): void {
		// This test verifies the method exists and can be called
		$this->expectNotToPerformAssertions();
		$this->admin->register_menu();
	}

	/**
	 * Test render_page requires manage_options capability
	 *
	 * This is a basic test to ensure the method exists.
	 * Full integration testing would require WordPress test framework.
	 */
	public function test_render_page_exists(): void {
		$this->assertTrue(
			method_exists( $this->admin, 'render_page' ),
			'render_page method should exist'
		);
	}

	/**
	 * Test CSV import handler exists
	 */
	public function test_csv_import_handler_exists(): void {
		$this->assertTrue(
			method_exists( $this->admin, 'handle_csv_import' ),
			'handle_csv_import method should exist'
		);
	}

	/**
	 * Test CSV export handler exists
	 */
	public function test_csv_export_handler_exists(): void {
		$this->assertTrue(
			method_exists( $this->admin, 'handle_csv_export' ),
			'handle_csv_export method should exist'
		);
	}
}
