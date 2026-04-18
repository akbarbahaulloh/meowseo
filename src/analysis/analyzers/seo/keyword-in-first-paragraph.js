/**
 * Keyword in First Paragraph Analyzer
 *
 * Checks if the focus keyword appears in the first 100 words of content.
 * Uses case-insensitive matching with Indonesian stemming support.
 *
 * @module analysis/analyzers/seo/keyword-in-first-paragraph
 */

import { stemWord } from '../../utils/indonesian-stemmer.js';
import { parseHtml } from '../../utils/html-parser.js';

/**
 * Analyzes if the focus keyword appears in the first 100 words of content
 *
 * @param {string} content - The HTML content to analyze
 * @param {string} keyword - The focus keyword to search for
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeKeywordInFirstParagraph('<p>SEO optimization is crucial for rankings.</p>', 'seo optimization')
 * // Returns: { id: 'keyword-in-first-paragraph', type: 'good', message: 'Focus keyword found in first 100 words', score: 100, weight: 0.08, details: { keyword: 'seo optimization', found: true, wordCount: 50 } }
 */
export function analyzeKeywordInFirstParagraph( content, keyword ) {
	// Handle missing or empty inputs
	if ( ! keyword || keyword.trim() === '' ) {
		return {
			id: 'keyword-in-first-paragraph',
			type: 'problem',
			message:
				'Set a focus keyword to analyze first paragraph optimization',
			score: 0,
			weight: 0.08,
			details: {
				keyword: '',
				found: false,
				wordCount: 0,
			},
		};
	}

	if ( ! content || content.trim() === '' ) {
		return {
			id: 'keyword-in-first-paragraph',
			type: 'problem',
			message: 'Add content to analyze first paragraph',
			score: 0,
			weight: 0.08,
			details: {
				keyword: keyword.trim(),
				found: false,
				wordCount: 0,
			},
		};
	}

	// Parse HTML and extract text
	const parsed = parseHtml( content );
	const fullText = parsed.text;

	// Get first 100 words
	const allWords = fullText.split( /\s+/ ).filter( ( w ) => w.length > 0 );
	const firstHundredWords = allWords.slice( 0, 100 ).join( ' ' );
	const wordCount = Math.min( allWords.length, 100 );

	// Normalize for comparison
	const normalizedFirstParagraph = firstHundredWords.toLowerCase();
	const normalizedKeyword = keyword.toLowerCase().trim();

	// Check for direct match first
	let found = normalizedFirstParagraph.includes( normalizedKeyword );

	// If not found directly, try stemmed matching
	if ( ! found ) {
		const keywordWords = normalizedKeyword.split( /\s+/ );
		const stemmedKeywords = keywordWords.map( ( word ) =>
			stemWord( word )
		);

		const paragraphWords = normalizedFirstParagraph.split( /\s+/ );
		const stemmedParagraphWords = paragraphWords.map( ( word ) =>
			stemWord( word )
		);

		const stemmedKeywordPhrase = stemmedKeywords.join( ' ' );
		const stemmedParagraph = stemmedParagraphWords.join( ' ' );

		found = stemmedParagraph.includes( stemmedKeywordPhrase );
	}

	// Build result
	if ( found ) {
		return {
			id: 'keyword-in-first-paragraph',
			type: 'good',
			message: 'Focus keyword found in first 100 words',
			score: 100,
			weight: 0.08,
			details: {
				keyword: keyword.trim(),
				found: true,
				wordCount,
			},
		};
	}

	return {
		id: 'keyword-in-first-paragraph',
		type: 'problem',
		message: 'Add focus keyword to first paragraph',
		score: 0,
		weight: 0.08,
		details: {
			keyword: keyword.trim(),
			found: false,
			wordCount,
		},
	};
}

export default analyzeKeywordInFirstParagraph;
