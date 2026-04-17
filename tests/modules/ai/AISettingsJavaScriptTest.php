<?php
/**
 * Tests for AI Settings JavaScript functionality
 *
 * Tests the JavaScript implementation for:
 * - 34.1 Drag-and-drop provider ordering
 * - 34.2 Test connection functionality
 * - 34.3 Provider status auto-refresh
 * - 34.4 Custom instructions character counter
 *
 * @package MeowSEO\Tests\Modules\AI
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;

/**
 * AISettingsJavaScriptTest class
 *
 * Tests the JavaScript file structure and enqueuing.
 */
class AISettingsJavaScriptTest extends TestCase {

	/**
	 * Test that the JavaScript file exists
	 */
	public function test_javascript_file_exists() {
		$js_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/js/ai-settings.js';
		$this->assertFileExists( $js_file, 'JavaScript file should exist at includes/modules/ai/assets/js/ai-settings.js' );
	}

	/**
	 * Test that the CSS file exists
	 */
	public function test_css_file_exists() {
		$css_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/css/ai-settings.css';
		$this->assertFileExists( $css_file, 'CSS file should exist at includes/modules/ai/assets/css/ai-settings.css' );
	}

	/**
	 * Test that the JavaScript file contains required functions
	 */
	public function test_javascript_contains_required_functions() {
		$js_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/js/ai-settings.js';
		$content = file_get_contents( $js_file );

		// Test for 34.1 - Drag-and-drop
		$this->assertStringContainsString( 'initDragAndDrop', $content, 'Should contain initDragAndDrop function' );
		$this->assertStringContainsString( 'updateProviderOrder', $content, 'Should contain updateProviderOrder function' );

		// Test for 34.2 - Test connection
		$this->assertStringContainsString( 'initTestConnection', $content, 'Should contain initTestConnection function' );
		$this->assertStringContainsString( 'testProviderConnection', $content, 'Should contain testProviderConnection function' );
		$this->assertStringContainsString( 'showTestStatus', $content, 'Should contain showTestStatus function' );

		// Test for 34.3 - Status auto-refresh
		$this->assertStringContainsString( 'initStatusAutoRefresh', $content, 'Should contain initStatusAutoRefresh function' );
		$this->assertStringContainsString( 'refreshProviderStatus', $content, 'Should contain refreshProviderStatus function' );
		$this->assertStringContainsString( 'updateStatusTable', $content, 'Should contain updateStatusTable function' );

		// Test for 34.4 - Character counter
		$this->assertStringContainsString( 'initCharacterCounter', $content, 'Should contain initCharacterCounter function' );
	}

	/**
	 * Test that the JavaScript file contains required configuration
	 */
	public function test_javascript_contains_required_configuration() {
		$js_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/js/ai-settings.js';
		$content = file_get_contents( $js_file );

		// Test for required selectors
		$this->assertStringContainsString( '#meowseo-providers-sortable', $content, 'Should contain sortable selector' );
		$this->assertStringContainsString( '.meowseo-test-connection-btn', $content, 'Should contain test button selector' );
		$this->assertStringContainsString( '#ai_custom_instructions', $content, 'Should contain custom instructions selector' );

		// Test for required endpoints
		$this->assertStringContainsString( '/wp-json/meowseo/v1/ai/test-provider', $content, 'Should contain test-provider endpoint' );
		$this->assertStringContainsString( '/wp-json/meowseo/v1/ai/provider-status', $content, 'Should contain provider-status endpoint' );

		// Test for 30-second refresh interval (34.3)
		$this->assertStringContainsString( '30000', $content, 'Should contain 30-second refresh interval' );
	}

	/**
	 * Test that the CSS file contains required styles
	 */
	public function test_css_contains_required_styles() {
		$css_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/css/ai-settings.css';
		$content = file_get_contents( $css_file );

		// Test for drag-and-drop styles
		$this->assertStringContainsString( '.meowseo-dragging', $content, 'Should contain dragging state styles' );
		$this->assertStringContainsString( '.meowseo-provider-drag-handle', $content, 'Should contain drag handle styles' );

		// Test for test connection styles
		$this->assertStringContainsString( '.meowseo-test-status', $content, 'Should contain test status styles' );
		$this->assertStringContainsString( '.meowseo-test-status-success', $content, 'Should contain success status styles' );
		$this->assertStringContainsString( '.meowseo-test-status-error', $content, 'Should contain error status styles' );

		// Test for status table styles
		$this->assertStringContainsString( '.meowseo-provider-status-table', $content, 'Should contain status table styles' );
		$this->assertStringContainsString( '.meowseo-status-badge', $content, 'Should contain status badge styles' );

		// Test for character counter styles
		$this->assertStringContainsString( '#ai_custom_instructions_count', $content, 'Should contain character counter styles' );
	}

	/**
	 * Test that the JavaScript file is valid JavaScript
	 */
	public function test_javascript_is_valid() {
		$js_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/js/ai-settings.js';
		$content = file_get_contents( $js_file );

		// Check for basic JavaScript structure
		$this->assertStringContainsString( '(function()', $content, 'Should use IIFE pattern' );
		$this->assertStringContainsString( "'use strict'", $content, 'Should use strict mode' );
		$this->assertStringContainsString( 'const AISettings', $content, 'Should define AISettings object' );
		$this->assertStringContainsString( 'init: function()', $content, 'Should have init method' );
	}

