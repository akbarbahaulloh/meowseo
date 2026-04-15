/**
 * RobotsToggles Component Tests
 * 
 * Unit tests for the RobotsToggles component.
 * 
 * Requirements: 14.1, 14.2, 14.3
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import RobotsToggles from '../RobotsToggles';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';

// Mock @wordpress/i18n
jest.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
  _x: (text: string) => text,
  _n: (single: string, plural: string, number: number) => number === 1 ? single : plural,
  isRTL: () => false,
}));

// Mock @wordpress/components
jest.mock('@wordpress/components', () => ({
  ToggleControl: ({ label, help, checked, onChange }: any) => {
    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
      onChange(e.target.checked);
    };
    
    return (
      <div>
        <label>
          <input
            type="checkbox"
            checked={checked}
            onChange={handleChange}
            aria-label={label}
          />
          {label}
        </label>
        {help && <p>{help}</p>}
      </div>
    );
  },
}));

// Mock the useEntityPropBinding hook
jest.mock('../../../hooks/useEntityPropBinding');

const mockUseEntityPropBinding = useEntityPropBinding as jest.MockedFunction<typeof useEntityPropBinding>;

describe('RobotsToggles', () => {
  let mockSetNoindex: jest.Mock;
  let mockSetNofollow: jest.Mock;
  
  beforeEach(() => {
    // Reset all mocks
    jest.clearAllMocks();
    
    // Create fresh mock functions
    mockSetNoindex = jest.fn();
    mockSetNofollow = jest.fn();
  });
  
  afterEach(() => {
    jest.clearAllMocks();
  });
  
  /**
   * Test: Both toggle controls are displayed
   * Requirement: 14.1 - Display toggles for noindex and nofollow
   */
  it('should display noindex and nofollow toggles', () => {
    mockUseEntityPropBinding
      .mockReturnValueOnce(['', mockSetNoindex])
      .mockReturnValueOnce(['', mockSetNofollow]);
    
    render(<RobotsToggles />);
    
    const noindexToggle = screen.getByLabelText(/no index/i);
    const nofollowToggle = screen.getByLabelText(/no follow/i);
    
    expect(noindexToggle).toBeInTheDocument();
    expect(nofollowToggle).toBeInTheDocument();
  });
  
  /**
   * Test: useEntityPropBinding is called with correct meta keys
   * Requirement: 14.2, 14.3 - Use Entity_Prop for robots directives
   */
  it('should use useEntityPropBinding with correct meta keys', () => {
    mockUseEntityPropBinding
      .mockReturnValueOnce(['', mockSetNoindex])
      .mockReturnValueOnce(['', mockSetNofollow]);
    
    render(<RobotsToggles />);
    
    expect(mockUseEntityPropBinding).toHaveBeenCalledWith('_meowseo_robots_noindex');
    expect(mockUseEntityPropBinding).toHaveBeenCalledWith('_meowseo_robots_nofollow');
  });
  
  /**
   * Test: Toggles are unchecked by default
   * Requirement: 14.1 - Display toggles with correct initial state
   */
  it('should display toggles as unchecked when values are empty', () => {
    mockUseEntityPropBinding
      .mockReturnValueOnce(['', mockSetNoindex])
      .mockReturnValueOnce(['', mockSetNofollow]);
    
    render(<RobotsToggles />);
    
    const noindexToggle = screen.getByLabelText(/no index/i) as HTMLInputElement;
    const nofollowToggle = screen.getByLabelText(/no follow/i) as HTMLInputElement;
    
    expect(noindexToggle.checked).toBe(false);
    expect(nofollowToggle.checked).toBe(false);
  });
  
  /**
   * Test: Toggles are checked when values are '1'
   * Requirement: 14.2, 14.3 - Display persisted values correctly
   */
  it('should display toggles as checked when values are "1"', () => {
    // Don't clear mocks - let beforeEach handle setup
    // Create new mock functions for this test
    const testMockSetNoindex = jest.fn();
    const testMockSetNofollow = jest.fn();
    
    // Override the mock for this specific test
    mockUseEntityPropBinding
      .mockReturnValueOnce(['1', testMockSetNoindex])
      .mockReturnValueOnce(['1', testMockSetNofollow]);
    
    render(<RobotsToggles />);
    
    const noindexToggle = screen.getByLabelText(/no index/i) as HTMLInputElement;
    const nofollowToggle = screen.getByLabelText(/no follow/i) as HTMLInputElement;
    
    expect(noindexToggle.checked).toBe(true);
    expect(nofollowToggle.checked).toBe(true);
  });
  
  /**
   * Test: Toggles are checked when values are 'true'
   * Requirement: 14.2, 14.3 - Handle boolean string values
   */
  it('should display toggles as checked when values are "true"', () => {
    // Don't clear mocks - let beforeEach handle setup
    // Create new mock functions for this test
    const testMockSetNoindex = jest.fn();
    const testMockSetNofollow = jest.fn();
    
    // Override the mock for this specific test
    mockUseEntityPropBinding
      .mockReturnValueOnce(['true', testMockSetNoindex])
      .mockReturnValueOnce(['true', testMockSetNofollow]);
    
    render(<RobotsToggles />);
    
    const noindexToggle = screen.getByLabelText(/no index/i) as HTMLInputElement;
    const nofollowToggle = screen.getByLabelText(/no follow/i) as HTMLInputElement;
    
    expect(noindexToggle.checked).toBe(true);
    expect(nofollowToggle.checked).toBe(true);
  });
  
  /**
   * Test: Toggling noindex calls setValue with '1'
   * Requirement: 14.2 - Persist noindex to postmeta
   */
  it('should call setValue with "1" when noindex is toggled on', () => {
    mockUseEntityPropBinding
      .mockReturnValueOnce(['', mockSetNoindex])
      .mockReturnValueOnce(['', mockSetNofollow]);
    
    render(<RobotsToggles />);
    
    const noindexToggle = screen.getByLabelText(/no index/i);
    fireEvent.click(noindexToggle);
    
    expect(mockSetNoindex).toHaveBeenCalledWith('1');
  });
  
  /**
   * Test: Toggling noindex off calls setValue with empty string
   * Requirement: 14.2 - Persist noindex to postmeta
   */
  it('should call setValue with empty string when noindex is toggled off', () => {
    // Don't clear mocks - let beforeEach handle setup
    // Create new mock functions for this test
    const testMockSetNoindex = jest.fn();
    const testMockSetNofollow = jest.fn();
    
    // Override the mock for this specific test
    mockUseEntityPropBinding
      .mockReturnValueOnce(['1', testMockSetNoindex])
      .mockReturnValueOnce(['', testMockSetNofollow]);
    
    render(<RobotsToggles />);
    
    const noindexToggle = screen.getByLabelText(/no index/i);
    fireEvent.click(noindexToggle);
    
    expect(testMockSetNoindex).toHaveBeenCalledWith('');
  });
  
  /**
   * Test: Toggling nofollow calls setValue with '1'
   * Requirement: 14.3 - Persist nofollow to postmeta
   */
  it('should call setValue with "1" when nofollow is toggled on', () => {
    mockUseEntityPropBinding
      .mockReturnValueOnce(['', mockSetNoindex])
      .mockReturnValueOnce(['', mockSetNofollow]);
    
    render(<RobotsToggles />);
    
    const nofollowToggle = screen.getByLabelText(/no follow/i);
    fireEvent.click(nofollowToggle);
    
    expect(mockSetNofollow).toHaveBeenCalledWith('1');
  });
  
  /**
   * Test: Toggling nofollow off calls setValue with empty string
   * Requirement: 14.3 - Persist nofollow to postmeta
   */
  it('should call setValue with empty string when nofollow is toggled off', () => {
    // Don't clear mocks - let beforeEach handle setup
    // Create new mock functions for this test
    const testMockSetNoindex = jest.fn();
    const testMockSetNofollow = jest.fn();
    
    // Override the mock for this specific test
    mockUseEntityPropBinding
      .mockReturnValueOnce(['', testMockSetNoindex])
      .mockReturnValueOnce(['1', testMockSetNofollow]);
    
    render(<RobotsToggles />);
    
    const nofollowToggle = screen.getByLabelText(/no follow/i);
    fireEvent.click(nofollowToggle);
    
    expect(testMockSetNofollow).toHaveBeenCalledWith('');
  });
  
  /**
   * Test: Help text is displayed for both toggles
   * Requirement: 14.1 - Display help text for toggles
   */
  it('should display help text for both toggles', () => {
    mockUseEntityPropBinding
      .mockReturnValueOnce(['', mockSetNoindex])
      .mockReturnValueOnce(['', mockSetNofollow]);
    
    render(<RobotsToggles />);
    
    const noindexHelp = screen.getByText(/prevent search engines from indexing/i);
    const nofollowHelp = screen.getByText(/prevent search engines from following links/i);
    
    expect(noindexHelp).toBeInTheDocument();
    expect(nofollowHelp).toBeInTheDocument();
  });
});
