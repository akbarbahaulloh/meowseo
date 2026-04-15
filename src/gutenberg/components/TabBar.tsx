/**
 * TabBar Component
 * 
 * Displays four tabs (General, Social, Schema, Advanced) and handles tab switching.
 * Dispatches setActiveTab action on tab click and highlights the active tab visually.
 * 
 * Requirements: 8.1, 8.2, 8.5, 8.7
 */

import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { STORE_NAME, TabType } from '../store';
import './TabBar.css';

export const TabBar: React.FC = () => {
  const { activeTab } = useSelect((select) => {
    const store = select(STORE_NAME) as any;
    return {
      activeTab: store.getActiveTab(),
    };
  }, []);

  const { setActiveTab } = useDispatch(STORE_NAME) as any;

  const tabs: Array<{ id: TabType; label: string }> = [
    { id: 'general', label: __('General', 'meowseo') },
    { id: 'social', label: __('Social', 'meowseo') },
    { id: 'schema', label: __('Schema', 'meowseo') },
    { id: 'advanced', label: __('Advanced', 'meowseo') },
  ];

  const handleTabClick = (tabId: TabType) => {
    setActiveTab(tabId);
  };

  return (
    <div className="meowseo-tab-bar" role="tablist">
      {tabs.map((tab) => (
        <button
          key={tab.id}
          role="tab"
          aria-selected={activeTab === tab.id}
          aria-controls={`meowseo-tab-panel-${tab.id}`}
          id={`meowseo-tab-${tab.id}`}
          className={`meowseo-tab ${activeTab === tab.id ? 'is-active' : ''}`}
          onClick={() => handleTabClick(tab.id)}
          data-testid={`tab-${tab.id}`}
        >
          {tab.label}
        </button>
      ))}
    </div>
  );
};
