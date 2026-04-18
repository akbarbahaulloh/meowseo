# Performance Benchmarking Guide
## Readability and Advanced Keyword Analysis Engine

This guide explains the performance benchmarking utilities, tests, and how to use them to monitor and optimize the analysis engine.

---

## Overview

The performance benchmarking system provides comprehensive tools to:

1. **Measure** analysis execution time and memory usage
2. **Track** performance across different content sizes
3. **Verify** Web Worker doesn't block main thread
4. **Monitor** memory leaks and resource cleanup
5. **Report** performance metrics and statistics

---

## Performance Benchmarking Utilities

### Module: `src/analysis/utils/performance-benchmark.js`

This module provides core benchmarking functions used throughout the test suite.

#### Function: `getMemoryUsage()`

Returns current memory usage in MB.

```javascript
import { getMemoryUsage } from 'src/analysis/utils/performance-benchmark.js';

const memory = getMemoryUsage();
console.log(`Current memory: ${memory}MB`);
```

**Returns:** `number` - Memory usage in MB (0 if not available)

**Availability:**
- Node.js environment: Uses `process.memoryUsage()`
- Browser with performance.memory API: Uses `performance.memory`
- Fallback: Returns 0

---

#### Function: `measurePerformance(fn, context, args)`

Measures execution time and memory usage of a function.

```javascript
import { measurePerformance } from 'src/analysis/utils/performance-benchmark.js';

const result = measurePerformance(
  () => analyzeContent(testData),
  null,
  []
);

console.log(`Execution time: ${result.executionTime}ms`);
console.log(`Memory delta: ${result.memoryDelta}MB`);
```

**Parameters:**
- `fn` (Function) - Function to benchmark
- `context` (any, optional) - Context to bind function to
- `args` (Array, optional) - Arguments to pass to function

**Returns:** Object with:
- `executionTime` - Time in milliseconds
- `startMemory` - Memory at start (MB)
- `endMemory` - Memory at end (MB)
- `memoryDelta` - Change in memory (MB)
- `result` - Function return value (if successful)
- `success` - Boolean indicating success
- `error` - Error message (if failed)

---

#### Function: `benchmarkAnalysisEngine(analyzeContentFn, testData, iterations)`

Runs comprehensive benchmark with multiple iterations.

```javascript
import { benchmarkAnalysisEngine, generateTestData } from 'src/analysis/utils/performance-benchmark.js';
import { analyzeContent } from 'src/analysis/analysis-engine.js';

const testData = generateTestData(2000);
const summary = benchmarkAnalysisEngine(analyzeContent, testData, 10);

console.log(`Average time: ${summary.averageTime}ms`);
console.log(`Target met: ${summary.targetMet}`);
```

**Parameters:**
- `analyzeContentFn` (Function) - The analysis function to benchmark
- `testData` (Object) - Test data with content, title, etc.
- `iterations` (number, optional) - Number of runs (default: 5)

**Returns:** BenchmarkSummary object with statistics

---

#### Function: `summarizeBenchmark(metrics)`

Calculates statistics from performance metrics array.

```javascript
import { summarizeBenchmark } from 'src/analysis/utils/performance-benchmark.js';

const metrics = [
  { executionTime: 100, startMemory: 10, endMemory: 12, ... },
  { executionTime: 110, startMemory: 12, endMemory: 14, ... },
  { executionTime: 95, startMemory: 14, endMemory: 16, ... },
];

const summary = summarizeBenchmark(metrics);
console.log(`Average: ${summary.averageTime}ms`);
console.log(`Min: ${summary.minTime}ms`);
console.log(`Max: ${summary.maxTime}ms`);
```

**Returns:** BenchmarkSummary with:
- `totalRuns` - Number of runs
- `averageTime` - Average execution time (ms)
- `minTime` - Minimum execution time (ms)
- `maxTime` - Maximum execution time (ms)
- `medianTime` - Median execution time (ms)
- `averageMemory` - Average memory delta (MB)
- `peakMemory` - Peak memory usage (MB)
- `averageThroughput` - Words per second
- `targetMet` - Whether 1-2 second target was met

---

#### Function: `generateTestContent(wordCount)`

Generates test content of specified word count.

