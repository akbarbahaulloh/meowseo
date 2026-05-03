/**
 * Keyphrase Length Analyzer
 *
 * @module analysis/analyzers/seo/keyword-length
 */

export function analyzeKeywordLength( keyword ) {
	if ( ! keyword || keyword.trim() === '' ) {
		return {
			id: 'keyword-length',
			type: 'problem',
			message: 'Keyphrase Length: No focus keyphrase was set for this page.',
			score: 0,
			weight: 0.05,
			details: { length: 0 },
		};
	}

	const normalizedKeyword = keyword.trim();
	const words = normalizedKeyword.split( /\s+/ ).length;

	let type, message, score;

	if ( words <= 4 ) {
		type = 'good';
		message = 'Keyphrase Length: Good job!';
		score = 100;
	} else if ( words <= 6 ) {
		type = 'ok';
		message = 'Keyphrase Length: The keyphrase is slightly long. Consider shortening it to 4 words or less.';
		score = 50;
	} else {
		type = 'problem';
		message = 'Keyphrase Length: The keyphrase is too long. Consider shortening it.';
		score = 0;
	}

	return {
		id: 'keyword-length',
		type,
		message,
		score,
		weight: 0.05,
		details: { length: words },
	};
}

export default analyzeKeywordLength;
