# MeowSEO Analysis Engine - API Documentation

## Table of Contents

1. [Overview](#overview)
2. [Analysis Engine](#analysis-engine)
3. [Utility Functions](#utility-functions)
4. [SEO Analyzers](#seo-analyzers)
5. [Readability Analyzers](#readability-analyzers)
6. [Web Worker API](#web-worker-api)
7. [React Hooks](#react-hooks)
8. [Redux Store](#redux-store)
9. [Type Definitions](#type-definitions)

---

## Overview

This document provides complete API reference for the MeowSEO Analysis Engine. All functions are documented with parameters, return values, and usage examples.

### Module Structure

```
src/
├── analysis/
│   ├── analysis-engine.js          # Main orchestration
│   ├── analyzers/
│   │   ├── seo/                    # SEO analyzers
│   │   └── readability/            # Readability analyzers
│   └── utils/                      # Utility functions
├── gutenberg/
│   ├── workers/
│   │   └── analysis-worker.ts      # Web Worker
│   ├── hooks/
│   │   └── useAnalysis.ts          # Analysis hook
│   └── store/                      # Redux store
```

---

## Analysis Engine

### `analyzeContent(data)`

Main analysis function that orchestrates all 16 analyzers.

**Module**: `src/analysis/analysis-engine.js`

**Parameters**:
- `data` (Object): Analysis input data
  - `content` (string): Post content (HTML)
  - `title` (string): SEO title
  - `description` (string): Meta description
  - `slug` (string): URL slug
  - `keyword` (string): Focus keyword
  - `directAnswer` (string): Direct Answer field
  - `schemaType` (string): Schema Type field

**Returns**: `Object`
```javascript
{
  seoResults: Array<AnalyzerResult>,
  readabilityResults: Array<AnalyzerResult>,
  seoScore: number,              // 0-100
  readabilityScore: number,      // 0-100
  wordCount: number,
  sentenceCount: number,
  paragraphCount: number,
  fleschScore: number,           // 0-100
  keywordDensity: number,        // 0-100 (percentage)
  analysisTimestamp: number      // Unix timestamp
}
```

**Example**:
```javascript
import { analyzeContent } from './analysis/analysis-engine.js';

const result = analyzeContent({
  content: '<p>Your content here...</p>',
  title: 'Your SEO Title',
  description: 'Your meta description',
  slug: 'your-url-slug',
  keyword: 'focus keyword',
  directAnswer: 'Direct answer text...',
  schemaType: 'Article'
});

console.log('SEO Score:', result.seoScore);
console.log('Readability Score:', result.readabilityScore);
```

---

## Utility Functions

### Indonesian Stemmer

#### `stemWord(word)`

Stems an Indonesian word by removing prefixes and suffixes.

**Module**: `src/analysis/utils/indonesian-stemmer.js`

**Parameters**:
- `word` (string): The word to stem

**Returns**: `string` - The stemmed base form

**Example**:
```javascript
import { stemWord } from './utils/indonesian-stemmer.js';

stemWord('membuat');    // → 'buat'
stemWord('dibuat');     // → 'buat'
stemWord('pembuatan');  // → 'buat'
stemWord('berjalan');   // → 'jalan'
```

**Supported Patterns**:
- Prefixes: me-, di-, ber-, ter-, pe-
- Suffixes: -an, -kan, -i, -nya
- Combinations: me-...-kan, di-...-i, etc.

---

### Sentence Splitter

#### `splitSentences(text)`

Splits text into sentences, handling Indonesian abbreviations.

**Module**: `src/analysis/utils/sentence-splitter.js`

**Parameters**:
- `text` (string): Text to split into sentences

**Returns**: `Array<string>` - Array of sentences

**Example**:
```javascript
import { splitSentences } from './utils/sentence-splitter.js';

const text = 'Dr. Ahmad adalah profesor. Dia mengajar di universitas.';
const sentences = splitSentences(text);
// → ['Dr. Ahmad adalah profesor.', 'Dia mengajar di universitas.']
```

**Preserved Abbreviations**:
- dr., prof., dll., dst., dsb., yg., dg.

---

### Syllable Counter

#### `countSyllables(word)`

Counts syllables in a word using Indonesian vowel patterns.

**Module**: `src/analysis/utils/syllable-counter.js`

**Parameters**:
- `word` (string): Word to count syllables in

**Returns**: `number` - Number of syllables

**Example**:
```javascript
import { countSyllables } from './utils/syllable-counter.js';

countSyllables('membuat');     // → 3 (mem-bu-at)
countSyllables('pendidikan');  // → 4 (pen-di-di-kan)
countSyllables('sekolah');     // → 3 (se-ko-lah)
```

**Algorithm**:
- Counts vowel groups: a, e, i, o, u, y
- Handles diphthongs: ai, au, ei, oi, ui, ey, oy

---

### HTML Parser

#### `parseHtml(html)`

Parses HTML content and extracts structured data.

**Module**: `src/analysis/utils/html-parser.js`

**Parameters**:
- `html` (string): HTML content to parse

**Returns**: `Object`
```javascript
{
  text: string,                    // Plain text content
  headings: Array<{
    level: number,                 // 2 or 3
    text: string,
    position: number               // Character position
  }>,
  images: Array<{
    src: string,
    alt: string
  }>,
  links: Array<{
    href: string,
    text: string,
    isInternal: boolean,
    hasNofollow: boolean
  }>,
  paragraphs: Array<{
    text: string,
    wordCount: number
  }>
}
```

**Example**:
```javascript
import { parseHtml } from './utils/html-parser.js';

const html = '<h2>Heading</h2><p>Content...</p>';
const parsed = parseHtml(html);

console.log('Headings:', parsed.headings.length);
console.log('Paragraphs:', parsed.paragraphs.length);
```

---

## SEO Analyzers

All SEO analyzers follow the same interface and return an `AnalyzerResult` object.

### Common Return Structure

```javascript
{
  id: string,                      // Unique analyzer ID
  type: 'good' | 'ok' | 'problem', // Status
  message: string,                 // User-facing message
  score: number,                   // 0, 50, or 100
  weight: number,                  // 0.0-1.0 (percentage)
  details: Object                  // Analyzer-specific data
}
```

---

### `analyzeKeywordInTitle(title, keyword)`

Checks if focus keyword appears in SEO title.

**Module**: `src/analysis/analyzers/seo/keyword-in-title.js`

**Parameters**:
- `title` (string): SEO title
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  keyword: string,
  found: boolean,
  position: number  // -1 if not found
}
```

**Example**:
```javascript
import { analyzeKeywordInTitle } from './analyzers/seo/keyword-in-title.js';

const result = analyzeKeywordInTitle(
  'SEO Optimization Tips for Beginners',
  'seo optimization'
);

console.log(result.type);     // 'good'
console.log(result.score);    // 100
console.log(result.details.found);  // true
```

---

### `analyzeKeywordInDescription(description, keyword)`

Checks if focus keyword appears in meta description.

**Module**: `src/analysis/analyzers/seo/keyword-in-description.js`

**Parameters**:
- `description` (string): Meta description
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  keyword: string,
  found: boolean
}
```

---

### `analyzeKeywordInFirstParagraph(content, keyword)`

Checks if focus keyword appears in first 100 words.

**Module**: `src/analysis/analyzers/seo/keyword-in-first-paragraph.js`

**Parameters**:
- `content` (string): Post content (HTML)
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  keyword: string,
  found: boolean,
  firstParagraphWordCount: number
}
```

---

### `analyzeKeywordDensity(content, keyword)`

Calculates keyword density as percentage of total words.

**Module**: `src/analysis/analyzers/seo/keyword-density.js`

**Parameters**:
- `content` (string): Post content (HTML)
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  density: number,        // Percentage (0-100)
  count: number,          // Keyword occurrences
  totalWords: number
}
```

**Scoring**:
- Good (100): 0.5% - 2.5%
- OK (50): 0.3% - 0.5% or 2.5% - 3.5%
- Problem (0): < 0.3% or > 3.5%

---

### `analyzeKeywordInHeadings(content, keyword)`

Checks if focus keyword appears in H2/H3 headings.

**Module**: `src/analysis/analyzers/seo/keyword-in-headings.js`

**Parameters**:
- `content` (string): Post content (HTML)
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  keyword: string,
  headingCount: number,
  headingsWithKeyword: number,
  found: boolean
}
```

---

### `analyzeKeywordInSlug(slug, keyword)`

Checks if focus keyword appears in URL slug.

**Module**: `src/analysis/analyzers/seo/keyword-in-slug.js`

**Parameters**:
- `slug` (string): URL slug
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  keyword: string,
  slug: string,
  found: boolean
}
```

---

### `analyzeImageAlt(content, keyword)`

Analyzes image alt text coverage and keyword presence.

**Module**: `src/analysis/analyzers/seo/image-alt-analysis.js`

**Parameters**:
- `content` (string): Post content (HTML)
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  totalImages: number,
  withAlt: number,
  withKeyword: number,
  coverage: number  // Percentage with alt text
}
```

**Scoring**:
- Good (100): > 80% with alt text and keyword
- OK (50): > 50% with alt text
- Problem (0): < 50% with alt text

---

### `analyzeInternalLinks(content)`

Analyzes internal linking structure.

**Module**: `src/analysis/analyzers/seo/internal-links-analysis.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  totalLinks: number,
  descriptiveLinks: number,
  genericAnchors: Array<string>  // List of generic anchors found
}
```

**Scoring**:
- Good (100): > 3 descriptive internal links
- OK (50): 1-3 descriptive internal links
- Problem (0): < 1 or generic anchor text

---

### `analyzeOutboundLinks(content)`

Analyzes external linking.

**Module**: `src/analysis/analyzers/seo/outbound-links-analysis.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  totalLinks: number,
  withNofollow: number
}
```

---

### `analyzeContentLength(content)`

Analyzes total word count.

**Module**: `src/analysis/analyzers/seo/content-length.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  wordCount: number
}
```

**Scoring**:
- Good (100): 300-2500 words
- OK (50): 150-300 or 2500-5000 words
- Problem (0): < 150 or > 5000 words

---

### `analyzeDirectAnswer(directAnswer)`

Checks Direct Answer field presence and length.

**Module**: `src/analysis/analyzers/seo/direct-answer-presence.js`

**Parameters**:
- `directAnswer` (string): Direct Answer field value

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  characterCount: number,
  present: boolean
}
```

**Scoring**:
- Good (100): 300-450 characters
- OK (50): Present but outside range
- Problem (0): Missing

---

### `analyzeSchemaPresence(schemaType)`

Checks if schema markup is configured.

**Module**: `src/analysis/analyzers/seo/schema-presence.js`

**Parameters**:
- `schemaType` (string): Schema Type field value

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  schemaType: string,
  configured: boolean
}
```

---

## Readability Analyzers

### `analyzeSentenceLength(content)`

Analyzes average sentence length.

**Module**: `src/analysis/analyzers/readability/sentence-length.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  averageLength: number,
  sentenceCount: number,
  longSentences: number  // Count of sentences > 25 words
}
```

**Scoring**:
- Good (100): < 20 words average
- OK (50): 20-25 words average
- Problem (0): > 25 words average

---

### `analyzeParagraphLength(content)`

Analyzes average paragraph length.

**Module**: `src/analysis/analyzers/readability/paragraph-length.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  averageLength: number,
  paragraphCount: number,
  longParagraphs: number  // Count of paragraphs > 200 words
}
```

**Scoring**:
- Good (100): < 150 words average
- OK (50): 150-200 words average
- Problem (0): > 200 words average

---

### `analyzePassiveVoice(content)`

Detects passive voice usage (Indonesian patterns).

**Module**: `src/analysis/analyzers/readability/passive-voice.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  passivePercentage: number,
  passiveSentences: number,
  totalSentences: number,
  examples: Array<string>  // Sample passive sentences
}
```

**Scoring**:
- Good (100): < 10% passive voice
- OK (50): 10-15% passive voice
- Problem (0): > 15% passive voice

**Detected Patterns**:
- di- prefix: dibuat, diambil
- ter- prefix: terbuat, terambil
- ke-an pattern: keadaan, kebakaran

---

### `analyzeTransitionWords(content)`

Analyzes transition word usage.

**Module**: `src/analysis/analyzers/readability/transition-words.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  transitionPercentage: number,
  sentencesWithTransitions: number,
  totalSentences: number,
  transitionsFound: Array<string>  // List of transitions used
}
```

**Scoring**:
- Good (100): > 30% sentences with transitions
- OK (50): 20-30% sentences with transitions
- Problem (0): < 20% sentences with transitions

---

### `analyzeSubheadingDistribution(content)`

Analyzes spacing between headings.

**Module**: `src/analysis/analyzers/readability/subheading-distribution.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  averageSpacing: number,  // Words between headings
  headingCount: number,
  sections: Array<{
    heading: string,
    wordCount: number
  }>
}
```

**Scoring**:
- Good (100): < 300 words between headings
- OK (50): 300-400 words between headings
- Problem (0): > 400 words between headings

---

### `analyzeFleschReadingEase(content)`

Calculates Flesch Reading Ease score (Indonesian adapted).

**Module**: `src/analysis/analyzers/readability/flesch-reading-ease.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  score: number,              // 0-100
  readabilityLevel: string,   // e.g., "Easy to read"
  sentenceCount: number,
  wordCount: number,
  syllableCount: number
}
```

**Scoring** (informational only, weight = 0%):
- Good (100): 60-100 (easy to read)
- OK (50): 40-60 (moderate difficulty)
- Problem (0): < 40 (difficult to read)

**Formula**:
```
206.835 - 1.015(words/sentences) - 0.684(syllables/words)
```

---

## Web Worker API

### Message Protocol

**Main Thread → Worker**:

```typescript
interface WorkerMessage {
  type: 'ANALYZE';
  payload: {
    content: string;
    title: string;
    description: string;
    slug: string;
    keyword: string;
    directAnswer: string;
    schemaType: string;
  };
}
```

**Worker → Main Thread**:

```typescript
interface WorkerResponse {
  type: 'ANALYSIS_COMPLETE';
  payload: {
    seoResults: Array<AnalyzerResult>;
    readabilityResults: Array<AnalyzerResult>;
    seoScore: number;
    readabilityScore: number;
    wordCount: number;
    sentenceCount: number;
    paragraphCount: number;
    fleschScore: number;
    keywordDensity: number;
    analysisTimestamp: number;
    error?: string;
  };
}
```

### Usage Example

```typescript
// Create worker
const worker = new Worker('./analysis-worker.ts', { type: 'module' });

