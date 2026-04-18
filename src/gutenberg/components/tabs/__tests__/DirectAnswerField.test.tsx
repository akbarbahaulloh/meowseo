/**
 * DirectAnswerField Component Tests
 *
 * Unit tests for the DirectAnswerField component.
 *
 * Requirements: 15.6
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import DirectAnswerField from '../DirectAnswerField';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
	isRTL: () => false,
} ) );

// Mock @wordpress/components
jest.mock( '@wordpress/components', () => ( {
	TextareaControl: ( {
		label,
		value,
		onChange,
		help,
		placeholder,
		rows,
	}: any ) => (
		<div>
			<label htmlFor="direct-answer">{ label }</label>
			<textarea
				id="direct-answer"
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				placeholder={ placeholder }
				rows={ rows }
			/>
			{ help && <p>{ help }</p> }
		</div>
	),
} ) );

// Mock @wordpress/core-data
jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn(),
} ) );

// Mock the useEntityPropBinding hook
jest.mock( '../../../hooks/useEntityPropBinding' );

const mockUseEntityPropBinding = useEntityPropBinding as jest.MockedFunction<
	typeof useEntityPropBinding
>;

describe( 'DirectAnswerField', () => {
	let mockSetDirectAnswer: jest.Mock;

	beforeEach( () => {
		mockSetDirectAnswer = jest.fn();
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetDirectAnswer ] );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	/**
	 * Test: Direct answer field is displayed
	 * Requirement: 15.6 - Display TextareaControl with label
	 */
	it( 'should display direct answer textarea field', () => {
		render( <DirectAnswerField /> );

		const textarea = screen.getByLabelText( /direct answer/i );
		expect( textarea ).toBeInTheDocument();
		expect( textarea.tagName ).toBe( 'TEXTAREA' );
	} );

	/**
	 * Test: useEntityPropBinding is called with correct meta key
	 * Requirement: 15.6 - Use useEntityPropBinding for _meowseo_direct_answer
	 */
	it( 'should use useEntityPropBinding with _meowseo_direct_answer', () => {
		render( <DirectAnswerField /> );

		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_direct_answer'
		);
	} );

	/**
	 * Test: Textarea displays current direct answer value
	 * Requirement: 15.6 - Display persisted value
	 */
	it( 'should display current direct answer value', () => {
		const testAnswer =
			'WordPress SEO is the practice of optimizing your website.';
		mockUseEntityPropBinding.mockReturnValue( [
			testAnswer,
			mockSetDirectAnswer,
		] );

		render( <DirectAnswerField /> );

		const textarea = screen.getByLabelText(
			/direct answer/i
		) as HTMLTextAreaElement;
		expect( textarea.value ).toBe( testAnswer );
	} );

	/**
	 * Test: Changing textarea calls setValue
	 * Requirement: 15.6 - Persist value to postmeta automatically
	 */
	it( 'should call setValue when textarea changes', () => {
		render( <DirectAnswerField /> );

		const textarea = screen.getByLabelText( /direct answer/i );
		const newValue = 'SEO helps improve search rankings.';
		fireEvent.change( textarea, { target: { value: newValue } } );

		expect( mockSetDirectAnswer ).toHaveBeenCalledWith( newValue );
	} );

	/**
	 * Test: Empty value is handled correctly
	 * Requirement: 15.6 - Handle empty direct answer
	 */
	it( 'should handle empty direct answer', () => {
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetDirectAnswer ] );

		render( <DirectAnswerField /> );

		const textarea = screen.getByLabelText(
			/direct answer/i
		) as HTMLTextAreaElement;
		expect( textarea.value ).toBe( '' );
	} );

	/**
	 * Test: Textarea has help text
	 * Requirement: 15.6 - Display help text for user guidance
	 */
	it( 'should display help text', () => {
		render( <DirectAnswerField /> );

		const helpText = screen.getByText( /provide a concise answer/i );
		expect( helpText ).toBeInTheDocument();
	} );

	/**
	 * Test: Textarea has placeholder text
	 * Requirement: 15.6 - Display placeholder for user guidance
	 */
	it( 'should display placeholder text', () => {
		render( <DirectAnswerField /> );

		const textarea = screen.getByPlaceholderText(
			/wordpress seo is the practice/i
		);
		expect( textarea ).toBeInTheDocument();
	} );

	/**
	 * Test: Textarea has correct number of rows
	 * Requirement: 15.6 - Display textarea with appropriate size
	 */
	it( 'should have 4 rows', () => {
		render( <DirectAnswerField /> );

		const textarea = screen.getByLabelText(
			/direct answer/i
		) as HTMLTextAreaElement;
		expect( textarea.rows ).toBe( 4 );
	} );
} );
