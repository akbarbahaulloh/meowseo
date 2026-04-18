/**
 * FocusKeywordInput Component Tests
 *
 * Unit tests for the FocusKeywordInput component.
 *
 * Requirements: 9.1, 9.2, 9.3, 9.4
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import FocusKeywordInput from '../FocusKeywordInput';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';

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
	TextControl: ( { label, value, onChange, help, placeholder }: any ) => (
		<div>
			<label htmlFor="focus-keyword">{ label }</label>
			<input
				id="focus-keyword"
				type="text"
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				placeholder={ placeholder }
			/>
			{ help && <p>{ help }</p> }
		</div>
	),
} ) );

// Mock the useEntityPropBinding hook
jest.mock( '../../../hooks/useEntityPropBinding' );

const mockUseEntityPropBinding = useEntityPropBinding as jest.MockedFunction<
	typeof useEntityPropBinding
>;

describe( 'FocusKeywordInput', () => {
	let mockSetFocusKeyword: jest.Mock;

	beforeEach( () => {
		mockSetFocusKeyword = jest.fn();
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetFocusKeyword ] );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	/**
	 * Test: Focus keyword input field is displayed
	 * Requirement: 9.1 - Display focus keyword input field
	 */
	it( 'should display focus keyword input field', () => {
		render( <FocusKeywordInput /> );

		const input = screen.getByLabelText( /focus keyword/i );
		expect( input ).toBeInTheDocument();
	} );

	/**
	 * Test: Input displays help text
	 * Requirement: 9.1 - Display help text
	 */
	it( 'should display help text', () => {
		render( <FocusKeywordInput /> );

		const helpText = screen.getByText( /enter the main keyword/i );
		expect( helpText ).toBeInTheDocument();
	} );

	/**
	 * Test: useEntityPropBinding is called with correct meta key
	 * Requirement: 9.2, 9.3 - Use Entity_Prop for _meowseo_focus_keyword
	 */
	it( 'should use useEntityPropBinding with _meowseo_focus_keyword', () => {
		render( <FocusKeywordInput /> );

		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_focus_keyword'
		);
	} );

	/**
	 * Test: Input displays current focus keyword value
	 * Requirement: 9.5 - Display previously saved focus keyword
	 */
	it( 'should display current focus keyword value', () => {
		mockUseEntityPropBinding.mockReturnValue( [
			'wordpress seo',
			mockSetFocusKeyword,
		] );

		render( <FocusKeywordInput /> );

		const input = screen.getByLabelText(
			/focus keyword/i
		) as HTMLInputElement;
		expect( input.value ).toBe( 'wordpress seo' );
	} );

	/**
	 * Test: Changing input calls setValue
	 * Requirement: 9.4 - Trigger auto-save on change
	 */
	it( 'should call setValue when input changes', () => {
		render( <FocusKeywordInput /> );

		const input = screen.getByLabelText( /focus keyword/i );
		fireEvent.change( input, { target: { value: 'seo tips' } } );

		expect( mockSetFocusKeyword ).toHaveBeenCalledWith( 'seo tips' );
	} );

	/**
	 * Test: Empty value is handled correctly
	 * Requirement: 9.5 - Handle empty focus keyword
	 */
	it( 'should handle empty focus keyword', () => {
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetFocusKeyword ] );

		render( <FocusKeywordInput /> );

		const input = screen.getByLabelText(
			/focus keyword/i
		) as HTMLInputElement;
		expect( input.value ).toBe( '' );
	} );

	/**
	 * Test: Input has placeholder text
	 * Requirement: 9.1 - Display input with placeholder
	 */
	it( 'should display placeholder text', () => {
		render( <FocusKeywordInput /> );

		const input = screen.getByPlaceholderText( /e\.g\., wordpress seo/i );
		expect( input ).toBeInTheDocument();
	} );
} );
