# Analysis Engine Developer Documentation

## Overview

The Analysis Engine is a sophisticated content analysis system that provides real-time SEO and readability analysis for WordPress posts. It runs 16 specialized analyzers (11 SEO + 5 Readability) in a Web Worker to avoid blocking the editor UI.

## Architecture

### Components

1. **Analysis Engine** (`src/analysis/analysis-engine.js`)
   - Orchestrates all 16 analyzers
   - Calculates weighted scores
   - Extracts metadata
   - Handles error recovery

2. **Web Worker** (`src/gutenberg/workers/analysis-worker.ts`)
   - Runs analysis in background thread
   - Communicates via postMessage API
   - Prevents UI blocking

3. **useAnalysis Hook** (`src/gutenberg/hooks/useAnalysis.ts`)
   - Integrates with useContentSync hook
   - Manages Web Worker lifecycle
   - Dispatches Redux actions

4. **Redux Store** (`src/gutenberg/store/`)
   - Stores analysis results
   - Manages UI state
   - Provides selectors for components

5. **React Components** (`src/gutenberg/components/`)
   - ContentScoreWidget: Displays overall scores
   - ReadabilityScorePanel: Shows detailed readability analysis
   - AnalyzerResultItem: Individual analyzer result display

### Data Flow

```
Content Change (useContentSync)
    ↓
useAnalysis Hook (800ms debounce)
    ↓
Web Worker (ANALYZE message)
    ↓
Analysis Engine (16 analyzers)
    ↓
Web Worker (ANALYSIS_COMPLETE message)
    ↓
Redux Store (setAnalysisResults)
    ↓
React Components (render results)
```

## Analyzer System

### SEO Analyzers (11 total, 100% weight)

| Analyzer | Weight | Purpose |
|----------|--------|---------|
| KeywordInTitle | 8% | Check if keyword appears in title |
| KeywordInDescription | 7% | Check if keyword appears in meta description |
| KeywordInFirstParagraph | 8% | Check if keyword appears in first 100 words |
| KeywordDensity | 9% | Measure keyword frequency (optimal: 0.5-2.5%) |
| KeywordInHeadings | 8% | Check if keyword appears in H2/H3 headings |
| KeywordInSlug | 7% | Check if keyword appears in URL slug |
| ImageAltAnalysis | 8% | Check alt text coverage and keyword presence |
| InternalLinksAnalysis | 8% | Check for descriptive internal links |
| OutboundLinksAnalysis | 7% | Check for external links with attribution |
| ContentLength | 9% | Verify content length (optimal: 300-2500 words) |
| DirectAnswerPresence | 6% | Check if Direct Answer field is populated |
| SchemaPresence | 5% | Check if Schema Type is configured |

### Readability Analyzers (5 total, 100% weight)

| Analyzer | Weight | Purpose |
|----------|--------|---------|
| SentenceLength | 20% | Measure average sentence length (optimal: <20 words) |
| ParagraphLength | 20% | Measure average paragraph length (optimal: <150 words) |
| PassiveVoice | 20% | Detect passive voice usage (optimal: <10%) |
| TransitionWords | 20% | Check for transition words (target: >30% of sentences) |
| SubheadingDistribution | 20% | Check spacing between headings (optimal: <300 words) |
| FleschReadingEase | 0% | Calculate readability score (informational only) |

### Analyzer Result Structure

```javascript
{
  id: 'analyzer-id',                    // Unique identifier
  type: 'good' | 'ok' | 'problem',     // Status
  message: 'Human-readable message',    // User-facing message
  score: 0-100,                         // Numeric score
  weight: 0.08,                         // Contribution to overall score
  details: {                            // Analyzer-specific data
    // Varies by analyzer
  }
}
```

## Score Calculation

### SEO Score
```
SEO_Score = (Σ(analyzer_score × weight)) / 100
```

### Readability Score
```
Readability_Score = (Σ(analyzer_score × weight)) / 100
```

Both scores range from 0-100, where:
- 70-100: Good
- 40-69: Needs improvement
- 0-39: Needs significant work

## Adding New Analyzers

### Step 1: Create Analyzer File

Create a new file in `src/analysis/analyzers/seo/` or `src/analysis/analyzers/readability/`:

