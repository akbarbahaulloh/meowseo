/**
 * Property-Based Tests for TabContent Component
 * 
 * Property 7: Tab state isolation
 * **Validates: Requirements 8.3, 8.4**
 */

import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import * as fc from 'fast-check';
import { TabContentTestHelper } from '../TabContent.test-helper';
import { TabType } from '../../store/types';

// Mock WordPress dependencies
jest.mock('@wordpress/data', () => ({
  useSelect: jest.fn(),
  createReduxStore: jest.fn(),
  register: jest.fn(),
}));

// Mock tab content components
jest.mock('../tabs/GeneralTabContent', () => ({
  __esModule: true,
  default: () => <div data-testid="general-content">General Content</div>,
}));

jest.mock('../tabs/SocialTabContent', () => ({
  __esModule: true,
  default: () => <div data-testid="social-content">Social Content</div>,
}));

jest.mock('../tabs/SchemaTabContent', () => ({
  __esModule: true,
  default: () => <div data-testid="schema-content">Schema Content</div>,
}));

jest.mock('../tabs/AdvancedTabContent', () => ({
  __esModule: true,
  default: () => <div data-testid="advanced-content">Advanced Content</div>,
}));

const { useSelect } = require('@wordpress/data');

describe('TabContent - Property 7: Tab state isolation', () => {
  /**
   * Property: For all tabs, if a tab is not active, then its content is not rendered
   * 
   * This property verifies that:
   * 1. Only the active tab's content is rendered in the DOM
   * 2. Inactive tabs are not rendered (tab state isolation)
   * 3. This holds true for any sequence of tab switches
   */
  it('should only render active tab content (property test)', () => {
    fc.assert(
      fc.property(
        // Generate a random sequence of tab switches
        fc.array(
          fc.constantFrom<TabType>('general', 'social', 'schema', 'advanced'),
          { minLength: 1, maxLength: 10 }
        ),
        (tabSequence) => {
          // Test each tab in the sequence
          for (const activeTab of tabSequence) {
            // Mock useSelect to return the current active tab
            useSelect.mockImplementation((selector: any) => {
              const mockSelect = (storeName: string) => ({
                getActiveTab: () => activeTab,
              });
              return selector(mockSelect);
            });

            // Render the component
            const { container } = render(<TabContentTestHelper />);

            // Define all tabs
            const allTabs: TabType[] = ['general', 'social', 'schema', 'advanced'];

            // Verify only active tab content is rendered
            for (const tab of allTabs) {
              const tabPanel = container.querySelector(`#meowseo-tab-panel-${tab}`);
              
              if (tab === activeTab) {
                // Active tab should be rendered
                expect(tabPanel).toBeInTheDocument();
              } else {
                // Inactive tabs should NOT be rendered
                expect(tabPanel).not.toBeInTheDocument();
              }
            }

            // Clean up for next iteration
            container.remove();
          }
        }
      ),
      {
        numRuns: 50, // Run 50 random test cases
      }
    );
  });

  /**
   * Property: Tab content rendering is deterministic
   * 
   * This property verifies that:
   * 1. Given the same activeTab, the same content is always rendered
   * 2. The rendering is consistent across multiple renders
   */
  it('should render tab content deterministically (property test)', () => {
    fc.assert(
      fc.property(
        fc.constantFrom<TabType>('general', 'social', 'schema', 'advanced'),
        (activeTab) => {
          // Mock useSelect to return the active tab
          useSelect.mockImplementation((selector: any) => {
            const mockSelect = (storeName: string) => ({
              getActiveTab: () => activeTab,
            });
            return selector(mockSelect);
          });

          // Render the component twice
          const { container: container1 } = render(<TabContentTestHelper />);
          const { container: container2 } = render(<TabContentTestHelper />);

          // Both renders should have the same active tab panel
          const panel1 = container1.querySelector(`#meowseo-tab-panel-${activeTab}`);
          const panel2 = container2.querySelector(`#meowseo-tab-panel-${activeTab}`);

          expect(panel1).toBeInTheDocument();
          expect(panel2).toBeInTheDocument();

          // Clean up
          container1.remove();
          container2.remove();
        }
      ),
      {
        numRuns: 20,
      }
    );
  });

  /**
   * Property: Exactly one tab is rendered at any time
   * 
   * This property verifies that:
   * 1. There is always exactly one tab panel in the DOM
   * 2. Never zero tabs, never multiple tabs
   */
  it('should render exactly one tab at any time (property test)', () => {
    fc.assert(
      fc.property(
        fc.constantFrom<TabType>('general', 'social', 'schema', 'advanced'),
        (activeTab) => {
          // Mock useSelect to return the active tab
          useSelect.mockImplementation((selector: any) => {
            const mockSelect = (storeName: string) => ({
              getActiveTab: () => activeTab,
            });
            return selector(mockSelect);
          });

          // Render the component
          const { container } = render(<TabContentTestHelper />);

          // Count how many tab panels are rendered
          const allTabs: TabType[] = ['general', 'social', 'schema', 'advanced'];
          let renderedCount = 0;

          for (const tab of allTabs) {
            const tabPanel = container.querySelector(`#meowseo-tab-panel-${tab}`);
            if (tabPanel) {
              renderedCount++;
            }
          }

          // Exactly one tab should be rendered
          expect(renderedCount).toBe(1);

          // Clean up
          container.remove();
        }
      ),
      {
        numRuns: 30,
      }
    );
  });
});