// Send analysis request
worker.postMessage({
  type: 'ANALYZE',
  payload: {
    content: '<p>Your content...</p>',
    title: 'Your Title',
    description: 'Your description',
    slug: 'your-slug',
    keyword: 'your keyword',
    directAnswer: 'Direct answer...',
    schemaType: 'Article'
  }
});

// Listen for results
worker.addEventListener('message', (event) => {
  if (event.data.type === 'ANALYSIS_COMPLETE') {
    const results = event.data.payload;
    console.log('SEO Score:', results.seoScore);
    console.log('Readability Score:', results.readabilityScore);
  }
});

// Handle errors
worker.addEventListener('error', (error) => {
  console.error('Worker error:', error);
});
```

---

## React Hooks

### `useAnalysis()`

Main hook for triggering and managing content analysis.

**Module**: `src/gutenberg/hooks/useAnalysis.ts`

**Parameters**: None

**Returns**: `void` (updates Redux store)

**Usage**:
```typescript
import { useAnalysis } from './hooks/useAnalysis';

function Sidebar() {
  // Automatically triggers analysis on content changes
  useAnalysis();
  
  return <div>...</div>;
}
```

**Behavior**:
- Subscribes to contentSnapshot from Redux store
- Triggers analysis via Web Worker
- Updates Redux store with results
- Handles errors gracefully
- Cleans up on unmount

---

### `useContentSync()`

Syncs content from core/editor to meowseo/data store.

**Module**: `src/gutenberg/hooks/useContentSync.ts`

**Parameters**: None

**Returns**: `void` (updates Redux store)

**Usage**:
```typescript
import { useContentSync } from './hooks/useContentSync';

