# Implementation Plan: Sprint 4 - Advanced & Ecosystem

## Overview

This implementation plan breaks down Sprint 4 into discrete coding tasks that build incrementally. The sprint adds 11 advanced features: Role Manager, Multilingual Module, Multisite Module, Location CPT, Bulk Editor, GA4 Module, Admin Bar Module, Orphaned Detector, Gutenberg Blocks, AI Optimizer, and Synonym Analyzer. Each task references specific requirements for traceability and includes property-based tests for the three core algorithms (coordinate validation, CSV escaping, synonym score calculation).

## Tasks

- [ ] 1. Set up Sprint 4 module structure and base classes
  - Create directory structure: `includes/modules/roles/`, `includes/modules/multilingual/`, `includes/modules/multisite/`, `includes/modules/locations/`, `includes/modules/bulk/`, `includes/modules/analytics/`, `includes/modules/admin-bar/`, `includes/modules/orphaned/`, `includes/modules/ai/`, `includes/modules/synonyms/`
  - Create base module interface for consistent initialization
  - Register autoloader paths for new modules
  - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 8.1, 9.1, 10.1, 11.1_

- [ ] 2. Implement Role Manager module
  - [ ] 2.1 Create Role_Manager class with capability registration
    - Implement `register_capabilities()` method to define 15 MeowSEO capabilities
    - Implement `user_can()` method for permission checking
    - Implement `get_role_capabilities()` and capability management methods
    - Set default capability assignments for Administrator and Editor roles
    - _Requirements: 1.1, 1.2, 1.5, 1.6_
  
  - [ ] 2.2 Create admin interface for role capability management
    - Add settings page under MeowSEO menu for role management
    - Display capability matrix (roles × capabilities)
    - Implement AJAX handlers for adding/removing capabilities
    - Add capability persistence to WordPress database
    - _Requirements: 1.4, 1.7_
  
  - [ ] 2.3 Integrate capability checks throughout plugin
    - Add capability checks to settings pages, REST API endpoints, and admin interfaces
    - Hide UI elements for features user cannot access
    - Return WP_Error for unauthorized REST API requests
    - _Requirements: 1.3, 1.8, 1.9_
  
  - [ ]* 2.4 Write unit tests for Role Manager
    - Test capability registration and default assignments
    - Test user permission checking logic
    - Test capability add/remove operations
    - _Requirements: 1.1, 1.2, 1.3_

- [ ] 3. Implement Multilingual Module
  - [ ] 3.1 Create Multilingual_Module class with plugin detection
    - Implement `detect_translation_plugin()` to identify WPML or Polylang
    - Implement `get_translations()` to fetch post translations
    - Implement `get_default_language()` and `get_current_language()`
    - _Requirements: 2.1, 2.2_
  
  - [ ] 3.2 Implement hreflang tag generation
    - Create `output_hreflang_tags()` method that hooks into `wp_head`
    - Query translations for current post using detected plugin API
    - Generate hreflang link tags for each language version
    - Include x-default tag pointing to default language
    - _Requirements: 2.3, 2.4_
  
  - [ ] 3.3 Implement per-language metadata storage
    - Modify metadata save/load to use language-suffixed postmeta keys
    - Implement `get_translated_metadata()` method
    - Implement `sync_schema_translations()` for schema properties
    - _Requirements: 2.5, 2.6_
  
  - [ ] 3.4 Integrate multilingual support with redirects and sitemaps
    - Add per-language redirect rules support
    - Synchronize sitemap generation with translation plugin settings
    - Preserve SEO metadata context when switching languages in editor
    - _Requirements: 2.7, 2.8, 2.9_
  
  - [ ]* 3.5 Write integration tests for Multilingual Module
    - Mock WPML and Polylang APIs
    - Test hreflang tag generation with multiple languages
    - Test per-language metadata storage and retrieval
    - _Requirements: 2.3, 2.5_

