/**
 * Passive Voice Analyzer
 *
 * Detects passive voice usage in Indonesian content using language-specific patterns.
 * Indonesian passive voice is typically formed with prefixes: di-, ter-, ke-an
 *
 * Scoring:
 * - good (100): <10% passive voice
 * - ok (50): 10-15% passive voice
 * - problem (0): >15% passive voice
 *
 * @module passive-voice
 */

import { splitSentences } from '../../utils/sentence-splitter.js';

/**
 * Indonesian passive voice patterns
 * These prefixes indicate passive voice construction
 * Note: Only di- is a reliable passive voice marker in Indonesian
 * ter- and ke- can be used in both active and passive contexts
 */
const PASSIVE_PATTERNS = [
	/^di[a-z]/, // di- prefix (dibuat, diambil, dll) - most reliable passive marker
];

/**
 * Additional passive voice indicators (oleh = by, in passive constructions)
 */
const PASSIVE_INDICATORS = [ 'oleh' ];

/**
 * Analyzes passive voice usage in content
 *
 * @param {string} content - The content to analyze
 * @return {Object} Analyzer result with score, type, and details
 *
 * @example
 * analyzePassiveVoice('Buku dibaca oleh anak. Anak membaca buku.')
 * // returns {
 * //   id: 'passive-voice',
 * //   type: 'ok',
 * //   message: 'Passive voice usage is moderate (50%)',
 * //   score: 50,
 * //   weight: 0.20,
 * //   details: {
 * //     passivePercentage: 50,
 * //     sentenceCount: 2,
 * //     passiveCount: 1
 * //   }
 * // }
 */
export function analyzePassiveVoice( content ) {
	// Validate input
	if ( ! content || typeof content !== 'string' ) {
		return {
			id: 'passive-voice',
			type: 'problem',
			message: 'Unable to analyze passive voice',
			score: 0,
			weight: 0.2,
			details: {
				passivePercentage: 0,
				sentenceCount: 0,
				passiveCount: 0,
			},
		};
	}

	// Split content into sentences
	const sentences = splitSentences( content );

	// If no sentences found, return problem
	if ( sentences.length === 0 ) {
		return {
			id: 'passive-voice',
			type: 'problem',
			message: 'No sentences found in content',
			score: 0,
			weight: 0.2,
			details: {
				passivePercentage: 0,
				sentenceCount: 0,
				passiveCount: 0,
			},
		};
	}

	// Count passive voice sentences
	let passiveCount = 0;

	for ( const sentence of sentences ) {
		if ( isPassiveVoiceSentence( sentence ) ) {
			passiveCount++;
		}
	}

	// Calculate passive voice percentage
	const passivePercentage = Math.round(
		( passiveCount / sentences.length ) * 100
	);

	// Determine score and type based on percentage
	let score;
	let type;
	let message;

	if ( passivePercentage < 10 ) {
		score = 100;
		type = 'good';
		message = `Passive voice usage is low (${ passivePercentage }%). Good job using active voice!`;
	} else if ( passivePercentage <= 15 ) {
		score = 50;
		type = 'ok';
		message = `Passive voice usage is moderate (${ passivePercentage }%). Try to use more active voice.`;
	} else {
		score = 0;
		type = 'problem';
		message = `Passive voice usage is high (${ passivePercentage }%). Aim for less than 10% passive voice.`;
	}

	return {
		id: 'passive-voice',
		type,
		message,
		score,
		weight: 0.2,
		details: {
			passivePercentage,
			sentenceCount: sentences.length,
			passiveCount,
		},
	};
}

/**
 * Checks if a sentence contains passive voice
 *
 * @param {string} sentence - The sentence to check
 * @return {boolean} True if sentence contains passive voice
 */
function isPassiveVoiceSentence( sentence ) {
	if ( ! sentence || typeof sentence !== 'string' ) {
		return false;
	}

	// Convert to lowercase for matching
	const lowerSentence = sentence.toLowerCase();

	// Check for passive indicators (oleh = by) with word boundaries
	for ( const indicator of PASSIVE_INDICATORS ) {
		const pattern = new RegExp( `\\b${ indicator }\\b` );
		if ( pattern.test( lowerSentence ) ) {
			return true;
		}
	}

	// Extract words from sentence
	const words = lowerSentence
		.split( /\s+/ )
		.filter( ( word ) => word.length > 0 );

	// Check if any word matches passive voice patterns
	for ( const word of words ) {
		// Remove punctuation from word
		const cleanWord = word.replace( /[.,!?;:—-]+$/g, '' );

		// Check against passive patterns
		for ( const pattern of PASSIVE_PATTERNS ) {
			if ( pattern.test( cleanWord ) ) {
				return true;
			}
		}
	}

	return false;
}
