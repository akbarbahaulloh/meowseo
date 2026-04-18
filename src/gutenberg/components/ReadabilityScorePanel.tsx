/**
 * ReadabilityScorePanel Component
 *
 * Displays detailed readability analysis results including all 5 readability
 * analyzers, Flesch Reading Ease score, and content metrics.
 *
 * Requirements: 31.1, 31.2, 31.3, 31.4, 31.5, 31.6
 */

import { memo, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { STORE_NAME } from '../store';
import { AnalysisResult } from '../store/types';
import { AnalyzerResultItem } from './AnalyzerResultItem';
import './ReadabilityScorePanel.css';

/**
 * Get Flesch score interpretation
 * @param score
 */
const getFleschInterpretation = ( score: number ): string => {
	if ( score >= 60 ) {
		return __( 'Easy to read', 'meowseo' );
	} else if ( score >= 40 ) {
		return __( 'Moderate difficulty', 'meowseo' );
	}
	return __( 'Difficult to read', 'meowseo' );
};

/**
 * Get Flesch score color
 * @param score
 */
const getFleschColor = ( score: number ): string => {
	if ( score >= 60 ) {
		return '#46b450'; // Green
	} else if ( score >= 40 ) {
		return '#f56e28'; // Orange
	}
	return '#dc3232'; // Red
};

interface MetricRowProps {
	label: string;
	value: number | string;
}

/**
 * Metric Row Component
 *
 * Displays a single metric row with label and value.
 */
const MetricRow: React.FC< MetricRowProps > = memo( ( { label, value } ) => {
	return (
		<div className="meowseo-readability-metric-row">
			<span className="meowseo-readability-metric-label">{ label }</span>
			<span className="meowseo-readability-metric-value">{ value }</span>
		</div>
	);
} );

MetricRow.displayName = 'MetricRow';

/**
 * ReadabilityScorePanel Component
 *
 * Requirements:
 * - 31.1: Display all 5 readability analyzer results
 * - 31.2: Show each analyzer's status icon (good/ok/problem)
 * - 31.3: Show each analyzer's message and recommendations
 * - 31.4: Display Flesch_Reading_Ease score and interpretation
 * - 31.5: Display wordCount, sentenceCount, paragraphCount metrics
 * - 31.6: Update in real-time as analysis completes
 */
export const ReadabilityScorePanel: React.FC = memo( () => {
	const {
		readabilityResults,
		wordCount,
		sentenceCount,
		paragraphCount,
		fleschScore,
		isAnalyzing,
	} = useSelect( ( select ) => {
		try {
			const store = select( STORE_NAME ) as any;
			if ( ! store ) {
				console.warn(
					'MeowSEO: meowseo/data store not available in ReadabilityScorePanel'
				);
				return {
					readabilityResults: [],
					wordCount: 0,
					sentenceCount: 0,
					paragraphCount: 0,
					fleschScore: 0,
					isAnalyzing: false,
				};
			}
			return {
				readabilityResults: store.getReadabilityResults(),
				wordCount: store.getWordCount(),
				sentenceCount: store.getSentenceCount(),
				paragraphCount: store.getParagraphCount(),
				fleschScore: store.getFleschScore(),
				isAnalyzing: store.getIsAnalyzing(),
			};
		} catch ( error ) {
			console.error(
				'MeowSEO: Error reading from meowseo/data store in ReadabilityScorePanel:',
				error
			);
			return {
				readabilityResults: [],
				wordCount: 0,
				sentenceCount: 0,
				paragraphCount: 0,
				fleschScore: 0,
				isAnalyzing: false,
			};
		}
	}, [] );

	// Separate Flesch score from other readability results
	const fleschResult = useMemo( () => {
		return readabilityResults.find(
			( r ) => r.id === 'flesch-reading-ease'
		);
	}, [ readabilityResults ] );

	const otherReadabilityResults = useMemo( () => {
		return readabilityResults.filter(
			( r ) => r.id !== 'flesch-reading-ease'
		);
	}, [ readabilityResults ] );

	const fleschColor = getFleschColor( fleschScore );
	const fleschInterpretation = getFleschInterpretation( fleschScore );

	return (
		<div className="meowseo-readability-score-panel">
			{ /* Flesch Reading Ease Section */ }
			<div className="meowseo-readability-flesch-section">
				<h3 className="meowseo-readability-section-title">
					{ __( 'Flesch Reading Ease', 'meowseo' ) }
				</h3>
				{ isAnalyzing ? (
					<div className="meowseo-readability-flesch-loading">
						<Spinner />
						<span>{ __( 'Analyzing…', 'meowseo' ) }</span>
					</div>
				) : (
					<div className="meowseo-readability-flesch-score">
						<div
							className="meowseo-readability-flesch-value"
							style={ { color: fleschColor } }
						>
							{ fleschScore }
						</div>
						<div className="meowseo-readability-flesch-interpretation">
							<div
								className="meowseo-readability-flesch-status"
								style={ { color: fleschColor } }
							>
								{ fleschInterpretation }
							</div>
							{ fleschResult && (
								<div className="meowseo-readability-flesch-message">
									{ fleschResult.message }
								</div>
							) }
						</div>
					</div>
				) }
			</div>

			{ /* Content Metrics Section */ }
			<div className="meowseo-readability-metrics-section">
				<h3 className="meowseo-readability-section-title">
					{ __( 'Content Metrics', 'meowseo' ) }
				</h3>
				{ isAnalyzing ? (
					<div className="meowseo-readability-metrics-loading">
						<Spinner />
					</div>
				) : (
					<div className="meowseo-readability-metrics">
						<MetricRow
							label={ __( 'Word Count', 'meowseo' ) }
							value={ wordCount }
						/>
						<MetricRow
							label={ __( 'Sentence Count', 'meowseo' ) }
							value={ sentenceCount }
						/>
						<MetricRow
							label={ __( 'Paragraph Count', 'meowseo' ) }
							value={ paragraphCount }
						/>
					</div>
				) }
			</div>

			{ /* Readability Analyzers Section */ }
			<div className="meowseo-readability-analyzers-section">
				<h3 className="meowseo-readability-section-title">
					{ __( 'Readability Analysis', 'meowseo' ) }
				</h3>
				{ isAnalyzing ? (
					<div className="meowseo-readability-analyzers-loading">
						<Spinner />
						<span>{ __( 'Analyzing…', 'meowseo' ) }</span>
					</div>
				) : (
					<div className="meowseo-readability-analyzers">
						{ otherReadabilityResults.length === 0 ? (
							<div className="meowseo-readability-no-results">
								{ __(
									'No analysis results available',
									'meowseo'
								) }
							</div>
						) : (
							otherReadabilityResults.map( ( result ) => (
								<AnalyzerResultItem
									key={ result.id }
									result={ result }
								/>
							) )
						) }
					</div>
				) }
			</div>
		</div>
	);
} );

ReadabilityScorePanel.displayName = 'ReadabilityScorePanel';
