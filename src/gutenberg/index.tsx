/**
 * MeowSEO Gutenberg Editor Integration
 *
 * Entry point for the Gutenberg sidebar plugin.
 * This file registers the plugin and initializes the Redux store.
 *
 * Requirements: 1.1, 1.2, 18.1, 18.2
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from './utils/plugin-sidebar-compat';
import { Sidebar } from './components';
import { ErrorBoundary } from './components/ErrorBoundary';
import { configureApiFetch } from './utils/api-config';
import './store'; // Register the meowseo/data Redux store
import './components/ErrorBoundary.css';

// Configure apiFetch with nonce for secure REST API calls
// Requirements: 18.1, 18.2
configureApiFetch();

/**
 * Register the MeowSEO sidebar plugin
 *
 * Uses the compatibility shim to import PluginSidebar from the correct package
 * based on WordPress version (6.6+ uses @wordpress/editor, < 6.6 uses @wordpress/edit-post)
 */
registerPlugin( 'meowseo-sidebar', {
	render: () => {
		return (
			<PluginSidebar
				name="meowseo-sidebar"
				title="MeowSEO"
				icon="chart-line"
			>
				<ErrorBoundary>
					<Sidebar />
				</ErrorBoundary>
			</PluginSidebar>
		);
	},
} );
