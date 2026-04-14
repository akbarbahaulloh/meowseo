<?php
/**
 * Base Test Case for Meta Property Tests
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use WP_UnitTestCase;
use Eris\TestTrait;

/**
 * Base class for Meta property tests that require WordPress test framework
 */
abstract class MetaPropertyTestCase extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Check if factory is available (requires WordPress test framework with factory support).
		if ( ! isset( $this->factory ) || ! is_object( $this->factory ) ) {
			$this->markTestSkipped( 'This test requires WordPress test framework with factory support' );
		}
	}
}
