/**
 * Direct Answer Paragraph Analyzer
 *
 * @module analysis/analyzers/seo/direct-answer-paragraph
 */

export function analyzeDirectAnswerParagraph( content, directAnswerText ) {
	// If the user explicitly provided a direct answer, we assume it's good (if length is decent).
	if ( directAnswerText && directAnswerText.trim() !== '' ) {
		const words = directAnswerText.trim().split( /\s+/ ).length;
		if ( words >= 30 && words <= 60 ) {
			return {
				id: 'direct-answer-paragraph',
				type: 'good',
				message: 'Featured Snippet: You have a well-sized direct answer configured.',
				score: 100,
				weight: 0.05,
				details: { wordCount: words },
			};
		} else {
			return {
				id: 'direct-answer-paragraph',
				type: 'ok',
				message: `Featured Snippet: Your direct answer is ${words} words. The optimal length for a featured snippet is 40-50 words.`,
				score: 60,
				weight: 0.05,
				details: { wordCount: words },
			};
		}
	}

	if ( ! content || content.trim() === '' ) {
		return {
			id: 'direct-answer-paragraph',
			type: 'problem',
			message: 'Featured Snippet: No content to analyze for a direct answer.',
			score: 0,
			weight: 0.05,
			details: {},
		};
	}

	// Try to find a paragraph that is between 40 and 60 words
	const paragraphs = content.match( /<p[^>]*>(.*?)<\/p>/gi );
	if ( ! paragraphs ) {
		return {
			id: 'direct-answer-paragraph',
			type: 'problem',
			message: 'Featured Snippet: Add a concise paragraph (40-50 words) to increase the chance of getting a featured snippet.',
			score: 0,
			weight: 0.05,
			details: {},
		};
	}

	let hasOptimalParagraph = false;
	for ( const p of paragraphs ) {
		// Strip tags
		const text = p.replace( /<[^>]+>/g, '' ).trim();
		if ( ! text ) continue;

		const words = text.split( /\s+/ ).length;
		if ( words >= 40 && words <= 60 ) {
			hasOptimalParagraph = true;
			break;
		}
	}

	if ( hasOptimalParagraph ) {
		return {
			id: 'direct-answer-paragraph',
			type: 'good',
			message: 'Featured Snippet: You have a paragraph of optimal length (40-60 words) that may serve as a featured snippet.',
			score: 100,
			weight: 0.05,
			details: {},
		};
	}

	return {
		id: 'direct-answer-paragraph',
		type: 'ok',
		message: 'Featured Snippet: Consider adding a concise 40-50 word paragraph early in the content to directly answer the topic.',
		score: 50,
		weight: 0.05,
		details: {},
	};
}

export default analyzeDirectAnswerParagraph;
