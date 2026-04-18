/**
 * Image Alt Text Analysis Analyzer
 *
 * Checks if images have alt text and if the focus keyword appears in alt text.
 *
 * @module analysis/analyzers/seo/image-alt-analysis
 */

import { parseHtml } from '../../utils/html-parser.js';

/**
 * Analyzes images for alt text and keyword presence
 *
 * @param {string} content - The HTML content to analyze
 * @param {string} keyword - The focus keyword to search for in alt text
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeImageAlt('<img src="image.jpg" alt="SEO optimization guide">', 'seo optimization')
 * // Returns: { id: 'image-alt-analysis', type: 'good', message: 'All images have descriptive alt text', score: 100, weight: 0.08, details: { totalImages: 1, withAlt: 1, withKeyword: 1 } }
 */
export function analyzeImageAlt( content, keyword ) {
	// Handle missing or empty content
	if ( ! content || content.trim() === '' ) {
		return {
			id: 'image-alt-analysis',
			type: 'problem',
			message: 'Add content with images to analyze',
			score: 0,
			weight: 0.08,
			details: {
				totalImages: 0,
				withAlt: 0,
				withKeyword: 0,
			},
		};
	}

	// Parse HTML and extract images
	const parsed = parseHtml( content );
	const images = parsed.images;

	const totalImages = images.length;

	// Handle content with no images
	if ( totalImages === 0 ) {
		return {
			id: 'image-alt-analysis',
			type: 'problem',
			message: 'Add images to your content for better engagement',
			score: 0,
			weight: 0.08,
			details: {
				totalImages: 0,
				withAlt: 0,
				withKeyword: 0,
			},
		};
	}

	// Count images with alt text
	const withAlt = images.filter( ( img ) => img.hasAlt ).length;

	// Count images with keyword in alt text
	const normalizedKeyword = keyword ? keyword.toLowerCase().trim() : '';
	const withKeyword = images.filter( ( img ) => {
		if ( ! img.hasAlt ) {
			return false;
		}
		if ( ! normalizedKeyword ) {
			return false;
		}
		return img.alt.toLowerCase().includes( normalizedKeyword );
	} ).length;

	// Calculate coverage percentages
	const altCoverage = ( withAlt / totalImages ) * 100;
	const keywordCoverage =
		totalImages > 0 && normalizedKeyword
			? ( withKeyword / totalImages ) * 100
			: 0;

	// Determine status
	// Good: >80% images have alt text with keyword
	// Ok: >50% images have alt text
	// Problem: <50% images have alt text
	let type, message, score;

	if ( normalizedKeyword && keywordCoverage >= 80 ) {
		type = 'good';
		message = `All images have descriptive alt text with keyword (${ withKeyword }/${ totalImages })`;
		score = 100;
	} else if ( altCoverage >= 80 ) {
		type = 'good';
		message = `Most images have alt text (${ withAlt }/${ totalImages })`;
		score = 100;
	} else if ( altCoverage > 50 ) {
		type = 'ok';
		message = `Some images missing alt text (${ withAlt }/${ totalImages } have alt)`;
		score = 50;
	} else {
		type = 'problem';
		message = `Add alt text to images (${ withAlt }/${ totalImages } have alt)`;
		score = 0;
	}

	// Add keyword suggestion if keyword is set but not in alt text
	if ( normalizedKeyword && withKeyword === 0 && withAlt > 0 ) {
		message += `. Consider adding keyword "${ keyword.trim() }" to image alt text.`;
	}

	return {
		id: 'image-alt-analysis',
		type,
		message,
		score,
		weight: 0.08,
		details: {
			totalImages,
			withAlt,
			withKeyword,
		},
	};
}

export default analyzeImageAlt;
