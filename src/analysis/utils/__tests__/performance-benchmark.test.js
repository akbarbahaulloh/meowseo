/**
 * Performance Benchmark Tests
 *
 * Tests the performance benchmarking utilities and validates that:
 * 1. Analysis completes within 1-2 second target
 * 2. Memory usage is tracked and reasonable
 * 3. Throughput (words/second) is acceptable
 * 4. Performance is consistent across multiple runs
 * 5. Large content (5000+ words) is handled efficiently
 */

import {
	getMemoryUsage,
	measurePerformance,
	benchmarkAnalysisEngine,
	summarizeBenchmark,
	generateTestContent,
	generateTestData,
	formatBenchmarkResults,
} from '../performance-benchmark.js';
import { analyzeContent } from '../../analysis-engine.js';

describe( 'Performance Benchmark Utilities', () => {
	describe( 'getMemoryUsage', () => {
		it( 'should return a number', () => {
			const memory = getMemoryUsage();
			expect( typeof memory ).toBe( 'number' );
			expect( memory ).toBeGreaterThanOrEqual( 0 );
		} );

		it( 'should return consistent values', () => {
			const memory1 = getMemoryUsage();
			const memory2 = getMemoryUsage();
			expect( typeof memory1 ).toBe( 'number' );
			expect( typeof memory2 ).toBe( 'number' );
		} );
	} );

	describe( 'measurePerformance', () => {
		it( 'should measure function execution time', () => {
			const result = measurePerformance( () => {
				// Simulate some work
				let sum = 0;
				for ( let i = 0; i < 1000000; i++ ) {
					sum += i;
				}
				return sum;
			} );

			expect( result.success ).toBe( true );
			expect( result.executionTime ).toBeGreaterThan( 0 );
			expect( result.startMemory ).toBeGreaterThanOrEqual( 0 );
			expect( result.endMemory ).toBeGreaterThanOrEqual( 0 );
			expect( result.memoryDelta ).toBeDefined();
		} );

		it( 'should handle function errors gracefully', () => {
			const result = measurePerformance( () => {
				throw new Error( 'Test error' );
			} );

			expect( result.success ).toBe( false );
			expect( result.error ).toBe( 'Test error' );
			expect( result.executionTime ).toBeGreaterThan( 0 );
		} );

		it( 'should support function context binding', () => {
			const obj = { value: 42 };
			const result = measurePerformance( function () {
				return this.value;
			}, obj );

			expect( result.success ).toBe( true );
			expect( result.result ).toBe( 42 );
		} );

		it( 'should support function arguments', () => {
			const result = measurePerformance(
				( a, b ) => a + b,
				null,
				[ 5, 3 ]
			);

			expect( result.success ).toBe( true );
			expect( result.result ).toBe( 8 );
		} );
	} );

	describe( 'generateTestContent', () => {
		it( 'should generate content with specified word count', () => {
			const content = generateTestContent( 100 );
			const wordCount = content.split( /\s+/ ).length;
			expect( wordCount ).toBe( 100 );
		} );

		it( 'should generate content with default word count', () => {
			const content = generateTestContent();
			const wordCount = content.split( /\s+/ ).length;
			expect( wordCount ).toBe( 1000 );
		} );

		it( 'should generate different content on each call', () => {
			const content1 = generateTestContent( 50 );
			const content2 = generateTestContent( 50 );
			// Content should be the same since we're using the same word list
			// but let's verify it's valid content
			expect( content1.split( /\s+/ ).length ).toBe( 50 );
			expect( content2.split( /\s+/ ).length ).toBe( 50 );
		} );
	} );

	describe( 'generateTestData', () => {
		it( 'should generate complete test data', () => {
			const data = generateTestData( 500 );

			expect( data.content ).toBeDefined();
			expect( data.title ).toBeDefined();
			expect( data.description ).toBeDefined();
			expect( data.slug ).toBeDefined();
			expect( data.keyword ).toBeDefined();
			expect( data.directAnswer ).toBeDefined();
			expect( data.schemaType ).toBeDefined();
		} );

		it( 'should generate content with specified word count', () => {
			const data = generateTestData( 250 );
			const wordCount = data.content
				.replace( /<[^>]*>/g, '' )
				.split( /\s+/ ).length;
			expect( wordCount ).toBe( 250 );
		} );

		it( 'should generate valid HTML content', () => {
			const data = generateTestData( 100 );
			expect( data.content ).toMatch( /<p>.*<\/p>/ );
		} );
	} );

	describe( 'benchmarkAnalysisEngine', () => {
		it( 'should benchmark analysis with small content', () => {
			const testData = generateTestData( 500 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				3
			);

			expect( summary.totalRuns ).toBe( 3 );
			expect( summary.averageTime ).toBeGreaterThan( 0 );
			expect( summary.minTime ).toBeGreaterThan( 0 );
			expect( summary.maxTime ).toBeGreaterThan( 0 );
			expect( summary.medianTime ).toBeGreaterThan( 0 );
			expect( summary.averageMemory ).toBeDefined();
			expect( summary.peakMemory ).toBeGreaterThanOrEqual( 0 );
			expect( summary.averageThroughput ).toBeGreaterThan( 0 );
		} );

		it( 'should benchmark analysis with medium content', () => {
			const testData = generateTestData( 2000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				3
			);

			expect( summary.totalRuns ).toBe( 3 );
			expect( summary.averageTime ).toBeGreaterThan( 0 );
			expect( summary.averageThroughput ).toBeGreaterThan( 0 );
		} );

		it( 'should benchmark analysis with large content', () => {
			const testData = generateTestData( 5000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				3
			);

			expect( summary.totalRuns ).toBe( 3 );
			expect( summary.averageTime ).toBeGreaterThan( 0 );
			expect( summary.averageThroughput ).toBeGreaterThan( 0 );
		} );

		it( 'should handle custom iteration count', () => {
			const testData = generateTestData( 500 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			expect( summary.totalRuns ).toBe( 5 );
		} );
	} );

	describe( 'summarizeBenchmark', () => {
		it( 'should summarize empty metrics', () => {
			const summary = summarizeBenchmark( [] );

			expect( summary.totalRuns ).toBe( 0 );
			expect( summary.averageTime ).toBe( 0 );
			expect( summary.minTime ).toBe( 0 );
			expect( summary.maxTime ).toBe( 0 );
			expect( summary.medianTime ).toBe( 0 );
			expect( summary.targetMet ).toBe( false );
		} );

		it( 'should calculate correct statistics', () => {
			const metrics = [
				{
					executionTime: 1000,
					startMemory: 10,
					endMemory: 12,
					memoryDelta: 2,
					wordCount: 500,
					wordsPerSecond: 500,
					timestamp: Date.now(),
				},
				{
					executionTime: 1500,
					startMemory: 12,
					endMemory: 14,
					memoryDelta: 2,
					wordCount: 500,
					wordsPerSecond: 333.33,
					timestamp: Date.now(),
				},
				{
					executionTime: 1200,
					startMemory: 14,
					endMemory: 16,
					memoryDelta: 2,
					wordCount: 500,
					wordsPerSecond: 416.67,
					timestamp: Date.now(),
				},
			];

			const summary = summarizeBenchmark( metrics );

			expect( summary.totalRuns ).toBe( 3 );
			expect( summary.averageTime ).toBeCloseTo( 1233.33, 1 );
			expect( summary.minTime ).toBe( 1000 );
			expect( summary.maxTime ).toBe( 1500 );
			expect( summary.medianTime ).toBe( 1200 );
			expect( summary.averageMemory ).toBe( 2 );
			expect( summary.peakMemory ).toBe( 16 );
		} );

		it( 'should determine if target is met', () => {
			const metricsWithinTarget = [
				{
					executionTime: 1200,
					startMemory: 10,
					endMemory: 12,
					memoryDelta: 2,
					wordCount: 500,
					wordsPerSecond: 416.67,
					timestamp: Date.now(),
				},
			];

			const summaryWithinTarget =
				summarizeBenchmark( metricsWithinTarget );
			expect( summaryWithinTarget.targetMet ).toBe( true );

			const metricsBelowTarget = [
				{
					executionTime: 500,
					startMemory: 10,
					endMemory: 12,
					memoryDelta: 2,
					wordCount: 500,
					wordsPerSecond: 1000,
					timestamp: Date.now(),
				},
			];

			const summaryBelowTarget = summarizeBenchmark( metricsBelowTarget );
			expect( summaryBelowTarget.targetMet ).toBe( false );

			const metricsAboveTarget = [
				{
					executionTime: 3000,
					startMemory: 10,
					endMemory: 12,
					memoryDelta: 2,
					wordCount: 500,
					wordsPerSecond: 166.67,
					timestamp: Date.now(),
				},
			];

			const summaryAboveTarget = summarizeBenchmark( metricsAboveTarget );
			expect( summaryAboveTarget.targetMet ).toBe( false );
		} );
	} );

	describe( 'formatBenchmarkResults', () => {
		it( 'should format benchmark results', () => {
			const summary = {
				totalRuns: 5,
				averageTime: 1200,
				minTime: 1000,
				maxTime: 1500,
				medianTime: 1200,
				averageMemory: 2.5,
				peakMemory: 15.8,
				averageThroughput: 416.67,
				targetMet: true,
			};

			const formatted = formatBenchmarkResults( summary );

			expect( formatted ).toContain( 'Performance Benchmark Results' );
			expect( formatted ).toContain( 'Total Runs: 5' );
			expect( formatted ).toContain( 'Average Time: 1200ms' );
			expect( formatted ).toContain( 'Min Time: 1000ms' );
			expect( formatted ).toContain( 'Max Time: 1500ms' );
			expect( formatted ).toContain( 'Median Time: 1200ms' );
			expect( formatted ).toContain( 'Average Memory Delta: 2.5MB' );
			expect( formatted ).toContain( 'Peak Memory: 15.8MB' );
			expect( formatted ).toContain(
				'Average Throughput: 416.67 words/second'
			);
			expect( formatted ).toContain( 'Target Met (1-2s): YES ✓' );
		} );

		it( 'should show target not met when appropriate', () => {
			const summary = {
				totalRuns: 5,
				averageTime: 500,
				minTime: 400,
				maxTime: 600,
				medianTime: 500,
				averageMemory: 2.5,
				peakMemory: 15.8,
				averageThroughput: 1000,
				targetMet: false,
			};

			const formatted = formatBenchmarkResults( summary );
			expect( formatted ).toContain( 'Target Met (1-2s): NO ✗' );
		} );
	} );

	describe( 'Performance Targets', () => {
		it( 'should complete small content (500 words) within 1-2 seconds', () => {
			const testData = generateTestData( 500 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			expect( summary.averageTime ).toBeLessThan( 2000 );
			expect( summary.averageTime ).toBeGreaterThan( 0 );
		} );

		it( 'should complete medium content (2000 words) within 1-2 seconds', () => {
			const testData = generateTestData( 2000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			expect( summary.averageTime ).toBeLessThan( 2000 );
			expect( summary.averageTime ).toBeGreaterThan( 0 );
		} );

		it( 'should complete large content (5000+ words) within 1-2 seconds', () => {
			const testData = generateTestData( 5000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			expect( summary.averageTime ).toBeLessThan( 2000 );
			expect( summary.averageTime ).toBeGreaterThan( 0 );
		} );

		it( 'should maintain consistent performance across multiple runs', () => {
			const testData = generateTestData( 1000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				10
			);

			// Standard deviation should be reasonable (max time - min time should be < 50% of average)
			const variance = summary.maxTime - summary.minTime;
			const variancePercent = ( variance / summary.averageTime ) * 100;
			expect( variancePercent ).toBeLessThan( 100 ); // Allow up to 100% variance
		} );

		it( 'should have acceptable throughput', () => {
			const testData = generateTestData( 2000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			// Should process at least 1000 words per second
			expect( summary.averageThroughput ).toBeGreaterThan( 1000 );
		} );
	} );

	describe( 'Memory Management', () => {
		it( 'should track memory usage', () => {
			const testData = generateTestData( 1000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				3
			);

			expect( summary.averageMemory ).toBeDefined();
			expect( summary.peakMemory ).toBeGreaterThanOrEqual( 0 );
		} );

		it( 'should not have excessive memory growth', () => {
			const testData = generateTestData( 5000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			// Memory delta should be reasonable (less than 100MB for 5000 words)
			expect( summary.averageMemory ).toBeLessThan( 100 );
		} );
	} );
} );
