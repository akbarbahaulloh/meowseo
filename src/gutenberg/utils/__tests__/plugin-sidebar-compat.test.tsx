/**
 * Unit Tests: PluginSidebar Compatibility Wrapper
 *
 * Tests PluginSidebar import selection based on WordPress version.
 *
 * Requirements: 1.3, 1.4, 1.5
 */

import React from 'react';

// Mock the version detection module before importing the component
jest.mock( '../version-detection', () => ( {
	isWP66Plus: false, // Default to WP < 6.6 for tests
} ) );

describe( 'PluginSidebar Compatibility Wrapper', () => {
	beforeEach( () => {
		// Clear module cache to allow re-importing with different mocks
		jest.resetModules();
	} );

	describe( 'WordPress 6.6+ (import from @wordpress/editor)', () => {
		it( 'should import PluginSidebar from @wordpress/editor when isWP66Plus is true', () => {
			// Mock version detection to return true
			jest.doMock( '../version-detection', () => ( {
				isWP66Plus: true,
			} ) );

			// Mock @wordpress/editor
			const mockPluginSidebar = jest.fn( () => null );
			jest.doMock(
				'@wordpress/editor',
				() => ( {
					PluginSidebar: mockPluginSidebar,
				} ),
				{ virtual: true }
			);

			// Import the component
			const { PluginSidebar } = require( '../plugin-sidebar-compat' );

			// Verify it's the correct component
			expect( PluginSidebar ).toBe( mockPluginSidebar );
		} );

		it( 'should fall back to @wordpress/edit-post if @wordpress/editor import fails', () => {
			// Mock version detection to return true
			jest.doMock( '../version-detection', () => ( {
				isWP66Plus: true,
			} ) );

			// Mock @wordpress/editor to throw error
			jest.doMock(
				'@wordpress/editor',
				() => {
					throw new Error( 'Module not found' );
				},
				{ virtual: true }
			);

			// Mock @wordpress/edit-post as fallback
			const mockPluginSidebar = jest.fn( () => null );
			jest.doMock(
				'@wordpress/edit-post',
				() => ( {
					PluginSidebar: mockPluginSidebar,
				} ),
				{ virtual: true }
			);

			// Suppress console.error for this test
			const consoleErrorSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation();

			// Import the component
			const { PluginSidebar } = require( '../plugin-sidebar-compat' );

			// Verify it fell back to edit-post
			expect( PluginSidebar ).toBe( mockPluginSidebar );
			expect( consoleErrorSpy ).toHaveBeenCalled();

			consoleErrorSpy.mockRestore();
		} );
	} );

	describe( 'WordPress < 6.6 (import from @wordpress/edit-post)', () => {
		it( 'should import PluginSidebar from @wordpress/edit-post when isWP66Plus is false', () => {
			// Mock version detection to return false
			jest.doMock( '../version-detection', () => ( {
				isWP66Plus: false,
			} ) );

			// Mock @wordpress/edit-post
			const mockPluginSidebar = jest.fn( () => null );
			jest.doMock(
				'@wordpress/edit-post',
				() => ( {
					PluginSidebar: mockPluginSidebar,
				} ),
				{ virtual: true }
			);

			// Import the component
			const { PluginSidebar } = require( '../plugin-sidebar-compat' );

			// Verify it's the correct component
			expect( PluginSidebar ).toBe( mockPluginSidebar );
		} );

		it( 'should provide a fallback component if @wordpress/edit-post import fails', () => {
			// Mock version detection to return false
			jest.doMock( '../version-detection', () => ( {
				isWP66Plus: false,
			} ) );

			// Mock @wordpress/edit-post to throw error
			jest.doMock(
				'@wordpress/edit-post',
				() => {
					throw new Error( 'Module not found' );
				},
				{ virtual: true }
			);

			// Suppress console.error for this test
			const consoleErrorSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation();

			// Import the component
			const { PluginSidebar } = require( '../plugin-sidebar-compat' );

			// Verify it's a function (fallback component)
			expect( typeof PluginSidebar ).toBe( 'function' );

			// Verify fallback component returns null
			const result = PluginSidebar( {
				name: 'test',
				title: 'Test',
			} );
			expect( result ).toBeNull();

			expect( consoleErrorSpy ).toHaveBeenCalled();

			consoleErrorSpy.mockRestore();
		} );
	} );

	describe( 'isWP66Plus export', () => {
		it( 'should export isWP66Plus flag', () => {
			// Mock version detection
			jest.doMock( '../version-detection', () => ( {
				isWP66Plus: true,
			} ) );

			// Mock @wordpress/editor
			const mockPluginSidebar = jest.fn( () => null );
			jest.doMock(
				'@wordpress/editor',
				() => ( {
					PluginSidebar: mockPluginSidebar,
				} ),
				{ virtual: true }
			);

			// Import the module
			const { isWP66Plus } = require( '../plugin-sidebar-compat' );

			// Verify the flag is exported
			expect( typeof isWP66Plus ).toBe( 'boolean' );
		} );
	} );

	describe( 'PluginSidebar component interface', () => {
		it( 'should accept standard PluginSidebar props', () => {
			// Mock version detection
			jest.doMock( '../version-detection', () => ( {
				isWP66Plus: false,
			} ) );

			// Mock @wordpress/edit-post with a component that validates props
			const mockPluginSidebar = jest.fn( ( props ) => {
				// Validate props structure
				expect( props ).toHaveProperty( 'name' );
				expect( props ).toHaveProperty( 'title' );
				return null;
			} );

			jest.doMock(
				'@wordpress/edit-post',
				() => ( {
					PluginSidebar: mockPluginSidebar,
				} ),
				{ virtual: true }
			);

			// Import the component
			const { PluginSidebar } = require( '../plugin-sidebar-compat' );

			// Call with standard props
			PluginSidebar( {
				name: 'meowseo-sidebar',
				title: 'MeowSEO',
				icon: 'chart-line',
			} );

			// Verify the mock was called
			expect( mockPluginSidebar ).toHaveBeenCalledWith(
				expect.objectContaining( {
					name: 'meowseo-sidebar',
					title: 'MeowSEO',
					icon: 'chart-line',
				} )
			);
		} );
	} );
} );
