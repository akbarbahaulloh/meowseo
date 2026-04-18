/**
 * SchemaTabContent Component Tests
 *
 * Tests for Schema tab components including:
 * - Schema type selection
 * - Each schema form (Article, FAQPage, HowTo, LocalBusiness, Product)
 * - Repeatable fields (add/remove)
 * - Schema validation
 *
 * Requirements: 13.1, 13.2, 13.3, 13.4, 13.5, 13.6, 13.7, 13.8, 13.9, 13.10
 */

import { render, screen, fireEvent, act } from '@testing-library/react';
import '@testing-library/jest-dom';
import SchemaTabContent from '../SchemaTabContent';
import SchemaTypeSelector from '../schema/SchemaTypeSelector';
import ArticleForm from '../schema/ArticleForm';
import FAQPageForm from '../schema/FAQPageForm';
import HowToForm from '../schema/HowToForm';
import LocalBusinessForm from '../schema/LocalBusinessForm';
import ProductForm from '../schema/ProductForm';

// Mock useEntityPropBinding hook
const mockUseEntityPropBinding = jest.fn( ( metaKey: string ) => {
	return [ '', jest.fn() ];
} );

jest.mock( '../../../hooks/useEntityPropBinding', () => ( {
	useEntityPropBinding: ( metaKey: string ) =>
		mockUseEntityPropBinding( metaKey ),
} ) );

// Mock WordPress components
jest.mock( '@wordpress/components', () => ( {
	SelectControl: ( { label, value, options, onChange }: any ) => (
		<div>
			<label htmlFor="select-control">{ label }</label>
			<select
				id="select-control"
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
			>
				{ options.map( ( opt: any ) => (
					<option key={ opt.value } value={ opt.value }>
						{ opt.label }
					</option>
				) ) }
			</select>
		</div>
	),
	TextControl: ( { label, value, onChange, type, help }: any ) => (
		<div>
			<label htmlFor={ `text-${ label }` }>{ label }</label>
			<input
				id={ `text-${ label }` }
				type={ type || 'text' }
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
			/>
			{ help && <span>{ help }</span> }
		</div>
	),
	TextareaControl: ( { label, value, onChange, rows }: any ) => (
		<div>
			<label htmlFor={ `textarea-${ label }` }>{ label }</label>
			<textarea
				id={ `textarea-${ label }` }
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				rows={ rows }
			/>
		</div>
	),
	Button: ( { children, onClick, variant, isDestructive }: any ) => (
		<button
			onClick={ onClick }
			data-variant={ variant }
			data-destructive={ isDestructive }
		>
			{ children }
		</button>
	),
	Spinner: () => <div>Loading...</div>,
} ) );

// Mock WordPress i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

describe( 'SchemaTypeSelector', () => {
	it( 'should display schema type selector with 5 schema types', () => {
		render( <SchemaTypeSelector /> );

		expect( screen.getByLabelText( 'Schema Type' ) ).toBeInTheDocument();

		const select = screen.getByRole( 'combobox' );
		const options = select.querySelectorAll( 'option' );

		expect( options ).toHaveLength( 6 );
		expect( options[ 0 ] ).toHaveTextContent( 'None' );
		expect( options[ 1 ] ).toHaveTextContent( 'Article' );
		expect( options[ 2 ] ).toHaveTextContent( 'FAQPage' );
		expect( options[ 3 ] ).toHaveTextContent( 'HowTo' );
		expect( options[ 4 ] ).toHaveTextContent( 'LocalBusiness' );
		expect( options[ 5 ] ).toHaveTextContent( 'Product' );
	} );

	it( 'should update schema type on selection', () => {
		const mockSetValue = jest.fn();
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetValue ] );

		render( <SchemaTypeSelector /> );

		const select = screen.getByRole( 'combobox' );
		fireEvent.change( select, { target: { value: 'Article' } } );

		expect( mockSetValue ).toHaveBeenCalledWith( 'Article' );
	} );
} );

