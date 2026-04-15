/**
 * Action Creators for meowseo/data Redux Store
 */

import { ContentSnapshot, AnalysisResult, TabType } from './types';

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

export interface SetAnalysisResultsAction {
  type: typeof SET_ANALYSIS_RESULTS;
  payload: {
    seoScore: number;
    readabilityScore: number;
    analysisResults: AnalysisResult[];
  };
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
): UpdateContentSnapshotAction => ({
  type: UPDATE_CONTENT_SNAPSHOT,
  payload: snapshot,
});

export const setAnalyzing = (isAnalyzing: boolean): SetAnalyzingAction => ({
  type: SET_ANALYZING,
  payload: isAnalyzing,
});

export const setAnalysisResults = (
  seoScore: number,
  readabilityScore: number,
  analysisResults: AnalysisResult[]
): SetAnalysisResultsAction => ({
  type: SET_ANALYSIS_RESULTS,
  payload: {
    seoScore,
    readabilityScore,
    analysisResults,
  },
});

export const setActiveTab = (tab: TabType): SetActiveTabAction => ({
  type: SET_ACTIVE_TAB,
  payload: tab,
});
