/**
 * Selectors for meowseo/data Redux Store
 */

import { MeowSEOState, AnalysisResult, ContentSnapshot, TabType } from './types';

export const getSeoScore = (state: MeowSEOState): number => {
  return state.seoScore;
};

export const getReadabilityScore = (state: MeowSEOState): number => {
  return state.readabilityScore;
};

export const getAnalysisResults = (state: MeowSEOState): AnalysisResult[] => {
  return state.analysisResults;
};

export const getActiveTab = (state: MeowSEOState): TabType => {
  return state.activeTab;
};

export const getIsAnalyzing = (state: MeowSEOState): boolean => {
  return state.isAnalyzing;
};

export const getContentSnapshot = (state: MeowSEOState): ContentSnapshot => {
  return state.contentSnapshot;
};
