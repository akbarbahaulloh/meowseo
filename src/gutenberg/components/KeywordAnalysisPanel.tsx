/**
 * KeywordAnalysisPanel Component
 *
 * Displays per-keyword analysis results for primary and secondary keywords.
 * Each keyword gets its own score row with expandable details showing
 * individual check results (density, in-title, in-headings, etc.).
 *
 * Requirements: 2.9
 */

import { memo, useState, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { STORE_NAME } from '../store';
import './KeywordAnalysisPanel.css';

/**
 * Get color based on score
 * - Red: score < 40
 * - Orange: score 40-69
 * - Green: score >= 70
 *
 * @param score
 */
const getScoreColor = ( score: number ): string => {
	if ( score < 40 ) {
		return '#dc3232'; // Red
	} else if ( score < 70 ) {
		return '#f56e28'; // Orange
	}
	return '#46b450'; // Green
};

/**
 * Get score label based on score
 * @param score
 */
const getScoreLabel = ( score: number ): string => {
	if ( score < 40 ) {
		return __( 'Needs Improvement', 'meowseo' );
	} else if ( score < 70 ) {
		return __( 'Good', 'meowseo' );
	}
	return __( 'Excellent', 'meowseo' );
};

/**
 * Get check label for display
 * @param checkId
 */
const getCheckLabel = ( checkId: string ): string => {
	const labels: Record< string, string > = {
		density: __( 'Keyword Density', 'meowseo' ),
		in_title: __( 'In Title', 'meowseo' ),
		in_headings: __( 'In Headings', 'meowseo' ),
		in_slug: __( 'In Slug', 'meowseo' ),
		in_first_paragraph: __( 'In First Paragraph', 'meowseo' ),
		in_meta_description: __( 'In Meta Description', 'meowseo' ),
	};
	return labels[ checkId ] || checkId;
};

interface KeywordCheckResult {
	score: number;
	status: 'good' | 'ok' | 'problem';
}

interface KeywordAnalysisData {
	density?: KeywordCheckResult;
	in_title?: KeywordCheckResult;
	in_headings?: KeywordCheckResult;
	in_slug?: KeywordCheckResult;
	in_first_paragraph?: KeywordCheckResult;
	in_meta_description?: KeywordCheckResult;
	overall_score?: number;
}

interface KeywordScoreRowProps {
	keyword: string;
	analysisData: KeywordAnalysisData;
	isPrimary: boolean;
}

/**
 * KeywordScoreRow Component
 *
 * Displays a single keyword's analysis with expandable details.
 */
const KeywordScoreRow: React.FC< KeywordScoreRowProps > = memo(
	( { keyword, analysisData, isPrimary } ) => {
		const [ isExpanded, setIsExpanded ] = useState( false );

		const toggleExpanded = useCallback( () => {
			setIsExpanded( ( prev ) => ! prev );
		}, [] );

		const overallScore = analysisData.overall_score || 0;
		const scoreColor = getScoreColor( overallScore );
		const scoreLabel = getScoreLabel( overallScore );

		// Get individual check results
		const checks = [
			{ id: 'density', data: analysisData.density },
			{ id: 'in_title', data: analysisData.in_title },
			{ id: 'in_headings', data: analysisData.in_headings },
			{ id: 'in_slug', data: analysisData.in_slug },
			{
				id: 'in_first_paragraph',
				data: analysisData.in_first_paragraph,
			},
			{
				id: 'in_meta_description',
				data: analysisData.in_meta_description,
			},
		].filter( ( check ) => check.data !== undefined );

		return (
			<div className="meowseo-keyword-score-row">
				<button
					type="button"
					className="meowseo-keyword-score-header"
					onClick={ toggleExpanded }
					aria-expanded={ isExpanded }
				>
					<div className="meowseo-keyword-info">
						<span className="meowseo-keyword-name">
							{ keyword }
							{ isPrimary && (
								<span className="meowseo-keyword-badge">
									{ __( 'Primary', 'meowseo' ) }
								</span>
							) }
						</span>
					</div>
					<div className="meowseo-keyword-score">
						<span
							className="meowseo-keyword-score-value"
							style={ { color: scoreColor } }
						>
							{ overallScore }
						</span>
						<span
							className="meowseo-keyword-score-label"
							style={ { color: scoreColor } }
						>
							{ scoreLabel }
						</span>
					</div>
					<span className="meowseo-keyword-toggle">
						{ isExpanded ? '▲' : '▼' }
					</span>
				</button>

				{ isExpanded && (
					<div className="meowseo-keyword-details">
						{ checks.length === 0 ? (
							<div className="meowseo-keyword-no-checks">
								{ __(
									'No analysis data available',
									'meowseo'
								) }
							</div>
						) : (
							checks.map( ( check ) => {
								const checkData = check.data!;
								const checkColor = getScoreColor(
									checkData.score
								);
								const statusIcon =
									checkData.status === 'good'
										? '✓'
										: checkData.status === 'ok'
										? '⚠'
										: '✗';

								return (
									<div
										key={ check.id }
										className="meowseo-keyword-check-row"
									>
										<span
											className="meowseo-keyword-check-icon"
											style={ { color: checkColor } }
											aria-label={
												checkData.status === 'good'
													? __( 'Good', 'meowseo' )
													: checkData.status === 'ok'
													? __( 'OK', 'meowseo' )
													: __(
															'Problem',
															'meowseo'
													  )
											}
										>
											{ statusIcon }
										</span>
										<span className="meowseo-keyword-check-label">
											{ getCheckLabel( check.id ) }
										</span>
										<span
											className="meowseo-keyword-check-score"
											style={ { color: checkColor } }
										>
											{ checkData.score }
										</span>
									</div>
								);
							} )
						) }
					</div>
				) }
			</div>
		);
	}
);

KeywordScoreRow.displayName = 'KeywordScoreRow';

/**
 * KeywordAnalysisPanel Component
 *
 * Requirements:
 * - 2.9: Display separate score row for each keyword
 * - Display keyword name as row header
 * - Display overall score for that keyword
 * - Display expandable details with individual check scores
 * - Use color coding (red/orange/green) based on score
 */
export const KeywordAnalysisPanel: React.FC = memo( () => {
	const { primaryKeyword, secondaryKeywords, keywordAnalysis, isAnalyzing } =
		useSelect( ( select ) => {
			try {
				const store = select( STORE_NAME ) as any;
				if ( ! store ) {
					console.warn(
						'MeowSEO: meowseo/data store not available in KeywordAnalysisPanel'
					);
					return {
						primaryKeyword: '',
						secondaryKeywords: [],
						keywordAnalysis: {},
						isAnalyzing: false,
					};
				}

				// Get content snapshot which includes focus keyword
				const contentSnapshot = store.getContentSnapshot();
				const primaryKeyword = contentSnapshot.focusKeyword || '';

				// Get secondary keywords from postmeta
				// Note: This will be populated by the backend analysis
				// For now, we'll use an empty array as placeholder
				const secondaryKeywords: string[] = [];

				// Get keyword analysis results from postmeta
				// Note: This will be populated by the backend analysis
				// For now, we'll use an empty object as placeholder
				const keywordAnalysis: Record<
					string,
					KeywordAnalysisData
				> = {};

				return {
					primaryKeyword,
					secondaryKeywords,
					keywordAnalysis,
					isAnalyzing: store.getIsAnalyzing(),
				};
			} catch ( error ) {
				console.error(
					'MeowSEO: Error reading from meowseo/data store in KeywordAnalysisPanel:',
					error
				);
				return {
					primaryKeyword: '',
					secondaryKeywords: [],
					keywordAnalysis: {},
					isAnalyzing: false,
				};
			}
		}, [] );

	// Combine primary and secondary keywords
	const allKeywords = [
		primaryKeyword,
		...secondaryKeywords,
	].filter( Boolean );

	return (
		<div className="meowseo-keyword-analysis-panel">
			<h3 className="meowseo-keyword-analysis-title">
				{ __( 'Keyword Analysis', 'meowseo' ) }
			</h3>

			{ isAnalyzing ? (
				<div className="meowseo-keyword-analysis-loading">
					<Spinner />
					<span>{ __( 'Analyzing keywords…', 'meowseo' ) }</span>
				</div>
			) : allKeywords.length === 0 ? (
				<div className="meowseo-keyword-analysis-empty">
					{ __(
						'Add a focus keyword to see analysis results.',
						'meowseo'
					) }
				</div>
			) : (
				<div className="meowseo-keyword-analysis-list">
					{ allKeywords.map( ( keyword, index ) => {
						const analysisData =
							keywordAnalysis[ keyword ] || {};
						const isPrimary = index === 0;

						return (
							<KeywordScoreRow
								key={ keyword }
								keyword={ keyword }
								analysisData={ analysisData }
								isPrimary={ isPrimary }
							/>
						);
					} ) }
				</div>
			) }
		</div>
	);
} );

KeywordAnalysisPanel.displayName = 'KeywordAnalysisPanel';
