/**
 * Unit Tests for ContentScoreWidget Component
 * 
 * Tests:
 * - Score display
 * - Color coding based on score
 * - Analyze button click
 * - Button disabled state during analysis
 * - Loading indicator
 * 
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 5.1, 5.5
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import { ContentScoreWidget } from '../ContentScoreWidget';
import { useSelect, useDispatch } from '@wordpress/data';

// Mock the store module to prevent initialization
jest.mock('../../store', () => ({
  STORE_NAME: 'meowseo/data',
}));

// Mock @wordpress/data
jest.mock('@wordpress/data', () => ({
  useSelect: jest.fn(),
  useDispatch: jest.fn(),
}));

// Mock @wordpress/i18n
jest.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
}));

// Mock @wordpress/components
jest.mock('@wordpress/components', () => ({
  Button: ({ children, onClick, disabled, ...props }: any) => (
    <button onClick={onClick} disabled={disabled} {...props}>
      {children}
    </button>
  ),
  Spinner: () => <span data-testid="spinner">Loading...</span>,
}));

describe('ContentScoreWidget', () => {
  const mockAnalyzeContent = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    (useDispatch as jest.Mock).mockReturnValue({
      analyzeContent: mockAnalyzeContent,
    });
  });

  describe('Score Display', () => {
    it('should display SEO score', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const seoScore = screen.getByTestId('seo-score');
      expect(seoScore).toHaveTextContent('75');
    });

    it('should display readability score', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const readabilityScore = screen.getByTestId('readability-score');
      expect(readabilityScore).toHaveTextContent('60');
    });

    it('should display score labels', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      expect(screen.getByText('SEO Score')).toBeInTheDocument();
      expect(screen.getByText('Readability Score')).toBeInTheDocument();
    });
  });

  describe('Color Coding', () => {
    it('should display red color for score < 40', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 30,
        readabilityScore: 35,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const seoScore = screen.getByTestId('seo-score');
      const readabilityScore = screen.getByTestId('readability-score');

      expect(seoScore).toHaveStyle({ color: '#dc3232' });
      expect(readabilityScore).toHaveStyle({ color: '#dc3232' });
    });

    it('should display orange color for score 40-69', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 50,
        readabilityScore: 65,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const seoScore = screen.getByTestId('seo-score');
      const readabilityScore = screen.getByTestId('readability-score');

      expect(seoScore).toHaveStyle({ color: '#f56e28' });
      expect(readabilityScore).toHaveStyle({ color: '#f56e28' });
    });

    it('should display green color for score >= 70', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 80,
        readabilityScore: 90,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const seoScore = screen.getByTestId('seo-score');
      const readabilityScore = screen.getByTestId('readability-score');

      expect(seoScore).toHaveStyle({ color: '#46b450' });
      expect(readabilityScore).toHaveStyle({ color: '#46b450' });
    });

    it('should display orange color for score exactly 40', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 40,
        readabilityScore: 40,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const seoScore = screen.getByTestId('seo-score');
      const readabilityScore = screen.getByTestId('readability-score');

      expect(seoScore).toHaveStyle({ color: '#f56e28' });
      expect(readabilityScore).toHaveStyle({ color: '#f56e28' });
    });

    it('should display green color for score exactly 70', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 70,
        readabilityScore: 70,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const seoScore = screen.getByTestId('seo-score');
      const readabilityScore = screen.getByTestId('readability-score');

      expect(seoScore).toHaveStyle({ color: '#46b450' });
      expect(readabilityScore).toHaveStyle({ color: '#46b450' });
    });
  });

  describe('Analyze Button', () => {
    it('should display analyze button', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const button = screen.getByTestId('analyze-button');
      expect(button).toBeInTheDocument();
      expect(button).toHaveTextContent('Analyze');
    });

    it('should call analyzeContent when analyze button is clicked', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const button = screen.getByTestId('analyze-button');
      fireEvent.click(button);

      expect(mockAnalyzeContent).toHaveBeenCalledTimes(1);
    });

    it('should not be disabled when not analyzing', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const button = screen.getByTestId('analyze-button');
      expect(button).not.toBeDisabled();
    });
  });

  describe('Button Disabled State', () => {
    it('should disable button when analyzing', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: true,
      });

      render(<ContentScoreWidget />);

      const button = screen.getByTestId('analyze-button');
      expect(button).toBeDisabled();
    });

    it('should not call analyzeContent when button is clicked while analyzing', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: true,
      });

      render(<ContentScoreWidget />);

      const button = screen.getByTestId('analyze-button');
      fireEvent.click(button);

      // Button is disabled, so click should not trigger the action
      expect(mockAnalyzeContent).not.toHaveBeenCalled();
    });
  });

  describe('Loading Indicator', () => {
    it('should show loading indicator when analyzing', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: true,
      });

      render(<ContentScoreWidget />);

      const spinner = screen.getByTestId('spinner');
      expect(spinner).toBeInTheDocument();
    });

    it('should show "Analyzing..." text when analyzing', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: true,
      });

      render(<ContentScoreWidget />);

      const button = screen.getByTestId('analyze-button');
      expect(button).toHaveTextContent('Analyzing...');
    });

    it('should not show loading indicator when not analyzing', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const spinner = screen.queryByTestId('spinner');
      expect(spinner).not.toBeInTheDocument();
    });

    it('should show "Analyze" text when not analyzing', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 75,
        readabilityScore: 60,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const button = screen.getByTestId('analyze-button');
      expect(button).toHaveTextContent('Analyze');
      expect(button).not.toHaveTextContent('Analyzing...');
    });
  });

  describe('Edge Cases', () => {
    it('should handle score of 0', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 0,
        readabilityScore: 0,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const seoScore = screen.getByTestId('seo-score');
      const readabilityScore = screen.getByTestId('readability-score');

      expect(seoScore).toHaveTextContent('0');
      expect(readabilityScore).toHaveTextContent('0');
      expect(seoScore).toHaveStyle({ color: '#dc3232' }); // Red for < 40
    });

    it('should handle score of 100', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 100,
        readabilityScore: 100,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const seoScore = screen.getByTestId('seo-score');
      const readabilityScore = screen.getByTestId('readability-score');

      expect(seoScore).toHaveTextContent('100');
      expect(readabilityScore).toHaveTextContent('100');
      expect(seoScore).toHaveStyle({ color: '#46b450' }); // Green for >= 70
    });

    it('should handle boundary score of 39 (red)', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 39,
        readabilityScore: 39,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const seoScore = screen.getByTestId('seo-score');
      expect(seoScore).toHaveStyle({ color: '#dc3232' }); // Red
    });

    it('should handle boundary score of 69 (orange)', () => {
      (useSelect as jest.Mock).mockReturnValue({
        seoScore: 69,
        readabilityScore: 69,
        isAnalyzing: false,
      });

      render(<ContentScoreWidget />);

      const seoScore = screen.getByTestId('seo-score');
      expect(seoScore).toHaveStyle({ color: '#f56e28' }); // Orange
    });
  });
});
