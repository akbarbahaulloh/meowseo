/**
 * GSCIntegration Component Tests
 *
 * Unit tests for the GSCIntegration component.
 *
 * Requirements: 14.7, 14.8
 */

// Mock @wordpress/private-apis FIRST before any other imports
jest.mock( '@wordpress/private-apis', () => ( {
	__dangerousOptInToUnstableAPIsOnlyForCoreModules: jest.fn( () => ( {
		lock: jest.fn(),
		unlock: jest.fn( () => ( {
			registerPrivateSelectors: jest.fn(),
			registerPrivateActions: jest.fn(),
		} ) ),
	} ) ),
} ) );

// Mock @wordpress/core-data to avoid deep dependency issues
jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn(),
	store: jest.fn(),
} ) );

import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import GSCIntegration from '../GSCIntegration';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
	_x: ( text: string ) => text,
	_n: ( single: string, plural: string, number: number ) =>
		number === 1 ? single : plural,
	isRTL: () => false,
} ) );

// Mock @wordpress/components
jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, onClick, isBusy, disabled, variant }: any ) => (
		<button
			onClick={ onClick }
			disabled={ disabled }
			data-busy={ isBusy }
			data-variant={ variant }
		>
			{ children }
		</button>
	),
} ) );

// Mock @wordpress/data
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	createSelector: jest.fn( ( selector ) => selector ),
	createRegistrySelector: jest.fn( ( selector ) => selector ),
	combineReducers: jest.fn( ( reducers ) => reducers ),
	createReduxStore: jest.fn(),
	register: jest.fn(),
} ) );

// Mock @wordpress/api-fetch
jest.mock( '@wordpress/api-fetch' );

// Mock @wordpress/element
jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( 'react' ),
	useState: jest.requireActual( 'react' ).useState,
} ) );

// Mock the useEntityPropBinding hook
jest.mock( '../../../hooks/useEntityPropBinding' );

const mockUseEntityPropBinding = useEntityPropBinding as jest.MockedFunction<
	typeof useEntityPropBinding
