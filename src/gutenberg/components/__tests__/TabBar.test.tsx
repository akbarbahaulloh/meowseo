/**
 * Unit Tests for TabBar Component
 * 
 * Requirements: 8.1, 8.2, 8.5, 8.7
 */

import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';
import { TabBar } from '../TabBar';

// Mock WordPress dependencies
jest.mock('@wordpress/data', () => ({
  useSelect: jest.fn(),
  useDispatch: jest.fn(),
  createReduxStore: jest.fn(),
  register: jest.fn(),
}));

jest.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
}));

const { useSelect, useDispatch } = require('@wordpress/data');

describe('TabBar Component', () => {
  let mockSetActiveTab: jest.Mock;

  beforeEach(() => {
    mockSetActiveTab = jest.fn();

    useDispatch.mockReturnValue({
      setActiveTab: mockSetActiveTab,
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  /**
   * Requirement 8.1: Display tabs for General, Social, Schema, Advanced
   */
  it('should display all four tabs', () => {
    useSelect.mockImplementation((selector: any) => {
      const mockSelect = (storeName: string) => ({
        getActiveTab: () => 'general',
      });
      return selector(mockSelect);
    });

    render(<TabBar />);

    expect(screen.getByTestId('tab-general')).toBeInTheDocument();
    expect(screen.getByTestId('tab-social')).toBeInTheDocument();
    expect(screen.getByTestId('tab-schema')).toBeInTheDocument();
    expect(screen.getByTestId('tab-advanced')).toBeInTheDocument();
  });

  /**
   * Requirement 8.2: Dispatch setActiveTab on tab click
   */
  it('should dispatch setActiveTab when a tab is clicked', () => {
    useSelect.mockImplementation((selector: any) => {
      const mockSelect = (storeName: string) => ({
        getActiveTab: () => 'general',
      });
      return selector(mockSelect);
    });

    render(<TabBar />);

    const socialTab = screen.getByTestId('tab-social');
    fireEvent.click(socialTab);

    expect(mockSetActiveTab).toHaveBeenCalledWith('social');
  });

  /**
   * Requirement 8.2: Dispatch setActiveTab for each tab
   */
  it('should dispatch setActiveTab for all tabs', () => {
    useSelect.mockImplementation((selector: any) => {
      const mockSelect = (storeName: string) => ({
        getActiveTab: () => 'general',
      });
      return selector(mockSelect);
    });

    render(<TabBar />);

    const tabs = ['general', 'social', 'schema', 'advanced'] as const;

    tabs.forEach((tabId) => {
      const tab = screen.getByTestId(`tab-${tabId}`);
      fireEvent.click(tab);
      expect(mockSetActiveTab).toHaveBeenCalledWith(tabId);
    });

    expect(mockSetActiveTab).toHaveBeenCalledTimes(4);
  });

  /**
   * Requirement 8.7: Highlight active tab visually
   */
  it('should highlight the active tab with is-active class', () => {
    useSelect.mockImplementation((selector: any) => {
      const mockSelect = (storeName: string) => ({
        getActiveTab: () => 'social',
      });
      return selector(mockSelect);
    });

    render(<TabBar />);

    const generalTab = screen.getByTestId('tab-general');
    const socialTab = screen.getByTestId('tab-social');
    const schemaTab = screen.getByTestId('tab-schema');
    const advancedTab = screen.getByTestId('tab-advanced');

    expect(generalTab).not.toHaveClass('is-active');
    expect(socialTab).toHaveClass('is-active');
    expect(schemaTab).not.toHaveClass('is-active');
    expect(advancedTab).not.toHaveClass('is-active');
  });

  /**
   * Requirement 8.7: Visual indication of active tab (aria-selected)
   */
  it('should set aria-selected=true for active tab', () => {
    useSelect.mockImplementation((selector: any) => {
      const mockSelect = (storeName: string) => ({
        getActiveTab: () => 'schema',
      });
      return selector(mockSelect);
    });

    render(<TabBar />);

    const generalTab = screen.getByTestId('tab-general');
    const socialTab = screen.getByTestId('tab-social');
    const schemaTab = screen.getByTestId('tab-schema');
    const advancedTab = screen.getByTestId('tab-advanced');

    expect(generalTab).toHaveAttribute('aria-selected', 'false');
    expect(socialTab).toHaveAttribute('aria-selected', 'false');
    expect(schemaTab).toHaveAttribute('aria-selected', 'true');
    expect(advancedTab).toHaveAttribute('aria-selected', 'false');
  });

  /**
   * Requirement 8.5: General tab is active by default
   */
  it('should highlight general tab when it is the default active tab', () => {
    useSelect.mockImplementation((selector: any) => {
      const mockSelect = (storeName: string) => ({
        getActiveTab: () => 'general',
      });
      return selector(mockSelect);
    });

    render(<TabBar />);

    const generalTab = screen.getByTestId('tab-general');
    expect(generalTab).toHaveClass('is-active');
    expect(generalTab).toHaveAttribute('aria-selected', 'true');
  });

  /**
   * Requirement 8.1: Tabs have proper ARIA attributes
   */
  it('should have proper ARIA attributes for accessibility', () => {
    useSelect.mockImplementation((selector: any) => {
      const mockSelect = (storeName: string) => ({
        getActiveTab: () => 'general',
      });
      return selector(mockSelect);
    });

    render(<TabBar />);

    const generalTab = screen.getByTestId('tab-general');

    expect(generalTab).toHaveAttribute('role', 'tab');
    expect(generalTab).toHaveAttribute('id', 'meowseo-tab-general');
    expect(generalTab).toHaveAttribute('aria-controls', 'meowseo-tab-panel-general');
  });
});
