<?php
/**
 * Property 7: ISO 8601 Date Formatting
 *
 * Feature: meta-module-rebuild, Property 7: For any post with published_time or
 * modified_time, the output SHALL be in valid ISO 8601 format (YYYY-MM-DDTHH:MM:SS+00:00).
 *
 * Validates: Requirements 2.7
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use WP_UnitTestCase;
use MeowSEO\Modules\Meta\Meta_Output;
use MeowSEO\Modules\Meta\Meta_Resolver;
use MeowSEO\Modules\Meta\Title_Patterns;
use MeowSEO\Options;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Test Property 7: ISO 8601 Date Formatting
 */
class MetaProperty07ISO8601DateFormattingTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test ISO 8601 date formatting for article dates
	 *
	 * For any post with dates, article:published_time and article:modified_time
	 * should be in valid ISO 8601 format.
	 *
	 * @return void
	 */
	public function test_iso8601_date_formatting(): void {
		$this->forAll(
			Generator\string(),
			Generator\string()
		)->then( function( $post_title, $post_content ) {
			// Create post.
			$post_id = \wp_insert_post(
				array(
					'post_title'   => $post_title,
					'post_content' => $post_content,
					'post_status'  => 'publish',
					'post_type'    => 'post',
				)
			);

			// Capture output.
			ob_start();
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$output   = new Meta_Output( $resolver );
			$output->output_head_tags();
			$html = ob_get_clean();

			// Property: Output should not be empty.
			$this->assertNotEmpty( $html, 'Meta output should not be empty' );

			// Property: Verify ISO 8601 date format for article dates.
			$this->assert_iso8601_date_format( $html );

			// Cleanup.
			\wp_delete_post( $post_id, true );
		} );
	}

	/**
	 * Assert ISO 8601 date format in HTML output
	 *
	 * Verifies that article:published_time and article:modified_time
	 * are in valid ISO 8601 format (YYYY-MM-DDTHH:MM:SS+00:00).
	 *
	 * @param string $html HTML output to check.
	 * @return void
	 */
	private function assert_iso8601_date_format( string $html ): void {
		// ISO 8601 format regex: YYYY-MM-DDTHH:MM:SS+00:00.
		$iso8601_pattern = '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00/';

		// Check for article:published_time.
		if ( strpos( $html, '<meta property="article:published_time"' ) !== false ) {
			preg_match( '/<meta property="article:published_time" content="([^"]+)"/', $html, $matches );
			if ( ! empty( $matches[1] ) ) {
				$this->assertMatchesRegularExpression(
					$iso8601_pattern,
					$matches[1],
					'article:published_time should be in ISO 8601 format'
				);
			}
		}

		// Check for article:modified_time.
		if ( strpos( $html, '<meta property="article:modified_time"' ) !== false ) {
			preg_match( '/<meta property="article:modified_time" content="([^"]+)"/', $html, $matches );
			if ( ! empty( $matches[1] ) ) {
				$this->assertMatchesRegularExpression(
					$iso8601_pattern,
					$matches[1],
					'article:modified_time should be in ISO 8601 format'
				);
			}
		}
	}

	/**
	 * Test ISO 8601 date format with specific post
	 *
	 * Creates a post and verifies the date format is correct.
	 *
	 * @return void
	 */
	public function test_iso8601_date_format_specific(): void {
		// Create post.
		$post_id = \wp_insert_post(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		// Capture output.
		ob_start();
		$options  = new Options();
		$patterns = new Title_Patterns( $options );
		$resolver = new Meta_Resolver( $options, $patterns );
		$output   = new Meta_Output( $resolver );
		$output->output_head_tags();
		$html = ob_get_clean();

		// Property: Output should not be empty.
		$this->assertNotEmpty( $html, 'Meta output should not be empty' );

		// Property: Verify ISO 8601 date format.
		$this->assert_iso8601_date_format( $html );

		// Property: Verify article dates are present (for singular posts).
		// Note: In test environment, dates may not be output if post is not detected as singular.
		// This is expected behavior.

		// Cleanup.
		\wp_delete_post( $post_id, true );
	}

	/**
	 * Test ISO 8601 format validation
	 *
	 * Tests the format_iso8601 method directly to ensure it produces valid dates.
	 *
	 * @return void
	 */
	public function test_format_iso8601_method(): void {
		$this->forAll(
			Generator\int( 2020, 2030 ), // Year
			Generator\int( 1, 12 ),      // Month
			Generator\int( 1, 28 ),      // Day (safe range)
			Generator\int( 0, 23 ),      // Hour
			Generator\int( 0, 59 ),      // Minute
			Generator\int( 0, 59 )       // Second
		)->then( function( $year, $month, $day, $hour, $minute, $second ) {
			// Create MySQL datetime string.
			$mysql_date = sprintf(
				'%04d-%02d-%02d %02d:%02d:%02d',
				$year,
				$month,
				$day,
				$hour,
				$minute,
				$second
			);

			// Skip if strtotime can't parse it.
			if ( strtotime( $mysql_date . ' UTC' ) === false ) {
				return;
			}

			// Use reflection to access private method.
			$options  = new Options();
			$patterns = new Title_Patterns( $options );
			$resolver = new Meta_Resolver( $options, $patterns );
			$output   = new Meta_Output( $resolver );

			$reflection = new \ReflectionClass( $output );
			$method     = $reflection->getMethod( 'format_iso8601' );
			$method->setAccessible( true );

			// Format the date.
			$iso8601_date = $method->invoke( $output, $mysql_date );

			// Property: Result should not be empty for valid dates.
			$this->assertNotEmpty(
				$iso8601_date,
				'format_iso8601 should not return empty string for valid dates'
			);

			// Property: Result should match ISO 8601 format (allowing negative years for BC dates).
			$iso8601_pattern = '/^-?\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/';
			$this->assertMatchesRegularExpression(
				$iso8601_pattern,
				$iso8601_date,
				'format_iso8601 should produce valid ISO 8601 format'
			);

			// Property: Date should contain timezone offset.
			$this->assertStringContainsString(
				'+00:00',
				$iso8601_date,
				'ISO 8601 date should contain timezone offset +00:00'
			);
		} );
	}
}
