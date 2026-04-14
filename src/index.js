/**
 * MeowSEO Gutenberg Integration Entry Point
 *
 * Registers the Redux store and Gutenberg sidebar.
 * Initializes all JavaScript components.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

import { registerPlugin } from '@wordpress/plugins';
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { render } from '@wordpress/element';

// Import styles
import './editor.css';

// Import store (registers automatically on import)
import './store/index';

// Import sidebar component
import MeowSeoSidebar from './sidebar/MeowSeoSidebar';

// Import settings app
import SettingsApp from './settings/SettingsApp';

/**
 * MeowSEO Plugin Component
 *
 * Initializes meta from postmeta and renders sidebar.
 */
function MeowSeoPlugin() {
	const { initializeMeta } = useDispatch( 'meowseo/data' );

	// Get current post type
	const postType = useSelect(
		( select ) => select( 'core/editor' )?.getCurrentPostType(),
		[]
	);

	// Load meta from postmeta
	const [ metaTitle ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_title' );
	const [ metaDescription ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_description' );
	const [ metaRobots ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_robots' );
	const [ metaCanonical ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_canonical' );
	const [ focusKeyword ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_focus_keyword' );
	const [ schemaType ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_schema_type' );
	const [ socialTitle ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_social_title' );
	const [ socialDescription ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_social_description' );
	const [ socialImageId ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_social_image_id' );

	// Initialize store with postmeta values on mount
	useEffect( () => {
		if ( postType ) {
			initializeMeta( {
				title: metaTitle || '',
				description: metaDescription || '',
				robots: metaRobots || 'index,follow',
				canonical: metaCanonical || '',
				focusKeyword: focusKeyword || '',
				schemaType: schemaType || '',
				socialTitle: socialTitle || '',
				socialDescription: socialDescription || '',
				socialImageId: socialImageId || 0,
			} );
		}
	}, [
		postType,
		metaTitle,
		metaDescription,
		metaRobots,
		metaCanonical,
		focusKeyword,
		schemaType,
		socialTitle,
		socialDescription,
		socialImageId,
		initializeMeta,
	] );

	return <MeowSeoSidebar />;
}

// Check if we're on the settings page or in the editor
const settingsRoot = document.getElementById( 'meowseo-settings-root' );

if ( settingsRoot ) {
	// Render settings app on admin page
	render( <SettingsApp />, settingsRoot );
} else {
	// Register the Gutenberg plugin
	registerPlugin( 'meowseo', {
		render: MeowSeoPlugin,
		icon: 'search',
	} );
}
