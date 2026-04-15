# Requirements Document: Gutenberg Editor Integration

## Introduction

The Gutenberg Editor Integration feature provides a React-based SEO sidebar for the MeowSEO WordPress plugin within the Gutenberg block editor. This feature enables content editors to optimize their posts and pages for search engines through a performant, user-friendly interface that integrates seamlessly with WordPress's native editing experience.

The system addresses performance issues found in competing plugins by implementing a custom Redux store (meowseo/data) as the single source of truth, with centralized content synchronization, debounced updates, and Web Worker-based analysis to ensure a responsive editing experience.

## Glossary

- **Gutenberg_Editor**: WordPress's block-based content editor
- **Sidebar**: The MeowSEO panel that appears in the Gutenberg editor interface
- **Content_Sync_Hook**: The useContentSync hook that reads from core/editor
- **Redux_Store**: The meowseo/data store that manages SEO state
- **Web_Worker**: A separate thread for running SEO analysis without blocking the UI
- **Postmeta**: WordPress post metadata stored in wp_postmeta table
- **Entity_Prop**: WordPress's useEntityProp hook for reading/writing post data
- **SERP_Preview**: Search Engine Results Page preview component
- **Focus_Keyword**: The primary keyword the content is optimized for
- **Analysis_Result**: Individual SEO check result (good, ok, or problem)
- **Schema_Config**: Structured data configuration for a specific schema type
- **Debounce**: Delay mechanism to prevent excessive function calls

## Requirements

### Requirement 1: Sidebar Registration and Display

**User Story:** As a content editor, I want to see the MeowSEO sidebar in the Gutenberg editor, so that I can access SEO optimization tools while editing content.

#### Acceptance Criteria

1. WHEN the Gutenberg editor loads for a post or page, THE Sidebar SHALL appear as a plugin sidebar with the title "MeowSEO"
2. WHEN the sidebar is opened, THE Sidebar SHALL display a chart-line icon in the sidebar toggle
3. THE Sidebar SHALL be compatible with WordPress versions 6.0 through 6.6+
4. WHERE WordPress version is 6.6 or higher, THE Sidebar SHALL import PluginSidebar from @wordpress/editor
5. WHERE WordPress version is below 6.6, THE Sidebar SHALL import PluginSidebar from @wordpress/edit-post
6. WHEN the sidebar is rendered, THE Sidebar SHALL display the ContentScoreWidget at the top
7. WHEN the sidebar is rendered, THE Sidebar SHALL display a tab bar with four tabs: General, Social, Schema, and Advanced

### Requirement 2: Centralized Content Synchronization

**User Story:** As a system architect, I want a single point of content synchronization, so that the sidebar remains performant and avoids unnecessary re-renders.

#### Acceptance Criteria

1. THE Content_Sync_Hook SHALL be the only component that reads from core/editor
2. WHEN content changes in the editor, THE Content_Sync_Hook SHALL read the title, content, excerpt, post type, and permalink
3. WHEN content is read, THE Content_Sync_Hook SHALL wait 800 milliseconds before dispatching updates
4. IF additional content changes occur within the 800ms window, THE Content_Sync_Hook SHALL reset the timer
5. WHEN the debounce timer completes, THE Content_Sync_Hook SHALL dispatch updateContentSnapshot to the Redux_Store
6. THE Sidebar components SHALL NOT read directly from core/editor
7. THE Sidebar components SHALL read content data only from the Redux_Store

### Requirement 3: Redux Store Management

**User Story:** As a developer, I want a centralized state management system, so that SEO data is consistent across all sidebar components.

#### Acceptance Criteria

1. THE Redux_Store SHALL be registered with the name "meowseo/data"
2. THE Redux_Store SHALL maintain state for seoScore, readabilityScore, analysisResults, activeTab, isAnalyzing, and contentSnapshot
3. WHEN the store is initialized, THE Redux_Store SHALL set default values for all state properties
4. WHEN an action is dispatched, THE Redux_Store SHALL update state according to the reducer logic
5. WHEN components select from the store, THE Redux_Store SHALL provide the current state values
6. THE Redux_Store SHALL NOT mutate state directly
7. THE Redux_Store SHALL return new state objects for each update