```javascript
/**
 * MyAnalyzer
 * 
 * Description of what this analyzer does.
 * 
 * @param {string} content - Post content (HTML)
 * @param {string} keyword - Focus keyword
 * @returns {Object} Analyzer result
 */
export function analyzeMyFeature(content, keyword) {
  // Analyze content
  const score = calculateScore(content, keyword);
  
  return {
    id: 'my-feature',
    type: score === 100 ? 'good' : score > 50 ? 'ok' : 'problem',
    message: `My feature analysis: ${score}%`,
    score,
    weight: 0.08, // Set appropriate weight
    details: {
      // Include relevant details
    },
  };
}
```

### Step 2: Update Index File

Add to `src/analysis/analyzers/seo/index.js` or `src/analysis/analyzers/readability/index.js`:

```javascript
import { analyzeMyFeature } from './my-feature.js';

export const SEO_ANALYZER_WEIGHTS = {
  // ... existing analyzers
  'my-feature': 0.08,
};

export function runAllSeoAnalyzers(data) {
  const results = [];
  
  // ... existing analyzers
  
  try {
    results.push(analyzeMyFeature(data.content, data.keyword));
  } catch (error) {
    console.error('Error in MyFeature analyzer:', error);
    results.push({
      id: 'my-feature',
      type: 'problem',
      message: 'Error analyzing my feature',
      score: 0,
      weight: SEO_ANALYZER_WEIGHTS['my-feature'],
      details: { error: error.message },
    });
  }
  
  return results;
}
```

### Step 3: Update Analysis Engine

Update `src/analysis/analysis-engine.js` to include the new analyzer in the orchestration.

### Step 4: Write Tests

Create tests in `src/analysis/analyzers/seo/__tests__/my-feature.test.js`:

```javascript
import { analyzeMyFeature } from '../my-feature.js';

describe('MyFeature Analyzer', () => {
  it('should return good score for optimal content', () => {
    const result = analyzeMyFeature('<p>Test content</p>', 'keyword');
    
    expect(result.id).toBe('my-feature');
    expect(result.score).toBeGreaterThanOrEqual(0);
    expect(result.score).toBeLessThanOrEqual(100);
  });
});
```

## Web Worker Communication Protocol

### ANALYZE Message (Main Thread → Worker)

```javascript
{
  type: 'ANALYZE',
  payload: {
    content: '<p>Post content HTML</p>',
    title: 'SEO Title',
    description: 'Meta description',
    slug: 'post-slug',
    keyword: 'focus keyword',
    directAnswer: 'Direct answer text',
    schemaType: 'Article',
  }
}
```

### ANALYSIS_COMPLETE Message (Worker → Main Thread)

```javascript
{
  type: 'ANALYSIS_COMPLETE',
  payload: {
    seoResults: [...],
    readabilityResults: [...],
    seoScore: 75,
    readabilityScore: 80,
    wordCount: 500,
    sentenceCount: 25,
    paragraphCount: 5,
    fleschScore: 65,
    keywordDensity: 1.5,
    analysisTimestamp: 1234567890,
    error: undefined, // Only present if error occurred
  }
}
```

## Redux Store Structure

### State

```typescript
interface MeowSEOState {
  // Analysis Results
  seoResults: AnalyzerResult[];
  readabilityResults: AnalyzerResult[];
  
  // Scores
  seoScore: number;
  readabilityScore: number;
  
  // Metadata
  wordCount: number;
  sentenceCount: number;
  paragraphCount: number;
  fleschScore: number;
  keywordDensity: number;
  
  // State Management
  analysisTimestamp: number | null;
  isAnalyzing: boolean;
}
```

### Actions

```javascript
// Dispatch analysis results
dispatch(setAnalysisResults(
  seoResults,
  readabilityResults,
  seoScore,
  readabilityScore,
  wordCount,
  sentenceCount,
  paragraphCount,
  fleschScore,
  keywordDensity,
  analysisTimestamp
));

// Set analyzing flag
dispatch(setAnalyzing(true));
```

### Selectors

```javascript
// Get analysis results
const seoResults = select('meowseo/data').getSeoResults();
const readabilityResults = select('meowseo/data').getReadabilityResults();

// Get scores
const seoScore = select('meowseo/data').getSeoScore();
const readabilityScore = select('meowseo/data').getReadabilityScore();

// Get metadata
const wordCount = select('meowseo/data').getWordCount();
const sentenceCount = select('meowseo/data').getSentenceCount();
const paragraphCount = select('meowseo/data').getParagraphCount();
const fleschScore = select('meowseo/data').getFleschScore();
const keywordDensity = select('meowseo/data').getKeywordDensity();

// Get state
const isAnalyzing = select('meowseo/data').getIsAnalyzing();
const timestamp = select('meowseo/data').getAnalysisTimestamp();
```