- [ ] 4. Implement Multisite Module
  - [ ] 4.1 Create Multisite_Module class with network activation support
    - Implement `is_network_activated()` check
    - Implement per-site settings isolation using site-specific options
    - Create network admin menu registration
    - _Requirements: 3.1, 3.2, 3.3_
  
  - [ ] 4.2 Implement network-level configuration interface
    - Create network admin settings page
    - Implement `get_network_settings()` and `update_network_settings()`
    - Add default settings configuration for new sites
    - Add feature toggle interface for network-wide disabling
    - _Requirements: 3.4, 3.6_
  
  - [ ] 4.3 Implement new site initialization
    - Hook into `wpmu_new_blog` action
    - Implement `initialize_new_site()` to copy default settings
    - _Requirements: 3.5_
  
  - [ ] 4.4 Ensure per-site data isolation
    - Verify sitemaps use correct site URLs
    - Verify redirects, 404 logs, and analytics are site-specific
    - Test subdirectory and subdomain configurations
    - _Requirements: 3.7, 3.8, 3.9_
  
  - [ ]* 4.5 Write integration tests for Multisite Module
    - Test network activation and per-site settings isolation
    - Test new site initialization with default settings
    - Test feature disabling at network level
    - _Requirements: 3.1, 3.2, 3.5_

- [ ] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Implement Location CPT module
  - [ ] 6.1 Register meowseo_location custom post type
    - Register CPT with appropriate labels and capabilities
    - Add custom fields for business details (name, address, phone, email)
    - Add custom fields for GPS coordinates (latitude, longitude)
    - Add custom field for opening hours (JSON array)
    - _Requirements: 4.1, 4.2_
  
  - [ ] 6.2 Implement coordinate validation
    - Create validation function for latitude (-90 to 90) and longitude (-180 to 180)
    - Add validation to post save hook
    - Display validation errors in admin interface
    - _Requirements: 4.3_
  
  - [ ]* 6.3 Write property test for coordinate validation
    - **Property 1: Coordinate Validation Correctness**
    - **Validates: Requirements 4.3**
    - Use Eris to generate random latitude/longitude values including edge cases
    - Verify validation correctly identifies valid and invalid coordinates
  
  - [ ] 6.4 Implement LocalBusiness schema generation
    - Create schema generator for Location CPT
    - Include address, GPS coordinates, phone, email, opening hours
    - Hook into existing schema output system
    - _Requirements: 4.4_
  
  - [ ] 6.5 Implement location shortcodes
    - Create `[meowseo_address]` shortcode for formatted address output
    - Create `[meowseo_map]` shortcode for Google Maps iframe embed
    - Create `[meowseo_opening_hours]` shortcode for structured hours display
    - Create `[meowseo_store_locator]` shortcode for interactive map with all locations
    - _Requirements: 4.5, 4.6, 4.7, 4.8_
  
  - [ ] 6.6 Implement KML export functionality
    - Create KML generator class
    - Implement export endpoint that returns KML XML
    - Add "Export to KML" button in admin interface
    - _Requirements: 4.9, 4.10_
  
  - [ ]* 6.7 Write unit tests for Location CPT
    - Test schema generation with various location data
    - Test shortcode rendering
    - Test KML export format compliance
    - _Requirements: 4.4, 4.5, 4.9_

- [ ] 7. Implement Bulk Editor module
  - [ ] 7.1 Register bulk actions in post list table
    - Hook into `bulk_actions-edit-post` and similar filters
    - Add bulk actions: Set noindex, Set index, Set nofollow, Set follow, Remove canonical, Set schema to Article, Set schema to None
    - _Requirements: 5.1_
  
  - [ ] 7.2 Implement bulk action handlers
    - Create `handle_bulk_action()` method
    - Apply selected operation to all selected post IDs
    - Display admin notice with count of modified posts
    - Log operations to WordPress activity log
    - _Requirements: 5.2, 5.3, 5.9_
  
  - [ ] 7.3 Implement CSV export functionality
    - Create CSV generator class
    - Implement `export_to_csv()` method with proper RFC 4180 escaping
    - Include columns: ID, Title, URL, Focus Keyword, Meta Description, SEO Score, Noindex, Nofollow, Canonical URL, Schema Type
    - Add header row with column names
    - _Requirements: 5.4, 5.5, 5.6, 5.7_
  
  - [ ]* 7.4 Write property test for CSV export escaping
    - **Property 2: CSV Export RFC 4180 Compliance**
    - **Validates: Requirements 5.5, 5.7**
    - Use Eris to generate random strings with special characters (quotes, commas, newlines)
    - Verify CSV output is RFC 4180 compliant with proper escaping
  
  - [ ] 7.5 Extend bulk operations to custom post types
    - Support bulk operations on pages and custom post types
    - Add post type filter to CSV export
    - _Requirements: 5.8_
  
  - [ ]* 7.6 Write unit tests for Bulk Editor
    - Test bulk action application to multiple posts
    - Test CSV export with various post data
    - Test admin notice display
    - _Requirements: 5.2, 5.5_

