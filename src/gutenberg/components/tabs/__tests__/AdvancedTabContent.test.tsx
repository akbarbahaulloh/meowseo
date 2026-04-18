/**
 * AdvancedTabContent Component Tests
 *
 * Unit tests for the AdvancedTabContent component.
 *
 * Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8
 */

import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import AdvancedTabContent from '../AdvancedTabContent';

// Mock child components
jest.mock( '../RobotsToggles', () => {
	return function RobotsToggles() {
		return <div data-testid="robots-toggles">RobotsToggles Component</div>;
	};
} );

jest.mock( '../CanonicalURLInput', () => {
	return function CanonicalURLInput() {
		return (
			<div data-testid="canonical-url-input">
				CanonicalURLInput Component
			</div>
		);
	};
} );

jest.mock( '../GSCIntegration', () => {
	return function GSCIntegration() {
		return (
			<div data-testid="gsc-integration">GSCIntegration Component</div>
		);
	};
} );

describe( 'AdvancedTabContent', () => {
	/**
	 * Test: All child components are rendered
	 * Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8
	 */
	it( 'should render all child components', () => {
		render( <AdvancedTabContent /> );

		const robotsToggles = screen.getByTestId( 'robots-toggles' );
		const canonicalUrlInput = screen.getByTestId( 'canonical-url-input' );
		const gscIntegration = screen.getByTestId( 'gsc-integration' );

		expect( robotsToggles ).toBeInTheDocument();
		expect( canonicalUrlInput ).toBeInTheDocument();
		expect( gscIntegration ).toBeInTheDocument();
	} );

	/**
	 * Test: RobotsToggles is rendered first
	 * Requirements: 14.1, 14.2, 14.3
	 */
	it( 'should render RobotsToggles component', () => {
		render( <AdvancedTabContent /> );

		const robotsToggles = screen.getByTestId( 'robots-toggles' );
		expect( robotsToggles ).toBeInTheDocument();
		expect( robotsToggles ).toHaveTextContent( 'RobotsToggles Component' );
	} );

	/**
	 * Test: CanonicalURLInput is rendered second
	 * Requirements: 14.4, 14.5, 14.6
	 */
	it( 'should render CanonicalURLInput component', () => {
		render( <AdvancedTabContent /> );

		const canonicalUrlInput = screen.getByTestId( 'canonical-url-input' );
		expect( canonicalUrlInput ).toBeInTheDocument();
		expect( canonicalUrlInput ).toHaveTextContent(
			'CanonicalURLInput Component'
		);
	} );

	/**
	 * Test: GSCIntegration is rendered third
	 * Requirements: 14.7, 14.8
	 */
	it( 'should render GSCIntegration component', () => {
		render( <AdvancedTabContent /> );

		const gscIntegration = screen.getByTestId( 'gsc-integration' );
		expect( gscIntegration ).toBeInTheDocument();
		expect( gscIntegration ).toHaveTextContent(
			'GSCIntegration Component'
		);
	} );

	/**
	 * Test: Container has correct class name
	 * Requirement: Component structure
	 */
	it( 'should have correct container class name', () => {
		const { container } = render( <AdvancedTabContent /> );

		const advancedTab = container.querySelector( '.meowseo-advanced-tab' );
		expect( advancedTab ).toBeInTheDocument();
	} );

	/**
	 * Test: Components are rendered in correct order
	 * Requirements: 14.1-14.8 - Logical grouping of components
	 */
	it( 'should render components in correct order', () => {
		const { container } = render( <AdvancedTabContent /> );

		const advancedTab = container.querySelector( '.meowseo-advanced-tab' );
		const children = advancedTab?.children;

		expect( children?.[ 0 ] ).toHaveAttribute(
			'data-testid',
			'robots-toggles'
		);
		expect( children?.[ 1 ] ).toHaveAttribute(
			'data-testid',
			'canonical-url-input'
		);
		expect( children?.[ 2 ] ).toHaveAttribute(
			'data-testid',
			'gsc-integration'
		);
	} );
} );
