/**
 * SEO Analyzers Index
 *
 * Exports all 11 SEO analyzers and their weights configuration.
 * Total weight: 100% (1.00)
 *
 * @module analysis/analyzers/seo
 */

import { analyzeKeywordInTitle } from './keyword-in-title.js';
import { analyzeKeywordInDescription } from './keyword-in-description.js';
import { analyzeKeywordInFirstParagraph } from './keyword-in-first-paragraph.js';
import { analyzeKeywordDensity } from './keyword-density.js';
import { analyzeKeywordInHeadings } from './keyword-in-headings.js';
import { analyzeKeywordInSlug } from './keyword-in-slug.js';
import { analyzeImageAlt } from './image-alt-analysis.js';
import { analyzeInternalLinks } from './internal-links-analysis.js';
import { analyzeOutboundLinks } from './outbound-links-analysis.js';
import { analyzeContentLength } from './content-length.js';
import { analyzeDirectAnswer } from './direct-answer-presence.js';
import { analyzeSchemaPresence } from './schema-presence.js';

/**
 * SEO Analyzer Weights Configuration
 *
 * Each analyzer contributes a percentage to the overall SEO score.
 * Total must equal 100% (1.00).
 */
export const SEO_ANALYZER_WEIGHTS = {
	'keyword-in-title': 0.08, // 8%
	'keyword-in-description': 0.07, // 7%
	'keyword-in-first-paragraph': 0.08, // 8%
	'keyword-density': 0.09, // 9%
	'keyword-in-headings': 0.08, // 8%
	'keyword-in-slug': 0.07, // 7%
	'image-alt-analysis': 0.08, // 8%
	'internal-links-analysis': 0.08, // 8%
	'outbound-links-analysis': 0.07, // 7%
	'content-length': 0.09, // 9%
	'direct-answer-presence': 0.06, // 6%
	'schema-presence': 0.05, // 5%
	// Total: 100%
};

/**
 * All SEO analyzer functions
 */
export const seoAnalyzers = {
	analyzeKeywordInTitle,
	analyzeKeywordInDescription,
	analyzeKeywordInFirstParagraph,
	analyzeKeywordDensity,
	analyzeKeywordInHeadings,
	analyzeKeywordInSlug,
	analyzeImageAlt,
	analyzeInternalLinks,
	analyzeOutboundLinks,
	analyzeContentLength,
	analyzeDirectAnswer,
	analyzeSchemaPresence,
};

/**
 * Run all SEO analyzers and return results
 *
 * @param {Object} data              - Analysis data
 * @param {string} data.title        - SEO title
 * @param {string} data.description  - Meta description
 * @param {string} data.content      - Post content (HTML)
 * @param {string} data.slug         - URL slug
 * @param {string} data.keyword      - Focus keyword
 * @param {string} data.directAnswer - Direct Answer field
 * @param {string} data.schemaType   - Schema Type field
 * @return {Array<Object>} Array of analyzer results
 */
export function runAllSeoAnalyzers( data ) {
	const {
		title = '',
		description = '',
		content = '',
		slug = '',
		keyword = '',
		directAnswer = '',
		schemaType = '',
	} = data;

	const results = [];

	// Run each analyzer
	results.push( analyzeKeywordInTitle( title, keyword ) );
	results.push( analyzeKeywordInDescription( description, keyword ) );
	results.push( analyzeKeywordInFirstParagraph( content, keyword ) );
	results.push( analyzeKeywordDensity( content, keyword ) );
	results.push( analyzeKeywordInHeadings( content, keyword ) );
	results.push( analyzeKeywordInSlug( slug, keyword ) );
	results.push( analyzeImageAlt( content, keyword ) );
	results.push( analyzeInternalLinks( content ) );
	results.push( analyzeOutboundLinks( content ) );
	results.push( analyzeContentLength( content ) );
	results.push( analyzeDirectAnswer( directAnswer ) );
	results.push( analyzeSchemaPresence( schemaType ) );

	return results;
}

/**
 * Calculate overall SEO score from analyzer results
 *
 * @param {Array<Object>} results - Array of analyzer results
 * @return {number} SEO score (0-100)
 */
export function calculateSeoScore( results ) {
	if ( ! results || results.length === 0 ) {
		return 0;
	}

	let totalScore = 0;

	for ( const result of results ) {
		const weight = SEO_ANALYZER_WEIGHTS[ result.id ] || 0;
		totalScore += result.score * weight;
	}

	return Math.round( totalScore );
}

// Export individual analyzers
export {
	analyzeKeywordInTitle,
	analyzeKeywordInDescription,
	analyzeKeywordInFirstParagraph,
	analyzeKeywordDensity,
	analyzeKeywordInHeadings,
	analyzeKeywordInSlug,
	analyzeImageAlt,
	analyzeInternalLinks,
	analyzeOutboundLinks,
	analyzeContentLength,
	analyzeDirectAnswer,
	analyzeSchemaPresence,
};

export default seoAnalyzers;
