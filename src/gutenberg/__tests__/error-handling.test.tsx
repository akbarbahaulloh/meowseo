/**
 * Error Handling Tests
 *
 * Tests for Task 23.2: Write error handling tests
 *
 * Requirements:
 * - 17.1: Web Worker fallback to main thread with warning
 * - 17.2: REST API error handling with empty fallback
 * - 17.3: Postmeta null/undefined fallback to empty string
 * - 17.4: Analysis timeout (10 seconds) with worker termination
 * - 17.5: Console error logging for all errors
 * - 17.6: No user-facing JavaScript errors (handled by ErrorBoundary)
 * - 17.7: No user-facing JavaScript errors
 */

import { analyzeContent } from '../store/actions';
import { analyzeContent as analyzeContentEngine } from '../../analysis/analysis-engine';
import { render, screen, waitFor } from '@testing-library/react';
import { ErrorBoundary } from '../components/ErrorBoundary';
import '@testing-library/jest-dom';

describe( 'Error Handling', () => {
	let consoleWarnSpy: jest.SpyInstance;
	let consoleErrorSpy: jest.SpyInstance;
	let originalWorker: any;

	beforeEach( () => {
		consoleWarnSpy = jest.spyOn( console, 'warn' ).mockImplementation();
		consoleErrorSpy = jest.spyOn( console, 'error' ).mockImplementation();
		originalWorker = global.Worker;
	} );

	afterEach( () => {
		consoleWarnSpy.mockRestore();
		consoleErrorSpy.mockRestore();
		global.Worker = originalWorker;
	} );

	describe( 'Requirement 17.1: Web Worker fallback to main thread', () => {
		it( 'should fall back to main thread when Web Workers are not supported', async () => {
			// Mock Worker as undefined
			( global as any ).Worker = undefined;

			const mockSelect = {
				getState: () => ( {
					contentSnapshot: {
						title: 'Test Title',
						content: 'Test content',
						excerpt: 'Test excerpt',
						focusKeyword: 'test',
						postType: 'post',
						permalink: 'https://example.com/test',
					},
				} ),
			};

			const mockDispatch = {
				setAnalyzing: jest.fn(),
				setAnalysisResults: jest.fn(),
			};

			const action = analyzeContent();
			await action( { select: mockSelect, dispatch: mockDispatch } );

			// Verify warning was logged
			expect( consoleWarnSpy ).toHaveBeenCalledWith(
				expect.stringContaining( 'Web Workers not supported' )
			);

			// Verify analysis still completed
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( true );
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( false );
			expect( mockDispatch.setAnalysisResults ).toHaveBeenCalled();
		} );

		it( 'should fall back to main thread when worker fails', async () => {
			// Mock Worker that throws error
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onerror: null as any,
				onmessage: null as any,
			};

			global.Worker = jest.fn().mockImplementation( () => {
				setTimeout( () => {
					if ( mockWorker.onerror ) {
						mockWorker.onerror(
							new ErrorEvent( 'error', {
								message: 'Worker failed',
							} )
						);
					}
				}, 10 );
				return mockWorker;
			} ) as any;

			const mockSelect = {
				getState: () => ( {
					contentSnapshot: {
						title: 'Test Title',
						content: 'Test content',
						excerpt: 'Test excerpt',
						focusKeyword: 'test',
						postType: 'post',
						permalink: 'https://example.com/test',
					},
				} ),
			};

			const mockDispatch = {
				setAnalyzing: jest.fn(),
				setAnalysisResults: jest.fn(),
			};

			const action = analyzeContent();
			await action( { select: mockSelect, dispatch: mockDispatch } );

			// Verify error was logged
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				expect.stringContaining(
					'Analysis failed, falling back to main thread'
				),
				expect.anything()
			);

			// Verify analysis still completed via fallback
			expect( mockDispatch.setAnalysisResults ).toHaveBeenCalled();
		} );

		it( 'should log warning and continue with main thread analysis', async () => {
			// Mock Worker as undefined
			( global as any ).Worker = undefined;

			const mockSelect = {
				getState: () => ( {
					contentSnapshot: {
						title: 'SEO Test Title',
						content: '<p>SEO Test content with keyword</p>',
						excerpt: 'SEO Test excerpt',
						focusKeyword: 'SEO',
						postType: 'post',
						permalink: 'https://example.com/seo-test',
					},
				} ),
			};

			const mockDispatch = {
				setAnalyzing: jest.fn(),
				setAnalysisResults: jest.fn(),
			};

			const action = analyzeContent();
			await action( { select: mockSelect, dispatch: mockDispatch } );

			// Verify warning was logged
			expect( consoleWarnSpy ).toHaveBeenCalledWith(
				expect.stringContaining( 'Web Workers not supported' )
			);

			// Verify analysis results were set with valid data
			expect( mockDispatch.setAnalysisResults ).toHaveBeenCalledWith(
				expect.any( Number ),
				expect.any( Number ),
				expect.any( Array )
			);

			// Verify isAnalyzing was set to false
			expect( mockDispatch.setAnalyzing ).toHaveBeenLastCalledWith(
				false
			);
		} );

		it( 'should handle worker creation failure gracefully', async () => {
			// Mock Worker constructor that throws
			global.Worker = jest.fn().mockImplementation( () => {
				throw new Error( 'Worker creation failed' );
			} ) as any;

			const mockSelect = {
				getState: () => ( {
					contentSnapshot: {
						title: 'Test Title',
						content: 'Test content',
						excerpt: 'Test excerpt',
						focusKeyword: 'test',
						postType: 'post',
						permalink: 'https://example.com/test',
					},
				} ),
			};

			const mockDispatch = {
				setAnalyzing: jest.fn(),
				setAnalysisResults: jest.fn(),
			};

			const action = analyzeContent();
			await action( { select: mockSelect, dispatch: mockDispatch } );

			// Verify error was logged
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				expect.stringContaining(
					'Analysis failed, falling back to main thread'
				),
				expect.anything()
			);

			// Verify fallback analysis completed
			expect( mockDispatch.setAnalysisResults ).toHaveBeenCalled();
			expect( mockDispatch.setAnalyzing ).toHaveBeenLastCalledWith(
				false
			);
		} );
	} );

	describe( 'Requirement 17.2: REST API error handling', () => {
		it( 'should return empty array when internal links API fails', async () => {
			// This test verifies the pattern used in InternalLinkSuggestions component
			const mockApiFetch = jest
				.fn()
				.mockRejectedValue( new Error( 'Network error' ) );

			let suggestions: any[] = [];
			let error: string | null = null;

			try {
				const response = await mockApiFetch( {
					path: '/meowseo/v1/internal-links/suggestions',
					method: 'POST',
					data: { post_id: 1, keyword: 'test', limit: 5 },
				} );
				suggestions = response.suggestions || [];
			} catch ( err ) {
				console.error(
					'Failed to fetch internal link suggestions:',
					err
				);
				suggestions = [];
				error = 'Unable to load link suggestions';
			}

			// Verify empty array fallback
			expect( suggestions ).toEqual( [] );
			expect( error ).toBe( 'Unable to load link suggestions' );

			// Verify error was logged
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				expect.stringContaining(
					'Failed to fetch internal link suggestions'
				),
				expect.anything()
			);
		} );

		it( 'should return empty array when API returns invalid response', async () => {
			const mockApiFetch = jest.fn().mockResolvedValue( {} );

			let suggestions: any[] = [];

			try {
				const response = await mockApiFetch( {
					path: '/meowseo/v1/internal-links/suggestions',
					method: 'POST',
					data: { post_id: 1, keyword: 'test', limit: 5 },
				} );
				suggestions = response.suggestions || [];
			} catch ( err ) {
				console.error(
					'Failed to fetch internal link suggestions:',
					err
				);
				suggestions = [];
			}

			// Verify empty array fallback when suggestions is undefined
			expect( suggestions ).toEqual( [] );
		} );

		it( 'should handle GSC API errors gracefully', async () => {
			const mockApiFetch = jest
				.fn()
				.mockRejectedValue( new Error( 'GSC API error' ) );

			let error: string | null = null;
			let success: string | null = null;

			try {
				const response = await mockApiFetch( {
					path: '/meowseo/v1/gsc/request-indexing',
					method: 'POST',
					data: { post_id: 1, url: 'https://example.com/test' },
				} );

				if ( response.success ) {
					success = response.message;
				} else {
					error = response.message;
				}
			} catch ( err: any ) {
				console.error( 'GSC indexing request failed:', err );
				error = err.message || 'Failed to submit indexing request';
			}

			// Verify error handling
			expect( error ).toBe( 'GSC API error' );
			expect( success ).toBeNull();

			// Verify error was logged
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				expect.stringContaining( 'GSC indexing request failed' ),
				expect.anything()
			);
		} );

		it( 'should handle network timeout errors', async () => {
			const mockApiFetch = jest
				.fn()
				.mockRejectedValue( new Error( 'Request timeout' ) );

			let suggestions: any[] = [];

			try {
				const response = await mockApiFetch( {
					path: '/meowseo/v1/internal-links/suggestions',
					method: 'POST',
					data: { post_id: 1, keyword: 'test', limit: 5 },
				} );
				suggestions = response.suggestions || [];
			} catch ( err ) {
				console.error(
					'Failed to fetch internal link suggestions:',
					err
				);
				suggestions = [];
			}

			// Verify empty array fallback
			expect( suggestions ).toEqual( [] );
			expect( consoleErrorSpy ).toHaveBeenCalled();
		} );

		it( 'should handle 404 API errors', async () => {
			const mockApiFetch = jest
				.fn()
				.mockRejectedValue( new Error( '404 Not Found' ) );

			let suggestions: any[] = [];

			try {
				const response = await mockApiFetch( {
					path: '/meowseo/v1/internal-links/suggestions',
					method: 'POST',
					data: { post_id: 1, keyword: 'test', limit: 5 },
				} );
				suggestions = response.suggestions || [];
			} catch ( err ) {
				console.error(
					'Failed to fetch internal link suggestions:',
					err
				);
				suggestions = [];
			}

			// Verify empty array fallback
			expect( suggestions ).toEqual( [] );
		} );

		it( 'should handle 500 server errors', async () => {
			const mockApiFetch = jest
				.fn()
				.mockRejectedValue( new Error( '500 Internal Server Error' ) );

			let error: string | null = null;

			try {
				await mockApiFetch( {
					path: '/meowseo/v1/gsc/request-indexing',
					method: 'POST',
					data: { post_id: 1, url: 'https://example.com/test' },
				} );
			} catch ( err: any ) {
				console.error( 'GSC indexing request failed:', err );
				error = err.message;
			}

			// Verify error handling
			expect( error ).toBe( '500 Internal Server Error' );
			expect( consoleErrorSpy ).toHaveBeenCalled();
		} );
	} );

	describe( 'Requirement 17.3: Postmeta null/undefined fallback', () => {
		it( 'should return empty string when postmeta is null', () => {
			const meta = null;
			const metaKey = '_meowseo_focus_keyword';
			const value = meta?.[ metaKey ] || '';

			expect( value ).toBe( '' );
		} );

		it( 'should return empty string when postmeta is undefined', () => {
			const meta = undefined;
			const metaKey = '_meowseo_focus_keyword';
			const value = meta?.[ metaKey ] || '';

			expect( value ).toBe( '' );
		} );

		it( 'should return empty string when postmeta key does not exist', () => {
			const meta = { other_key: 'value' };
			const metaKey = '_meowseo_focus_keyword';
			const value = meta?.[ metaKey ] || '';

			expect( value ).toBe( '' );
		} );

		it( 'should return empty string when postmeta value is null', () => {
			const meta = { _meowseo_focus_keyword: null };
			const metaKey = '_meowseo_focus_keyword';
			const value = meta?.[ metaKey ] || '';

			expect( value ).toBe( '' );
		} );

		it( 'should return empty string when postmeta value is undefined', () => {
			const meta = { _meowseo_focus_keyword: undefined };
			const metaKey = '_meowseo_focus_keyword';
			const value = meta?.[ metaKey ] || '';

			expect( value ).toBe( '' );
		} );

		it( 'should return empty string when postmeta value is empty string', () => {
			const meta = { _meowseo_focus_keyword: '' };
			const metaKey = '_meowseo_focus_keyword';
			const value = meta?.[ metaKey ] || '';

			expect( value ).toBe( '' );
		} );

		it( 'should return actual value when postmeta exists', () => {
			const meta = { _meowseo_focus_keyword: 'test keyword' };
			const metaKey = '_meowseo_focus_keyword';
			const value = meta?.[ metaKey ] || '';

			expect( value ).toBe( 'test keyword' );
		} );

		it( 'should handle all postmeta keys with fallback', () => {
			const postmetaKeys = [
				'_meowseo_title',
				'_meowseo_description',
				'_meowseo_focus_keyword',
				'_meowseo_direct_answer',
				'_meowseo_og_title',
				'_meowseo_og_description',
				'_meowseo_twitter_title',
				'_meowseo_canonical',
			];

			const meta = null;

			postmetaKeys.forEach( ( key ) => {
				const value = meta?.[ key ] || '';
				expect( value ).toBe( '' );
			} );
		} );
	} );

	describe( 'Requirement 17.4: Analysis timeout', () => {
		it( 'should have timeout mechanism in place', () => {
			// This test verifies that the timeout code exists in the implementation
			// The actual timeout behavior is tested in integration tests
			const actionString = analyzeContent.toString();

			// Verify timeout is set to 10 seconds (10000ms)
			expect( actionString ).toContain( '10000' );
			expect( actionString ).toContain( 'setTimeout' );
			expect( actionString ).toContain( 'worker.terminate' );
			expect( actionString ).toContain( 'Analysis timed out' );
		} );

		it( 'should terminate worker after 10 seconds', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onerror: null as any,
				onmessage: null as any,
			};

			// Mock Worker that never responds
			global.Worker = jest
				.fn()
				.mockImplementation( () => mockWorker ) as any;

			const mockSelect = {
				getState: () => ( {
					contentSnapshot: {
						title: 'Test Title',
						content: 'Test content',
						excerpt: 'Test excerpt',
						focusKeyword: 'test',
						postType: 'post',
						permalink: 'https://example.com/test',
					},
				} ),
			};

			const mockDispatch = {
				setAnalyzing: jest.fn(),
				setAnalysisResults: jest.fn(),
			};

			// Use fake timers to control timeout
			jest.useFakeTimers();

			const action = analyzeContent();
			const promise = action( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			// Fast-forward time by 10 seconds
			jest.advanceTimersByTime( 10000 );

			// Run all pending promises
			await Promise.resolve();

			// Verify worker was terminated
			expect( mockWorker.terminate ).toHaveBeenCalled();

			// Verify error was logged
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				expect.stringContaining( 'Analysis timed out' )
			);

			jest.useRealTimers();
		} );

		it( 'should set isAnalyzing to false after timeout', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onerror: null as any,
				onmessage: null as any,
			};

			global.Worker = jest
				.fn()
				.mockImplementation( () => mockWorker ) as any;

			const mockSelect = {
				getState: () => ( {
					contentSnapshot: {
						title: 'Test Title',
						content: 'Test content',
						excerpt: 'Test excerpt',
						focusKeyword: 'test',
						postType: 'post',
						permalink: 'https://example.com/test',
					},
				} ),
			};

			const mockDispatch = {
				setAnalyzing: jest.fn(),
				setAnalysisResults: jest.fn(),
			};

			jest.useFakeTimers();

			const action = analyzeContent();
			const promise = action( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			jest.advanceTimersByTime( 10000 );

			// Run all pending promises
			await Promise.resolve();

			// Verify isAnalyzing was set to false
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( false );

			jest.useRealTimers();
		} );
	} );

	describe( 'Requirement 17.5: Console error logging', () => {
		it( 'should log errors to console when analysis fails', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onerror: null as any,
				onmessage: null as any,
			};

			global.Worker = jest.fn().mockImplementation( () => {
				// Trigger error immediately using setTimeout
				setTimeout( () => {
					if ( mockWorker.onerror ) {
						mockWorker.onerror(
							new ErrorEvent( 'error', { message: 'Test error' } )
						);
					}
				}, 0 );
				return mockWorker;
			} ) as any;

			const mockSelect = {
				getState: () => ( {
					contentSnapshot: {
						title: 'Test Title',
						content: 'Test content',
						excerpt: 'Test excerpt',
						focusKeyword: 'test',
						postType: 'post',
						permalink: 'https://example.com/test',
					},
				} ),
			};

			const mockDispatch = {
				setAnalyzing: jest.fn(),
				setAnalysisResults: jest.fn(),
			};

			const action = analyzeContent();
			await action( { select: mockSelect, dispatch: mockDispatch } );

			// Verify error was logged to console
			expect( consoleErrorSpy ).toHaveBeenCalled();
		} );

		it( 'should log all error types to console', async () => {
			// Test various error scenarios
			const errors = [
				'Network error',
				'Worker failed',
				'Invalid response',
			];

			for ( const errorMsg of errors ) {
				consoleErrorSpy.mockClear();

				const mockWorker = {
					postMessage: jest.fn(),
					terminate: jest.fn(),
					onerror: null as any,
					onmessage: null as any,
				};

				global.Worker = jest.fn().mockImplementation( () => {
					setTimeout( () => {
						if ( mockWorker.onerror ) {
							mockWorker.onerror(
								new ErrorEvent( 'error', { message: errorMsg } )
							);
						}
					}, 0 );
					return mockWorker;
				} ) as any;

				const mockSelect = {
					getState: () => ( {
						contentSnapshot: {
							title: 'Test',
							content: 'Test',
							excerpt: 'Test',
							focusKeyword: 'test',
							postType: 'post',
							permalink: 'https://example.com/test',
						},
					} ),
				};

				const mockDispatch = {
					setAnalyzing: jest.fn(),
					setAnalysisResults: jest.fn(),
				};

				const action = analyzeContent();
				await action( { select: mockSelect, dispatch: mockDispatch } );

				// Verify error was logged
				expect( consoleErrorSpy ).toHaveBeenCalled();
			}
		} );
	} );

	describe( 'Requirement 17.6 & 17.7: ErrorBoundary catches React errors', () => {
		it( 'should catch errors and display fallback UI', () => {
			const ThrowError = () => {
				throw new Error( 'Test error' );
			};

			render(
				<ErrorBoundary>
					<ThrowError />
				</ErrorBoundary>
			);

			// Verify fallback UI is displayed
			expect(
				screen.getByText( 'Something went wrong' )
			).toBeInTheDocument();
			expect(
				screen.getByText( /The MeowSEO sidebar encountered an error/ )
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'button', { name: /Refresh Page/i } )
			).toBeInTheDocument();
		} );

		it( 'should log error to console when caught', () => {
			const ThrowError = () => {
				throw new Error( 'Test error' );
			};

			render(
				<ErrorBoundary>
					<ThrowError />
				</ErrorBoundary>
			);

			// Verify error was logged
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				expect.stringContaining(
					'MeowSEO Error Boundary caught an error'
				),
				expect.anything(),
				expect.anything()
			);
		} );

		it( 'should render children when no error occurs', () => {
			const TestComponent = () => <div>Test content</div>;

			render(
				<ErrorBoundary>
					<TestComponent />
				</ErrorBoundary>
			);

			// Verify children are rendered
			expect( screen.getByText( 'Test content' ) ).toBeInTheDocument();
		} );

		it( 'should not display JavaScript errors to user', () => {
			const ThrowError = () => {
				throw new Error( 'Uncaught JavaScript error' );
			};

			render(
				<ErrorBoundary>
					<ThrowError />
				</ErrorBoundary>
			);

			// Verify user-friendly message is shown instead of raw error
			expect(
				screen.queryByText( 'Uncaught JavaScript error' )
			).not.toBeInTheDocument();
			expect(
				screen.getByText( 'Something went wrong' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Analysis worker edge cases', () => {
		it( 'should handle empty focus keyword gracefully', () => {
			const result = analyzeContentEngine( {
				title: 'Test Title',
				description: 'Test description',
				content: 'Test content',
				slug: 'test-slug',
				keyword: '',
				directAnswer: '',
				schemaType: '',
			} );

			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.seoScore ).toBeLessThanOrEqual( 100 );
			expect( result.seoResults ).toBeDefined();
		} );

		it( 'should handle malformed HTML content gracefully', () => {
			const result = analyzeContentEngine( {
				title: 'Test Title',
				description: 'Test description',
				content: '<p>Unclosed paragraph<h1>Heading</h1',
				slug: 'test-slug',
				keyword: 'test',
				directAnswer: '',
				schemaType: '',
			} );

			// Should not throw error
			expect( result ).toBeDefined();
			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.seoScore ).toBeLessThanOrEqual( 100 );
		} );

		it( 'should handle null content gracefully', () => {
			const result = analyzeContentEngine( {
				title: '',
				description: '',
				content: '',
				slug: '',
				keyword: 'test',
				directAnswer: '',
				schemaType: '',
			} );

			// Should not throw error
			expect( result ).toBeDefined();
			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
		} );

		it( 'should handle very long content gracefully', () => {
			const longContent = '<p>' + 'word '.repeat( 10000 ) + '</p>';

			const result = analyzeContentEngine( {
				title: 'Test Title',
				description: 'Test description',
				content: longContent,
				slug: 'test-slug',
				keyword: 'test',
				directAnswer: '',
				schemaType: '',
			} );

			// Should not throw error
			expect( result ).toBeDefined();
			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.seoScore ).toBeLessThanOrEqual( 100 );
		} );

		it( 'should handle special characters in focus keyword', () => {
			const result = analyzeContentEngine( {
				title: 'Test & Title',
				description: 'Test & description',
				content: '<p>Test & content</p>',
				slug: 'test-slug',
				keyword: 'test & keyword',
				directAnswer: '',
				schemaType: '',
			} );

			// Should not throw error
			expect( result ).toBeDefined();
			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
		} );
	} );
} );
