/**
 * Paragraph Length Analyzer
 *
 * Analyzes the average length of paragraphs in content.
 * Shorter paragraphs are generally easier to read and scan.
 *
 * Scoring:
 * - good (100): <150 words average
 * - ok (50): 150-200 words average
 * - problem (0): >200 words average
 *
 * @module paragraph-length
 */

import { parseHtml } from '../../utils/html-parser.js';

/**
 * Analyzes paragraph length in content
 *
 * @param {string} content - The HTML content to analyze
 * @return {Object} Analyzer result with score, type, and details
 *
 * @example
 * analyzeParagraphLength('<p>Short paragraph.</p><p>Another short one.</p>')
 * // returns {
 * //   id: 'paragraph-length',
 * //   type: 'good',
 * //   message: 'Paragraphs are well-sized (avg 3 words)',
 * //   score: 100,
 * //   weight: 0.20,
 * //   details: {
 * //     averageLength: 3,
 * //     paragraphCount: 2
 * //   }
 * // }
 */
export function analyzeParagraphLength( content ) {
	// Validate input
	if ( ! content || typeof content !== 'string' ) {
		return {
			id: 'paragraph-length',
			type: 'problem',
			message: 'Unable to analyze paragraph length',
			score: 0,
			weight: 0.2,
			details: {
				averageLength: 0,
				paragraphCount: 0,
			},
		};
	}

	// Parse HTML to extract paragraphs
	const parsed = parseHtml( content );
	const paragraphs = parsed.paragraphs;

	// If no paragraphs found, return problem
	if ( paragraphs.length === 0 ) {
		return {
			id: 'paragraph-length',
			type: 'problem',
			message: 'No paragraphs found in content',
			score: 0,
			weight: 0.2,
			details: {
				averageLength: 0,
				paragraphCount: 0,
			},
		};
	}

	// Calculate total words across all paragraphs
	let totalWords = 0;
	for ( const paragraph of paragraphs ) {
		totalWords += paragraph.wordCount;
	}

	// Calculate average paragraph length
	const averageLength =
		Math.round( ( totalWords / paragraphs.length ) * 10 ) / 10;

	// Determine score and type based on average length
	let score;
	let type;
	let message;

	if ( averageLength < 150 ) {
		score = 100;
		type = 'good';
		message = `Paragraphs are well-sized (avg ${ averageLength } words)`;
	} else if ( averageLength <= 200 ) {
		score = 50;
		type = 'ok';
		message = `Paragraph length is moderate (avg ${ averageLength } words)`;
	} else {
		score = 0;
		type = 'problem';
		message = `Paragraphs are too long (avg ${ averageLength } words). Aim for under 150 words per paragraph.`;
	}

	return {
		id: 'paragraph-length',
		type,
		message,
		score,
		weight: 0.2,
		details: {
			averageLength,
			paragraphCount: paragraphs.length,
		},
	};
}
