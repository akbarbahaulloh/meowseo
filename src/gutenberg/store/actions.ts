/**
 * Action Creators for meowseo/data Redux Store
 *
 * Requirements: 3.1, 3.9
 */

import { ContentSnapshot, AnalysisResult, TabType } from './types';
import { select, dispatch } from '@wordpress/data';
import { STORE_NAME } from './index';

// Action Types
export const UPDATE_CONTENT_SNAPSHOT = 'UPDATE_CONTENT_SNAPSHOT';
export const SET_ANALYZING = 'SET_ANALYZING';
export const SET_ANALYSIS_RESULTS = 'SET_ANALYSIS_RESULTS';
export const SET_ACTIVE_TAB = 'SET_ACTIVE_TAB';

// Action Interfaces
export interface UpdateContentSnapshotAction {
	type: typeof UPDATE_CONTENT_SNAPSHOT;
	payload: ContentSnapshot;
}

export interface SetAnalyzingAction {
	type: typeof SET_ANALYZING;
	payload: boolean;
}

/**
 * Payload for SET_ANALYSIS_RESULTS action
 * Contains all analysis data from the analysis engine
 * Requirements: 3.1, 3.9
 */
export interface AnalysisResultsPayload {
	/** SEO analysis results (11 analyzers) */
	seoResults: AnalysisResult[];
	/** Readability analysis results (5 analyzers) */
	readabilityResults: AnalysisResult[];
	/** Overall SEO score (0-100) */
	seoScore: number;
	/** Overall readability score (0-100) */
	readabilityScore: number;
	/** Total word count */
	wordCount: number;
	/** Total sentence count */
	sentenceCount: number;
	/** Total paragraph count */
	paragraphCount: number;
	/** Flesch Reading Ease score (0-100) */
	fleschScore: number;
	/** Keyword density percentage */
	keywordDensity: number;
	/** Timestamp when analysis was performed */
	analysisTimestamp: number;
}

export interface SetAnalysisResultsAction {
	type: typeof SET_ANALYSIS_RESULTS;
	payload: AnalysisResultsPayload;
}

export interface SetActiveTabAction {
	type: typeof SET_ACTIVE_TAB;
	payload: TabType;
}

export type MeowSEOAction =
	| UpdateContentSnapshotAction
	| SetAnalyzingAction
	| SetAnalysisResultsAction
	| SetActiveTabAction;

// Action Creators
export const updateContentSnapshot = (
	snapshot: ContentSnapshot
): UpdateContentSnapshotAction => ( {
	type: UPDATE_CONTENT_SNAPSHOT,
	payload: snapshot,
} );

export const setAnalyzing = ( isAnalyzing: boolean ): SetAnalyzingAction => ( {
	type: SET_ANALYZING,
	payload: isAnalyzing,
} );

/**
 * Set analysis results with all analysis data
 *
 * @param seoResults         - SEO analysis results array
 * @param readabilityResults - Readability analysis results array
 * @param seoScore           - Overall SEO score (0-100)
 * @param readabilityScore   - Overall readability score (0-100)
 * @param wordCount          - Total word count
 * @param sentenceCount      - Total sentence count
 * @param paragraphCount     - Total paragraph count
 * @param fleschScore        - Flesch Reading Ease score
 * @param keywordDensity     - Keyword density percentage
 * @param analysisTimestamp  - Timestamp of analysis
 *
 *                           Requirements: 3.1, 3.9
 */
export const setAnalysisResults = (
	seoResults: AnalysisResult[],
	readabilityResults: AnalysisResult[],
	seoScore: number,
	readabilityScore: number,
	wordCount: number,
	sentenceCount: number,
	paragraphCount: number,
	fleschScore: number,
	keywordDensity: number,
	analysisTimestamp: number
): SetAnalysisResultsAction => ( {
	type: SET_ANALYSIS_RESULTS,
	payload: {
		seoResults,
		readabilityResults,
		seoScore,
		readabilityScore,
		wordCount,
		sentenceCount,
		paragraphCount,
		fleschScore,
		keywordDensity,
		analysisTimestamp,
	},
} );

export const setActiveTab = ( tab: TabType ): SetActiveTabAction => ( {
	type: SET_ACTIVE_TAB,
	payload: tab,
} );

/**
 * Thunk action to analyze content using Web Worker
 *
 * This action:
 * 1. Gets contentSnapshot from store
 * 2. Sets isAnalyzing to true
 * 3. Creates Web Worker instance
 * 4. Posts contentSnapshot to worker
 * 5. Handles worker response and updates store
 * 6. Handles worker errors with fallback to main thread
 * 7. Terminates worker after completion
 * 8. Sets isAnalyzing to false
 *
 * Requirements: 5.2, 5.3, 5.4, 5.5, 5.6, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 17.1
 */
