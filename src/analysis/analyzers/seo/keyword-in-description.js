/**
 * Keyword in Description Analyzer
 *
 * Checks if the focus keyword appears in the meta description.
 * Uses case-insensitive matching with Indonesian stemming support.
 *
 * @module analysis/analyzers/seo/keyword-in-description
 */

import { stemWord } from '../../utils/indonesian-stemmer.js';

/**
 * Analyzes if the focus keyword appears in the meta description
 *
 * @param {string} description - The meta description to analyze
 * @param {string} keyword     - The focus keyword to search for
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeKeywordInDescription('Learn SEO optimization techniques for better rankings', 'seo optimization')
 * // Returns: { id: 'keyword-in-description', type: 'good', message: 'Focus keyword found in description', score: 100, weight: 0.07, details: { keyword: 'seo optimization', found: true, position: 6 } }
 */
export function analyzeKeywordInDescription( description, keyword ) {
	// Handle missing or empty inputs
	if ( ! keyword || keyword.trim() === '' ) {
		return {
			id: 'keyword-in-description',
			type: 'problem',
			message: 'Set a focus keyword to analyze description optimization',
			score: 0,
			weight: 0.07,
			details: {
				keyword: '',
				found: false,
				position: -1,
			},
		};
	}

	if ( ! description || description.trim() === '' ) {
		return {
			id: 'keyword-in-description',
			type: 'problem',
			message: 'Add a meta description to your content',
			score: 0,
			weight: 0.07,
			details: {
				keyword: keyword.trim(),
				found: false,
				position: -1,
			},
		};
	}

	// Normalize inputs for comparison
	const normalizedDescription = description.toLowerCase().trim();
	const normalizedKeyword = keyword.toLowerCase().trim();

	// Check for direct match first (case-insensitive)
	let position = normalizedDescription.indexOf( normalizedKeyword );
	let found = position !== -1;

	// If not found directly, try stemmed matching for Indonesian morphological variations
	if ( ! found ) {
		const keywordWords = normalizedKeyword.split( /\s+/ );
		const stemmedKeywords = keywordWords.map( ( word ) =>
			stemWord( word )
		);

		const descriptionWords = normalizedDescription.split( /\s+/ );
		const stemmedDescriptionWords = descriptionWords.map( ( word ) =>
			stemWord( word )
		);

		const stemmedKeywordPhrase = stemmedKeywords.join( ' ' );
		const stemmedDescription = stemmedDescriptionWords.join( ' ' );

		const stemmedPosition =
			stemmedDescription.indexOf( stemmedKeywordPhrase );
		if ( stemmedPosition !== -1 ) {
			found = true;
			position = 0;
		}
	}

	// Build result
	if ( found ) {
		return {
			id: 'keyword-in-description',
			type: 'good',
			message: 'Focus keyword found in description',
			score: 100,
			weight: 0.07,
			details: {
				keyword: keyword.trim(),
				found: true,
				position,
			},
		};
	}

	return {
		id: 'keyword-in-description',
		type: 'problem',
		message: 'Add focus keyword to description',
		score: 0,
		weight: 0.07,
		details: {
			keyword: keyword.trim(),
			found: false,
			position: -1,
		},
	};
}

export default analyzeKeywordInDescription;