function Sidebar() {
  // Syncs content with 800ms debounce
  useContentSync();
  
  return <div>...</div>;
}
```

**Behavior**:
- Reads from core/editor store
- Applies 800ms debounce
- Dispatches to meowseo/data store
- Cleans up timeout on unmount

---

### `useEntityPropBinding(key)`

Binds to WordPress postmeta field.

**Module**: `src/gutenberg/hooks/useEntityPropBinding.ts`

**Parameters**:
- `key` (string): Postmeta key (e.g., '_meowseo_focus_keyword')

**Returns**: `[string, (value: string) => void]`

**Usage**:
```typescript
import { useEntityPropBinding } from './hooks/useEntityPropBinding';

function FocusKeywordInput() {
  const [keyword, setKeyword] = useEntityPropBinding('_meowseo_focus_keyword');
  
  return (
    <TextControl
      value={keyword}
      onChange={setKeyword}
    />
  );
}
```

---

## Redux Store

### Store Name

`'meowseo/data'`

### State Structure

```typescript
interface MeowSEOState {
  seoScore: number;
  readabilityScore: number;
  analysisResults: Array<AnalyzerResult>;
  readabilityResults: Array<AnalyzerResult>;
  wordCount: number;
  sentenceCount: number;
  paragraphCount: number;
  fleschScore: number;
  keywordDensity: number;
  analysisTimestamp: number | null;
  activeTab: 'general' | 'social' | 'schema' | 'advanced';
  isAnalyzing: boolean;
  contentSnapshot: ContentSnapshot;
}
```

### Actions

#### `setAnalysisResults(...)`

Updates analysis results in store.

**Parameters**:
```typescript
setAnalysisResults(
  seoResults: Array<AnalyzerResult>,
  readabilityResults: Array<AnalyzerResult>,
  seoScore: number,
  readabilityScore: number,
  wordCount: number,
  sentenceCount: number,
  paragraphCount: number,
  fleschScore: number,
  keywordDensity: number,
  analysisTimestamp: number
)
```

**Usage**:
```typescript
import { useDispatch } from '@wordpress/data';
import { setAnalysisResults } from './store/actions';

