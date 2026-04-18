/**
 * TabContent Component
 *
 * Renders only the active tab content with lazy loading for code splitting.
 * Preserves state of all tabs by keeping them mounted but hidden.
 *
 * Optimized with React.memo for performance.
 * Requirements: 8.3, 8.4, 8.6, 16.3, 16.7
 */

import { lazy, Suspense, memo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { STORE_NAME } from '../store';
import './TabContent.css';

// Lazy load tab content components for code splitting
const GeneralTabContent = lazy( () => import( './tabs/GeneralTabContent' ) );
const SocialTabContent = lazy( () => import( './tabs/SocialTabContent' ) );
const SchemaTabContent = lazy( () => import( './tabs/SchemaTabContent' ) );
const AdvancedTabContent = lazy( () => import( './tabs/AdvancedTabContent' ) );

/**
 * TabContent Component
 *
 * Requirement 16.7: Use React.memo for pure components
 */
export const TabContent: React.FC = memo( () => {
	const { activeTab } = useSelect( ( select ) => {
		try {
			const store = select( STORE_NAME ) as any;
			if ( ! store ) {
				console.warn(
					'MeowSEO: meowseo/data store not available in TabContent'
				);
				return {
					activeTab: 'general' as const,
				};
			}
			return {
				activeTab: store.getActiveTab(),
			};
		} catch ( error ) {
			console.error(
				'MeowSEO: Error reading from meowseo/data store:',
				error
			);
			return {
				activeTab: 'general' as const,
			};
		}
	}, [] );

	return (
		<div className="meowseo-tab-content">
			<Suspense
				fallback={
					<div className="meowseo-tab-loading">
						<Spinner />
					</div>
				}
			>
				{ /* Only render active tab content */ }
				{ activeTab === 'general' && (
					<div
						role="tabpanel"
						id="meowseo-tab-panel-general"
						aria-labelledby="meowseo-tab-general"
						data-testid="tab-panel-general"
					>
						<GeneralTabContent />
					</div>
				) }
				{ activeTab === 'social' && (
					<div
						role="tabpanel"
						id="meowseo-tab-panel-social"
						aria-labelledby="meowseo-tab-social"
						data-testid="tab-panel-social"
					>
						<SocialTabContent />
					</div>
				) }
				{ activeTab === 'schema' && (
					<div
						role="tabpanel"
						id="meowseo-tab-panel-schema"
						aria-labelledby="meowseo-tab-schema"
						data-testid="tab-panel-schema"
					>
						<SchemaTabContent />
					</div>
				) }
				{ activeTab === 'advanced' && (
					<div
						role="tabpanel"
						id="meowseo-tab-panel-advanced"
						aria-labelledby="meowseo-tab-advanced"
						data-testid="tab-panel-advanced"
					>
						<AdvancedTabContent />
					</div>
				) }
			</Suspense>
		</div>
	);
} );
