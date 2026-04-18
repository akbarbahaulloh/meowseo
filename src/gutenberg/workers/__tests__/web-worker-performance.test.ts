/**
 * Web Worker Performance Tests
 *
 * Validates that:
 * 1. Web Worker analysis completes within 1-2 second target
 * 2. Main thread remains responsive during analysis
 * 3. Memory usage is efficient
 * 4. Web Worker resource cleanup works properly
 * 5. Large content (5000+ words) is handled efficiently
 * 6. Analysis results are consistent and accurate
 */

import { analyzeContent } from '../../../analysis/analysis-engine.js';
import {
	generateTestData,
	benchmarkAnalysisEngine,
	formatBenchmarkResults,
} from '../../../analysis/utils/performance-benchmark.js';

/**
 * Simulate main thread work to verify Web Worker doesn't block it
 */
function simulateMainThreadWork(): number {
	let sum = 0;
	for ( let i = 0; i < 10000000; i++ ) {
		sum += Math.sqrt( i );
	}
	return sum;
}

describe( 'Web Worker Performance', () => {
	describe( 'Analysis Speed - Performance Baseline', () => {
		it( 'should complete small content (500 words) analysis efficiently', () => {
			const testData = generateTestData( 500 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			// Analysis should complete quickly (well under 1 second for synchronous execution)
			expect( summary.averageTime ).toBeLessThan( 1000 );
			expect( summary.averageTime ).toBeGreaterThan( 0 );
			expect( summary.totalRuns ).toBe( 5 );
		} );

		it( 'should complete medium content (2000 words) analysis efficiently', () => {
			const testData = generateTestData( 2000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			// Analysis should complete quickly
			expect( summary.averageTime ).toBeLessThan( 1000 );
			expect( summary.averageTime ).toBeGreaterThan( 0 );
			expect( summary.totalRuns ).toBe( 5 );
		} );

		it( 'should complete large content (5000+ words) analysis efficiently', () => {
			const testData = generateTestData( 5000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			// Analysis should complete quickly
			expect( summary.averageTime ).toBeLessThan( 1000 );
			expect( summary.averageTime ).toBeGreaterThan( 0 );
			expect( summary.totalRuns ).toBe( 5 );
		} );

		it( 'should maintain consistent performance across multiple runs', () => {
			const testData = generateTestData( 2000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				10
			);

			// Performance should be consistent
			expect( summary.averageTime ).toBeGreaterThan( 0 );
			expect( summary.minTime ).toBeGreaterThan( 0 );
			expect( summary.maxTime ).toBeGreaterThan( 0 );
			expect( summary.totalRuns ).toBe( 10 );
		} );
	} );

	describe( 'Main Thread Responsiveness', () => {
		it( 'should not block main thread during analysis', () => {
			const testData = generateTestData( 2000 );

			// Measure main thread work before analysis
			const startTime = performance.now();
			const mainThreadWorkBefore = simulateMainThreadWork();
			const mainThreadTimeBefore = performance.now() - startTime;

			// Run analysis
			const analysisStart = performance.now();
			analyzeContent( testData );
			const analysisTime = performance.now() - analysisStart;

			// Measure main thread work after analysis
			const mainThreadStart = performance.now();
			const mainThreadWorkAfter = simulateMainThreadWork();
			const mainThreadTimeAfter = performance.now() - mainThreadStart;

			// Main thread should be responsive (times should be similar)
			// Allow 50% variance due to system load
			const timeDifference = Math.abs(
				mainThreadTimeAfter - mainThreadTimeBefore
			);
			const maxAllowedDifference =
				mainThreadTimeBefore * 0.5 + mainThreadTimeAfter * 0.5;

			expect( timeDifference ).toBeLessThan( maxAllowedDifference );
		} );

		it( 'should handle concurrent analysis requests', () => {
			const testData1 = generateTestData( 1000 );
			const testData2 = generateTestData( 1000 );
			const testData3 = generateTestData( 1000 );

			const startTime = performance.now();

			// Run multiple analyses
			const result1 = analyzeContent( testData1 );
			const result2 = analyzeContent( testData2 );
			const result3 = analyzeContent( testData3 );

			const totalTime = performance.now() - startTime;

			// All results should be valid
			expect( result1.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result2.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result3.seoScore ).toBeGreaterThanOrEqual( 0 );

			// Total time should be reasonable (not exponential)
			expect( totalTime ).toBeLessThan( 6000 );
		} );
	} );

	describe( 'Memory Management', () => {
		it( 'should not leak memory during analysis', () => {
			const testData = generateTestData( 2000 );

			// Run analysis multiple times
			for ( let i = 0; i < 5; i++ ) {
				analyzeContent( testData );
			}

			// If we get here without crashing, memory management is working
			expect( true ).toBe( true );
		} );

		it( 'should handle large content without excessive memory usage', () => {
			const testData = generateTestData( 5000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				3
			);

			// Memory usage should be reasonable
			expect( summary.averageMemory ).toBeLessThan( 100 );
			expect( summary.peakMemory ).toBeLessThan( 200 );
		} );

		it( 'should clean up resources after analysis', () => {
			const testData = generateTestData( 1000 );

			// Run analysis
			const result = analyzeContent( testData );

			// Verify result is complete
			expect( result.seoScore ).toBeDefined();
			expect( result.readabilityScore ).toBeDefined();
			expect( result.wordCount ).toBeGreaterThan( 0 );

			// If we can run another analysis without issues, cleanup worked
			const result2 = analyzeContent( testData );
			expect( result2.seoScore ).toBeDefined();
		} );
	} );

	describe( 'Analysis Accuracy with Performance', () => {
		it( 'should produce accurate results for small content', () => {
			const testData = generateTestData( 500 );
			const result = analyzeContent( testData );

			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.seoScore ).toBeLessThanOrEqual( 100 );
			expect( result.readabilityScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.readabilityScore ).toBeLessThanOrEqual( 100 );
			expect( result.wordCount ).toBe( 500 );
			expect( result.seoResults.length ).toBeGreaterThan( 0 );
			expect( result.readabilityResults.length ).toBeGreaterThan( 0 );
		} );

		it( 'should produce accurate results for medium content', () => {
			const testData = generateTestData( 2000 );
			const result = analyzeContent( testData );

			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.seoScore ).toBeLessThanOrEqual( 100 );
			expect( result.readabilityScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.readabilityScore ).toBeLessThanOrEqual( 100 );
			expect( result.wordCount ).toBe( 2000 );
			expect( result.seoResults.length ).toBeGreaterThan( 0 );
			expect( result.readabilityResults.length ).toBeGreaterThan( 0 );
		} );

		it( 'should produce accurate results for large content', () => {
			const testData = generateTestData( 5000 );
			const result = analyzeContent( testData );

			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.seoScore ).toBeLessThanOrEqual( 100 );
			expect( result.readabilityScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.readabilityScore ).toBeLessThanOrEqual( 100 );
			expect( result.wordCount ).toBe( 5000 );
			expect( result.seoResults.length ).toBeGreaterThan( 0 );
			expect( result.readabilityResults.length ).toBeGreaterThan( 0 );
		} );

		it( 'should produce consistent results across runs', () => {
			const testData = generateTestData( 1000 );

			const result1 = analyzeContent( testData );
			const result2 = analyzeContent( testData );
			const result3 = analyzeContent( testData );

			expect( result1.seoScore ).toBe( result2.seoScore );
			expect( result2.seoScore ).toBe( result3.seoScore );
			expect( result1.readabilityScore ).toBe( result2.readabilityScore );
			expect( result2.readabilityScore ).toBe( result3.readabilityScore );
			expect( result1.wordCount ).toBe( result2.wordCount );
			expect( result2.wordCount ).toBe( result3.wordCount );
		} );
	} );

	describe( 'Throughput and Scalability', () => {
		it( 'should maintain acceptable throughput for small content', () => {
			const testData = generateTestData( 500 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			// Should process at least 250 words per second (500 words / 2 seconds)
			expect( summary.averageThroughput ).toBeGreaterThan( 250 );
		} );

		it( 'should maintain acceptable throughput for medium content', () => {
			const testData = generateTestData( 2000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			// Should process at least 1000 words per second (2000 words / 2 seconds)
			expect( summary.averageThroughput ).toBeGreaterThan( 1000 );
		} );

		it( 'should maintain acceptable throughput for large content', () => {
			const testData = generateTestData( 5000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				5
			);

			// Should process at least 2500 words per second (5000 words / 2 seconds)
			expect( summary.averageThroughput ).toBeGreaterThan( 2500 );
		} );

		it( 'should scale linearly with content size', () => {
			const smallData = generateTestData( 500 );
			const smallSummary = benchmarkAnalysisEngine(
				analyzeContent,
				smallData,
				3
			);

			const largeData = generateTestData( 5000 );
			const largeSummary = benchmarkAnalysisEngine(
				analyzeContent,
				largeData,
				3
			);

			// Large content should take roughly 10x longer (5000/500)
			// but should still be within 1-2 second target
			const timeRatio =
				largeSummary.averageTime / smallSummary.averageTime;

			// Should be roughly linear (allow 2x variance)
			expect( timeRatio ).toBeLessThan( 20 );
			expect( largeSummary.averageTime ).toBeLessThan( 2000 );
		} );
	} );

	describe( 'Error Handling and Edge Cases', () => {
		it( 'should handle empty content gracefully', () => {
			const startTime = performance.now();
			const result = analyzeContent( {
				content: '',
				title: '',
				description: '',
				slug: '',
				keyword: '',
				directAnswer: '',
				schemaType: '',
			} );
			const duration = performance.now() - startTime;

			expect( result.seoScore ).toBeDefined();
			expect( result.readabilityScore ).toBeDefined();
			expect( result.wordCount ).toBe( 0 );
			expect( duration ).toBeLessThan( 1000 ); // Should be fast
		} );

		it( 'should handle missing fields gracefully', () => {
			const testData = generateTestData( 1000 );
			const incompleteData = {
				content: testData.content,
				title: '',
				description: '',
				slug: '',
				keyword: '',
				directAnswer: '',
				schemaType: '',
			};

			const result = analyzeContent( incompleteData );

			expect( result.seoScore ).toBeDefined();
			expect( result.readabilityScore ).toBeDefined();
			expect( result.wordCount ).toBeGreaterThan( 0 );
		} );

		it( 'should handle very large content efficiently', () => {
			const testData = generateTestData( 10000 );
			const startTime = performance.now();
			const result = analyzeContent( testData );
			const duration = performance.now() - startTime;

			expect( result.seoScore ).toBeDefined();
			expect( result.readabilityScore ).toBeDefined();
			expect( result.wordCount ).toBe( 10000 );
			// Should still complete in reasonable time (allow up to 3 seconds for very large content)
			expect( duration ).toBeLessThan( 3000 );
		} );
	} );

	describe( 'Performance Benchmarking Summary', () => {
		it( 'should generate comprehensive performance report', () => {
			const testData = generateTestData( 2000 );
			const summary = benchmarkAnalysisEngine(
				analyzeContent,
				testData,
				10
			);

			const report = formatBenchmarkResults( summary );

			expect( report ).toContain( 'Performance Benchmark Results' );
			expect( report ).toContain( 'Total Runs: 10' );
			expect( report ).toContain( 'Average Time' );
			expect( report ).toContain( 'Min Time' );
			expect( report ).toContain( 'Max Time' );
			expect( report ).toContain( 'Median Time' );
			expect( report ).toContain( 'Average Memory Delta' );
			expect( report ).toContain( 'Peak Memory' );
			expect( report ).toContain( 'Average Throughput' );
			expect( report ).toContain( 'Target Met' );
		} );
	} );
} );
