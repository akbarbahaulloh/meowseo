/**
 * Competitive Links Analyzer
 *
 * Checks if links use the focus keyword as anchor text.
 *
 * @module analysis/analyzers/seo/competitive-links
 */

import { parseHtml } from '../../utils/html-parser.js';

export function analyzeCompetitiveLinks( content, keyword ) {
	if ( ! keyword || keyword.trim() === '' ) {
		return {
			id: 'competitive-links',
			type: 'problem',
			message: 'Competitive Links: No focus keyphrase was set.',
			score: 0,
			weight: 0.05,
			details: {},
		};
	}

	if ( ! content || content.trim() === '' ) {
		return {
			id: 'competitive-links',
			type: 'good',
			message: 'Competitive Links: No links use your keyphrase or synonyms as anchor text.',
			score: 100,
			weight: 0.05,
			details: { competitiveLinksCount: 0 },
		};
	}

	const parsed = parseHtml( content );
	const links = parsed.links || [];
	const normalizedKeyword = keyword.toLowerCase().trim();

	let competitiveLinksCount = 0;

	for ( const link of links ) {
		const anchorText = ( link.text || '' ).toLowerCase().trim();
		if ( anchorText && anchorText.includes( normalizedKeyword ) ) {
			competitiveLinksCount++;
		}
	}

	let type, message, score;

	if ( competitiveLinksCount === 0 ) {
		type = 'good';
		message = 'Competitive Links: No links use your keyphrase or synonyms as anchor text.';
		score = 100;
	} else {
		type = 'problem';
		message = `Competitive Links: You are linking to another page with the words you want this page to rank for. Don't do that!`;
		score = 0;
	}

	return {
		id: 'competitive-links',
		type,
		message,
		score,
		weight: 0.05,
		details: { competitiveLinksCount },
	};
}

export default analyzeCompetitiveLinks;