describe( 'ArticleForm', () => {
	it( 'should display all Article form fields', () => {
		render( <ArticleForm /> );

		expect( screen.getByLabelText( 'Headline' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Date Published' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Date Modified' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Author' ) ).toBeInTheDocument();
	} );

	it( 'should persist Article form data to postmeta', () => {
		const mockSetValue = jest.fn();
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetValue ] );

		render( <ArticleForm /> );

		const headlineInput = screen.getByLabelText( 'Headline' );
		fireEvent.change( headlineInput, {
			target: { value: 'Test Article' },
		} );

		expect( mockSetValue ).toHaveBeenCalled();
		const callArg = mockSetValue.mock.calls[ 0 ][ 0 ];
		const config = JSON.parse( callArg );
		expect( config.headline ).toBe( 'Test Article' );
	} );
} );

describe( 'FAQPageForm', () => {
	it( 'should display initial question and answer fields', () => {
		render( <FAQPageForm /> );

		expect( screen.getByText( 'Question 1' ) ).toBeInTheDocument();
		expect( screen.getAllByLabelText( 'Question' ) ).toHaveLength( 1 );
		expect( screen.getAllByLabelText( 'Answer' ) ).toHaveLength( 1 );
	} );

	it( 'should add a new question when Add Question is clicked', () => {
		const mockSetValue = jest.fn();
		const initialConfig = JSON.stringify( {
			questions: [ { question: '', answer: '' } ],
		} );
		mockUseEntityPropBinding.mockReturnValue( [
			initialConfig,
			mockSetValue,
		] );

		render( <FAQPageForm /> );

		const addButton = screen.getByText( 'Add Question' );
		fireEvent.click( addButton );

		expect( mockSetValue ).toHaveBeenCalled();
		const callArg = mockSetValue.mock.calls[ 0 ][ 0 ];
		const config = JSON.parse( callArg );
		expect( config.questions ).toHaveLength( 2 );
	} );

	it( 'should remove a question when Remove Question is clicked', () => {
		const mockSetValue = jest.fn();
		const initialConfig = JSON.stringify( {
			questions: [
				{ question: 'Q1', answer: 'A1' },
				{ question: 'Q2', answer: 'A2' },
			],
		} );
		mockUseEntityPropBinding.mockReturnValue( [
			initialConfig,
			mockSetValue,
		] );

		render( <FAQPageForm /> );

		const removeButtons = screen.getAllByText( 'Remove Question' );
		fireEvent.click( removeButtons[ 0 ] );

		expect( mockSetValue ).toHaveBeenCalled();
		const callArg = mockSetValue.mock.calls[ 0 ][ 0 ];
		const config = JSON.parse( callArg );
		expect( config.questions ).toHaveLength( 1 );
	} );

	it( 'should not show remove button when only one question exists', () => {
		const initialConfig = JSON.stringify( {
			questions: [ { question: '', answer: '' } ],
		} );
		mockUseEntityPropBinding.mockReturnValue( [
			initialConfig,
			jest.fn(),
		] );

		render( <FAQPageForm /> );

		expect(
			screen.queryByText( 'Remove Question' )
		).not.toBeInTheDocument();
	} );
} );

