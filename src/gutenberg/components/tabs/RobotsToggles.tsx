/**
 * RobotsToggles Component
 *
 * Provides toggle controls for robots meta directives (noindex, nofollow).
 * Uses useEntityPropBinding for automatic persistence to postmeta.
 *
 * Requirements: 14.1, 14.2, 14.3, 15.9, 15.10
 */

import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';

/**
 * RobotsToggles Component
 *
 * Requirements:
 * - 14.1: Display toggles for noindex and nofollow robots directives
 * - 14.2: Persist noindex to _meowseo_robots_noindex postmeta
 * - 14.3: Persist nofollow to _meowseo_robots_nofollow postmeta
 * - 15.9: Use Entity_Prop for robots directives persistence
 * - 15.10: Trigger WordPress auto-save on changes
 */
const RobotsToggles: React.FC = () => {
	// Bind to postmeta fields
	// Requirements: 14.2, 14.3, 15.9, 15.10
	const [ noindex, setNoindex ] = useEntityPropBinding(
		'_meowseo_robots_noindex'
	);
	const [ nofollow, setNofollow ] = useEntityPropBinding(
		'_meowseo_robots_nofollow'
	);

	// Convert string values to boolean for ToggleControl
	const noindexChecked = noindex === '1' || noindex === 'true';
	const nofollowChecked = nofollow === '1' || nofollow === 'true';

	// Handle toggle changes
	const handleNoindexChange = ( checked: boolean ) => {
		setNoindex( checked ? '1' : '' );
	};

	const handleNofollowChange = ( checked: boolean ) => {
		setNofollow( checked ? '1' : '' );
	};

	return (
		<div className="meowseo-robots-toggles">
			<h3>{ __( 'Robots Meta Directives', 'meowseo' ) }</h3>

			<ToggleControl
				label={ __( 'No Index', 'meowseo' ) }
				help={ __(
					'Prevent search engines from indexing this page',
					'meowseo'
				) }
				checked={ noindexChecked }
				onChange={ handleNoindexChange }
			/>

			<ToggleControl
				label={ __( 'No Follow', 'meowseo' ) }
				help={ __(
					'Prevent search engines from following links on this page',
					'meowseo'
				) }
				checked={ nofollowChecked }
				onChange={ handleNofollowChange }
			/>
		</div>
	);
};

export default RobotsToggles;
