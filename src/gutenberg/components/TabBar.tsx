/**
 * TabBar Component
 *
 * Displays four tabs (General, Social, Schema, Advanced) and handles tab switching.
 * Dispatches setActiveTab action on tab click and highlights the active tab visually.
 *
 * Optimized with React.memo and useCallback for performance.
 * Requirements: 8.1, 8.2, 8.5, 8.7, 16.7, 16.8
 */

import { memo, useCallback, useMemo } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { STORE_NAME, TabType } from '../store';
import './TabBar.css';

/**
 * TabBar Component
 *
 * Requirement 16.7: Use React.memo for pure components
 */
export const TabBar: React.FC = memo( () => {
	const { activeTab } = useSelect( ( select ) => {
		try {
			const store = select( STORE_NAME ) as any;
			if ( ! store ) {
				console.warn(
					'MeowSEO: meowseo/data store not available in TabBar'
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

	const { setActiveTab } = useDispatch( STORE_NAME ) as any;

	// Memoize tabs array to prevent recreation on every render
	const tabs: Array< { id: TabType; label: string } > = useMemo(
		() => [
			{ id: 'general', label: __( 'General', 'meowseo' ) },
			{ id: 'social', label: __( 'Social', 'meowseo' ) },
			{ id: 'schema', label: __( 'Schema', 'meowseo' ) },
			{ id: 'advanced', label: __( 'Advanced', 'meowseo' ) },
		],
		[]
	);

	// Requirement 16.8: Use useCallback for event handlers
	const handleTabClick = useCallback(
		( tabId: TabType ) => {
			setActiveTab( tabId );
		},
		[ setActiveTab ]
	);

	return (
		<div className="meowseo-tab-bar" role="tablist">
			{ tabs.map( ( tab ) => (
				<button
					key={ tab.id }
					role="tab"
					aria-selected={ activeTab === tab.id }
					aria-controls={ `meowseo-tab-panel-${ tab.id }` }
					id={ `meowseo-tab-${ tab.id }` }
					className={ `meowseo-tab ${
						activeTab === tab.id ? 'is-active' : ''
					}` }
					onClick={ () => handleTabClick( tab.id ) }
					data-testid={ `tab-${ tab.id }` }
				>
					{ tab.label }
				</button>
			) ) }
		</div>
	);
} );
