/**
 * PluginSidebar Compatibility Wrapper
 *
 * Provides a unified PluginSidebar component that dynamically imports
 * from the correct package based on WordPress version.
 *
 * - WordPress 6.6+: Import from @wordpress/editor
 * - WordPress < 6.6: Import from @wordpress/edit-post
 *
 * Requirements: 1.4, 1.5
 */

import { isWP66Plus } from './version-detection';

// Type definitions for PluginSidebar props
export interface PluginSidebarProps {
	name: string;
	title: string;
	icon?: string | JSX.Element;
	children?: React.ReactNode;
	className?: string;
	isPinnable?: boolean;
	isActive?: boolean;
}

// Import PluginSidebar from the appropriate package based on WordPress version
let PluginSidebarComponent: React.ComponentType< PluginSidebarProps >;

if ( isWP66Plus ) {
	// WordPress 6.6+: Import from @wordpress/editor
	try {
		const editorModule = require( '@wordpress/editor' );
		PluginSidebarComponent = editorModule.PluginSidebar;
	} catch ( error ) {
		console.error(
			'MeowSEO: Failed to import PluginSidebar from @wordpress/editor:',
			error
		);
		// Fallback to @wordpress/edit-post if import fails
		const editPostModule = require( '@wordpress/edit-post' );
		PluginSidebarComponent = editPostModule.PluginSidebar;
	}
} else {
	// WordPress < 6.6: Import from @wordpress/edit-post
	try {
		const editPostModule = require( '@wordpress/edit-post' );
		PluginSidebarComponent = editPostModule.PluginSidebar;
	} catch ( error ) {
		console.error(
			'MeowSEO: Failed to import PluginSidebar from @wordpress/edit-post:',
			error
		);
		// Create a fallback component that renders nothing
		PluginSidebarComponent = () => null;
	}
}

/**
 * Unified PluginSidebar component
 *
 * This component automatically uses the correct PluginSidebar implementation
 * based on the detected WordPress version.
 *
 * @param {PluginSidebarProps} props - PluginSidebar props
 * @return {JSX.Element} PluginSidebar component
 */
export const PluginSidebar: React.ComponentType< PluginSidebarProps > =
	PluginSidebarComponent;

/**
 * Export the detected WordPress version flag for debugging
 */
export { isWP66Plus };
