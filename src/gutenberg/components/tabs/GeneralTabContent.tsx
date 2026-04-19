/**
 * GeneralTabContent Component
 *
 * Main content for the General tab, including focus keyword input,
 * SERP preview, direct answer field, internal link suggestions,
 * and readability analysis panel.
 *
 * Requirements: 1.7, 9.6, 6.5
 */

import SerpPreview from './SerpPreview';
import FocusKeywordInput from './FocusKeywordInput';
import SecondaryKeywordsInput from './SecondaryKeywordsInput';
import DirectAnswerField from './DirectAnswerField';
import InternalLinkSuggestions from './InternalLinkSuggestions';
import { ReadabilityScorePanel } from '../ReadabilityScorePanel';
import { KeywordAnalysisPanel } from '../KeywordAnalysisPanel';
import './SerpPreview.css';
import './InternalLinkSuggestions.css';
import './SecondaryKeywordsInput.css';

/**
 * GeneralTabContent Component
 *
 * Requirements:
 * - 1.7: Render General tab with all components
 * - 9.6: Wire General tab components together
 * - 6.5: Add ReadabilityScorePanel to sidebar (collapsible section)
 * - 2.9: Display per-keyword analysis results
 */
const GeneralTabContent: React.FC = () => {
	return (
		<div className="meowseo-general-tab">
			<SerpPreview />
			<FocusKeywordInput />
			<SecondaryKeywordsInput />
			<KeywordAnalysisPanel />
			<DirectAnswerField />
			<InternalLinkSuggestions />
			<ReadabilityScorePanel />
		</div>
	);
};

export default GeneralTabContent;
