<?php
/**
 * Tests for Import_Admin class.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Import;

use MeowSEO\Modules\Import\Import_Admin;
use MeowSEO\Modules\Import\Import_Manager;
use PHPUnit\Framework\TestCase;

/**
 * Import_Admin test case.
 */
class ImportAdminTest extends TestCase {

	/**
	 * Test Import_Admin can be instantiated.
	 *
	 * @return void
	 */
	public function test_can_instantiate(): void {
		$import_manager_mock = $this->createMock( Import_Manager::class );
		$import_admin = new Import_Admin( $import_manager_mock );

		$this->assertInstanceOf( Import_Admin::class, $import_admin );
	}

	/**
	 * Test boot method does not throw exceptions.
	 *
	 * @return void
	 */
	public function test_boot_does_not_throw(): void {
		$import_manager_mock = $this->createMock( Import_Manager::class );
		$import_admin = new Import_Admin( $import_manager_mock );

		$this->expectNotToPerformAssertions();
		$import_admin->boot();
	}
}
