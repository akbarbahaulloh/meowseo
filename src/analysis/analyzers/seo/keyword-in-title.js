/**
 * Keyword in Title Analyzer
 *
 * Checks if the focus keyword appears in the SEO title.
 * Uses case-insensitive matching with Indonesian stemming support.
 *
 * @module analysis/analyzers/seo/keyword-in-title
 */

import { stemWord } from '../../utils/indonesian-stemmer.js';

/**
 * Analyzes if the focus keyword appears in the SEO title
 *
 * @param {string} title   - The SEO title to analyze
 * @param {string} keyword - The focus keyword to search for
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeKeywordInTitle('SEO Optimization Tips for Beginners', 'seo optimization')
 * // Returns: { id: 'keyword-in-title', type: 'good', message: 'Focus keyword found in title', score: 100, weight: 0.08, details: { keyword: 'seo optimization', found: true, position: 0 } }
 *
 * @example
 * analyzeKeywordInTitle('Tips for Content Writers', 'seo optimization')
 * // Returns: { id: 'keyword-in-title', type: 'problem', message: 'Add focus keyword to title', score: 0, weight: 0.08, details: { keyword: 'seo optimization', found: false, position: -1 } }
 */
export function analyzeKeywordInTitle( title, keyword ) {
	// Handle missing or empty inputs
	if ( ! keyword || keyword.trim() === '' ) {
		return {
			id: 'keyword-in-title',
			type: 'problem',
			message: 'Set a focus keyword to analyze title optimization',
			score: 0,
			weight: 0.08,
			details: {
				keyword: '',
				found: false,
				position: -1,
			},
		};
	}

	if ( ! title || title.trim() === '' ) {
		return {
			id: 'keyword-in-title',
			type: 'problem',
			message: 'Add an SEO title to your content',
			score: 0,
			weight: 0.08,
			details: {
				keyword: keyword.trim(),
				found: false,
				position: -1,
			},
		};
	}

	// Normalize inputs for comparison
	const normalizedTitle = title.toLowerCase().trim();
	const normalizedKeyword = keyword.toLowerCase().trim();

	// Check for direct match first (case-insensitive)
	let position = normalizedTitle.indexOf( normalizedKeyword );
	let found = position !== -1;

	// If not found directly, try stemmed matching for Indonesian morphological variations
	if ( ! found ) {
		// Split keyword into words and stem each
		const keywordWords = normalizedKeyword.split( /\s+/ );
		const stemmedKeywords = keywordWords.map( ( word ) =>
			stemWord( word )
		);

		// Split title into words and stem each
		const titleWords = normalizedTitle.split( /\s+/ );
		const stemmedTitleWords = titleWords.map( ( word ) =>
			stemWord( word )
		);

		// Check if all stemmed keyword words appear in stemmed title
		const stemmedKeywordPhrase = stemmedKeywords.join( ' ' );
		const stemmedTitle = stemmedTitleWords.join( ' ' );

		// Try to find the stemmed keyword in stemmed title
		const stemmedPosition = stemmedTitle.indexOf( stemmedKeywordPhrase );
		if ( stemmedPosition !== -1 ) {
			found = true;
			// For stemmed matches, we indicate position as 0 (found somewhere)
			position = 0;
		}
	}

	// Build result
	if ( found ) {
		return {
			id: 'keyword-in-title',
			type: 'good',
			message: 'Focus keyword found in title',
			score: 100,
			weight: 0.08,
			details: {
				keyword: keyword.trim(),
				found: true,
				position,
			},
		};
	}

	return {
		id: 'keyword-in-title',
		type: 'problem',
		message: 'Add focus keyword to title',
		score: 0,
		weight: 0.08,
		details: {
			keyword: keyword.trim(),
			found: false,
			position: -1,
		},
	};
}

export default analyzeKeywordInTitle;
