/**
 * Reducer for meowseo/data Redux Store
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
  seoScore: 0,
  readabilityScore: 0,
  analysisResults: [],
  activeTab: 'general',
  isAnalyzing: false,
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
  switch (action.type) {
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
        seoScore: action.payload.seoScore,
        readabilityScore: action.payload.readabilityScore,
        analysisResults: action.payload.analysisResults,
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
