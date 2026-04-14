/**
 * Meta Tab Component
 *
 * SEO title, description, robots, canonical fields.
 * Reads from meowseo/data store and persists via useEntityProp.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

import { TextControl, TextareaControl, SelectControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

/**
 * Meta Tab Component
 */
export default function MetaTab() {
	const { updateMeta } = useDispatch( 'meowseo/data' );

	// Get current post type and ID
	const { postType, postId } = useSelect( ( select ) => {
		return {
			postType: select( 'core/editor' ).getCurrentPostType(),
			postId: select( 'core/editor' ).getCurrentPostId(),
		};
	}, [] );

	// Use useEntityProp for postmeta persistence
	const [ metaTitle, setMetaTitle ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_title' );
	const [ metaDescription, setMetaDescription ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_description' );
	const [ metaRobots, setMetaRobots ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_robots' );
	const [ metaCanonical, setMetaCanonical ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_canonical' );
	const [ focusKeyword, setFocusKeyword ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_focus_keyword' );

	// Robots options
	const robotsOptions = [
		{ label: __( 'Index, Follow', 'meowseo' ), value: 'index,follow' },
		{ label: __( 'No Index, Follow', 'meowseo' ), value: 'noindex,follow' },
		{ label: __( 'Index, No Follow', 'meowseo' ), value: 'index,nofollow' },
		{ label: __( 'No Index, No Follow', 'meowseo' ), value: 'noindex,nofollow' },
	];

	/**
	 * Handle field change
	 *
	 * Updates both store and postmeta.
	 *
	 * @param {string}   field    Field name
	 * @param {*}        value    Field value
	 * @param {Function} setter   useEntityProp setter
	 */
	const handleChange = ( field, value, setter ) => {
		// Update store
		updateMeta( field, value );
		// Update postmeta
		setter( value );
	};

	return (
		<div className="meowseo-meta-tab">
			<TextControl
				label={ __( 'Focus Keyword', 'meowseo' ) }
				value={ focusKeyword || '' }
				onChange={ ( value ) => handleChange( 'focusKeyword', value, setFocusKeyword ) }
				help={ __( 'Primary keyword for SEO analysis', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'SEO Title', 'meowseo' ) }
				value={ metaTitle || '' }
				onChange={ ( value ) => handleChange( 'title', value, setMetaTitle ) }
				help={ __( 'Leave empty to use post title + site title', 'meowseo' ) }
			/>

			<TextareaControl
				label={ __( 'Meta Description', 'meowseo' ) }
				value={ metaDescription || '' }
				onChange={ ( value ) => handleChange( 'description', value, setMetaDescription ) }
				help={ __( 'Recommended: 50-160 characters', 'meowseo' ) }
				rows={ 3 }
			/>

			<SelectControl
				label={ __( 'Robots', 'meowseo' ) }
				value={ metaRobots || 'index,follow' }
				options={ robotsOptions }
				onChange={ ( value ) => handleChange( 'robots', value, setMetaRobots ) }
			/>

			<TextControl
				label={ __( 'Canonical URL', 'meowseo' ) }
				value={ metaCanonical || '' }
				onChange={ ( value ) => handleChange( 'canonical', value, setMetaCanonical ) }
				help={ __( 'Leave empty to use post permalink', 'meowseo' ) }
				type="url"
			/>
		</div>
	);
}
