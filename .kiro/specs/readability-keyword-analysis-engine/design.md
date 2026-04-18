# Design Document: Readability and Advanced Keyword Analysis Engine

## Overview

The Readability and Advanced Keyword Analysis Engine provides sophisticated content analysis running entirely in the browser via Web Workers. The system delivers 20+ specialized analyzers covering SEO and readability metrics, triggered by content changes with 800ms debounce, storing results in Redux, and rendering analysis UI in the Gutenberg editor sidebar.

### Key Design Goals

1. **Non-Blocking Analysis**: Web Worker ensures editor UI remains responsive
2. **Real-Time Feedback**: 800ms debounce provides immediate analysis without lag
3. **Comprehensive Metrics**: 16 specialized analyzers covering SEO and readability
4. **Indonesian Optimization**: Language-specific algorithms for stemming, passive voice, and Flesch score
5. **Seamless Integration**: Redux store and Gutenberg sidebar integration
6. **Extensibility**: Analyzer interface allows easy addition of new metrics

### Design Decisions

| Decision | Rationale |
|----------|-----------|
| Web Worker for Analysis | Prevents UI blocking during computation-heavy analysis |
| 800ms Debounce | Balances responsiveness with performance (avoids analysis on every keystroke) |
| Redux Store | Centralized state management for analysis results and UI state |
| Analyzer Pattern | Each analyzer is independent, testable, and composable |
| Indonesian Stemming | Handles morphological variations for accurate keyword matching |
| Weighted Scoring | Different analyzers contribute different percentages to final scores |

---

## Architecture

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Gutenberg Editor                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         useContentSync Hook (800ms debounce)        │  │
│  │  Provides: contentSnapshot, title, excerpt, slug    │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                  │
│                          ▼                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │      Analysis Engine (Main Thread)                  │  │
│  │  - Subscribes to contentSnapshot                    │  │
│  │  - Applies 800ms debounce                           │  │
│  │  - Spawns Web Worker                                │  │
│  │  - Dispatches Redux actions                         │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                  │
│                          ▼                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         Web Worker (Separate Thread)                │  │
│  │  ┌────────────────────────────────────────────────┐ │  │
│  │  │  SEO Analyzers (11 total)                     │ │  │
│  │  │  - KeywordInTitle                             │ │  │
│  │  │  - KeywordInDescription                       │ │  │
│  │  │  - KeywordInFirstParagraph                    │ │  │
│  │  │  - KeywordDensity                             │ │  │
│  │  │  - KeywordInHeadings                          │ │  │
│  │  │  - KeywordInSlug                              │ │  │
│  │  │  - ImageAltAnalysis                           │ │  │
│  │  │  - InternalLinksAnalysis                      │ │  │
│  │  │  - OutboundLinksAnalysis                      │ │  │
│  │  │  - ContentLength                              │ │  │
│  │  │  - DirectAnswerPresence                       │ │  │
│  │  │  - SchemaPresence                             │ │  │
│  │  └────────────────────────────────────────────────┘ │  │
│  │  ┌────────────────────────────────────────────────┐ │  │
│  │  │  Readability Analyzers (5 total)              │ │  │
│  │  │  - SentenceLength                             │ │  │
│  │  │  - ParagraphLength                            │ │  │
│  │  │  - PassiveVoice (Indonesian patterns)         │ │  │
│  │  │  - TransitionWords (Indonesian)               │ │  │
│  │  │  - SubheadingDistribution                     │ │  │
│  │  │  - FleschReadingEase (Indonesian adapted)     │ │  │
│  │  └────────────────────────────────────────────────┘ │  │
│  │  ┌────────────────────────────────────────────────┐ │  │
│  │  │  Utility Functions                            │ │  │
│  │  │  - Indonesian Stemmer                         │ │  │
│  │  │  - Sentence Splitter                          │ │  │
│  │  │  - HTML Parser                                │ │  │
│  │  │  - Syllable Counter (Indonesian)              │ │  │
│  │  └────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                  │
│                          ▼                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │      Redux Store (meowseo/data)                     │  │
│  │  - readabilityResults: Array<AnalyzerResult>       │  │
│  │  - wordCount: number                               │  │
│  │  - sentenceCount: number                           │  │
│  │  - paragraphCount: number                          │  │
│  │  - fleschScore: number                             │  │
│  │  - keywordDensity: number                          │  │
│  │  - seoScore: number                                │  │
│  │  - readabilityScore: number                        │  │
│  │  - analysisTimestamp: number                       │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                  │
│                          ▼                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │      UI Components                                 │  │
│  │  - ContentScoreWidget (SEO + Readability scores)   │  │
│  │  - ReadabilityScorePanel (Detailed readability)    │  │
│  │  - AnalyzerResultItem (Individual analyzer result) │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow Diagram

