/**
 * LSI Keyword Analyzer
 *
 * @module analysis/analyzers/seo/lsi-keyword-analysis
 */

export function analyzeLsiKeywords( content, lsiKeywordsStr ) {
	if ( ! lsiKeywordsStr || lsiKeywordsStr.trim() === '' ) {
		return {
			id: 'lsi-keyword-analysis',
			type: 'problem',
			message: 'Semantic SEO: No LSI keywords provided. Add secondary keywords to improve semantic relevance.',
			score: 0,
			weight: 0.05,
			details: {},
		};
	}

	const lsiKeywords = lsiKeywordsStr.split(',').map( k => k.trim().toLowerCase() ).filter( k => k !== '' );
	
	if ( lsiKeywords.length === 0 ) {
		return {
			id: 'lsi-keyword-analysis',
			type: 'problem',
			message: 'Semantic SEO: No LSI keywords provided. Add secondary keywords to improve semantic relevance.',
			score: 0,
			weight: 0.05,
			details: {},
		};
	}

	if ( ! content || content.trim() === '' ) {
		return {
			id: 'lsi-keyword-analysis',
			type: 'problem',
			message: 'Semantic SEO: Add content to analyze LSI keyword usage.',
			score: 0,
			weight: 0.05,
			details: {},
		};
	}

	const contentLower = content.toLowerCase();
	let foundCount = 0;
	let foundKeywords = [];

	lsiKeywords.forEach( keyword => {
		if ( contentLower.includes( keyword ) ) {
			foundCount++;
			foundKeywords.push( keyword );
		}
	});

	const percentage = foundCount / lsiKeywords.length;

	if ( percentage >= 0.5 ) {
		return {
			id: 'lsi-keyword-analysis',
			type: 'good',
			message: `Semantic SEO: Great! You've used ${foundCount} of ${lsiKeywords.length} LSI keywords.`,
			score: 100,
			weight: 0.05,
			details: { found: foundKeywords },
		};
	} else if ( percentage > 0 ) {
		return {
			id: 'lsi-keyword-analysis',
			type: 'ok',
			message: `Semantic SEO: You've only used ${foundCount} of ${lsiKeywords.length} LSI keywords. Try to include more for better semantic context.`,
			score: 60,
			weight: 0.05,
			details: { found: foundKeywords },
		};
	}

	return {
		id: 'lsi-keyword-analysis',
		type: 'problem',
		message: 'Semantic SEO: None of the LSI keywords appear in the content. Add them to improve semantic relevance.',
		score: 0,
		weight: 0.05,
		details: { found: [] },
	};
}

export default analyzeLsiKeywords;
