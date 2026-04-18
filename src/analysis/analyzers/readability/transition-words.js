/**
 * Transition Words Analyzer
 *
 * Detects transition words in Indonesian content to measure content flow.
 * Transition words connect ideas between sentences and improve readability.
 *
 * Scoring:
 * - good (100): >30% of sentences contain transition words
 * - ok (50): 20-30% of sentences contain transition words
 * - problem (0): <20% of sentences contain transition words
 *
 * @module transition-words
 */

import { splitSentences } from '../../utils/sentence-splitter.js';

/**
 * Indonesian transition words and phrases
 * These words help connect ideas and improve content flow
 */
const TRANSITION_WORDS = [
	// Additive transitions
	'dan',
	'juga',
	'selain itu',
	'tambahan',
	'lagi',
	'pula',
	'lagipula',

	// Contrast transitions
	'namun',
	'tetapi',
	'tapi',
	'akan tetapi',
	'sebaliknya',
	'berbeda',
	'meskipun',
	'walaupun',
	'padahal',

	// Causal transitions
	'karena',
	'sebab',
	'oleh karena itu',
	'akibatnya',
	'hasilnya',
	'maka',
	'jadi',
	'dengan demikian',

	// Sequential transitions
	'kemudian',
	'lalu',
	'selanjutnya',
	'berikutnya',
	'setelah itu',
	'sebelumnya',
	'pertama',
	'kedua',
	'ketiga',
	'akhirnya',
	'terakhir',
	'pada akhirnya',

	// Exemplifying transitions
	'misalnya',
	'contohnya',
	'seperti',
	'sebagai contoh',
	'untuk contoh',
	'yakni',
	'yaitu',

	// Emphasizing transitions
	'tentu saja',
	'jelas',
	'nyata',
	'penting',
	'terutama',
	'khususnya',
	'sangat',
	'amat',

	// Concluding transitions
	'kesimpulannya',
	'singkatnya',
	'ringkasnya',
	'pada intinya',
	'dengan kata lain',
	'dengan singkat',

	// Transitional phrases
	'di sisi lain',
	'di pihak lain',
	'di satu pihak',
	'di pihak yang lain',
	'sebaliknya',
	'sebagai hasilnya',
	'sebagai akibatnya',
	'sebagai konsekuensinya',
];

/**
 * Analyzes transition word usage in content
 *
 * @param {string} content - The content to analyze
 * @return {Object} Analyzer result with score, type, and details
 *
 * @example
 * analyzeTransitionWords('Pertama, kami membuat rencana. Kemudian, kami melaksanakannya.')
 * // returns {
 * //   id: 'transition-words',
 * //   type: 'good',
 * //   message: 'Good use of transition words (100%)',
 * //   score: 100,
 * //   weight: 0.20,
 * //   details: {
 * //     transitionPercentage: 100,
 * //     sentenceCount: 2,
 * //     sentencesWithTransitions: 2
 * //   }
 * // }
 */
export function analyzeTransitionWords( content ) {
	// Validate input
	if ( ! content || typeof content !== 'string' ) {
		return {
			id: 'transition-words',
			type: 'problem',
			message: 'Unable to analyze transition words',
			score: 0,
			weight: 0.2,
			details: {
				transitionPercentage: 0,
				sentenceCount: 0,
				sentencesWithTransitions: 0,
			},
		};
	}

	// Split content into sentences
	const sentences = splitSentences( content );

	// If no sentences found, return problem
	if ( sentences.length === 0 ) {
		return {
			id: 'transition-words',
			type: 'problem',
			message: 'No sentences found in content',
			score: 0,
			weight: 0.2,
			details: {
				transitionPercentage: 0,
				sentenceCount: 0,
				sentencesWithTransitions: 0,
			},
		};
	}

	// Count sentences with transition words
	let sentencesWithTransitions = 0;

	for ( const sentence of sentences ) {
		if ( hasTransitionWord( sentence ) ) {
			sentencesWithTransitions++;
		}
	}

	// Calculate transition word percentage
	const transitionPercentage = Math.round(
		( sentencesWithTransitions / sentences.length ) * 100
	);

	// Determine score and type based on percentage
	let score;
	let type;
	let message;

	if ( transitionPercentage > 30 ) {
		score = 100;
		type = 'good';
		message = `Good use of transition words (${ transitionPercentage }%). Content flows well!`;
	} else if ( transitionPercentage >= 20 ) {
		score = 50;
		type = 'ok';
		message = `Moderate use of transition words (${ transitionPercentage }%). Try to add more.`;
	} else {
		score = 0;
		type = 'problem';
		message = `Low use of transition words (${ transitionPercentage }%). Aim for more than 20% to improve flow.`;
	}

	return {
		id: 'transition-words',
		type,
		message,
		score,
		weight: 0.2,
		details: {
			transitionPercentage,
			sentenceCount: sentences.length,
			sentencesWithTransitions,
		},
	};
}

/**
 * Checks if a sentence contains transition words
 *
 * @param {string} sentence - The sentence to check
 * @return {boolean} True if sentence contains transition words
 */
function hasTransitionWord( sentence ) {
	if ( ! sentence || typeof sentence !== 'string' ) {
		return false;
	}

	// Convert to lowercase for matching
	const lowerSentence = sentence.toLowerCase();

	// Check for each transition word
	for ( const word of TRANSITION_WORDS ) {
		// Create a regex pattern that matches the word as a whole word
		// Use word boundaries to avoid partial matches
		const pattern = new RegExp( `\\b${ word }\\b`, 'i' );
		if ( pattern.test( lowerSentence ) ) {
			return true;
		}
	}

	return false;
}
