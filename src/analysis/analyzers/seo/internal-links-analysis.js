/**
 * Internal Links Analysis Analyzer
 *
 * Analyzes internal links in content for quantity and anchor text quality.
 *
 * @module analysis/analyzers/seo/internal-links-analysis
 */

import { parseHtml } from '../../utils/html-parser.js';

/**
 * Analyzes internal links in content
 *
 * @param {string} content - The HTML content to analyze
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeInternalLinks('<p>Check our <a href="/guide">SEO optimization guide</a> for more tips.</p>')
 * // Returns: { id: 'internal-links-analysis', type: 'good', message: 'Good internal linking structure', score: 100, weight: 0.08, details: { totalLinks: 1, descriptiveLinks: 1 } }
 */
export function analyzeInternalLinks( content ) {
	// Handle missing or empty content
	if ( ! content || content.trim() === '' ) {
		return {
			id: 'internal-links-analysis',
			type: 'problem',
			message: 'Add content to analyze internal links',
			score: 0,
			weight: 0.08,
			details: {
				totalLinks: 0,
				descriptiveLinks: 0,
			},
		};
	}

	// Parse HTML and extract links
	const parsed = parseHtml( content );
	const links = parsed.links;

	// Filter internal links
	const internalLinks = links.filter( ( link ) => link.isInternal );
	const totalLinks = internalLinks.length;

	// Count links with descriptive anchor text
	const descriptiveLinks = internalLinks.filter(
		( link ) => link.isDescriptive
	).length;

	// Determine status
	// Good: >3 internal links with descriptive text
	// Ok: 1-3 internal links with descriptive text
	// Problem: <1 internal link or all generic anchor text
	let type, message, score;

	if ( totalLinks === 0 ) {
		type = 'problem';
		message = 'Add internal links to connect your content';
		score = 0;
	} else if ( descriptiveLinks > 3 ) {
		type = 'good';
		message = `Good internal linking structure (${ descriptiveLinks } descriptive links)`;
		score = 100;
	} else if ( descriptiveLinks >= 1 ) {
		type = 'ok';
		message = `Add more internal links with descriptive text (${ descriptiveLinks } found)`;
		score = 50;
	} else {
		type = 'problem';
		message =
			'Use descriptive anchor text for internal links instead of generic text like "click here"';
		score = 0;
	}

	return {
		id: 'internal-links-analysis',
		type,
		message,
		score,
		weight: 0.08,
		details: {
			totalLinks,
			descriptiveLinks,
		},
	};
}

export default analyzeInternalLinks;
