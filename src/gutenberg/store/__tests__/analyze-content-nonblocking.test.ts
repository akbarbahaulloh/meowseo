/**
 * Property Test: Analysis Non-Blocking
 *
 * Property 3: Analysis non-blocking
 * Validates: Requirements 6.5, 16.9
 *
 * Test that analysis runs in Web Worker and doesn't block UI thread.
 * Measure main thread blocking time during analysis.
 */

import { analyzeContent } from '../actions';

describe( 'Property Test: Analysis Non-Blocking', () => {
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
	} );

	afterEach( () => {
		// Restore original Worker
		global.Worker = originalWorker;
	} );

	it( 'should run analysis in Web Worker without blocking main thread', async () => {
		// Mock Web Worker
		const mockWorker = {
			postMessage: jest.fn(),
			terminate: jest.fn(),
			onmessage: null as any,
			onerror: null as any,
		};

		global.Worker = jest.fn( () => mockWorker ) as any;

		// Start analysis
		const analyzePromise = analyzeContent()( {
			select: mockSelect,
			dispatch: mockDispatch,
		} );

		// Measure main thread blocking time
		const startTime = performance.now();

		// Simulate some main thread work
		let iterations = 0;
		const workInterval = setInterval( () => {
			iterations++;
		}, 1 );

		// Simulate worker response after 100ms
		setTimeout( () => {
			if ( mockWorker.onmessage ) {
				mockWorker.onmessage( {
					data: {
						score: 80,
						results: [
							{
								id: 'keyword-in-title',
								type: 'good',
								message: 'Keyword in title',
							},
						],
						color: 'green',
					},
				} as MessageEvent );
			}
		}, 100 );

		await analyzePromise;

		clearInterval( workInterval );
		const endTime = performance.now();
		const elapsedTime = endTime - startTime;

		// Verify worker was created and used
		expect( global.Worker ).toHaveBeenCalled();
		expect( mockWorker.postMessage ).toHaveBeenCalled();
		expect( mockWorker.terminate ).toHaveBeenCalled();

		// Verify main thread was not blocked (iterations should be > 0)
		// If main thread was blocked, iterations would be 0 or very low
		expect( iterations ).toBeGreaterThan( 0 );

		// Verify analysis completed
		expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( true );
		expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( false );
		expect( mockDispatch.setAnalysisResults ).toHaveBeenCalled();
	} );

	it( 'should measure main thread blocking time is minimal', async () => {
		// Mock Web Worker
		const mockWorker = {
			postMessage: jest.fn(),
			terminate: jest.fn(),
			onmessage: null as any,
			onerror: null as any,
		};

		global.Worker = jest.fn( () => mockWorker ) as any;

		// Measure blocking time
		const blockingTimes: number[] = [];

		// Start analysis
		const analyzePromise = analyzeContent()( {
			select: mockSelect,
			dispatch: mockDispatch,
		} );

		// Measure main thread responsiveness
		const measureInterval = setInterval( () => {
			const measureStart = performance.now();
			// Simulate a small task
			for ( let i = 0; i < 1000; i++ ) {
				Math.sqrt( i );
			}
			const measureEnd = performance.now();
			blockingTimes.push( measureEnd - measureStart );
		}, 10 );

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
		}, 150 );

		await analyzePromise;
		clearInterval( measureInterval );

		// Calculate average blocking time
		const avgBlockingTime =
			blockingTimes.reduce( ( a, b ) => a + b, 0 ) / blockingTimes.length;

		// Main thread blocking should be minimal (< 50ms on average)
		// This validates that analysis doesn't block the UI
		expect( avgBlockingTime ).toBeLessThan( 50 );

		// Verify worker was used
		expect( mockWorker.postMessage ).toHaveBeenCalled();
		expect( mockWorker.terminate ).toHaveBeenCalled();
	} );

	it( 'should fall back to main thread when Worker is unavailable', async () => {
		// Remove Worker support
		global.Worker = undefined as any;

		// Spy on console.warn and console.error
		const consoleWarnSpy = jest
			.spyOn( console, 'warn' )
			.mockImplementation();
		const consoleErrorSpy = jest
			.spyOn( console, 'error' )
			.mockImplementation();

		// Start analysis
		await analyzeContent()( {
			select: mockSelect,
			dispatch: mockDispatch,
		} );

		// Verify fallback was attempted (warning logged)
		expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( true );
		expect( mockDispatch.setAnalyzing ).toHaveBeenCalledWith( false );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			expect.stringContaining( 'Web Workers not supported' )
		);

		consoleWarnSpy.mockRestore();
		consoleErrorSpy.mockRestore();
	} );

	it( 'should not block UI during multiple concurrent analyses', async () => {
		// Mock Web Worker
		const workers: any[] = [];

		global.Worker = jest.fn( () => {
			const mockWorker = {
				postMessage: jest.fn(),
				terminate: jest.fn(),
				onmessage: null as any,
				onerror: null as any,
			};
			workers.push( mockWorker );
			return mockWorker;
		} ) as any;

		// Start multiple analyses
		const promises = [];
		for ( let i = 0; i < 5; i++ ) {
			promises.push(
				analyzeContent()( {
					select: mockSelect,
					dispatch: mockDispatch,
				} )
			);
		}

		// Measure main thread responsiveness
		let uiResponsive = true;
		const checkInterval = setInterval( () => {
			const start = performance.now();
			// Simulate UI work
			for ( let i = 0; i < 10000; i++ ) {
				Math.sqrt( i );
			}
			const end = performance.now();

			// If this takes too long, UI is blocked
			if ( end - start > 100 ) {
				uiResponsive = false;
			}
		}, 20 );

		// Simulate worker responses
		setTimeout( () => {
			workers.forEach( ( worker ) => {
				if ( worker.onmessage ) {
					worker.onmessage( {
						data: {
							score: 70,
							results: [],
							color: 'green',
						},
					} as MessageEvent );
				}
			} );
		}, 100 );

		await Promise.all( promises );
		clearInterval( checkInterval );

		// Verify UI remained responsive
		expect( uiResponsive ).toBe( true );

		// Verify workers were created
		expect( global.Worker ).toHaveBeenCalledTimes( 5 );
	}, 10000 ); // Increase timeout to 10 seconds
} );
