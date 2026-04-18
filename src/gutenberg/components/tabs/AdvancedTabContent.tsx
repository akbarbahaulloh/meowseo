/**
 * AdvancedTabContent Component
 *
 * Main content for the Advanced tab, including robots toggles,
 * canonical URL input, Google Search Console integration, and
 * detailed readability analysis panel.
 *
 * Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8, 31.1, 31.2, 31.3, 31.4, 31.5, 31.6
 */

import RobotsToggles from './RobotsToggles';
import CanonicalURLInput from './CanonicalURLInput';
import GSCIntegration from './GSCIntegration';
import { ReadabilityScorePanel } from '../ReadabilityScorePanel';

/**
 * AdvancedTabContent Component
 *
 * Requirements:
 * - 14.1, 14.2, 14.3: Render RobotsToggles for noindex/nofollow
 * - 14.4, 14.5, 14.6: Render CanonicalURLInput for canonical URL
 * - 14.7, 14.8: Render GSCIntegration for Google Search Console
 * - 31.1-31.6: Render ReadabilityScorePanel for detailed readability analysis
 */
const AdvancedTabContent: React.FC = () => {
	return (
		<div className="meowseo-advanced-tab">
			<RobotsToggles />
			<CanonicalURLInput />
			<GSCIntegration />
			<ReadabilityScorePanel />
		</div>
	);
};

export default AdvancedTabContent;
