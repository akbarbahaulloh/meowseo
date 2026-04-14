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
	try {
		const dispatch = useDispatch( 'meowseo/data' );
		
		// Check if store is available
		if ( ! dispatch ) {
			console.error( 'MeowSEO: Store not available' );
			return null;
		}

		const { initializeMeta } = dispatch;

		// Get current post type
		const postType = useSelect(
			( select ) => select( 'core/editor' )?.getCurrentPostType(),
			[]
		);

		// Load meta from postmeta with error handling
		let metaTitle, metaDescription, metaRobots, metaCanonical, focusKeyword, schemaType, socialTitle, socialDescription, socialImageId;
		
		try {
			[ metaTitle ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_title' );
			[ metaDescription ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_description' );
			[ metaRobots ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_robots' );
			[ metaCanonical ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_canonical' );
			[ focusKeyword ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_focus_keyword' );
			[ schemaType ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_schema_type' );
			[ socialTitle ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_social_title' );
			[ socialDescription ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_social_description' );
			[ socialImageId ] = useEntityProp( 'postType', postType, 'meta', 'meowseo_social_image_id' );
		} catch ( error ) {
			console.error( 'MeowSEO: Error loading meta from postmeta', error );
			// Use default values
			metaTitle = '';
			metaDescription = '';
			metaRobots = 'index,follow';
			metaCanonical = '';
			focusKeyword = '';
			schemaType = '';
			socialTitle = '';
			socialDescription = '';
			socialImageId = 0;
		}

		// Initialize store with postmeta values on mount
		useEffect( () => {
			if ( postType && initializeMeta ) {
				try {
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
				} catch ( error ) {
					console.error( 'MeowSEO: Error initializing meta', error );
				}
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
	} catch ( error ) {
		console.error( 'MeowSEO: Error in plugin component', error );
		return null;
	}
}

// Check if we're on the settings page or in the editor
const settingsRoot = document.getElementById( 'meowseo-settings-root' );

if ( settingsRoot ) {
	// Render settings app on admin page with error handling
	try {
		render( <SettingsApp />, settingsRoot );
	} catch ( error ) {
		console.error( 'MeowSEO: Error rendering settings app', error );
		settingsRoot.innerHTML = '<div class="notice notice-error"><p>MeowSEO: Failed to load settings interface. Please check the browser console for details.</p></div>';
	}
} else {
	// Register the Gutenberg plugin with error handling
	try {
		registerPlugin( 'meowseo', {
			render: MeowSeoPlugin,
			icon: 'search',
		} );
	} catch ( error ) {
		console.error( 'MeowSEO: Error registering plugin', error );
	}
}
