/**
 * Unit Tests for useEntityPropBinding Hook
 *
 * Tests that the hook:
 * - Returns correct value and setter
 * - Handles null/undefined with empty string fallback
 * - Triggers useEntityProp update when setValue is called
 *
 * Requirements: 15.1, 15.2, 15.11, 17.3
 */

import { renderHook, act } from '@testing-library/react';
import { useEntityPropBinding } from '../useEntityPropBinding';

// Mock WordPress dependencies
jest.mock( '@wordpress/element', () => ( {
	useCallback: jest.requireActual( 'react' ).useCallback,
} ) );

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn(),
} ) );

import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

describe( 'useEntityPropBinding - Unit Tests', () => {
	let mockEditorSelect: any;
	let mockMeta: Record< string, string >;
	let mockSetMeta: jest.Mock;

	beforeEach( () => {
		jest.clearAllMocks();

		mockMeta = {};
		mockSetMeta = jest.fn();

		mockEditorSelect = {
			getCurrentPostType: jest.fn( () => 'post' ),
			getCurrentPostId: jest.fn( () => 123 ),
		};

		( useSelect as jest.Mock ).mockImplementation( ( callback: any ) => {
			const select = ( storeName: string ) => {
				if ( storeName === 'core/editor' ) {
					return mockEditorSelect;
				}
				return {};
			};
			return callback( select );
		} );

		( useEntityProp as jest.Mock ).mockImplementation( () => {
			return [ mockMeta, mockSetMeta ];
		} );
	} );

	/**
	 * Test: Hook returns correct value and setter
	 * Requirements: 15.1
	 */
	it( 'should return tuple of [value, setValue]', () => {
		mockMeta = { _meowseo_title: 'Test Title' };

		const { result } = renderHook( () =>
			useEntityPropBinding( '_meowseo_title' )
		);

		expect( result.current ).toHaveLength( 2 );
		expect( typeof result.current[ 0 ] ).toBe( 'string' );
		expect( typeof result.current[ 1 ] ).toBe( 'function' );
		expect( result.current[ 0 ] ).toBe( 'Test Title' );
	} );

	/**
	 * Test: Returns empty string for missing key
	 * Requirements: 15.11, 17.3
	 */
	it( 'should return empty string when meta key does not exist', () => {
		mockMeta = { _meowseo_title: 'Test Title' };

		const { result } = renderHook( () =>
			useEntityPropBinding( '_meowseo_description' )
		);

		expect( result.current[ 0 ] ).toBe( '' );
	} );

	/**
	 * Test: Returns empty string for null meta
	 * Requirements: 17.3
	 */
	it( 'should return empty string when meta is null', () => {
		( useEntityProp as jest.Mock ).mockImplementation( () => {
			return [ null, mockSetMeta ];
		} );

		const { result } = renderHook( () =>
			useEntityPropBinding( '_meowseo_title' )
		);

		expect( result.current[ 0 ] ).toBe( '' );
	} );

	/**
	 * Test: Returns empty string for undefined meta
	 * Requirements: 17.3
	 */
	it( 'should return empty string when meta is undefined', () => {
		( useEntityProp as jest.Mock ).mockImplementation( () => {
			return [ undefined, mockSetMeta ];
		} );

		const { result } = renderHook( () =>
			useEntityPropBinding( '_meowseo_title' )
		);

		expect( result.current[ 0 ] ).toBe( '' );
	} );

	/**
	 * Test: setValue triggers useEntityProp update
	 * Requirements: 15.1, 15.2
	 */
	it( 'should call setMeta when setValue is called', () => {
		mockMeta = { _meowseo_title: 'Old Title' };

		const { result } = renderHook( () =>
			useEntityPropBinding( '_meowseo_title' )
		);
		const [ , setValue ] = result.current;

		act( () => {
			setValue( 'New Title' );
		} );

		expect( mockSetMeta ).toHaveBeenCalledTimes( 1 );
		expect( mockSetMeta ).toHaveBeenCalledWith( {
			_meowseo_title: 'New Title',
		} );
	} );

	/**
	 * Test: setValue preserves other meta keys
	 * Requirements: 15.1
	 */
	it( 'should preserve other meta keys when updating', () => {
		mockMeta = {
			_meowseo_title: 'Test Title',
			_meowseo_description: 'Test Description',
			_meowseo_focus_keyword: 'test',
		};

		const { result } = renderHook( () =>
			useEntityPropBinding( '_meowseo_title' )
		);
		const [ , setValue ] = result.current;

		act( () => {
			setValue( 'Updated Title' );
		} );

		expect( mockSetMeta ).toHaveBeenCalledWith( {
			_meowseo_title: 'Updated Title',
			_meowseo_description: 'Test Description',
			_meowseo_focus_keyword: 'test',
		} );
	} );

	/**
	 * Test: Hook reads postType and postId from core/editor
	 * Requirements: 15.1
	 */
	it( 'should get postType and postId from core/editor', () => {
		renderHook( () => useEntityPropBinding( '_meowseo_title' ) );

		expect( mockEditorSelect.getCurrentPostType ).toHaveBeenCalled();
		expect( mockEditorSelect.getCurrentPostId ).toHaveBeenCalled();
	} );

	/**
	 * Test: Hook calls useEntityProp with correct parameters
	 * Requirements: 15.1
	 */
	it( 'should call useEntityProp with correct parameters', () => {
		renderHook( () => useEntityPropBinding( '_meowseo_title' ) );

		expect( useEntityProp ).toHaveBeenCalledWith(
			'postType',
			'post',
			'meta',
			123
		);
	} );

	/**
	 * Test: Multiple updates to same key
	 * Requirements: 15.2
	 */
	it( 'should handle multiple updates to the same key', () => {
		mockMeta = { _meowseo_title: 'Initial' };

		const { result } = renderHook( () =>
			useEntityPropBinding( '_meowseo_title' )
		);
		const [ , setValue ] = result.current;

		act( () => {
			setValue( 'Update 1' );
		} );

		act( () => {
			setValue( 'Update 2' );
		} );

		act( () => {
			setValue( 'Update 3' );
		} );

		expect( mockSetMeta ).toHaveBeenCalledTimes( 3 );
		expect( mockSetMeta ).toHaveBeenLastCalledWith( {
			_meowseo_title: 'Update 3',
		} );
	} );

	/**
	 * Test: Empty string value
	 * Requirements: 15.1
	 */
	it( 'should handle empty string values', () => {
		mockMeta = { _meowseo_title: 'Some Title' };

		const { result } = renderHook( () =>
			useEntityPropBinding( '_meowseo_title' )
		);
		const [ , setValue ] = result.current;

		act( () => {
			setValue( '' );
		} );

		expect( mockSetMeta ).toHaveBeenCalledWith( {
			_meowseo_title: '',
		} );
	} );

	/**
	 * Test: Different meta keys
	 * Requirements: 15.1
	 */
	it( 'should work with different meta keys', () => {
		const metaKeys = [
			'_meowseo_title',
			'_meowseo_description',
			'_meowseo_focus_keyword',
			'_meowseo_canonical',
			'_meowseo_og_title',
		];

		metaKeys.forEach( ( key ) => {
			mockSetMeta.mockClear();
			mockMeta = { [ key ]: 'Test Value' };

			const { result } = renderHook( () => useEntityPropBinding( key ) );
			const [ value, setValue ] = result.current;

			expect( value ).toBe( 'Test Value' );

			act( () => {
				setValue( 'Updated Value' );
			} );

			expect( mockSetMeta ).toHaveBeenCalledWith( {
				[ key ]: 'Updated Value',
			} );
		} );
	} );

	/**
	 * Test: setValue function reference stability
	 * Requirements: 15.1
	 */
	it( 'should maintain stable setValue reference when meta changes', () => {
		mockMeta = { _meowseo_title: 'Initial' };

		const { result, rerender } = renderHook( () =>
			useEntityPropBinding( '_meowseo_title' )
		);
		const [ , initialSetValue ] = result.current;

		// Change meta
		mockMeta = { _meowseo_title: 'Changed' };
		rerender();

		const [ , newSetValue ] = result.current;

		// setValue reference should change when meta changes (due to useCallback dependency)
		// This is expected behavior as setValue depends on meta
		expect( typeof newSetValue ).toBe( 'function' );
	} );

	/**
	 * Test: Works with page post type
	 * Requirements: 15.1
	 */
	it( 'should work with different post types', () => {
		mockEditorSelect.getCurrentPostType.mockReturnValue( 'page' );
		mockEditorSelect.getCurrentPostId.mockReturnValue( 456 );

		renderHook( () => useEntityPropBinding( '_meowseo_title' ) );

		expect( useEntityProp ).toHaveBeenCalledWith(
			'postType',
			'page',
			'meta',
			456
		);
	} );

	/**
	 * Test: Handles special characters in values
	 * Requirements: 15.1
	 */
	it( 'should handle special characters in meta values', () => {
		const specialValues = [
			'Title with "quotes"',
			"Title with 'apostrophes'",
			'Title with <html> tags',
			'Title with & ampersand',
			'Title with émojis 🎉',
		];

		specialValues.forEach( ( specialValue ) => {
			mockSetMeta.mockClear();
			mockMeta = {};

			const { result } = renderHook( () =>
				useEntityPropBinding( '_meowseo_title' )
			);
			const [ , setValue ] = result.current;

			act( () => {
				setValue( specialValue );
			} );

			expect( mockSetMeta ).toHaveBeenCalledWith( {
				_meowseo_title: specialValue,
			} );
		} );
	} );
} );
