<?php
/**
 * Settings_Manager Archive Robots UI Tests
 *
 * Integration tests for the Archive Robots settings UI.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Admin;

use PHPUnit\Framework\TestCase;
use MeowSEO\Admin\Settings_Manager;
use MeowSEO\Options;

/**
 * Settings_Manager Archive Robots UI test case
 *
 * Tests the rendering of the Archive Robots settings section.
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
 *
 * @since 1.0.0
 */
class SettingsManagerArchiveRobotsUITest extends TestCase {

	/**
	 * Settings_Manager instance
	 *
	 * @var Settings_Manager
	 */
	private Settings_Manager $settings_manager;

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

		// Create a mock Module_Manager for testing.
		$module_manager = $this->createMock( \MeowSEO\Module_Manager::class );

		$this->settings_manager = new Settings_Manager( $this->options, $module_manager );
	}

	/**
	 * Test render_advanced_tab outputs Archive Robots section
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_render_advanced_tab_outputs_archive_robots_section(): void {
		ob_start();
		$this->settings_manager->render_advanced_tab();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Archive Robots', $output );
		$this->assertStringContainsString( 'Configure robots meta tags for archive pages', $output );
	}

	/**
	 * Test render_advanced_tab outputs all archive types
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6
	 *
	 * @return void
	 */
	public function test_render_advanced_tab_outputs_all_archive_types(): void {
		ob_start();
		$this->settings_manager->render_advanced_tab();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Author Archives', $output );
		$this->assertStringContainsString( 'Date Archives', $output );
		$this->assertStringContainsString( 'Category Archives', $output );
		$this->assertStringContainsString( 'Tag Archives', $output );
		$this->assertStringContainsString( 'Search Results', $output );
		$this->assertStringContainsString( 'Media Attachments', $output );
	}

	/**
	 * Test render_advanced_tab outputs noindex and nofollow checkboxes
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_render_advanced_tab_outputs_noindex_and_nofollow_checkboxes(): void {
		ob_start();
		$this->settings_manager->render_advanced_tab();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'robots_author_archive[noindex]', $output );
		$this->assertStringContainsString( 'robots_author_archive[nofollow]', $output );
		$this->assertStringContainsString( 'robots_date_archive[noindex]', $output );
		$this->assertStringContainsString( 'robots_date_archive[nofollow]', $output );
		$this->assertStringContainsString( 'robots_category_archive[noindex]', $output );
		$this->assertStringContainsString( 'robots_category_archive[nofollow]', $output );
		$this->assertStringContainsString( 'robots_tag_archive[noindex]', $output );
		$this->assertStringContainsString( 'robots_tag_archive[nofollow]', $output );
		$this->assertStringContainsString( 'robots_search_results[noindex]', $output );
		$this->assertStringContainsString( 'robots_search_results[nofollow]', $output );
		$this->assertStringContainsString( 'robots_attachment[noindex]', $output );
		$this->assertStringContainsString( 'robots_attachment[nofollow]', $output );
	}

	/**
	 * Test render_advanced_tab outputs help text
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_render_advanced_tab_outputs_help_text(): void {
		ob_start();
		$this->settings_manager->render_advanced_tab();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'global defaults', $output );
		$this->assertStringContainsString( 'overridden on individual taxonomy terms', $output );
	}

	/**
	 * Test render_advanced_tab respects saved settings
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_render_advanced_tab_respects_saved_settings(): void {
		// Set some archive robots settings.
		$this->options->set( 'robots_author_archive', array(
			'noindex'  => true,
			'nofollow' => false,
		) );
		$this->options->set( 'robots_date_archive', array(
			'noindex'  => true,
			'nofollow' => true,
		) );

		ob_start();
		$this->settings_manager->render_advanced_tab();
		$output = ob_get_clean();

		// Check that the checkboxes are checked based on saved settings.
		$this->assertMatchesRegularExpression(
			'/name="robots_author_archive\[noindex\]"[^>]*checked/',
			$output,
			'Author archive noindex should be checked'
		);
		$this->assertMatchesRegularExpression(
			'/name="robots_date_archive\[noindex\]"[^>]*checked/',
			$output,
			'Date archive noindex should be checked'
		);
		$this->assertMatchesRegularExpression(
			'/name="robots_date_archive\[nofollow\]"[^>]*checked/',
			$output,
			'Date archive nofollow should be checked'
		);
	}

	/**
	 * Test render_advanced_tab outputs table structure
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_render_advanced_tab_outputs_table_structure(): void {
		ob_start();
		$this->settings_manager->render_advanced_tab();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<table class="widefat"', $output );
		$this->assertStringContainsString( '<thead>', $output );
		$this->assertStringContainsString( '<tbody>', $output );
		$this->assertStringContainsString( 'Archive Type', $output );
		$this->assertStringContainsString( 'Noindex', $output );
		$this->assertStringContainsString( 'Nofollow', $output );
	}

	/**
	 * Test render_advanced_tab outputs accessibility attributes
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_render_advanced_tab_outputs_accessibility_attributes(): void {
		ob_start();
		$this->settings_manager->render_advanced_tab();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'aria-label=', $output );
		$this->assertMatchesRegularExpression(
			'/aria-label="[^"]*Noindex[^"]*Author Archives[^"]*"/',
			$output,
			'Should have aria-label for author archive noindex'
		);
		$this->assertMatchesRegularExpression(
			'/aria-label="[^"]*Nofollow[^"]*Author Archives[^"]*"/',
			$output,
			'Should have aria-label for author archive nofollow'
		);
	}
}
