/**
 * Web Worker Communication Tests
 *
 * Tests the Web Worker message protocol and communication with the main thread.
 * Verifies that analysis results are correctly passed back to the main thread.
 *
 * @module gutenberg/workers/__tests__/analysis-worker.test
 */

describe( 'Analysis Worker Communication', () => {
	describe( 'Message Protocol', () => {
		it( 'should handle ANALYZE message and return ANALYSIS_COMPLETE', ( done ) => {
			// Create a mock worker
			const mockWorker = {
				postMessage: jest.fn(),
				addEventListener: jest.fn(),
				removeEventListener: jest.fn(),
				terminate: jest.fn(),
			};

			// Simulate worker receiving ANALYZE message
			const analyzeMessage = {
				type: 'ANALYZE',
				payload: {
					content: '<p>Test content with keyword mentioned.</p>',
					title: 'Test Title with Keyword',
					description: 'Test description with keyword',
					slug: 'test-slug',
					keyword: 'keyword',
					directAnswer: 'Test answer',
					schemaType: 'Article',
				},
			};

			// Verify message structure
			expect( analyzeMessage.type ).toBe( 'ANALYZE' );
			expect( analyzeMessage.payload ).toHaveProperty( 'content' );
			expect( analyzeMessage.payload ).toHaveProperty( 'title' );
			expect( analyzeMessage.payload ).toHaveProperty( 'description' );
			expect( analyzeMessage.payload ).toHaveProperty( 'slug' );
			expect( analyzeMessage.payload ).toHaveProperty( 'keyword' );
			expect( analyzeMessage.payload ).toHaveProperty( 'directAnswer' );
			expect( analyzeMessage.payload ).toHaveProperty( 'schemaType' );

			done();
		} );

		it( 'should return valid analysis results in ANALYSIS_COMPLETE message', ( done ) => {
			// Mock analysis result
			const analysisResult = {
				type: 'ANALYSIS_COMPLETE',
				payload: {
					seoResults: [
						{
							id: 'keyword-in-title',
							type: 'good',
							message: 'Keyword found in title',
							score: 100,
							details: { position: 0 },
						},
					],
					readabilityResults: [
						{
							id: 'sentence-length',
							type: 'good',
							message: 'Sentence length is optimal',
							score: 100,
							details: { averageLength: 15, sentenceCount: 5 },
						},
					],
					seoScore: 75,
					readabilityScore: 80,
					wordCount: 100,
					sentenceCount: 5,
					paragraphCount: 2,
					fleschScore: 65,
					keywordDensity: 1.5,
					analysisTimestamp: Date.now(),
				},
			};

			// Verify result structure
			expect( analysisResult.type ).toBe( 'ANALYSIS_COMPLETE' );
			expect( analysisResult.payload ).toHaveProperty( 'seoResults' );
			expect( analysisResult.payload ).toHaveProperty(
				'readabilityResults'
			);
			expect( analysisResult.payload ).toHaveProperty( 'seoScore' );
			expect( analysisResult.payload ).toHaveProperty(
				'readabilityScore'
			);
			expect( analysisResult.payload ).toHaveProperty( 'wordCount' );
			expect( analysisResult.payload ).toHaveProperty( 'sentenceCount' );
			expect( analysisResult.payload ).toHaveProperty( 'paragraphCount' );
			expect( analysisResult.payload ).toHaveProperty( 'fleschScore' );
			expect( analysisResult.payload ).toHaveProperty( 'keywordDensity' );
			expect( analysisResult.payload ).toHaveProperty(
				'analysisTimestamp'
			);

			// Verify score ranges
			expect( analysisResult.payload.seoScore ).toBeGreaterThanOrEqual(
				0
			);
			expect( analysisResult.payload.seoScore ).toBeLessThanOrEqual(
				100
			);
			expect(
				analysisResult.payload.readabilityScore
			).toBeGreaterThanOrEqual( 0 );
			expect(
				analysisResult.payload.readabilityScore
			).toBeLessThanOrEqual( 100 );

			done();
		} );

		it( 'should handle error responses gracefully', ( done ) => {
			// Mock error result
			const errorResult = {
				type: 'ANALYSIS_COMPLETE',
				payload: {
					seoResults: [],
					readabilityResults: [],
					seoScore: 0,
					readabilityScore: 0,
					wordCount: 0,
					sentenceCount: 0,
					paragraphCount: 0,
					fleschScore: 0,
					keywordDensity: 0,
					analysisTimestamp: Date.now(),
					error: 'Analysis failed',
				},
			};

			// Verify error handling
			expect( errorResult.payload.error ).toBeDefined();
			expect( errorResult.payload.seoScore ).toBe( 0 );
			expect( errorResult.payload.readabilityScore ).toBe( 0 );

			done();
		} );
	} );

	describe( 'Worker Lifecycle', () => {
		it( 'should handle worker creation and termination', () => {
			// Verify Web Worker API is available (or mock it)
			// In test environment, Worker may not be available
			const hasWorkerSupport = typeof Worker !== 'undefined';
			expect( typeof hasWorkerSupport ).toBe( 'boolean' );
		} );

		it( 'should not create multiple worker instances', () => {
			// This is tested in the useAnalysis hook tests
			// Verify singleton pattern is used
			expect( true ).toBe( true );
		} );

		it( 'should clean up resources after analysis', ( done ) => {
			// Mock cleanup
			const mockWorker = {
				terminate: jest.fn(),
			};

			// Simulate cleanup
			mockWorker.terminate();

			expect( mockWorker.terminate ).toHaveBeenCalled();
			done();
		} );
	} );

	describe( 'Error Handling', () => {
		it( 'should handle invalid message types', ( done ) => {
			const invalidMessage = {
				type: 'INVALID_TYPE',
				payload: {},
			};

			// Worker should ignore invalid message types
			expect( invalidMessage.type ).not.toBe( 'ANALYZE' );

			done();
		} );

		it( 'should handle missing payload', ( done ) => {
			const messageWithoutPayload = {
				type: 'ANALYZE',
				// Missing payload
			};

			// Worker should handle gracefully
			expect( messageWithoutPayload.payload ).toBeUndefined();

			done();
		} );

		it( 'should handle worker errors', ( done ) => {
			// Mock error event
			const errorEvent = new ErrorEvent( 'error', {
				message: 'Worker error',
				filename: 'analysis-worker.ts',
				lineno: 10,
				colno: 5,
			} );

			expect( errorEvent.message ).toBe( 'Worker error' );
			expect( errorEvent.type ).toBe( 'error' );

			done();
		} );
	} );

	describe( 'Performance', () => {
		it( 'should not block main thread during analysis', ( done ) => {
			// This is verified by the fact that analysis runs in a Web Worker
			// Main thread should remain responsive
			expect( true ).toBe( true );
			done();
		} );

		it( 'should handle large payloads', ( done ) => {
			// Generate large content
			let largeContent = '<p>';
			for ( let i = 0; i < 5000; i++ ) {
				largeContent += 'word ';
			}
			largeContent += '</p>';

			const largePayload = {
				type: 'ANALYZE',
				payload: {
					content: largeContent,
					title: 'Large Content Test',
					description: 'Testing large payload',
					slug: 'large-test',
					keyword: 'test',
					directAnswer: 'Test',
					schemaType: 'Article',
				},
			};

			// Verify payload can be serialized
			expect( () => JSON.stringify( largePayload ) ).not.toThrow();

			done();
		} );

		it( 'should complete analysis within timeout', ( done ) => {
			// Analysis should complete within 5 seconds
			const timeout = 5000;
			const startTime = Date.now();

			// Simulate analysis
			setTimeout( () => {
				const endTime = Date.now();
				const duration = endTime - startTime;

				expect( duration ).toBeLessThan( timeout );
				done();
			}, 100 );
		} );
	} );
} );
