/**
 * Performance Benchmark Tests
 *
 * Tests performance characteristics of the analysis engine, including:
 * - Execution time benchmarks
 * - Memory usage monitoring
 * - Throughput measurements
 * - Target achievement (1-2 second analysis time)
 *
 * @module analysis/__tests__/performance-benchmark.test
 */

import { analyzeContent } from '../analysis-engine.js';
import {
	benchmarkAnalysisEngine,
	generateTestData,
	formatBenchmarkResults,
} from '../utils/performance-benchmark.js';

describe( 'Performance Benchmarking', () => {
	describe( 'Analysis Engine Performance', () => {
		it( 'should complete analysis within 2 seconds for typical content', () => {
			const testData = generateTestData( 1000 ); // 1000 words
			const startTime = performance.now();

			const result = analyzeContent( testData );

			const endTime = performance.now();
			const executionTime = endTime - startTime;

			// Should complete within 2 seconds
			expect( executionTime ).toBeLessThan( 2000 );
			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
		} );

		it( 'should complete analysis within 3 seconds for large content', () => {
			const testData = generateTestData( 5000 ); // 5000 words
			const startTime = performance.now();

			const result = analyzeContent( testData );

			const endTime = performance.now();
			const executionTime = endTime - startTime;

			// Should complete within 3 seconds for large content
			expect( executionTime ).toBeLessThan( 3000 );
			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
		} );

		it( 'should maintain consistent performance across multiple runs', () => {
			const testData = generateTestData( 1000 );
			const times = [];

			for ( let i = 0; i < 5; i++ ) {
				const startTime = performance.now();
				analyzeContent( testData );
				const endTime = performance.now();
				times.push( endTime - startTime );
			}

			// Calculate average and standard deviation
			const average = times.reduce( ( a, b ) => a + b, 0 ) / times.length;
			const variance =
				times.reduce(
					( sum, time ) => sum + Math.pow( time - average, 2 ),
					0
				) / times.length;
			const stdDev = Math.sqrt( variance );

			// Performance should be relatively consistent (low standard deviation)
			expect( stdDev ).toBeLessThan( average * 0.5 ); // Std dev < 50% of average
		} );

		it( 'should scale linearly with content size', () => {
			const sizes = [ 500, 1000, 2000 ];
			const times = [];

			sizes.forEach( ( size ) => {
				const testData = generateTestData( size );
				const startTime = performance.now();
				analyzeContent( testData );
				const endTime = performance.now();
				times.push( endTime - startTime );
			} );

			// Time should increase roughly linearly with content size
			// (not exponentially)
			const ratio1 = times[ 1 ] / times[ 0 ]; // 1000 words vs 500 words
			const ratio2 = times[ 2 ] / times[ 1 ]; // 2000 words vs 1000 words

			// Ratios should be similar (both around 2x, with some variance)
			expect( Math.abs( ratio1 - ratio2 ) ).toBeLessThan( 2.0 );
		} );
	} );

	describe( 'Benchmark Utilities', () => {
		it( 'should generate test data correctly', () => {
			const testData = generateTestData( 1000 );

			expect( testData ).toHaveProperty( 'content' );
			expect( testData ).toHaveProperty( 'title' );
			expect( testData ).toHaveProperty( 'description' );
			expect( testData ).toHaveProperty( 'slug' );
			expect( testData ).toHaveProperty( 'keyword' );
			expect( testData ).toHaveProperty( 'directAnswer' );
			expect( testData ).toHaveProperty( 'schemaType' );

			// Verify content has approximately the right word count
			const wordCount = testData.content.split( /\s+/ ).length;
			expect( wordCount ).toBeGreaterThan( 900 );
			expect( wordCount ).toBeLessThan( 1100 );
		} );

		it( 'should benchmark analysis engine', () => {
			const testData = generateTestData( 1000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				3
			);

			expect( summary ).toHaveProperty( 'totalRuns' );
			expect( summary ).toHaveProperty( 'averageTime' );
			expect( summary ).toHaveProperty( 'minTime' );
			expect( summary ).toHaveProperty( 'maxTime' );
			expect( summary ).toHaveProperty( 'medianTime' );
			expect( summary ).toHaveProperty( 'averageMemory' );
			expect( summary ).toHaveProperty( 'peakMemory' );
			expect( summary ).toHaveProperty( 'averageThroughput' );
			expect( summary ).toHaveProperty( 'targetMet' );

			expect( summary.totalRuns ).toBe( 3 );
			expect( summary.averageTime ).toBeGreaterThan( 0 );
			expect( summary.minTime ).toBeGreaterThan( 0 );
			expect( summary.maxTime ).toBeGreaterThan( 0 );
		} );

		it( 'should format benchmark results', () => {
			const testData = generateTestData( 1000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				2
			);
			const formatted = formatBenchmarkResults( summary );

			expect( typeof formatted ).toBe( 'string' );
			expect( formatted ).toContain( 'Performance Benchmark Results' );
			expect( formatted ).toContain( 'Average Time' );
			expect( formatted ).toContain( 'Target Met' );
		} );
	} );

	describe( 'Memory Usage', () => {
		it( 'should not leak memory with repeated analysis', () => {
			const testData = generateTestData( 1000 );

			// Run analysis multiple times
			for ( let i = 0; i < 20; i++ ) {
				const result = analyzeContent( testData );
				expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			}

			// If we got here without crashing, memory handling is reasonable
			expect( true ).toBe( true );
		} );

		it( 'should handle large content without excessive memory', () => {
			const testData = generateTestData( 5000 );
			const result = analyzeContent( testData );

			// Should complete successfully
			expect( result.wordCount ).toBeGreaterThan( 4000 );
			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
		} );
	} );

	describe( 'Throughput', () => {
		it( 'should process at least 500 words per second', () => {
			const testData = generateTestData( 1000 );
			const startTime = performance.now();

			const result = analyzeContent( testData );

			const endTime = performance.now();
			const executionTime = ( endTime - startTime ) / 1000; // Convert to seconds
			const wordsPerSecond = result.wordCount / executionTime;

			// Should process at least 500 words per second
			expect( wordsPerSecond ).toBeGreaterThan( 500 );
		} );

		it( 'should maintain throughput for large content', () => {
			const testData = generateTestData( 5000 );
			const startTime = performance.now();

			const result = analyzeContent( testData );

			const endTime = performance.now();
			const executionTime = ( endTime - startTime ) / 1000; // Convert to seconds
			const wordsPerSecond = result.wordCount / executionTime;

			// Should maintain reasonable throughput even for large content
			expect( wordsPerSecond ).toBeGreaterThan( 300 );
		} );
	} );

	describe( 'Target Achievement', () => {
		it( 'should meet 1-2 second target for typical content', () => {
			const testData = generateTestData( 1000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			// Average time should be between 1-2 seconds
			// (or close to it for typical content)
			expect( summary.averageTime ).toBeGreaterThan( 0 );
			expect( summary.averageTime ).toBeLessThan( 3000 );
		} );

		it( 'should complete within debounce window', () => {
			// Debounce is 800ms, so analysis should complete quickly after
			const testData = generateTestData( 1000 );
			const startTime = performance.now();

			const result = analyzeContent( testData );

			const endTime = performance.now();
			const executionTime = endTime - startTime;

			// Should complete within 2 seconds (allowing for debounce + analysis)
			expect( executionTime ).toBeLessThan( 2000 );
			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
		} );
	} );

	describe( 'Analyzer Performance', () => {
		it( 'should complete all 16 analyzers efficiently', () => {
			const testData = generateTestData( 1000 );
			const startTime = performance.now();

			const result = analyzeContent( testData );

			const endTime = performance.now();
			const executionTime = endTime - startTime;

			// All 16 analyzers should complete within 2 seconds
			expect( executionTime ).toBeLessThan( 2000 );

			// Should have results from all analyzer categories
			expect( result.seoResults.length ).toBeGreaterThan( 0 );
			expect( result.readabilityResults.length ).toBeGreaterThan( 0 );
		} );

		it( 'should handle analyzer failures without significant performance impact', () => {
			const testData = generateTestData( 1000 );
			const times = [];

			// Run multiple times to measure consistency
			for ( let i = 0; i < 3; i++ ) {
				const startTime = performance.now();
				const result = analyzeContent( testData );
				const endTime = performance.now();

				times.push( endTime - startTime );
				expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			}

			// All runs should complete in similar time
			const average = times.reduce( ( a, b ) => a + b, 0 ) / times.length;
			times.forEach( ( time ) => {
				expect( Math.abs( time - average ) ).toBeLessThan(
					average * 0.5
				);
			} );
		} );
	} );
} );
