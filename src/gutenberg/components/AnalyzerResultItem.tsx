/**
 * AnalyzerResultItem Component
 *
 * Displays individual analyzer result with status icon, message,
 * and expandable details section.
 *
 * Requirements: 22.1, 22.2, 22.3, 22.4, 22.5, 22.6, 22.7
 */

import { memo, useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { AnalysisResult } from '../store/types';
import './AnalyzerResultItem.css';

/**
 * Get status icon based on result type
 * - good: ✓ (checkmark)
 * - ok: ⚠ (warning)
 * - problem: ✗ (cross)
 * @param type
 */
const getStatusIcon = ( type: AnalysisResult[ 'type' ] ): string => {
	switch ( type ) {
		case 'good':
			return '✓';
		case 'ok':
			return '⚠';
		case 'problem':
			return '✗';
		default:
			return '';
	}
};

/**
 * Get color based on result type
 * - good: green
 * - ok: orange/yellow
 * - problem: red
 * @param type
 */
const getStatusColor = ( type: AnalysisResult[ 'type' ] ): string => {
	switch ( type ) {
		case 'good':
			return '#46b450'; // Green
		case 'ok':
			return '#f56e28'; // Orange
		case 'problem':
			return '#dc3232'; // Red
		default:
			return '#757575'; // Gray
	}
};

interface AnalyzerResultItemProps {
	/** The analyzer result to display */
	result: AnalysisResult;
}

/**
 * AnalyzerResultItem Component
 *
 * Displays an individual analyzer result with:
 * - Status icon (✓, ⚠, ✗)
 * - Analyzer message
 * - Expandable details section (if details exist)
 * - Color coding matching status
 *
 * Requirement 22.7: Use consistent styling
 */
export const AnalyzerResultItem: React.FC< AnalyzerResultItemProps > = memo(
	( { result } ) => {
		const [ isExpanded, setIsExpanded ] = useState( false );

		const toggleExpanded = useCallback( () => {
			setIsExpanded( ( prev ) => ! prev );
		}, [] );

		const hasDetails =
			result.details && Object.keys( result.details ).length > 0;

		return (
			<div
				className="meowseo-analyzer-result-item"
				data-testid={ `analyzer-result-${ result.id }` }
			>
				<div className="meowseo-analyzer-result-header">
					<span
						className="meowseo-analyzer-status-icon"
						style={ { color: getStatusColor( result.type ) } }
						aria-label={
							result.type === 'good'
								? __( 'Good', 'meowseo' )
								: result.type === 'ok'
								? __( 'OK', 'meowseo' )
								: __( 'Problem', 'meowseo' )
						}
					>
						{ getStatusIcon( result.type ) }
					</span>
					<span className="meowseo-analyzer-message">
						{ result.message }
					</span>
					{ hasDetails && (
						<button
							type="button"
							className="meowseo-analyzer-details-toggle"
							onClick={ toggleExpanded }
							aria-expanded={ isExpanded }
							aria-label={
								isExpanded
									? __( 'Hide details', 'meowseo' )
									: __( 'Show details', 'meowseo' )
							}
						>
							{ isExpanded ? '▲' : '▼' }
						</button>
					) }
				</div>
				{ hasDetails && isExpanded && (
					<div className="meowseo-analyzer-details">
						{ Object.entries( result.details! ).map(
							( [ key, value ] ) => (
								<div
									key={ key }
									className="meowseo-analyzer-detail-row"
								>
									<span className="meowseo-analyzer-detail-key">
										{ key }:
									</span>
									<span className="meowseo-analyzer-detail-value">
										{ typeof value === 'object'
											? JSON.stringify( value )
											: String( value ) }
									</span>
								</div>
							)
						) }
					</div>
				) }
			</div>
		);
	}
);

AnalyzerResultItem.displayName = 'AnalyzerResultItem';
