<?php
/**
 * Tests for Settings_Manager archive patterns functionality
 *
 * @package MeowSEO
 * @subpackage Tests\Admin
 */

namespace MeowSEO\Tests\Admin;

use MeowSEO\Admin\Settings_Manager;
use MeowSEO\Options;
use MeowSEO\Module_Manager;
use WP_UnitTestCase;

/**
 * Test Settings_Manager archive patterns
 *
 * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12, 5.13, 5.14, 5.15, 5.16
 */
class SettingsManagerArchivePatternsTest extends WP_UnitTestCase {

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
	 * Set up test
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = new Options();
		$module_manager = $this->createMock( Module_Manager::class );
		$this->settings_manager = new Settings_Manager( $this->options, $module_manager );
	}

	/**
	 * Test archive pattern validation with valid patterns
	 *
	 * Requirements: 5.1, 5.2, 5.3
	 */
	public function test_validate_archive_patterns_with_valid_patterns(): void {
		$settings = array(
			'archive_pattern_category_archive_title' => '%%category%% Archives %%sep%% %%sitename%%',
			'archive_pattern_category_archive_description' => 'Browse all posts in %%category%%',
			'archive_pattern_tag_archive_title' => '%%tag%% Tag %%sep%% %%sitename%%',
			'archive_pattern_tag_archive_description' => 'Posts tagged with %%tag%%',
		);

		$validated = $this->settings_manager->validate_settings( $settings );

		$this->assertNotInstanceOf( 'WP_Error', $validated );
		$this->assertArrayHasKey( 'title_patterns', $validated );
		$this->assertArrayHasKey( 'category_archive', $validated['title_patterns'] );
		$this->assertArrayHasKey( 'tag_archive', $validated['title_patterns'] );
		
		// Check that patterns were converted from %% to {} syntax
		$this->assertStringContainsString( '{category}', $validated['title_patterns']['category_archive']['title'] );
		$this->assertStringContainsString( '{site_name}', $validated['title_patterns']['category_archive']['title'] );
	}

	/**
	 * Test archive pattern validation with unmatched delimiters
	 *
	 * Requirements: 5.2, 5.3
	 */
	public function test_validate_archive_patterns_with_unmatched_delimiters(): void {
		$settings = array(
			'archive_pattern_category_archive_title' => '%%category%% Archives %%sep% %%sitename%%', // Missing one %
		);

		$validated = $this->settings_manager->validate_settings( $settings );

		$this->assertInstanceOf( 'WP_Error', $validated );
		$errors = $validated->get_error_data();
		$this->assertIsArray( $errors );
		$this->assertArrayHasKey( 'archive_pattern_category_archive_title', $errors );
		$this->assertStringContainsString( 'Unmatched', $errors['archive_pattern_category_archive_title'] );
	}

	/**
	 * Test archive pattern validation with invalid variable
	 *
	 * Requirements: 5.2, 5.3
	 */
	public function test_validate_archive_patterns_with_invalid_variable(): void {
		$settings = array(
			'archive_pattern_category_archive_title' => '%%invalid_var%% Archives %%sep%% %%sitename%%',
		);

		$validated = $this->settings_manager->validate_settings( $settings );

		$this->assertInstanceOf( 'WP_Error', $validated );
		$errors = $validated->get_error_data();
		$this->assertIsArray( $errors );
		$this->assertArrayHasKey( 'archive_pattern_category_archive_title', $errors );
		$this->assertStringContainsString( 'Invalid variable', $errors['archive_pattern_category_archive_title'] );
	}

	/**
	 * Test archive pattern validation with empty patterns
	 *
	 * Requirements: 5.1, 5.2
	 */
	public function test_validate_archive_patterns_with_empty_patterns(): void {
		$settings = array(
			'archive_pattern_category_archive_title' => '',
			'archive_pattern_category_archive_description' => '',
		);

		$validated = $this->settings_manager->validate_settings( $settings );

		$this->assertNotInstanceOf( 'WP_Error', $validated );
		$this->assertArrayHasKey( 'title_patterns', $validated );
		// Empty patterns should be stored as empty strings
		$this->assertEquals( '', $validated['title_patterns']['category_archive']['title'] );
		$this->assertEquals( '', $validated['title_patterns']['category_archive']['description'] );
	}

	/**
	 * Test all archive types are validated
	 *
	 * Requirements: 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12, 5.13, 5.14, 5.15, 5.16
	 */
	public function test_validate_all_archive_types(): void {
		$archive_types = array(
			'category_archive',
			'tag_archive',
			'custom_taxonomy_archive',
			'author_page',
			'search_results',
			'date_archive',
			'404_page',
			'homepage',
		);

		$settings = array();
		foreach ( $archive_types as $type ) {
			$settings[ 'archive_pattern_' . $type . '_title' ] = '%%title%% %%sep%% %%sitename%%';
			$settings[ 'archive_pattern_' . $type . '_description' ] = 'Test description';
		}

		$validated = $this->settings_manager->validate_settings( $settings );

		$this->assertNotInstanceOf( 'WP_Error', $validated );
		$this->assertArrayHasKey( 'title_patterns', $validated );

		foreach ( $archive_types as $type ) {
			$this->assertArrayHasKey( $type, $validated['title_patterns'] );
			$this->assertArrayHasKey( 'title', $validated['title_patterns'][ $type ] );
			$this->assertArrayHasKey( 'description', $validated['title_patterns'][ $type ] );
		}
	}

	/**
	 * Test pattern conversion from %% to {} syntax
	 *
	 * Requirements: 5.1, 5.2
	 */
	public function test_pattern_conversion_to_internal_syntax(): void {
		$settings = array(
			'archive_pattern_category_archive_title' => '%%category%% %%sep%% %%sitename%% %%page%%',
		);

		$validated = $this->settings_manager->validate_settings( $settings );

		$this->assertNotInstanceOf( 'WP_Error', $validated );
		$pattern = $validated['title_patterns']['category_archive']['title'];
		
		// Check all variables were converted
		$this->assertStringContainsString( '{category}', $pattern );
		$this->assertStringContainsString( '{sep}', $pattern );
		$this->assertStringContainsString( '{site_name}', $pattern );
		$this->assertStringContainsString( '{page}', $pattern );
		
		// Check no %% syntax remains
		$this->assertStringNotContainsString( '%%', $pattern );
	}

	/**
	 * Test archive patterns are saved to options
	 *
	 * Requirements: 5.1, 5.2
	 */
	public function test_archive_patterns_saved_to_options(): void {
		$settings = array(
			'archive_pattern_category_archive_title' => '%%category%% Archives %%sep%% %%sitename%%',
			'archive_pattern_category_archive_description' => 'Browse all posts in %%category%%',
		);

		$validated = $this->settings_manager->validate_settings( $settings );
		
		$this->assertNotInstanceOf( 'WP_Error', $validated );
		
		// Save the validated settings
		foreach ( $validated as $key => $value ) {
			$this->options->set( $key, $value );
		}
		$this->options->save();

		// Retrieve and verify
		$saved_patterns = $this->options->get( 'title_patterns', array() );
		$this->assertArrayHasKey( 'category_archive', $saved_patterns );
		$this->assertArrayHasKey( 'title', $saved_patterns['category_archive'] );
		$this->assertArrayHasKey( 'description', $saved_patterns['category_archive'] );
	}

	/**
	 * Test multiple validation errors are collected
	 *
	 * Requirements: 5.2, 5.3
	 */
	public function test_multiple_validation_errors_collected(): void {
		$settings = array(
			'archive_pattern_category_archive_title' => '%%category%% Archives %%sep% %%sitename%%', // Unmatched delimiter
			'archive_pattern_tag_archive_title' => '%%invalid%% Tag %%sep%% %%sitename%%', // Invalid variable
		);

		$validated = $this->settings_manager->validate_settings( $settings );

		$this->assertInstanceOf( 'WP_Error', $validated );
		$errors = $validated->get_error_data();
		$this->assertIsArray( $errors );
		$this->assertCount( 2, $errors );
		$this->assertArrayHasKey( 'archive_pattern_category_archive_title', $errors );
		$this->assertArrayHasKey( 'archive_pattern_tag_archive_title', $errors );
	}
}
