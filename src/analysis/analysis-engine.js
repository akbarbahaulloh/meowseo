/**
 * Analysis Engine
 *
 * Orchestrates all 16 analyzers (11 SEO + 5 Readability) to provide comprehensive
 * content analysis. Runs SEO and readability analyzers in parallel and calculates
 * weighted scores for each category.
 *
 * @module analysis/analysis-engine
 */

import {
	runAllSeoAnalyzers,
	calculateSeoScore,
} from './analyzers/seo/index.js';

import {
	analyzeSentenceLength,
	analyzeParagraphLength,
	analyzePassiveVoice,
	analyzeTransitionWords,
	analyzeSubheadingDistribution,
	analyzeFleschReadingEase,
} from './analyzers/readability/index.js';

/**
 * Readability Analyzer Weights Configuration
 *
 * Each readability analyzer contributes a percentage to the overall readability score.
 * Total must equal 100% (1.00).
 * FleschReadingEase contributes 0% (informational only).
 */
const READABILITY_ANALYZER_WEIGHTS = {
	'sentence-length': 0.2, // 20%
	'paragraph-length': 0.2, // 20%
	'passive-voice': 0.2, // 20%
	'transition-words': 0.2, // 20%
	'subheading-distribution': 0.2, // 20%
	'flesch-reading-ease': 0.0, // 0% (informational only)
	// Total: 100%
};

/**
 * Run all readability analyzers and return results
 *
 * @param {string} content - Post content (HTML)
 * @return {Array<Object>} Array of readability analyzer results
 */
function runAllReadabilityAnalyzers( content ) {
	const results = [];

	try {
		results.push( analyzeSentenceLength( content ) );
	} catch ( error ) {
		results.push( {
			id: 'sentence-length',
			type: 'problem',
			message: 'Error analyzing sentence length',
			score: 0,
			weight: READABILITY_ANALYZER_WEIGHTS[ 'sentence-length' ],
			details: { error: error.message },
		} );
	}

	try {
		results.push( analyzeParagraphLength( content ) );
	} catch ( error ) {
		results.push( {
			id: 'paragraph-length',
			type: 'problem',
			message: 'Error analyzing paragraph length',
			score: 0,
			weight: READABILITY_ANALYZER_WEIGHTS[ 'paragraph-length' ],
			details: { error: error.message },
		} );
	}

	try {
		results.push( analyzePassiveVoice( content ) );
	} catch ( error ) {
		results.push( {
			id: 'passive-voice',
			type: 'problem',
			message: 'Error analyzing passive voice',
			score: 0,
			weight: READABILITY_ANALYZER_WEIGHTS[ 'passive-voice' ],
			details: { error: error.message },
		} );
	}

	try {
		results.push( analyzeTransitionWords( content ) );
	} catch ( error ) {
		results.push( {
			id: 'transition-words',
			type: 'problem',
			message: 'Error analyzing transition words',
			score: 0,
			weight: READABILITY_ANALYZER_WEIGHTS[ 'transition-words' ],
			details: { error: error.message },
		} );
	}

	try {
		results.push( analyzeSubheadingDistribution( content ) );
	} catch ( error ) {
		results.push( {
			id: 'subheading-distribution',
			type: 'problem',
			message: 'Error analyzing subheading distribution',
			score: 0,
			weight: READABILITY_ANALYZER_WEIGHTS[ 'subheading-distribution' ],
			details: { error: error.message },
		} );
	}

	try {
		results.push( analyzeFleschReadingEase( content ) );
	} catch ( error ) {
		results.push( {
			id: 'flesch-reading-ease',
			type: 'problem',
			message: 'Error analyzing Flesch Reading Ease',
			score: 0,
			weight: READABILITY_ANALYZER_WEIGHTS[ 'flesch-reading-ease' ],
			details: { error: error.message },
		} );
	}

	return results;
}

/**
 * Calculate overall readability score from analyzer results
 *
 * @param {Array<Object>} results - Array of readability analyzer results
 * @return {number} Readability score (0-100)
 */
function calculateReadabilityScore( results ) {
	if ( ! results || results.length === 0 ) {
		return 0;
	}

	let totalScore = 0;

	for ( const result of results ) {
		const weight = READABILITY_ANALYZER_WEIGHTS[ result.id ] || 0;
		totalScore += result.score * weight;
	}

	return Math.round( totalScore );
}

