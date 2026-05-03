/**
 * SEO Title Length Analyzer
 *
 * Checks if the SEO title length is optimal.
 *
 * @module analysis/analyzers/seo/title-length
 */

export function analyzeTitleLength( title ) {
	if ( ! title || title.trim() === '' ) {
		return {
			id: 'title-length',
			type: 'problem',
			message: 'SEO Title Length: Please create an SEO title.',
			score: 0,
			weight: 0.05,
			details: { length: 0 },
		};
	}

	const length = title.trim().length;

	let type, message, score;

	if ( length > 30 && length <= 60 ) {
		type = 'good';
		message = 'SEO Title Length: Excellent!';
		score = 100;
	} else if ( length > 60 ) {
		type = 'problem';
		message = `SEO Title Length: The SEO title is too long. To ensure the entire title is visible, you should make it shorter!`;
		score = 0;
	} else {
		type = 'problem';
		message = `SEO Title Length: The SEO title is too short. Use the space to add keyword variations or create compelling copy.`;
		score = 0;
	}

	return {
		id: 'title-length',
		type,
		message,
		score,
		weight: 0.05,
		details: { length },
	};
}

export default analyzeTitleLength;
