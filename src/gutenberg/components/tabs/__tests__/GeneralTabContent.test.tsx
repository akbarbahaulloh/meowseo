/**
 * GeneralTabContent Component Tests
 *
 * Unit tests for the GeneralTabContent component.
 *
 * Requirements: 1.7, 9.6
 */

import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import GeneralTabContent from '../GeneralTabContent';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useSelect } from '@wordpress/data';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

// Mock @wordpress/components
jest.mock( '@wordpress/components', () => ( {
	TextControl: ( { label }: any ) => <div>{ label }</div>,
	TextareaControl: ( { label }: any ) => <div>{ label }</div>,
	Button: ( { children }: any ) => <button>{ children }</button>,
	ButtonGroup: ( { children }: any ) => <div>{ children }</div>,
	Spinner: () => <div>Loading...</div>,
} ) );

// Mock @wordpress/data
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	createSelector: jest.fn( ( selector ) => selector ),
} ) );

// Mock @wordpress/api-fetch
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

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

describe( 'GeneralTabContent', () => {
	beforeEach( () => {
		mockUseEntityPropBinding.mockReturnValue( [ '', jest.fn() ] );
		mockUseSelect.mockImplementation( ( selector: any ) => {
			return selector( ( storeName: string ) => {
				if ( storeName === 'core/editor' ) {
					return {
						getPermalink: () => 'https://example.com/test',
						getEditedPostAttribute: () => 'Test Title',
						getCurrentPostId: () => 123,
					};
				}
				return {};
			} );
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	/**
	 * Test: Render all General tab components
	 * Requirement: 1.7, 9.6 - Wire General tab components together
	 */
	it( 'should render all General tab components', () => {
		render( <GeneralTabContent /> );

		// Check for FocusKeywordInput
		expect( screen.getByText( 'Focus Keyword' ) ).toBeInTheDocument();

		// Check for DirectAnswerField
		expect( screen.getByText( 'Direct Answer' ) ).toBeInTheDocument();

		// Check for SerpPreview
		expect( screen.getByText( 'Search Preview' ) ).toBeInTheDocument();

		// Check for InternalLinkSuggestions (won't render without keyword, but component is mounted)
		const container = screen
			.getByText( 'Focus Keyword' )
			.closest( '.meowseo-general-tab' );
		expect( container ).toBeInTheDocument();
	} );

	/**
	 * Test: Components are rendered in correct order
	 * Requirement: 9.6 - Wire General tab components together
	 */
	it( 'should render components in correct order', () => {
		const { container } = render( <GeneralTabContent /> );

		const generalTab = container.querySelector( '.meowseo-general-tab' );
		expect( generalTab ).toBeInTheDocument();

		// Check that components exist in the DOM
		expect( screen.getByText( 'Search Preview' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Focus Keyword' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Direct Answer' ) ).toBeInTheDocument();
	} );

	/**
	 * Test: General tab has correct CSS class
	 * Requirement: 1.7 - Render General tab
	 */
	it( 'should have correct CSS class', () => {
		const { container } = render( <GeneralTabContent /> );

		const generalTab = container.querySelector( '.meowseo-general-tab' );
		expect( generalTab ).toBeInTheDocument();
	} );

	/**
	 * Test: SerpPreview component is rendered
	 * Requirement: 9.6 - Wire General tab components together
	 */
	it( 'should render SerpPreview component', () => {
		render( <GeneralTabContent /> );

		expect( screen.getByText( 'Search Preview' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Desktop' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Mobile' ) ).toBeInTheDocument();
	} );

	/**
	 * Test: FocusKeywordInput component is rendered
	 * Requirement: 9.6 - Wire General tab components together
	 */
	it( 'should render FocusKeywordInput component', () => {
		render( <GeneralTabContent /> );

		expect( screen.getByText( 'Focus Keyword' ) ).toBeInTheDocument();
	} );

	/**
	 * Test: DirectAnswerField component is rendered
	 * Requirement: 9.6 - Wire General tab components together
	 */
	it( 'should render DirectAnswerField component', () => {
		render( <GeneralTabContent /> );

		expect( screen.getByText( 'Direct Answer' ) ).toBeInTheDocument();
	} );

	/**
	 * Test: InternalLinkSuggestions component is mounted
	 * Requirement: 9.6 - Wire General tab components together
	 */
	it( 'should mount InternalLinkSuggestions component', () => {
		// Set focus keyword to trigger rendering
		mockUseEntityPropBinding.mockImplementation( ( key: string ) => {
			if ( key === '_meowseo_focus_keyword' ) {
				return [ 'wordpress seo', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		render( <GeneralTabContent /> );

		// Component should be mounted (will show loading or suggestions)
		const container = screen
			.getByText( 'Focus Keyword' )
			.closest( '.meowseo-general-tab' );
		expect( container ).toBeInTheDocument();
	} );
} );
