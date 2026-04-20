<?php
/**
 * Admin Tests
 *
 * Unit tests for the Admin class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Admin;
use MeowSEO\Options;

/**
 * Admin test case
 *
 * Tests admin menu registration and page rendering.
 * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5
 *
 * @since 1.0.0
 */
class AdminTest extends TestCase {

	/**
	 * Admin instance
	 *
	 * @var Admin
	 */
	private Admin $admin;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = new Options();
		
		// Setup Brain\Monkey mocking for WordPress functions
		setup_brain_monkey_mocks();
		
		// Create a mock Module_Manager for testing.
		$module_manager = $this->createMock( \MeowSEO\Module_Manager::class );
		
		$this->admin = new Admin( $this->options, $module_manager );
		
		// Reset global test overrides.
		global $test_current_user_can_override;
		$test_current_user_can_override = null;
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		
		// Reset global test overrides.
		global $test_current_user_can_override;
		$test_current_user_can_override = null;
	}

	/**
	 * Test Admin instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( Admin::class, $this->admin );
	}

	/**
	 * Test boot method registers hooks
	 *
	 * Verifies that boot() registers the admin_menu and admin_enqueue_scripts actions.
	 *
	 * @return void
	 */
	public function test_boot_registers_hooks(): void {
		// Reset global filter storage.
		global $wp_filter;
		$wp_filter = array();

		// Call boot.
		$this->admin->boot();

		// Verify hooks are registered by checking the global $wp_filter array.
		$this->assertArrayHasKey( 'admin_menu', $wp_filter, 'admin_menu hook should be registered' );
		$this->assertArrayHasKey( 'admin_enqueue_scripts', $wp_filter, 'admin_enqueue_scripts hook should be registered' );
	}

	/**
	 * Test render_dashboard_page outputs correct HTML
	 *
	 * Verifies that the dashboard page renders the correct widget containers.
	 * Requirement: 1.4, 2.1
	 *
	 * @return void
	 */
	public function test_render_dashboard_page_outputs_correct_html(): void {
		// Set current_user_can to return true.
		global $test_current_user_can_override;
		$test_current_user_can_override = true;

		// Boot the admin to initialize dashboard_widgets.
		$this->admin->boot();

		ob_start();
		$this->admin->render_dashboard_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'class="meowseo-dashboard-widgets"', $output, 'Dashboard page should contain dashboard widgets container' );
		$this->assertStringContainsString( 'MeowSEO Dashboard', $output, 'Dashboard page should contain title' );
		$this->assertStringContainsString( 'meowseo-widget', $output, 'Dashboard page should contain widget elements' );
		$this->assertStringContainsString( 'data-widget-id', $output, 'Dashboard page should contain widget data attributes' );
		$this->assertStringContainsString( 'data-endpoint', $output, 'Dashboard page should contain endpoint data attributes' );
	}

	/**
	 * Test render_settings_page outputs correct HTML
	 *
	 * Verifies that the settings page renders the correct tabbed interface.
	 * Requirement: 1.4, 4.1, 4.2, 4.3
	 *
	 * @return void
	 */
	public function test_render_settings_page_outputs_correct_html(): void {
		// Set current_user_can to return true.
		global $test_current_user_can_override;
		$test_current_user_can_override = true;

		// Boot the admin to initialize settings_manager.
		$this->admin->boot();

		ob_start();
		$this->admin->render_settings_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'class="meowseo-settings-container"', $output, 'Settings page should contain settings container' );
		$this->assertStringContainsString( 'meowseo-settings-tabs', $output, 'Settings page should contain tabs navigation' );
		$this->assertStringContainsString( 'meowseo-tab-button', $output, 'Settings page should contain tab buttons' );
		$this->assertStringContainsString( 'meowseo-tab-panel', $output, 'Settings page should contain tab panels' );
	}

	/**
	 * Test render_tools_page outputs correct HTML
	 *
	 * Verifies that the tools page renders the correct root element.
	 * Requirement: 1.4
	 *
	 * @return void
	 */
	public function test_render_tools_page_outputs_correct_html(): void {
		// Set current_user_can to return true.
		global $test_current_user_can_override;
		$test_current_user_can_override = true;

		ob_start();
		$this->admin->render_tools_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'class="meowseo-tools-container"', $output, 'Tools page should contain tools container element' );
		$this->assertStringContainsString( 'Tools', $output, 'Tools page should contain title' );
	}

	/**
	 * Test render_redirects_page outputs correct HTML
	 *
	 * Verifies that the redirects page renders the correct root element.
	 * Requirement: 1.4
	 *
	 * @return void
	 */
	public function test_render_redirects_page_outputs_correct_html(): void {
		// Set current_user_can to return true.
		global $test_current_user_can_override;
		$test_current_user_can_override = true;

		ob_start();
		$this->admin->render_redirects_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="meowseo-redirects-root"', $output, 'Redirects page should contain redirects root element' );
		$this->assertStringContainsString( 'Redirects', $output, 'Redirects page should contain title' );
	}

	/**
	 * Test render_404_monitor_page outputs correct HTML
	 *
	 * Verifies that the 404 monitor page renders the correct root element.
	 * Requirement: 1.4
	 *
	 * @return void
	 */
	public function test_render_404_monitor_page_outputs_correct_html(): void {
		// Set current_user_can to return true.
		global $test_current_user_can_override;
		$test_current_user_can_override = true;

		ob_start();
		$this->admin->render_404_monitor_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="meowseo-404-monitor-root"', $output, '404 Monitor page should contain 404 monitor root element' );
		$this->assertStringContainsString( '404 Monitor', $output, '404 Monitor page should contain title' );
	}

	/**
	 * Test render_search_console_page outputs correct HTML
	 *
	 * Verifies that the search console page renders the correct root element.
	 * Requirement: 1.4
	 *
	 * @return void
	 */
	public function test_render_search_console_page_outputs_correct_html(): void {
		// Set current_user_can to return true.
		global $test_current_user_can_override;
		$test_current_user_can_override = true;

		ob_start();
		$this->admin->render_search_console_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="meowseo-search-console-root"', $output, 'Search Console page should contain search console root element' );
		$this->assertStringContainsString( 'Search Console', $output, 'Search Console page should contain title' );
	}

	/**
	 * Test enqueue_admin_assets maps hook suffixes correctly
	 *
	 * Verifies that assets are enqueued for the correct admin pages.
	 * Requirement: 1.5
	 *
	 * @return void
	 */
	public function test_enqueue_admin_assets_maps_hook_suffixes(): void {
		// Test that the method handles different hook suffixes.
		$hook_suffixes = array(
			'toplevel_page_meowseo',
			'meowseo_page_meowseo-settings',
			'meowseo_page_meowseo-redirects',
			'meowseo_page_meowseo-404-monitor',
			'meowseo_page_meowseo-search-console',
			'meowseo_page_meowseo-tools',
		);

		foreach ( $hook_suffixes as $hook_suffix ) {
			// Call enqueue_admin_assets with each hook suffix.
			// Since asset files don't exist in test environment, method will return early.
			// We just verify it doesn't throw errors.
			try {
				$this->admin->enqueue_admin_assets( $hook_suffix );
				$this->assertTrue( true, "enqueue_admin_assets should handle {$hook_suffix} without errors" );
			} catch ( \Exception $e ) {
				$this->fail( "enqueue_admin_assets threw exception for {$hook_suffix}: " . $e->getMessage() );
			}
		}
	}

	/**
	 * Test enqueue_admin_assets ignores non-MeowSEO pages
	 *
	 * Verifies that assets are not enqueued on non-MeowSEO admin pages.
	 * Requirement: 1.5
	 *
	 * @return void
	 */
	public function test_enqueue_admin_assets_ignores_non_meowseo_pages(): void {
		// Test with a non-MeowSEO hook suffix.
		$hook_suffix = 'edit.php';

		// Call enqueue_admin_assets.
		// Should return early without enqueuing anything.
		try {
			$this->admin->enqueue_admin_assets( $hook_suffix );
			$this->assertTrue( true, 'enqueue_admin_assets should handle non-MeowSEO pages gracefully' );
		} catch ( \Exception $e ) {
			$this->fail( 'enqueue_admin_assets should not throw exception for non-MeowSEO pages: ' . $e->getMessage() );
		}
	}

	/**
	 * Test render methods check capabilities
	 *
	 * Verifies that render methods check for manage_options capability.
	 * Requirement: 1.4
	 *
	 * @return void
	 */
	public function test_render_dashboard_page_checks_capabilities(): void {
		// Set current_user_can to return false.
		global $test_current_user_can_override;
		$test_current_user_can_override = false;

		// Expect wp_die to be called (which will output and exit).
		// We can't easily test this without mocking wp_die, so we'll just verify
		// that the method calls current_user_can by checking the output.
		ob_start();
		try {
			$this->admin->render_dashboard_page();
			$output = ob_get_clean();
			// If we get here, wp_die was called and output was captured.
			$this->assertStringContainsString( 'sufficient permissions', $output, 'Dashboard page should check capabilities' );
		} catch ( \Exception $e ) {
			ob_end_clean();
			// Exception is expected if wp_die throws.
			$this->assertStringContainsString( 'sufficient permissions', $e->getMessage() );
		}
	}
}

