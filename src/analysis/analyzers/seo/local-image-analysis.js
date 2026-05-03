/**
 * Local Image Analysis Analyzer
 *
 * @module analysis/analyzers/seo/local-image-analysis
 */

export function analyzeLocalImageAnalysis( content ) {
	if ( ! content || content.trim() === '' ) {
		return {
			id: 'local-image-analysis',
			type: 'good',
			message: 'Image Quality: No images to analyze.',
			score: 100,
			weight: 0.05,
			details: {},
		};
	}

	const imageRegex = /<img[^>]+src=["']([^"']+)["'][^>]*>/gi;
	let match;
	let externalImages = 0;
	let totalImages = 0;

	// In Gutenberg, relative URLs or absolute URLs from the same domain are local.
	// This runs on the frontend/worker so we might not know the site URL perfectly, 
	// but we can check if it's a completely different domain if we have a way.
	// A simple heuristic: if it starts with http and does not contain the current hostname (if available).
	// Since we are in a worker, `location.hostname` might be available if it's not a blob, 
	// or we can pass siteUrl. For simplicity, let's just warn if images lack local structure or suggest optimization.

	while ( ( match = imageRegex.exec( content ) ) !== null ) {
		totalImages++;
		const src = match[1];
		
		// If it's a data URI or relative, it's local.
		if ( src.startsWith('data:') || src.startsWith('/') ) {
			continue;
		}
		
		// It's an absolute URL. We assume external if it doesn't match wp-content/uploads 
		// (a common WP pattern) or if it's clearly a different domain.
		if ( !src.includes('wp-content/uploads') ) {
			externalImages++;
		}
	}

	if ( totalImages === 0 ) {
		return {
			id: 'local-image-analysis',
			type: 'problem',
			message: 'Image Quality: No images appear on this page. Consider adding some.',
			score: 0,
			weight: 0.05,
			details: {},
		};
	}

	if ( externalImages > 0 ) {
		return {
			id: 'local-image-analysis',
			type: 'ok',
			message: `Image Quality: Found ${externalImages} external or non-standard image(s). Host images locally to improve SEO and load times.`,
			score: 50,
			weight: 0.05,
			details: { totalImages, externalImages },
		};
	}

	return {
		id: 'local-image-analysis',
		type: 'good',
		message: 'Image Quality: All images appear to be hosted locally. Ensure they are compressed (ideally under 150KB).',
		score: 100,
		weight: 0.05,
		details: { totalImages, externalImages },
	};
}

export default analyzeLocalImageAnalysis;
