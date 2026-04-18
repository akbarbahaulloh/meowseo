/**
 * Reducer for meowseo/data Redux Store
 *
 * Requirements: 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9
 */

import { MeowSEOState } from './types';
import {
	MeowSEOAction,
	UPDATE_CONTENT_SNAPSHOT,
	SET_ANALYZING,
	SET_ANALYSIS_RESULTS,
	SET_ACTIVE_TAB,
} from './actions';

// Initial State
export const initialState: MeowSEOState = {
	// Analysis scores
	seoScore: 0,
	readabilityScore: 0,

	// SEO analysis results
	analysisResults: [],

	// Readability analysis results
	// Requirements: 3.2
	readabilityResults: [],

	// Content metrics
	// Requirements: 3.3, 3.4, 3.5
	wordCount: 0,
	sentenceCount: 0,
	paragraphCount: 0,

	// Readability metrics
	// Requirements: 3.6, 3.7
	fleschScore: 0,
	keywordDensity: 0,

	// Analysis metadata
	// Requirements: 3.8
	analysisTimestamp: null,

	// UI state
	activeTab: 'general',
	isAnalyzing: false,

	// Content snapshot
	contentSnapshot: {
		title: '',
		content: '',
		excerpt: '',
		focusKeyword: '',
		postType: '',
		permalink: '',
	},
};

// Reducer
export const reducer = (
	state: MeowSEOState = initialState,
	action: MeowSEOAction
): MeowSEOState => {
	switch ( action.type ) {
		case UPDATE_CONTENT_SNAPSHOT:
			return {
				...state,
				contentSnapshot: action.payload,
			};

		case SET_ANALYZING:
			return {
				...state,
				isAnalyzing: action.payload,
			};

		case SET_ANALYSIS_RESULTS:
			return {
				...state,
				// Scores
				seoScore: action.payload.seoScore,
				readabilityScore: action.payload.readabilityScore,
				// Results arrays
				analysisResults: action.payload.seoResults,
				readabilityResults: action.payload.readabilityResults,
				// Content metrics
				wordCount: action.payload.wordCount,
				sentenceCount: action.payload.sentenceCount,
				paragraphCount: action.payload.paragraphCount,
				// Readability metrics
				fleschScore: action.payload.fleschScore,
				keywordDensity: action.payload.keywordDensity,
				// Metadata
				analysisTimestamp: action.payload.analysisTimestamp,
			};

		case SET_ACTIVE_TAB:
			return {
				...state,
				activeTab: action.payload,
			};

		default:
			return state;
	}
};
