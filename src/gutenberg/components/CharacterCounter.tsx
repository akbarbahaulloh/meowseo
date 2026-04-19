/**
 * Character Counter Component
 *
 * Displays character count with color-coded status indicators.
 * Implements Sprint 2 requirements for SEO field character counting.
 *
 * Requirements: 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 2.10, 2.11
 *
 * @package MeowSEO
 * @since 2.0.0
 */

import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

interface CharacterCounterProps {
	value: string;
	maxLength: number;
	optimalMin: number;
	optimalMax: number;
	label: string;
}

type CounterStatus = 'good' | 'warning' | 'error';

/**
 * Calculate status based on character count
 *
 * Requirements:
 * - 2.6, 2.7, 2.8: Title status (red >60, orange <50, green 50-60)
 * - 2.9, 2.10, 2.11: Description status (red >155, orange <120, green 120-155)
 *
 * @param count Current character count
 * @param optimalMin Minimum optimal length
 * @param optimalMax Maximum optimal length
 * @param maxLength Maximum allowed length
 * @returns Status indicator: 'good', 'warning', or 'error'
 */
const getStatus = (
	count: number,
	optimalMin: number,
	optimalMax: number,
	maxLength: number
): CounterStatus => {
	// Requirement 2.6, 2.9: Red when exceeds maximum
	if ( count > maxLength ) {
		return 'error';
	}

	// Requirement 2.7, 2.10: Green when in optimal range
	if ( count >= optimalMin && count <= optimalMax ) {
		return 'good';
	}

	// Requirement 2.8, 2.11: Orange when below optimal
	return 'warning';
};

/**
 * Get status message for accessibility
 *
 * @param status Current status
 * @param count Current character count
 * @param maxLength Maximum length
 * @returns Accessible status message
 */
const getStatusMessage = (
	status: CounterStatus,
	count: number,
	maxLength: number
): string => {
	if ( status === 'error' ) {
		return __(
			`Character count exceeds maximum by ${ count - maxLength }`,
			'meowseo'
		);
	}
	if ( status === 'good' ) {
		return __( 'Character count is optimal', 'meowseo' );
	}
	return __( 'Character count is below optimal range', 'meowseo' );
};

/**
 * Character Counter Component
 *
 * Displays current character count with color-coded status:
 * - Green (#46b450): Optimal length
 * - Orange (#f56e28): Suboptimal but acceptable
 * - Red (#dc3232): Exceeds maximum
 *
 * Requirements:
 * - 2.4: Display current character count and maximum
 * - 2.5: Calculate and display color-coded status
 * - 2.11: Update immediately on input change (no debounce)
 */
const CharacterCounter: React.FC< CharacterCounterProps > = memo(
	( { value, maxLength, optimalMin, optimalMax, label } ) => {
		// Requirement 2.4: Display current character count
		const count = value ? value.length : 0;

		// Requirement 2.5: Calculate status
		const status = getStatus( count, optimalMin, optimalMax, maxLength );

		// Get accessible status message
		const statusMessage = getStatusMessage( status, count, maxLength );

		return (
			<div
				className={ `meowseo-character-counter meowseo-character-counter--${ status }` }
				role="status"
				aria-live="polite"
				aria-label={ `${ label }: ${ statusMessage }` }
			>
				<span className="meowseo-character-counter__label">
					{ label }
				</span>
				<span className="meowseo-character-counter__count">
					<span className="meowseo-character-counter__current">
						{ count }
					</span>
					<span className="meowseo-character-counter__separator">
						/
					</span>
					<span className="meowseo-character-counter__max">
						{ maxLength }
					</span>
				</span>
			</div>
		);
	}
);

CharacterCounter.displayName = 'CharacterCounter';

export default CharacterCounter;
