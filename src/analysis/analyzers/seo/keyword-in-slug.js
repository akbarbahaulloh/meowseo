/**
 * Keyword in Slug Analyzer
 *
 * Checks if the focus keyword appears in the URL slug.
 * Uses case-insensitive matching.
 *
 * @module analysis/analyzers/seo/keyword-in-slug
 */

/**
 * Analyzes if the focus keyword appears in the URL slug
 *
 * @param {string} slug    - The URL slug to analyze
 * @param {string} keyword - The focus keyword to search for
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeKeywordInSlug('seo-optimization-guide', 'seo optimization')
 * // Returns: { id: 'keyword-in-slug', type: 'good', message: 'Focus keyword found in URL slug', score: 100, weight: 0.07, details: { keyword: 'seo optimization', found: true } }
 */
export function analyzeKeywordInSlug( slug, keyword ) {
	// Handle missing or empty inputs
	if ( ! keyword || keyword.trim() === '' ) {
		return {
			id: 'keyword-in-slug',
			type: 'problem',
			message: 'Set a focus keyword to analyze slug optimization',
			score: 0,
			weight: 0.07,
			details: {
				keyword: '',
				found: false,
			},
		};
	}

	if ( ! slug || slug.trim() === '' ) {
		return {
			id: 'keyword-in-slug',
			type: 'problem',
			message: 'Add a URL slug to your content',
			score: 0,
			weight: 0.07,
			details: {
				keyword: keyword.trim(),
				found: false,
			},
		};
	}

	// Normalize for comparison
	// Slugs typically use hyphens, so we normalize both
	const normalizedSlug = slug.toLowerCase().replace( /-/g, ' ' ).trim();
	const normalizedKeyword = keyword.toLowerCase().trim();

	// Check if keyword appears in slug
	const found = normalizedSlug.includes( normalizedKeyword );

	// Build result
	if ( found ) {
		return {
			id: 'keyword-in-slug',
			type: 'good',
			message: 'Focus keyword found in URL slug',
			score: 100,
			weight: 0.07,
			details: {
				keyword: keyword.trim(),
				found: true,
			},
		};
	}

	return {
		id: 'keyword-in-slug',
		type: 'problem',
		message: 'Add focus keyword to URL slug',
		score: 0,
		weight: 0.07,
		details: {
			keyword: keyword.trim(),
			found: false,
		},
	};
}

export default analyzeKeywordInSlug;
