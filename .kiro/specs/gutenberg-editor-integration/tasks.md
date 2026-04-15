# Implementation Plan: Gutenberg Editor Integration

## Overview

This implementation plan breaks down the Gutenberg Editor Integration feature into discrete coding tasks. The feature provides a React-based SEO sidebar for the MeowSEO WordPress plugin with a custom Redux store (meowseo/data), centralized content synchronization via useContentSync hook, Web Worker-based analysis, and four main tabs (General, Social, Schema, Advanced). All tasks build incrementally, with property-based tests for universal correctness properties and unit tests for specific functionality.

## Tasks

- [x] 1. Set up build configuration and project structure
  - Install @wordpress/scripts v27+ and configure webpack
  - Create directory structure: src/gutenberg/, src/gutenberg/components/, src/gutenberg/store/, src/gutenberg/workers/
  - Configure TypeScript with tsconfig.json for React and WordPress types
  - Set up entry point in src/gutenberg/index.tsx
  - Create PHP asset enqueuing class in includes/modules/meta/class-gutenberg-assets.php
  - _Requirements: 1.3, 16.3, 16.4, 16.5_

- [x] 2. Implement Redux store (meowseo/data)
  - [x] 2.1 Create store structure with types, actions, reducers, and selectors
    - Define MeowSEOState interface with seoScore, readabilityScore, analysisResults, activeTab, isAnalyzing, contentSnapshot
    - Implement action creators: updateContentSnapshot, setAnalyzing, setAnalysisResults, setActiveTab
    - Implement reducer with initial state and action handlers
    - Implement selectors: getSeoScore, getReadabilityScore, getAnalysisResults, getActiveTab, getIsAnalyzing, getContentSnapshot
    - Register store with @wordpress/data using createReduxStore
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_

  - [x] 2.2 Write property test for store state immutability
    - **Property 1: Store state immutability**
    - **Validates: Requirements 3.6, 3.7**
    - Test that dispatching actions never mutates existing state objects
    - Generate random action sequences and verify state references change

  - [x] 2.3 Write unit tests for store actions and reducers
    - Test each action creator returns correct action object
    - Test reducer handles each action type correctly
    - Test initial state values
    - _Requirements: 3.2, 3.3, 3.4_

- [x] 3. Implement WordPress 6.6+ compatibility shim
  - [x] 3.1 Create version detection utility
    - Detect WordPress version from window.wp global
    - Export isWP66Plus boolean flag
    - _Requirements: 1.3, 1.4, 1.5_

  - [x] 3.2 Create dynamic PluginSidebar import wrapper
    - Conditionally import from @wordpress/editor (WP 6.6+) or @wordpress/edit-post (WP < 6.6)
    - Export unified PluginSidebar component
    - _Requirements: 1.4, 1.5_

  - [x] 3.3 Write unit tests for version detection
    - Test version detection with different WordPress versions
    - Test PluginSidebar import selection
    - _Requirements: 1.3, 1.4, 1.5_

- [x] 4. Implement useContentSync hook (centralized content synchronization)
  - [x] 4.1 Create useContentSync hook with 800ms debounce
    - Read title, content, excerpt, postType, permalink from core/editor
    - Implement 800ms debounce using useEffect and setTimeout
    - Dispatch updateContentSnapshot to meowseo/data store
    - Clean up timeout on unmount
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 16.1, 16.2_

  - [x] 4.2 Write property test for debounce behavior
    - **Property 2: Debounce guarantee**
    - **Validates: Requirements 2.3, 2.4, 16.2**
    - Test that rapid content changes within 800ms result in only one dispatch
    - Generate random sequences of content changes and verify dispatch count

  - [x] 4.3 Write unit tests for useContentSync
    - Test hook reads from core/editor
    - Test 800ms debounce timer
    - Test cleanup on unmount
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Implement Web Worker for SEO analysis
  - [x] 6.1 Create analysis worker file (src/gutenberg/workers/analysis-worker.ts)
    - Implement analyzeSEO function with 5 keyword checks (title, description, first paragraph, headings, slug)
    - Calculate score (20 points per passed check, 0-100 range)
    - Determine color (red < 40, orange 40-70, green >= 70)
    - Post results back to main thread
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9_

  - [x] 6.2 Write property test for score bounds
    - **Property 3: Score bounds**
    - **Validates: Requirements 7.8, 7.9**
    - Test that for any analysis input, score is always 0 <= score <= 100
    - Generate random content and keyword combinations

  - [x] 6.3 Write property test for color consistency
    - **Property 5: Score color mapping**
    - **Validates: Requirements 4.3, 4.4, 4.5**
    - Test that color mapping is deterministic based on score thresholds
    - Generate random scores and verify color assignment

  - [x] 6.4 Write property test for idempotent analysis
    - **Property 4: Idempotent analysis**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7**
    - Test that running analysis twice on same content produces identical results
    - Generate random content samples and verify consistency

  - [x] 6.5 Write unit tests for analysis worker
    - Test each of the 5 keyword checks individually
    - Test score calculation
    - Test color determination
    - Test with empty focus keyword
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9_

