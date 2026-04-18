/**
 * DirectAnswerField Component
 *
 * Provides a textarea for the direct answer field that persists to postmeta.
 * The direct answer is used for featured snippet optimization.
 *
 * Requirements: 15.6
 */

import { TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';

/**
 * DirectAnswerField Component
 *
 * Requirements:
 * - 15.6: Use useEntityPropBinding for _meowseo_direct_answer
 * - Display TextareaControl with label
 * - Persist value to postmeta automatically
 */
const DirectAnswerField: React.FC = () => {
	// Requirement 15.6: Use useEntityPropBinding for _meowseo_direct_answer
	const [ directAnswer, setDirectAnswer ] = useEntityPropBinding(
		'_meowseo_direct_answer'
	);

	return (
		<TextareaControl
			label={ __( 'Direct Answer', 'meowseo' ) }
			value={ directAnswer }
			onChange={ setDirectAnswer }
			help={ __(
				'Provide a concise answer to the main question your content addresses. This helps optimize for featured snippets in search results.',
				'meowseo'
			) }
			placeholder={ __(
				'e.g., WordPress SEO is the practice of optimizing your WordPress website to rank higher in search engine results.',
				'meowseo'
			) }
			rows={ 4 }
		/>
	);
};

export default DirectAnswerField;
