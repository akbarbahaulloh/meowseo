<?php
/**
 * Bug Condition Exploration Test - Asset Loading Failures
 *
 * This test encodes the EXPECTED BEHAVIOR (correct behavior after fix).
 * It MUST FAIL on unfixed code to prove the bugs exist.
 *
 * **Validates: Requirements 2.1, 2.2, 2.3, 2.4**
 *
 * Bug Conditions Being Tested:
 * 1. Loading Gutenberg editor with missing build/gutenberg.asset.php causes fatal error
 * 2. Missing build/gutenberg.js prevents sidebar registration
 * 3. System references build/index.css instead of build/gutenberg.css
 * 4. No file existence checks occur before include/enqueue operations
 *
 * Expected Behavior Properties (what SHOULD happen after fix):
 * - File existence checks SHOULD occur before include/enqueue operations
 * - Error logging SHOULD happen when files are missing
 * - Fallback mechanisms SHOULD be used when asset files are unavailable
 * - Admin notices SHOULD be provided for debugging
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use MeowSEO\Modules\Meta\Gutenberg_Assets;
use WP_UnitTestCase;

/**
 * Property-Based Test for Asset Loading Bug Conditions
 *
 * This test runs BEFORE the fix is implemented to surface counterexamples.
 */
class BugfixAssetLoadingPropertyTest extends WP_UnitTestCase {

	/**
	 * Gutenberg Assets instance
	 *
	 * @var Gutenberg_Assets
	 */
	private $gutenberg_assets;

