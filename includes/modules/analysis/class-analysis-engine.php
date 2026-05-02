<?php
/**
 * Analysis Engine
 *
 * Orchestrates all analyzers (SEO and Readability) to provide comprehensive
 * content analysis with actionable fix explanations.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Analysis;

use MeowSEO\Modules\Meta\SEO_Analyzer;
use MeowSEO\Modules\Meta\Readability;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Analysis_Engine class
 *
 * Orchestrates all analyzers and integrates fix explanations for failing/warning results.
 *
 * @since 1.0.0
 */
class Analysis_Engine {

	/**
	 * Fix Explanation Provider instance
	 *
	 * @var Fix_Explanation_Provider
	 */
	private Fix_Explanation_Provider $fix_provider;

	/**
	 * Constructor
	 *
	 * @param Fix_Explanation_Provider $fix_provider Fix explanation provider instance.
	 */
	public function __construct( Fix_Explanation_Provider $fix_provider ) {
		$this->fix_provider = $fix_provider;
	}

	/**
	 * Analyze content using all analyzers
	 *
	 * Runs SEO and readability analyzers, calculating scores and adding fix explanations
	 * for failing/warning results.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $data {
	 *     Analysis data.
	 *
	 *     @type string $title          SEO title.
	 *     @type string $description    Meta description.
	 *     @type string $content        Post content (HTML).
	 *     @type string $slug           URL slug.
	 *     @type string $focus_keyword  Focus keyword.
	 * }
	 * @return array {
	 *     Complete analysis result.
	 *
	 *     @type array $seo_results         SEO analyzer results with fix explanations.
	 *     @type array $readability_results Readability analyzer results with fix explanations.
	 *     @type int   $seo_score           Overall SEO score (0-100).
	 *     @type int   $readability_score   Overall readability score (0-100).
	 * }
	 */
	public function analyze( int $post_id, array $data ): array {
		$title = $data['title'] ?? '';
		$description = $data['description'] ?? '';
		$content = $data['content'] ?? '';
		$slug = $data['slug'] ?? '';
		$focus_keyword = $data['focus_keyword'] ?? '';

		// Run SEO analysis
		$seo_result = SEO_Analyzer::analyze( array(
			'title' => $title,
			'description' => $description,
			'content' => $content,
			'slug' => $slug,
			'focus_keyword' => $focus_keyword,
		) );

		// Run readability analysis
		$readability_result = Readability::analyze( $content );

		// Add fix explanations to SEO checks
		$seo_results_with_explanations = $this->add_fix_explanations(
			$seo_result['checks'] ?? array(),
			'seo',
			array(
				'keyword'            => $focus_keyword,
				'title_length'       => mb_strlen( $title ),
				'description_length' => mb_strlen( $description ),
				'content_length'     => mb_strlen( wp_strip_all_tags( $content ) ),
				'word_count'         => $this->count_words( $content ),
			)
		);

		// Add fix explanations to readability checks
		$readability_results_with_explanations = $this->add_fix_explanations(
			$readability_result['checks'] ?? array(),
			'readability',
			array(
				'keyword' => $focus_keyword,
			)
		);

		return array(
			'seo_results' => $seo_results_with_explanations,
			'readability_results' => $readability_results_with_explanations,
			'seo_score' => $seo_result['score'] ?? 0,
			'readability_score' => $readability_result['score'] ?? 0,
			'seo_color' => $seo_result['color'] ?? 'red',
			'readability_color' => $readability_result['color'] ?? 'red',
		);
	}

	/**
	 * Add fix explanations to analyzer results
	 *
	 * @param array  $checks      Array of check results.
	 * @param string $analyzer_type Type of analyzer ('seo' or 'readability').
	 * @param array  $context     Context data for explanations.
	 * @return array Checks with fix explanations added.
	 */
	private function add_fix_explanations( array $checks, string $analyzer_type, array $context ): array {
		foreach ( $checks as &$check ) {
			// Add fix explanation for failing checks
			if ( ! $check['pass'] ) {
				// Map check ID to explanation ID
				$explanation_id = $this->map_check_id_to_explanation_id( $check['id'], $context );

				$explanation = $this->fix_provider->get_explanation(
					$explanation_id,
					$this->build_context( $check, $context )
				);

				if ( ! empty( $explanation ) ) {
					$check['fix_explanation'] = $explanation;
				}
			}
		}

		return $checks;
	}

	/**
	 * Map check ID to explanation ID
	 *
	 * Converts SEO analyzer check IDs to explanation template IDs.
	 *
	 * @param string $check_id Check ID from analyzer.
	 * @param array  $context  Context data.
	 * @return string Explanation ID for fix provider.
	 */
	private function map_check_id_to_explanation_id( string $check_id, array $context ): string {
		switch ( $check_id ) {
			case 'title_length':
				$current_length = $context['title_length'] ?? 0;
				if ( $current_length < 30 ) {
					return 'title_too_short';
				} elseif ( $current_length > 60 ) {
					return 'title_too_long';
				}
				return $check_id;

			case 'description_length':
				$current_length = $context['description_length'] ?? 0;
				if ( 0 === $current_length ) {
					return 'description_missing';
				}
				return $check_id;

			case 'keyword_in_title':
				return 'keyword_missing_title';

			case 'keyword_in_first_paragraph':
				return 'keyword_missing_first_paragraph';

			case 'keyword_in_headings':
				return 'keyword_missing_headings';

			case 'keyword_in_slug':
				return 'slug_not_optimized';

			case 'keyword_density':
				return 'keyword_density';

			case 'internal_links':
				return 'internal_links_missing';

			case 'outbound_links':
				return 'outbound_links_missing';

			case 'image_alt_text':
				return 'image_alt_missing';

			case 'flesch_reading_ease':
				return 'flesch_score_low';

			default:
				return $check_id;
		}
	}

	/**
	 * Build context data for fix explanation
	 *
	 * Maps check-specific data to context variables used by explanation templates.
	 *
	 * @param array $check   Check result.
	 * @param array $context General context data.
	 * @return array Context data for explanation provider.
	 */
	private function build_context( array $check, array $context ): array {
		$check_id = $check['id'] ?? '';
		$explanation_context = $context;

		// Map check-specific context based on check ID
		switch ( $check_id ) {
			case 'title_length':
				$current_length = $context['title_length'] ?? 0;
				$explanation_context['current_length'] = $current_length;
				$explanation_context['min_length'] = 30;
				$explanation_context['max_length'] = 60;
				break;

			case 'description_length':
				$current_length = $context['description_length'] ?? 0;
				$explanation_context['current_length'] = $current_length;
				$explanation_context['min_length'] = 50;
				$explanation_context['max_length'] = 160;
				break;
		}

		return $explanation_context;
	}

	/**
	 * Count words in content
	 *
	 * @param string $content HTML content.
	 * @return int Word count.
	 */
	private function count_words( string $content ): int {
		$text = wp_strip_all_tags( $content );
		$text = trim( $text );

		if ( empty( $text ) ) {
			return 0;
		}

		return count( array_filter( explode( ' ', $text ) ) );
	}
}
