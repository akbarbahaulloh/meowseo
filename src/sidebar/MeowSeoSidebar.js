/**
 * MeowSEO Sidebar
 *
 * Main Gutenberg sidebar component with tabbed interface.
 * Registers PluginSidebar and renders tab navigation.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { useSelect, useDispatch } from '@wordpress/data';
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useContentSync } from '../store/content-sync-hook';

// Import tab components
import MetaTab from './tabs/MetaTab';
import AnalysisTab from './tabs/AnalysisTab';
import SocialTab from './tabs/SocialTab';
import SchemaTab from './tabs/SchemaTab';
import LinksTab from './tabs/LinksTab';
import GscTab from './tabs/GscTab';

/**
 * MeowSEO Sidebar Component
 */
export default function MeowSeoSidebar() {
	// Initialize content sync hook
	useContentSync();

	const { setActiveTab } = useDispatch( 'meowseo/data' );
	const activeTab = useSelect(
		( select ) => select( 'meowseo/data' ).getActiveTab(),
		[]
	);

	// Define tabs
	const tabs = [
		{
			name: 'meta',
			title: __( 'SEO Meta', 'meowseo' ),
			className: 'meowseo-tab-meta',
		},
		{
			name: 'analysis',
			title: __( 'Analysis', 'meowseo' ),
			className: 'meowseo-tab-analysis',
		},
		{
			name: 'social',
			title: __( 'Social', 'meowseo' ),
			className: 'meowseo-tab-social',
		},
		{
			name: 'schema',
			title: __( 'Schema', 'meowseo' ),
			className: 'meowseo-tab-schema',
		},
		{
			name: 'links',
			title: __( 'Links', 'meowseo' ),
			className: 'meowseo-tab-links',
		},
		{
			name: 'gsc',
			title: __( 'GSC', 'meowseo' ),
			className: 'meowseo-tab-gsc',
		},
	];

	/**
	 * Render tab content
	 *
	 * @param {Object} tab Current tab
	 * @return {JSX.Element} Tab content
	 */
	const renderTabContent = ( tab ) => {
		switch ( tab.name ) {
			case 'meta':
				return <MetaTab />;
			case 'analysis':
				return <AnalysisTab />;
			case 'social':
				return <SocialTab />;
			case 'schema':
				return <SchemaTab />;
			case 'links':
				return <LinksTab />;
			case 'gsc':
				return <GscTab />;
			default:
				return null;
		}
	};

	return (
		<>
			<PluginSidebarMoreMenuItem target="meowseo-sidebar">
				{ __( 'MeowSEO', 'meowseo' ) }
			</PluginSidebarMoreMenuItem>

			<PluginSidebar
				name="meowseo-sidebar"
				title={ __( 'MeowSEO', 'meowseo' ) }
				icon="search"
			>
				<div className="meowseo-sidebar">
					<TabPanel
						className="meowseo-tab-panel"
						activeClass="is-active"
						tabs={ tabs }
						initialTabName={ activeTab }
						onSelect={ ( tabName ) => setActiveTab( tabName ) }
					>
						{ ( tab ) => (
							<div className="meowseo-tab-content">
								{ renderTabContent( tab ) }
							</div>
						) }
					</TabPanel>
				</div>
			</PluginSidebar>
		</>
	);
}
