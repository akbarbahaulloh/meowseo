<?php
/**
 * Readability Analyzer
 *
 * Pure function for readability analysis. Checks sentence length, paragraph length,
 * transition words, and passive voice usage.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Readability class
 *
 * Provides pure functions for readability analysis without side effects.
 *
 * @since 1.0.0
 */
class Readability {

	/**
	 * Common transition words
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private static $transition_words = array(
		'however', 'therefore', 'furthermore', 'moreover', 'consequently',
		'nevertheless', 'thus', 'hence', 'accordingly', 'meanwhile',
		'additionally', 'likewise', 'similarly', 'conversely', 'nonetheless',
		'otherwise', 'subsequently', 'indeed', 'besides', 'also',
		'first', 'second', 'third', 'finally', 'next', 'then',
		'although', 'though', 'while', 'whereas', 'because', 'since',
		'if', 'unless', 'until', 'when', 'whenever', 'after', 'before',
		'as', 'so', 'yet', 'but', 'and', 'or', 'nor', 'for',
	);

	/**
	 * Passive voice indicators
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private static $passive_indicators = array(
		'is being', 'are being', 'was being', 'were being',
		'has been', 'have been', 'had been',
		'will be', 'will have been',
		'is', 'are', 'was', 'were', 'be', 'been', 'being',
	);

	/**
	 * Analyze readability for given content
	 *
	 * Checks:
	 * - Average sentence length ≤ 20 words
	 * - Paragraph length ≤ 150 words
	 * - Transition word usage ≥ 30% of sentences
	 * - Passive voice usage ≤ 10%
	 *
	 * @since 1.0.0
	 * @param string $content Post content (HTML).
	 * @return array {
	 *     Analysis result.
	 *
	 *     @type int   $score  Score from 0-100.
	 *     @type array $checks Array of check results.
	 *     @type string $color Color indicator: 'red', 'orange', or 'green'.
	 * }
	 */
	public static function analyze( string $content ): array {
		// Strip HTML and shortcodes.
		$text = wp_strip_all_tags( strip_shortcodes( $content ) );

		if ( empty( $text ) ) {
			return array(
				'score'  => 0,
				'checks' => array(),
				'color'  => 'red',
			);
		}

		$checks = array();

		// Check 1: Average sentence length ≤ 20 words.
		$avg_sentence_length = self::get_average_sentence_length( $text );
		$checks[] = array(
			'id'    => 'sentence_length',
			'label' => __( 'Average sentence length ≤ 20 words', 'meowseo' ),
			'pass'  => $avg_sentence_length <= 20,
		);

		// Check 2: Paragraph length ≤ 150 words.
		$max_paragraph_length = self::get_max_paragraph_length( $content );
		$checks[] = array(
			'id'    => 'paragraph_length',
			'label' => __( 'All paragraphs ≤ 150 words', 'meowseo' ),
			'pass'  => $max_paragraph_length <= 150,
		);

		// Check 3: Transition word usage ≥ 30% of sentences.
		$transition_percentage = self::get_transition_word_percentage( $text );
		$checks[] = array(
			'id'    => 'transition_words',
			'label' => __( 'Transition words in ≥ 30% of sentences', 'meowseo' ),
			'pass'  => $transition_percentage >= 30,
		);

		// Check 4: Passive voice usage ≤ 10%.
		$passive_percentage = self::get_passive_voice_percentage( $text );
		$checks[] = array(
			'id'    => 'passive_voice',
			'label' => __( 'Passive voice in ≤ 10% of sentences', 'meowseo' ),
			'pass'  => $passive_percentage <= 10,
		);

		// Check 5: Flesch Reading Ease score ≥ 60 (standard or easier).
		$flesch_score = self::get_flesch_reading_ease( $text );
		$checks[] = array(
			'id'    => 'flesch_reading_ease',
			'label' => __( 'Flesch Reading Ease score ≥ 60', 'meowseo' ),
			'pass'  => $flesch_score >= 60,
			'value' => round( $flesch_score, 1 ),
		);

		// Check 6: No 3+ consecutive sentences starting with the same word.
		$has_consecutive = self::has_consecutive_same_start( $text );
		$checks[] = array(
			'id'    => 'consecutive_sentences',
			'label' => __( 'No 3+ consecutive sentences start with the same word', 'meowseo' ),
			'pass'  => ! $has_consecutive,
		);

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
	 * Get average sentence length in words
	 *
	 * @since 1.0.0
	 * @param string $text Plain text content.
	 * @return float Average sentence length.
	 */
	private static function get_average_sentence_length( string $text ): float {
		$sentences = self::split_into_sentences( $text );
		
		if ( empty( $sentences ) ) {
			return 0;
		}

		$total_words = 0;
		foreach ( $sentences as $sentence ) {
			$total_words += self::count_words( $sentence );
		}

		return $total_words / count( $sentences );
	}

	/**
	 * Get maximum paragraph length in words
	 *
	 * @since 1.0.0
	 * @param string $content HTML content.
	 * @return int Maximum paragraph length.
	 */
	private static function get_max_paragraph_length( string $content ): int {
		$paragraphs = self::extract_paragraphs( $content );
		
		if ( empty( $paragraphs ) ) {
			return 0;
		}

		$max_length = 0;
		foreach ( $paragraphs as $paragraph ) {
			$word_count = self::count_words( $paragraph );
			if ( $word_count > $max_length ) {
				$max_length = $word_count;
			}
		}

		return $max_length;
	}

	/**
	 * Get percentage of sentences with transition words
	 *
	 * @since 1.0.0
	 * @param string $text Plain text content.
	 * @return float Percentage (0-100).
	 */
	private static function get_transition_word_percentage( string $text ): float {
		$sentences = self::split_into_sentences( $text );
		
		if ( empty( $sentences ) ) {
			return 0;
		}

		$sentences_with_transitions = 0;
		foreach ( $sentences as $sentence ) {
			if ( self::contains_transition_word( $sentence ) ) {
				$sentences_with_transitions++;
			}
		}

		return ( $sentences_with_transitions / count( $sentences ) ) * 100;
	}

	/**
	 * Get percentage of sentences with passive voice
	 *
	 * @since 1.0.0
	 * @param string $text Plain text content.
	 * @return float Percentage (0-100).
	 */
	private static function get_passive_voice_percentage( string $text ): float {
		$sentences = self::split_into_sentences( $text );
		
		if ( empty( $sentences ) ) {
			return 0;
		}

		$passive_sentences = 0;
		foreach ( $sentences as $sentence ) {
			if ( self::contains_passive_voice( $sentence ) ) {
				$passive_sentences++;
			}
		}

		return ( $passive_sentences / count( $sentences ) ) * 100;
	}

	/**
	 * Split text into sentences
	 *
	 * @since 1.0.0
	 * @param string $text Plain text content.
	 * @return array Array of sentences.
	 */
	private static function split_into_sentences( string $text ): array {
		// Split on period, exclamation, or question mark followed by space or end.
		$sentences = preg_split( '/[.!?]+\s+|\s*[.!?]+$/', $text, -1, PREG_SPLIT_NO_EMPTY );
		
		if ( ! $sentences ) {
			return array();
		}

		// Filter out very short sentences (likely not real sentences).
		return array_filter( $sentences, fn( $s ) => self::count_words( $s ) >= 3 );
	}

	/**
	 * Extract paragraphs from HTML content
	 *
	 * @since 1.0.0
	 * @param string $content HTML content.
	 * @return array Array of paragraph texts.
	 */
	private static function extract_paragraphs( string $content ): array {
		if ( empty( $content ) ) {
			return array();
		}

		$paragraphs = array();

		// Extract <p> tags.
		if ( preg_match_all( '/<p[^>]*>(.*?)<\/p>/is', $content, $matches ) ) {
			foreach ( $matches[1] as $paragraph ) {
				$text = wp_strip_all_tags( $paragraph );
				if ( ! empty( trim( $text ) ) ) {
					$paragraphs[] = $text;
				}
			}
		}

		// If no <p> tags found, treat entire content as one paragraph.
		if ( empty( $paragraphs ) ) {
			$text = wp_strip_all_tags( $content );
			if ( ! empty( trim( $text ) ) ) {
				$paragraphs[] = $text;
			}
		}

		return $paragraphs;
	}

	/**
	 * Count words in text
	 *
	 * @since 1.0.0
	 * @param string $text Text to count.
	 * @return int Word count.
	 */
	private static function count_words( string $text ): int {
		$text = trim( $text );
		if ( empty( $text ) ) {
			return 0;
		}

		return count( preg_split( '/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY ) );
	}

	/**
	 * Check if sentence contains a transition word
	 *
	 * @since 1.0.0
	 * @param string $sentence Sentence text.
	 * @return bool True if transition word found.
	 */
	private static function contains_transition_word( string $sentence ): bool {
		$sentence_lower = mb_strtolower( $sentence );

		foreach ( self::$transition_words as $word ) {
			// Use word boundaries to match whole words only.
			if ( preg_match( '/\b' . preg_quote( $word, '/' ) . '\b/', $sentence_lower ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if sentence contains passive voice
	 *
	 * @since 1.0.0
	 * @param string $sentence Sentence text.
	 * @return bool True if passive voice detected.
	 */
	private static function contains_passive_voice( string $sentence ): bool {
		$sentence_lower = mb_strtolower( $sentence );

		// Look for passive indicators followed by past participle pattern.
		foreach ( self::$passive_indicators as $indicator ) {
			// Simple heuristic: passive indicator + word ending in 'ed', 'en', 'n'.
			$pattern = '/\b' . preg_quote( $indicator, '/' ) . '\s+\w+(ed|en|n)\b/';
			if ( preg_match( $pattern, $sentence_lower ) ) {
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
	 * Calculate Flesch Reading Ease score
	 *
	 * Formula: 206.835 − 1.015×(words/sentences) − 84.6×(syllables/words)
	 * Interpretation:
	 *   90-100  Very easy
	 *   80-90   Easy
	 *   70-80   Fairly easy
	 *   60-70   Standard ← threshold
	 *   50-60   Fairly difficult
	 *   30-50   Difficult
	 *   0-30    Very confusing
	 *
	 * @since 1.0.0
	 * @param string $text Plain text.
	 * @return float Flesch score (0-100, clamped).
	 */
	private static function get_flesch_reading_ease( string $text ): float {
		$sentences = self::split_into_sentences( $text );
		$words     = preg_split( '/\s+/', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );

		$sentence_count = max( 1, count( $sentences ) );
		$word_count     = max( 1, count( $words ) );

		$syllable_count = 0;
		foreach ( $words as $word ) {
			$syllable_count += self::count_syllables( $word );
		}

		$asl   = $word_count / $sentence_count;         // Average sentence length.
		$asw   = $syllable_count / $word_count;          // Average syllables per word.
		$score = 206.835 - ( 1.015 * $asl ) - ( 84.6 * $asw );

		// Clamp to 0-100.
		return max( 0.0, min( 100.0, $score ) );
	}

	/**
	 * Count syllables in a single word using vowel-group heuristic
	 *
	 * Based on the CMU Pronouncing Dictionary approach adapted for PHP:
	 * - Count vowel groups (aeiou + y after consonant)
	 * - Subtract silent 'e' at end
	 * - Minimum 1 syllable per word
	 *
	 * @since 1.0.0
	 * @param string $word Word to count syllables for.
	 * @return int Syllable count.
	 */
	private static function count_syllables( string $word ): int {
		$word = mb_strtolower( preg_replace( '/[^a-zA-Z]/', '', $word ) );

		if ( empty( $word ) ) {
			return 0;
		}

		// Count vowel groups.
		$syllables = preg_match_all( '/[aeiouy]+/', $word );

		// Subtract silent 'e' at end of word (e.g. "make", "face").
		if ( mb_strlen( $word ) > 2 && mb_substr( $word, -1 ) === 'e' ) {
			$syllables--;
		}

		// Subtract silent 'le' if preceded by consonant (e.g. "table" is 2, not 3).
		if ( mb_strlen( $word ) > 2 && mb_substr( $word, -2 ) === 'le' ) {
			$preceding = mb_substr( $word, -3, 1 );
			if ( $preceding && ! preg_match( '/[aeiouy]/', $preceding ) ) {
				$syllables++; // already subtracted 'e', now add back for 'le'.
			}
		}

		// Every word has at least 1 syllable.
		return max( 1, (int) $syllables );
	}

	/**
	 * Check if 3 or more consecutive sentences start with the same word
	 *
	 * Detecting this pattern helps writers vary their sentence openings
	 * for a more engaging reading experience.
	 *
	 * @since 1.0.0
	 * @param string $text Plain text.
	 * @return bool True if 3+ consecutive same-start sentences found.
	 */
	private static function has_consecutive_same_start( string $text ): bool {
		$sentences = self::split_into_sentences( $text );

		if ( count( $sentences ) < 3 ) {
			return false;
		}

		$consecutive = 1;
		$prev_start  = '';

		foreach ( $sentences as $sentence ) {
			$words      = preg_split( '/\s+/', trim( $sentence ), -1, PREG_SPLIT_NO_EMPTY );
			$first_word = mb_strtolower( $words[0] ?? '' );

			if ( $first_word && $first_word === $prev_start ) {
				$consecutive++;
				if ( $consecutive >= 3 ) {
					return true;
				}
			} else {
				$consecutive = 1;
				$prev_start  = $first_word;
			}
		}

		return false;
	}
}