/**
 * Extract metadata from content and analyzer results
 *
 * @param {string}        content            - Post content (HTML)
 * @param {Array<Object>} seoResults         - SEO analyzer results
 * @param {Array<Object>} readabilityResults - Readability analyzer results
 * @return {Object} Metadata object
 */
function extractMetadata( content, seoResults, readabilityResults ) {
	let wordCount = 0;
	let sentenceCount = 0;
	let paragraphCount = 0;
	let fleschScore = 0;
	let keywordDensity = 0;

	// Extract wordCount from ContentLength analyzer
	const contentLengthResult = seoResults.find(
		( r ) => r.id === 'content-length'
	);
	if ( contentLengthResult && contentLengthResult.details ) {
		wordCount = contentLengthResult.details.wordCount || 0;
	}

	// Extract sentenceCount from SentenceLength analyzer
	const sentenceLengthResult = readabilityResults.find(
		( r ) => r.id === 'sentence-length'
	);
	if ( sentenceLengthResult && sentenceLengthResult.details ) {
		sentenceCount = sentenceLengthResult.details.sentenceCount || 0;
	}

	// Extract paragraphCount from ParagraphLength analyzer
	const paragraphLengthResult = readabilityResults.find(
		( r ) => r.id === 'paragraph-length'
	);
	if ( paragraphLengthResult && paragraphLengthResult.details ) {
		paragraphCount = paragraphLengthResult.details.paragraphCount || 0;
	}

	// Extract fleschScore from FleschReadingEase analyzer
	const fleschResult = readabilityResults.find(
		( r ) => r.id === 'flesch-reading-ease'
	);
	if ( fleschResult && fleschResult.details ) {
		fleschScore = fleschResult.details.score || 0;
	}

	// Extract keywordDensity from KeywordDensity analyzer
	const keywordDensityResult = seoResults.find(
		( r ) => r.id === 'keyword-density'
	);
	if ( keywordDensityResult && keywordDensityResult.details ) {
		keywordDensity = keywordDensityResult.details.density || 0;
	}

	return {
		wordCount,
		sentenceCount,
		paragraphCount,
		fleschScore,
		keywordDensity,
	};
}

/**
 * Analyze content using all 16 analyzers
 *
 * Orchestrates 11 SEO analyzers and 5 readability analyzers, calculating
 * weighted scores for each category. Handles individual analyzer failures
 * gracefully and returns complete analysis result object.
 *
 * @param {Object} data              - Analysis data
 * @param {string} data.content      - Post content (HTML)
 * @param {string} data.title        - SEO title
 * @param {string} data.description  - Meta description
 * @param {string} data.slug         - URL slug
 * @param {string} data.keyword      - Focus keyword
 * @param {string} data.directAnswer - Direct Answer field
 * @param {string} data.schemaType   - Schema Type field
 * @return {Object} Complete analysis result object
 */
export function analyzeContent( data ) {
	const {
		content = '',
		title = '',
		description = '',
		slug = '',
		keyword = '',
		directAnswer = '',
		schemaType = '',
	} = data;

	// Run SEO analyzers
	let seoResults = [];
	try {
		seoResults = runAllSeoAnalyzers( {
			title,
			description,
			content,
			slug,
			keyword,
			directAnswer,
			schemaType,
		} );
	} catch ( error ) {
		seoResults = [];
	}

	// Run readability analyzers
	let readabilityResults = [];
	try {
		readabilityResults = runAllReadabilityAnalyzers( content );
	} catch ( error ) {
		readabilityResults = [];
	}

	// Calculate scores
	const seoScore = calculateSeoScore( seoResults );
	const readabilityScore = calculateReadabilityScore( readabilityResults );

	// Extract metadata
	const metadata = extractMetadata( content, seoResults, readabilityResults );

	// Return complete analysis result
	return {
		seoResults,
		readabilityResults,
		seoScore,
		readabilityScore,
		wordCount: metadata.wordCount,
		sentenceCount: metadata.sentenceCount,
		paragraphCount: metadata.paragraphCount,
		fleschScore: metadata.fleschScore,
		keywordDensity: metadata.keywordDensity,
		analysisTimestamp: Date.now(),
	};
}

export default analyzeContent;
