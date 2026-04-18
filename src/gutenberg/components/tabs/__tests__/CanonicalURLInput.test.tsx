/**
 * CanonicalURLInput Component Tests
 *
 * Unit tests for the CanonicalURLInput component.
 *
 * Requirements: 14.4, 14.5, 14.6
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

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import CanonicalURLInput from '../CanonicalURLInput';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useSelect } from '@wordpress/data';

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
	TextControl: ( {
		label,
		value,
		onChange,
		help,
		placeholder,
		type,
	}: any ) => (
		<div>
			<label htmlFor="canonical-url">{ label }</label>
			<input
				id="canonical-url"
				type={ type || 'text' }
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				placeholder={ placeholder }
			/>
			{ help && <p>{ help }</p> }
		</div>
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

// Mock the useEntityPropBinding hook
jest.mock( '../../../hooks/useEntityPropBinding' );

const mockUseEntityPropBinding = useEntityPropBinding as jest.MockedFunction<
	typeof useEntityPropBinding
>;
const mockUseSelect = useSelect as jest.MockedFunction< typeof useSelect >;

describe( 'CanonicalURLInput', () => {
	let mockSetCanonical: jest.Mock;

	beforeEach( () => {
		mockSetCanonical = jest.fn();
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetCanonical ] );

		// Mock permalink from core/editor
		mockUseSelect.mockReturnValue( 'https://example.com/sample-post' );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	/**
	 * Test: Canonical URL input field is displayed
	 * Requirement: 14.4 - Display canonical URL input field
	 */
	it( 'should display canonical URL input field', () => {
		render( <CanonicalURLInput /> );

		const input = screen.getByLabelText( /custom canonical url/i );
		expect( input ).toBeInTheDocument();
	} );

	/**
	 * Test: useEntityPropBinding is called with correct meta key
	 * Requirement: 14.5 - Use Entity_Prop for _meowseo_canonical
	 */
	it( 'should use useEntityPropBinding with _meowseo_canonical', () => {
		render( <CanonicalURLInput /> );

		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_canonical'
		);
	} );

	/**
	 * Test: Input displays current canonical URL value
	 * Requirement: 14.5 - Display persisted canonical URL
	 */
	it( 'should display current canonical URL value', () => {
		mockUseEntityPropBinding.mockReturnValue( [
			'https://example.com/custom',
			mockSetCanonical,
		] );

		render( <CanonicalURLInput /> );

		const input = screen.getByLabelText(
			/custom canonical url/i
		) as HTMLInputElement;
		expect( input.value ).toBe( 'https://example.com/custom' );
	} );

	/**
	 * Test: Changing input calls setValue
	 * Requirement: 14.5 - Persist canonical URL to postmeta
	 */
	it( 'should call setValue when input changes', () => {
		render( <CanonicalURLInput /> );

		const input = screen.getByLabelText( /custom canonical url/i );
		fireEvent.change( input, {
			target: { value: 'https://example.com/new-url' },
		} );

		expect( mockSetCanonical ).toHaveBeenCalledWith(
			'https://example.com/new-url'
		);
	} );

	/**
	 * Test: Resolved canonical URL is displayed
	 * Requirement: 14.6 - Display resolved canonical URL (read-only)
	 */
	it( 'should display resolved canonical URL', () => {
		render( <CanonicalURLInput /> );

		const resolvedLabel = screen.getByText( /resolved canonical url/i );
		expect( resolvedLabel ).toBeInTheDocument();

		const resolvedValue = screen.getByText(
			'https://example.com/sample-post'
		);
		expect( resolvedValue ).toBeInTheDocument();
	} );

	/**
	 * Test: Resolved canonical URL shows custom value when set
	 * Requirement: 14.6 - Display custom canonical URL when set
	 */
	it( 'should display custom canonical URL as resolved when set', () => {
		mockUseEntityPropBinding.mockReturnValue( [
			'https://example.com/custom',
			mockSetCanonical,
		] );
		mockUseSelect.mockReturnValue( 'https://example.com/custom' );

		render( <CanonicalURLInput /> );

		const resolvedValue = screen.getByText( 'https://example.com/custom' );
		expect( resolvedValue ).toBeInTheDocument();
	} );

	/**
	 * Test: Resolved canonical URL shows permalink when custom is empty
	 * Requirement: 14.6 - Display default permalink when custom is empty
	 */
	it( 'should display permalink as resolved when custom is empty', () => {
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetCanonical ] );
		mockUseSelect.mockReturnValue( 'https://example.com/sample-post' );

		render( <CanonicalURLInput /> );

		const resolvedValue = screen.getByText(
			'https://example.com/sample-post'
		);
		expect( resolvedValue ).toBeInTheDocument();
	} );

	/**
	 * Test: Input has type="url"
	 * Requirement: 14.4 - Display URL input with proper type
	 */
	it( 'should have type="url" for input field', () => {
		render( <CanonicalURLInput /> );

		const input = screen.getByLabelText(
			/custom canonical url/i
		) as HTMLInputElement;
		expect( input.type ).toBe( 'url' );
	} );

	/**
	 * Test: Input has placeholder text
	 * Requirement: 14.4 - Display input with placeholder
	 */
	it( 'should display placeholder text', () => {
		render( <CanonicalURLInput /> );

		const input = screen.getByPlaceholderText(
			/https:\/\/example\.com\/custom-url/i
		);
		expect( input ).toBeInTheDocument();
	} );

	/**
	 * Test: Help text is displayed
	 * Requirement: 14.4 - Display help text
	 */
	it( 'should display help text for both fields', () => {
		render( <CanonicalURLInput /> );

		const inputHelp = screen.getByText(
			/override the default canonical url/i
		);
		const resolvedHelp = screen.getByText(
			/this is the canonical url that will be output/i
		);

		expect( inputHelp ).toBeInTheDocument();
		expect( resolvedHelp ).toBeInTheDocument();
	} );
} );