- [x] 7. Implement analyzeContent action and Web Worker integration
  - [x] 7.1 Create analyzeContent thunk action
    - Get contentSnapshot from store
    - Set isAnalyzing to true
    - Create Web Worker instance
    - Post contentSnapshot to worker
    - Handle worker response and update store
    - Handle worker errors with fallback to main thread
    - Terminate worker after completion
    - Set isAnalyzing to false
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.6, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 17.1_

  - [x] 7.2 Write property test for non-blocking analysis
    - **Property 3: Analysis non-blocking**
    - **Validates: Requirements 6.5, 16.9**
    - Test that analysis runs in Web Worker and doesn't block UI thread
    - Measure main thread blocking time during analysis

  - [x] 7.3 Write unit tests for analyzeContent action
    - Test worker creation and message posting
    - Test result handling
    - Test error handling and fallback
    - Test isAnalyzing state transitions
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.6, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

- [x] 8. Implement useEntityPropBinding utility hook
  - [x] 8.1 Create useEntityPropBinding hook for postmeta operations
    - Get postType and postId from core/editor
    - Use useEntityProp from @wordpress/core-data
    - Return [value, setValue] tuple
    - Handle null/undefined with empty string fallback
    - _Requirements: 15.1, 15.2, 15.11, 17.3_

  - [x] 8.2 Write property test for postmeta persistence
    - **Property 4: Postmeta persistence**
    - **Validates: Requirements 15.1, 15.2**
    - Test that all postmeta updates use useEntityProp
    - Generate random postmeta key-value pairs and verify persistence

  - [x] 8.3 Write unit tests for useEntityPropBinding
    - Test hook returns correct value and setter
    - Test null/undefined fallback to empty string
    - Test setValue triggers useEntityProp update
    - _Requirements: 15.1, 15.2, 15.11, 17.3_

- [x] 9. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Implement ContentScoreWidget component
  - [x] 10.1 Create ContentScoreWidget with score display and analyze button
    - Display seoScore and readabilityScore from store
    - Implement color-coded score display (red/orange/green)
    - Add "Analyze" button that dispatches analyzeContent
    - Disable button when isAnalyzing is true
    - Show loading indicator when analyzing
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 10.2 Write unit tests for ContentScoreWidget
    - Test score display
    - Test color coding based on score
    - Test analyze button click
    - Test button disabled state during analysis
    - Test loading indicator
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 5.1, 5.5_

- [x] 11. Implement tab navigation system
  - [x] 11.1 Create TabBar component with four tabs
    - Display tabs for General, Social, Schema, Advanced
    - Dispatch setActiveTab on tab click
    - Highlight active tab visually
    - _Requirements: 8.1, 8.2, 8.5, 8.7_

  - [x] 11.2 Create TabContent component with conditional rendering
    - Render only active tab content
    - Preserve state of all tabs
    - Use lazy loading for tab content (code splitting)
    - _Requirements: 8.3, 8.4, 8.6, 16.3_

  - [x] 11.3 Write property test for tab state isolation
    - **Property 7: Tab state isolation**
    - **Validates: Requirements 8.3, 8.4**
    - Test that inactive tabs are not rendered
    - Generate random tab switches and verify rendering

  - [x] 11.4 Write unit tests for tab navigation
    - Test tab switching updates activeTab
    - Test only active tab content is rendered
    - Test visual indication of active tab
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.7_