const dispatch = useDispatch('meowseo/data');

dispatch(setAnalysisResults(
  seoResults,
  readabilityResults,
  85,  // seoScore
  72,  // readabilityScore
  1250,  // wordCount
  45,  // sentenceCount
  8,  // paragraphCount
  68,  // fleschScore
  1.2,  // keywordDensity
  Date.now()
));
```

#### `setAnalyzing(isAnalyzing)`

Sets analyzing status.

**Parameters**:
- `isAnalyzing` (boolean): Whether analysis is in progress

**Usage**:
```typescript
dispatch(setAnalyzing(true));
```

#### `updateContentSnapshot(snapshot)`

Updates content snapshot.

**Parameters**:
- `snapshot` (ContentSnapshot): Content snapshot object

#### `setActiveTab(tab)`

Changes active tab.

**Parameters**:
- `tab` (string): Tab name ('general', 'social', 'schema', 'advanced')

### Selectors

#### `getSeoScore()`

Returns SEO score (0-100).

**Usage**:
```typescript
import { useSelect } from '@wordpress/data';

const seoScore = useSelect(
  (select) => select('meowseo/data').getSeoScore(),
  []
);
```

#### `getReadabilityScore()`

Returns readability score (0-100).

#### `getAnalysisResults()`

Returns SEO analyzer results array.

#### `getReadabilityResults()`

Returns readability analyzer results array.

#### `getWordCount()`

Returns word count.

#### `getSentenceCount()`

Returns sentence count.

#### `getParagraphCount()`

Returns paragraph count.

#### `getFleschScore()`

Returns Flesch Reading Ease score.

#### `getKeywordDensity()`

Returns keyword density percentage.

#### `getAnalysisTimestamp()`

Returns analysis timestamp.

#### `getIsAnalyzing()`

Returns analyzing status (boolean).

#### `getContentSnapshot()`

Returns content snapshot object.

#### `getActiveTab()`

Returns active tab name.

---

## Type Definitions

### `AnalyzerResult`

```typescript
interface AnalyzerResult {
  id: string;
  type: 'good' | 'ok' | 'problem';
  message: string;
  score: number;
  weight: number;
  details?: Record<string, any>;
}
```

### `ContentSnapshot`

```typescript
interface ContentSnapshot {
  title: string;
  content: string;
  excerpt: string;
  focusKeyword: string;
  permalink: string;
  postType: string;
}
```

### `AnalysisPayload`

```typescript
interface AnalysisPayload {
  content: string;
  title: string;
  description: string;
  slug: string;
  keyword: string;
  directAnswer: string;
  schemaType: string;
}
```

### `AnalysisResults`

```typescript
interface AnalysisResults {
  seoResults: Array<AnalyzerResult>;
  readabilityResults: Array<AnalyzerResult>;
  seoScore: number;
  readabilityScore: number;
  wordCount: number;
  sentenceCount: number;
  paragraphCount: number;
  fleschScore: number;
  keywordDensity: number;
  analysisTimestamp: number;
  error?: string;
}
```

---

## Error Handling

### Analyzer Errors

All analyzers include try-catch blocks:

```javascript
try {
  results.push(analyzeFeature(data));
} catch (error) {
  results.push({
    id: 'feature-name',
    type: 'problem',
    message: 'Error analyzing feature',
    score: 0,
    weight: 0.08,
    details: { error: error.message }
  });
}
```

### Web Worker Errors

Worker errors return fallback results:

```typescript
worker.addEventListener('error', (error) => {
  console.error('Worker error:', error);
  
  // Return fallback scores
  dispatch(setAnalysisResults(
    [], [], 0, 0, 0, 0, 0, 0, 0, Date.now()
  ));
});
```

### Redux Errors

Redux update failures are logged and skipped:

```typescript
try {
  dispatch(setAnalysisResults(...));
} catch (error) {
  console.error('Failed to dispatch results:', error);
  // Continue without blocking
}
```

---

## Performance Metrics

### Target Metrics

- **Analysis Time**: 1-2 seconds from debounce trigger
- **UI Blocking**: 0ms (runs in Web Worker)
- **Memory Usage**: < 50MB for analysis
- **Debounce Delay**: 800ms

### Monitoring

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

## Version History

### v1.0.0 (Current)

- Initial release
- 16 analyzers (11 SEO + 5 Readability)
- Web Worker architecture
- Indonesian language support
- Redux store integration
- React hooks and components

---

## Additional Resources

- [Developer Guide](./DEVELOPER_GUIDE.md)
- [User Guide](./USER_GUIDE.md)
- [Requirements Document](../.kiro/specs/readability-keyword-analysis-engine/requirements.md)
- [Design Document](../.kiro/specs/readability-keyword-analysis-engine/design.md)
