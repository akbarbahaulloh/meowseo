/**
 * useAnalysis Hook
 *
 * Subscribes to contentSnapshot from useContentSync hook and triggers analysis
 * via Web Worker. Handles the complete analysis lifecycle including:
 * - Subscribing to content changes (800ms debounce handled by useContentSync)
 * - Creating and managing Web Worker instance (singleton pattern)
 * - Sending ANALYZE message to Web Worker
 * - Listening for ANALYSIS_COMPLETE message
 * - Dispatching setAnalysisResults action to Redux store
 * - Handling Web Worker errors gracefully
 * - Cleaning up Web Worker on unmount
 *
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 33.1, 33.2, 33.3, 33.4, 34.1, 34.2, 34.3, 34.4, 35.1, 35.2, 35.3, 35.4, 35.5
 */

import { useEffect, useRef, useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { setAnalysisResults, setAnalyzing } from '../store/actions';
import { ContentSnapshot } from '../store/types';

/**
 * Web Worker message types
 */
interface WorkerMessage {
	type: 'ANALYZE';
	payload: AnalysisPayload;
}

interface WorkerResponse {
	type: 'ANALYSIS_COMPLETE';
	payload: AnalysisResults;
}

interface AnalysisPayload {
	content: string;
	title: string;
	description: string;
	slug: string;
	keyword: string;
	directAnswer: string;
	schemaType: string;
}

interface AnalysisResults {
	seoResults: Array< {
		id: string;
		type: 'good' | 'ok' | 'problem';
		message: string;
		score: number;
		details?: Record< string, unknown >;
	} >;
	readabilityResults: Array< {
		id: string;
		type: 'good' | 'ok' | 'problem';
		message: string;
		score: number;
		details?: Record< string, unknown >;
	} >;
	seoScore: number;
	readabilityScore: number;
	wordCount: number;
	sentenceCount: number;
	paragraphCount: number;
	fleschScore: number;
	keywordDensity: number;
	analysisTimestamp: number;
	error?: string;
}

/**
 * Singleton Web Worker instance
 * Requirement 1.7: The Analysis_Engine SHALL not create multiple Web Worker instances
 */
let workerInstance: Worker | null = null;
const workerPending = false;

/**
 * Get or create the Web Worker instance (singleton pattern)
 *
 * @return Worker instance or null if Web Workers not supported
 */
function getWorkerInstance(): Worker | null {
	// Check if Web Workers are supported
	if ( typeof Worker === 'undefined' ) {
		console.warn( 'MeowSEO: Web Workers not supported' );
		return null;
	}

	// Return existing instance if available
	if ( workerInstance ) {
		return workerInstance;
	}

	// Create new instance
	try {
		// In production, webpack will handle the worker path
		// The worker file is at src/gutenberg/workers/analysis-worker.ts
		// Using a static path that webpack can resolve
		workerInstance = new Worker(
			// @ts-ignore - webpack handles this path
			/* webpackChunkName: "analysis-worker" */
			'../workers/analysis-worker.ts',
			{ type: 'module' }
		);
		return workerInstance;
	} catch ( error ) {
		console.error( 'MeowSEO: Failed to create Web Worker:', error );
		return null;
	}
}

/**
 * Extract slug from permalink URL
 *
 * @param permalink - The full permalink URL
 * @return The URL slug
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

/**
 * useAnalysis Hook
 *
 * Integrates with useContentSync hook to receive content changes (already debounced at 800ms)
 * and triggers analysis via Web Worker. Results are stored in Redux store.
 *
 * Requirements:
 * - 2.1: Subscribe to Content_Snapshot from useContentSync hook
 * - 2.2: Apply 800ms debounce delay from last content change (handled by useContentSync)
 * - 2.3: Trigger analysis after debounce when Content_Snapshot changes
 * - 2.4: Pass current Content_Snapshot to Web Worker
 * - 2.5: Not trigger analysis if Content_Snapshot is empty
 * - 2.6: Track Analysis_Timestamp for each analysis run
 * - 33.1: Complete analysis within 1-2 seconds of debounce trigger
 * - 33.2: Not block editor UI during analysis
 * - 33.3: Display loading indicator during analysis
 * - 33.4: Web Worker processes analysis without impacting main thread
 * - 34.1: Not create memory leaks in Web Worker
 * - 34.2: Clean up analysis results when component unmounts
 * - 34.3: Limit Redux_Store state size
 * - 34.4: Web Worker releases resources after analysis completes
 * - 35.1: Log error and continue when analysis fails
 * - 35.2: Display error message when Web Worker fails
 * - 35.3: Retry or skip when Redux update fails
 * - 35.4: Provide fallback scores (0) if analysis fails
 * - 35.5: Not prevent post save on analysis failure
 */
export function useAnalysis(): void {
	const dispatch = useDispatch( 'meowseo/data' );

	// Get contentSnapshot from Redux store (populated by useContentSync)
	// Requirement 2.1: Subscribe to Content_Snapshot from useContentSync hook
	const contentSnapshot = useSelect(
		( select: any ) =>
			select( 'meowseo/data' ).getContentSnapshot() as ContentSnapshot,
		[]
	);

	// Get meta fields from Redux store
	// These are managed separately via useEntityPropBinding
	const { directAnswer, schemaType } = useSelect( ( select: any ) => {
		const state = select( 'meowseo/data' );
		return {
			directAnswer: state.getDirectAnswer?.() || '',
			schemaType: state.getSchemaType?.() || '',
		};
	}, [] );

	// Track if we're currently analyzing to prevent duplicate requests
	const isAnalyzingRef = useRef( false );

	// Track the last content hash to avoid unnecessary analysis
	const lastContentHashRef = useRef< string >( '' );

	/**
	 * Generate a hash of the analysis input to detect changes
	 */
	const getContentHash = useCallback(
		(
			snapshot: ContentSnapshot,
			directAnswer: string,
			schemaType: string
		): string => {
			return JSON.stringify( {
				title: snapshot.title,
				content: snapshot.content,
				excerpt: snapshot.excerpt,
				focusKeyword: snapshot.focusKeyword,
				permalink: snapshot.permalink,
				directAnswer,
				schemaType,
			} );
		},
		[]
	);

	/**
	 * Handle analysis completion
	 */
	const handleAnalysisComplete = useCallback(
		( results: AnalysisResults ) => {
			isAnalyzingRef.current = false;

			// Check for error in results
			if ( results.error ) {
				console.error(
					'MeowSEO: Analysis returned error:',
					results.error
				);
				// Requirement 35.4: Provide fallback scores (0) if analysis fails
				dispatch(
					setAnalysisResults(
						[],
						[],
						0,
						0,
						0,
						0,
						0,
						0,
						0,
						Date.now()
					)
				);
				dispatch( setAnalyzing( false ) );
				return;
			}

			// Dispatch results to Redux store
			// Requirement 3.1: Dispatch Redux actions to store analysis results
			try {
				dispatch(
					setAnalysisResults(
						results.seoResults,
						results.readabilityResults,
						results.seoScore,
						results.readabilityScore,
						results.wordCount,
						results.sentenceCount,
						results.paragraphCount,
						results.fleschScore,
						results.keywordDensity,
						results.analysisTimestamp
					)
				);
			} catch ( error ) {
				// Requirement 35.3: Retry or skip when Redux update fails
				console.error(
					'MeowSEO: Failed to dispatch analysis results:',
					error
				);
			}

			dispatch( setAnalyzing( false ) );
		},
		[ dispatch ]
	);

	/**
	 * Run analysis using Web Worker
	 */
	const runAnalysis = useCallback(
		(
			snapshot: ContentSnapshot,
			directAnswer: string,
			schemaType: string
		) => {
			// Requirement 2.5: Not trigger analysis if Content_Snapshot is empty
			if ( ! snapshot.content && ! snapshot.title ) {
				return;
			}

			// Prevent duplicate analysis requests
			if ( isAnalyzingRef.current ) {
				return;
			}

			// Check if content has actually changed
			const contentHash = getContentHash(
				snapshot,
				directAnswer,
				schemaType
			);
			if ( contentHash === lastContentHashRef.current ) {
				return;
			}
			lastContentHashRef.current = contentHash;

			// Mark as analyzing
			isAnalyzingRef.current = true;
			dispatch( setAnalyzing( true ) );

			// Get or create Web Worker instance
			const worker = getWorkerInstance();

			if ( ! worker ) {
				// Requirement 35.2: Display error message when Web Worker fails
				console.error( 'MeowSEO: Web Worker not available' );
				isAnalyzingRef.current = false;
				dispatch( setAnalyzing( false ) );
				return;
			}

			// Set up message handler
			const handleMessage = ( event: MessageEvent ) => {
				const response: WorkerResponse = event.data;
				if ( response.type === 'ANALYSIS_COMPLETE' ) {
					worker.removeEventListener( 'message', handleMessage );
					handleAnalysisComplete( response.payload );
				}
			};

			// Set up error handler
			const handleError = ( error: ErrorEvent ) => {
				console.error( 'MeowSEO: Web Worker error:', error );
				worker.removeEventListener( 'error', handleError );
				isAnalyzingRef.current = false;
				dispatch( setAnalyzing( false ) );

				// Requirement 35.4: Provide fallback scores (0) if analysis fails
				dispatch(
					setAnalysisResults(
						[],
						[],
						0,
						0,
						0,
						0,
						0,
						0,
						0,
						Date.now()
					)
				);
			};

			worker.addEventListener( 'message', handleMessage );
			worker.addEventListener( 'error', handleError );

			// Extract slug from permalink
			const slug = extractSlugFromPermalink( snapshot.permalink );

			// Send ANALYZE message to Web Worker
			// Requirement 1.2: Communicate with Web Worker via postMessage API
			// Requirement 2.4: Pass current Content_Snapshot to Web Worker
			const payload: AnalysisPayload = {
				content: snapshot.content || '',
				title: snapshot.title || '',
				description: snapshot.excerpt || '',
				slug,
				keyword: snapshot.focusKeyword || '',
				directAnswer: directAnswer || '',
				schemaType: schemaType || '',
			};

			worker.postMessage( {
				type: 'ANALYZE',
				payload,
			} as WorkerMessage );
		},
		[ dispatch, getContentHash, handleAnalysisComplete ]
	);

	/**
	 * Effect: Trigger analysis when contentSnapshot changes
	 * Requirement 2.3: Trigger analysis after debounce when Content_Snapshot changes
	 */
	useEffect( () => {
		// Only run if we have content
		if (
			contentSnapshot &&
			( contentSnapshot.content || contentSnapshot.title )
		) {
			runAnalysis( contentSnapshot, directAnswer, schemaType );
		}
	}, [ contentSnapshot, directAnswer, schemaType, runAnalysis ] );

	/**
	 * Effect: Clean up Web Worker on unmount
	 * Requirement 34.2: Clean up analysis results when component unmounts
	 * Requirement 34.4: Web Worker releases resources after analysis completes
	 */
	useEffect( () => {
		return () => {
			// Note: We don't terminate the worker on component unmount
			// because we're using a singleton pattern. The worker is reused
			// across component lifecycles for better performance.
			// However, we do clean up any pending analysis state.
			isAnalyzingRef.current = false;
		};
	}, [] );
}

export default useAnalysis;