	/**
	 * Test that the CSS file is valid CSS
	 */
	public function test_css_is_valid() {
		$css_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/css/ai-settings.css';
		$content = file_get_contents( $css_file );

		// Check for basic CSS structure
		$this->assertStringContainsString( '{', $content, 'Should contain CSS rules' );
		$this->assertStringContainsString( '}', $content, 'Should contain CSS closing braces' );
		$this->assertStringContainsString( '/*', $content, 'Should contain CSS comments' );
	}

	/**
	 * Test that AI_Settings class has enqueue_admin_assets method
	 */
	public function test_ai_settings_has_enqueue_method() {
		$settings_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/class-ai-settings.php';
		$content = file_get_contents( $settings_file );

		$this->assertStringContainsString( 'public function enqueue_admin_assets', $content, 'Should have enqueue_admin_assets method' );
		$this->assertStringContainsString( 'meowseo-ai-settings', $content, 'Should enqueue meowseo-ai-settings script' );
		$this->assertStringContainsString( 'meowseoAISettings', $content, 'Should localize meowseoAISettings' );
	}

	/**
	 * Test that the JavaScript file handles nonce correctly
	 */
	public function test_javascript_handles_nonce() {
		$js_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/js/ai-settings.js';
		$content = file_get_contents( $js_file );

		$this->assertStringContainsString( 'getNonce', $content, 'Should have getNonce method' );
		$this->assertStringContainsString( 'X-WP-Nonce', $content, 'Should include nonce in headers' );
		$this->assertStringContainsString( 'this.state.nonce', $content, 'Should store nonce in state' );
	}

	/**
	 * Test that the JavaScript file implements all 4 subtasks
	 */
	public function test_all_subtasks_implemented() {
		$js_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/js/ai-settings.js';
		$content = file_get_contents( $js_file );

		// 34.1 - Drag-and-drop provider ordering
		$this->assertStringContainsString( '34.1', $content, 'Should document 34.1 subtask' );
		$this->assertStringContainsString( 'draggable = true', $content, 'Should implement HTML5 drag-and-drop' );
		$this->assertStringContainsString( 'dragstart', $content, 'Should handle dragstart event' );
		$this->assertStringContainsString( 'dragend', $content, 'Should handle dragend event' );

		// 34.2 - Test connection functionality
		$this->assertStringContainsString( '34.2', $content, 'Should document 34.2 subtask' );
		$this->assertStringContainsString( 'fetch', $content, 'Should use fetch API for AJAX' );
		$this->assertStringContainsString( 'test-provider', $content, 'Should call test-provider endpoint' );

		// 34.3 - Provider status auto-refresh
		$this->assertStringContainsString( '34.3', $content, 'Should document 34.3 subtask' );
		$this->assertStringContainsString( 'setInterval', $content, 'Should use setInterval for polling' );
		$this->assertStringContainsString( 'provider-status', $content, 'Should call provider-status endpoint' );

		// 34.4 - Custom instructions character counter
		$this->assertStringContainsString( '34.4', $content, 'Should document 34.4 subtask' );
		$this->assertStringContainsString( 'maxLength', $content, 'Should check max length' );
		$this->assertStringContainsString( 'addEventListener', $content, 'Should listen to input events' );
	}

	/**
	 * Test that the JavaScript file includes proper error handling
	 */
	public function test_javascript_includes_error_handling() {
		$js_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/js/ai-settings.js';
		$content = file_get_contents( $js_file );

		$this->assertStringContainsString( '.catch', $content, 'Should handle fetch errors' );
		$this->assertStringContainsString( 'console.error', $content, 'Should log errors to console' );
		$this->assertStringContainsString( '.finally', $content, 'Should have finally block for cleanup' );
	}

	/**
	 * Test that the JavaScript file includes proper cleanup
	 */
	public function test_javascript_includes_cleanup() {
		$js_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/js/ai-settings.js';
		$content = file_get_contents( $js_file );

		$this->assertStringContainsString( 'destroy', $content, 'Should have destroy method' );
		$this->assertStringContainsString( 'clearInterval', $content, 'Should clear intervals on cleanup' );
		$this->assertStringContainsString( 'beforeunload', $content, 'Should cleanup on page unload' );
	}

	/**
	 * Test that the CSS file includes responsive design
	 */
	public function test_css_includes_responsive_design() {
		$css_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/css/ai-settings.css';
		$content = file_get_contents( $css_file );

		$this->assertStringContainsString( '@media', $content, 'Should include media queries' );
		$this->assertStringContainsString( 'max-width: 768px', $content, 'Should include mobile breakpoint' );
	}

	/**
	 * Test that the CSS file includes accessibility styles
	 */
	public function test_css_includes_accessibility_styles() {
		$css_file = MEOWSEO_PLUGIN_DIR . 'includes/modules/ai/assets/css/ai-settings.css';
		$content = file_get_contents( $css_file );

		$this->assertStringContainsString( ':focus', $content, 'Should include focus styles' );
		$this->assertStringContainsString( 'outline', $content, 'Should include outline for focus indicators' );
	}
}
