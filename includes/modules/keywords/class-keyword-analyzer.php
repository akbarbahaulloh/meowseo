<?php
/**
 * Keyword Analyzer class for per-keyword SEO analysis.
 *
 * Runs all keyword-based analysis checks for each focus keyword (primary + secondary).
 * Extends the existing SEO_Analyzer to provide per-keyword scoring.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Keywords;

use MeowSEO\Modules\Meta\SEO_Analyzer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keyword_Analyzer class.
 *
 * Analyzes content against multiple focus keywords.
 */
class Keyword_Analyzer {

	/**
	 * Keyword Manager instance.
	 *
	 * @var Keyword_Manager
	 */
	private Keyword_Manager $keyword_manager;

	/**
	 * Constructor.
	 *
	 * @param Keyword_Manager $keyword_manager Keyword Manager instance.
	 */
	public function __construct( Keyword_Manager $keyword_manager ) {
		$this->keyword_manager = $keyword_manager;
	}

	/**
	 * Analyze all keywords for a post.
	 *
	 * Runs all keyword-based analysis checks for each focus keyword.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $content Post content (HTML).
	 * @param array  $context Additional context data (title, description, slug).
	 * @return array Array of analysis results keyed by keyword.
	 */
	public function analyze_all_keywords( int $post_id, string $content, array $context = array() ): array {
		$keywords = $this->keyword_manager->get_keywords( $post_id );
		$results  = array();

		// Analyze primary keyword.
		if ( ! empty( $keywords['primary'] ) ) {
			$results[ $keywords['primary'] ] = $this->analyze_single_keyword(
				$keywords['primary'],
				$content,
				$context
			);
		}

		// Analyze secondary keywords.
		foreach ( $keywords['secondary'] as $keyword ) {
			if ( ! empty( $keyword ) ) {
				$results[ $keyword ] = $this->analyze_single_keyword(
					$keyword,
					$content,
					$context
				);
			}
		}

		// Store results in postmeta.
		update_post_meta( $post_id, '_meowseo_keyword_analysis', wp_json_encode( $results ) );

		return $results;
	}

	/**
	 * Analyze a single keyword.
	 *
	 * Runs all keyword-based checks for one keyword.
	 *
	 * @param string $keyword Focus keyword.
	 * @param string $content Post content (HTML).
	 * @param array  $context Additional context data (title, description, slug).
	 * @return array Analysis result with individual check scores.
	 */
	public function analyze_single_keyword( string $keyword, string $content, array $context = array() ): array {
		$title       = $context['title'] ?? '';
		$description = $context['description'] ?? '';
		$slug        = $context['slug'] ?? '';

		// Run keyword density analysis.
		$density = $this->analyze_keyword_density( $keyword, $content );

		// Run keyword-in-title analysis.
		$in_title = $this->analyze_keyword_in_title( $keyword, $title );

		// Run keyword-in-heading analysis.
		$in_headings = $this->analyze_keyword_in_headings( $keyword, $content );

		// Run keyword-in-slug analysis.
		$in_slug = $this->analyze_keyword_in_slug( $keyword, $slug );

		// Run keyword-in-first-paragraph analysis.
		$in_first_paragraph = $this->analyze_keyword_in_first_paragraph( $keyword, $content );

		// Run keyword-in-meta-description analysis.
		$in_meta_description = $this->analyze_keyword_in_meta_description( $keyword, $description );

		// Calculate overall score.
		$checks = array(
			$density,
			$in_title,
			$in_headings,
			$in_slug,
			$in_first_paragraph,
			$in_meta_description,
		);

		$total_score = array_sum( array_column( $checks, 'score' ) );
		$overall_score = (int) round( $total_score / count( $checks ) );

		return array(
			'density'               => $density,
			'in_title'              => $in_title,
			'in_headings'           => $in_headings,
			'in_slug'               => $in_slug,
			'in_first_paragraph'    => $in_first_paragraph,
			'in_meta_description'   => $in_meta_description,
			'overall_score'         => $overall_score,
		);
	}

	/**
	 * Analyze keyword density.
	 *
	 * Calculates keyword density as percentage of total words.
	 * Optimal range: 0.5% - 2.5%
	 *
	 * @param string $keyword Focus keyword.
	 * @param string $content Post content (HTML).
	 * @return array Analysis result with score and status.
	 */
	private function analyze_keyword_density( string $keyword, string $content ): array {
		if ( empty( $keyword ) || empty( $content ) ) {
			return array(
				'score'  => 0,
				'status' => 'poor',
				'value'  => 0,
			);
		}

		// Strip HTML tags.
		$text = wp_strip_all_tags( $content );

		// Count total words.
		$word_count = str_word_count( $text );

		if ( $word_count === 0 ) {
			return array(
				'score'  => 0,
				'status' => 'poor',
				'value'  => 0,
			);
		}

		// Count keyword occurrences (case-insensitive).
		$keyword_count = substr_count( strtolower( $text ), strtolower( $keyword ) );

		// Calculate density percentage.
		$density = ( $keyword_count / $word_count ) * 100;

		// Score based on optimal range (0.5% - 2.5%).
		$score = 0;
		$status = 'poor';

		if ( $density >= 0.5 && $density <= 2.5 ) {
			$score = 100;
			$status = 'good';
		} elseif ( $density > 0 && $density < 0.5 ) {
			$score = 70;
			$status = 'ok';
		} elseif ( $density > 2.5 && $density <= 4.0 ) {
			$score = 70;
			$status = 'ok';
		} else {
			$score = 40;
			$status = 'poor';
		}

		return array(
			'score'  => $score,
			'status' => $status,
			'value'  => round( $density, 2 ),
		);
	}

