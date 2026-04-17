<?php
/**
 * AI Module Autoloader Tests
 *
 * Unit tests for verifying the AI module autoloader integration.
 *
 * @package MeowSEO\Tests
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;

/**
 * AI Module Autoloader test case
 *
 * Tests that the autoloader can resolve AI module classes.
 * Requirements: 1.1, 17.1, 18.1, 19.1, 20.1, 21.1
 *
 * @since 1.0.0
 */
class AIModuleAutoloaderTest extends TestCase {

	/**
	 * Test that AI_Module class can be loaded by autoloader.
	 *
	 * @return void
	 */
	public function test_ai_module_class_can_be_loaded(): void {
		$this->assertTrue(
			class_exists( \MeowSEO\Modules\AI\AI_Module::class ),
			'AI_Module class should be loadable by autoloader'
		);
	}

	/**
	 * Test that AI_Module implements Module interface.
	 *
	 * @return void
	 */
	public function test_ai_module_implements_module_interface(): void {
		$this->assertTrue(
			in_array(
				\MeowSEO\Contracts\Module::class,
				class_implements( \MeowSEO\Modules\AI\AI_Module::class ),
				true
			),
			'AI_Module should implement Module interface'
		);
	}

	/**
	 * Test that AI_Module can be instantiated.
	 *
	 * @return void
	 */
	public function test_ai_module_can_be_instantiated(): void {
		$options = new \MeowSEO\Options();
		$ai_module = new \MeowSEO\Modules\AI\AI_Module( $options );
		
		$this->assertInstanceOf(
			\MeowSEO\Modules\AI\AI_Module::class,
			$ai_module,
			'AI_Module should be instantiable'
		);
	}

	/**
	 * Test that AI_Module has required methods.
	 *
	 * @return void
	 */
	public function test_ai_module_has_required_methods(): void {
		$options = new \MeowSEO\Options();
		$ai_module = new \MeowSEO\Modules\AI\AI_Module( $options );
		
		$this->assertTrue(
			method_exists( $ai_module, 'get_id' ),
			'AI_Module should have get_id method'
		);
		
		$this->assertTrue(
			method_exists( $ai_module, 'boot' ),
			'AI_Module should have boot method'
		);
	}

	/**
	 * Test that AI_Module get_id returns correct value.
	 *
	 * @return void
	 */
	public function test_ai_module_get_id_returns_correct_value(): void {
		$options = new \MeowSEO\Options();
		$ai_module = new \MeowSEO\Modules\AI\AI_Module( $options );
		
		$this->assertEquals(
			'ai',
			$ai_module->get_id(),
			'AI_Module get_id should return "ai"'
		);
	}
}
