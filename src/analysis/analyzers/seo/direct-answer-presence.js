/**
 * Direct Answer Presence Analyzer
 *
 * Checks if the Direct Answer field is populated for Google AI Overviews.
 * Optimal length: 300-450 characters
 *
 * @module analysis/analyzers/seo/direct-answer-presence
 */

/**
 * Analyzes Direct Answer field presence and length
 *
 * @param {string} directAnswer - The Direct Answer field content
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeDirectAnswer('SEO optimization is the process of improving your website to rank higher in search results.')
 * // Returns: { id: 'direct-answer-presence', type: 'good', message: 'Direct Answer configured for AI Overviews', score: 100, weight: 0.06, details: { characterCount: 85 } }
 */
export function analyzeDirectAnswer( directAnswer ) {
	// Handle missing or empty direct answer
	if ( ! directAnswer || directAnswer.trim() === '' ) {
		return {
			id: 'direct-answer-presence',
			type: 'problem',
			message:
				'Add a Direct Answer (300-450 characters) for Google AI Overviews',
			score: 0,
			weight: 0.06,
			details: {
				characterCount: 0,
			},
		};
	}

	const characterCount = directAnswer.trim().length;

	// Determine status
	// Good: present and 300-450 characters
	// Ok: present but outside character range
	// Problem: missing
	let type, message, score;

	if ( characterCount >= 300 && characterCount <= 450 ) {
		type = 'good';
		message = `Direct Answer configured for AI Overviews (${ characterCount } characters)`;
		score = 100;
	} else if ( characterCount > 0 ) {
		type = 'ok';
		if ( characterCount < 300 ) {
			message = `Direct Answer is too short (${ characterCount } characters). Aim for 300-450 characters.`;
		} else {
			message = `Direct Answer is too long (${ characterCount } characters). Keep it under 450 characters.`;
		}
		score = 50;
	} else {
		type = 'problem';
		message =
			'Add a Direct Answer (300-450 characters) for Google AI Overviews';
		score = 0;
	}

	return {
		id: 'direct-answer-presence',
		type,
		message,
		score,
		weight: 0.06,
		details: {
			characterCount,
		},
	};
}

export default analyzeDirectAnswer;
