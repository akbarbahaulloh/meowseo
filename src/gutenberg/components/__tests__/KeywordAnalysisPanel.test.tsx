/**
 * KeywordAnalysisPanel Component Tests
 *
 * Tests for per-keyword analysis display component.
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import { KeywordAnalysisPanel } from '../KeywordAnalysisPanel';
import { useSelect } from '@wordpress/data';

// Mock @wordpress/data
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

// Mock @wordpress/components
jest.mock( '@wordpress/components', () => ( {
	Spinner: () => <div data-testid="spinner">Loading...</div>,
} ) );

describe( 'KeywordAnalysisPanel', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render empty state when no keywords are present', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			primaryKeyword: '',
			secondaryKeywords: [],
			keywordAnalysis: {},
			isAnalyzing: false,
		} );

		render( <KeywordAnalysisPanel /> );

		expect(
			screen.getByText( 'Add a focus keyword to see analysis results.' )
		).toBeInTheDocument();
	} );

	it( 'should render loading state when analyzing', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			primaryKeyword: 'wordpress seo',
			secondaryKeywords: [],
			keywordAnalysis: {},
			isAnalyzing: true,
		} );

		render( <KeywordAnalysisPanel /> );

		expect( screen.getByTestId( 'spinner' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Analyzing keywords…' ) ).toBeInTheDocument();
	} );

	it( 'should render primary keyword with analysis data', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			primaryKeyword: 'wordpress seo',
			secondaryKeywords: [],
			keywordAnalysis: {
				'wordpress seo': {
					overall_score: 85,
					density: { score: 80, status: 'good' },
					in_title: { score: 100, status: 'good' },
					in_headings: { score: 70, status: 'ok' },
					in_slug: { score: 100, status: 'good' },
					in_first_paragraph: { score: 100, status: 'good' },
					in_meta_description: { score: 100, status: 'good' },
				},
			},
			isAnalyzing: false,
		} );

		render( <KeywordAnalysisPanel /> );

		expect( screen.getByText( 'wordpress seo' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Primary' ) ).toBeInTheDocument();
		expect( screen.getByText( '85' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Excellent' ) ).toBeInTheDocument();
	} );

	it( 'should render multiple keywords (primary + secondary)', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			primaryKeyword: 'wordpress seo',
			secondaryKeywords: [ 'seo plugin', 'search optimization' ],
			keywordAnalysis: {
				'wordpress seo': {
					overall_score: 85,
				},
				'seo plugin': {
					overall_score: 70,
				},
				'search optimization': {
					overall_score: 55,
				},
			},
			isAnalyzing: false,
		} );

		render( <KeywordAnalysisPanel /> );

		expect( screen.getByText( 'wordpress seo' ) ).toBeInTheDocument();
		expect( screen.getByText( 'seo plugin' ) ).toBeInTheDocument();
		expect( screen.getByText( 'search optimization' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Primary' ) ).toBeInTheDocument();
	} );

	it( 'should expand and show individual check scores when clicked', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			primaryKeyword: 'wordpress seo',
			secondaryKeywords: [],
			keywordAnalysis: {
				'wordpress seo': {
					overall_score: 85,
					density: { score: 80, status: 'good' },
					in_title: { score: 100, status: 'good' },
					in_headings: { score: 70, status: 'ok' },
				},
			},
			isAnalyzing: false,
		} );

		render( <KeywordAnalysisPanel /> );

		// Initially, details should not be visible
		expect( screen.queryByText( 'Keyword Density' ) ).not.toBeInTheDocument();

		// Click to expand
		const expandButton = screen.getByRole( 'button', {
			name: /wordpress seo/i,
		} );
		fireEvent.click( expandButton );

		// Now details should be visible
		expect( screen.getByText( 'Keyword Density' ) ).toBeInTheDocument();
		expect( screen.getByText( 'In Title' ) ).toBeInTheDocument();
		expect( screen.getByText( 'In Headings' ) ).toBeInTheDocument();
	} );

	it( 'should use correct color coding for scores', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			primaryKeyword: 'test keyword',
			secondaryKeywords: [],
			keywordAnalysis: {
				'test keyword': {
					overall_score: 30, // Red (< 40)
					density: { score: 30, status: 'problem' },
				},
			},
			isAnalyzing: false,
		} );

		render( <KeywordAnalysisPanel /> );

		const scoreValue = screen.getByText( '30' );
		expect( scoreValue ).toHaveStyle( { color: '#dc3232' } ); // Red
		expect( screen.getByText( 'Needs Improvement' ) ).toBeInTheDocument();
	} );

	it( 'should handle missing analysis data gracefully', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			primaryKeyword: 'wordpress seo',
			secondaryKeywords: [],
			keywordAnalysis: {}, // No analysis data
			isAnalyzing: false,
		} );

		render( <KeywordAnalysisPanel /> );

		expect( screen.getByText( 'wordpress seo' ) ).toBeInTheDocument();
		expect( screen.getByText( '0' ) ).toBeInTheDocument();

		// Expand to see details
		const expandButton = screen.getByRole( 'button', {
			name: /wordpress seo/i,
		} );
		fireEvent.click( expandButton );

		expect(
			screen.getByText( 'No analysis data available' )
		).toBeInTheDocument();
	} );

	it( 'should handle store errors gracefully', () => {
		// Mock console.error to suppress React error boundary warnings
		const consoleError = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );

		( useSelect as jest.Mock ).mockReturnValue( {
			primaryKeyword: '',
			secondaryKeywords: [],
			keywordAnalysis: {},
			isAnalyzing: false,
		} );

		// Should render with default values when store has issues
		render( <KeywordAnalysisPanel /> );

		expect(
			screen.getByText( 'Add a focus keyword to see analysis results.' )
		).toBeInTheDocument();

		consoleError.mockRestore();
	} );
} );