```
Content Change
      │
      ▼
useContentSync Hook
      │
      ├─ Debounce 800ms
      │
      ▼
Analysis Engine
      │
      ├─ Extract: title, description, slug, keyword, content
      │
      ▼
Post to Web Worker
      │
      ├─ Run 11 SEO Analyzers (parallel)
      │ ├─ KeywordInTitle
      │ ├─ KeywordInDescription
      │ ├─ KeywordInFirstParagraph
      │ ├─ KeywordDensity
      │ ├─ KeywordInHeadings
      │ ├─ KeywordInSlug
      │ ├─ ImageAltAnalysis
      │ ├─ InternalLinksAnalysis
      │ ├─ OutboundLinksAnalysis
      │ ├─ ContentLength
      │ ├─ DirectAnswerPresence
      │ └─ SchemaPresence
      │
      ├─ Run 5 Readability Analyzers (parallel)
      │ ├─ SentenceLength
      │ ├─ ParagraphLength
      │ ├─ PassiveVoice
      │ ├─ TransitionWords
      │ ├─ SubheadingDistribution
      │ └─ FleschReadingEase
      │
      ├─ Calculate SEO Score (weighted sum)
      │
      ├─ Calculate Readability Score (weighted sum)
      │
      ▼
Return Results to Main Thread
      │
      ▼
Dispatch Redux Actions
      │
      ├─ setAnalysisResults(seoResults, readabilityResults)
      │ ├─ readabilityResults: Array<AnalyzerResult>
      │ ├─ wordCount: number
      │ ├─ sentenceCount: number
      │ ├─ paragraphCount: number
      │ ├─ fleschScore: number
      │ ├─ keywordDensity: number
      │ ├─ seoScore: number
      │ ├─ readabilityScore: number
      │ └─ analysisTimestamp: number
      │
      ▼
Redux Store Updated
      │
      ▼
Components Re-render
      │
      ├─ ContentScoreWidget
      └─ ReadabilityScorePanel
```

---

## Analyzer Specifications

### SEO Analyzers (11 total, 100% weight distributed)

#### 1. KeywordInTitle (8% weight)
- **Input**: SEO title, focus keyword
- **Process**: Check if keyword appears in title (case-insensitive, stemmed)
- **Output**: 
  - good (100%): keyword present
  - problem (0%): keyword missing
- **Message**: "Focus keyword found in title" or "Add focus keyword to title"

#### 2. KeywordInDescription (7% weight)
- **Input**: Meta description, focus keyword
- **Process**: Check if keyword appears in description
- **Output**:
  - good (100%): keyword present
  - problem (0%): keyword missing
- **Message**: "Focus keyword found in description" or "Add focus keyword to description"

#### 3. KeywordInFirstParagraph (8% weight)
- **Input**: First 100 words of content, focus keyword
- **Process**: Extract first 100 words, check for keyword
- **Output**:
  - good (100%): keyword present in first 100 words
  - problem (0%): keyword missing
- **Message**: "Focus keyword found in first 100 words" or "Add focus keyword to first paragraph"

#### 4. KeywordDensity (9% weight)
- **Input**: Content, focus keyword
- **Process**: 
  1. Count total words in content
  2. Count keyword occurrences (stemmed)
  3. Calculate: (keyword_count / total_words) × 100
- **Output**:
  - good (100%): 0.5-2.5% density
  - ok (50%): 0.3-0.5% or 2.5-3.5% density
  - problem (0%): <0.3% or >3.5% density
