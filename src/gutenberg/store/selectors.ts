/**
 * Selectors for meowseo/data Redux Store
 *
 * Optimized selectors with memoization for performance.
 * Requirements: 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 16.6
 */

import {
	MeowSEOState,
	AnalysisResult,
	ContentSnapshot,
	TabType,
} from './types';

// Simple selectors (no memoization needed for primitive values)
export const getSeoScore = ( state: MeowSEOState ): number => {
	return state.seoScore;
};

export const getReadabilityScore = ( state: MeowSEOState ): number => {
	return state.readabilityScore;
};

export const getActiveTab = ( state: MeowSEOState ): TabType => {
	return state.activeTab;
};

export const getIsAnalyzing = ( state: MeowSEOState ): boolean => {
	return state.isAnalyzing;
};

// Direct selectors for complex objects/arrays
// WordPress's @wordpress/data automatically memoizes selectors
export const getAnalysisResults = ( state: MeowSEOState ): AnalysisResult[] => {
	return state.analysisResults;
};

/**
 * Get readability analysis results
 * Requirements: 3.2
 * @param state
 */
export const getReadabilityResults = (
	state: MeowSEOState
): AnalysisResult[] => {
	return state.readabilityResults;
};

export const getContentSnapshot = ( state: MeowSEOState ): ContentSnapshot => {
	return state.contentSnapshot;
};

/**
 * Get word count
 * Requirements: 3.3
 * @param state
 */
export const getWordCount = ( state: MeowSEOState ): number => {
	return state.wordCount;
};

/**
 * Get sentence count
 * Requirements: 3.4
 * @param state
 */
export const getSentenceCount = ( state: MeowSEOState ): number => {
	return state.sentenceCount;
};

/**
 * Get paragraph count
 * Requirements: 3.5
 * @param state
 */
export const getParagraphCount = ( state: MeowSEOState ): number => {
	return state.paragraphCount;
};

/**
 * Get Flesch Reading Ease score
 * Requirements: 3.6
 * @param state
 */
export const getFleschScore = ( state: MeowSEOState ): number => {
	return state.fleschScore;
};

/**
 * Get keyword density
 * Requirements: 3.7
 * @param state
 */
export const getKeywordDensity = ( state: MeowSEOState ): number => {
	return state.keywordDensity;
};

/**
 * Get analysis timestamp
 * Requirements: 3.8
 * @param state
 */
export const getAnalysisTimestamp = ( state: MeowSEOState ): number | null => {
	return state.analysisTimestamp;
};

// Derived selectors with manual memoization
let cachedAnalysisResultsByType: {
	good: AnalysisResult[];
	ok: AnalysisResult[];
	problem: AnalysisResult[];
} | null = null;
let lastAnalysisResults: AnalysisResult[] | null = null;

export const getAnalysisResultsByType = ( state: MeowSEOState ) => {
	// Manual memoization: only recalculate if analysisResults changed
	if ( state.analysisResults !== lastAnalysisResults ) {
		lastAnalysisResults = state.analysisResults;
		cachedAnalysisResultsByType = {
			good: state.analysisResults.filter( ( r ) => r.type === 'good' ),
			ok: state.analysisResults.filter( ( r ) => r.type === 'ok' ),
			problem: state.analysisResults.filter(
				( r ) => r.type === 'problem'
			),
		};
	}
	return cachedAnalysisResultsByType!;
};

// Derived selector: Get score color
let cachedSeoScoreColor: string | null = null;
let lastSeoScore: number | null = null;

export const getSeoScoreColor = ( state: MeowSEOState ): string => {
	// Manual memoization: only recalculate if score changed
	if ( state.seoScore !== lastSeoScore ) {
		lastSeoScore = state.seoScore;
		if ( state.seoScore < 40 ) {
			cachedSeoScoreColor = 'red';
		} else if ( state.seoScore < 70 ) {
			cachedSeoScoreColor = 'orange';
		} else {
			cachedSeoScoreColor = 'green';
		}
	}
	return cachedSeoScoreColor!;
};

let cachedReadabilityScoreColor: string | null = null;
let lastReadabilityScore: number | null = null;

export const getReadabilityScoreColor = ( state: MeowSEOState ): string => {
	// Manual memoization: only recalculate if score changed
	if ( state.readabilityScore !== lastReadabilityScore ) {
		lastReadabilityScore = state.readabilityScore;
		if ( state.readabilityScore < 40 ) {
			cachedReadabilityScoreColor = 'red';
		} else if ( state.readabilityScore < 70 ) {
			cachedReadabilityScoreColor = 'orange';
		} else {
			cachedReadabilityScoreColor = 'green';
		}
	}
	return cachedReadabilityScoreColor!;
};
