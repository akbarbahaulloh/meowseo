/**
 * Sample component test to demonstrate React component testing.
 *
 * @package MeowSEO\Tests\JS
 */

import { render, screen, fireEvent } from '@testing-library/react';
import { Button } from '@wordpress/components';

/**
 * Simple test component for demonstration.
 */
const TestButton = ( { onClick, children } ) => {
	return <Button onClick={ onClick }>{ children }</Button>;
};

describe( 'Sample Component Tests', () => {
	it( 'should render button with text', () => {
		render( <TestButton>Click Me</TestButton> );
		
		const button = screen.getByText( 'Click Me' );
		expect( button ).toBeInTheDocument();
	} );

	it( 'should call onClick handler when clicked', () => {
		const handleClick = jest.fn();
		render( <TestButton onClick={ handleClick }>Click Me</TestButton> );
		
		const button = screen.getByText( 'Click Me' );
		fireEvent.click( button );
		
		expect( handleClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should render children correctly', () => {
		render( <TestButton>Test Content</TestButton> );
		
		expect( screen.getByText( 'Test Content' ) ).toBeInTheDocument();
	} );
} );

/**
 * Example of testing WordPress i18n functions.
 */
describe( 'WordPress i18n Integration', () => {
	it( 'should translate strings using __', () => {
		const translated = wp.i18n.__( 'Hello World' );
		expect( translated ).toBe( 'Hello World' );
	} );

	it( 'should handle plural translations using _n', () => {
		const single = wp.i18n._n( '1 item', '%d items', 1 );
		const plural = wp.i18n._n( '1 item', '%d items', 5 );
		
		expect( single ).toBe( '1 item' );
		expect( plural ).toBe( '%d items' );
	} );
} );

/**
 * Example of testing with global meowseo object.
 */
describe( 'MeowSEO Global Object', () => {
	it( 'should have restUrl defined', () => {
		expect( global.meowseo.restUrl ).toBeDefined();
		expect( global.meowseo.restUrl ).toContain( 'wp-json/meowseo/v1/' );
	} );

	it( 'should have nonce defined', () => {
		expect( global.meowseo.nonce ).toBeDefined();
		expect( global.meowseo.nonce ).toBe( 'test-nonce' );
	} );

	it( 'should have version defined', () => {
		expect( global.meowseo.version ).toBeDefined();
	} );
} );
