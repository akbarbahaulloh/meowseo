<?php
/**
 * SEO Analyzer
 *
 * Pure function for SEO analysis. Checks focus keyword presence and meta field lengths.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO Analyzer class
 *
 * Provides pure functions for SEO analysis without side effects.
 *
 * @since 1.0.0
 */
class SEO_Analyzer {

	/**
	 * Analyze SEO for given content and metadata
	 *
	 * Checks:
	 * - Focus keyword in title
	 * - Focus keyword in description
	 * - Focus keyword in first paragraph
	 * - Focus keyword in H2/H3 headings
	 * - Focus keyword in URL slug
	 * - Meta description length (50-160 chars)
	 * - Title length (30-60 chars)
	 *
	 * @since 1.0.0
	 * @param array $data {
	 *     Analysis data.
	 *
	 *     @type string $title          SEO title.
	 *     @type string $description    Meta description.
	 *     @type string $content        Post content (HTML).
	 *     @type string $slug           URL slug.
	 *     @type string $focus_keyword  Focus keyword.
	 * }
	 * @return array {
	 *     Analysis result.
	 *
	 *     @type int   $score  Score from 0-100.
	 *     @type array $checks Array of check results.
	 *     @type string $color Color indicator: 'red', 'orange', or 'green'.
	 * }
	 */
	public static function analyze( array $data ): array {
		$title = $data['title'] ?? '';
		$description = $data['description'] ?? '';
		$content = $data['content'] ?? '';
		$slug = $data['slug'] ?? '';
		$focus_keyword = $data['focus_keyword'] ?? '';
		$lsi_keywords = $data['lsi_keywords'] ?? '';
		$direct_answer = $data['direct_answer'] ?? '';
		$post_id = $data['post_id'] ?? 0;

		$checks = array();

		// Check 1: Focus keyword in title.
		$checks[] = array(
			'id'    => 'keyword_in_title',
			'label' => __( 'Focus keyword in SEO title', 'meowseo' ),
			'pass'  => self::contains_keyword( $title, $focus_keyword ),
		);

		// Check 2: Focus keyword in description.
		$checks[] = array(
			'id'    => 'keyword_in_description',
			'label' => __( 'Focus keyword in meta description', 'meowseo' ),
			'pass'  => self::contains_keyword( $description, $focus_keyword ),
		);

		// Check 3: Focus keyword in first paragraph.
		$first_paragraph = self::extract_first_paragraph( $content );
		$checks[] = array(
			'id'    => 'keyword_in_first_paragraph',
			'label' => __( 'Focus keyword in first paragraph', 'meowseo' ),
			'pass'  => self::contains_keyword( $first_paragraph, $focus_keyword ),
		);

		// Check 4: Focus keyword in H2/H3 headings.
		$headings = self::extract_headings( $content );
		$checks[] = array(
			'id'    => 'keyword_in_headings',
			'label' => __( 'Focus keyword in at least one H2/H3 heading', 'meowseo' ),
			'pass'  => self::keyword_in_headings( $headings, $focus_keyword ),
		);

		// Check 5: Focus keyword in URL slug.
		$checks[] = array(
			'id'    => 'keyword_in_slug',
			'label' => __( 'Focus keyword in URL slug', 'meowseo' ),
			'pass'  => self::contains_keyword( $slug, $focus_keyword ),
		);

		// Check 6: Meta description length (50-160 chars).
		$desc_length = mb_strlen( $description );
		$checks[] = array(
			'id'    => 'description_length',
			'label' => __( 'Meta Description Length: The meta description is optimal length.', 'meowseo' ),
			'pass'  => $desc_length >= 120 && $desc_length <= 155,
		);

		// Check 7: Title length (30-60 chars).
		$title_length = mb_strlen( $title );
		$checks[] = array(
			'id'    => 'title_length',
			'label' => __( 'SEO Title Length: Excellent!', 'meowseo' ),
			'pass'  => $title_length >= 30 && $title_length <= 60,
		);

		// Check 8: Keyword density (0.5%-3%).
		$density = self::get_keyword_density( $content, $focus_keyword );
		$checks[] = array(
			'id'    => 'keyword_density',
			'label' => __( 'Focus keyword density (0.5%–3%)', 'meowseo' ),
			'pass'  => ! empty( $focus_keyword ) && $density >= 0.5 && $density <= 3.0,
			'value' => round( $density, 2 ),
		);

		// Check 9: Internal links.
		$internal_count = self::count_internal_links( $content );
		$checks[] = array(
			'id'    => 'internal_links',
			'label' => __( 'Internal Links: You have enough internal links.', 'meowseo' ),
			'pass'  => $internal_count >= 1,
			'value' => $internal_count,
		);

		// Check 10: Outbound links.
		$outbound_count = self::count_outbound_links( $content );
		$checks[] = array(
			'id'    => 'outbound_links',
			'label' => __( 'External Links: You have enough external links.', 'meowseo' ),
			'pass'  => $outbound_count >= 1,
			'value' => $outbound_count,
		);

		// Check 11: Image alt text.
		$images_without_alt = self::count_images_missing_alt( $content );
		$checks[] = array(
			'id'    => 'image_alt_text',
			'label' => __( 'All images have alt text', 'meowseo' ),
			'pass'  => 0 === $images_without_alt,
			'value' => $images_without_alt,
		);

		// Check 12: Content length ≥ 600 words.
		$word_count = self::count_words( $content );
		$checks[] = array(
			'id'    => 'content_length',
			'label' => __( 'Content length (≥ 600 words)', 'meowseo' ),
			'pass'  => $word_count >= 600,
			'value' => $word_count,
		);

		// Check 13: Keyword in at least one image alt text.
		$checks[] = array(
			'id'    => 'keyword_in_image_alt',
			'label' => __( 'Focus keyword in at least one image alt text', 'meowseo' ),
			'pass'  => ! empty( $focus_keyword ) && self::keyword_in_image_alt( $content, $focus_keyword ),
		);

		// Check 14: Keyword near beginning of title (first third of characters).
		$checks[] = array(
			'id'    => 'keyword_at_start_of_title',
			'label' => __( 'Focus keyword near the beginning of SEO title', 'meowseo' ),
			'pass'  => ! empty( $focus_keyword ) && self::keyword_at_start_of_title( $title, $focus_keyword ),
		);

		// Check 15: Title contains a power word.
		$checks[] = array(
			'id'    => 'title_power_word',
			'label' => __( 'SEO title contains a power word', 'meowseo' ),
			'pass'  => self::title_has_power_word( $title ),
		);

		// Check 16: Title contains a number.
		$checks[] = array(
			'id'    => 'title_has_number',
			'label' => __( 'SEO title contains a number', 'meowseo' ),
			'pass'  => self::title_has_number( $title ),
		);

		// Check 17: Subheading distribution (≤ 300 words per section).
		$checks[] = array(
			'id'    => 'subheading_distribution',
			'label' => __( 'Subheadings break up content every ≤ 300 words', 'meowseo' ),
			'pass'  => self::has_good_subheading_distribution( $content ),
		);

		// Check 18: Single Headline
		$checks[] = array(
			'id'    => 'single_headline',
			'label' => __( 'Single Headline: You don\'t have multiple H1 headings.', 'meowseo' ),
			'pass'  => self::has_single_headline( $content ),
		);

		// Check 19: Previously used keyword (Cannibalization Check)
		$checks[] = self::check_cannibalization( $post_id, $focus_keyword );

		// Check 20: LSI Keyword Analysis
		$checks[] = self::analyze_lsi_keywords( $content, $lsi_keywords );

		// Check 21: Heading Hierarchy
		$checks[] = self::analyze_heading_hierarchy( $content );

		// Check 22: ToC Detection
		$checks[] = self::analyze_toc_detection( $content );

		// Check 23: Local Image Analysis
		$checks[] = self::analyze_local_images( $content );

		// Check 24: External Link Quality
		$checks[] = self::analyze_external_links( $content );

		// Check 25: Direct Answer Paragraph
		$checks[] = self::analyze_direct_answer( $content, $direct_answer );

		// Check 26: List Table Detection
		$checks[] = self::analyze_list_table( $content );

		// Secondary Keywords Checks
		$secondary_keywords = $data['secondary_keywords'] ?? array();
		if ( ! empty( $secondary_keywords ) && is_array( $secondary_keywords ) ) {
			foreach ( $secondary_keywords as $i => $sk ) {
				if ( empty( trim( $sk ) ) ) continue;
				$sk_num = $i + 1;
				$checks[] = array(
					'id'    => 'secondary_keyword_in_content_' . $sk_num,
					'label' => sprintf( __( 'Secondary keyword %d (%s) found in content', 'meowseo' ), $sk_num, esc_html( $sk ) ),
					'pass'  => self::contains_keyword( $content, $sk ),
				);
			}
		}

		// Calculate score.
		$passing_checks = count( array_filter( $checks, fn( $check ) => $check['pass'] ) );
		$total_checks = count( $checks );
		$score = (int) round( ( $passing_checks / $total_checks ) * 100 );

		// Determine color indicator.
		$color = self::get_color_indicator( $score );

		return array(
			'score'  => $score,
			'checks' => $checks,
			'color'  => $color,
		);
	}

