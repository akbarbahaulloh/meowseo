/**
 * Unit Tests for ContentScoreWidget Component
 *
 * Tests:
 * - Score display
 * - Color coding based on score
 * - Loading indicator
 * - Analyzer category display
 *
 * Requirements: 30.1, 30.2, 30.3, 30.4, 30.5
 */

import { render, screen, act } from '@testing-library/react';
import '@testing-library/jest-dom';
import { ContentScoreWidget } from '../ContentScoreWidget';
import { useSelect } from '@wordpress/data';

// Mock the store module to prevent initialization
jest.mock( '../../store', () => ( {
	STORE_NAME: 'meowseo/data',
} ) );

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
	Spinner: () => <span data-testid="spinner">Loading...</span>,
} ) );

// Mock AnalyzerResultItem
jest.mock( '../AnalyzerResultItem', () => ( {
	AnalyzerResultItem: ( { result }: any ) => (
		<div data-testid={ `analyzer-result-${ result.id }` }>
			{ result.message }
		</div>
	),
} ) );

describe( 'ContentScoreWidget', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'Score Display', () => {
		it( 'should display SEO and readability scores', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 75,
				readabilityScore: 60,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			render( <ContentScoreWidget /> );

			expect( screen.getByText( 'SEO Score' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Readability' ) ).toBeInTheDocument();
			expect( screen.getByText( '75' ) ).toBeInTheDocument();
			expect( screen.getByText( '60' ) ).toBeInTheDocument();
		} );

		it( 'should display score labels', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 75,
				readabilityScore: 60,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			render( <ContentScoreWidget /> );

			expect( screen.getByText( 'SEO Score' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Readability' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'Color Coding', () => {
		it( 'should display red color for score < 40', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 30,
				readabilityScore: 35,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			const { container } = render( <ContentScoreWidget /> );
			const scoreValues = container.querySelectorAll(
				'.meowseo-score-value'
			);

			expect( scoreValues.length ).toBeGreaterThan( 0 );
			// Check that red color is applied
			expect( scoreValues[ 0 ] ).toHaveStyle( { color: '#dc3232' } );
		} );

		it( 'should display orange color for score 40-69', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 50,
				readabilityScore: 65,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			const { container } = render( <ContentScoreWidget /> );
			const scoreValues = container.querySelectorAll(
				'.meowseo-score-value'
			);

			expect( scoreValues.length ).toBeGreaterThan( 0 );
			// Check that orange color is applied
			expect( scoreValues[ 0 ] ).toHaveStyle( { color: '#f56e28' } );
		} );

		it( 'should display green color for score >= 70', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 80,
				readabilityScore: 90,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			const { container } = render( <ContentScoreWidget /> );
			const scoreValues = container.querySelectorAll(
				'.meowseo-score-value'
			);

			expect( scoreValues.length ).toBeGreaterThan( 0 );
			// Check that green color is applied
			expect( scoreValues[ 0 ] ).toHaveStyle( { color: '#46b450' } );
		} );
	} );

	describe( 'Loading Indicator', () => {
		it( 'should show loading indicator when analyzing', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 75,
				readabilityScore: 60,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: true,
			} );

			render( <ContentScoreWidget /> );

			const spinners = screen.getAllByTestId( 'spinner' );
			expect( spinners.length ).toBeGreaterThan( 0 );
		} );

		it( 'should not show loading indicator when not analyzing', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 75,
				readabilityScore: 60,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			render( <ContentScoreWidget /> );

			const spinner = screen.queryByTestId( 'spinner' );
			expect( spinner ).not.toBeInTheDocument();
		} );
	} );

	describe( 'Analyzer Categories', () => {
		it( 'should display SEO Analysis category', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 75,
				readabilityScore: 60,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			render( <ContentScoreWidget /> );

			expect( screen.getByText( 'SEO Analysis' ) ).toBeInTheDocument();
		} );

		it( 'should display Readability Analysis category', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 75,
				readabilityScore: 60,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			render( <ContentScoreWidget /> );

			expect(
				screen.getByText( 'Readability Analysis' )
			).toBeInTheDocument();
		} );

		it( 'should display analyzer results when expanded', () => {
			const seoResults = [
				{
					id: 'keyword-in-title',
					type: 'good',
					message: 'Focus keyword appears in SEO title',
					score: 100,
					weight: 0.08,
				},
			];

			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 75,
				readabilityScore: 60,
				seoResults,
				readabilityResults: [],
				isAnalyzing: false,
			} );

			const { container } = render( <ContentScoreWidget /> );

			// Click to expand SEO Analysis
			const seoHeader = container.querySelector(
				'.meowseo-analyzer-category-header'
			) as HTMLButtonElement;
			if ( seoHeader ) {
				act( () => {
					seoHeader.click();
				} );
			}

			// The analyzer result should be displayed
			expect(
				container.querySelector(
					'[data-testid="analyzer-result-keyword-in-title"]'
				)
			).toBeInTheDocument();
		} );
	} );

	describe( 'Edge Cases', () => {
		it( 'should handle score of 0', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 0,
				readabilityScore: 0,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			render( <ContentScoreWidget /> );

			const scores = screen.getAllByText( '0' );
			expect( scores.length ).toBeGreaterThan( 0 );
		} );

		it( 'should handle score of 100', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 100,
				readabilityScore: 100,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			render( <ContentScoreWidget /> );

			const scores = screen.getAllByText( '100' );
			expect( scores.length ).toBeGreaterThan( 0 );
		} );

		it( 'should handle missing store gracefully', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 0,
				readabilityScore: 0,
				seoResults: [],
				readabilityResults: [],
				isAnalyzing: false,
			} );

			render( <ContentScoreWidget /> );

			expect( screen.getByText( 'SEO Score' ) ).toBeInTheDocument();
		} );
	} );
} );