>;
const mockUseSelect = useSelect as jest.MockedFunction< typeof useSelect >;
const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'GSCIntegration', () => {
	let mockSetLastSubmit: jest.Mock;

	beforeEach( () => {
		mockSetLastSubmit = jest.fn();
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetLastSubmit ] );

		// Mock post ID and permalink
		mockUseSelect.mockReturnValue( {
			postId: 123,
			permalink: 'https://example.com/sample-post',
		} );

		// Clear console.error mock
		jest.spyOn( console, 'error' ).mockImplementation( () => {} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
		jest.restoreAllMocks();
	} );

	/**
	 * Test: Last submission timestamp is displayed
	 * Requirement: 14.7 - Display last submission timestamp
	 */
	it( 'should display last submission timestamp', () => {
		mockUseEntityPropBinding.mockReturnValue( [
			'2024-01-15T10:30:00',
			mockSetLastSubmit,
		] );

		render( <GSCIntegration /> );

		const label = screen.getByText( /last indexing request/i );
		expect( label ).toBeInTheDocument();

		// Timestamp should be formatted
		const timestamp = screen.getByText( /2024/ );
		expect( timestamp ).toBeInTheDocument();
	} );

	/**
	 * Test: "Never" is displayed when no timestamp exists
	 * Requirement: 14.7 - Display "Never" when no submission exists
	 */
	it( 'should display "Never" when no timestamp exists', () => {
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetLastSubmit ] );

		render( <GSCIntegration /> );

		const neverText = screen.getByText( /never/i );
		expect( neverText ).toBeInTheDocument();
	} );

	/**
	 * Test: Request Indexing button is displayed
	 * Requirement: 14.8 - Display "Request Indexing" button
	 */
	it( 'should display Request Indexing button', () => {
		render( <GSCIntegration /> );

		const button = screen.getByRole( 'button', {
			name: /request indexing/i,
		} );
		expect( button ).toBeInTheDocument();
	} );

	/**
	 * Test: Clicking button calls API
	 * Requirement: 14.8 - Call Google Search Console API on button click
	 */
	it( 'should call API when button is clicked', async () => {
		mockApiFetch.mockResolvedValue( {
			success: true,
			message: 'Indexing request submitted',
		} );

		render( <GSCIntegration /> );

		const button = screen.getByRole( 'button', {
			name: /request indexing/i,
		} );
		fireEvent.click( button );

		await waitFor( () => {
			expect( mockApiFetch ).toHaveBeenCalledWith( {
				path: '/meowseo/v1/gsc/request-indexing',
				method: 'POST',
				data: {
					post_id: 123,
					url: 'https://example.com/sample-post',
				},
			} );
		} );
	} );

	/**
	 * Test: Button is disabled while requesting
	 * Requirement: 14.8 - Disable button during API call
	 */
	it( 'should disable button while requesting', async () => {
		mockApiFetch.mockImplementation(
			() => new Promise( ( resolve ) => setTimeout( resolve, 100 ) )
		);

		render( <GSCIntegration /> );

		const button = screen.getByRole( 'button', {
			name: /request indexing/i,
		} );
		fireEvent.click( button );

		// Button should be disabled immediately
		expect( button ).toBeDisabled();
		expect( button ).toHaveAttribute( 'data-busy', 'true' );

		// Button text should change
		expect( screen.getByText( /requesting\.\.\./i ) ).toBeInTheDocument();
	} );

	/**
	 * Test: Success message is displayed on successful request
	 * Requirement: 14.8 - Display success message
	 */
	it( 'should display success message on successful request', async () => {
		mockApiFetch.mockResolvedValue( {
			success: true,
			message: 'Indexing request submitted successfully',
		} );

		render( <GSCIntegration /> );

		const button = screen.getByRole( 'button', {
			name: /request indexing/i,
		} );
		fireEvent.click( button );

		await waitFor( () => {
			const successMessage = screen.getByText(
				/indexing request submitted successfully/i
			);
			expect( successMessage ).toBeInTheDocument();
		} );
	} );

	/**
	 * Test: Error message is displayed on failed request
	 * Requirement: 14.8 - Display error message on failure
	 */
	it( 'should display error message on failed request', async () => {
		mockApiFetch.mockRejectedValue( new Error( 'API error' ) );

		render( <GSCIntegration /> );

		const button = screen.getByRole( 'button', {
			name: /request indexing/i,
		} );
		fireEvent.click( button );

		await waitFor( () => {
			const errorMessage = screen.getByText( /api error/i );
			expect( errorMessage ).toBeInTheDocument();
		} );
	} );

	/**
	 * Test: Error is logged to console on failure
	 * Requirement: 14.8 - Log errors to console
	 */
	it( 'should log error to console on failure', async () => {
		const consoleError = jest.spyOn( console, 'error' );
		mockApiFetch.mockRejectedValue( new Error( 'API error' ) );

		render( <GSCIntegration /> );

		const button = screen.getByRole( 'button', {
			name: /request indexing/i,
		} );
		fireEvent.click( button );

		await waitFor( () => {
			expect( consoleError ).toHaveBeenCalledWith(
				'GSC indexing request failed:',
				expect.any( Error )
			);
		} );
	} );

	/**
	 * Test: Help text mentions manage_options capability
	 * Requirement: 14.8 - Require manage_options capability
	 */
	it( 'should display help text mentioning manage_options capability', () => {
		render( <GSCIntegration /> );

		const helpText = screen.getByText(
			/requires manage_options capability/i
		);
		expect( helpText ).toBeInTheDocument();
	} );

	/**
	 * Test: useEntityPropBinding is called with correct meta key
	 * Requirement: 14.7 - Read from _meowseo_gsc_last_submit
	 */
	it( 'should use useEntityPropBinding with _meowseo_gsc_last_submit', () => {
		render( <GSCIntegration /> );

		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_gsc_last_submit'
		);
	} );

	/**
	 * Test: Button is re-enabled after request completes
	 * Requirement: 14.8 - Re-enable button after request
	 */
	it( 'should re-enable button after request completes', async () => {
		mockApiFetch.mockResolvedValue( {
			success: true,
			message: 'Success',
		} );

		render( <GSCIntegration /> );

		const button = screen.getByRole( 'button', {
			name: /request indexing/i,
		} );
		fireEvent.click( button );

		await waitFor( () => {
			expect( button ).not.toBeDisabled();
			expect( button ).toHaveAttribute( 'data-busy', 'false' );
		} );
	} );
} );