	/**
	 * Original build directory path
	 *
	 * @var string
	 */
	private $build_dir;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->gutenberg_assets = new Gutenberg_Assets();
		$this->build_dir = dirname( __DIR__, 2 ) . '/build/';
	}

	/**
	 * Test Property 1: File existence check for gutenberg.asset.php
	 *
	 * EXPECTED BEHAVIOR: System should check if build/gutenberg.asset.php exists
	 * before attempting to include it, and use fallback dependencies if missing.
	 *
	 * CURRENT BEHAVIOR (UNFIXED): System attempts to include without checking,
	 * causing fatal error when file is missing.
	 *
	 * This test will FAIL on unfixed code because:
	 * - The code at line 40 does: include plugin_dir_path(...) . 'build/gutenberg.asset.php'
	 * - No file_exists() check before the include
	 * - No try-catch or error handling
	 */
	public function test_asset_file_existence_check_before_include() {
		// Backup the actual file if it exists
		$asset_file_path = $this->build_dir . 'gutenberg.asset.php';
		$backup_path = $this->build_dir . 'gutenberg.asset.php.backup';
		$file_existed = false;

		if ( file_exists( $asset_file_path ) ) {
			rename( $asset_file_path, $backup_path );
			$file_existed = true;
		}

		// Capture any errors or warnings
		$error_triggered = false;
		set_error_handler(
			function ( $errno, $errstr ) use ( &$error_triggered ) {
				$error_triggered = true;
				return true; // Suppress the error
			}
		);

		// Attempt to enqueue assets with missing file
		ob_start();
		try {
			$this->gutenberg_assets->enqueue_editor_assets();
			$output = ob_get_clean();
		} catch ( \Throwable $e ) {
			$output = ob_get_clean();
			$error_triggered = true;
		}
		restore_error_handler();

		// Restore the file if it existed
		if ( $file_existed && file_exists( $backup_path ) ) {
			rename( $backup_path, $asset_file_path );
		}

		// EXPECTED BEHAVIOR: No fatal error should occur
		// The system should handle missing file gracefully
		$this->assertFalse(
			$error_triggered,
			'EXPECTED BEHAVIOR: System should check file existence before include and handle missing file gracefully. ' .
			'CURRENT BEHAVIOR: Fatal error occurs when build/gutenberg.asset.php is missing (no file existence check at line 40).'
		);
	}

	/**
	 * Test Property 2: Correct CSS file path reference
	 *
	 * EXPECTED BEHAVIOR: System should reference build/gutenberg.css (which exists)
	 * not build/index.css (which doesn't exist).
	 *
	 * CURRENT BEHAVIOR (UNFIXED): System references build/index.css at line 54,
	 * which is the wrong path.
	 *
	 * This test will FAIL on unfixed code because:
	 * - Line 54 references 'build/index.css'
	 * - Should reference 'build/gutenberg.css'
	 */
	public function test_correct_css_file_path_reference() {
		// Read the source file to check what CSS path is referenced
		$source_file = dirname( __DIR__, 2 ) . '/includes/modules/meta/class-gutenberg-assets.php';
		$source_content = file_get_contents( $source_file );

		// Check if the file references the correct CSS path
		$references_correct_path = strpos( $source_content, "build/gutenberg.css" ) !== false;
		$references_wrong_path = strpos( $source_content, "build/index.css" ) !== false;

		// EXPECTED BEHAVIOR: Should reference build/gutenberg.css
		$this->assertTrue(
			$references_correct_path && ! $references_wrong_path,
			'EXPECTED BEHAVIOR: System should reference build/gutenberg.css (the actual file that exists). ' .
			'CURRENT BEHAVIOR: System references build/index.css at line 54, which is the wrong path.'
		);
	}

	/**
	 * Test Property 3: File existence check before enqueue operations
	 *
	 * EXPECTED BEHAVIOR: System should check if build/gutenberg.js exists
	 * before attempting to enqueue it.
	 *
	 * CURRENT BEHAVIOR (UNFIXED): System enqueues without checking if file exists.
	 *
	 * This test will FAIL on unfixed code because:
	 * - No file_exists() check before wp_enqueue_script() call
	 * - No error handling for missing JavaScript bundle
	 */
	public function test_js_file_existence_check_before_enqueue() {
		// Read the source file to check for file existence checks
		$source_file = dirname( __DIR__, 2 ) . '/includes/modules/meta/class-gutenberg-assets.php';
		$source_content = file_get_contents( $source_file );

		// Check if there are file existence checks in the enqueue_editor_assets method
		// Expected pattern: file_exists() check before wp_enqueue_script()
		// Allow for both literal strings and variables
		$has_file_exists_check = preg_match(
			'/file_exists\s*\([^)]*(?:gutenberg\.js|js_file_path)[^)]*\)/i',
			$source_content
		);

		// EXPECTED BEHAVIOR: Should have file_exists() check before enqueue
		$this->assertTrue(
			(bool) $has_file_exists_check,
			'EXPECTED BEHAVIOR: System should check if build/gutenberg.js exists before enqueueing. ' .
			'CURRENT BEHAVIOR: No file existence check before wp_enqueue_script() call (lines 45-50).'
		);
	}

	/**
	 * Test Property 4: Error logging when files are missing
	 *
	 * EXPECTED BEHAVIOR: System should log errors with file paths when
	 * asset files are missing.
	 *
	 * CURRENT BEHAVIOR (UNFIXED): No error logging mechanism exists.
	 *
	 * This test will FAIL on unfixed code because:
	 * - No error logging helper method exists
	 * - No calls to error_log() or similar functions
	 */
	public function test_error_logging_for_missing_files() {
		// Read the source file to check for error logging
		$source_file = dirname( __DIR__, 2 ) . '/includes/modules/meta/class-gutenberg-assets.php';
		$source_content = file_get_contents( $source_file );

		// Check if there's an error logging method or error_log() calls
		$has_error_logging = preg_match(
			'/log_asset_error|error_log|trigger_error/i',
			$source_content
		);

		// EXPECTED BEHAVIOR: Should have error logging mechanism
		$this->assertTrue(
			(bool) $has_error_logging,
			'EXPECTED BEHAVIOR: System should log errors with file paths when asset files are missing. ' .
			'CURRENT BEHAVIOR: No error logging mechanism exists in class-gutenberg-assets.php.'
		);
	}

	/**
	 * Test Property 5: Admin notices for debugging
	 *
	 * EXPECTED BEHAVIOR: System should provide admin notices when
	 * asset files are missing to aid debugging.
	 *
	 * CURRENT BEHAVIOR (UNFIXED): No admin notice mechanism exists.
	 *
	 * This test will FAIL on unfixed code because:
	 * - No admin notice generation
	 * - No transient storage for notices
	 */
	public function test_admin_notices_for_missing_files() {
		// Read the source file to check for admin notice mechanism
		$source_file = dirname( __DIR__, 2 ) . '/includes/modules/meta/class-gutenberg-assets.php';
		$source_content = file_get_contents( $source_file );

		// Check if there's admin notice mechanism (transient, add_action for admin_notices, etc.)
		$has_admin_notice = preg_match(
			'/set_transient|add_action.*admin_notices|admin_notice/i',
			$source_content
		);

		// EXPECTED BEHAVIOR: Should have admin notice mechanism
		$this->assertTrue(
			(bool) $has_admin_notice,
			'EXPECTED BEHAVIOR: System should provide admin notices for debugging when asset files are missing. ' .
			'CURRENT BEHAVIOR: No admin notice mechanism exists in class-gutenberg-assets.php.'
		);
	}

	/**
	 * Test Property 6: Fallback dependencies when asset file is missing
	 *
	 * EXPECTED BEHAVIOR: System should use fallback dependencies array
	 * when build/gutenberg.asset.php is missing.
	 *
	 * CURRENT BEHAVIOR (UNFIXED): No fallback mechanism exists.
	 *
	 * This test will FAIL on unfixed code because:
	 * - No fallback dependencies defined
	 * - No conditional logic to use fallback when file is missing
	 */
	public function test_fallback_dependencies_when_asset_file_missing() {
		// Read the source file to check for fallback dependencies
		$source_file = dirname( __DIR__, 2 ) . '/includes/modules/meta/class-gutenberg-assets.php';
		$source_content = file_get_contents( $source_file );

		// Check if there's a fallback dependencies array defined
		$has_fallback = preg_match(
			'/fallback.*dependencies|default.*dependencies/i',
			$source_content
		);

		// EXPECTED BEHAVIOR: Should have fallback dependencies mechanism
		$this->assertTrue(
			(bool) $has_fallback,
			'EXPECTED BEHAVIOR: System should use fallback dependencies when build/gutenberg.asset.php is missing. ' .
			'CURRENT BEHAVIOR: No fallback dependencies mechanism exists (line 40 directly includes without fallback).'
		);
	}
}
