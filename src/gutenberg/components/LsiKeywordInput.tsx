import { memo, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const LsiKeywordInput: React.FC = memo(() => {
	// Get current post type and ID
	const { postType, postId } = useSelect((select: any) => {
		const editorSelect = select('core/editor');
		return {
			postType: editorSelect?.getCurrentPostType() || 'post',
			postId: editorSelect?.getCurrentPostId() || 0,
		};
	}, []);

	// Get and set meta
	const [meta, setMeta] = useEntityProp('postType', postType, 'meta', postId);
	const lsiKeywords = meta?._meowseo_lsi_keywords || '';

	const handleChange = useCallback(
		(value: string) => {
			setMeta({
				...meta,
				_meowseo_lsi_keywords: value,
			});
		},
		[meta, setMeta]
	);

	return (
		<div className="meowseo-lsi-keyword-input" style={{ marginTop: '15px' }}>
			<TextControl
				label={__('LSI Keywords (comma separated)', 'meowseo')}
				value={lsiKeywords}
				onChange={handleChange}
				help={__('Enter synonyms or related keywords to improve semantic analysis.', 'meowseo')}
			/>
		</div>
	);
});

LsiKeywordInput.displayName = 'LsiKeywordInput';
