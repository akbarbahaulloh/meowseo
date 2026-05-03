/**
 * Table of Contents Detection Analyzer
 *
 * @module analysis/analyzers/seo/toc-detection
 */

export function analyzeTocDetection( content ) {
	if ( ! content || content.trim() === '' ) {
		return {
			id: 'toc-detection',
			type: 'good',
			message: 'Content Structure: No content to analyze for Table of Contents.',
			score: 100,
			weight: 0.05,
			details: {},
		};
	}

	// Strip HTML tags for word count
	const textOnly = content.replace( /<[^>]+>/g, ' ' ).replace( /\s+/g, ' ' ).trim();
	const wordCount = textOnly.split( ' ' ).length;

	// If article is less than 1000 words, ToC is not strictly necessary
	if ( wordCount < 1000 ) {
		return {
			id: 'toc-detection',
			type: 'good',
			message: 'Content Structure: Article length is standard, Table of Contents is optional.',
			score: 100,
			weight: 0.05,
			details: { wordCount },
		};
	}

	// For articles >= 1000 words, check for ToC existence.
	// We look for common ToC block patterns, classes, or IDs.
	const hasToc = /class=["'][^"']*ez-toc-container/is.test( content ) ||
	               /id=["'][^"']*toc/is.test( content ) ||
	               /<!-- wp:(simpletoc\/toc|yoast\/toc|meowseo\/toc)/is.test( content );

	if ( hasToc ) {
		return {
			id: 'toc-detection',
			type: 'good',
			message: 'Content Structure: Excellent! Your long article includes a Table of Contents for better navigation.',
			score: 100,
			weight: 0.05,
			details: { wordCount, hasToc: true },
		};
	}

	return {
		id: 'toc-detection',
		type: 'ok',
		message: `Content Structure: Your article is quite long (${wordCount} words). Consider adding a Table of Contents to improve user experience.`,
		score: 60,
		weight: 0.05,
		details: { wordCount, hasToc: false },
	};
}

export default analyzeTocDetection;
