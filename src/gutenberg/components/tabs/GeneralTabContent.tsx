/**
 * GeneralTabContent Component
 *
 * Main content for the General tab, including focus keyword input,
 * SERP preview, direct answer field, internal link suggestions,
 * and readability analysis panel.
 *
 * Requirements: 1.7, 9.6, 6.5, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6
 */

import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import SERPPreview from '../SERPPreview';
import CharacterCounter from '../CharacterCounter';
import FocusKeywordInput from './FocusKeywordInput';
import SecondaryKeywordsInput from './SecondaryKeywordsInput';
import DirectAnswerField from './DirectAnswerField';
import InternalLinkSuggestions from './InternalLinkSuggestions';
import { ReadabilityScorePanel } from '../ReadabilityScorePanel';
import { KeywordAnalysisPanel } from '../KeywordAnalysisPanel';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';
import '../SERPPreview.css';
import '../CharacterCounter.css';
import './GeneralTabContent.css';
import './InternalLinkSuggestions.css';
import './SecondaryKeywordsInput.css';

/**
 * GeneralTabContent Component
 *
 * Requirements:
 * - 1.7: Render General tab with all components
 * - 9.6: Wire General tab components together
 * - 6.5: Add ReadabilityScorePanel to sidebar (collapsible section)
 * - 2.1, 2.2, 2.3: SERP Preview with real-time updates
 * - 2.4, 2.5: Character Counter with status indicators
 * - 2.6: Integration of SERP Preview and Character Counter
 */
const GeneralTabContent: React.FC = () => {
	// Requirement 2.6: Connect to SEO title and meta description state
	const [ seoTitle, setSeoTitle ] = useEntityPropBinding( '_meowseo_title' );
	const [ seoDescription, setSeoDescription ] = useEntityPropBinding(
		'_meowseo_description'
	);

	// Requirement 2.12, 2.13, 2.14: Load mode preference from localStorage
	const [ previewMode, setPreviewMode ] = useState< 'desktop' | 'mobile' >(
		() => {
			try {
				const saved = localStorage.getItem(
					'meowseo_serp_preview_mode'
				);
				return saved === 'mobile' ? 'mobile' : 'desktop';
			} catch {
				return 'desktop';
			}
		}
	);

	// Get permalink and post title from WordPress
	const { permalink, postTitle } = useSelect( ( select: any ) => {
		try {
			const editorSelect = select( 'core/editor' );
			if ( ! editorSelect ) {
				return { permalink: '', postTitle: '' };
			}
			return {
				permalink: editorSelect.getPermalink() || '',
				postTitle:
					editorSelect.getEditedPostAttribute( 'title' ) || '',
			};
		} catch ( error ) {
			console.error(
				'MeowSEO: Error reading permalink/title:',
				error
			);
			return { permalink: '', postTitle: '' };
		}
	}, [] );

	// Use post title as fallback for SEO title
	const displayTitle = seoTitle || postTitle;

	return (
		<div className="meowseo-general-tab">
			{ /* Requirement 2.6: Add SERP Preview above title/description fields */ }
			<SERPPreview
				title={ displayTitle }
				description={ seoDescription }
				url={ permalink }
				mode={ previewMode }
				onModeChange={ setPreviewMode }
			/>

			{ /* SEO Title Field with Character Counter */ }
			<div className="meowseo-field-group">
				<TextControl
					label={ __( 'SEO Title', 'meowseo' ) }
					value={ seoTitle }
					onChange={ setSeoTitle }
					help={ __(
						'The title that appears in search results. Leave empty to use the post title.',
						'meowseo'
					) }
					placeholder={ postTitle }
				/>
				{ /* Requirement 2.6: Add Character Counter below title field */ }
				<CharacterCounter
					value={ displayTitle }
					maxLength={ 60 }
					optimalMin={ 50 }
					optimalMax={ 60 }
					label={ __( 'Title Length', 'meowseo' ) }
				/>
			</div>

			{ /* Meta Description Field with Character Counter */ }
			<div className="meowseo-field-group">
				<TextControl
					label={ __( 'Meta Description', 'meowseo' ) }
					value={ seoDescription }
					onChange={ setSeoDescription }
					help={ __(
						'A brief description that appears in search results.',
						'meowseo'
					) }
					placeholder={ __(
						'Enter a compelling description...',
						'meowseo'
					) }
				/>
				{ /* Requirement 2.6: Add Character Counter below description field */ }
				<CharacterCounter
					value={ seoDescription }
					maxLength={ 155 }
					optimalMin={ 120 }
					optimalMax={ 155 }
					label={ __( 'Description Length', 'meowseo' ) }
				/>
			</div>

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
