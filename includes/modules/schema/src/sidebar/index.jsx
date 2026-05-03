/**
 * Schema Sidebar - Gutenberg Plugin
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import SchemaSidebar from './SchemaSidebar';

const SchemaPanel = () => {
	const postId = useSelect((select) => {
		return select('core/editor').getCurrentPostId();
	}, []);

	return (
		<PluginDocumentSettingPanel
			name="meowseo-schema"
			title={__('Schema Generator', 'meowseo')}
			icon="admin-generic"
		>
			<SchemaSidebar postId={postId} />
		</PluginDocumentSettingPanel>
	);
};

registerPlugin('meowseo-schema', {
	render: SchemaPanel,
});
