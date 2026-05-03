/**
 * Meta Description Length Analyzer
 *
 * Checks if the meta description length is optimal.
 *
 * @module analysis/analyzers/seo/meta-description-length
 */

export function analyzeMetaDescriptionLength( description ) {
	if ( ! description || description.trim() === '' ) {
		return {
			id: 'meta-description-length',
			type: 'problem',
			message: 'Meta Description Length: No meta description has been specified. Search engines will display copy from the page instead.',
			score: 0,
			weight: 0.05,
			details: { length: 0 },
		};
	}

	const length = description.trim().length;

	let type, message, score;

	if ( length > 120 && length <= 155 ) {
		type = 'good';
		message = 'Meta Description Length: Well done!';
		score = 100;
	} else if ( length > 155 ) {
		type = 'problem';
		message = `Meta Description Length: The meta description is too long at ${ length } characters. To ensure the entire description is visible, you should make it shorter!`;
		score = 0;
	} else {
		type = 'problem';
		message = `Meta Description Length: The meta description is under 120 characters long. However, up to 155 characters are available.`;
		score = 0;
	}

	return {
		id: 'meta-description-length',
		type,
		message,
		score,
		weight: 0.05,
		details: { length },
	};
}

export default analyzeMetaDescriptionLength;
