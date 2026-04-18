/**
 * Subheading Distribution Analyzer
 *
 * Analyzes the spacing between H2/H3 headings in content.
 * Well-distributed headings improve content scannability and structure.
 *
 * Scoring:
 * - good (100): Headings appear every <300 words
 * - ok (50): Headings appear every 300-400 words
 * - problem (0): Headings appear every >400 words
 *
 * @module subheading-distribution
 */

import { parseHtml } from '../../utils/html-parser.js';

/**
 * Analyzes subheading distribution in content
 *
 * @param {string} content - The HTML content to analyze
 * @return {Object} Analyzer result with score, type, and details
 *
 * @example
 * analyzeSubheadingDistribution('<h2>Section 1</h2><p>Content here.</p><h2>Section 2</h2><p>More content.</p>')
 * // returns {
 * //   id: 'subheading-distribution',
 * //   type: 'good',
 * //   message: 'Headings are well-distributed (avg 5 words between headings)',
 * //   score: 100,
 * //   weight: 0.20,
 * //   details: {
 * //     averageSpacing: 5,
 * //     headingCount: 2
 * //   }
 * // }
 */
export function analyzeSubheadingDistribution( content ) {
	// Validate input
	if ( ! content || typeof content !== 'string' ) {
		return {
			id: 'subheading-distribution',
			type: 'problem',
			message: 'Unable to analyze subheading distribution',
			score: 0,
			weight: 0.2,
			details: {
				averageSpacing: 0,
				headingCount: 0,
			},
		};
	}

	// Parse HTML to extract headings and text
	const parsed = parseHtml( content );
	const headings = parsed.headings;
	const fullText = parsed.text;

	// If fewer than 2 headings, we can't calculate spacing
	if ( headings.length < 2 ) {
		return {
			id: 'subheading-distribution',
			type: 'problem',
			message: 'Not enough headings to analyze distribution',
			score: 0,
			weight: 0.2,
			details: {
				averageSpacing: 0,
				headingCount: headings.length,
			},
		};
	}

	// Calculate spacing between headings
	const spacings = [];

	for ( let i = 0; i < headings.length - 1; i++ ) {
		const currentHeading = headings[ i ];
		const nextHeading = headings[ i + 1 ];

		// Extract text between headings
		const startPos = currentHeading.position + currentHeading.text.length;
		const endPos = nextHeading.position;
		const betweenText = fullText.substring( startPos, endPos );

		// Count words between headings
		const words = betweenText
			.split( /\s+/ )
			.filter( ( w ) => w.length > 0 );
		spacings.push( words.length );
	}

	// Calculate average spacing
	const averageSpacing = Math.round(
		spacings.reduce( ( a, b ) => a + b, 0 ) / spacings.length
	);

	// Determine score and type based on average spacing
	let score;
	let type;
	let message;

	if ( averageSpacing < 300 ) {
		score = 100;
		type = 'good';
		message = `Headings are well-distributed (avg ${ averageSpacing } words between headings)`;
	} else if ( averageSpacing <= 400 ) {
		score = 50;
		type = 'ok';
		message = `Heading distribution is moderate (avg ${ averageSpacing } words between headings)`;
	} else {
		score = 0;
		type = 'problem';
		message = `Headings are too far apart (avg ${ averageSpacing } words). Aim for headings every 300 words or less.`;
	}

	return {
		id: 'subheading-distribution',
		type,
		message,
		score,
		weight: 0.2,
		details: {
			averageSpacing,
			headingCount: headings.length,
		},
	};
}