### Requirement 4: SEO Score Display

**User Story:** As a content editor, I want to see my content's SEO score at a glance, so that I know how well optimized my content is.

#### Acceptance Criteria

1. THE ContentScoreWidget SHALL display the current SEO score as a number from 0 to 100
2. THE ContentScoreWidget SHALL display the current readability score as a number from 0 to 100
3. WHEN the SEO score is below 40, THE ContentScoreWidget SHALL display the score in red
4. WHEN the SEO score is between 40 and 69, THE ContentScoreWidget SHALL display the score in orange
5. WHEN the SEO score is 70 or above, THE ContentScoreWidget SHALL display the score in green
6. THE ContentScoreWidget SHALL remain visible at the top of the sidebar regardless of which tab is active
7. WHEN analysis is running, THE ContentScoreWidget SHALL display a loading indicator

### Requirement 5: Manual Analysis Trigger

**User Story:** As a content editor, I want to manually trigger SEO analysis, so that I can see updated scores after making changes.

#### Acceptance Criteria

1. THE ContentScoreWidget SHALL display an "Analyze" button
2. WHEN the Analyze button is clicked, THE Sidebar SHALL dispatch an analyzeContent action
3. WHEN analysis starts, THE Redux_Store SHALL set isAnalyzing to true
4. WHEN analysis completes, THE Redux_Store SHALL set isAnalyzing to false
5. WHEN analysis is running, THE Analyze button SHALL be disabled
6. WHEN analysis completes successfully, THE Redux_Store SHALL update seoScore, readabilityScore, and analysisResults
7. IF analysis fails, THE Sidebar SHALL log the error and set isAnalyzing to false

### Requirement 6: Web Worker Analysis

**User Story:** As a content editor, I want SEO analysis to run without freezing the editor, so that I can continue working while analysis is in progress.

#### Acceptance Criteria

1. WHEN analysis is triggered, THE Sidebar SHALL create a Web_Worker instance
2. THE Web_Worker SHALL receive the content snapshot as input
3. THE Web_Worker SHALL perform SEO analysis checks in a separate thread
4. WHEN analysis completes, THE Web_Worker SHALL post results back to the main thread
5. THE Web_Worker SHALL NOT block the UI thread during analysis
6. WHEN the Web_Worker completes, THE Sidebar SHALL terminate the worker instance
7. IF the browser does not support Web Workers, THE Sidebar SHALL fall back to main thread analysis and log a warning


### Requirement 7: SEO Analysis Checks

**User Story:** As a content editor, I want detailed SEO feedback, so that I know exactly what to improve in my content.

#### Acceptance Criteria

1. WHEN the focus keyword is present in the SEO title, THE Web_Worker SHALL add a "good" result for keyword-in-title
2. WHEN the focus keyword is missing from the SEO title, THE Web_Worker SHALL add a "problem" result for keyword-in-title
3. WHEN the focus keyword is present in the meta description, THE Web_Worker SHALL add a "good" result for keyword-in-description
4. WHEN the focus keyword is missing from the meta description, THE Web_Worker SHALL add a "problem" result for keyword-in-description
5. WHEN the focus keyword is present in the first paragraph, THE Web_Worker SHALL add a "good" result for keyword-in-first-paragraph
6. WHEN the focus keyword is present in at least one heading, THE Web_Worker SHALL add a "good" result for keyword-in-headings
7. WHEN the focus keyword is present in the URL slug, THE Web_Worker SHALL add a "good" result for keyword-in-slug
8. THE Web_Worker SHALL calculate the SEO score by adding 20 points for each passed check
9. THE Web_Worker SHALL return a score between 0 and 100

