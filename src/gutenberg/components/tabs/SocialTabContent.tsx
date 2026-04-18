/**
 * SocialTabContent Component
 *
 * Main content for the Social tab, including Facebook and Twitter sub-tabs.
 * Allows customization of social media metadata for optimal sharing appearance.
 *
 * Optimized with React.memo and useCallback for performance.
 * Requirements: 12.1, 12.9, 16.7, 16.8
 */

import { useState, memo, useCallback } from '@wordpress/element';
import { Button, ButtonGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FacebookSubTab from './FacebookSubTab';
import TwitterSubTab from './TwitterSubTab';
import './SocialTabContent.css';

type SocialSubTab = 'facebook' | 'twitter';

/**
 * SocialTabContent Component
 *
 * Requirements:
 * - 12.1: Display Facebook and Twitter sub-tabs
 * - 12.9: Implement sub-tab navigation
 * - 16.7: Use React.memo for pure components
 * - 16.8: Use useCallback for event handlers
 */
const SocialTabContent: React.FC = memo( () => {
	// Requirement 12.9: Implement sub-tab navigation
	const [ activeSubTab, setActiveSubTab ] =
		useState< SocialSubTab >( 'facebook' );

	// Requirement 16.8: Use useCallback for event handlers
	const handleSubTabChange = useCallback( ( subTab: SocialSubTab ) => {
		setActiveSubTab( subTab );
	}, [] );

	return (
		<div className="meowseo-social-tab">
			{ /* Requirement 12.1: Display Facebook and Twitter sub-tabs */ }
			<div className="meowseo-social-subtab-navigation">
				<ButtonGroup>
					<Button
						variant={
							activeSubTab === 'facebook'
								? 'primary'
								: 'secondary'
						}
						onClick={ () => handleSubTabChange( 'facebook' ) }
					>
						{ __( 'Facebook', 'meowseo' ) }
					</Button>
					<Button
						variant={
							activeSubTab === 'twitter' ? 'primary' : 'secondary'
						}
						onClick={ () => handleSubTabChange( 'twitter' ) }
					>
						{ __( 'Twitter', 'meowseo' ) }
					</Button>
				</ButtonGroup>
			</div>

			{ /* Requirement 12.9: Render active sub-tab content */ }
			<div className="meowseo-social-subtab-content">
				{ activeSubTab === 'facebook' && <FacebookSubTab /> }
				{ activeSubTab === 'twitter' && <TwitterSubTab /> }
			</div>
		</div>
	);
} );

export default SocialTabContent;
