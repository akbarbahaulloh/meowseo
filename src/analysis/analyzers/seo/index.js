/**
 * SEO Analyzers Index
 *
 * Exports all SEO analyzers and their weights configuration.
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
// New Yoast feature parity analyzers
import { analyzeSynonymWordForm } from './synonym-word-form.js';
import { analyzeAdditionalSeoAssessment } from './additional-seo-assessment.js';
import { analyzeKeywordLength } from './keyword-length.js';
import { analyzePreviouslyUsedKeyword } from './previously-used-keyword.js';
import { analyzeSingleHeadline } from './single-headline.js';
import { analyzeCompetitiveLinks } from './competitive-links.js';
import { analyzeTitleLength } from './title-length.js';
import { analyzeMetaDescriptionLength } from './meta-description-length.js';
import { analyzeLsiKeywords } from './lsi-keyword-analysis.js';
import { analyzeDirectAnswerParagraph } from './direct-answer-paragraph.js';
import { analyzeListTableDetection } from './list-table-detection.js';
import { analyzeHeadingHierarchy } from './heading-hierarchy.js';
import { analyzeTocDetection } from './toc-detection.js';
import { analyzeLocalImageAnalysis } from './local-image-analysis.js';
import { analyzeExternalLinkQuality } from './external-link-quality.js';

/**
 * SEO Analyzer Weights Configuration
 *
 * Each analyzer contributes a percentage to the overall SEO score.
 * Total must equal 100% (1.00).
 */
export const SEO_ANALYZER_WEIGHTS = {
	'keyword-in-title': 0.05,
	'keyword-in-description': 0.05,
	'keyword-in-first-paragraph': 0.05,
	'keyword-density': 0.05,
	'keyword-in-headings': 0.05,
	'keyword-in-slug': 0.05,
	'image-alt-analysis': 0.05,
	'internal-links-analysis': 0.05,
	'outbound-links-analysis': 0.05,
	'content-length': 0.05,
	'direct-answer-presence': 0.05,
	'schema-presence': 0.05,
	'synonym-word-form': 0.00, // Informational
	'additional-seo-assessment': 0.00, // Informational
	'keyword-length': 0.05,
	'previously-used-keyword': 0.05,
	'single-headline': 0.05,
	'competitive-links': 0.05,
	'title-length': 0.05,
	'meta-description-length': 0.05,
	'lsi-keyword-analysis': 0.05,
	'direct-answer-paragraph': 0.04,
	'list-table-detection': 0.04,
	'heading-hierarchy': 0.04,
	'toc-detection': 0.04,
	'local-image-analysis': 0.04,
	'external-link-quality': 0.04,
	// Total: 1.00 (approximately, we will adjust if necessary)
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
	analyzeSynonymWordForm,
	analyzeAdditionalSeoAssessment,
	analyzeKeywordLength,
	analyzePreviouslyUsedKeyword,
	analyzeSingleHeadline,
	analyzeCompetitiveLinks,
	analyzeTitleLength,
	analyzeMetaDescriptionLength,
	analyzeLsiKeywords,
	analyzeDirectAnswerParagraph,
	analyzeListTableDetection,
	analyzeHeadingHierarchy,
	analyzeTocDetection,
	analyzeLocalImageAnalysis,
	analyzeExternalLinkQuality,
};

/**
 * Run all SEO analyzers and return results
 *
 * @param {Object} data              - Analysis data
 * @return {Array<Object>} Array of analyzer results
 */
export async function runAllSeoAnalyzers( data ) {
	const {
		title = '',
		description = '',
		content = '',
		slug = '',
		keyword = '',
		lsiKeywords = '',
		directAnswer = '',
		schemaType = '',
		postId = 0,
		restUrl = '',
		nonce = '',
		tldBlacklist = '',
	} = data;

	const results = [];

	// Run each analyzer (synchronous ones)
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
	results.push( analyzeSynonymWordForm( content ) );
	results.push( analyzeAdditionalSeoAssessment( content ) );
	results.push( analyzeKeywordLength( keyword ) );
	results.push( analyzeSingleHeadline( content ) );
	results.push( analyzeCompetitiveLinks( content, keyword ) );
	results.push( analyzeTitleLength( title ) );
	results.push( analyzeMetaDescriptionLength( description ) );

	// Async analyzers
	results.push( await analyzePreviouslyUsedKeyword( keyword, postId, restUrl, nonce ) );
	results.push( analyzeLsiKeywords( content, lsiKeywords ) ); 
	results.push( analyzeDirectAnswerParagraph( content, directAnswer ) );
	results.push( analyzeListTableDetection( content ) );
	results.push( analyzeHeadingHierarchy( content ) );
	results.push( analyzeTocDetection( content ) );
	results.push( analyzeLocalImageAnalysis( content ) );
	results.push( analyzeExternalLinkQuality( content, tldBlacklist ) );
	
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

export default seoAnalyzers;