### Requirement 8: Tab Navigation

**User Story:** As a content editor, I want to navigate between different SEO settings categories, so that I can organize my optimization work.

#### Acceptance Criteria

1. THE Sidebar SHALL display tabs for General, Social, Schema, and Advanced
2. WHEN a tab is clicked, THE Redux_Store SHALL update activeTab to the selected tab name
3. WHEN activeTab changes, THE Sidebar SHALL render only the content for the active tab
4. WHEN a tab is not active, THE Sidebar SHALL NOT render that tab's content
5. THE Sidebar SHALL display the General tab as active by default
6. WHEN switching tabs, THE Sidebar SHALL preserve the state of all tabs
7. THE Sidebar SHALL provide visual indication of which tab is currently active

### Requirement 9: Focus Keyword Management

**User Story:** As a content editor, I want to set a focus keyword for my content, so that the analysis can check if my content is optimized for that keyword.

#### Acceptance Criteria

1. THE General tab SHALL display a focus keyword input field
2. WHEN the focus keyword is changed, THE Sidebar SHALL persist the value to _meowseo_focus_keyword postmeta
3. THE Sidebar SHALL use Entity_Prop to read and write the focus keyword
4. WHEN the focus keyword is saved, THE Sidebar SHALL trigger WordPress auto-save
5. WHEN the page is reloaded, THE Sidebar SHALL display the previously saved focus keyword
6. THE General tab SHALL display analysis results related to the focus keyword
7. WHEN the focus keyword is empty, THE Web_Worker SHALL skip keyword-specific checks

### Requirement 10: SERP Preview

**User Story:** As a content editor, I want to see how my content will appear in search results, so that I can optimize the title and description for click-through rate.

#### Acceptance Criteria

1. THE General tab SHALL display a SERP_Preview component
2. THE SERP_Preview SHALL display the SEO title, meta description, and URL
3. THE SERP_Preview SHALL support desktop and mobile preview modes
4. WHEN the preview mode is changed, THE SERP_Preview SHALL update the display format
5. WHEN the SEO title or description changes, THE SERP_Preview SHALL update after 800 milliseconds
6. THE SERP_Preview SHALL truncate the title at 60 characters for desktop view
7. THE SERP_Preview SHALL truncate the description at 160 characters for desktop view

### Requirement 11: Internal Link Suggestions

**User Story:** As a content editor, I want to see suggestions for internal links, so that I can improve my site's internal linking structure.

#### Acceptance Criteria

1. THE General tab SHALL display an internal link suggestions component
2. WHEN the focus keyword changes, THE Sidebar SHALL fetch link suggestions after 3 seconds
3. IF the focus keyword is less than 3 characters, THE Sidebar SHALL NOT fetch suggestions
4. WHEN fetching suggestions, THE Sidebar SHALL call the /meowseo/v1/internal-links/suggestions REST endpoint
5. THE Sidebar SHALL send the post ID, focus keyword, and limit of 5 in the request
6. WHEN suggestions are loading, THE Sidebar SHALL display a loading indicator
7. IF the API call fails, THE Sidebar SHALL display an empty suggestions list and log the error
8. WHEN suggestions are returned, THE Sidebar SHALL display the post title, URL, and relevance score for each suggestion

### Requirement 12: Social Media Metadata

**User Story:** As a content editor, I want to customize how my content appears when shared on social media, so that I can maximize engagement on social platforms.

#### Acceptance Criteria