	/**
	 * Analyze keyword in title.
	 *
	 * @param string $keyword Focus keyword.
	 * @param string $title   SEO title.
	 * @return array Analysis result with score and status.
	 */
	private function analyze_keyword_in_title( string $keyword, string $title ): array {
		$found = $this->contains_keyword( $title, $keyword );

		return array(
			'score'  => $found ? 100 : 0,
			'status' => $found ? 'good' : 'poor',
		);
	}

	/**
	 * Analyze keyword in headings.
	 *
	 * @param string $keyword Focus keyword.
	 * @param string $content Post content (HTML).
	 * @return array Analysis result with score and status.
	 */
	private function analyze_keyword_in_headings( string $keyword, string $content ): array {
		$headings = $this->extract_headings( $content );
		$found = $this->keyword_in_headings( $headings, $keyword );

		return array(
			'score'  => $found ? 100 : 0,
			'status' => $found ? 'good' : 'poor',
		);
	}

	/**
	 * Analyze keyword in slug.
	 *
	 * @param string $keyword Focus keyword.
	 * @param string $slug    URL slug.
	 * @return array Analysis result with score and status.
	 */
	private function analyze_keyword_in_slug( string $keyword, string $slug ): array {
		// Convert slug dashes to spaces for matching.
		$slug_text = str_replace( '-', ' ', $slug );
		$found = $this->contains_keyword( $slug_text, $keyword );

		return array(
			'score'  => $found ? 100 : 0,
			'status' => $found ? 'good' : 'poor',
		);
	}

	/**
	 * Analyze keyword in first paragraph.
	 *
	 * @param string $keyword Focus keyword.
	 * @param string $content Post content (HTML).
	 * @return array Analysis result with score and status.
	 */
	private function analyze_keyword_in_first_paragraph( string $keyword, string $content ): array {
		$first_paragraph = $this->extract_first_paragraph( $content );
		$found = $this->contains_keyword( $first_paragraph, $keyword );

		return array(
			'score'  => $found ? 100 : 0,
			'status' => $found ? 'good' : 'poor',
		);
	}

	/**
	 * Analyze keyword in meta description.
	 *
	 * @param string $keyword     Focus keyword.
	 * @param string $description Meta description.
	 * @return array Analysis result with score and status.
	 */
	private function analyze_keyword_in_meta_description( string $keyword, string $description ): array {
		$found = $this->contains_keyword( $description, $keyword );

		return array(
			'score'  => $found ? 100 : 0,
			'status' => $found ? 'good' : 'poor',
		);
	}

	/**
	 * Check if text contains keyword (case-insensitive).
	 *
	 * @param string $text    Text to search in.
	 * @param string $keyword Keyword to search for.
	 * @return bool True if keyword found.
	 */
	private function contains_keyword( string $text, string $keyword ): bool {
		if ( empty( $keyword ) || empty( $text ) ) {
			return false;
		}

		return mb_stripos( $text, $keyword ) !== false;
	}

	/**
	 * Extract first paragraph from HTML content.
	 *
	 * @param string $content HTML content.
	 * @return string First paragraph text.
	 */
	private function extract_first_paragraph( string $content ): string {
		if ( empty( $content ) ) {
			return '';
		}

		// Strip shortcodes first.
		$content = strip_shortcodes( $content );

		// Try to extract first <p> tag.
		if ( preg_match( '/<p[^>]*>(.*?)<\/p>/is', $content, $matches ) ) {
			return wp_strip_all_tags( $matches[1] );
		}

		// Fallback: get first 200 characters of stripped content.
		$text = wp_strip_all_tags( $content );
		return mb_substr( $text, 0, 200 );
	}

	/**
	 * Extract H1-H6 headings from HTML content.
	 *
	 * @param string $content HTML content.
	 * @return array Array of heading texts.
	 */
	private function extract_headings( string $content ): array {
		if ( empty( $content ) ) {
			return array();
		}

		$headings = array();

		// Extract H1-H6 headings.
		for ( $i = 1; $i <= 6; $i++ ) {
			if ( preg_match_all( "/<h{$i}[^>]*>(.*?)<\/h{$i}>/is", $content, $matches ) ) {
				foreach ( $matches[1] as $heading ) {
					$headings[] = wp_strip_all_tags( $heading );
				}
			}
		}

		return $headings;
	}

	/**
	 * Check if keyword appears in any heading.
	 *
	 * @param array  $headings Array of heading texts.
	 * @param string $keyword  Keyword to search for.
	 * @return bool True if keyword found in at least one heading.
	 */
	private function keyword_in_headings( array $headings, string $keyword ): bool {
		if ( empty( $keyword ) || empty( $headings ) ) {
			return false;
		}

		foreach ( $headings as $heading ) {
			if ( $this->contains_keyword( $heading, $keyword ) ) {
				return true;
			}
		}

		return false;
	}
}
