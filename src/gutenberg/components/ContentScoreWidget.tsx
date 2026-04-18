/**
 * ContentScoreWidget Component
 *
 * Displays SEO and readability scores with color-coded indicators,
 * score breakdown by analyzer category, and expandable detailed results.
 *
 * Requirements: 30.1, 30.2, 30.3, 30.4, 30.5
 */

import { memo, useState, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { STORE_NAME } from '../store';
import { AnalysisResult } from '../store/types';
import { AnalyzerResultItem } from './AnalyzerResultItem';
import './ContentScoreWidget.css';

/**
 * Get color based on score
 * - Red: score < 40
 * - Orange: score 40-69
 * - Green: score >= 70
 *
 * Requirement 30.4: Color coding
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

interface ScoreCircleProps {
	score: number;
	label: string;
	isLoading: boolean;
}

/**
 * Score Circle Component
 *
 * Displays a circular score indicator with color coding.
 */
const ScoreCircle: React.FC< ScoreCircleProps > = memo(
	( { score, label, isLoading } ) => {
		const color = getScoreColor( score );
		const circumference = 2 * Math.PI * 45; // radius = 45
		const strokeDashoffset =
			circumference - ( score / 100 ) * circumference;

		return (
			<div className="meowseo-score-circle-container">
				<div className="meowseo-score-circle">
					{ isLoading ? (
						<div className="meowseo-score-loading">
							<Spinner />
						</div>
					) : (
						<svg
							viewBox="0 0 100 100"
							className="meowseo-score-svg"
						>
							<circle
								className="meowseo-score-circle-bg"
								cx="50"
								cy="50"
								r="45"
								fill="none"
								stroke="#e0e0e0"
								strokeWidth="8"
							/>
							<circle
								className="meowseo-score-circle-progress"
								cx="50"
								cy="50"
								r="45"
								fill="none"
								stroke={ color }
								strokeWidth="8"
								strokeLinecap="round"
								strokeDasharray={ circumference }
								strokeDashoffset={ strokeDashoffset }
								transform="rotate(-90 50 50)"
							/>
						</svg>
					) }
					<div className="meowseo-score-value" style={ { color } }>
						{ isLoading ? '-' : score }
					</div>
				</div>
				<div className="meowseo-score-label">{ label }</div>
				{ ! isLoading && (
					<div className="meowseo-score-status" style={ { color } }>
						{ getScoreLabel( score ) }
					</div>
				) }
			</div>
		);
	}
);

ScoreCircle.displayName = 'ScoreCircle';

interface AnalyzerCategoryProps {
	title: string;
	results: AnalysisResult[];
	isExpanded: boolean;
	onToggle: () => void;
	isLoading: boolean;
}

/**
 * Analyzer Category Component
 *
 * Displays a collapsible section for analyzer results by category.
 */
const AnalyzerCategory: React.FC< AnalyzerCategoryProps > = memo(
	( { title, results, isExpanded, onToggle, isLoading } ) => {
		const goodCount = results.filter( ( r ) => r.type === 'good' ).length;
		const totalCount = results.length;

		return (
			<div className="meowseo-analyzer-category">
				<button
					type="button"
					className="meowseo-analyzer-category-header"
					onClick={ onToggle }
					aria-expanded={ isExpanded }
				>
					<span className="meowseo-analyzer-category-title">
						{ title }
					</span>
					<span className="meowseo-analyzer-category-count">
						{ isLoading ? (
							<Spinner />
						) : (
							`${ goodCount }/${ totalCount } ${ __(
								'passed',
								'meowseo'
							) }`
						) }
					</span>
					<span className="meowseo-analyzer-category-toggle">
						{ isExpanded ? '▲' : '▼' }
					</span>
				</button>
				{ isExpanded && ! isLoading && (
					<div className="meowseo-analyzer-category-results">
						{ results.length === 0 ? (
							<div className="meowseo-analyzer-no-results">
								{ __(
									'No analysis results available',
									'meowseo'
								) }
							</div>
						) : (
							results.map( ( result ) => (
								<AnalyzerResultItem
									key={ result.id }
									result={ result }
								/>
							) )
						) }
					</div>
				) }
			</div>
		);
	}
);

AnalyzerCategory.displayName = 'AnalyzerCategory';

/**
 * ContentScoreWidget Component
 *
 * Requirements:
 * - 30.1: Display SEO_Score prominently
 * - 30.2: Display Readability_Score prominently
 * - 30.3: Display score breakdown by analyzer category
 * - 30.4: Use color coding (green ≥70, orange 40-69, red <40)
 * - 30.5: Update in real-time as content changes
 */
export const ContentScoreWidget: React.FC = memo( () => {
	const [ isSeoExpanded, setIsSeoExpanded ] = useState( false );
	const [ isReadabilityExpanded, setIsReadabilityExpanded ] =
		useState( false );

	const {
		seoScore,
		readabilityScore,
		seoResults,
		readabilityResults,
		isAnalyzing,
	} = useSelect( ( select ) => {
		try {
			const store = select( STORE_NAME ) as any;
			if ( ! store ) {
				console.warn(
					'MeowSEO: meowseo/data store not available in ContentScoreWidget'
				);
				return {
					seoScore: 0,
					readabilityScore: 0,
					seoResults: [],
					readabilityResults: [],
					isAnalyzing: false,
				};
			}
			return {
				seoScore: store.getSeoScore(),
				readabilityScore: store.getReadabilityScore(),
				seoResults: store.getAnalysisResults(),
				readabilityResults: store.getReadabilityResults(),
				isAnalyzing: store.getIsAnalyzing(),
			};
		} catch ( error ) {
			console.error(
				'MeowSEO: Error reading from meowseo/data store:',
				error
			);
			return {
				seoScore: 0,
				readabilityScore: 0,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			};
		}
	}, [] );

	const toggleSeoExpanded = useCallback( () => {
		setIsSeoExpanded( ( prev ) => ! prev );
	}, [] );

	const toggleReadabilityExpanded = useCallback( () => {
		setIsReadabilityExpanded( ( prev ) => ! prev );
	}, [] );

	return (
		<div className="meowseo-content-score-widget">
			{ /* Score Circles */ }
			<div className="meowseo-scores">
				<ScoreCircle
					score={ seoScore }
					label={ __( 'SEO Score', 'meowseo' ) }
					isLoading={ isAnalyzing }
				/>
				<ScoreCircle
					score={ readabilityScore }
					label={ __( 'Readability', 'meowseo' ) }
					isLoading={ isAnalyzing }
				/>
			</div>

			{ /* Analyzer Categories */ }
			<div className="meowseo-analyzer-categories">
				<AnalyzerCategory
					title={ __( 'SEO Analysis', 'meowseo' ) }
					results={ seoResults }
					isExpanded={ isSeoExpanded }
					onToggle={ toggleSeoExpanded }
					isLoading={ isAnalyzing }
				/>
				<AnalyzerCategory
					title={ __( 'Readability Analysis', 'meowseo' ) }
					results={ readabilityResults }
					isExpanded={ isReadabilityExpanded }
					onToggle={ toggleReadabilityExpanded }
					isLoading={ isAnalyzing }
				/>
			</div>
		</div>
	);
} );

ContentScoreWidget.displayName = 'ContentScoreWidget';
