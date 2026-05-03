/**
 * Keyword in Headings Analyzer
 *
 * Checks if the focus keyword appears in H2/H3 headings.
 * Uses case-insensitive matching with Indonesian stemming support.
 *
 * @module analysis/analyzers/seo/keyword-in-headings
 */

import { stemWord } from '../../utils/indonesian-stemmer.js';
import { parseHtml } from '../../utils/html-parser.js';

/**
 * Analyzes if the focus keyword appears in H2/H3 headings
 *
 * @param {string} content - The HTML content to analyze
 * @param {string} keyword - The focus keyword to search for
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeKeywordInHeadings('<h2>SEO Optimization Guide</h2><p>Content here.</p>', 'seo optimization')
 * // Returns: { id: 'keyword-in-headings', type: 'good', message: 'Focus keyword found in headings', score: 100, weight: 0.08, details: { headingCount: 1, headingsWithKeyword: 1 } }
 */
export function analyzeKeywordInHeadings( content, keyword ) {
	// Handle missing or empty inputs
	if ( ! keyword || keyword.trim() === '' ) {
		return {
			id: 'keyword-in-headings',
			type: 'problem',
			message: 'Set a focus keyword to analyze heading optimization',
			score: 0,
			weight: 0.08,
			details: {
				keyword: '',
				headingCount: 0,
				headingsWithKeyword: 0,
			},
		};
	}

	if ( ! content || content.trim() === '' ) {
		return {
			id: 'keyword-in-headings',
			type: 'problem',
			message: 'Add content with headings to analyze',
			score: 0,
			weight: 0.08,
			details: {
				keyword: keyword.trim(),
				headingCount: 0,
				headingsWithKeyword: 0,
			},
		};
	}

	// Parse HTML and extract headings
	const parsed = parseHtml( content );
	const headings = parsed.headings;
	const text = parsed.text;

	const headingCount = headings.length;
	const normalizedKeyword = keyword.toLowerCase().trim();
	const keywordWords = normalizedKeyword.split( /\s+/ );
	const stemmedKeywords = keywordWords.map( ( w ) => stemWord( w ) );
	const stemmedKeywordPhrase = stemmedKeywords.join( ' ' );

	// Check if keyword appears in content (for 'ok' status)
	const normalizedText = text.toLowerCase();
	const keywordInContent = normalizedText.includes( normalizedKeyword );

	// Count headings with keyword
	let headingsWithKeyword = 0;

	for ( const heading of headings ) {
		const normalizedHeading = heading.text.toLowerCase();

		// Direct match
		if ( normalizedHeading.includes( normalizedKeyword ) ) {
			headingsWithKeyword++;
			continue;
		}

		// Stemmed match
		const headingWords = normalizedHeading.split( /\s+/ );
		const stemmedHeadingWords = headingWords.map( ( w ) => stemWord( w ) );
		const stemmedHeading = stemmedHeadingWords.join( ' ' );

		if ( stemmedHeading.includes( stemmedKeywordPhrase ) ) {
			headingsWithKeyword++;
		}
	}

	// Determine status
	// Good: keyword in at least one heading
	// Ok: keyword in content but not headings
	// Problem: keyword missing from content
	let type, message, score;

	if ( headingsWithKeyword > 0 ) {
		type = 'good';
		message = `Keyphrase in Subheading: ${ headingsWithKeyword } of your H2 and H3 subheadings reflect the topic of your copy.`;
		score = 100;
	} else if ( headingCount > 0 ) {
		type = 'ok';
		message =
			'Keyphrase in Subheading: Your keyphrase or its synonyms do not appear in any H2 and H3 subheadings. Add some!';
		score = 50;
	} else {
		type = 'problem';
		message =
			'Keyphrase in Subheading: You have not used any H2 or H3 subheadings. Use some to break up your text!';
		score = 0;
	}

	return {
		id: 'keyword-in-headings',
		type,
		message,
		score,
		weight: 0.08,
		details: {
			keyword: keyword.trim(),
			headingCount,
			headingsWithKeyword,
		},
	};
}

export default analyzeKeywordInHeadings;