describe( 'HowToForm', () => {
	it( 'should display HowTo name and initial step fields', () => {
		render( <HowToForm /> );

		expect( screen.getByLabelText( 'HowTo Name' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Steps' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Step 1' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Step Name' ) ).toBeInTheDocument();
		expect(
			screen.getByLabelText( 'Step Instructions' )
		).toBeInTheDocument();
		expect(
			screen.getByLabelText( 'Step Image URL (optional)' )
		).toBeInTheDocument();
	} );

	it( 'should add a new step when Add Step is clicked', () => {
		const mockSetValue = jest.fn();
		const initialConfig = JSON.stringify( {
			name: '',
			steps: [ { name: '', text: '', image: '' } ],
		} );
		mockUseEntityPropBinding.mockReturnValue( [
			initialConfig,
			mockSetValue,
		] );

		render( <HowToForm /> );

		const addButton = screen.getByText( 'Add Step' );
		fireEvent.click( addButton );

		expect( mockSetValue ).toHaveBeenCalled();
		const callArg = mockSetValue.mock.calls[ 0 ][ 0 ];
		const config = JSON.parse( callArg );
		expect( config.steps ).toHaveLength( 2 );
	} );

	it( 'should remove a step when Remove Step is clicked', () => {
		const mockSetValue = jest.fn();
		const initialConfig = JSON.stringify( {
			name: 'Test HowTo',
			steps: [
				{ name: 'Step 1', text: 'Text 1', image: '' },
				{ name: 'Step 2', text: 'Text 2', image: '' },
			],
		} );
		mockUseEntityPropBinding.mockReturnValue( [
			initialConfig,
			mockSetValue,
		] );

		render( <HowToForm /> );

		const removeButtons = screen.getAllByText( 'Remove Step' );
		fireEvent.click( removeButtons[ 0 ] );

		expect( mockSetValue ).toHaveBeenCalled();
		const callArg = mockSetValue.mock.calls[ 0 ][ 0 ];
		const config = JSON.parse( callArg );
		expect( config.steps ).toHaveLength( 1 );
	} );
} );

describe( 'LocalBusinessForm', () => {
	it( 'should display all LocalBusiness form fields', () => {
		render( <LocalBusinessForm /> );

		expect( screen.getByLabelText( 'Business Name' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Address' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Street Address' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'City' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'State/Region' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Postal Code' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Country' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Telephone' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Opening Hours' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Geo Coordinates (Optional)' )
		).toBeInTheDocument();
		expect( screen.getByLabelText( 'Latitude' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Longitude' ) ).toBeInTheDocument();
	} );

	it( 'should persist LocalBusiness form data to postmeta', () => {
		const mockSetValue = jest.fn();
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetValue ] );

		render( <LocalBusinessForm /> );

		const nameInput = screen.getByLabelText( 'Business Name' );
		fireEvent.change( nameInput, { target: { value: 'Test Business' } } );

		expect( mockSetValue ).toHaveBeenCalled();
		const callArg = mockSetValue.mock.calls[ 0 ][ 0 ];
		const config = JSON.parse( callArg );
		expect( config.name ).toBe( 'Test Business' );
	} );
} );

describe( 'ProductForm', () => {
	it( 'should display all Product form fields', () => {
		render( <ProductForm /> );

		expect( screen.getByLabelText( 'Product Name' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Description' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'SKU' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Pricing' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Price' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Currency' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Availability' ) ).toBeInTheDocument();
	} );

	it( 'should persist Product form data to postmeta', () => {
		const mockSetValue = jest.fn();
		mockUseEntityPropBinding.mockReturnValue( [ '', mockSetValue ] );

		render( <ProductForm /> );

		const nameInput = screen.getByLabelText( 'Product Name' );
		fireEvent.change( nameInput, { target: { value: 'Test Product' } } );

		expect( mockSetValue ).toHaveBeenCalled();
		const callArg = mockSetValue.mock.calls[ 0 ][ 0 ];
		const config = JSON.parse( callArg );
		expect( config.name ).toBe( 'Test Product' );
	} );

	it( 'should display availability options', () => {
		render( <ProductForm /> );

		const availabilitySelect = screen.getByLabelText( 'Availability' );
		const options = availabilitySelect.querySelectorAll( 'option' );

		expect( options ).toHaveLength( 4 );
		expect( options[ 0 ] ).toHaveTextContent( 'In Stock' );
		expect( options[ 1 ] ).toHaveTextContent( 'Out of Stock' );
		expect( options[ 2 ] ).toHaveTextContent( 'Pre-Order' );
		expect( options[ 3 ] ).toHaveTextContent( 'Discontinued' );
	} );
} );

describe( 'SchemaTabContent - Integration', () => {
	it( 'should display help text when no schema type is selected', () => {
		mockUseEntityPropBinding.mockReturnValue( [ '', jest.fn() ] );

		render( <SchemaTabContent /> );

		expect(
			screen.getByText(
				'Select a schema type above to configure structured data for this content.'
			)
		).toBeInTheDocument();
	} );

	it( 'should not show validation error for empty config', () => {
		mockUseEntityPropBinding.mockReturnValue( [ '', jest.fn() ] );

		render( <SchemaTabContent /> );

		expect(
			screen.queryByText(
				'Invalid schema configuration. Please check your inputs.'
			)
		).not.toBeInTheDocument();
	} );

	it( 'should validate invalid JSON configuration', async () => {
		mockUseEntityPropBinding.mockImplementation( ( metaKey: string ) => {
			if ( metaKey === '_meowseo_schema_type' ) {
				return [ 'Article', jest.fn() ];
			}
			if ( metaKey === '_meowseo_schema_config' ) {
				return [ 'invalid json', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		await act( async () => {
			render( <SchemaTabContent /> );
		} );

		expect(
			screen.getByText(
				'Invalid schema configuration. Please check your inputs.'
			)
		).toBeInTheDocument();
	} );
} );