- [ ] 12. Implement General tab components
  - [ ] 12.1 Create FocusKeywordInput component
    - Use useEntityPropBinding for _meowseo_focus_keyword
    - Display TextControl with label and help text
    - Trigger auto-save on change
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 15.5_

  - [ ] 12.2 Create SerpPreview component
    - Display SEO title, meta description, and URL
    - Support desktop and mobile preview modes
    - Implement 800ms debounce for updates
    - Truncate title at 60 chars (desktop)
    - Truncate description at 160 chars (desktop)
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7_

  - [ ] 12.3 Create DirectAnswerField component
    - Use useEntityPropBinding for _meowseo_direct_answer
    - Display TextareaControl with label
    - _Requirements: 15.6_

  - [ ] 12.4 Create InternalLinkSuggestions component
    - Implement 3-second debounce for focus keyword changes
    - Skip fetch if keyword < 3 characters
    - Call /meowseo/v1/internal-links/suggestions REST endpoint
    - Display loading indicator during fetch
    - Display suggestions with title, URL, and relevance score
    - Handle API errors gracefully
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 11.8, 17.2_

  - [ ] 12.5 Wire General tab components together
    - Create GeneralTabContent component
    - Render SerpPreview, FocusKeywordInput, DirectAnswerField, InternalLinkSuggestions
    - _Requirements: 1.7, 9.6_

  - [ ] 12.6 Write unit tests for General tab components
    - Test FocusKeywordInput persistence
    - Test SerpPreview truncation and mode switching
    - Test InternalLinkSuggestions debounce and API call
    - Test DirectAnswerField persistence
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 11.8_

- [ ] 13. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 14. Implement Social tab components
  - [ ] 14.1 Create FacebookSubTab component
    - Use useEntityPropBinding for _meowseo_og_title, _meowseo_og_description, _meowseo_og_image_id
    - Display TextControl for title, TextareaControl for description, MediaUpload for image
    - Display Facebook preview card
    - _Requirements: 12.2, 12.4, 12.8_

  - [ ] 14.2 Create TwitterSubTab component
    - Use useEntityPropBinding for _meowseo_twitter_title, _meowseo_twitter_description, _meowseo_twitter_image_id, _meowseo_use_og_for_twitter
    - Display TextControl for title, TextareaControl for description, MediaUpload for image
    - Display "Use Open Graph for Twitter" toggle
    - Disable Twitter-specific inputs when toggle is enabled
    - Display Twitter preview card
    - _Requirements: 12.3, 12.5, 12.6, 12.7, 12.8_

  - [ ] 14.3 Wire Social tab components together
    - Create SocialTabContent component with Facebook and Twitter sub-tabs
    - Implement sub-tab navigation
    - _Requirements: 12.1, 12.9_

  - [ ] 14.4 Write unit tests for Social tab components
    - Test Facebook inputs and persistence
    - Test Twitter inputs and persistence
    - Test "Use Open Graph for Twitter" toggle behavior
    - Test preview card updates
    - _Requirements: 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8, 12.9_

