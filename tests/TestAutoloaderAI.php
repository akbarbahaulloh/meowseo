<?php
/**
 * Test autoloader integration for AI module.
 *
 * @package MeowSEO\Tests
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test that the autoloader can resolve AI module classes.
 */
class TestAutoloaderAI {

	/**
	 * Run the test.
	 *
	 * @return bool True if test passes, false otherwise.
	 */
	public static function run(): bool {
		// Test that AI_Module class can be resolved.
		$class_exists = class_exists( \MeowSEO\Modules\AI\AI_Module::class );
		
		if ( ! $class_exists ) {
			error_log( 'AI_Module class could not be loaded by autoloader' );
			return false;
		}

		// Test that the class implements the Module interface.
		$implements_module = in_array(
			\MeowSEO\Contracts\Module::class,
			class_implements( \MeowSEO\Modules\AI\AI_Module::class ),
			true
		);

		if ( ! $implements_module ) {
			error_log( 'AI_Module does not implement Module interface' );
			return false;
		}

		error_log( 'AI_Module autoloader integration test passed' );
		return true;
	}
}
