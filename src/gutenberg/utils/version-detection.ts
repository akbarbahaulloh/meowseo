/**
 * WordPress Version Detection Utility
 * 
 * Detects WordPress version from window.wp global to determine
 * which package to use for PluginSidebar import.
 * 
 * Requirements: 1.3, 1.4, 1.5
 */

declare global {
	interface Window {
		wp?: {
			data?: {
				select?: (storeName: string) => any;
			};
		};
	}
}

/**
 * Detect if WordPress version is 6.6 or higher
 * 
 * @returns {boolean} True if WordPress 6.6+, false otherwise
 */
export function detectWordPressVersion(): boolean {
	// Check if wp global exists
	if (typeof window === 'undefined' || !window.wp) {
		console.warn('MeowSEO: window.wp not available, assuming WP < 6.6');
		return false;
	}

	// Try to get version from core/editor store
	try {
		const coreEditor = window.wp.data?.select?.('core/editor');
		if (coreEditor && typeof coreEditor.getEditorSettings === 'function') {
			const settings = coreEditor.getEditorSettings();
			
			// Check if __experimentalPluginSidebar exists (WP 6.6+ indicator)
			// In WP 6.6+, PluginSidebar moved to @wordpress/editor
			if (settings && '__experimentalPluginSidebar' in settings) {
				return true;
			}
		}
	} catch (error) {
		console.warn('MeowSEO: Error detecting WordPress version:', error);
	}

	// Fallback: Check if @wordpress/editor exports PluginSidebar
	// This is a more reliable check for WP 6.6+
	try {
		// @ts-ignore - dynamic import check
		const editorPackage = window.wp.editor;
		if (editorPackage && 'PluginSidebar' in editorPackage) {
			return true;
		}
	} catch (error) {
		// Ignore error, fall through to default
	}

	// Default to false (WP < 6.6)
	return false;
}

/**
 * Boolean flag indicating if WordPress version is 6.6 or higher
 * 
 * This is computed once at module load time.
 */
export const isWP66Plus: boolean = detectWordPressVersion();
