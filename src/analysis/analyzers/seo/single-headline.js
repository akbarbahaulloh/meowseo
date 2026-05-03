/**
 * Single Headline Analyzer
 *
 * Checks if there's more than one H1 heading.
 *
 * @module analysis/analyzers/seo/single-headline
 */

import { parseHtml } from '../../utils/html-parser.js';

export function analyzeSingleHeadline( content ) {
	if ( ! content || content.trim() === '' ) {
		return {
			id: 'single-headline',
			type: 'good',
			message: 'Single Headline: You don\'t have multiple H1 headings.',
			score: 100,
			weight: 0.05,
			details: { h1Count: 0 },
		};
	}

	const parsed = parseHtml( content );
	// Assuming parseHtml returns headings or we can use regex
	const h1Matches = content.match( /<h1[^>]*>/gi );
	const h1Count = h1Matches ? h1Matches.length : 0;

	let type, message, score;

	if ( h1Count <= 1 ) {
		type = 'good';
		message = 'Single Headline: You don\'t have multiple H1 headings.';
		score = 100;
	} else {
		type = 'problem';
		message = `Single Headline: You have ${ h1Count } H1 headings. It is best practice to use only one H1 heading per page.`;
		score = 0;
	}

	return {
		id: 'single-headline',
		type,
		message,
		score,
		weight: 0.05,
		details: { h1Count },
	};
}

export default analyzeSingleHeadline;
