/**
 * Web Worker for Content Analysis
 *
 * Runs comprehensive content analysis in a separate thread to avoid blocking the UI.
 * Orchestrates 16 analyzers (11 SEO + 5 Readability) and returns complete analysis results.
 */

import { analyzeContent } from '../../analysis/analysis-engine.js';

interface AnalysisInput {
	content: string;
	title: string;
	description: string;
	slug: string;
	keyword: string;
	directAnswer: string;
	schemaType: string;
}

interface AnalysisOutput {
	seoResults: Array< any >;
	readabilityResults: Array< any >;
	seoScore: number;
	readabilityScore: number;
	wordCount: number;
	sentenceCount: number;
	paragraphCount: number;
	fleschScore: number;
	keywordDensity: number;
	analysisTimestamp: number;
}

/**
 * Web Worker message handler
 *
 * Listens for ANALYZE messages from main thread, runs analysis engine,
 * and returns ANALYSIS_COMPLETE message with results.
 */
self.addEventListener( 'message', ( event: MessageEvent ) => {
	try {
		const { type, payload } = event.data;

		if ( type === 'ANALYZE' ) {
			// Run analysis engine with payload data
			const result: AnalysisOutput = analyzeContent( {
				content: payload.content || '',
				title: payload.title || '',
				description: payload.description || '',
				slug: payload.slug || '',
				keyword: payload.keyword || '',
				directAnswer: payload.directAnswer || '',
				schemaType: payload.schemaType || '',
			} );

			// Return ANALYSIS_COMPLETE message with results
			self.postMessage( {
				type: 'ANALYSIS_COMPLETE',
				payload: result,
			} );
		}
	} catch ( error ) {
		// Handle errors gracefully with fallback scores
		self.postMessage( {
			type: 'ANALYSIS_COMPLETE',
			payload: {
				seoResults: [],
				readabilityResults: [],
				seoScore: 0,
				readabilityScore: 0,
				wordCount: 0,
				sentenceCount: 0,
				paragraphCount: 0,
				fleschScore: 0,
				keywordDensity: 0,
				analysisTimestamp: Date.now(),
				error: error instanceof Error ? error.message : 'Unknown error',
			},
		} );
	}
} );
