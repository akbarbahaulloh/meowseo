/**
 * Content Length Analyzer
 *
 * Analyzes the word count of content.
 * Optimal range: 300-2500 words
 *
 * @module analysis/analyzers/seo/content-length
 */

import { parseHtml } from '../../utils/html-parser.js';

/**
 * Analyzes content length (word count)
 *
 * @param {string} content - The HTML content to analyze
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeContentLength('<p>Content with enough words...</p>') // 500 words
 * // Returns: { id: 'content-length', type: 'good', message: 'Content length is optimal (500 words)', score: 100, weight: 0.09, details: { wordCount: 500 } }
 */
export function analyzeContentLength( content ) {
	// Handle missing or empty content
	if ( ! content || content.trim() === '' ) {
		return {
			id: 'content-length',
			type: 'problem',
			message: 'Add content to your post',
			score: 0,
			weight: 0.09,
			details: {
				wordCount: 0,
			},
		};
	}

	// Parse HTML and extract text
	const parsed = parseHtml( content );
	const text = parsed.text;

	// Count words
	const words = text.split( /\s+/ ).filter( ( w ) => w.length > 0 );
	const wordCount = words.length;

	// Determine status
	// Good: 300-2500 words
	// Ok: 150-300 or 2500-5000 words
	// Problem: <150 or >5000 words
	let type, message, score;

	if ( wordCount === 0 ) {
		type = 'problem';
		message = 'Add content to your post';
		score = 0;
	} else if ( wordCount >= 300 && wordCount <= 2500 ) {
		type = 'good';
		message = `Content length is optimal (${ wordCount.toLocaleString() } words)`;
		score = 100;
	} else if (
		( wordCount >= 150 && wordCount < 300 ) ||
		( wordCount > 2500 && wordCount <= 5000 )
	) {
		type = 'ok';
		if ( wordCount < 300 ) {
			message = `Content is a bit short (${ wordCount } words). Expand to at least 300 words for better SEO.`;
		} else {
			message = `Content is quite long (${ wordCount.toLocaleString() } words). Consider breaking into multiple posts.`;
		}
		score = 50;
	} else {
		type = 'problem';
		if ( wordCount < 150 ) {
			message = `Content is too short (${ wordCount } words). Add more content for better SEO.`;
		} else {
			message = `Content is very long (${ wordCount.toLocaleString() } words). Consider splitting into multiple posts.`;
		}
		score = 0;
	}

	return {
		id: 'content-length',
		type,
		message,
		score,
		weight: 0.09,
		details: {
			wordCount,
		},
	};
}

export default analyzeContentLength;
