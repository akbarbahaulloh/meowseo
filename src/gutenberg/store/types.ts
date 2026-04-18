/**
 * Store Types for meowseo/data Redux Store
 *
 * Requirements: 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 22.1, 22.2, 22.3, 22.4, 22.5, 22.6, 22.7
 */

/**
 * Analyzer result details - optional additional data
 * Requirements: 22.5, 22.6, 22.7
 */
export interface AnalysisResultDetails {
	[ key: string ]: unknown;
	/** Actual values (e.g., keyword density percentage) */
	actualValue?: number | string;
	/** Recommendations when applicable */
	recommendation?: string;
}

/**
 * Individual analyzer result
 * Requirements: 22.1, 22.2, 22.3, 22.4, 22.5
 */
export interface AnalysisResult {
	/** Unique identifier (e.g., 'keyword-in-title') */
	id: string;
	/** Status type */
	type: 'good' | 'ok' | 'problem';
	/** User-facing actionable message */
	message: string;
	/** Score contribution (0-100) */
	score: number;
	/** Optional additional data */
	details?: AnalysisResultDetails;
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

	// SEO analysis results (11 analyzers)
	analysisResults: AnalysisResult[];

	// Readability analysis results (5 analyzers)
	// Requirements: 3.2
	readabilityResults: AnalysisResult[];

	// Content metrics
	// Requirements: 3.3, 3.4, 3.5
	wordCount: number;
	sentenceCount: number;
	paragraphCount: number;

	// Readability metrics
	// Requirements: 3.6, 3.7
	fleschScore: number;
	keywordDensity: number;

	// Analysis metadata
	// Requirements: 3.8, 2.6
	analysisTimestamp: number | null;

	// UI state
	activeTab: 'general' | 'social' | 'schema' | 'advanced';
	isAnalyzing: boolean;

	// Content snapshot
	contentSnapshot: ContentSnapshot;
}

export type TabType = 'general' | 'social' | 'schema' | 'advanced';
