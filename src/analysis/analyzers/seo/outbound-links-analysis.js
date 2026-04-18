/**
 * Outbound Links Analysis Analyzer
 *
 * Analyzes external links in content for presence and proper attribution.
 *
 * @module analysis/analyzers/seo/outbound-links-analysis
 */

import { parseHtml } from '../../utils/html-parser.js';

/**
 * Analyzes outbound (external) links in content
 *
 * @param {string} content - The HTML content to analyze
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeOutboundLinks('<p>Read more on <a href="https://example.com" rel="nofollow">Example Site</a>.</p>')
 * // Returns: { id: 'outbound-links-analysis', type: 'good', message: 'Good external linking', score: 100, weight: 0.07, details: { totalLinks: 1, withNofollow: 1 } }
 */
export function analyzeOutboundLinks( content ) {
	// Handle missing or empty content
	if ( ! content || content.trim() === '' ) {
		return {
			id: 'outbound-links-analysis',
			type: 'problem',
			message: 'Add content to analyze outbound links',
			score: 0,
			weight: 0.07,
			details: {
				totalLinks: 0,
				withNofollow: 0,
			},
		};
	}

	// Parse HTML and extract links
	const parsed = parseHtml( content );
	const links = parsed.links;

	// Filter external links
	const externalLinks = links.filter( ( link ) => link.isExternal );
	const totalLinks = externalLinks.length;

	// Count links with nofollow attribute
	const withNofollow = externalLinks.filter(
		( link ) => link.hasNofollow
	).length;

	// Determine status
	// Good: external links present with proper attribution (nofollow)
	// Ok: external links exist but lack nofollow
	// Problem: no external links
	let type, message, score;

	if ( totalLinks === 0 ) {
		type = 'problem';
		message =
			'Add external links to authoritative sources for better credibility';
		score = 0;
	} else if ( withNofollow > 0 ) {
		type = 'good';
		message = `Good external linking (${ totalLinks } link${
			totalLinks > 1 ? 's' : ''
		} with proper attribution)`;
		score = 100;
	} else {
		type = 'ok';
		message = `External links present (${ totalLinks }). Consider adding rel="nofollow" for sponsored or untrusted links.`;
		score = 50;
	}

	return {
		id: 'outbound-links-analysis',
		type,
		message,
		score,
		weight: 0.07,
		details: {
			totalLinks,
			withNofollow,
		},
	};
}

export default analyzeOutboundLinks;
