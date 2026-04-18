/**
 * Analysis Tab Component
 *
 * SEO score and readability indicators with check lists.
 * Reads from meowseo/data store (updated by ContentSyncHook).
 *
 * @package
 * @since 1.0.0
 */

import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';

/**
 * Analysis Tab Component
 */
export default function AnalysisTab() {
	const { seoScore, seoChecks, readabilityScore, readabilityChecks } =
		useSelect( ( select ) => {
			return {
				seoScore: select( 'meowseo/data' ).getSeoScore(),
				seoChecks: select( 'meowseo/data' ).getSeoChecks(),
				readabilityScore:
					select( 'meowseo/data' ).getReadabilityScore(),
				readabilityChecks:
					select( 'meowseo/data' ).getReadabilityChecks(),
			};
		}, [] );

	/**
	 * Get color class based on score
	 *
	 * @param {number} score Score (0-100)
	 * @return {string} Color class
	 */
	const getScoreColor = ( score ) => {
		if ( score >= 70 ) {
			return 'green';
		}
		if ( score >= 40 ) {
			return 'orange';
		}
		return 'red';
	};

	/**
	 * Render score indicator
	 *
	 * @param {string} label Label text
	 * @param {number} score Score (0-100)
	 * @return {JSX.Element} Score indicator
	 */
	const renderScoreIndicator = ( label, score ) => {
		const color = getScoreColor( score );
		return (
			<div
				className={ `meowseo-score-indicator meowseo-score-${ color }` }
			>
				<div className="meowseo-score-label">{ label }</div>
				<div className="meowseo-score-value">{ score }</div>
				<div className="meowseo-score-bar">
					<div
						className="meowseo-score-bar-fill"
						style={ { width: `${ score }%` } }
					/>
				</div>
			</div>
		);
	};

	/**
	 * Render check list
	 *
	 * @param {Array} checks Array of check result objects
	 * @return {JSX.Element} Check list
	 */
	const renderCheckList = ( checks ) => {
		if ( ! checks || checks.length === 0 ) {
			return (
				<p className="meowseo-no-checks">
					{ __( 'No checks available', 'meowseo' ) }
				</p>
			);
		}

		return (
			<ul className="meowseo-check-list">
				{ checks.map( ( check ) => (
					<li
						key={ check.id }
						className={ `meowseo-check-item meowseo-check-${
							check.pass ? 'pass' : 'fail'
						}` }
					>
						<Icon
							icon={ check.pass ? 'yes-alt' : 'dismiss' }
							className="meowseo-check-icon"
						/>
						<span className="meowseo-check-label">
							{ check.label }
						</span>
					</li>
				) ) }
			</ul>
		);
	};

	return (
		<div className="meowseo-analysis-tab">
			<div className="meowseo-analysis-section">
				<h3>{ __( 'SEO Analysis', 'meowseo' ) }</h3>
				{ renderScoreIndicator(
					__( 'SEO Score', 'meowseo' ),
					seoScore
				) }
				{ renderCheckList( seoChecks ) }
			</div>

			<div className="meowseo-analysis-section">
				<h3>{ __( 'Readability Analysis', 'meowseo' ) }</h3>
				{ renderScoreIndicator(
					__( 'Readability Score', 'meowseo' ),
					readabilityScore
				) }
				{ renderCheckList( readabilityChecks ) }
			</div>
		</div>
	);
}
