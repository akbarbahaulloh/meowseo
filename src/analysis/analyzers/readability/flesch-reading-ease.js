/**
 * Flesch Reading Ease Analyzer
 *
 * Calculates the Flesch Reading Ease score adapted for Indonesian language.
 * The score indicates how easy or difficult text is to read.
 *
 * Formula adapted for Indonesian:
 * Score = 206.835 - (1.3 * words/sentences) - (0.6 * syllables/words)
 *
 * Scoring:
 * - good (100): 60-100 (easy to read)
 * - ok (50): 40-60 (moderate difficulty)
 * - problem (0): <40 (difficult to read)
 *
 * @module flesch-reading-ease
 */

import { splitSentences } from '../../utils/sentence-splitter.js';
import { countSyllables } from '../../utils/syllable-counter.js';

/**
 * Analyzes Flesch Reading Ease score for Indonesian content
 *
 * @param {string} content - The content to analyze
 * @return {Object} Analyzer result with score, type, and details
 *
 * @example
 * analyzeFleschReadingEase('Ini adalah teks yang mudah dibaca. Kalimatnya pendek.')
 * // returns {
 * //   id: 'flesch-reading-ease',
 * //   type: 'good',
 * //   message: 'Content is easy to read (Flesch score: 75)',
 * //   score: 100,
 * //   weight: 0,
 * //   details: {
 * //     fleschScore: 75,
 * //     wordCount: 10,
 * //     sentenceCount: 2,
 * //     syllableCount: 20
 * //   }
 * // }
 */
export function analyzeFleschReadingEase( content ) {
	// Validate input
	if ( ! content || typeof content !== 'string' ) {
		return {
			id: 'flesch-reading-ease',
			type: 'problem',
			message: 'Unable to analyze Flesch Reading Ease',
			score: 0,
			weight: 0,
			details: {
				fleschScore: 0,
				wordCount: 0,
				sentenceCount: 0,
				syllableCount: 0,
			},
		};
	}

	// Split content into sentences
	const sentences = splitSentences( content );

	// If no sentences found, return problem
	if ( sentences.length === 0 ) {
		return {
			id: 'flesch-reading-ease',
			type: 'problem',
			message: 'No sentences found in content',
			score: 0,
			weight: 0,
			details: {
				fleschScore: 0,
				wordCount: 0,
				sentenceCount: 0,
				syllableCount: 0,
			},
		};
	}

	// Count total words and syllables
	let totalWords = 0;
	let totalSyllables = 0;

	for ( const sentence of sentences ) {
		// Split sentence into words
		const words = sentence
			.trim()
			.split( /\s+/ )
			.filter( ( word ) => word.length > 0 );
		totalWords += words.length;

		// Count syllables for each word
		for ( const word of words ) {
			// Remove punctuation from word
			const cleanWord = word.replace( /[.,!?;:—-]+$/g, '' );
			if ( cleanWord.length > 0 ) {
				totalSyllables += countSyllables( cleanWord );
			}
		}
	}

	// If no words found, return problem
	if ( totalWords === 0 ) {
		return {
			id: 'flesch-reading-ease',
			type: 'problem',
			message: 'No words found in content',
			score: 0,
			weight: 0,
			details: {
				fleschScore: 0,
				wordCount: 0,
				sentenceCount: sentences.length,
				syllableCount: 0,
			},
		};
	}

	// Calculate Flesch Reading Ease score
	// Formula: 206.835 - (1.3 * words/sentences) - (0.6 * syllables/words)
	const fleschScore = Math.round(
		206.835 -
			1.3 * ( totalWords / sentences.length ) -
			0.6 * ( totalSyllables / totalWords )
	);

	// Clamp score to 0-100 range
	const clampedScore = Math.max( 0, Math.min( 100, fleschScore ) );

	// Determine readability level and type based on score
	let type;
	let message;
	let scoreContribution;

	if ( clampedScore >= 60 ) {
		type = 'good';
		scoreContribution = 100;
		message = `Content is easy to read (Flesch score: ${ clampedScore })`;
	} else if ( clampedScore >= 40 ) {
		type = 'ok';
		scoreContribution = 50;
		message = `Content has moderate readability (Flesch score: ${ clampedScore })`;
	} else {
		type = 'problem';
		scoreContribution = 0;
		message = `Content is difficult to read (Flesch score: ${ clampedScore }). Simplify language and shorten sentences.`;
	}

	return {
		id: 'flesch-reading-ease',
		type,
		message,
		score: scoreContribution,
		weight: 0,
		details: {
			fleschScore: clampedScore,
			wordCount: totalWords,
			sentenceCount: sentences.length,
			syllableCount: totalSyllables,
		},
	};
}