- **Details**: { density: number, count: number, totalWords: number }
- **Message**: "Keyword density is optimal (1.2%)" or "Increase keyword usage" or "Reduce keyword usage"

#### 5. KeywordInHeadings (8% weight)
- **Input**: H2/H3 headings, focus keyword
- **Process**: Extract all H2/H3 headings, check if keyword appears in any
- **Output**:
  - good (100%): keyword in at least one heading
  - ok (50%): keyword in content but not headings
  - problem (0%): keyword missing from content
- **Details**: { headingCount: number, headingsWithKeyword: number }
- **Message**: "Focus keyword found in headings" or "Add focus keyword to at least one heading"

#### 6. KeywordInSlug (7% weight)
- **Input**: URL slug, focus keyword
- **Process**: Check if keyword appears in slug
- **Output**:
  - good (100%): keyword present
  - problem (0%): keyword missing
- **Message**: "Focus keyword found in URL slug" or "Add focus keyword to URL slug"

#### 7. ImageAltAnalysis (8% weight)
- **Input**: Images in content, focus keyword
- **Process**:
  1. Extract all images
  2. Count images with alt text
  3. Count alt texts containing keyword
  4. Calculate coverage percentage
- **Output**:
  - good (100%): >80% images have alt text with keyword
  - ok (50%): >50% images have alt text
  - problem (0%): <50% images have alt text
- **Details**: { totalImages: number, withAlt: number, withKeyword: number }
- **Message**: "All images have descriptive alt text" or "Add alt text to images"

#### 8. InternalLinksAnalysis (8% weight)
- **Input**: Internal links in content
- **Process**:
  1. Extract all internal links
  2. Check for descriptive anchor text (not "click here", "read more", etc.)
  3. Count links with descriptive text
- **Output**:
  - good (100%): >3 internal links with descriptive text
  - ok (50%): 1-3 internal links with descriptive text
  - problem (0%): <1 internal link or generic anchor text
- **Details**: { totalLinks: number, descriptiveLinks: number }
- **Message**: "Good internal linking structure" or "Add more internal links with descriptive text"

#### 9. OutboundLinksAnalysis (7% weight)
- **Input**: External links in content
- **Process**:
  1. Extract all external links
  2. Check for nofollow attribute
  3. Verify links are present
- **Output**:
  - good (100%): external links present with proper attribution
  - ok (50%): external links exist but lack nofollow
  - problem (0%): no external links
- **Details**: { totalLinks: number, withNofollow: number }
- **Message**: "Good external linking" or "Add external links to authoritative sources"

#### 10. ContentLength (9% weight)
- **Input**: Post content
- **Process**: Count total words in content
- **Output**:
  - good (100%): 300-2500 words
  - ok (50%): 150-300 or 2500-5000 words
  - problem (0%): <150 or >5000 words
- **Details**: { wordCount: number }
- **Message**: "Content length is optimal (1,250 words)" or "Expand content to at least 300 words"

#### 11. DirectAnswerPresence (6% weight)
- **Input**: Direct Answer field value
- **Process**: Check if Direct Answer is populated and within character range
- **Output**:
  - good (100%): present and 300-450 characters
  - ok (50%): present but outside character range
  - problem (0%): missing
- **Details**: { characterCount: number }
- **Message**: "Direct Answer configured for AI Overviews" or "Add Direct Answer (300-450 characters)"

#### 12. SchemaPresence (5% weight)
- **Input**: Schema Type field value
- **Process**: Check if Schema Type is configured
- **Output**:
  - good (100%): Schema Type set to valid type
  - problem (0%): Schema Type missing
- **Details**: { schemaType: string }
- **Message**: "Schema type configured (Article)" or "Configure schema type for rich results"

### Readability Analyzers (5 total, 100% weight distributed)

#### 1. SentenceLength (20% weight)
- **Input**: Post content
- **Process**:
  1. Split content into sentences (handling Indonesian abbreviations)
  2. Count words in each sentence
  3. Calculate average: total_words / sentence_count
- **Output**:
  - good (100%): <20 words average
  - ok (50%): 20-25 words average
  - problem (0%): >25 words average