	/**
	 * Check if text contains keyword (case-insensitive)
	 *
	 * @since 1.0.0
	 * @param string $text    Text to search in.
	 * @param string $keyword Keyword to search for.
	 * @return bool True if keyword found.
	 */
	private static function contains_keyword( string $text, string $keyword ): bool {
		if ( empty( $keyword ) || empty( $text ) ) {
			return false;
		}

		return mb_stripos( $text, $keyword ) !== false;
	}

	/**
	 * Extract first paragraph from HTML content
	 *
	 * @since 1.0.0
	 * @param string $content HTML content.
	 * @return string First paragraph text.
	 */
	private static function extract_first_paragraph( string $content ): string {
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
	 * Extract H2 and H3 headings from HTML content
	 *
	 * @since 1.0.0
	 * @param string $content HTML content.
	 * @return array Array of heading texts.
	 */
	private static function extract_headings( string $content ): array {
		if ( empty( $content ) ) {
			return array();
		}

		$headings = array();

		// Extract H2 headings.
		if ( preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $content, $matches ) ) {
			foreach ( $matches[1] as $heading ) {
				$headings[] = wp_strip_all_tags( $heading );
			}
		}

		// Extract H3 headings.
		if ( preg_match_all( '/<h3[^>]*>(.*?)<\/h3>/is', $content, $matches ) ) {
			foreach ( $matches[1] as $heading ) {
				$headings[] = wp_strip_all_tags( $heading );
			}
		}

		return $headings;
	}