- [ ] 15. Implement Schema tab components
  - [ ] 15.1 Create SchemaTypeSelector component
    - Display SelectControl with 5 schema types: Article, FAQPage, HowTo, LocalBusiness, Product
    - Use useEntityPropBinding for _meowseo_schema_type
    - _Requirements: 13.1, 13.2_

  - [ ] 15.2 Create ArticleForm component
    - Use useEntityPropBinding for _meowseo_schema_config
    - Display inputs for headline, datePublished, dateModified, author
    - _Requirements: 13.4, 13.9_

  - [ ] 15.3 Create FAQPageForm component
    - Use useEntityPropBinding for _meowseo_schema_config
    - Display repeatable question and answer fields
    - Add/remove question pairs
    - _Requirements: 13.5, 13.9_

  - [ ] 15.4 Create HowToForm component
    - Use useEntityPropBinding for _meowseo_schema_config
    - Display repeatable step fields with name, text, optional image
    - Add/remove steps
    - _Requirements: 13.6, 13.9_

  - [ ] 15.5 Create LocalBusinessForm component
    - Use useEntityPropBinding for _meowseo_schema_config
    - Display inputs for name, address (street, locality, region, postal, country), telephone, opening hours, optional geo coordinates
    - _Requirements: 13.7, 13.9_

  - [ ] 15.6 Create ProductForm component
    - Use useEntityPropBinding for _meowseo_schema_config
    - Display inputs for name, description, SKU, price, currency, availability
    - _Requirements: 13.8, 13.9_

  - [ ] 15.7 Wire Schema tab components together
    - Create SchemaTabContent component
    - Lazy load schema forms based on selected type
    - Validate schema configuration before saving
    - _Requirements: 13.3, 13.9, 13.10, 16.4_

  - [ ] 15.8 Write unit tests for Schema tab components
    - Test schema type selection
    - Test each schema form (Article, FAQPage, HowTo, LocalBusiness, Product)
    - Test repeatable fields (add/remove)
    - Test schema validation
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5, 13.6, 13.7, 13.8, 13.9, 13.10_

- [ ] 16. Implement Advanced tab components
  - [ ] 16.1 Create RobotsToggles component
    - Use useEntityPropBinding for _meowseo_robots_noindex and _meowseo_robots_nofollow
    - Display ToggleControl for noindex and nofollow
    - _Requirements: 14.1, 14.2, 14.3, 15.9, 15.10_

  - [ ] 16.2 Create CanonicalURLInput component
    - Use useEntityPropBinding for _meowseo_canonical
    - Display TextControl for canonical URL
    - Display read-only resolved canonical URL
    - _Requirements: 14.4, 14.5, 14.6, 15.11_

  - [ ] 16.3 Create GSCIntegration component
    - Display last submission timestamp from _meowseo_gsc_last_submit
    - Display "Request Indexing" button
    - Call Google Search Console API on button click
    - Require manage_options capability
    - _Requirements: 14.7, 14.8_

  - [ ] 16.4 Wire Advanced tab components together
    - Create AdvancedTabContent component
    - Render RobotsToggles, CanonicalURLInput, GSCIntegration
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8_

  - [ ] 16.5 Write unit tests for Advanced tab components
    - Test robots toggles persistence
    - Test canonical URL input and display
    - Test GSC integration button and API call
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8_

- [ ] 17. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 18. Implement main Sidebar component and plugin registration
  - [ ] 18.1 Create Sidebar component
    - Call useContentSync hook (ONLY place to read from core/editor)
    - Select activeTab from meowseo/data store
    - Render ContentScoreWidget (always visible)
    - Render TabBar
    - Render TabContent with activeTab
    - _Requirements: 1.6, 1.7, 2.6, 2.7_

  - [ ] 18.2 Register plugin with WordPress
    - Use registerPlugin from @wordpress/plugins
    - Import PluginSidebar from compatibility shim
    - Register sidebar with name "meowseo-sidebar", title "MeowSEO", icon "chart-line"
    - _Requirements: 1.1, 1.2_

  - [ ] 18.3 Write property test for single content sync source
    - **Property 1: Single content sync source**
    - **Validates: Requirements 2.1, 2.6, 2.7**
    - Test that only useContentSync reads from core/editor
    - Verify no other components subscribe to core/editor

  - [ ] 18.4 Write property test for no keystroke re-renders
    - **Property 6: No keystroke re-renders**
    - **Validates: Requirements 16.1, 16.2**
    - Test that sidebar components don't re-render on every keystroke
    - Simulate rapid keystrokes and measure re-render count

  - [ ] 18.5 Write integration tests for sidebar
    - Test sidebar appears in Gutenberg editor
    - Test typing in editor triggers content sync after 800ms
    - Test clicking "Analyze" button updates scores
    - Test tab switching works
    - Test postmeta persistence after save and reload
    - _Requirements: 1.1, 1.2, 1.6, 1.7, 2.1, 2.2, 2.3, 2.4, 2.5, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 15.1, 15.2_