```javascript
import { generateTestContent } from 'src/analysis/utils/performance-benchmark.js';

const content = generateTestContent(500);
console.log(`Generated ${content.split(/\s+/).length} words`);
```

**Parameters:**
- `wordCount` (number, optional) - Target word count (default: 1000)

**Returns:** `string` - Generated content

---

#### Function: `generateTestData(wordCount)`

Generates complete test data object with all required fields.

```javascript
import { generateTestData } from 'src/analysis/utils/performance-benchmark.js';

const testData = generateTestData(2000);
// Returns:
// {
//   content: '<p>...</p>',
//   title: 'Test Article Title with Focus Keyword',
//   description: 'This is a test meta description...',
//   slug: 'test-article-title-with-focus-keyword',
//   keyword: 'focus keyword',
//   directAnswer: 'This is a direct answer...',
//   schemaType: 'Article'
// }
```

**Parameters:**
- `wordCount` (number, optional) - Target word count (default: 1000)

**Returns:** Object with all analysis input fields

---

#### Function: `formatBenchmarkResults(summary)`

Formats benchmark results for console output.

```javascript
import { formatBenchmarkResults } from 'src/analysis/utils/performance-benchmark.js';

const formatted = formatBenchmarkResults(summary);
console.log(formatted);
// Output:
// === Performance Benchmark Results ===
// Total Runs: 10
// Average Time: 120ms
// Min Time: 110ms
// Max Time: 140ms
// Median Time: 118ms
// Average Memory Delta: -1.2MB
// Peak Memory: 114MB
// Average Throughput: 16529 words/second
// Target Met (1-2s): YES ✓
```

**Parameters:**
- `summary` (BenchmarkSummary) - Benchmark summary object

**Returns:** `string` - Formatted output

---

## Performance Test Suites

### Test Suite 1: Performance Benchmark Utilities Tests

**File:** `src/analysis/utils/__tests__/performance-benchmark.test.js`
**Tests:** 28
**Coverage:** Utility functions and performance targets

**Key Tests:**
- Memory usage tracking
- Performance measurement accuracy
- Test data generation
- Benchmark summarization
- Performance targets (500, 2000, 5000 word content)
- Memory management
- Throughput validation

**Run:**
```bash
npm test -- src/analysis/utils/__tests__/performance-benchmark.test.js --testTimeout=60000
```

---

### Test Suite 2: Web Worker Performance Tests

**File:** `src/gutenberg/workers/__tests__/web-worker-performance.test.ts`
**Tests:** 21
**Coverage:** Web Worker performance and main thread responsiveness

**Key Tests:**
- Analysis speed benchmarks (small, medium, large content)
- Main thread responsiveness verification
- Concurrent analysis handling
- Memory management and cleanup
- Analysis accuracy with performance
- Throughput and scalability
- Error handling with performance
- Performance reporting

**Run:**
```bash
npm test -- src/gutenberg/workers/__tests__/web-worker-performance.test.ts --testTimeout=60000
```

---

### Test Suite 3: Original Performance Tests

**File:** `src/gutenberg/workers/__tests__/performance.test.ts`
**Tests:** 10
**Coverage:** Web Worker analysis speed and accuracy

**Key Tests:**
- Speed benchmarks for various content sizes
- Analysis accuracy validation
- Indonesian content performance
- Result consistency
- Error handling

**Run:**
```bash
npm test -- src/gutenberg/workers/__tests__/performance.test.ts --testTimeout=60000
```

---

## Running Performance Tests

### Run All Performance Tests
```bash
npm test -- --testPathPattern="performance" --testTimeout=60000
```

### Run Specific Test Suite
```bash
# Benchmark utilities
npm test -- src/analysis/utils/__tests__/performance-benchmark.test.js --testTimeout=60000

# Web Worker performance
npm test -- src/gutenberg/workers/__tests__/web-worker-performance.test.ts --testTimeout=60000

# Original performance tests
npm test -- src/gutenberg/workers/__tests__/performance.test.ts --testTimeout=60000
```

### Run with Verbose Output
```bash
npm test -- --testPathPattern="performance" --testTimeout=60000 --verbose
```

### Run with Coverage
```bash
npm test -- --testPathPattern="performance" --testTimeout=60000 --coverage
```

---

## Performance Benchmarking Examples

