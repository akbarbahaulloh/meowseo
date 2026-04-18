/**
 * Performance Benchmarking Utilities
 *
 * Provides utilities for measuring and monitoring analysis performance,
 * including execution time, memory usage, and throughput metrics.
 *
 * @module analysis/utils/performance-benchmark
 */

/**
 * Performance metrics for a single analysis run
 * @typedef {Object} PerformanceMetrics
 * @property {number} executionTime  - Time to complete analysis in milliseconds
 * @property {number} startMemory    - Memory usage at start (MB)
 * @property {number} endMemory      - Memory usage at end (MB)
 * @property {number} memoryDelta    - Change in memory usage (MB)
 * @property {number} wordCount      - Number of words analyzed
 * @property {number} wordsPerSecond - Throughput (words/second)
 * @property {number} timestamp      - When the analysis was performed
 */

/**
 * Benchmark result summary
 * @typedef {Object} BenchmarkSummary
 * @property {number} totalRuns         - Number of analysis runs
 * @property {number} averageTime       - Average execution time (ms)
 * @property {number} minTime           - Minimum execution time (ms)
 * @property {number} maxTime           - Maximum execution time (ms)
 * @property {number} medianTime        - Median execution time (ms)
 * @property {number} averageMemory     - Average memory delta (MB)
 * @property {number} peakMemory        - Peak memory usage (MB)
 * @property {number} averageThroughput - Average words/second
 * @property {number} targetMet         - Whether 1-2 second target was met
 */

/**
 * Get current memory usage in MB
 * Only available in Node.js environment or with performance.memory API
 *
 * @return {number} Memory usage in MB, or 0 if not available
 */
export function getMemoryUsage() {
	if ( typeof performance !== 'undefined' && performance.memory ) {
		return performance.memory.usedJSHeapSize / 1024 / 1024;
	}
	if ( typeof process !== 'undefined' && process.memoryUsage ) {
		return process.memoryUsage().heapUsed / 1024 / 1024;
	}
	return 0;
}

/**
 * Measure execution time and memory usage of a function
 *
 * @param {Function} fn      - Function to benchmark
 * @param {*}        context - Context to bind function to (optional)
 * @param {Array}    args    - Arguments to pass to function
 * @return {Object} Benchmark result with time and memory metrics
 */
export function measurePerformance( fn, context = null, args = [] ) {
	const startMemory = getMemoryUsage();
	const startTime = performance.now();

	let result;
	try {
		result = context ? fn.apply( context, args ) : fn( ...args );
	} catch ( error ) {
		const endTime = performance.now();
		const endMemory = getMemoryUsage();
		return {
			executionTime: endTime - startTime,
			startMemory,
			endMemory,
			memoryDelta: endMemory - startMemory,
			error: error.message,
			success: false,
		};
	}

	const endTime = performance.now();
	const endMemory = getMemoryUsage();

	return {
		executionTime: endTime - startTime,
		startMemory,
		endMemory,
		memoryDelta: endMemory - startMemory,
		result,
		success: true,
	};
}

/**
 * Benchmark analysis engine with various content sizes
 *
 * @param {Function} analyzeContentFn - The analyzeContent function to benchmark
 * @param {Object}   testData         - Test data with content, title, description, etc.
 * @param {number}   iterations       - Number of times to run each test
 * @return {BenchmarkSummary} Summary of benchmark results
 */
export function benchmarkAnalysisEngine(
	analyzeContentFn,
	testData,
	iterations = 5
) {
	const metrics = [];

	for ( let i = 0; i < iterations; i++ ) {
		const perf = measurePerformance( analyzeContentFn, null, [ testData ] );

		if ( perf.success ) {
			const wordCount = testData.content
				? testData.content.split( /\s+/ ).length
				: 0;
			const wordsPerSecond = ( wordCount / perf.executionTime ) * 1000;

			metrics.push( {
				executionTime: perf.executionTime,
				startMemory: perf.startMemory,
				endMemory: perf.endMemory,
				memoryDelta: perf.memoryDelta,
				wordCount,
				wordsPerSecond,
				timestamp: Date.now(),
			} );
		}
	}

	return summarizeBenchmark( metrics );
}

/**
 * Summarize benchmark metrics
 *
 * @param {Array<PerformanceMetrics>} metrics - Array of performance metrics
 * @return {BenchmarkSummary} Summary statistics
 */
