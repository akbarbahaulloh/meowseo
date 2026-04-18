/**
 * Unit Tests for TabContent Component
 *
 * Requirements: 8.3, 8.4
 */

import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import { TabContentTestHelper } from '../TabContent.test-helper';

// Mock WordPress dependencies
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	createReduxStore: jest.fn(),
	register: jest.fn(),
} ) );

// Mock tab content components
jest.mock( '../tabs/GeneralTabContent', () => ( {
	__esModule: true,
	default: () => <div data-testid="general-content">General Content</div>,
} ) );

jest.mock( '../tabs/SocialTabContent', () => ( {
	__esModule: true,
	default: () => <div data-testid="social-content">Social Content</div>,
} ) );

jest.mock( '../tabs/SchemaTabContent', () => ( {
	__esModule: true,
	default: () => <div data-testid="schema-content">Schema Content</div>,
} ) );

jest.mock( '../tabs/AdvancedTabContent', () => ( {
	__esModule: true,
	default: () => <div data-testid="advanced-content">Advanced Content</div>,
} ) );

const { useSelect } = require( '@wordpress/data' );

describe( 'TabContent Component', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	/**
	 * Requirement 8.3: Render only active tab content
	 */
	it( 'should render only the general tab content when general is active', () => {
		useSelect.mockImplementation( ( selector: any ) => {
			const mockSelect = ( storeName: string ) => ( {
				getActiveTab: () => 'general',
			} );
			return selector( mockSelect );
		} );

		render( <TabContentTestHelper /> );

		expect( screen.getByTestId( 'tab-panel-general' ) ).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'tab-panel-social' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'tab-panel-schema' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'tab-panel-advanced' )
		).not.toBeInTheDocument();
	} );

	/**
	 * Requirement 8.3: Render only active tab content
	 */
	it( 'should render only the social tab content when social is active', () => {
		useSelect.mockImplementation( ( selector: any ) => {
			const mockSelect = ( storeName: string ) => ( {
				getActiveTab: () => 'social',
			} );
			return selector( mockSelect );
		} );

		render( <TabContentTestHelper /> );

		expect(
			screen.queryByTestId( 'tab-panel-general' )
		).not.toBeInTheDocument();
		expect( screen.getByTestId( 'tab-panel-social' ) ).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'tab-panel-schema' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'tab-panel-advanced' )
		).not.toBeInTheDocument();
	} );

	/**
	 * Requirement 8.3: Render only active tab content
	 */
	it( 'should render only the schema tab content when schema is active', () => {
		useSelect.mockImplementation( ( selector: any ) => {
			const mockSelect = ( storeName: string ) => ( {
				getActiveTab: () => 'schema',
			} );
			return selector( mockSelect );
		} );

		render( <TabContentTestHelper /> );

		expect(
			screen.queryByTestId( 'tab-panel-general' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'tab-panel-social' )
		).not.toBeInTheDocument();
		expect( screen.getByTestId( 'tab-panel-schema' ) ).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'tab-panel-advanced' )
		).not.toBeInTheDocument();
	} );

	/**
	 * Requirement 8.3: Render only active tab content
	 */
	it( 'should render only the advanced tab content when advanced is active', () => {
		useSelect.mockImplementation( ( selector: any ) => {
			const mockSelect = ( storeName: string ) => ( {
				getActiveTab: () => 'advanced',
			} );
			return selector( mockSelect );
		} );

		render( <TabContentTestHelper /> );

		expect(
			screen.queryByTestId( 'tab-panel-general' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'tab-panel-social' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'tab-panel-schema' )
		).not.toBeInTheDocument();
		expect(
			screen.getByTestId( 'tab-panel-advanced' )
		).toBeInTheDocument();
	} );

	/**
	 * Requirement 8.4: Inactive tabs are not rendered
	 */
	it( 'should not render inactive tab content in the DOM', () => {
		useSelect.mockImplementation( ( selector: any ) => {
			const mockSelect = ( storeName: string ) => ( {
				getActiveTab: () => 'general',
			} );
			return selector( mockSelect );
		} );

		const { container } = render( <TabContentTestHelper /> );

		// Check that only general tab panel exists in DOM
		expect(
			container.querySelector( '#meowseo-tab-panel-general' )
		).toBeInTheDocument();
		expect(
			container.querySelector( '#meowseo-tab-panel-social' )
		).not.toBeInTheDocument();
		expect(
			container.querySelector( '#meowseo-tab-panel-schema' )
		).not.toBeInTheDocument();
		expect(
			container.querySelector( '#meowseo-tab-panel-advanced' )
		).not.toBeInTheDocument();
	} );

	/**
	 * Requirement 8.3, 8.4: Tab content has proper ARIA attributes
	 */
	it( 'should have proper ARIA attributes for active tab panel', () => {
		useSelect.mockImplementation( ( selector: any ) => {
			const mockSelect = ( storeName: string ) => ( {
				getActiveTab: () => 'social',
			} );
			return selector( mockSelect );
		} );

		render( <TabContentTestHelper /> );

		const socialPanel = screen.getByTestId( 'tab-panel-social' );

		expect( socialPanel ).toHaveAttribute( 'role', 'tabpanel' );
		expect( socialPanel ).toHaveAttribute(
			'id',
			'meowseo-tab-panel-social'
		);
		expect( socialPanel ).toHaveAttribute(
			'aria-labelledby',
			'meowseo-tab-social'
		);
	} );
} );