	/**
	 * Check if keyword appears in any heading
	 *
	 * @since 1.0.0
	 * @param array  $headings Array of heading texts.
	 * @param string $keyword  Keyword to search for.
	 * @return bool True if keyword found in at least one heading.
	 */
	private static function keyword_in_headings( array $headings, string $keyword ): bool {
		if ( empty( $keyword ) || empty( $headings ) ) {
			return false;
		}

		foreach ( $headings as $heading ) {
			if ( self::contains_keyword( $heading, $keyword ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get color indicator based on score
	 *
	 * @since 1.0.0
	 * @param int $score Score from 0-100.
	 * @return string Color: 'red', 'orange', or 'green'.
	 */
	private static function get_color_indicator( int $score ): string {
		if ( $score >= 80 ) {
			return 'green';
		} elseif ( $score >= 50 ) {
			return 'orange';
		} else {
			return 'red';
		}
	}
	/**
	 * Get focus keyword density in content
	 *
	 * @since 1.0.0
	 * @param string $content  HTML content.
	 * @param string $keyword  Focus keyword.
	 * @return float Density percentage (0-100).
	 */
	private static function get_keyword_density( string $content, string $keyword ): float {
		if ( empty( $keyword ) || empty( $content ) ) {
			return 0.0;
		}

		$text       = wp_strip_all_tags( $content );
		$word_count = count( preg_split( '/\s+/', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) );

		if ( 0 === $word_count ) {
			return 0.0;
		}

		$keyword_count = (int) preg_match_all(
			'/\b' . preg_quote( mb_strtolower( $keyword ), '/' ) . '\b/iu',
			mb_strtolower( $text )
		);

		$keyword_words = count( preg_split( '/\s+/', trim( $keyword ), -1, PREG_SPLIT_NO_EMPTY ) );

		return ( $keyword_count * $keyword_words / $word_count ) * 100;
	}

	/**
	 * Count internal links in content
	 *
	 * Counts anchor tags pointing to the same domain or relative URLs.
	 *
	 * @since 1.0.0
	 * @param string $content HTML content.
	 * @return int Number of internal links.
	 */
	private static function count_internal_links( string $content ): int {
		if ( empty( $content ) ) {
			return 0;
		}

		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$count     = 0;

		if ( preg_match_all( '/href=["\']([^"\']+)["\']/i', $content, $matches ) ) {
			foreach ( $matches[1] as $href ) {
				$href = trim( $href );
				// Relative URLs are internal.
				if ( ! preg_match( '/^https?:\/\//i', $href ) ) {
					if ( ! preg_match( '/^(mailto:|tel:|#)/i', $href ) ) {
						$count++;
					}
					continue;
				}
				// Same-domain URLs.
				$link_host = wp_parse_url( $href, PHP_URL_HOST );
				if ( $link_host && $link_host === $home_host ) {
					$count++;
				}
			}
		}

		return $count;
	}

	/**
	 * Count outbound (external) links in content
	 *
	 * Counts anchor tags pointing to a different domain.
	 *
	 * @since 1.0.0
	 * @param string $content HTML content.
	 * @return int Number of outbound links.
	 */
	private static function count_outbound_links( string $content ): int {
		if ( empty( $content ) ) {
			return 0;
		}

		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$count     = 0;

		if ( preg_match_all( '/href=["\']([^"\']+)["\']/i', $content, $matches ) ) {
			foreach ( $matches[1] as $href ) {
				$href = trim( $href );
				if ( ! preg_match( '/^https?:\/\//i', $href ) ) {
					continue;
				}
				$link_host = wp_parse_url( $href, PHP_URL_HOST );
				if ( $link_host && $link_host !== $home_host ) {
					$count++;
				}
			}
		}

		return $count;
	}

	/**
	 * Count images missing alt text
	 *
	 * @since 1.0.0
	 * @param string $content HTML content.
	 * @return int Number of images without meaningful alt text.
	 */
	private static function count_images_missing_alt( string $content ): int {
		if ( empty( $content ) ) {
			return 0;
		}

		$missing = 0;

		if ( preg_match_all( '/<img[^>]+>/i', $content, $matches ) ) {
			foreach ( $matches[0] as $img_tag ) {
				// Check for alt attribute with non-empty value.
				if ( ! preg_match( '/alt=["\']([^"\'][^"\']*)["\']/i', $img_tag ) ) {
					$missing++;
				}
			}
		}

		return $missing;
	}
	/**
	 * Count words in HTML content
	 *
	 * @param string $content HTML content.
	 * @return int Word count.
	 */
	private static function count_words( string $content ): int {
		$text = wp_strip_all_tags( strip_shortcodes( $content ) );
		$text = trim( $text );
		if ( empty( $text ) ) {
			return 0;
		}
		return count( preg_split( '/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY ) );
	}

	/**
	 * Check if focus keyword appears in at least one image alt text
	 *
	 * @param string $content HTML content.
	 * @param string $keyword Focus keyword.
	 * @return bool True if keyword found in any alt text.
	 */
	private static function keyword_in_image_alt( string $content, string $keyword ): bool {
		if ( empty( $content ) || empty( $keyword ) ) {
			return false;
		}
		if ( preg_match_all( '/<img[^>]+>/i', $content, $img_matches ) ) {
			foreach ( $img_matches[0] as $img_tag ) {
				if ( preg_match( '/alt=["\']([^"\']*)["\']/', $img_tag, $alt_match ) ) {
					if ( mb_stripos( $alt_match[1], $keyword ) !== false ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Check if focus keyword appears within first third of title
	 *
	 * @param string $title   SEO title.
	 * @param string $keyword Focus keyword.
	 * @return bool
	 */
	private static function keyword_at_start_of_title( string $title, string $keyword ): bool {
		if ( empty( $title ) || empty( $keyword ) ) {
			return false;
		}
		$pos = mb_stripos( $title, $keyword );
		if ( false === $pos ) {
			return false;
		}
		return $pos <= (int) ( mb_strlen( $title ) / 3 );
	}

	/**
	 * Check if title contains at least one power word
	 *
	 * Power words are emotionally charged words proven to improve CTR.
	 *
	 * @param string $title SEO title.
	 * @return bool
	 */
	private static function title_has_power_word( string $title ): bool {
		if ( empty( $title ) ) {
			return false;
		}
		$power_words = array(
			'best', 'ultimate', 'complete', 'definitive', 'essential', 'incredible',
			'amazing', 'powerful', 'proven', 'secret', 'free', 'new', 'top', 'great',
			'perfect', 'easy', 'simple', 'quick', 'fast', 'effective', 'guide', 'tips',
			'tricks', 'ways', 'steps', 'strategies', 'methods', 'ideas', 'examples',
			'reasons', 'facts', 'benefits', 'avoid', 'mistakes', 'warning', 'important',
			'surprising', 'critical', 'exclusive', 'boost', 'increase', 'improve',
			'guaranteed', 'instant', 'massive', 'epic', 'insane', 'genius', 'hack',
			'discover', 'revealed', 'blueprint', 'cheatsheet', 'checklist', 'review',
		);
		$title_lower = mb_strtolower( $title );
		foreach ( $power_words as $word ) {
			if ( preg_match( '/\b' . preg_quote( $word, '/' ) . '\b/', $title_lower ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if title contains a number
	 *
	 * Numbers in titles improve click-through rates (e.g. "10 Ways to...")
	 *
	 * @param string $title SEO title.
	 * @return bool
	 */
	private static function title_has_number( string $title ): bool {
		return (bool) preg_match( '/\d+/', $title );
	}

	/**
	 * Check subheading distribution
	 *
	 * For content over 300 words, verifies no section between headings
	 * exceeds 300 words. Short content always passes.
	 *
	 * @param string $content HTML content.
	 * @return bool True if distribution is good.
	 */
	private static function has_good_subheading_distribution( string $content ): bool {
		if ( empty( $content ) ) {
			return true;
		}
		$text        = wp_strip_all_tags( $content );
		$total_words = count( preg_split( '/\s+/', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) );

		// Short content: not applicable, auto-pass.
		if ( $total_words <= 300 ) {
			return true;
		}

		// Split on any heading tag to isolate sections between headings.
		$sections = preg_split( '/<h[1-6][^>]*>.*?<\/h[1-6]>/is', $content );

		if ( ! $sections ) {
			return false; // Has lots of content but no headings at all.
		}

		foreach ( $sections as $section ) {
			$section_text  = wp_strip_all_tags( $section );
			$section_words = count( preg_split( '/\s+/', trim( $section_text ), -1, PREG_SPLIT_NO_EMPTY ) );
			if ( $section_words > 300 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if content has single H1 headline.
	 *
	 * @param string $content HTML content.
	 * @return bool True if 1 or 0 H1 headings.
	 */
	private static function has_single_headline( string $content ): bool {
		if ( empty( $content ) ) {
			return true;
		}
		$count = preg_match_all( '/<h1[^>]*>/is', $content, $matches );
		return $count <= 1;
	}

	/**
	 * Analyze LSI Keywords
	 */
	private static function analyze_lsi_keywords( string $content, string $lsi_keywords ): array {
		if ( empty( $lsi_keywords ) ) {
			return array(
				'id'    => 'lsi_keyword_analysis',
				'label' => __( 'Semantic SEO: No LSI keywords provided. Add secondary keywords to improve semantic relevance.', 'meowseo' ),
				'pass'  => false,
			);
		}

		$keywords = array_filter( array_map( 'trim', explode( ',', strtolower( $lsi_keywords ) ) ) );
		if ( empty( $keywords ) || empty( $content ) ) {
			return array(
				'id'    => 'lsi_keyword_analysis',
				'label' => __( 'Semantic SEO: Add content to analyze LSI keyword usage.', 'meowseo' ),
				'pass'  => false,
			);
		}

		$content_lower = strtolower( wp_strip_all_tags( $content ) );
		$found = 0;
		foreach ( $keywords as $kw ) {
			if ( strpos( $content_lower, $kw ) !== false ) {
				$found++;
			}
		}

		$percent = $found / count( $keywords );
		return array(
			'id'    => 'lsi_keyword_analysis',
			'label' => sprintf( __( 'Semantic SEO: You\'ve used %1$d of %2$d LSI keywords.', 'meowseo' ), $found, count( $keywords ) ),
			'pass'  => $percent >= 0.5,
		);
	}

	/**
	 * Analyze Heading Hierarchy
	 */
	private static function analyze_heading_hierarchy( string $content ): array {
		if ( empty( $content ) ) {
			return array( 'id' => 'heading_hierarchy', 'label' => __( 'Content Structure: No content to analyze for heading hierarchy.', 'meowseo' ), 'pass' => true );
		}

		if ( preg_match_all( '/<h([1-6])[^>]*>/i', $content, $matches ) ) {
			$levels = array_map( 'intval', $matches[1] );
			$prev = $levels[0];
			for ( $i = 1; $i < count( $levels ); $i++ ) {
				$curr = $levels[$i];
				if ( $curr > $prev + 1 ) {
					return array(
						'id'    => 'heading_hierarchy',
						'label' => __( 'Content Structure: Your heading structure is incorrect. Do not skip heading levels.', 'meowseo' ),
						'pass'  => false,
					);
				}
				$prev = $curr;
			}
		}

		return array(
			'id'    => 'heading_hierarchy',
			'label' => __( 'Content Structure: Great! Your heading hierarchy is logically structured.', 'meowseo' ),
			'pass'  => true,
		);
	}

	/**
	 * Analyze ToC Detection
	 */
	private static function analyze_toc_detection( string $content ): array {
		$word_count = self::count_words( $content );
		if ( $word_count < 1000 ) {
			return array( 'id' => 'toc_detection', 'label' => __( 'Content Structure: Article length is standard, Table of Contents is optional.', 'meowseo' ), 'pass' => true );
		}

		$has_toc = preg_match( '/class=["\'][^"\']*ez-toc-container/is', $content ) ||
				   preg_match( '/id=["\'][^"\']*toc/is', $content ) ||
				   preg_match( '/<!-- wp:(simpletoc\/toc|yoast\/toc|meowseo\/toc)/is', $content );

		if ( $has_toc ) {
			return array( 'id' => 'toc_detection', 'label' => __( 'Content Structure: Excellent! Your long article includes a Table of Contents.', 'meowseo' ), 'pass' => true );
		}

		return array( 'id' => 'toc_detection', 'label' => __( 'Content Structure: Consider adding a Table of Contents for long articles.', 'meowseo' ), 'pass' => false );
	}

	/**
	 * Analyze Local Image Quality
	 */
	private static function analyze_local_images( string $content ): array {
		if ( empty( $content ) || ! preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches ) ) {
			return array( 'id' => 'local_image_analysis', 'label' => __( 'Image Quality: No images appear on this page.', 'meowseo' ), 'pass' => false );
		}

		$external = 0;
		foreach ( $matches[1] as $src ) {
			if ( strpos( $src, 'data:' ) === 0 || strpos( $src, '/' ) === 0 ) {
				continue;
			}
			if ( strpos( $src, 'wp-content/uploads' ) === false ) {
				$external++;
			}
		}

		if ( $external > 0 ) {
			return array( 'id' => 'local_image_analysis', 'label' => __( 'Image Quality: Found external images. Host images locally for better SEO.', 'meowseo' ), 'pass' => false );
		}

		return array( 'id' => 'local_image_analysis', 'label' => __( 'Image Quality: All images appear to be hosted locally.', 'meowseo' ), 'pass' => true );
	}

	/**
	 * Analyze External Link Quality
	 */
	private static function analyze_external_links( string $content ): array {
		if ( empty( $content ) || ! preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\']/i', $content, $matches ) ) {
			return array( 'id' => 'external_link_quality', 'label' => __( 'Link Quality: No external links found.', 'meowseo' ), 'pass' => true );
		}

		$spam = 0;
		$http = 0;
		$blacklist = array( '.xyz', '.top', '.loan', '.click', '.gq', '.cf', '.tk', '.ml' );

		foreach ( $matches[1] as $href ) {
			if ( strpos( $href, 'http://' ) === 0 ) {
				$http++;
			}
			if ( preg_match( '/^https?:\/\//i', $href ) ) {
				$host = wp_parse_url( $href, PHP_URL_HOST );
				if ( $host ) {
					foreach ( $blacklist as $tld ) {
						if ( substr( $host, -strlen( $tld ) ) === $tld ) {
							$spam++;
							break;
						}
					}
				}
			}
		}

		if ( $spam > 0 || $http > 0 ) {
			return array( 'id' => 'external_link_quality', 'label' => __( 'Link Quality: Found HTTP links or blacklisted TLDs. Use HTTPS and avoid spam domains.', 'meowseo' ), 'pass' => false );
		}

		return array( 'id' => 'external_link_quality', 'label' => __( 'Link Quality: All external links are HTTPS and avoid spam TLDs.', 'meowseo' ), 'pass' => true );
	}

	/**
	 * Analyze Direct Answer
	 */
	private static function analyze_direct_answer( string $content, string $direct_answer ): array {
		if ( ! empty( $direct_answer ) ) {
			$words = str_word_count( strip_tags( $direct_answer ) );
			return array(
				'id'    => 'direct_answer_paragraph',
				'label' => __( 'Featured Snippet: Direct answer paragraph provided.', 'meowseo' ),
				'pass'  => $words >= 30 && $words <= 60,
			);
		}

		if ( preg_match_all( '/<p[^>]*>(.*?)<\/p>/is', $content, $matches ) ) {
			foreach ( $matches[1] as $p ) {
				$text = wp_strip_all_tags( $p );
				$words = str_word_count( $text );
				if ( $words >= 40 && $words <= 60 ) {
					return array( 'id' => 'direct_answer_paragraph', 'label' => __( 'Featured Snippet: You have a paragraph of optimal length (40-60 words).', 'meowseo' ), 'pass' => true );
				}
			}
		}
		return array( 'id' => 'direct_answer_paragraph', 'label' => __( 'Featured Snippet: Add a concise 40-50 word paragraph early in the content.', 'meowseo' ), 'pass' => false );
	}

	/**
	 * Analyze List Table
	 */
	private static function analyze_list_table( string $content ): array {
		$has_list = preg_match( '/<(ul|ol)[^>]*>/is', $content );
		$has_table = preg_match( '/<table[^>]*>/is', $content );

		if ( $has_list || $has_table ) {
			return array( 'id' => 'list_table_detection', 'label' => __( 'Featured Snippet: Your content contains a list or table.', 'meowseo' ), 'pass' => true );
		}

		return array( 'id' => 'list_table_detection', 'label' => __( 'Featured Snippet: Try adding a list or a table.', 'meowseo' ), 'pass' => false );
	}

	/**
	 * Check Keyword Cannibalization
	 */
	private static function check_cannibalization( int $post_id, string $keyword ): array {
		if ( empty( $keyword ) ) {
			return array( 'id' => 'previously_used_keyword', 'label' => __( 'Keyphrase Used Previously: No focus keyphrase was set.', 'meowseo' ), 'pass' => false );
		}

		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_status = 'publish' 
			AND p.ID != %d 
			AND pm.meta_key = %s 
			AND pm.meta_value = %s
			LIMIT 1",
			$post_id,
			'_meowseo_focus_keyword',
			$keyword
		);

		$is_used = (bool) $wpdb->get_var( $query );

		if ( $is_used ) {
			return array( 'id' => 'previously_used_keyword', 'label' => __( 'Keyphrase Used Previously: You\'ve used this keyphrase once before. Do not use your keyphrase more than once.', 'meowseo' ), 'pass' => false );
		}

		return array( 'id' => 'previously_used_keyword', 'label' => __( 'Keyphrase Used Previously: You haven\'t used this keyphrase before, which is great.', 'meowseo' ), 'pass' => true );
	}
}
