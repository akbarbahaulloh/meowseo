/**
 * Sidebar Component
 * 
 * Main sidebar component that brings together all previously implemented components.
 * This is the ONLY component that calls useContentSync hook to read from core/editor.
 * All other components read from the meowseo/data Redux store.
 * 
 * Requirements: 1.6, 1.7, 2.6, 2.7
 */

import { useSelect } from '@wordpress/data';
import { useContentSync } from '../hooks/useContentSync';
import { ContentScoreWidget } from './ContentScoreWidget';
import { TabBar } from './TabBar';
import { TabContent } from './TabContent';
import { STORE_NAME } from '../store';
import './Sidebar.css';

export const Sidebar: React.FC = () => {
  // This is the ONLY place we call useContentSync
  // useContentSync is the ONLY hook allowed to read from core/editor
  useContentSync();

  // Read activeTab from meowseo/data store (NOT from core/editor)
  const { activeTab } = useSelect((select) => {
    const store = select(STORE_NAME) as any;
    return {
      activeTab: store.getActiveTab(),
    };
  }, []);

  return (
    <div className="meowseo-sidebar" data-testid="meowseo-sidebar">
      {/* ContentScoreWidget is always visible at the top */}
      <ContentScoreWidget />
      
      {/* Tab navigation */}
      <TabBar />
      
      {/* Active tab content */}
      <TabContent />
    </div>
  );
};