export function summarizeBenchmark( metrics ) {
	if ( metrics.length === 0 ) {
		return {
			totalRuns: 0,
			averageTime: 0,
			minTime: 0,
			maxTime: 0,
			medianTime: 0,
			averageMemory: 0,
			peakMemory: 0,
			averageThroughput: 0,
			targetMet: false,
		};
	}

	const times = metrics
		.map( ( m ) => m.executionTime )
		.sort( ( a, b ) => a - b );
	const memories = metrics.map( ( m ) => m.memoryDelta );
	const throughputs = metrics.map( ( m ) => m.wordsPerSecond );

	const averageTime = times.reduce( ( a, b ) => a + b, 0 ) / times.length;
	const medianTime = times[ Math.floor( times.length / 2 ) ];
	const averageMemory =
		memories.reduce( ( a, b ) => a + b, 0 ) / memories.length;
	const peakMemory = Math.max( ...metrics.map( ( m ) => m.endMemory ) );
	const averageThroughput =
		throughputs.reduce( ( a, b ) => a + b, 0 ) / throughputs.length;

	// Target: 1-2 seconds from debounce
	const targetMet = averageTime >= 1000 && averageTime <= 2000;

	return {
		totalRuns: metrics.length,
		averageTime: Math.round( averageTime * 100 ) / 100,
		minTime: Math.round( times[ 0 ] * 100 ) / 100,
		maxTime: Math.round( times[ times.length - 1 ] * 100 ) / 100,
		medianTime: Math.round( medianTime * 100 ) / 100,
		averageMemory: Math.round( averageMemory * 100 ) / 100,
		peakMemory: Math.round( peakMemory * 100 ) / 100,
		averageThroughput: Math.round( averageThroughput * 100 ) / 100,
		targetMet,
	};
}

/**
 * Generate test content of specified word count
 *
 * @param {number} wordCount - Target number of words
 * @return {string} Generated content
 */
export function generateTestContent( wordCount = 1000 ) {
	const words = [
		'lorem',
		'ipsum',
		'dolor',
		'sit',
		'amet',
		'consectetur',
		'adipiscing',
		'elit',
		'sed',
		'do',
		'eiusmod',
		'tempor',
		'incididunt',
		'ut',
		'labore',
		'et',
		'dolore',
		'magna',
		'aliqua',
		'enim',
		'ad',
		'minim',
		'veniam',
		'quis',
		'nostrud',
	];

	let content = '';
	for ( let i = 0; i < wordCount; i++ ) {
		content += words[ Math.floor( Math.random() * words.length ) ] + ' ';
	}

	return content.trim();
}

/**
 * Generate test data with specified content size
 *
 * @param {number} wordCount - Target number of words in content
 * @return {Object} Test data object
 */
export function generateTestData( wordCount = 1000 ) {
	const content = generateTestContent( wordCount );

	return {
		content: `<p>${ content }</p>`,
		title: 'Test Article Title with Focus Keyword',
		description:
			'This is a test meta description with focus keyword included for testing purposes.',
		slug: 'test-article-title-with-focus-keyword',
		keyword: 'focus keyword',
		directAnswer:
			'This is a direct answer to the question about focus keyword optimization.',
		schemaType: 'Article',
	};
}

/**
 * Format benchmark results for console output
 *
 * @param {BenchmarkSummary} summary - Benchmark summary
 * @return {string} Formatted output
 */
export function formatBenchmarkResults( summary ) {
	const lines = [
		'=== Performance Benchmark Results ===',
		`Total Runs: ${ summary.totalRuns }`,
		`Average Time: ${ summary.averageTime }ms`,
		`Min Time: ${ summary.minTime }ms`,
		`Max Time: ${ summary.maxTime }ms`,
		`Median Time: ${ summary.medianTime }ms`,
		`Average Memory Delta: ${ summary.averageMemory }MB`,
		`Peak Memory: ${ summary.peakMemory }MB`,
		`Average Throughput: ${ summary.averageThroughput } words/second`,
		`Target Met (1-2s): ${ summary.targetMet ? 'YES ✓' : 'NO ✗' }`,
	];

	return lines.join( '\n' );
}

export default {
	getMemoryUsage,
	measurePerformance,
	benchmarkAnalysisEngine,
	summarizeBenchmark,
	generateTestContent,
	generateTestData,
	formatBenchmarkResults,
};
