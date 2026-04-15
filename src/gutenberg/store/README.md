# MeowSEO Redux Store (meowseo/data)

This directory contains the Redux store implementation for the MeowSEO Gutenberg Editor Integration.

## Overview

The `meowseo/data` store is the single source of truth for all SEO data in the Gutenberg editor. It manages:

- SEO and readability scores
- Analysis results
- Active tab state
- Analysis status
- Content snapshot from the editor

## Architecture

The store follows Redux best practices with:

- **Types** (`types.ts`): TypeScript interfaces for state and data structures
- **Actions** (`actions.ts`): Action creators for dispatching state changes
- **Reducer** (`reducer.ts`): Pure reducer function that handles state updates
- **Selectors** (`selectors.ts`): Functions to read specific parts of the state
- **Index** (`index.ts`): Store registration and exports

## State Structure

```typescript
interface MeowSEOState {
  seoScore: number;              // 0-100
  readabilityScore: number;      // 0-100
  analysisResults: AnalysisResult[];
  activeTab: 'general' | 'social' | 'schema' | 'advanced';
  isAnalyzing: boolean;
  contentSnapshot: ContentSnapshot;
}
```

## Actions

- `updateContentSnapshot(snapshot)` - Updates the content snapshot from the editor
- `setAnalyzing(isAnalyzing)` - Sets the analyzing status
- `setAnalysisResults(seoScore, readabilityScore, results)` - Updates analysis results
- `setActiveTab(tab)` - Changes the active tab

## Selectors

- `getSeoScore(state)` - Returns the SEO score
- `getReadabilityScore(state)` - Returns the readability score
- `getAnalysisResults(state)` - Returns the analysis results array
- `getActiveTab(state)` - Returns the active tab
- `getIsAnalyzing(state)` - Returns the analyzing status
- `getContentSnapshot(state)` - Returns the content snapshot

## Usage

```typescript
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from './store';

// Reading from the store
const { seoScore, isAnalyzing } = useSelect((select) => ({
  seoScore: select(STORE_NAME).getSeoScore(),
  isAnalyzing: select(STORE_NAME).getIsAnalyzing(),
}));

// Dispatching actions
const { setActiveTab } = useDispatch(STORE_NAME);
setActiveTab('social');
```

## Testing

The store includes comprehensive tests:

- **Property-Based Tests** (`store-immutability.test.ts`): Validates that state is never mutated
- **Unit Tests** (`actions.test.ts`, `reducer.test.ts`, `selectors.test.ts`): Tests individual functions

Run tests with:
```bash
npm test -- --testPathPattern=store
```

## Requirements Validated

- **Requirement 3.1**: Store registered with name "meowseo/data"
- **Requirement 3.2**: State maintains all required properties
- **Requirement 3.3**: Default values set on initialization
- **Requirement 3.4**: Actions update state correctly
- **Requirement 3.5**: Selectors provide current state values
- **Requirement 3.6**: State is never mutated directly
- **Requirement 3.7**: New state objects returned for each update