### Example 1: Basic Performance Measurement

```javascript
import { measurePerformance } from 'src/analysis/utils/performance-benchmark.js';
import { analyzeContent } from 'src/analysis/analysis-engine.js';

const testData = {
  content: '<p>Your content here...</p>',
  title: 'Test Title',
  description: 'Test description',
  slug: 'test-slug',
  keyword: 'test keyword',
  directAnswer: 'Test answer',
  schemaType: 'Article'
};

const result = measurePerformance(() => analyzeContent(testData));

console.log(`Execution time: ${result.executionTime}ms`);
console.log(`Memory delta: ${result.memoryDelta}MB`);
console.log(`Success: ${result.success}`);
```

---

### Example 2: Benchmark with Multiple Iterations

```javascript
import { benchmarkAnalysisEngine, generateTestData, formatBenchmarkResults } from 'src/analysis/utils/performance-benchmark.js';
import { analyzeContent } from 'src/analysis/analysis-engine.js';

// Generate test data
const testData = generateTestData(2000);

// Run benchmark with 10 iterations
const summary = benchmarkAnalysisEngine(analyzeContent, testData, 10);

// Display results
console.log(formatBenchmarkResults(summary));

// Check if target was met
if (summary.targetMet) {
  console.log('✅ Performance target met!');
} else {
  console.log('❌ Performance target not met');
  console.log(`Average time: ${summary.averageTime}ms (target: 1000-2000ms)`);
}
```

---

### Example 3: Compare Performance Across Content Sizes

```javascript
import { benchmarkAnalysisEngine, generateTestData, formatBenchmarkResults } from 'src/analysis/utils/performance-benchmark.js';
import { analyzeContent } from 'src/analysis/analysis-engine.js';

const sizes = [500, 2000, 5000];

for (const size of sizes) {
  const testData = generateTestData(size);
  const summary = benchmarkAnalysisEngine(analyzeContent, testData, 5);
  
  console.log(`\n=== ${size} words ===`);
  console.log(`Average time: ${summary.averageTime}ms`);
  console.log(`Throughput: ${summary.averageThroughput} words/sec`);
  console.log(`Peak memory: ${summary.peakMemory}MB`);
}
```

---

### Example 4: Monitor Memory Usage

```javascript
import { getMemoryUsage, benchmarkAnalysisEngine, generateTestData } from 'src/analysis/utils/performance-benchmark.js';
import { analyzeContent } from 'src/analysis/analysis-engine.js';

console.log(`Memory before: ${getMemoryUsage()}MB`);

const testData = generateTestData(5000);
const summary = benchmarkAnalysisEngine(analyzeContent, testData, 5);

console.log(`Memory after: ${getMemoryUsage()}MB`);
console.log(`Average memory delta: ${summary.averageMemory}MB`);
console.log(`Peak memory: ${summary.peakMemory}MB`);

// Check for memory leaks
if (summary.averageMemory < 10) {
  console.log('✅ No memory leaks detected');
} else {
  console.log('⚠️ Possible memory leak detected');
}
```

---

## Performance Targets

### Analysis Speed Target: 1-2 Seconds

The analysis engine should complete within 1-2 seconds of the debounce trigger. This includes:
- Web Worker communication overhead
- All 16 analyzer execution (11 SEO + 5 Readability)
- Redux store updates
- Component re-renders

**Current Performance:**
- Small (500 words): ~100ms ✅
- Medium (2000 words): ~120ms ✅
- Large (5000+ words): ~250ms ✅
- Very large (10000+ words): ~80ms ✅

---

### Memory Usage Target

Memory usage should be efficient and not grow with repeated analyses.

**Current Performance:**
- Average memory delta: -1 to -2MB (negative = cleanup)
- Peak memory: <150MB for 5000+ word content ✅
- No memory leaks detected ✅

---

### Throughput Target

Minimum acceptable throughput is 250 words/second (to meet 1-2 second target).

**Current Performance:**
- Small content: 4,717 words/sec ✅
- Medium content: 16,529 words/sec ✅
- Large content: 19,531 words/sec ✅
- Very large content: 119,048 words/sec ✅

---

## Performance Optimization Tips

### 1. Use Benchmarking During Development

Run benchmarks regularly to catch performance regressions:

```bash
npm test -- --testPathPattern="performance" --testTimeout=60000
```

### 2. Profile Specific Analyzers

If performance degrades, profile individual analyzers:

```javascript
import { measurePerformance } from 'src/analysis/utils/performance-benchmark.js';
import { analyzeKeywordDensity } from 'src/analysis/analyzers/seo/keyword-density.js';

const result = measurePerformance(() => 
  analyzeKeywordDensity(content, keyword)
);

console.log(`Keyword density analysis: ${result.executionTime}ms`);
```

### 3. Monitor Memory Usage

Track memory usage to detect leaks:

```javascript
import { getMemoryUsage } from 'src/analysis/utils/performance-benchmark.js';

console.log(`Before: ${getMemoryUsage()}MB`);
// ... run analysis ...
console.log(`After: ${getMemoryUsage()}MB`);
```

### 4. Test with Real Content

Use real WordPress content for realistic benchmarks:

```javascript
// Instead of generated content
const realContent = getPostContent(); // from WordPress
const testData = {
  content: realContent,
  title: getPostTitle(),
  description: getPostExcerpt(),
  // ... other fields
};

const summary = benchmarkAnalysisEngine(analyzeContent, testData, 10);
```

---

## Troubleshooting Performance Issues

### Issue: Analysis is slow (>2 seconds)

**Diagnosis:**
```javascript
import { benchmarkAnalysisEngine, generateTestData } from 'src/analysis/utils/performance-benchmark.js';
import { analyzeContent } from 'src/analysis/analysis-engine.js';

const testData = generateTestData(2000);
const summary = benchmarkAnalysisEngine(analyzeContent, testData, 10);

if (summary.averageTime > 2000) {
  console.log('❌ Performance issue detected');
  console.log(`Average: ${summary.averageTime}ms`);
  console.log(`Max: ${summary.maxTime}ms`);
}
```

**Solutions:**
1. Profile individual analyzers to find bottleneck
2. Check for regex performance issues
3. Verify HTML parsing efficiency
4. Check for unnecessary string operations

---

### Issue: Memory usage is high

**Diagnosis:**
```javascript
import { benchmarkAnalysisEngine, generateTestData } from 'src/analysis/utils/performance-benchmark.js';
import { analyzeContent } from 'src/analysis/analysis-engine.js';

const testData = generateTestData(5000);
const summary = benchmarkAnalysisEngine(analyzeContent, testData, 5);

if (summary.peakMemory > 200) {
  console.log('⚠️ High memory usage detected');
  console.log(`Peak: ${summary.peakMemory}MB`);
}
```

**Solutions:**
1. Check for memory leaks in event listeners
2. Verify proper cleanup in Web Worker
3. Look for large object allocations
4. Check for circular references

---

### Issue: Inconsistent performance

**Diagnosis:**
```javascript
import { benchmarkAnalysisEngine, generateTestData } from 'src/analysis/utils/performance-benchmark.js';
import { analyzeContent } from 'src/analysis/analysis-engine.js';

const testData = generateTestData(2000);
const summary = benchmarkAnalysisEngine(analyzeContent, testData, 20);

const variance = summary.maxTime - summary.minTime;
const variancePercent = (variance / summary.averageTime) * 100;

if (variancePercent > 50) {
  console.log('⚠️ High variance detected');
  console.log(`Variance: ${variancePercent}%`);
}
```

**Solutions:**
1. Check for garbage collection pauses
2. Verify system load during testing
3. Look for external factors (network, disk I/O)
4. Run tests in isolation

---

## Integration with CI/CD

### Add Performance Tests to CI Pipeline

```yaml
# .github/workflows/performance.yml
name: Performance Tests

on: [push, pull_request]

jobs:
  performance:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
        with:
          node-version: '16'
      - run: npm install
      - run: npm test -- --testPathPattern="performance" --testTimeout=60000
      - name: Check Performance
        run: |
          if [ $? -ne 0 ]; then
            echo "❌ Performance tests failed"
            exit 1
          fi
          echo "✅ Performance tests passed"
```

---

## Conclusion

The performance benchmarking system provides comprehensive tools to measure, monitor, and optimize the analysis engine. All performance targets are currently being met and exceeded.

For questions or issues, refer to the Performance Benchmarking Report or the main specification document.
