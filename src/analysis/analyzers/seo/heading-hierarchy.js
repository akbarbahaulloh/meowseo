/**
 * Heading Hierarchy Analyzer
 *
 * @module analysis/analyzers/seo/heading-hierarchy
 */

export function analyzeHeadingHierarchy( content ) {
	if ( ! content || content.trim() === '' ) {
		return {
			id: 'heading-hierarchy',
			type: 'good',
			message: 'Content Structure: No content to analyze for heading hierarchy.',
			score: 100,
			weight: 0.05,
			details: {},
		};
	}

	const headingRegex = /<h([1-6])[^>]*>(.*?)<\/h\1>/gi;
	let match;
	let headings = [];

	while ( ( match = headingRegex.exec( content ) ) !== null ) {
		headings.push( parseInt( match[1], 10 ) );
	}

	if ( headings.length === 0 ) {
		return {
			id: 'heading-hierarchy',
			type: 'problem',
			message: 'Content Structure: No headings found. Headings help structure your content.',
			score: 0,
			weight: 0.05,
			details: {},
		};
	}

	let skippedLevels = false;
	let previousLevel = headings[0];

	// Ensure the first heading isn't skipping from H1 (since H1 is title usually, first should be H2 ideally)
	// But let's just check relative hierarchy
	for ( let i = 1; i < headings.length; i++ ) {
		const currentLevel = headings[i];
		// If current is deeper than previous by more than 1 level (e.g., H2 -> H4)
		if ( currentLevel > previousLevel + 1 ) {
			skippedLevels = true;
			break;
		}
		previousLevel = currentLevel;
	}

	if ( skippedLevels ) {
		return {
			id: 'heading-hierarchy',
			type: 'problem',
			message: 'Content Structure: Your heading structure is incorrect. Do not skip heading levels (e.g. from H2 to H4 without an H3).',
			score: 0,
			weight: 0.05,
			details: { skippedLevels: true },
		};
	}

	return {
		id: 'heading-hierarchy',
		type: 'good',
		message: 'Content Structure: Great! Your heading hierarchy is logically structured.',
		score: 100,
		weight: 0.05,
		details: { skippedLevels: false },
	};
}

export default analyzeHeadingHierarchy;
