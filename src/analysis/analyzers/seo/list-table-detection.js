/**
 * List & Table Detection Analyzer
 *
 * @module analysis/analyzers/seo/list-table-detection
 */

export function analyzeListTableDetection( content ) {
	if ( ! content || content.trim() === '' ) {
		return {
			id: 'list-table-detection',
			type: 'problem',
			message: 'Featured Snippet: Add lists (ul/ol) or tables to increase the chance of getting a featured snippet.',
			score: 0,
			weight: 0.05,
			details: {},
		};
	}

	const hasList = /<(ul|ol)[^>]*>.*?<\/\1>/is.test( content );
	const hasTable = /<table[^>]*>.*?<\/table>/is.test( content );

	if ( hasList && hasTable ) {
		return {
			id: 'list-table-detection',
			type: 'good',
			message: 'Featured Snippet: Great! Your content contains both lists and tables, which search engines love for featured snippets.',
			score: 100,
			weight: 0.05,
			details: { hasList: true, hasTable: true },
		};
	} else if ( hasList || hasTable ) {
		return {
			id: 'list-table-detection',
			type: 'good',
			message: `Featured Snippet: Your content contains ${hasList ? 'a list' : 'a table'}, which is good for featured snippets.`,
			score: 100,
			weight: 0.05,
			details: { hasList, hasTable },
		};
	}

	return {
		id: 'list-table-detection',
		type: 'problem',
		message: 'Featured Snippet: Try adding a list or a table. Search engines often use these for featured snippets.',
		score: 0,
		weight: 0.05,
		details: { hasList: false, hasTable: false },
	};
}

export default analyzeListTableDetection;