1. THE Social tab SHALL display Facebook and Twitter sub-tabs
2. THE Facebook sub-tab SHALL provide inputs for Open Graph title, description, and image
3. THE Twitter sub-tab SHALL provide inputs for Twitter title, description, and image
4. WHEN Open Graph values are changed, THE Sidebar SHALL persist them to _meowseo_og_title, _meowseo_og_description, and _meowseo_og_image_id postmeta
5. WHEN Twitter values are changed, THE Sidebar SHALL persist them to _meowseo_twitter_title, _meowseo_twitter_description, and _meowseo_twitter_image_id postmeta
6. THE Twitter sub-tab SHALL display a "Use Open Graph for Twitter" toggle
7. WHEN the toggle is enabled, THE Sidebar SHALL use Open Graph values for Twitter and disable Twitter-specific inputs
8. THE Social tab SHALL display preview cards for Facebook and Twitter
9. WHEN social metadata changes, THE preview cards SHALL update immediately

### Requirement 13: Schema Markup Configuration

**User Story:** As a content editor, I want to add structured data to my content, so that search engines can better understand and display my content.

#### Acceptance Criteria

1. THE Schema tab SHALL display a schema type selector
2. THE Schema tab SHALL support Article, FAQPage, HowTo, LocalBusiness, and Product schema types
3. WHEN a schema type is selected, THE Sidebar SHALL display a form specific to that schema type
4. THE Article form SHALL provide inputs for headline, datePublished, dateModified, and author
5. THE FAQPage form SHALL provide repeatable question and answer fields
6. THE HowTo form SHALL provide repeatable step fields with name, text, and optional image
7. THE LocalBusiness form SHALL provide inputs for name, address, telephone, opening hours, and optional geo coordinates
8. THE Product form SHALL provide inputs for name, description, SKU, price, currency, and availability
9. WHEN schema configuration changes, THE Sidebar SHALL persist the configuration to _meowseo_schema_type and _meowseo_schema_config postmeta
10. THE Sidebar SHALL validate schema configuration against the selected schema type before saving

### Requirement 14: Advanced SEO Settings

**User Story:** As a content editor, I want to control advanced SEO settings like robots directives and canonical URLs, so that I have fine-grained control over how search engines index my content.

#### Acceptance Criteria

1. THE Advanced tab SHALL display toggles for noindex and nofollow robots directives
2. WHEN the noindex toggle is changed, THE Sidebar SHALL persist the value to _meowseo_robots_noindex postmeta
3. WHEN the nofollow toggle is changed, THE Sidebar SHALL persist the value to _meowseo_robots_nofollow postmeta
4. THE Advanced tab SHALL display a canonical URL input field
5. WHEN the canonical URL is changed, THE Sidebar SHALL persist the value to _meowseo_canonical postmeta
6. THE Advanced tab SHALL display the resolved canonical URL (read-only)
7. THE Advanced tab SHALL display the last Google Search Console submission timestamp
8. THE Advanced tab SHALL display a "Request Indexing" button for submitting to Google Search Console

### Requirement 15: Postmeta Persistence

**User Story:** As a developer, I want all SEO data to be persisted to WordPress postmeta, so that the data is stored reliably and integrates with WordPress's save system.

#### Acceptance Criteria

1. THE Sidebar SHALL use Entity_Prop for all postmeta read and write operations
2. WHEN a postmeta value is updated via Entity_Prop, THE Sidebar SHALL trigger WordPress auto-save
3. THE Sidebar SHALL persist SEO title to _meowseo_title
4. THE Sidebar SHALL persist meta description to _meowseo_description
5. THE Sidebar SHALL persist focus keyword to _meowseo_focus_keyword
6. THE Sidebar SHALL persist direct answer to _meowseo_direct_answer
7. THE Sidebar SHALL persist all social metadata to their respective postmeta keys
8. THE Sidebar SHALL persist schema type and configuration to _meowseo_schema_type and _meowseo_schema_config
9. THE Sidebar SHALL persist robots directives to _meowseo_robots_noindex and _meowseo_robots_nofollow
10. THE Sidebar SHALL persist canonical URL to _meowseo_canonical
11. WHEN postmeta is read and the key does not exist, THE Sidebar SHALL use an empty string as the default value

### Requirement 16: Performance Optimization

**User Story:** As a content editor, I want the sidebar to be fast and responsive, so that it doesn't slow down my editing workflow.