- [ ] 8. Implement GA4 Module
  - [ ] 8.1 Create GA4_Module class with OAuth authentication
    - Implement OAuth flow for Google Analytics 4 API
    - Create `authenticate_oauth()` method that returns Google consent URL
    - Implement `handle_oauth_callback()` to exchange code for tokens
    - Store refresh token securely in WordPress options (encrypted)
    - _Requirements: 6.1, 6.2_
  
  - [ ] 8.2 Implement GA4 metrics fetching
    - Integrate Google Analytics Data API client library
    - Implement `get_ga4_metrics()` to fetch sessions, users, pageviews, bounce rate, session duration
    - Implement caching with 6-hour expiration using transients
    - _Requirements: 6.4, 6.10_
  
  - [ ] 8.3 Implement GSC metrics fetching
    - Integrate Google Search Console API client library
    - Implement `get_gsc_metrics()` to fetch impressions, clicks, CTR, average position
    - Implement caching with 6-hour expiration
    - _Requirements: 6.5, 6.10_
  
  - [ ] 8.4 Implement PageSpeed Insights integration
    - Integrate PageSpeed Insights API
    - Implement `get_pagespeed_insights()` to fetch Core Web Vitals
    - Cache results per URL
    - _Requirements: 6.9_
  
  - [ ] 8.5 Create combined analytics dashboard
    - Create admin page displaying GA4 and GSC metrics side-by-side
    - Implement winning/losing content identification algorithms
    - Display top keywords and traffic changes
    - _Requirements: 6.3, 6.6, 6.7_
  
  - [ ] 8.6 Implement weekly email report
    - Create email template for weekly summary
    - Implement `send_weekly_report()` method
    - Schedule WP-Cron job for weekly execution
    - _Requirements: 6.8_
  
  - [ ]* 8.7 Write integration tests for GA4 Module
    - Mock Google API responses
    - Test OAuth flow and token storage
    - Test metrics fetching and caching
    - Test error handling for API failures
    - _Requirements: 6.1, 6.4, 6.5_

- [ ] 9. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 10. Implement Admin Bar Module
  - [ ] 10.1 Create Admin_Bar_Module class with score display
    - Hook into `admin_bar_menu` action
    - Implement `add_admin_bar_menu()` to add MeowSEO menu item
    - Implement `get_current_page_score()` to calculate SEO score
    - Display color-coded indicator (red: 0-49, orange: 50-79, green: 80-100)
    - _Requirements: 7.1, 7.2_
  
  - [ ] 10.2 Implement admin bar dropdown content
    - Create dropdown HTML with SEO score, readability score, focus keyword, failing checks count
    - Add "Edit SEO" link that opens post editor with MeowSEO sidebar
    - _Requirements: 7.3, 7.4, 7.5_
  
  - [ ] 10.3 Implement capability checks and caching
    - Only display for users with `meowseo_view_admin_bar` capability
    - Only display on singular posts/pages
    - Cache scores for 5 minutes using transients
    - _Requirements: 7.1, 7.6, 7.7, 7.8_
  
  - [ ]* 10.4 Write unit tests for Admin Bar Module
    - Test score calculation and color coding
    - Test capability checks
    - Test singular post detection
    - _Requirements: 7.1, 7.2, 7.8_

