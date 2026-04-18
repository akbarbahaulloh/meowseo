# MeowSEO Analysis Engine - Developer Guide

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Analyzer Interface](#analyzer-interface)
4. [Adding New Analyzers](#adding-new-analyzers)
5. [Scoring System](#scoring-system)
6. [Indonesian Language Features](#indonesian-language-features)
7. [Web Worker Architecture](#web-worker-architecture)
8. [Code Examples](#code-examples)
9. [Testing](#testing)
10. [Performance Considerations](#performance-considerations)

---

## Overview

The MeowSEO Analysis Engine provides sophisticated content analysis running entirely in the browser via Web Workers. The system delivers 16 specialized analyzers covering SEO and readability metrics, triggered by content changes with 800ms debounce, storing results in Redux, and rendering analysis UI in the Gutenberg editor sidebar.

### Key Features

- **Non-Blocking Analysis**: Web Worker ensures editor UI remains responsive
- **Real-Time Feedback**: 800ms debounce provides immediate analysis without lag
- **Comprehensive Metrics**: 16 specialized analyzers (11 SEO + 5 Readability)
- **Indonesian Optimization**: Language-specific algorithms for stemming, passive voice, and Flesch score
- **Seamless Integration**: Redux store and Gutenberg sidebar integration
- **Extensibility**: Analyzer interface allows easy addition of new metrics

---

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Gutenberg Editor                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  useContentSync Hook (800ms debounce)                      │
│           ↓                                                 │
│  useAnalysis Hook                                          │
│           ↓                                                 │
│  Web Worker (analysis-worker.ts)                           │
│           ↓                                                 │
│  Analysis Engine (analysis-engine.js)                      │
│           ↓                                                 │
│  16 Analyzers (11 SEO + 5 Readability)                    │
│           ↓                                                 │
│  Redux Store (meowseo/data)                                │
│           ↓                                                 │
│  UI Components (ContentScoreWidget, ReadabilityScorePanel) │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

1. **Content Change**: User edits content in Gutenberg editor
2. **Debounce**: `useContentSync` hook waits 800ms after last change
3. **Trigger Analysis**: `useAnalysis` hook sends ANALYZE message to Web Worker
4. **Run Analyzers**: Web Worker runs all 16 analyzers in parallel
5. **Calculate Scores**: Weighted scores calculated for SEO and Readability
6. **Update Store**: Results dispatched to Redux store
7. **Render UI**: Components re-render with new scores and results

---

## Analyzer Interface

All analyzers follow a consistent interface for predictable behavior and easy integration.

### Analyzer Result Structure

```javascript
{
  id: string,              // Unique identifier (e.g., 'keyword-in-title')
  type: 'good' | 'ok' | 'problem',  // Status indicator
  message: string,         // User-facing actionable message
  score: number,           // 0-100 contribution to final score
  weight: number,          // Percentage weight in final score (0.0-1.0)
  details: {               // Optional additional data
    // Analyzer-specific data
  }
}
```

### Analyzer Function Signature

```javascript
/**
 * Analyzer function signature
 *
 * @param {Object} input - Analyzer-specific input data
 * @return {Object} Analyzer result object
 */
function analyzeFeature(input) {
  // Analysis logic
  return {
    id: 'feature-name',
    type: 'good' | 'ok' | 'problem',
    message: 'User-facing message',
    score: 0 | 50 | 100,
    weight: 0.08,  // 8% weight
    details: {
      // Additional data
    }
  };
}
```

### Score Values

- **100**: Good - Feature meets optimization criteria
- **50**: OK - Feature partially meets criteria
- **0**: Problem - Feature does not meet criteria

---

## Adding New Analyzers

Follow these steps to add a new analyzer to the system:

### Step 1: Create Analyzer File

Create a new file in the appropriate directory:
- SEO analyzers: `src/analysis/analyzers/seo/`
- Readability analyzers: `src/analysis/analyzers/readability/`

```javascript
/**
 * My New Analyzer
 *
 * Description of what this analyzer checks.
 *
 * @module analysis/analyzers/seo/my-new-analyzer
 */

/**
 * Analyzes [feature description]
 *
 * @param {string} input - Input data to analyze
 * @return {Object} Analyzer result
 *
 * @example
 * analyzeMyFeature('sample input')
 * // Returns: { id: 'my-feature', type: 'good', ... }
 */
export function analyzeMyFeature(input) {
  // Validation
  if (!input || input.trim() === '') {
    return {
      id: 'my-feature',
      type: 'problem',
      message: 'No input provided',
      score: 0,
      weight: 0.05,  // 5% weight
      details: {}
    };
  }

  // Analysis logic
  const result = performAnalysis(input);

  // Return result based on criteria
  if (result.meetsGoodCriteria) {
    return {
      id: 'my-feature',
      type: 'good',
      message: 'Feature is optimized',
      score: 100,
      weight: 0.05,
      details: { value: result.value }
    };
  } else if (result.meetsOkCriteria) {
    return {
      id: 'my-feature',
      type: 'ok',
      message: 'Feature could be improved',
      score: 50,
      weight: 0.05,
      details: { value: result.value }
    };
  }

  return {
    id: 'my-feature',
    type: 'problem',
    message: 'Feature needs attention',
    score: 0,
    weight: 0.05,
    details: { value: result.value }
  };
}

export default analyzeMyFeature;
```

### Step 2: Export from Index

Add your analyzer to the appropriate index file:

**For SEO analyzers** (`src/analysis/analyzers/seo/index.js`):

```javascript
export { analyzeMyFeature } from './my-new-analyzer.js';

// Update weights configuration
export const SEO_ANALYZER_WEIGHTS = {
  // ... existing weights
  'my-feature': 0.05,  // 5%
  // Total must equal 1.00 (100%)
};
```

**For Readability analyzers** (`src/analysis/analyzers/readability/index.js`):

```javascript
export { analyzeMyFeature } from './my-new-analyzer.js';

// Update weights configuration
export const READABILITY_ANALYZER_WEIGHTS = {
  // ... existing weights
  'my-feature': 0.05,  // 5%
  // Total must equal 1.00 (100%)
};
```

### Step 3: Integrate into Analysis Engine

Update `src/analysis/analysis-engine.js`:

```javascript
import { analyzeMyFeature } from './analyzers/seo/my-new-analyzer.js';

function runAllSeoAnalyzers(data) {
  const results = [];
  
  // ... existing analyzers
  
  try {
    results.push(analyzeMyFeature(data.input));
  } catch (error) {
    results.push({
      id: 'my-feature',
      type: 'problem',
      message: 'Error analyzing feature',
      score: 0,
      weight: 0.05,
      details: { error: error.message }
    });
  }
  
  return results;
}
```

### Step 4: Write Tests

Create test file `src/analysis/analyzers/seo/__tests__/my-new-analyzer.test.js`:

```javascript
import { analyzeMyFeature } from '../my-new-analyzer.js';

describe('analyzeMyFeature', () => {
  it('returns good status when criteria met', () => {
    const result = analyzeMyFeature('optimal input');
    
    expect(result.id).toBe('my-feature');
    expect(result.type).toBe('good');
    expect(result.score).toBe(100);
    expect(result.weight).toBe(0.05);
  });

  it('returns problem status when criteria not met', () => {
    const result = analyzeMyFeature('poor input');
    
    expect(result.type).toBe('problem');
    expect(result.score).toBe(0);
  });

  it('handles empty input gracefully', () => {
    const result = analyzeMyFeature('');
    
    expect(result.type).toBe('problem');
    expect(result.message).toContain('No input');
  });
});
```

### Step 5: Update Documentation

Update this guide and the user guide with information about the new analyzer.

---

## Scoring System

### SEO Score Calculation

The SEO score is calculated as a weighted sum of all SEO analyzer results:

```javascript
SEO_Score = Σ(analyzer_score × analyzer_weight)

Where:
- analyzer_score = 100 (good), 50 (ok), or 0 (problem)
- analyzer_weight = percentage weight (sum = 100%)
```

### Current SEO Analyzer Weights

| Analyzer | Weight | Rationale |
|----------|--------|-----------|
| KeywordInTitle | 8% | Critical for search visibility |
| KeywordInDescription | 7% | Important for click-through rate |
| KeywordInFirstParagraph | 8% | Signals content relevance |
| KeywordDensity | 9% | Core SEO metric |
| KeywordInHeadings | 8% | Content structure signal |
| KeywordInSlug | 7% | URL optimization |
| ImageAltAnalysis | 8% | Accessibility and SEO |
| InternalLinksAnalysis | 8% | Site structure |
| OutboundLinksAnalysis | 7% | Content credibility |
| ContentLength | 9% | Content depth indicator |
| DirectAnswerPresence | 6% | AI Overview optimization |
| SchemaPresence | 5% | Rich results eligibility |
| **Total** | **100%** | |

### Readability Score Calculation

The readability score is calculated similarly:

```javascript
Readability_Score = Σ(analyzer_score × analyzer_weight)
```

### Current Readability Analyzer Weights

| Analyzer | Weight | Rationale |
|----------|--------|-----------|
| SentenceLength | 20% | Core readability metric |
| ParagraphLength | 20% | Visual readability |
| PassiveVoice | 20% | Writing clarity |
| TransitionWords | 20% | Content flow |
| SubheadingDistribution | 20% | Content structure |
| FleschReadingEase | 0% | Informational only |
| **Total** | **100%** | |

### Adjusting Weights

When adding or modifying analyzers, ensure weights sum to 100%:

```javascript
// Example: Adding new analyzer with 5% weight
// Reduce other weights proportionally

const OLD_WEIGHTS = {
  'analyzer-1': 0.20,  // 20%
  'analyzer-2': 0.20,  // 20%
  'analyzer-3': 0.20,  // 20%
  'analyzer-4': 0.20,  // 20%
  'analyzer-5': 0.20,  // 20%
  // Total: 100%
};

const NEW_WEIGHTS = {
  'analyzer-1': 0.19,  // 19%
  'analyzer-2': 0.19,  // 19%
  'analyzer-3': 0.19,  // 19%
  'analyzer-4': 0.19,  // 19%
  'analyzer-5': 0.19,  // 19%
  'new-analyzer': 0.05,  // 5%
  // Total: 100%
};
```

---

## Indonesian Language Features

The analysis engine includes specialized support for Indonesian language content.

### Indonesian Stemmer

Handles morphological variations by removing prefixes and suffixes:

```javascript
import { stemWord } from './utils/indonesian-stemmer.js';

// Prefix removal
stemWord('membuat');  // → 'buat'
stemWord('dibuat');   // → 'buat'
stemWord('berjalan'); // → 'jalan'
stemWord('terbuat');  // → 'buat'

// Suffix removal
stemWord('buatkan');    // → 'buat'
stemWord('pembuatan');  // → 'buat'
stemWord('bukunya');    // → 'buku'

// Combined
stemWord('membuatkan'); // → 'buat'
```

**Supported Patterns:**
- Prefixes: me-, di-, ber-, ter-, pe-
- Suffixes: -an, -kan, -i, -nya
- Nasal insertion: membeli → beli, menjual → jual

### Sentence Splitter

Handles Indonesian abbreviations correctly:

```javascript
import { splitSentences } from './utils/sentence-splitter.js';

const text = 'Dr. Ahmad adalah profesor. Dia mengajar di universitas.';
const sentences = splitSentences(text);
// → ['Dr. Ahmad adalah profesor.', 'Dia mengajar di universitas.']
```

**Preserved Abbreviations:**
- dr., prof., dll., dst., dsb., yg., dg.

### Syllable Counter

Counts syllables using Indonesian vowel patterns:

```javascript
import { countSyllables } from './utils/syllable-counter.js';

countSyllables('membuat');  // → 3 (mem-bu-at)
countSyllables('pendidikan'); // → 4 (pen-di-di-kan)
countSyllables('sekolah');  // → 3 (se-ko-lah)
```

**Algorithm:**
- Counts vowel groups: a, e, i, o, u, y
- Handles diphthongs: ai, au, ei, oi, ui, ey, oy

### Passive Voice Detection

Detects Indonesian passive voice patterns:

```javascript
// Patterns detected:
// 1. di- prefix: dibuat, diambil, ditentukan
// 2. ter- prefix: terbuat, terambil, terpilih
// 3. ke-an pattern: keadaan, kebakaran, keputusan
```

### Transition Words

Comprehensive list of Indonesian transition words:

```javascript
const TRANSITION_WORDS = [
  // Contrast
  'namun', 'tetapi', 'akan tetapi', 'sebaliknya', 'meskipun',
  
  // Cause/Effect
  'oleh karena itu', 'dengan demikian', 'akibatnya', 'sebagai hasilnya',
  
  // Addition
  'selain itu', 'lebih lanjut', 'tambahan lagi', 'juga', 'pula',
  
  // Example
  'misalnya', 'contohnya', 'sebagai contoh', 'seperti',
  
  // Sequence
  'pertama', 'kedua', 'ketiga', 'kemudian', 'selanjutnya', 'akhirnya'
];
```

---

## Web Worker Architecture

### Why Web Workers?

Web Workers run analysis in a separate thread, preventing UI blocking during computation-heavy operations.

**Benefits:**
- Editor remains responsive during analysis
- No lag or freezing during typing
- Better user experience
- Efficient use of multi-core processors

### Worker Communication Protocol

**Main Thread → Worker:**

```javascript
worker.postMessage({
  type: 'ANALYZE',
  payload: {
    content: '...',
    title: '...',
    description: '...',
    slug: '...',
    keyword: '...',
    directAnswer: '...',
    schemaType: '...'
  }
});
```

**Worker → Main Thread:**

```javascript
self.postMessage({
  type: 'ANALYSIS_COMPLETE',
  payload: {
    seoResults: [...],
    readabilityResults: [...],
    seoScore: 85,
    readabilityScore: 72,
    wordCount: 1250,
    sentenceCount: 45,
    paragraphCount: 8,
    fleschScore: 68,
    keywordDensity: 1.2,
    analysisTimestamp: Date.now()
  }
});
```

### Worker Lifecycle

1. **Creation**: Singleton instance created on first analysis
2. **Reuse**: Same instance used for all subsequent analyses
3. **Cleanup**: Worker persists across component lifecycles for performance

### Error Handling

```javascript
// Worker error handler
worker.addEventListener('error', (error) => {
  console.error('Worker error:', error);
  
  // Return fallback scores
  dispatch(setAnalysisResults(
    [], [], 0, 0, 0, 0, 0, 0, 0, Date.now()
  ));
});
```

---

## Code Examples

### Example 1: Using Analysis Results in Components

```typescript
import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '../store';

function MyComponent() {
  const { seoScore, readabilityScore, isAnalyzing } = useSelect(
    (select) => {
      const store = select(STORE_NAME);
      return {
        seoScore: store.getSeoScore(),
        readabilityScore: store.getReadabilityScore(),
        isAnalyzing: store.getIsAnalyzing()
      };
    },
    []
  );

  return (
    <div>
      <h3>SEO Score: {seoScore}</h3>
      <h3>Readability: {readabilityScore}</h3>
      {isAnalyzing && <Spinner />}
    </div>
  );
}
```

### Example 2: Accessing Specific Analyzer Results

```typescript
const { readabilityResults } = useSelect(
  (select) => ({
    readabilityResults: select(STORE_NAME).getReadabilityResults()
  }),
  []
);

// Find specific analyzer
const fleschResult = readabilityResults.find(
  r => r.id === 'flesch-reading-ease'
);

if (fleschResult) {
  console.log('Flesch score:', fleschResult.details.score);
  console.log('Readability level:', fleschResult.details.readabilityLevel);
}
```

### Example 3: Creating a Custom Utility Function

```javascript
/**
 * Extract keywords from text
 *
 * @param {string} text - Text to analyze
 * @param {number} count - Number of keywords to extract
 * @return {Array<string>} Top keywords
 */
export function extractKeywords(text, count = 10) {
  // Remove HTML tags
  const plainText = text.replace(/<[^>]*>/g, ' ');
  
  // Split into words
  const words = plainText.toLowerCase()
    .split(/\s+/)
    .filter(word => word.length > 3);
  
  // Count frequency
  const frequency = {};
  words.forEach(word => {
    const stemmed = stemWord(word);
    frequency[stemmed] = (frequency[stemmed] || 0) + 1;
  });
  
  // Sort by frequency
  return Object.entries(frequency)
    .sort((a, b) => b[1] - a[1])
    .slice(0, count)
    .map(([word]) => word);
}
```

### Example 4: Integrating Analysis with AI Generation

```typescript
import { useSelect } from '@wordpress/data';

function useAIContext() {
  const analysisContext = useSelect(
    (select) => {
      const store = select('meowseo/data');
      return {
        seoScore: store.getSeoScore(),
        readabilityScore: store.getReadabilityScore(),
        keywordDensity: store.getKeywordDensity(),
        fleschScore: store.getFleschScore(),
        wordCount: store.getWordCount()
      };
    },
    []
  );

  // Build AI prompt context
  const promptContext = `
Current content analysis:
- SEO Score: ${analysisContext.seoScore}/100
- Readability: ${analysisContext.readabilityScore}/100
- Keyword Density: ${analysisContext.keywordDensity}%
- Flesch Score: ${analysisContext.fleschScore}
- Word Count: ${analysisContext.wordCount}

Suggestions for improvement:
${analysisContext.seoScore < 70 ? '- Improve SEO optimization' : ''}
${analysisContext.keywordDensity < 0.5 ? '- Increase keyword usage' : ''}
${analysisContext.fleschScore < 60 ? '- Simplify language for better readability' : ''}
  `.trim();

  return promptContext;
}
```

---

## Testing

### Unit Testing Analyzers

```javascript
import { analyzeKeywordInTitle } from '../keyword-in-title.js';

describe('analyzeKeywordInTitle', () => {
  it('returns good status when keyword is present', () => {
    const result = analyzeKeywordInTitle(
      'SEO Optimization Tips',
      'seo optimization'
    );
    
    expect(result.type).toBe('good');
    expect(result.score).toBe(100);
    expect(result.details.found).toBe(true);
  });

  it('handles Indonesian morphological variations', () => {
    const result = analyzeKeywordInTitle(
      'Membuat Website yang Baik',
      'buat website'
    );
    
    expect(result.type).toBe('good');
    expect(result.details.found).toBe(true);
  });

  it('returns problem status when keyword is missing', () => {
    const result = analyzeKeywordInTitle(
      'Content Writing Tips',
      'seo optimization'
    );
    
    expect(result.type).toBe('problem');
    expect(result.score).toBe(0);
  });
});
```

### Integration Testing

```typescript
import { analyzeContent } from '../analysis-engine.js';

describe('analyzeContent', () => {
  it('runs all analyzers and calculates scores', () => {
    const result = analyzeContent({
      content: '<p>Sample content with keyword...</p>',
      title: 'Sample Title with Keyword',
      description: 'Sample description with keyword',
      slug: 'sample-keyword',
      keyword: 'keyword',
      directAnswer: 'This is a direct answer...',
      schemaType: 'Article'
    });

    expect(result.seoResults).toHaveLength(12);
    expect(result.readabilityResults).toHaveLength(6);
    expect(result.seoScore).toBeGreaterThanOrEqual(0);
    expect(result.seoScore).toBeLessThanOrEqual(100);
    expect(result.readabilityScore).toBeGreaterThanOrEqual(0);
    expect(result.readabilityScore).toBeLessThanOrEqual(100);
  });
});
```

### Performance Testing

```javascript
import { performance } from 'perf_hooks';

describe('Performance', () => {
  it('completes analysis within 2 seconds', async () => {
    const start = performance.now();
    
    const result = await analyzeContent({
      content: generateLargeContent(5000), // 5000 words
      title: 'Sample Title',
      keyword: 'test keyword'
    });
    
    const duration = performance.now() - start;
    expect(duration).toBeLessThan(2000); // 2 seconds
  });
});
```

---

## Performance Considerations

### Analysis Speed

**Target**: 1-2 seconds from debounce trigger to results

**Optimization Strategies:**
1. Run analyzers in parallel
2. Use Web Worker for non-blocking execution
3. Cache expensive computations
4. Minimize DOM operations
5. Use efficient algorithms

### Memory Management

**Best Practices:**
1. Clean up event listeners
2. Avoid memory leaks in Web Worker
3. Limit Redux store state size
4. Release resources after analysis

### Debounce Strategy

**800ms debounce** balances responsiveness with performance:
- Prevents analysis on every keystroke
- User sees results within 1-2 seconds of stopping typing
- Reduces unnecessary computations

### Monitoring Performance

```javascript
// Add performance markers
performance.mark('analysis-start');

const result = analyzeContent(data);

performance.mark('analysis-end');
performance.measure('analysis', 'analysis-start', 'analysis-end');

const measure = performance.getEntriesByName('analysis')[0];
console.log(`Analysis took ${measure.duration}ms`);
```

---

## Additional Resources

- [Requirements Document](../.kiro/specs/readability-keyword-analysis-engine/requirements.md)
- [Design Document](../.kiro/specs/readability-keyword-analysis-engine/design.md)
- [User Guide](./USER_GUIDE.md)
- [API Documentation](./API_DOCUMENTATION.md)

---

## Support

For questions or issues:
1. Check existing documentation
2. Review code examples
3. Run tests to verify behavior
4. Consult requirements and design documents
