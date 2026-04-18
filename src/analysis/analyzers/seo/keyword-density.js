/**
 * Keyword Density Analyzer
 *
 * Calculates the keyword density as a percentage of total words.
 * Optimal range: 0.5-2.5%
 *
 * @module analysis/analyzers/seo/keyword-density
 */

import { stemWord } from '../../utils/indonesian-stemmer.js';
import { parseHtml } from '../../utils/html-parser.js';

/**
 * Analyzes keyword density in content
 *
 * @param {string} content - The HTML content to analyze
 * @param {string} keyword - The focus keyword to count
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeKeywordDensity('<p>SEO optimization is important. SEO optimization helps rankings.</p>', 'seo optimization')
 * // Returns density percentage and status based on optimal range
 */
export function analyzeKeywordDensity( content, keyword ) {
	// Handle missing or empty inputs
	if ( ! keyword || keyword.trim() === '' ) {
		return {
			id: 'keyword-density',
			type: 'problem',
			message: 'Set a focus keyword to analyze keyword density',
			score: 0,
			weight: 0.09,
			details: {
				keyword: '',
				density: 0,
				count: 0,
				totalWords: 0,
			},
		};
	}

	if ( ! content || content.trim() === '' ) {
		return {
			id: 'keyword-density',
			type: 'problem',
			message: 'Add content to analyze keyword density',
			score: 0,
			weight: 0.09,
			details: {
				keyword: keyword.trim(),
				density: 0,
				count: 0,
				totalWords: 0,
			},
		};
	}

	// Parse HTML and extract text
	const parsed = parseHtml( content );
	const text = parsed.text;

	// Count total words
	const words = text.split( /\s+/ ).filter( ( w ) => w.length > 0 );
	const totalWords = words.length;

	if ( totalWords === 0 ) {
		return {
			id: 'keyword-density',
			type: 'problem',
			message: 'Add content to analyze keyword density',
			score: 0,
			weight: 0.09,
			details: {
				keyword: keyword.trim(),
				density: 0,
				count: 0,
				totalWords: 0,
			},
		};
	}

	// Normalize keyword
	const normalizedKeyword = keyword.toLowerCase().trim();
	const keywordWords = normalizedKeyword.split( /\s+/ );
	const stemmedKeywords = keywordWords.map( ( w ) => stemWord( w ) );
	const stemmedKeywordPhrase = stemmedKeywords.join( ' ' );

	// Count keyword occurrences
	let keywordCount = 0;

	// For single-word keywords
	if ( keywordWords.length === 1 ) {
		const stemmedWords = words.map( ( w ) => stemWord( w.toLowerCase() ) );
		keywordCount = stemmedWords.filter(
			( w ) => w === stemmedKeywordPhrase
		).length;
	} else {
		// For multi-word keywords, find all occurrences in text
		const normalizedText = text.toLowerCase();
		const stemmedText = words
			.map( ( w ) => stemWord( w.toLowerCase() ) )
			.join( ' ' );

		// Count occurrences using regex for direct match
		const regex = new RegExp( escapeRegex( normalizedKeyword ), 'gi' );
		const directMatches = normalizedText.match( regex );
		keywordCount = directMatches ? directMatches.length : 0;

		// Also check stemmed version if no direct matches
		if ( keywordCount === 0 ) {
			const stemmedRegex = new RegExp(
				escapeRegex( stemmedKeywordPhrase ),
				'gi'
			);
			const stemmedMatches = stemmedText.match( stemmedRegex );
			keywordCount = stemmedMatches ? stemmedMatches.length : 0;
		}
	}

	// Calculate density
	const density = totalWords > 0 ? ( keywordCount / totalWords ) * 100 : 0;

	// Determine status based on density
	// Good: 0.5-2.5%
	// Ok: 0.3-0.5% or 2.5-3.5%
	// Problem: <0.3% or >3.5%
	let type, message, score;

	if ( density >= 0.5 && density <= 2.5 ) {
		type = 'good';
		message = `Keyword density is optimal (${ density.toFixed( 1 ) }%)`;
		score = 100;
	} else if (
		( density >= 0.3 && density < 0.5 ) ||
		( density > 2.5 && density <= 3.5 )
	) {
		type = 'ok';
		if ( density < 0.5 ) {
			message = `Keyword density is low (${ density.toFixed(
				1
			) }%). Consider adding more keyword occurrences.`;
		} else {
			message = `Keyword density is high (${ density.toFixed(
				1
			) }%). Consider reducing keyword usage.`;
		}
		score = 50;
	} else {
		type = 'problem';
		if ( density < 0.3 ) {
			message = `Keyword density is too low (${ density.toFixed(
				1
			) }%). Increase keyword usage.`;
		} else {
			message = `Keyword density is too high (${ density.toFixed(
				1
			) }%). Reduce keyword usage to avoid over-optimization.`;
		}
		score = 0;
	}

	return {
		id: 'keyword-density',
		type,
		message,
		score,
		weight: 0.09,
		details: {
			keyword: keyword.trim(),
			density: parseFloat( density.toFixed( 2 ) ),
			count: keywordCount,
			totalWords,
		},
	};
}

/**
 * Escapes special regex characters
 * @param {string} string - String to escape
 * @return {string} Escaped string
 */
function escapeRegex( string ) {
	return string.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
}

export default analyzeKeywordDensity;