- [ ] 11. Implement Orphaned Detector module
  - [ ] 11.1 Create Orphaned_Detector class with scanning logic
    - Create database table `meowseo_orphaned_content`
    - Implement `scan_all_content()` to query published posts/pages
    - Count inbound links from `meowseo_internal_links` table
    - Mark posts with zero inbound links as orphaned
    - _Requirements: 8.1, 8.2, 8.3_
  
  - [ ] 11.2 Create orphaned content admin page
    - Display list of orphaned posts with title, URL, publish date
    - Add filters for post type and date range
    - Add dashboard widget showing orphaned post count
    - _Requirements: 8.4, 8.5, 8.9_
  
  - [ ] 11.3 Implement linking suggestion algorithm
    - Implement `suggest_linking_opportunities()` method
    - Analyze content similarity using keyword overlap or TF-IDF
    - Consider category, tag, and keyword relationships
    - Return 5 suggested posts to link from
    - _Requirements: 8.6, 8.7_
  
  - [ ] 11.4 Schedule weekly orphaned content scan
    - Implement WP-Cron job for weekly scanning
    - Process posts in batches of 100 to avoid timeouts
    - _Requirements: 8.8_
  
  - [ ]* 11.5 Write unit tests for Orphaned Detector
    - Test link counting algorithm
    - Test orphaned post identification
    - Test linking suggestion generation
    - _Requirements: 8.2, 8.3, 8.7_

- [ ] 12. Implement Gutenberg Blocks (React/TypeScript)
  - [ ] 12.1 Set up block development environment
    - Create `src/blocks/` directory structure
    - Configure TypeScript and React build process
    - Register block category `meowseo`
    - _Requirements: 9.1_
  
  - [ ] 12.2 Create Estimated Reading Time block
    - Register `meowseo/estimated-reading-time` block
    - Implement reading time calculation (word count / words per minute)
    - Add block settings for words per minute (150-300, default 200)
    - Add settings for icon display and custom text
    - _Requirements: 9.1, 9.2, 9.3_
  
  - [ ] 12.3 Create Related Posts block
    - Register `meowseo/related-posts` block
    - Implement query logic for keyword, category, or tag matching
    - Add block settings: number of posts (1-10), display style (list/grid), show excerpt, show thumbnail
    - _Requirements: 9.4, 9.5, 9.6_
  
  - [ ] 12.4 Create Siblings and Subpages blocks
    - Register `meowseo/siblings` block for same-parent posts
    - Register `meowseo/subpages` block for child pages
    - Add settings for thumbnails, ordering, depth
    - _Requirements: 9.7, 9.8_
  
  - [ ] 12.5 Ensure accessibility compliance
    - Use semantic HTML with proper heading hierarchy
    - Add ARIA labels for interactive elements
    - Implement keyboard navigation support
    - Test with screen readers
    - _Requirements: 9.9, 9.10_
  
  - [ ]* 12.6 Write Jest tests for Gutenberg blocks
    - Test block registration and attribute handling
    - Test reading time calculation
    - Test related posts query logic
    - Test accessibility with axe-core
    - _Requirements: 9.2, 9.5, 9.10_

- [ ] 13. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 14. Implement AI Optimizer module
  - [ ] 14.1 Create AI_Optimizer class with suggestion generation
    - Implement `get_suggestion()` method that calls configured AI provider
    - Construct prompt template: "This content is failing the [check name] SEO check. Focus keyword: [keyword]. Current content: [excerpt]. Provide a specific, actionable suggestion to fix this issue."
    - Support all SEO checks: keyword density, keyword in title, keyword in headings, etc.
    - _Requirements: 10.1, 10.2, 10.4, 10.9_
  
  - [ ] 14.2 Integrate with existing AI provider configuration
    - Use same provider settings as AI generation module (OpenAI, Anthropic, Gemini)
    - Respect API key and quota limits
    - Handle API errors gracefully with user-friendly messages
    - _Requirements: 10.3, 10.7, 10.8_
  
  - [ ] 14.3 Add AI suggestion UI to editor
    - Add "AI Suggestion" button next to failing checks
    - Display suggestion in collapsible panel below check
    - Implement suggestion caching (1 hour per check per post)
    - _Requirements: 10.1, 10.5, 10.6_
  
  - [ ]* 14.4 Write integration tests for AI Optimizer
    - Mock AI provider responses
    - Test prompt construction for various checks
    - Test caching behavior
    - Test error handling
    - _Requirements: 10.4, 10.6, 10.7_

