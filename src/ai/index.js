/**
 * MeowSEO AI Generator Module - Gutenberg Sidebar Plugin
 *
 * This file registers the AI Generator sidebar panel in the WordPress block editor.
 * It integrates with the Gutenberg editor to provide AI-powered SEO content generation.
 *
 * Requirements: 7.1
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/edit-post';
import { AiGeneratorPanel } from './components/AiGeneratorPanel';
import './styles/ai-generator.css';

/**
 * Register the AI Generator sidebar plugin
 *
 * This creates a new sidebar panel in the Gutenberg editor that allows users to:
 * - Generate SEO metadata (title, description, keywords, etc.)
 * - Generate featured images
 * - Preview generated content before applying
 * - Apply generated content to post fields
 *
 * Requirements: 7.1
 */
registerPlugin( 'meowseo-ai-generator', {
	render: () => {
		return (
			<PluginSidebar
				name="meowseo-ai-generator"
				title="AI Generator"
				icon="sparkles"
			>
				<AiGeneratorPanel />
			</PluginSidebar>
		);
	},
} );
