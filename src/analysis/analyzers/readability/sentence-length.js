/**
 * Sentence Length Analyzer
 *
 * Analyzes the average length of sentences in content.
 * Shorter sentences are generally easier to read.
 *
 * Scoring:
 * - good (100): <20 words average
 * - ok (50): 20-25 words average
 * - problem (0): >25 words average
 *
 * @module sentence-length
 */

import { splitSentences } from '../../utils/sentence-splitter.js';

/**
 * Analyzes sentence length in content
 *
 * @param {string} content - The content to analyze
 * @return {Object} Analyzer result with score, type, and details
 *
 * @example
 * analyzeSentenceLength('This is a short sentence. This is another short one.')
 * // returns {
 * //   id: 'sentence-length',
 * //   type: 'good',
 * //   message: 'Sentences are concise (avg 5 words)',
 * //   score: 100,
 * //   weight: 0.20,
 * //   details: {
 * //     averageLength: 5,
 * //     sentenceCount: 2
 * //   }
 * // }
 */
export function analyzeSentenceLength( content ) {
	// Validate input
	if ( ! content || typeof content !== 'string' ) {
		return {
			id: 'sentence-length',
			type: 'problem',
			message: 'Unable to analyze sentence length',
			score: 0,
			weight: 0.2,
			details: {
				averageLength: 0,
				sentenceCount: 0,
			},
		};
	}

	// Split content into sentences
	const sentences = splitSentences( content );

	// If no sentences found, return problem
	if ( sentences.length === 0 ) {
		return {
			id: 'sentence-length',
			type: 'problem',
			message: 'No sentences found in content',
			score: 0,
			weight: 0.2,
			details: {
				averageLength: 0,
				sentenceCount: 0,
			},
		};
	}

	// Calculate total words across all sentences
	let totalWords = 0;
	for ( const sentence of sentences ) {
		// Split sentence into words (simple split on whitespace)
		const words = sentence
			.trim()
			.split( /\s+/ )
			.filter( ( word ) => word.length > 0 );
		totalWords += words.length;
	}

	// Calculate average sentence length
	const averageLength =
		Math.round( ( totalWords / sentences.length ) * 10 ) / 10;

	// Determine score and type based on average length
	let score;
	let type;
	let message;

	if ( averageLength < 20 ) {
		score = 100;
		type = 'good';
		message = `Sentences are concise (avg ${ averageLength } words)`;
	} else if ( averageLength <= 25 ) {
		score = 50;
		type = 'ok';
		message = `Sentence length is moderate (avg ${ averageLength } words)`;
	} else {
		score = 0;
		type = 'problem';
		message = `Sentences are too long (avg ${ averageLength } words). Aim for under 20 words per sentence.`;
	}

	return {
		id: 'sentence-length',
		type,
		message,
		score,
		weight: 0.2,
		details: {
			averageLength,
			sentenceCount: sentences.length,
		},
	};
}
