<?php
/**
 * Tests for Import module.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Import;

use MeowSEO\Modules\Import\Import;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Import module test case.
 */
class ImportTest extends TestCase {

	/**
	 * Test module ID.
	 *
	 * @return void
	 */
	public function test_get_id(): void {
		$options = $this->createMock( Options::class );
		$import = new Import( $options );

		$this->assertSame( 'import', $import->get_id() );
	}

	/**
	 * Test module boots without errors.
	 *
	 * @return void
	 */
	public function test_boot(): void {
		$options = $this->createMock( Options::class );
		$import = new Import( $options );

		// Boot should not throw any exceptions.
		$this->expectNotToPerformAssertions();
		$import->boot();
	}

	/**
	 * Test get_import_manager returns Import_Manager instance.
	 *
	 * @return void
	 */
	public function test_get_import_manager(): void {
		$options = $this->createMock( Options::class );
		$import = new Import( $options );

		$import_manager = $import->get_import_manager();

		$this->assertInstanceOf( \MeowSEO\Modules\Import\Import_Manager::class, $import_manager );
	}
}