## Utility Functions

### Indonesian Language Support

```javascript
import { stemWord } from './utils/indonesian-stemmer.js';
import { splitSentences } from './utils/sentence-splitter.js';
import { countSyllables } from './utils/syllable-counter.js';
import { parseHtml } from './utils/html-parser.js';

// Stem Indonesian word
const stemmed = stemWord('mengoptimalkan'); // Returns 'optim'

// Split sentences
const sentences = splitSentences('Ini adalah kalimat. Ini adalah kalimat lain.');

// Count syllables
const syllables = countSyllables('optimasi'); // Returns 4

// Parse HTML
const parsed = parseHtml('<p>Content</p><h2>Heading</h2>');
// Returns: { text, headings, images, links, paragraphs }
```

### Performance Benchmarking

```javascript
import {
  benchmarkAnalysisEngine,
  generateTestData,
  formatBenchmarkResults,
} from './utils/performance-benchmark.js';

// Generate test data
const testData = generateTestData(1000); // 1000 words

// Benchmark analysis
const summary = benchmarkAnalysisEngine(analyzeContent, testData, 5);

// Format results
console.log(formatBenchmarkResults(summary));
```

## Performance Targets

- **Analysis Time**: 1-2 seconds from debounce trigger
- **Throughput**: 500+ words per second
- **Memory**: <10MB delta per analysis
- **UI Responsiveness**: No blocking during analysis
- **Large Content**: Handle 5000+ words efficiently

## Error Handling

### Analyzer Failures

If an individual analyzer fails:
1. Error is caught and logged
2. Fallback result with score 0 is returned
3. Other analyzers continue running
4. Overall scores are calculated from successful analyzers

### Web Worker Failures

If Web Worker fails:
1. Error is logged
2. Fallback scores (0) are returned
3. Analysis is skipped
4. UI remains responsive

### Redux Update Failures

If Redux update fails:
1. Error is logged
2. Analysis results are not stored
3. Components show previous results
4. Post save is not blocked

## Testing

### Unit Tests

Test individual analyzers:
```bash
npm test -- --testPathPattern="analyzers" --runInBand
```

### Integration Tests

Test complete analysis flow:
```bash
npm test -- --testPathPattern="integration" --runInBand
```

### Performance Tests

Test performance characteristics:
```bash
npm test -- --testPathPattern="performance" --runInBand
```

## Debugging

### Enable Logging

Add to `useAnalysis.ts`:
```javascript
const DEBUG = true;

if (DEBUG) {
  console.log('Analysis triggered:', contentSnapshot);
  console.log('Analysis results:', results);
}
```

### Monitor Web Worker

In browser DevTools:
1. Open Sources tab
2. Find "analysis-worker.ts" in Workers section
3. Set breakpoints to debug

### Check Redux State

In browser console:
```javascript
wp.data.select('meowseo/data').getState();
```

## Optimization Tips

1. **Caching**: Cache parsed HTML to avoid re-parsing
2. **Lazy Loading**: Load analyzers on demand
3. **Memoization**: Cache analyzer results for identical content
4. **Batching**: Batch multiple analyses together
5. **Profiling**: Use performance.mark() to profile analyzers

## Common Issues

### Analysis Not Triggering

- Check if useContentSync is providing content
- Verify 800ms debounce is working
- Check browser console for errors

### Scores Not Updating

- Verify Redux store is receiving actions
- Check if components are subscribed to selectors
- Verify Web Worker is returning results

### Performance Issues

- Profile with DevTools Performance tab
- Check for memory leaks with DevTools Memory tab
- Verify Web Worker is not blocking main thread

## Resources

- [Web Workers API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Workers_API)
- [Redux Documentation](https://redux.js.org/)
- [WordPress Data Module](https://developer.wordpress.org/block-editor/reference-guides/data/)
- [Flesch Reading Ease](https://en.wikipedia.org/wiki/Flesch%E2%80%93Kincaid_readability_tests)
- [Indonesian Language Processing](https://en.wikipedia.org/wiki/Indonesian_language)
