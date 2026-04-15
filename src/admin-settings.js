/**
 * MeowSEO Admin Settings Entry Point
 *
 * Renders the settings page interface.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

import { render } from '@wordpress/element';
import SettingsApp from './settings/SettingsApp';

// Import styles
import './editor.css';

// Render settings app
const settingsRoot = document.getElementById( 'meowseo-settings-root' );

if ( settingsRoot ) {
	try {
		render( <SettingsApp />, settingsRoot );
	} catch ( error ) {
		console.error( 'MeowSEO: Error rendering settings app', error );
		settingsRoot.innerHTML = '<div class="notice notice-error"><p>MeowSEO: Failed to load settings interface. Please check the browser console for details.</p></div>';
	}
}
