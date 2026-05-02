<?php
/**
 * Fix Explanation Provider
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Analysis;

/**
 * Fix_Explanation_Provider class
 *
 * Provides actionable fix explanations for failing analyzer checks.
 */
class Fix_Explanation_Provider {
	/**
	 * Explanation templates for all analyzer types
	 *
	 * @var array
	 */
	private array $explanations = array(
		'title_too_short' => array(
			'issue' => 'Your SEO title is too short at {current_length} characters.',
			'fix' => 'Aim for {min_length}-{max_length} characters. Add more descriptive words that include your focus keyword "{keyword}".',
		),
		'title_too_long' => array(
			'issue' => 'Your SEO title is too long at {current_length} characters.',
			'fix' => 'Shorten it to {max_length} characters or less. Google typically displays the first {max_length} characters in search results.',
		),
		'keyword_missing_title' => array(
			'issue' => 'Your focus keyword "{keyword}" is not in the SEO title.',
			'fix' => 'Add "{keyword}" near the beginning of your title for better SEO. Example: "{keyword} - {site_name}"',
		),
		'keyword_missing_first_paragraph' => array(
			'issue' => 'Your focus keyword "{keyword}" is not in the first paragraph.',
			'fix' => 'Include "{keyword}" in the opening sentences to signal relevance to search engines and readers.',
		),
		'description_missing' => array(
			'issue' => 'Your meta description is missing.',
			'fix' => 'Write a compelling 150-160 character summary that includes your focus keyword "{keyword}" and encourages clicks.',
		),
		'content_too_short' => array(
			'issue' => 'Your content is only {current_words} words.',
			'fix' => 'Aim for at least {min_words} words. Add more detailed information, examples, or sections to provide comprehensive coverage of "{keyword}".',
		),
		'keyword_density_low' => array(
			'issue' => 'Your keyword density for "{keyword}" is {current_density}%.',
			'fix' => 'Aim for {target_min}%-{target_max}% density. Add "{keyword}" naturally in headings, body text, and image alt text.',
		),
		'keyword_density_high' => array(
			'issue' => 'Your keyword density for "{keyword}" is {current_density}%, which may be considered keyword stuffing.',
			'fix' => 'Reduce to {target_max}% or less. Use synonyms and related terms instead of repeating "{keyword}" excessively.',
		),
		'keyword_missing_headings' => array(
			'issue' => 'Your focus keyword "{keyword}" is not in any headings.',
			'fix' => 'Add "{keyword}" to at least one H2 or H3 heading to improve content structure and SEO.',
		),
		'images_missing_alt' => array(
			'issue' => 'You have {count} images without alt text.',
			'fix' => 'Add descriptive alt text to all images. Include "{keyword}" where relevant, but prioritize accurate descriptions for accessibility.',
		),
		'slug_not_optimized' => array(
			'issue' => 'Your URL slug doesn\'t include the focus keyword.',
			'fix' => 'Edit the permalink to include "{keyword}". Keep it short and readable. Example: /your-site/{keyword_slug}/',
		),
		'keyword_density' => array(
			'issue' => 'Your keyword density for "{keyword}" is outside the ideal 0.5%–3% range.',
			'fix' => 'Use "{keyword}" naturally throughout your content. Aim for 1–2 occurrences per 100 words. Avoid keyword stuffing.',
		),
		'internal_links_missing' => array(
			'issue' => 'Your content has no internal links.',
			'fix' => 'Add at least one link to another page on your site. This improves navigation and distributes link equity.',
		),
		'outbound_links_missing' => array(
			'issue' => 'Your content has no outbound (external) links.',
			'fix' => 'Link to at least one authoritative external source to add credibility and context to your content.',
		),
		'image_alt_missing' => array(
			'issue' => 'One or more images in your content are missing alt text.',
			'fix' => 'Add descriptive alt text to every image. Include "{keyword}" where relevant. Alt text improves accessibility and helps images rank in Google Image Search.',
		),
		'flesch_score_low' => array(
			'issue' => 'Your content has a low Flesch Reading Ease score — it may be difficult to read.',
			'fix' => 'Use shorter sentences (aim for ≤20 words), simpler vocabulary, and break long paragraphs into smaller ones. The target score is 60 or above.',
		),
	);

	/**
	 * Get explanation for analyzer result
	 *
	 * @param string $analyzer_id Analyzer identifier.
	 * @param array  $context     Context data for explanation.
	 * @return string Fix explanation.
	 */
	public function get_explanation( string $analyzer_id, array $context = array() ): string {
		// Return empty string for unknown analyzer IDs
		if ( ! isset( $this->explanations[ $analyzer_id ] ) ) {
			return '';
		}

		$template = $this->explanations[ $analyzer_id ];

		$issue = $this->replace_variables( $template['issue'], $context );
		$fix   = $this->replace_variables( $template['fix'], $context );

		return sprintf(
			'<div class="meowseo-fix-explanation"><p class="issue">%s</p><p class="fix"><strong>How to fix:</strong> %s</p></div>',
			esc_html( $issue ),
			esc_html( $fix )
		);
	}

	/**
	 * Replace variables in template text with context values
	 *
	 * @param string $text    Template text with variable placeholders.
	 * @param array  $context Context data for variable substitution.
	 * @return string Text with variables replaced.
	 */
	private function replace_variables( string $text, array $context ): string {
		$replacements = array(
			'{current_length}' => isset( $context['current_length'] ) ? (string) $context['current_length'] : '',
			'{min_length}' => isset( $context['min_length'] ) ? (string) $context['min_length'] : '',
			'{max_length}' => isset( $context['max_length'] ) ? (string) $context['max_length'] : '',
			'{keyword}' => isset( $context['keyword'] ) ? (string) $context['keyword'] : '',
			'{site_name}' => get_bloginfo( 'name' ),
			'{current_words}' => isset( $context['current_words'] ) ? (string) $context['current_words'] : '',
			'{min_words}' => isset( $context['min_words'] ) ? (string) $context['min_words'] : '',
			'{current_density}' => isset( $context['current_density'] ) ? (string) $context['current_density'] : '',
			'{target_min}' => isset( $context['target_min'] ) ? (string) $context['target_min'] : '',
			'{target_max}' => isset( $context['target_max'] ) ? (string) $context['target_max'] : '',
			'{count}' => isset( $context['count'] ) ? (string) $context['count'] : '',
			'{keyword_slug}' => isset( $context['keyword'] ) ? sanitize_title( $context['keyword'] ) : '',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $text );
	}
}
