/**
 * Store Types for meowseo/data Redux Store
 */

export interface AnalysisResult {
  id: string;
  type: 'good' | 'ok' | 'problem';
  message: string;
}

export interface ContentSnapshot {
  title: string;
  content: string;
  excerpt: string;
  focusKeyword: string;
  postType: string;
  permalink: string;
}

export interface MeowSEOState {
  // Analysis scores
  seoScore: number; // 0-100
  readabilityScore: number; // 0-100
  analysisResults: AnalysisResult[];
  
  // UI state
  activeTab: 'general' | 'social' | 'schema' | 'advanced';
  isAnalyzing: boolean;
  
  // Content snapshot
  contentSnapshot: ContentSnapshot;
}

export type TabType = 'general' | 'social' | 'schema' | 'advanced';