- [ ] 19. Implement PHP asset enqueuing
  - [ ] 19.1 Create Gutenberg_Assets class
    - Enqueue compiled JavaScript bundle in Gutenberg editor
    - Enqueue compiled CSS bundle
    - Localize script with meowseoData (nonce, postId, restUrl)
    - Register postmeta keys with register_post_meta for REST API access
    - Hook into enqueue_block_editor_assets
    - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 18.7, 18.8, 18.9_

  - [ ] 19.2 Write unit tests for Gutenberg_Assets class
    - Test script enqueuing
    - Test style enqueuing
    - Test script localization
    - Test postmeta registration
    - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 18.7, 18.8, 18.9_

- [ ] 20. Implement security measures
  - [ ] 20.1 Add nonce verification to all REST API calls
    - Include X-WP-Nonce header in apiFetch calls
    - Retrieve nonce from meowseoData.nonce
    - _Requirements: 18.1, 18.2, 18.3_

  - [ ] 20.2 Add input sanitization and output escaping
    - Sanitize all user input before storage (sanitize_text_field, esc_url_raw)
    - Sanitize HTML content with wp_kses_post
    - Validate schema configuration JSON
    - Escape all output with appropriate WordPress functions
    - Avoid dangerouslySetInnerHTML except for trusted content
    - _Requirements: 18.6, 18.7, 18.8, 18.9, 18.10_

  - [ ] 20.3 Write security tests
    - Test nonce verification
    - Test input sanitization
    - Test capability checks
    - Test XSS prevention
    - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 18.7, 18.8, 18.9, 18.10_

- [ ] 21. Implement internationalization
  - [ ] 21.1 Add i18n to all user-facing strings
    - Use __() or _x() from @wordpress/i18n for all translatable strings
    - Use "meowseo" text domain
    - Support RTL languages
    - _Requirements: 19.1, 19.2, 19.3, 19.4, 19.5, 19.6_

  - [ ] 21.2 Write i18n tests
    - Test all strings are wrapped with translation functions
    - Test text domain is correct
    - Test RTL support
    - _Requirements: 19.1, 19.2, 19.3, 19.4, 19.5, 19.6_

- [ ] 22. Implement performance optimizations
  - [ ] 22.1 Add memoization and React optimization
    - Use createSelector for expensive selectors
    - Use React.memo for pure components
    - Use useCallback for event handlers
    - Verify bundle size < 150KB gzipped
    - _Requirements: 16.6, 16.7, 16.8, 16.5_

  - [ ] 22.2 Write performance tests
    - Test bundle size
    - Test main thread blocking time during analysis
    - Test re-render count on content changes
    - _Requirements: 16.5, 16.6, 16.7, 16.8, 16.9_

- [ ] 23. Implement error handling
  - [ ] 23.1 Add error handling for all failure scenarios
    - Web Worker fallback to main thread with warning
    - REST API error handling with empty fallback
    - Postmeta null/undefined fallback to empty string
    - Analysis timeout (10 seconds) with worker termination
    - Console error logging for all errors
    - No user-facing JavaScript errors
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 17.7_

  - [ ] 23.2 Write error handling tests
    - Test Web Worker fallback
    - Test REST API error handling
    - Test postmeta fallback
    - Test analysis timeout
    - Test error logging
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 17.7_

- [ ] 24. Final checkpoint and integration verification
  - Ensure all tests pass, ask the user if questions arise.
  - Verify sidebar appears in Gutenberg editor
  - Verify all tabs work correctly
  - Verify postmeta persistence
  - Verify Web Worker analysis
  - Verify performance metrics (bundle size, no keystroke re-renders, analysis non-blocking)

## Notes

- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- All code uses TypeScript as specified in the design document
- The implementation follows the design's architecture with meowseo/data as single source of truth
- useContentSync is the ONLY component allowed to read from core/editor
- All postmeta operations use useEntityProp for WordPress integration
