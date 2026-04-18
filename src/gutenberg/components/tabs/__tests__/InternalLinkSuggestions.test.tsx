/**
 * InternalLinkSuggestions Component Tests
 *
 * Unit tests for the InternalLinkSuggestions component.
 *
 * Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 11.8
 */

import { render, screen, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import { act } from 'react-dom/test-utils';
import InternalLinkSuggestions from '../InternalLinkSuggestions';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

// Mock @wordpress/components
jest.mock( '@wordpress/components', () => ( {
	Spinner: () => <div data-testid="spinner">Loading...</div>,
} ) );

// Mock @wordpress/data
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	createSelector: jest.fn( ( selector ) => selector ),
} ) );

// Mock @wordpress/api-fetch
jest.mock( '@wordpress/api-fetch' );

// Mock @wordpress/core-data
jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn(),
} ) );

// Mock the useEntityPropBinding hook
jest.mock( '../../../hooks/useEntityPropBinding' );

const mockUseEntityPropBinding = useEntityPropBinding as jest.MockedFunction<
	typeof useEntityPropBinding
>;
const mockUseSelect = useSelect as jest.MockedFunction< typeof useSelect >;
const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'InternalLinkSuggestions', () => {
	beforeEach( () => {
		jest.useFakeTimers();

		mockUseEntityPropBinding.mockReturnValue( [
			'wordpress seo',
			jest.fn(),
		] );
		mockUseSelect.mockReturnValue( 123 ); // Mock post ID

		mockApiFetch.mockResolvedValue( {
			suggestions: [
				{
					post_id: 1,
					title: 'SEO Best Practices',
					url: 'https://example.com/seo-best-practices',
					relevance_score: 0.95,
				},
				{
					post_id: 2,
					title: 'WordPress SEO Guide',
					url: 'https://example.com/wordpress-seo-guide',
					relevance_score: 0.87,
				},
			],
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
		jest.useRealTimers();
	} );

	/**
	 * Test: Component does not render when focus keyword is empty
	 * Requirement: 11.3 - Skip fetch if focus keyword < 3 characters
	 */
	it( 'should not render when focus keyword is empty', () => {
		mockUseEntityPropBinding.mockReturnValue( [ '', jest.fn() ] );

		const { container } = render( <InternalLinkSuggestions /> );

		expect( container.firstChild ).toBeNull();
	} );

	/**
	 * Test: Component does not render when focus keyword is less than 3 characters
	 * Requirement: 11.3 - Skip fetch if focus keyword < 3 characters
	 */
	it( 'should not render when focus keyword is less than 3 characters', () => {
		mockUseEntityPropBinding.mockReturnValue( [ 'ab', jest.fn() ] );

		const { container } = render( <InternalLinkSuggestions /> );

		expect( container.firstChild ).toBeNull();
	} );

	/**
	 * Test: Display loading indicator during fetch
	 * Requirement: 11.6 - Display loading indicator during fetch
	 */
	it( 'should display loading indicator during fetch', () => {
		render( <InternalLinkSuggestions /> );

		expect( screen.getByTestId( 'spinner' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Loading suggestions...' )
		).toBeInTheDocument();
	} );

	/**
	 * Test: Implement 3-second debounce for focus keyword changes
	 * Requirement: 11.2 - Fetch link suggestions after 3 seconds
	 */
	it( 'should debounce API call by 3 seconds', async () => {
		render( <InternalLinkSuggestions /> );

		// Should not call API immediately
		expect( mockApiFetch ).not.toHaveBeenCalled();

		// Advance timers by 3 seconds
		act( () => {
			jest.advanceTimersByTime( 3000 );
		} );

		// Should call API after debounce
		await waitFor( () => {
			expect( mockApiFetch ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	/**
	 * Test: Call correct REST endpoint with correct parameters
	 * Requirement: 11.4, 11.5 - Call /meowseo/v1/internal-links/suggestions with post_id, keyword, limit
	 */
	it( 'should call REST endpoint with correct parameters', async () => {
		render( <InternalLinkSuggestions /> );

		act( () => {
			jest.advanceTimersByTime( 3000 );
		} );

		await waitFor( () => {
			expect( mockApiFetch ).toHaveBeenCalledWith( {
				path: '/meowseo/v1/internal-links/suggestions',
				method: 'POST',
				data: {
					post_id: 123,
					keyword: 'wordpress seo',
					limit: 5,
				},
			} );
		} );
	} );

	/**
	 * Test: Display suggestions with title, URL, and relevance score
	 * Requirement: 11.8 - Display post title, URL, and relevance score
	 */
	it( 'should display suggestions with title, URL, and relevance score', async () => {
		render( <InternalLinkSuggestions /> );

		act( () => {
			jest.advanceTimersByTime( 3000 );
		} );

		await waitFor( () => {
			expect(
				screen.getByText( 'SEO Best Practices' )
			).toBeInTheDocument();
			expect(
				screen.getByText( 'https://example.com/seo-best-practices' )
			).toBeInTheDocument();
			expect( screen.getByText( '95%' ) ).toBeInTheDocument();

			expect(
				screen.getByText( 'WordPress SEO Guide' )
			).toBeInTheDocument();
			expect(
				screen.getByText( 'https://example.com/wordpress-seo-guide' )
			).toBeInTheDocument();
			expect( screen.getByText( '87%' ) ).toBeInTheDocument();
		} );
	} );

	/**
	 * Test: Handle API errors gracefully
	 * Requirement: 11.7, 17.2 - Handle API errors gracefully and log error
	 */
	it( 'should handle API errors gracefully', async () => {
		const consoleErrorSpy = jest
			.spyOn( console, 'error' )
			.mockImplementation();
		mockApiFetch.mockRejectedValue( new Error( 'API Error' ) );

		render( <InternalLinkSuggestions /> );

		act( () => {
			jest.advanceTimersByTime( 3000 );
		} );

		await waitFor( () => {
			expect(
				screen.getByText(
					'Unable to load link suggestions. Please try again later.'
				)
			).toBeInTheDocument();
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				'Failed to fetch internal link suggestions:',
				expect.any( Error )
			);
		} );

		consoleErrorSpy.mockRestore();
	} );

	/**
	 * Test: Display empty state when no suggestions found
	 * Requirement: 11.1 - Display internal link suggestions component
	 */
	it( 'should display empty state when no suggestions found', async () => {
		mockApiFetch.mockResolvedValue( { suggestions: [] } );

		render( <InternalLinkSuggestions /> );

		act( () => {
			jest.advanceTimersByTime( 3000 );
		} );

		await waitFor( () => {
			expect(
				screen.getByText(
					'No internal link suggestions found for this keyword.'
				)
			).toBeInTheDocument();
		} );
	} );

	/**
	 * Test: Display component title
	 * Requirement: 11.1 - Display internal link suggestions component
	 */
	it( 'should display component title', () => {
		render( <InternalLinkSuggestions /> );

		expect(
			screen.getByText( 'Internal Link Suggestions' )
		).toBeInTheDocument();
	} );

	/**
	 * Test: Relevance score is displayed as percentage
	 * Requirement: 11.8 - Display relevance score
	 */
	it( 'should display relevance score as percentage', async () => {
		render( <InternalLinkSuggestions /> );

		act( () => {
			jest.advanceTimersByTime( 3000 );
		} );

		await waitFor( () => {
			const relevanceLabels = screen.getAllByText( 'Relevance:' );
			expect( relevanceLabels ).toHaveLength( 2 );
		} );
	} );

	/**
	 * Test: Links open in new tab
	 * Requirement: 11.8 - Display suggestions with clickable links
	 */
	it( 'should render links that open in new tab', async () => {
		render( <InternalLinkSuggestions /> );

		act( () => {
			jest.advanceTimersByTime( 3000 );
		} );

		await waitFor( () => {
			const links = screen.getAllByRole( 'link' );
			links.forEach( ( link ) => {
				expect( link ).toHaveAttribute( 'target', '_blank' );
				expect( link ).toHaveAttribute( 'rel', 'noopener noreferrer' );
			} );
		} );
	} );
} );