- **Details**: { averageLength: number, sentenceCount: number }
- **Message**: "Sentences are concise (avg 15 words)" or "Shorten sentences for better readability"

#### 2. ParagraphLength (20% weight)
- **Input**: Post content
- **Process**:
  1. Extract all paragraphs
  2. Count words in each paragraph
  3. Calculate average: total_words / paragraph_count
- **Output**:
  - good (100%): <150 words average
  - ok (50%): 150-200 words average
  - problem (0%): >200 words average
- **Details**: { averageLength: number, paragraphCount: number }
- **Message**: "Paragraphs are well-sized (avg 120 words)" or "Break up long paragraphs"

#### 3. PassiveVoice (20% weight)
- **Input**: Post content
- **Process**:
  1. Split into sentences
  2. Detect passive voice using Indonesian patterns:
     - di- prefix (dibuat, diambil, ditentukan)
     - ter- prefix (terbuat, terambil, terpilih)
     - ke-an pattern (keadaan, kebakaran, keputusan)
  3. Calculate: (passive_sentences / total_sentences) × 100
- **Output**:
  - good (100%): <10% passive voice
  - ok (50%): 10-15% passive voice
  - problem (0%): >15% passive voice
- **Details**: { passivePercentage: number, passiveSentences: number }
- **Message**: "Good use of active voice (8% passive)" or "Use more active voice"

#### 4. TransitionWords (20% weight)
- **Input**: Post content
- **Process**:
  1. Split into sentences
  2. Check each sentence for Indonesian transition words:
     - Contrast: namun, tetapi, akan tetapi, sebaliknya, meskipun
     - Cause/Effect: oleh karena itu, dengan demikian, akibatnya, sebagai hasilnya
     - Addition: selain itu, lebih lanjut, tambahan lagi, juga, pula
     - Example: misalnya, contohnya, sebagai contoh, seperti
     - Sequence: pertama, kedua, ketiga, kemudian, selanjutnya, akhirnya
  3. Calculate: (sentences_with_transitions / total_sentences) × 100
- **Output**:
  - good (100%): >30% sentences with transition words
  - ok (50%): 20-30% sentences with transition words
  - problem (0%): <20% sentences with transition words
- **Details**: { transitionPercentage: number, sentencesWithTransitions: number }
- **Message**: "Good use of transition words (35%)" or "Add more transition words for flow"

#### 5. SubheadingDistribution (20% weight)
- **Input**: Post content with H2/H3 headings
- **Process**:
  1. Extract all H2/H3 headings with their positions
  2. Calculate word count between headings
  3. Calculate average: total_words / heading_count
- **Output**:
  - good (100%): headings every <300 words
  - ok (50%): headings every 300-400 words
  - problem (0%): headings every >400 words
- **Details**: { averageSpacing: number, headingCount: number }
- **Message**: "Good subheading distribution (avg 250 words)" or "Add more subheadings"

#### 6. FleschReadingEase (0% weight - informational only)
- **Input**: Post content
- **Process**:
  1. Count sentences (handling Indonesian abbreviations)
  2. Count words
  3. Count syllables using Indonesian algorithm:
     - Count vowel groups (a, e, i, o, u, y)
     - Handle diphthongs (ai, au, ei, oi, ui, ey, oy)
  4. Apply formula: 206.835 - 1.015(words/sentences) - 0.684(syllables/words)
  5. Clamp to 0-100 scale
- **Output**: Score 0-100
  - good (100%): 60-100 (easy to read)
  - ok (50%): 40-60 (moderate difficulty)
  - problem (0%): <40 (difficult to read)
- **Details**: { score: number, readabilityLevel: string }
- **Message**: "Flesch score: 72 (Easy to read)" or "Flesch score: 35 (Difficult to read)"

---

## Scoring System

### SEO Score Calculation

