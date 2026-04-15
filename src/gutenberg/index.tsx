/**
 * MeowSEO Gutenberg Editor Integration
 * 
 * Entry point for the Gutenberg sidebar plugin.
 * This file registers the plugin and initializes the Redux store.
 * 
 * Requirements: 1.1, 1.2
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from './utils/plugin-sidebar-compat';
import { Sidebar } from './components';
import './store'; // Register the meowseo/data Redux store

/**
 * Register the MeowSEO sidebar plugin
 * 
 * Uses the compatibility shim to import PluginSidebar from the correct package
 * based on WordPress version (6.6+ uses @wordpress/editor, < 6.6 uses @wordpress/edit-post)
 */
registerPlugin('meowseo-sidebar', {
	render: () => {
		return (
			<PluginSidebar
				name="meowseo-sidebar"
				title="MeowSEO"
				icon="chart-line"
			>
				<Sidebar />
			</PluginSidebar>
		);
	},
});