#### Acceptance Criteria

1. THE Sidebar SHALL NOT re-render on every keystroke in the editor
2. WHEN content changes, THE Sidebar SHALL wait 800 milliseconds before updating
3. THE Sidebar SHALL use code splitting to lazy load tab content
4. THE Sidebar SHALL lazy load schema forms based on the selected schema type
5. THE Sidebar bundle size SHALL be less than 150 kilobytes gzipped
6. THE Sidebar SHALL memoize expensive selectors using createSelector
7. THE Sidebar SHALL use React.memo for pure components
8. THE Sidebar SHALL use useCallback for event handlers to prevent unnecessary re-renders
9. WHEN analysis runs, THE Sidebar SHALL reduce main thread blocking time by 60-80% compared to main thread analysis

### Requirement 17: Error Handling

**User Story:** As a content editor, I want the sidebar to handle errors gracefully, so that I can continue working even when something goes wrong.

#### Acceptance Criteria

1. IF the Web_Worker fails to load, THE Sidebar SHALL fall back to main thread analysis and log a warning
2. IF the REST API call for internal links fails, THE Sidebar SHALL return an empty suggestions array and log the error
3. IF postmeta returns null or undefined, THE Sidebar SHALL use an empty string or default value
4. IF analysis takes longer than 10 seconds, THE Sidebar SHALL terminate the worker and display a timeout message
5. WHEN an error occurs, THE Sidebar SHALL log the error to the console
6. WHEN an error occurs, THE Sidebar SHALL continue functioning with degraded capabilities
7. THE Sidebar SHALL NOT display JavaScript errors to the user in the UI

### Requirement 18: Security

**User Story:** As a site administrator, I want the sidebar to be secure, so that unauthorized users cannot modify SEO data or access sensitive information.

#### Acceptance Criteria

1. THE Sidebar SHALL include X-WP-Nonce header in all REST API calls
2. THE Sidebar SHALL retrieve the nonce from meowseoData.nonce localized from PHP
3. THE REST API endpoints SHALL verify the nonce before processing requests
4. THE REST API endpoints SHALL require edit_post capability for postmeta updates
5. THE REST API endpoints SHALL require manage_options capability for Google Search Console indexing requests
6. THE Sidebar SHALL sanitize all user input before storage using sanitize_text_field or esc_url_raw
7. THE Sidebar SHALL sanitize HTML content using wp_kses_post
8. THE Sidebar SHALL validate schema configuration JSON before storage
9. THE Sidebar SHALL escape all output using appropriate WordPress functions
10. THE Sidebar SHALL avoid using dangerouslySetInnerHTML except for trusted content

### Requirement 19: Internationalization

**User Story:** As a content editor using WordPress in a non-English language, I want the sidebar to be translated, so that I can use it in my preferred language.

#### Acceptance Criteria

1. THE Sidebar SHALL use @wordpress/i18n for all user-facing strings
2. THE Sidebar SHALL wrap all translatable strings with __() or _x() functions
3. THE Sidebar SHALL use the "meowseo" text domain for translations
4. WHEN WordPress locale changes, THE Sidebar SHALL display translated strings
5. THE Sidebar SHALL support right-to-left (RTL) languages
6. THE Sidebar SHALL NOT hardcode any user-facing text in English

### Requirement 20: Browser Compatibility

**User Story:** As a content editor, I want the sidebar to work in my browser, so that I can use it regardless of which browser I prefer.

#### Acceptance Criteria

1. THE Sidebar SHALL support Chrome 90 and higher
2. THE Sidebar SHALL support Firefox 88 and higher
3. THE Sidebar SHALL support Safari 14 and higher
4. THE Sidebar SHALL support Edge 90 and higher
5. THE Sidebar SHALL require ES6+ JavaScript support
6. THE Sidebar SHALL require Web Worker support with fallback
7. THE Sidebar SHALL require LocalStorage support for UI preferences