```
SEO_Score = (Σ(analyzer_score × analyzer_weight)) / 100

Where:
- analyzer_score = 100 (good), 50 (ok), or 0 (problem)
- analyzer_weight = percentage weight (sum = 100)

Weights:
- KeywordInTitle: 8%
- KeywordInDescription: 7%
- KeywordInFirstParagraph: 8%
- KeywordDensity: 9%
- KeywordInHeadings: 8%
- KeywordInSlug: 7%
- ImageAltAnalysis: 8%
- InternalLinksAnalysis: 8%
- OutboundLinksAnalysis: 7%
- ContentLength: 9%
- DirectAnswerPresence: 6%
- SchemaPresence: 5%
Total: 100%

Example:
- KeywordInTitle: good (100) × 0.08 = 8
- KeywordDensity: ok (50) × 0.09 = 4.5
- ContentLength: good (100) × 0.09 = 9
- ... (other analyzers)
- Total: 85 / 100 = 85 SEO Score
```

### Readability Score Calculation

```
Readability_Score = (Σ(analyzer_score × analyzer_weight)) / 100

Where:
- analyzer_score = 100 (good), 50 (ok), or 0 (problem)
- analyzer_weight = percentage weight (sum = 100)
- FleschReadingEase does not contribute to score (informational only)

Weights:
- SentenceLength: 20%
- ParagraphLength: 20%
- PassiveVoice: 20%
- TransitionWords: 20%
- SubheadingDistribution: 20%
Total: 100%

Example:
- SentenceLength: good (100) × 0.20 = 20
- ParagraphLength: ok (50) × 0.20 = 10
- PassiveVoice: good (100) × 0.20 = 20
- TransitionWords: problem (0) × 0.20 = 0
- SubheadingDistribution: good (100) × 0.20 = 20
- Total: 70 / 100 = 70 Readability Score
```

---

## Implementation Details

### File Structure

```
src/
├── analysis/
│   ├── compute-analysis.js (REPLACED - new analyzer-based system)
│   ├── analyzers/
│   │   ├── seo/
│   │   │   ├── keyword-in-title.js
│   │   │   ├── keyword-in-description.js
│   │   │   ├── keyword-in-first-paragraph.js
│   │   │   ├── keyword-density.js
│   │   │   ├── keyword-in-headings.js
│   │   │   ├── keyword-in-slug.js
│   │   │   ├── image-alt-analysis.js
│   │   │   ├── internal-links-analysis.js
│   │   │   ├── outbound-links-analysis.js
│   │   │   ├── content-length.js
│   │   │   ├── direct-answer-presence.js
│   │   │   └── schema-presence.js
│   │   ├── readability/
│   │   │   ├── sentence-length.js
│   │   │   ├── paragraph-length.js
│   │   │   ├── passive-voice.js
│   │   │   ├── transition-words.js
│   │   │   ├── subheading-distribution.js
│   │   │   └── flesch-reading-ease.js
│   │   └── index.js (exports all analyzers)
│   ├── utils/
│   │   ├── indonesian-stemmer.js
│   │   ├── sentence-splitter.js
│   │   ├── html-parser.js
│   │   ├── syllable-counter.js
│   │   └── index.js
│   └── analysis-engine.js (orchestrates analyzers)
├── workers/
│   └── analysis-worker.js (Web Worker entry point)
├── hooks/
│   └── useAnalysis.js (NEW - subscribes to contentSnapshot, triggers analysis)
├── components/
│   ├── ContentScoreWidget.tsx (UPDATED - uses new analyzer results)
│   ├── ReadabilityScorePanel.tsx (NEW - displays readability analysis)
│   └── AnalyzerResultItem.tsx (NEW - displays individual analyzer result)
└── store/
    ├── index.js (UPDATED - add analysis state)
    ├── actions.ts (UPDATED - add analysis actions)
    ├── reducer.ts (UPDATED - add analysis reducer)
    └── selectors.ts (UPDATED - add analysis selectors)
```

### Redux Store Updates

```javascript
// Add to DEFAULT_STATE
analysis: {
  seoScore: 0,
  readabilityScore: 0,
  readabilityResults: [], // Array<AnalyzerResult>
  wordCount: 0,
  sentenceCount: 0,
  paragraphCount: 0,
  fleschScore: 0,
  keywordDensity: 0,
  analysisTimestamp: null,
}

// Add action
setAnalysisResults(results) {
  return {
    type: 'SET_ANALYSIS_RESULTS',
    payload: results
  }
}

// Add selector
getAnalysisResults(state) {
  return state.analysis
}
```

