<?php
/**
 * Settings_Manager Tests
 *
 * Unit tests for the Settings_Manager class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Admin;

use PHPUnit\Framework\TestCase;
use MeowSEO\Admin\Settings_Manager;
use MeowSEO\Options;

/**
 * Settings_Manager test case
 *
 * Tests settings validation, sanitization, and rendering.
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
 *
 * @since 1.0.0
 */
class SettingsManagerTest extends TestCase {

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
	 * Test Settings_Manager instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( Settings_Manager::class, $this->settings_manager );
	}

	/**
	 * Test validate_settings returns array for valid settings
	 *
	 * @return void
	 */
	public function test_validate_settings_returns_array_for_valid_settings(): void {
		$settings = array(
			'homepage_title' => 'Test Title',
			'separator'      => '|',
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'homepage_title', $result );
		$this->assertEquals( 'Test Title', $result['homepage_title'] );
	}

	/**
	 * Test validate_settings sanitizes text fields
	 *
	 * Requirement: 30.1
	 *
	 * @return void
	 */
	public function test_validate_settings_sanitizes_text_fields(): void {
		$settings = array(
			'homepage_title' => '  Test Title  ',
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertEquals( 'Test Title', $result['homepage_title'] );
	}

	/**
	 * Test validate_settings sanitizes textarea fields
	 *
	 * Requirement: 30.2
	 *
	 * @return void
	 */
	public function test_validate_settings_sanitizes_textarea_fields(): void {
		$settings = array(
			'homepage_description' => '  <p>Test Description</p>  ',
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertEquals( 'Test Description', $result['homepage_description'] );
	}

	/**
	 * Test validate_settings validates separator
	 *
	 * @return void
	 */
	public function test_validate_settings_validates_separator(): void {
		$settings = array(
			'separator' => '|',
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertEquals( '|', $result['separator'] );
	}

	/**
	 * Test validate_settings rejects invalid separator
	 *
	 * @return void
	 */
	public function test_validate_settings_rejects_invalid_separator(): void {
		$settings = array(
			'separator' => 'INVALID',
		);

		$result = $this->settings_manager->validate_settings( $settings );

		// Invalid separator should result in WP_Error.
		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test sanitize_social_url returns sanitized URL
	 *
	 * Requirement: 6.5
	 *
	 * @return void
	 */
	public function test_sanitize_social_url_returns_sanitized_url(): void {
		$url = 'https://facebook.com/testpage';

		$result = $this->settings_manager->sanitize_social_url( $url );

		$this->assertIsString( $result );
		$this->assertEquals( 'https://facebook.com/testpage', $result );
	}

	/**
	 * Test sanitize_social_url rejects invalid URL
	 *
	 * Requirement: 6.3, 6.4
	 *
	 * @return void
	 */
	public function test_sanitize_social_url_rejects_invalid_url(): void {
		$url = 'not-a-valid-url';

		$result = $this->settings_manager->sanitize_social_url( $url );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test sanitize_social_url rejects non-HTTPS URL
	 *
	 * @return void
	 */
	public function test_sanitize_social_url_rejects_non_https_url(): void {
		$url = 'http://facebook.com/testpage';

		$result = $this->settings_manager->sanitize_social_url( $url );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test sanitize_social_url returns empty for empty input
	 *
	 * @return void
	 */
	public function test_sanitize_social_url_returns_empty_for_empty_input(): void {
		$result = $this->settings_manager->sanitize_social_url( '' );

		$this->assertEquals( '', $result );
	}

	/**
	 * Test validate_settings handles enabled_modules
	 *
	 * @return void
	 */
	public function test_validate_settings_handles_enabled_modules(): void {
		$settings = array(
			'enabled_modules' => array( 'meta', 'schema', 'invalid_module' ),
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'enabled_modules', $result );
		$this->assertContains( 'meta', $result['enabled_modules'] );
		$this->assertContains( 'schema', $result['enabled_modules'] );
		$this->assertNotContains( 'invalid_module', $result['enabled_modules'] );
	}

	/**
	 * Test validate_settings handles noindex arrays
	 *
	 * @return void
	 */
	public function test_validate_settings_handles_noindex_arrays(): void {
		$settings = array(
			'noindex_post_types' => array( 'post', 'page' ),
			'noindex_taxonomies' => array( 'category' ),
			'noindex_archives'   => array( 'author' ),
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'noindex_post_types', $result );
		$this->assertEquals( array( 'post', 'page' ), $result['noindex_post_types'] );
	}

	/**
	 * Test validate_settings handles breadcrumbs settings
	 *
	 * @return void
	 */
	public function test_validate_settings_handles_breadcrumbs_settings(): void {
		$settings = array(
			'breadcrumbs_enabled'           => true,
			'breadcrumbs_separator'         => '>',
			'breadcrumbs_home_label'        => 'Home',
			'breadcrumbs_position'          => 'before_content',
			'breadcrumbs_show_on_post_types' => array( 'post' ),
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['breadcrumbs_enabled'] );
		$this->assertEquals( '>', $result['breadcrumbs_separator'] );
		$this->assertEquals( 'before_content', $result['breadcrumbs_position'] );
	}

	/**
	 * Test validate_settings handles canonical settings
	 *
	 * @return void
	 */
	public function test_validate_settings_handles_canonical_settings(): void {
		$settings = array(
			'canonical_force_trailing_slash' => true,
			'canonical_force_https'          => true,
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['canonical_force_trailing_slash'] );
		$this->assertTrue( $result['canonical_force_https'] );
	}

	/**
	 * Test validate_settings handles RSS settings with HTML
	 *
	 * Requirement: 30.4
	 *
	 * @return void
	 */
	public function test_validate_settings_handles_rss_settings_with_html(): void {
		$settings = array(
			'rss_before_content' => '<p>Read more at our site</p>',
			'rss_after_content'  => '<a href="https://example.com">Visit us</a>',
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'rss_before_content', $result );
		$this->assertArrayHasKey( 'rss_after_content', $result );
	}

	/**
	 * Test validate_settings handles delete_on_uninstall
	 *
	 * @return void
	 */
	public function test_validate_settings_handles_delete_on_uninstall(): void {
		$settings = array(
			'delete_on_uninstall' => true,
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['delete_on_uninstall'] );
	}

	/**
	 * Test render_settings_tabs outputs correct HTML
	 *
	 * Requirement: 4.1, 4.3
	 *
	 * @return void
	 */
	public function test_render_settings_tabs_outputs_correct_html(): void {
		ob_start();
		$this->settings_manager->render_settings_tabs();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'meowseo-settings-tabs', $output );
		$this->assertStringContainsString( 'meowseo-tab-button', $output );
		$this->assertStringContainsString( 'General', $output );
		$this->assertStringContainsString( 'Social Profiles', $output );
		$this->assertStringContainsString( 'Modules', $output );
		$this->assertStringContainsString( 'Advanced', $output );
		$this->assertStringContainsString( 'Breadcrumbs', $output );
	}

	/**
	 * Test render_settings_tabs marks active tab
	 *
	 * Requirement: 4.3
	 *
	 * @return void
	 */
	public function test_render_settings_tabs_marks_active_tab(): void {
		ob_start();
		$this->settings_manager->render_settings_tabs( 'modules' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="meowseo-tab-modules"', $output );
		$this->assertStringContainsString( 'class="meowseo-tab-button active"', $output );
	}

	/**
	 * Test get_errors returns empty array initially
	 *
	 * @return void
	 */
	public function test_get_errors_returns_empty_array_initially(): void {
		$this->assertEmpty( $this->settings_manager->get_errors() );
	}

	/**
	 * Test validate_settings handles archive robots settings
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_validate_settings_handles_archive_robots_settings(): void {
		$settings = array(
			'robots_author_archive'   => array(
				'noindex'  => true,
				'nofollow' => false,
			),
			'robots_date_archive'     => array(
				'noindex'  => true,
				'nofollow' => true,
			),
			'robots_category_archive' => array(
				'noindex'  => false,
				'nofollow' => false,
			),
			'robots_tag_archive'      => array(
				'noindex'  => false,
				'nofollow' => true,
			),
			'robots_search_results'   => array(
				'noindex'  => true,
				'nofollow' => false,
			),
			'robots_attachment'       => array(
				'noindex'  => true,
				'nofollow' => true,
			),
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'robots_author_archive', $result );
		$this->assertTrue( $result['robots_author_archive']['noindex'] );
		$this->assertFalse( $result['robots_author_archive']['nofollow'] );
		$this->assertTrue( $result['robots_date_archive']['noindex'] );
		$this->assertTrue( $result['robots_date_archive']['nofollow'] );
		$this->assertFalse( $result['robots_category_archive']['noindex'] );
		$this->assertFalse( $result['robots_category_archive']['nofollow'] );
	}

	/**
	 * Test validate_settings sets default values for missing archive robots settings
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_validate_settings_sets_defaults_for_missing_archive_robots(): void {
		$settings = array(
			'homepage_title' => 'Test',
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'robots_author_archive', $result );
		$this->assertFalse( $result['robots_author_archive']['noindex'] );
		$this->assertFalse( $result['robots_author_archive']['nofollow'] );
		$this->assertArrayHasKey( 'robots_date_archive', $result );
		$this->assertFalse( $result['robots_date_archive']['noindex'] );
		$this->assertFalse( $result['robots_date_archive']['nofollow'] );
	}

	/**
	 * Test validate_settings handles archive robots with only noindex set
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_validate_settings_handles_archive_robots_with_only_noindex(): void {
		$settings = array(
			'robots_author_archive' => array(
				'noindex' => true,
			),
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'robots_author_archive', $result );
		$this->assertTrue( $result['robots_author_archive']['noindex'] );
		$this->assertFalse( $result['robots_author_archive']['nofollow'] );
	}

	/**
	 * Test validate_settings handles archive robots with only nofollow set
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_validate_settings_handles_archive_robots_with_only_nofollow(): void {
		$settings = array(
			'robots_search_results' => array(
				'nofollow' => true,
			),
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'robots_search_results', $result );
		$this->assertFalse( $result['robots_search_results']['noindex'] );
		$this->assertTrue( $result['robots_search_results']['nofollow'] );
	}

	/**
	 * Test validate_settings converts non-boolean values to boolean for archive robots
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
	 *
	 * @return void
	 */
	public function test_validate_settings_converts_non_boolean_values_for_archive_robots(): void {
		$settings = array(
			'robots_attachment' => array(
				'noindex'  => '1',
				'nofollow' => '',
			),
		);

		$result = $this->settings_manager->validate_settings( $settings );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'robots_attachment', $result );
		$this->assertIsBool( $result['robots_attachment']['noindex'] );
		$this->assertIsBool( $result['robots_attachment']['nofollow'] );
		$this->assertTrue( $result['robots_attachment']['noindex'] );
		$this->assertFalse( $result['robots_attachment']['nofollow'] );
	}
}
