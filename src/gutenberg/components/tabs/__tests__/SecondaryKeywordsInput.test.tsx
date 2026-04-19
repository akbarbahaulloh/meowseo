/**
 * SecondaryKeywordsInput Component Tests
 *
 * Tests for the secondary keywords input component.
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import SecondaryKeywordsInput from '../SecondaryKeywordsInput';

// Mock WordPress dependencies
jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( 'react' ),
	memo: ( component: any ) => component,
	useState: jest.requireActual( 'react' ).useState,
	useCallback: jest.requireActual( 'react' ).useCallback,
} ) );

jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, onClick, disabled, variant }: any ) => (
		<button onClick={ onClick } disabled={ disabled } data-variant={ variant }>
			{ children }
		</button>
	),
	TextControl: ( { value, onChange, placeholder, onKeyPress }: any ) => (
		<input
			type="text"
			value={ value }
			onChange={ ( e ) => onChange( e.target.value ) }
			placeholder={ placeholder }
			onKeyPress={ onKeyPress }
		/>
	),
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn( () => ( {
		postType: 'post',
		postId: 1,
	} ) ),
	useDispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn( () => [
		{
			_meowseo_focus_keyword: 'primary keyword',
			_meowseo_secondary_keywords: '[]',
		},
		jest.fn(),
	] ),
} ) );

jest.mock( 'react-beautiful-dnd', () => ( {
	DragDropContext: ( { children }: any ) => <div>{ children }</div>,
	Droppable: ( { children }: any ) =>
		children( {
			droppableProps: {},
			innerRef: jest.fn(),
			placeholder: null,
		} ),
	Draggable: ( { children }: any ) =>
		children( {
			draggableProps: {},
			dragHandleProps: {},
			innerRef: jest.fn(),
		} ),
} ) );

describe( 'SecondaryKeywordsInput', () => {
	it( 'renders the component', () => {
		render( <SecondaryKeywordsInput /> );
		expect( screen.getByText( 'Secondary Keywords' ) ).toBeInTheDocument();
	} );

	it( 'displays the description', () => {
		render( <SecondaryKeywordsInput /> );
		expect(
			screen.getByText(
				'Add up to 4 additional keywords to optimize for. Drag to reorder.'
			)
		).toBeInTheDocument();
	} );

	it( 'displays the Add Keyword button', () => {
		render( <SecondaryKeywordsInput /> );
		expect( screen.getByText( 'Add Keyword' ) ).toBeInTheDocument();
	} );

	it( 'displays the keyword count', () => {
		render( <SecondaryKeywordsInput /> );
		expect( screen.getByText( /Keywords:/ ) ).toBeInTheDocument();
	} );

	it( 'has a text input for entering keywords', () => {
		render( <SecondaryKeywordsInput /> );
		const input = screen.getByPlaceholderText( 'Enter a keyword' );
		expect( input ).toBeInTheDocument();
	} );
} );