export const analyzeContent = () => {
	return async ( { select, dispatch }: { select: any; dispatch: any } ) => {
		// Get contentSnapshot from store
		const state = select.getState();
		const { contentSnapshot } = state;

		// Set isAnalyzing to true
		dispatch.setAnalyzing( true );

		try {
			// Check if Web Workers are supported
			if ( typeof Worker === 'undefined' ) {
				console.warn(
					'Web Workers not supported, falling back to main thread analysis'
				);
				// Fallback to main thread analysis
				const { analyzeSEO } = await import(
					'../workers/analysis-worker'
				);
				const result = analyzeSEO( {
					title: contentSnapshot.title,
					description: contentSnapshot.excerpt,
					content: contentSnapshot.content,
					slug: extractSlugFromPermalink( contentSnapshot.permalink ),
					focusKeyword: contentSnapshot.focusKeyword,
				} );

				// Update store with results (using new signature with all fields)
				dispatch.setAnalysisResults(
					result.results, // seoResults
					[], // readabilityResults (placeholder for now)
					result.score, // seoScore
					result.score, // readabilityScore (placeholder)
					0, // wordCount (placeholder)
					0, // sentenceCount (placeholder)
					0, // paragraphCount (placeholder)
					0, // fleschScore (placeholder)
					0, // keywordDensity (placeholder)
					Date.now() // analysisTimestamp
				);

				return;
			}

			// Create Web Worker instance
			// Note: In production, webpack will handle the worker path
			// In tests, we mock the Worker constructor
			const worker = new Worker( '/workers/analysis-worker.js' );

			// Set up timeout (10 seconds)
			const timeoutId = setTimeout( () => {
				worker.terminate();
				console.error( 'Analysis timed out after 10 seconds' );
				dispatch.setAnalyzing( false );
			}, 10000 );

			// Handle worker response
			const result = await new Promise< {
				score: number;
				results: AnalysisResult[];
				color: 'red' | 'orange' | 'green';
			} >( ( resolve, reject ) => {
				worker.onmessage = ( e: MessageEvent ) => {
					clearTimeout( timeoutId );
					resolve( e.data );
				};

				worker.onerror = ( error: ErrorEvent ) => {
					clearTimeout( timeoutId );
					reject( error );
				};

				// Post contentSnapshot to worker
				worker.postMessage( {
					title: contentSnapshot.title,
					description: contentSnapshot.excerpt,
					content: contentSnapshot.content,
					slug: extractSlugFromPermalink( contentSnapshot.permalink ),
					focusKeyword: contentSnapshot.focusKeyword,
				} );
			} );

			// Terminate worker after completion
			worker.terminate();

			// Update store with results (using new signature with all fields)
			dispatch.setAnalysisResults(
				result.results, // seoResults
				[], // readabilityResults (placeholder for now)
				result.score, // seoScore
				result.score, // readabilityScore (placeholder)
				0, // wordCount (placeholder)
				0, // sentenceCount (placeholder)
				0, // paragraphCount (placeholder)
				0, // fleschScore (placeholder)
				0, // keywordDensity (placeholder)
				Date.now() // analysisTimestamp
			);
		} catch ( error ) {
			console.error(
				'Analysis failed, falling back to main thread:',
				error
			);

			// Fallback to main thread analysis
			try {
				const { analyzeSEO } = await import(
					'../workers/analysis-worker'
				);
				const result = analyzeSEO( {
					title: contentSnapshot.title,
					description: contentSnapshot.excerpt,
					content: contentSnapshot.content,
					slug: extractSlugFromPermalink( contentSnapshot.permalink ),
					focusKeyword: contentSnapshot.focusKeyword,
				} );

				// Update store with results (using new signature with all fields)
				dispatch.setAnalysisResults(
					result.results, // seoResults
					[], // readabilityResults (placeholder for now)
					result.score, // seoScore
					result.score, // readabilityScore (placeholder)
					0, // wordCount (placeholder)
					0, // sentenceCount (placeholder)
					0, // paragraphCount (placeholder)
					0, // fleschScore (placeholder)
					0, // keywordDensity (placeholder)
					Date.now() // analysisTimestamp
				);
			} catch ( fallbackError ) {
				console.error(
					'Main thread analysis also failed:',
					fallbackError
				);
			}
		} finally {
			// Set isAnalyzing to false
			dispatch.setAnalyzing( false );
		}
	};
};

/**
 * Extract slug from permalink URL
 * @param permalink
 */
function extractSlugFromPermalink( permalink: string ): string {
	try {
		const url = new URL( permalink );
		const pathParts = url.pathname.split( '/' ).filter( Boolean );
		return pathParts[ pathParts.length - 1 ] || '';
	} catch {
		return '';
	}
}