- [ ] 15. Implement Synonym Analyzer module
  - [ ] 15.1 Create Synonym_Analyzer class with storage
    - Add synonym input field to General tab in editor
    - Implement `set_synonyms()` to store synonyms in `_meowseo_keyword_synonyms` postmeta as JSON
    - Implement `get_synonyms()` to retrieve synonyms
    - Limit to 5 synonyms per post with validation
    - _Requirements: 11.1, 11.2, 11.7_
  
  - [ ] 15.2 Implement synonym analysis checks
    - Extend keyword analysis to run checks for each synonym
    - Implement checks: synonym density (0.5-2.5%), synonym in title, synonym in headings, synonym in first paragraph, synonym in meta description
    - _Requirements: 11.3, 11.8_
  
  - [ ] 15.3 Implement combined score calculation
    - Create `calculate_combined_score()` method
    - Formula: (primary_keyword_score * 0.6) + (average_synonym_score * 0.4)
    - Ensure result is always between 0 and 100
    - _Requirements: 11.5_
  
  - [ ]* 15.4 Write property test for combined score calculation
    - **Property 3: Combined Synonym Score Bounds and Formula**
    - **Validates: Requirements 11.5**
    - Use Eris to generate random primary and synonym scores (0-100)
    - Verify combined score is between 0 and 100
    - Verify formula correctness: (primary * 0.6) + (avg_synonyms * 0.4)
    - Verify proportional increase when scores change
  
  - [ ] 15.5 Update editor UI for synonym display
    - Display separate analysis results for primary keyword and each synonym
    - Highlight synonym matches in different color (green) than primary keyword (blue)
    - Show summary of optimization status per synonym
    - _Requirements: 11.4, 11.6, 11.9_
  
  - [ ]* 15.6 Write unit tests for Synonym Analyzer
    - Test synonym storage and retrieval
    - Test synonym validation (max 5, sanitization)
    - Test combined score calculation with various inputs
    - _Requirements: 11.2, 11.5, 11.7_

- [ ] 16. Integration and wiring
  - [ ] 16.1 Register all modules in main plugin class
    - Initialize all Sprint 4 modules in plugin bootstrap
    - Ensure proper hook registration order
    - Verify module dependencies are loaded correctly
    - _Requirements: All_
  
  - [ ] 16.2 Update plugin settings to include Sprint 4 features
    - Add settings sections for Role Manager, Multisite, GA4, Admin Bar
    - Add feature toggles for optional modules
    - Update settings save/load logic
    - _Requirements: 1.4, 3.3, 6.1, 7.1_
  
  - [ ] 16.3 Update REST API to include Sprint 4 endpoints
    - Add endpoints for bulk operations, orphaned content, AI suggestions
    - Add capability checks to all new endpoints
    - Update API documentation
    - _Requirements: 1.9, 5.1, 8.4, 10.1_
  
  - [ ]* 16.4 Write integration tests for Sprint 4 features
    - Test module initialization and hook registration
    - Test cross-module interactions (e.g., Role Manager with all features)
    - Test multisite compatibility with all modules
    - _Requirements: All_

- [ ] 17. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property-based tests validate three core algorithms: coordinate validation, CSV escaping, and synonym score calculation
- Integration tests ensure modules work correctly with WordPress core, translation plugins, and external APIs
- Gutenberg blocks require React/TypeScript development with accessibility compliance
- All modules follow MeowSEO's existing architectural patterns: PSR-4 autoloading, WordPress hooks, options storage
