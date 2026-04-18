/**
 * SerpPreview Component Tests
 *
 * Unit tests for the SerpPreview component.
 *
 * Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7
 */

import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import { act } from 'react-dom/test-utils';
import SerpPreview from '../SerpPreview';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useSelect } from '@wordpress/data';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

// Mock @wordpress/components
jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, onClick, variant }: any ) => (
		<button onClick={ onClick } data-variant={ variant }>
			{ children }
		</button>
	),
	ButtonGroup: ( { children }: any ) => <div>{ children }</div>,
} ) );

// Mock @wordpress/data
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	createSelector: jest.fn( ( selector ) => selector ),
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
const mockUseSelect = useSelect as jest.MockedFunction< typeof useSelect >;

describe( 'SerpPreview', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		mockUseEntityPropBinding.mockImplementation( ( key: string ) => {
			if ( key === '_meowseo_title' ) {
				return [ 'Test SEO Title', jest.fn() ];
			}
			if ( key === '_meowseo_description' ) {
				return [ 'Test meta description for SEO', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		mockUseSelect.mockImplementation( ( selector: any ) => {
			return selector( ( storeName: string ) => {
				if ( storeName === 'core/editor' ) {
					return {
						getPermalink: () => 'https://example.com/test-post',
						getEditedPostAttribute: () => 'Fallback Title',
					};
				}
				return {};
			} );
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
		jest.useRealTimers();
	} );

	/**
	 * Test: Display SEO title, meta description, and URL
	 * Requirement: 10.1 - Display SEO title, meta description, and URL
	 */
	it( 'should display SEO title, description, and URL', async () => {
		render( <SerpPreview /> );

		// Wait for debounce
		act( () => {
			jest.advanceTimersByTime( 800 );
		} );

		await waitFor( () => {
			expect( screen.getByText( 'Test SEO Title' ) ).toBeInTheDocument();
			expect(
				screen.getByText( 'Test meta description for SEO' )
			).toBeInTheDocument();
			expect(
				screen.getByText( 'example.com/test-post' )
			).toBeInTheDocument();
		} );
	} );

	/**
	 * Test: Support desktop and mobile preview modes
	 * Requirement: 10.2 - Support desktop and mobile preview modes
	 */
	it( 'should support desktop and mobile preview modes', () => {
		render( <SerpPreview /> );

		const desktopButton = screen.getByText( 'Desktop' );
		const mobileButton = screen.getByText( 'Mobile' );

		expect( desktopButton ).toBeInTheDocument();
		expect( mobileButton ).toBeInTheDocument();
	} );

	/**
	 * Test: Switch between desktop and mobile modes
	 * Requirement: 10.7 - Update display format when preview mode changes
	 */
	it( 'should switch between desktop and mobile modes', () => {
		const { container } = render( <SerpPreview /> );

		// Default is desktop
		expect(
			container.querySelector( '.meowseo-serp-preview-desktop' )
		).toBeInTheDocument();

		// Switch to mobile
		const mobileButton = screen.getByText( 'Mobile' );
		fireEvent.click( mobileButton );

		expect(
			container.querySelector( '.meowseo-serp-preview-mobile' )
		).toBeInTheDocument();
		expect(
			container.querySelector( '.meowseo-serp-preview-desktop' )
		).not.toBeInTheDocument();
	} );

	/**
	 * Test: Implement 800ms debounce for updates
	 * Requirement: 10.3 - Implement 800ms debounce for updates
	 */
	it( 'should debounce updates by 800ms', async () => {
		const { rerender } = render( <SerpPreview /> );

		// Change title
		mockUseEntityPropBinding.mockImplementation( ( key: string ) => {
			if ( key === '_meowseo_title' ) {
				return [ 'New Title', jest.fn() ];
			}
			if ( key === '_meowseo_description' ) {
				return [ 'Test description', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		rerender( <SerpPreview /> );

		// Should not update immediately
		expect( screen.queryByText( 'New Title' ) ).not.toBeInTheDocument();

		// Advance timers by 800ms
		act( () => {
			jest.advanceTimersByTime( 800 );
		} );

		// Should update after debounce
		await waitFor( () => {
			expect( screen.getByText( 'New Title' ) ).toBeInTheDocument();
		} );
	} );

	/**
	 * Test: Truncate title at 60 chars for desktop
	 * Requirement: 10.4 - Truncate title at 60 chars (desktop)
	 */
	it( 'should truncate title at 60 characters for desktop', async () => {
		const longTitle =
			'This is a very long SEO title that exceeds sixty characters and should be truncated';

		mockUseEntityPropBinding.mockImplementation( ( key: string ) => {
			if ( key === '_meowseo_title' ) {
				return [ longTitle, jest.fn() ];
			}
			if ( key === '_meowseo_description' ) {
				return [ 'Description', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		render( <SerpPreview /> );

		act( () => {
			jest.advanceTimersByTime( 800 );
		} );

		await waitFor( () => {
			const titleElement = screen.getByText(
				/This is a very long SEO title/
			);
			expect( titleElement.textContent ).toHaveLength( 63 ); // 60 chars + '...'
			expect( titleElement.textContent ).toContain( '...' );
		} );
	} );

	/**
	 * Test: Truncate description at 160 chars for desktop
	 * Requirement: 10.5 - Truncate description at 160 chars (desktop)
	 */
	it( 'should truncate description at 160 characters for desktop', async () => {
		const longDescription =
			'This is a very long meta description that exceeds one hundred sixty characters and should be truncated to fit within the search engine results page preview limits for desktop view';

		mockUseEntityPropBinding.mockImplementation( ( key: string ) => {
			if ( key === '_meowseo_title' ) {
				return [ 'Title', jest.fn() ];
			}
			if ( key === '_meowseo_description' ) {
				return [ longDescription, jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		render( <SerpPreview /> );

		act( () => {
			jest.advanceTimersByTime( 800 );
		} );

		await waitFor( () => {
			const descElement = screen.getByText(
				/This is a very long meta description/
			);
			expect( descElement.textContent ).toHaveLength( 163 ); // 160 chars + '...'
			expect( descElement.textContent ).toContain( '...' );
		} );
	} );

	/**
	 * Test: Use post title as fallback when SEO title is empty
	 * Requirement: 10.6 - Update preview when SEO title changes
	 */
	it( 'should use post title as fallback when SEO title is empty', async () => {
		mockUseEntityPropBinding.mockImplementation( ( key: string ) => {
			if ( key === '_meowseo_title' ) {
				return [ '', jest.fn() ];
			}
			if ( key === '_meowseo_description' ) {
				return [ 'Description', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		render( <SerpPreview /> );

		act( () => {
			jest.advanceTimersByTime( 800 );
		} );

		await waitFor( () => {
			expect( screen.getByText( 'Fallback Title' ) ).toBeInTheDocument();
		} );
	} );

	/**
	 * Test: Display placeholder when no title is set
	 * Requirement: 10.1 - Display appropriate message when no title
	 */
	it( 'should display placeholder when no title is set', async () => {
		mockUseEntityPropBinding.mockImplementation( ( key: string ) => {
			if ( key === '_meowseo_title' ) {
				return [ '', jest.fn() ];
			}
			if ( key === '_meowseo_description' ) {
				return [ '', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		mockUseSelect.mockImplementation( ( selector: any ) => {
			return selector( ( storeName: string ) => {
				if ( storeName === 'core/editor' ) {
					return {
						getPermalink: () => 'https://example.com/test',
						getEditedPostAttribute: () => '',
					};
				}
				return {};
			} );
		} );

		render( <SerpPreview /> );

		act( () => {
			jest.advanceTimersByTime( 800 );
		} );

		await waitFor( () => {
			expect( screen.getByText( '(No title set)' ) ).toBeInTheDocument();
		} );
	} );

	/**
	 * Test: Remove protocol from URL display
	 * Requirement: 10.1 - Display URL without protocol
	 */
	it( 'should remove protocol from URL display', async () => {
		render( <SerpPreview /> );

		act( () => {
			jest.advanceTimersByTime( 800 );
		} );

		await waitFor( () => {
			expect(
				screen.getByText( 'example.com/test-post' )
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'https://example.com/test-post' )
			).not.toBeInTheDocument();
		} );
	} );
} );
