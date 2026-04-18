/**
 * Property-Based Tests for useContentSync Hook
 *
 * **Property 2: Debounce guarantee**
 * **Validates: Requirements 2.3, 2.4, 16.2**
 *
 * Tests that rapid content changes within 800ms result in only one dispatch.
 */

import * as fc from 'fast-check';
import { renderHook, waitFor } from '@testing-library/react';
import { useContentSync } from '../useContentSync';

// Mock WordPress dependencies
jest.mock( '@wordpress/element', () => ( {
	useEffect: jest.requireActual( 'react' ).useEffect,
} ) );

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

import { useSelect, useDispatch } from '@wordpress/data';

describe( 'useContentSync - Property Tests', () => {
	let mockDispatch: jest.Mock;
	let mockUpdateContentSnapshot: jest.Mock;
	let mockEditorSelect: any;

	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();

		mockUpdateContentSnapshot = jest.fn();
		mockDispatch = jest.fn( () => mockUpdateContentSnapshot );

		( useDispatch as jest.Mock ).mockReturnValue(
			mockUpdateContentSnapshot
		);

		mockEditorSelect = {
			getEditedPostAttribute: jest.fn( ( attr: string ) => {
				const defaults: Record< string, string > = {
					title: 'Test Title',
					content: 'Test Content',
					excerpt: 'Test Excerpt',
				};
				return defaults[ attr ] || '';
			} ),
			getCurrentPostType: jest.fn( () => 'post' ),
			getPermalink: jest.fn( () => 'https://example.com/test' ),
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
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	/**
	 * Property 2: Debounce Guarantee
	 *
	 * For any sequence of content changes within 800ms,
	 * only one updateContentSnapshot should be dispatched.
	 */
	it( 'should dispatch only once for rapid changes within 800ms', () => {
		fc.assert(
			fc.property(
				// Generate a sequence of 2-10 content changes
				fc.array(
					fc.record( {
						title: fc.string( { minLength: 1, maxLength: 100 } ),
						content: fc.string( { minLength: 1, maxLength: 500 } ),
						excerpt: fc.string( { minLength: 0, maxLength: 200 } ),
					} ),
					{ minLength: 2, maxLength: 10 }
				),
				// Generate delays between changes (all less than 800ms)
				fc.array( fc.integer( { min: 10, max: 700 } ), {
					minLength: 1,
					maxLength: 9,
				} ),
				( contentChanges, delays ) => {
					// Reset mocks for each property test iteration
					mockUpdateContentSnapshot.mockClear();
					jest.clearAllTimers();

					// Render the hook initially
					const { rerender } = renderHook( () => useContentSync() );

					// Simulate rapid content changes
					contentChanges.forEach( ( change, index ) => {
						// Update the mock to return new content
						mockEditorSelect.getEditedPostAttribute.mockImplementation(
							( attr: string ) => {
								const data: Record< string, string > = {
									title: change.title,
									content: change.content,
									excerpt: change.excerpt,
								};
								return data[ attr ] || '';
							}
						);

						// Trigger re-render
						rerender();

						// Advance time by the delay (but less than 800ms)
						if ( delays[ index ] ) {
							jest.advanceTimersByTime( delays[ index ] );
						}
					} );

					// At this point, no dispatch should have happened yet
					// because we haven't waited the full 800ms
					const dispatchCountBeforeDebounce =
						mockUpdateContentSnapshot.mock.calls.length;

					// Now advance past the 800ms debounce
					jest.advanceTimersByTime( 800 );

					// Should have dispatched exactly once
					const dispatchCountAfterDebounce =
						mockUpdateContentSnapshot.mock.calls.length;

					// Verify: only one dispatch after the debounce period
					return (
						dispatchCountAfterDebounce ===
						dispatchCountBeforeDebounce + 1
					);
				}
			),
			{
				numRuns: 50, // Run 50 random test cases
				verbose: true,
			}
		);
	} );

	/**
	 * Property 2b: Multiple Debounce Cycles
	 *
	 * If we wait more than 800ms between changes,
	 * each change should result in a separate dispatch.
	 */
	it( 'should dispatch separately for changes with >800ms gaps', () => {
		fc.assert(
			fc.property(
				// Generate 2-5 content changes
				fc.array(
					fc.record( {
						title: fc.string( { minLength: 1, maxLength: 100 } ),
						content: fc.string( { minLength: 1, maxLength: 500 } ),
					} ),
					{ minLength: 2, maxLength: 5 }
				),
				( contentChanges ) => {
					// Reset mocks
					mockUpdateContentSnapshot.mockClear();
					jest.clearAllTimers();

					const { rerender } = renderHook( () => useContentSync() );

					let expectedDispatches = 0;

					contentChanges.forEach( ( change ) => {
						// Update mock
						mockEditorSelect.getEditedPostAttribute.mockImplementation(
							( attr: string ) => {
								const data: Record< string, string > = {
									title: change.title,
									content: change.content,
									excerpt: '',
								};
								return data[ attr ] || '';
							}
						);

						// Trigger re-render
						rerender();

						// Wait for debounce to complete
						jest.advanceTimersByTime( 800 );
						expectedDispatches++;

						// Verify dispatch count matches expected
						const actualDispatches =
							mockUpdateContentSnapshot.mock.calls.length;
						if ( actualDispatches !== expectedDispatches ) {
							return false;
						}
					} );

					return true;
				}
			),
			{
				numRuns: 30,
				verbose: true,
			}
		);
	} );

	/**
	 * Property 2c: Timer Reset on New Changes
	 *
	 * Each new change within 800ms should reset the timer.
	 */
	it( 'should reset timer on each new change within 800ms', () => {
		fc.assert(
			fc.property(
				fc.integer( { min: 3, max: 8 } ), // Number of changes
				fc.integer( { min: 100, max: 700 } ), // Delay between changes
				( numChanges, delay ) => {
					mockUpdateContentSnapshot.mockClear();
					jest.clearAllTimers();

					const { rerender } = renderHook( () => useContentSync() );

					// Make multiple changes, each separated by 'delay' ms
					for ( let i = 0; i < numChanges; i++ ) {
						mockEditorSelect.getEditedPostAttribute.mockImplementation(
							( attr: string ) => {
								if ( attr === 'title' ) {
									return `Title ${ i }`;
								}
								if ( attr === 'content' ) {
									return `Content ${ i }`;
								}
								return '';
							}
						);

						rerender();
						jest.advanceTimersByTime( delay );
					}

					// At this point, no dispatch should have occurred
					const dispatchCountDuringChanges =
						mockUpdateContentSnapshot.mock.calls.length;

					// Now wait for the full 800ms
					jest.advanceTimersByTime( 800 );

					// Should have exactly one dispatch
					const finalDispatchCount =
						mockUpdateContentSnapshot.mock.calls.length;

					return (
						dispatchCountDuringChanges === 0 &&
						finalDispatchCount === 1
					);
				}
			),
			{
				numRuns: 40,
				verbose: true,
			}
		);
	} );
} );
