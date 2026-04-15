/**
 * TabContent Test Helper
 * 
 * Non-lazy version for testing
 */

import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '../store';
import GeneralTabContent from './tabs/GeneralTabContent';
import SocialTabContent from './tabs/SocialTabContent';
import SchemaTabContent from './tabs/SchemaTabContent';
import AdvancedTabContent from './tabs/AdvancedTabContent';
import './TabContent.css';

export const TabContentTestHelper: React.FC = () => {
  const { activeTab } = useSelect((select) => {
    const store = select(STORE_NAME) as any;
    return {
      activeTab: store.getActiveTab(),
    };
  }, []);

  return (
    <div className="meowseo-tab-content">
      {activeTab === 'general' && (
        <div
          role="tabpanel"
          id="meowseo-tab-panel-general"
          aria-labelledby="meowseo-tab-general"
          data-testid="tab-panel-general"
        >
          <GeneralTabContent />
        </div>
      )}
      {activeTab === 'social' && (
        <div
          role="tabpanel"
          id="meowseo-tab-panel-social"
          aria-labelledby="meowseo-tab-social"
          data-testid="tab-panel-social"
        >
          <SocialTabContent />
        </div>
      )}
      {activeTab === 'schema' && (
        <div
          role="tabpanel"
          id="meowseo-tab-panel-schema"
          aria-labelledby="meowseo-tab-schema"
          data-testid="tab-panel-schema"
        >
          <SchemaTabContent />
        </div>
      )}
      {activeTab === 'advanced' && (
        <div
          role="tabpanel"
          id="meowseo-tab-panel-advanced"
          aria-labelledby="meowseo-tab-advanced"
          data-testid="tab-panel-advanced"
        >
          <AdvancedTabContent />
        </div>
      )}
    </div>
  );
};