### Analyzer Result Structure

```javascript
{
  id: 'keyword-in-title',           // unique identifier
  type: 'good' | 'ok' | 'problem',  // status
  message: 'Focus keyword found in title',  // user-facing message
  score: 100,                       // 0-100 contribution to final score
  weight: 0.08,                     // percentage weight in final score
  details: {                        // optional additional data
    keyword: 'seo optimization',
    found: true,
    position: 0
  }
}
```

### Web Worker Communication

```javascript
// Main thread sends analysis request
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
})

// Web Worker returns results
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
})
```

### Indonesian Language Utilities

#### Stemmer
- Removes prefixes: me-, di-, ber-, ter-, pe-
- Removes suffixes: -an, -kan, -i, -nya
- Handles combinations: me-...-kan, di-...-i, etc.
- Returns base form for keyword matching

#### Sentence Splitter
- Splits on: . ! ?
- Preserves abbreviations: dr., prof., dll., dst., dsb., yg., dg.
- Handles ellipsis: ...
- Returns array of sentences

#### Syllable Counter
- Counts vowel groups: a, e, i, o, u, y
- Handles diphthongs: ai, au, ei, oi, ui, ey, oy
- Returns syllable count for Flesch calculation

#### HTML Parser
- Extracts text content
- Extracts headings (H2, H3)
- Extracts images with alt text
- Extracts links (internal/external)
- Extracts paragraphs
- Returns structured data

---

## Integration Points

### 1. useContentSync Hook Integration
- Analysis Engine subscribes to contentSnapshot
- Applies 800ms debounce
- Extracts: content, title, excerpt, slug, keyword, directAnswer, schemaType
- Triggers analysis on change

### 2. Redux Store Integration
- Dispatch setAnalysisResults action with all analysis data
- Components subscribe to analysis selectors
- Real-time updates as analysis completes

### 3. ContentScoreWidget Update
- Display SEO_Score and Readability_Score prominently
- Show score breakdown by analyzer category
- Use color coding: green (good), yellow (ok), red (problem)
- Update in real-time

### 4. ReadabilityScorePanel (New Component)
- Display all 5 readability analyzer results
- Show each analyzer's status and message
- Display Flesch score and interpretation
- Display wordCount, sentenceCount, paragraphCount
- Update in real-time

### 5. AI Module Integration
- AI_Generation_Module accesses current analysis results from Redux
- Uses SEO_Score, Readability_Score, keywordDensity, fleschScore in prompts
- Includes analysis context in generation strategy
- Example: "Current SEO score is 72, focus on improving keyword density"

---

## Performance Considerations

### Analysis Speed
- Target: 1-2 seconds from debounce trigger to results
- Web Worker runs in parallel with main thread
- Analyzers run in parallel within Web Worker
- No blocking of editor UI

### Memory Management
- Web Worker cleans up after analysis
- Redux store limited to analysis results only
- No memory leaks from repeated analysis
- Component cleanup on unmount

### Debounce Strategy
- 800ms delay from last content change
- Prevents analysis on every keystroke
- Balances responsiveness with performance
- User sees results within 1-2 seconds of stopping typing

---

## Error Handling

### Web Worker Errors
- Catch errors in Web Worker
- Return error message to main thread
- Log error for debugging
- Display fallback scores (0) to user
- Continue without blocking editor

### Analysis Failures
- Individual analyzer failures don't block others
- Return 'problem' status for failed analyzers
- Log error with analyzer name
- Continue with other analyzers

### Redux Update Failures
- Retry Redux dispatch
- Log error
- Skip update if retry fails
- Don't prevent post save

---

## Testing Strategy

### Unit Tests
- Each analyzer tested independently
- Test with various content types
- Test edge cases (empty content, no keyword, etc.)
- Test Indonesian language features

### Integration Tests
- Web Worker communication
- Redux store updates
- Component rendering
- Real-time updates

### Performance Tests
- Analysis speed benchmarks
- Memory usage monitoring
- Debounce effectiveness
- UI responsiveness

