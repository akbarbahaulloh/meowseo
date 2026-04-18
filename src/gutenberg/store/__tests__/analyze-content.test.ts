/**
 * Unit Tests: analyzeContent Action
 *
 * Tests worker creation, message posting, result handling,
 * error handling, fallback, and isAnalyzing state transitions.
 *
 * Requirements: 5.2, 5.3, 5.4, 5.5, 5.6, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6
 */

import { analyzeContent } from '../actions';

describe( 'analyzeContent Action', () => {
	let mockSelect: any;
	let mockDispatch: any;
	let originalWorker: any;

	beforeEach( () => {
		// Mock store select and dispatch
		mockSelect = {
			getState: jest.fn( () => ( {
				contentSnapshot: {
					title: 'Test Title with Keyword',
					content:
						'<p>Test content with keyword in first paragraph</p><h2>Heading with keyword</h2>',
					excerpt: 'Test excerpt with keyword',
					focusKeyword: 'keyword',
					postType: 'post',
					permalink: 'https://example.com/test-keyword',
				},
			} ) ),
		};

		mockDispatch = {
			setAnalyzing: jest.fn(),
			setAnalysisResults: jest.fn(),
		};

		// Save original Worker
		originalWorker = global.Worker;

		// Clear all mocks
		jest.clearAllMocks();
	} );

	afterEach( () => {
		// Restore original Worker
		global.Worker = originalWorker;
	} );

	describe( 'Worker Creation and Message Posting', () => {
		it( 'should create a Web Worker instance', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			// Simulate worker response
			setTimeout( () => {
				if ( mockWorker.onmessage ) {
					mockWorker.onmessage( {
						data: {
							score: 80,
							results: [],
							color: 'green',
						},
					} as MessageEvent );
				}
			}, 10 );

			await analyzePromise;

			expect( global.Worker ).toHaveBeenCalled();
		} );

		it( 'should post contentSnapshot to worker', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			// Simulate worker response
			setTimeout( () => {
				if ( mockWorker.onmessage ) {
					mockWorker.onmessage( {
						data: {
							score: 60,
							results: [],
							color: 'orange',
						},
					} as MessageEvent );
				}
			}, 10 );

			await analyzePromise;

			expect( mockWorker.postMessage ).toHaveBeenCalledWith( {
				title: 'Test Title with Keyword',
				description: 'Test excerpt with keyword',
				content:
					'<p>Test content with keyword in first paragraph</p><h2>Heading with keyword</h2>',
				slug: 'test-keyword',
				focusKeyword: 'keyword',
			} );
		} );

		it( 'should extract slug from permalink correctly', async () => {
			mockSelect.getState = jest.fn( () => ( {
				contentSnapshot: {
					title: 'Test',
					content: 'Test',
					excerpt: 'Test',
					focusKeyword: 'test',
					postType: 'post',
					permalink:
						'https://example.com/category/subcategory/my-post-slug/',
				},
			} ) );

			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			setTimeout( () => {
				if ( mockWorker.onmessage ) {
					mockWorker.onmessage( {
						data: { score: 50, results: [], color: 'orange' },
					} as MessageEvent );
				}
			}, 10 );

			await analyzePromise;

			expect( mockWorker.postMessage ).toHaveBeenCalledWith(
				expect.objectContaining( {
					slug: 'my-post-slug',
				} )
			);
		} );
	} );

	describe( 'Result Handling', () => {
		it( 'should update store with analysis results', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			const analysisResults = [
				{
					id: 'keyword-in-title',
					type: 'good' as const,
					message: 'Keyword in title',
				},
				{
					id: 'keyword-in-description',
					type: 'good' as const,
					message: 'Keyword in description',
				},
			];

			setTimeout( () => {
				if ( mockWorker.onmessage ) {
					mockWorker.onmessage( {
						data: {
							score: 85,
							results: analysisResults,
							color: 'green',
						},
					} as MessageEvent );
				}
			}, 10 );

			await analyzePromise;

			expect( mockDispatch.setAnalysisResults ).toHaveBeenCalledWith(
				analysisResults,
				[],
				85,
				85,
				0,
				0,
				0,
				0,
				0,
				expect.any( Number )
			);
		} );

		it( 'should terminate worker after receiving results', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			setTimeout( () => {
				if ( mockWorker.onmessage ) {
					mockWorker.onmessage( {
						data: {
							score: 70,
							results: [],
							color: 'green',
						},
					} as MessageEvent );
				}
			}, 10 );

			await analyzePromise;

			expect( mockWorker.terminate ).toHaveBeenCalled();
		} );
	} );

	describe( 'Error Handling and Fallback', () => {
		it( 'should fall back to main thread when Worker is not supported', async () => {
			// Remove Worker support
			global.Worker = undefined as any;

			// Spy on console.warn and console.error
			const consoleWarnSpy = jest
				.spyOn( console, 'warn' )
				.mockImplementation();
			const consoleErrorSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation();

			await analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			// Should attempt fallback (warning logged)
			expect( consoleWarnSpy ).toHaveBeenCalledWith(
				expect.stringContaining( 'Web Workers not supported' )
			);

			// Should set isAnalyzing to false even if fallback fails
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( false );

			consoleWarnSpy.mockRestore();
			consoleErrorSpy.mockRestore();
		} );

		it( 'should handle worker errors and fall back to main thread', async () => {
			const consoleErrorSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation();

			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			// Simulate worker error
			setTimeout( () => {
				if ( mockWorker.onerror ) {
					mockWorker.onerror(
						new ErrorEvent( 'error', {
							message: 'Worker failed',
						} )
					);
				}
			}, 10 );

			await analyzePromise;

			// Should still complete and set isAnalyzing to false
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( false );
			expect( consoleErrorSpy ).toHaveBeenCalled();

			consoleErrorSpy.mockRestore();
		} );

		it( 'should handle timeout after 10 seconds', async () => {
			jest.useFakeTimers();

			const consoleErrorSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation();

			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			// Fast-forward time by 10 seconds
			jest.advanceTimersByTime( 10000 );

			await Promise.resolve(); // Allow promises to resolve

			// Worker should be terminated
			expect( mockWorker.terminate ).toHaveBeenCalled();

			// isAnalyzing should be set to false
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( false );

			// Should log timeout error
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				expect.stringContaining( 'Analysis timed out' )
			);

			consoleErrorSpy.mockRestore();
			jest.useRealTimers();
		} );

		it( 'should log warning when falling back to main thread', async () => {
			const consoleSpy = jest
				.spyOn( console, 'warn' )
				.mockImplementation();

			// Remove Worker support
			global.Worker = undefined as any;

			await analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			expect( consoleSpy ).toHaveBeenCalledWith(
				expect.stringContaining( 'Web Workers not supported' )
			);

			consoleSpy.mockRestore();
		} );

		it( 'should log error when worker fails', async () => {
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation();

			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			// Simulate worker error
			setTimeout( () => {
				if ( mockWorker.onerror ) {
					mockWorker.onerror(
						new ErrorEvent( 'error', {
							message: 'Worker crashed',
						} )
					);
				}
			}, 10 );

			await analyzePromise;

			expect( consoleSpy ).toHaveBeenCalledWith(
				expect.stringContaining( 'Analysis failed' ),
				expect.anything()
			);

			consoleSpy.mockRestore();
		} );
	} );

	describe( 'isAnalyzing State Transitions', () => {
		it( 'should set isAnalyzing to true before analysis', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			// Check immediately after calling
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( true );

			// Complete the analysis
			setTimeout( () => {
				if ( mockWorker.onmessage ) {
					mockWorker.onmessage( {
						data: { score: 50, results: [], color: 'orange' },
					} as MessageEvent );
				}
			}, 10 );

			await analyzePromise;
		} );

		it( 'should set isAnalyzing to false after successful analysis', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			setTimeout( () => {
				if ( mockWorker.onmessage ) {
					mockWorker.onmessage( {
						data: { score: 90, results: [], color: 'green' },
					} as MessageEvent );
				}
			}, 10 );

			await analyzePromise;

			// Should be called twice: true at start, false at end
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( true );
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( false );
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledTimes( 2 );
		} );

		it( 'should set isAnalyzing to false after failed analysis', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			// Simulate worker error
			setTimeout( () => {
				if ( mockWorker.onerror ) {
					mockWorker.onerror( new ErrorEvent( 'error' ) );
				}
			}, 10 );

			await analyzePromise;

			// Should still set to false even on error
			expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( false );
		} );

		it( 'should set isAnalyzing to false in finally block', async () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			// Simulate worker response
			setTimeout( () => {
				if ( mockWorker.onmessage ) {
					mockWorker.onmessage( {
						data: { score: 75, results: [], color: 'green' },
					} as MessageEvent );
				}
			}, 10 );

			await analyzePromise;

			// Verify the call order: true first, then false
			const calls = mockDispatch.setAnalyzing.mock.calls;
			expect( calls[ 0 ][ 0 ] ).toBe( true );
			expect( calls[ calls.length - 1 ][ 0 ] ).toBe( false );
		} );
	} );

	describe( 'Edge Cases', () => {
		it( 'should handle empty focus keyword', async () => {
			mockSelect.getState = jest.fn( () => ( {
				contentSnapshot: {
					title: 'Test Title',
					content: '<p>Test content</p>',
					excerpt: 'Test excerpt',
					focusKeyword: '',
					postType: 'post',
					permalink: 'https://example.com/test',
				},
			} ) );

			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			setTimeout( () => {
				if ( mockWorker.onmessage ) {
					mockWorker.onmessage( {
						data: { score: 0, results: [], color: 'red' },
					} as MessageEvent );
				}
			}, 10 );

			await analyzePromise;

			expect( mockWorker.postMessage ).toHaveBeenCalledWith(
				expect.objectContaining( {
					focusKeyword: '',
				} )
			);
		} );

		it( 'should handle invalid permalink', async () => {
			mockSelect.getState = jest.fn( () => ( {
				contentSnapshot: {
					title: 'Test',
					content: 'Test',
					excerpt: 'Test',
					focusKeyword: 'test',
					postType: 'post',
					permalink: 'not-a-valid-url',
				},
			} ) );

			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};

			global.Worker = jest.fn( () => mockWorker ) as any;

			const analyzePromise = analyzeContent()( {
				select: mockSelect,
				dispatch: mockDispatch,
			} );

			setTimeout( () => {
				if ( mockWorker.onmessage ) {
					mockWorker.onmessage( {
						data: { score: 20, results: [], color: 'red' },
					} as MessageEvent );
				}
			}, 10 );

			await analyzePromise;

			// Should handle gracefully with empty slug
			expect( mockWorker.postMessage ).toHaveBeenCalledWith(
				expect.objectContaining( {
					slug: '',
				} )
			);
		} );
	} );
} );
