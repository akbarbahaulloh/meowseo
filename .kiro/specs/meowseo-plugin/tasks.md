# Implementation Plan: MeowSEO Plugin

## Overview

This implementation plan breaks down the MeowSEO WordPress SEO plugin development into manageable, sequential tasks. The plugin follows a modular architecture where only enabled features are loaded, optimized for performance and headless WordPress deployments.

## Tasks

- [x] 1. Set up plugin foundation and core infrastructure
  - Create plugin entry point with autoloader registration
  - Implement Plugin singleton class with version checks
  - Set up WordPress class naming convention autoloader
  - Create Options class for centralized settings management
  - Implement Installer class with dbDelta() for custom tables
  - _Requirements: 1.1, 1.4, 1.5, 1.6, 2.1, 2.2_

- [ ]* 1.1 Write property test for autoloader class resolution
  - **Property 2: Autoloader resolves class names to correct file paths**
  - **Validates: Requirements 1.4**

- [ ] 2. Implement Module Manager and modular loading system
  - [x] 2.1 Create Module interface and Module_Manager class
    - Define Module contract with boot() and get_id() methods
    - Implement conditional module loading based on enabled settings
    - Ensure disabled modules are never loaded or instantiated
    - _Requirements: 1.2, 1.3_

  - [ ]* 2.2 Write property test for module loading
    - **Property 1: Module_Manager loads exactly the enabled set**
    - **Validates: Requirements 1.2, 1.3**

  - [ ]* 2.3 Write property test for Options round-trip
    - **Property 3: Options round-trip preserves all values**
    - **Validates: Requirements 2.2**

- [ ] 3. Create helper classes for caching and database operations
  - [x] 3.1 Implement Cache helper with Object Cache abstraction
    - Wrap wp_cache_* functions with meowseo_ prefix
    - Implement transient fallback when Object Cache unavailable
    - Add atomic lock support via wp_cache_add()
    - _Requirements: 14.2, 14.3_

  - [ ]* 3.2 Write property test for cache key prefixing
    - **Property 19: Cache keys always use the meowseo_ prefix**
    - **Validates: Requirements 14.2**

  - [x] 3.3 Implement DB helper with prepared statements
    - Create wrapper for all $wpdb interactions
    - Implement methods for redirects, 404 log, GSC queue, link checks
    - Ensure all queries use $wpdb->prepare() for security
    - _Requirements: 15.1_

- [ ] 4. Implement Meta Module for SEO meta management
  - [x] 4.1 Create Meta module with postmeta storage
    - Store SEO data in wp_postmeta with meowseo_ prefix
    - Implement title, description, robots, canonical getters
    - Add fallback logic for empty SEO title and description
    - Output meta tags in wp_head hook
    - _Requirements: 3.1, 3.4, 3.5, 3.6, 3.7_

  - [ ]* 4.2 Write property test for SEO meta key prefix
    - **Property 4: SEO meta uses consistent key prefix**
    - **Validates: Requirements 3.1, 3.4**

  - [ ]* 4.3 Write property test for title fallback
    - **Property 5: SEO title fallback produces non-empty output**
    - **Validates: Requirements 3.6**

  - [ ]* 4.4 Write property test for description fallback
    - **Property 6: Meta description fallback is bounded and HTML-stripped**
    - **Validates: Requirements 3.7**

  - [x] 4.5 Implement SEO analysis and scoring system
    - Create SEO analyzer for focus keyword analysis
    - Implement readability scoring algorithm
    - Add score computation with color indicators
    - _Requirements: 4.2, 4.3, 4.4, 4.5_

  - [ ]* 4.6 Write property test for SEO score calculation
    - **Property 7: SEO score is proportional to passing checks**
    - **Validates: Requirements 4.2, 4.3**

  - [ ]* 4.7 Write property test for score color mapping
    - **Property 8: Score color mapping is total and exhaustive**
    - **Validates: Requirements 4.4**

  - [ ]* 4.8 Write property test for readability score bounds
    - **Property 9: Readability score is bounded**
    - **Validates: Requirements 4.5**

- [x] 5. Checkpoint - Ensure core infrastructure tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Implement Schema Module for structured data
  - [x] 6.1 Create Schema_Builder helper class
    - Build JSON-LD graphs for WebSite, WebPage, Article, BreadcrumbList
    - Add Organization and Person schema support
    - Implement WooCommerce Product schema integration
    - Support FAQPage schema from structured data
    - _Requirements: 5.1, 5.2, 5.3, 5.5, 5.7_

  - [ ]* 6.2 Write property test for schema graph completeness
    - **Property 10: Schema graph contains all required types**
    - **Validates: Requirements 5.2**

  - [x] 6.3 Create Schema module with JSON-LD output
    - Output single script tag with all schema graphs
    - Allow per-post schema type override
    - Integrate with WooCommerce for product data
    - _Requirements: 5.1, 5.4, 5.6_

- [x] 7. Implement Sitemap Module with performance optimization
  - [x] 7.1 Create Sitemap_Generator with file-based storage
    - Generate index and child sitemaps as physical files
    - Store file paths in Object Cache, not XML content
    - Implement lock pattern to prevent cache stampede
    - Support image entries for posts with featured images
    - _Requirements: 6.1, 6.2, 6.4, 6.7_

  - [ ]* 7.2 Write property test for sitemap cache storage
    - **Property 11: Sitemap cache stores file paths, not XML content**
    - **Validates: Requirements 6.2**

  - [ ]* 7.3 Write property test for sitemap lock exclusivity
    - **Property 12: Sitemap lock is mutually exclusive**
    - **Validates: Requirements 6.4**

  - [x] 7.4 Create Sitemap module with URL interception
    - Intercept meowseo-sitemap.xml requests early
    - Serve files directly bypassing WordPress template loading
    - Invalidate cache on post save and schedule regeneration
    - _Requirements: 6.3, 6.6_

  - [ ]* 7.5 Write property test for noindex exclusion
    - **Property 13: Noindex posts are excluded from sitemaps**
    - **Validates: Requirements 6.8**

- [x] 8. Implement Redirect Module with database-level matching
  - [x] 8.1 Create redirect matching algorithm
    - Implement exact-match query with indexed source_url
    - Add regex fallback for pattern-based redirects
    - Never load all redirect rules into PHP memory
    - Support redirect types: 301, 302, 307, 410
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [ ]* 8.2 Write property test for redirect matching correctness
    - **Property 14: Redirect matching algorithm correctness**
    - **Validates: Requirements 7.2, 7.3, 7.4**

  - [x] 8.3 Create Redirect module with hit tracking
    - Hook into wp action early for redirect checking
    - Log hit counts and last-accessed timestamps
    - Implement REST endpoints for redirect CRUD operations
    - _Requirements: 7.6, 7.7_

- [x] 9. Implement 404 Monitor Module with buffered logging
  - [x] 9.1 Create 404 detection and buffering system
    - Detect 404 responses and buffer in Object Cache
    - Use per-minute bucket keys for efficient batching
    - Prevent synchronous database writes during requests
    - _Requirements: 8.1, 8.2_

  - [ ]* 9.2 Write property test for 404 buffering
    - **Property 15: 404 buffering prevents synchronous DB writes**
    - **Validates: Requirements 8.1**

  - [x] 9.3 Create 404 flush mechanism with WP-Cron
    - Register 60-second cron interval for buffer flushing
    - Use bulk INSERT with ON DUPLICATE KEY UPDATE
    - Preserve total hit counts across flush operations
    - _Requirements: 8.3, 8.4_

  - [ ]* 9.4 Write property test for hit count preservation
    - **Property 16: 404 flush preserves total hit counts**
    - **Validates: Requirements 8.3**

  - [x] 9.5 Add 404 log REST API endpoints
    - Provide paginated 404 log access
    - Implement log entry deletion functionality
    - _Requirements: 8.5, 8.6_

- [x] 10. Checkpoint - Ensure core modules are working
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Implement Internal Links Module
  - [x] 11.1 Create link scanning and analysis system
    - Parse post content with DOMDocument for link extraction
    - Store link data in meowseo_link_checks table
    - Schedule HTTP status checks via WP-Cron
    - Filter to internal URLs only (same host)
    - _Requirements: 9.1, 9.2, 9.3_

  - [x] 11.2 Add link suggestion system
    - Surface link suggestions in Gutenberg sidebar
    - Base suggestions on keyword overlap analysis
    - Provide REST endpoint for link health data
    - _Requirements: 9.4, 9.5_

- [x] 12. Implement GSC Module with rate-limited API integration
  - [x] 12.1 Create GSC authentication and credential storage
    - Implement OAuth 2.0 flow for Google Search Console
    - Store encrypted credentials using WordPress secret keys
    - Never expose raw credentials via REST endpoints
    - _Requirements: 10.1, 15.6_

  - [ ]* 12.2 Write property test for credential encryption
    - **Property 21: Credential encryption round-trip is lossless**
    - **Validates: Requirements 15.6**

  - [x] 12.3 Create GSC queue processing system
    - Enqueue all Google API calls instead of synchronous execution
    - Process maximum 10 queue entries per WP-Cron execution
    - Implement exponential backoff for HTTP 429 responses
    - _Requirements: 10.2, 10.3, 10.4_

  - [ ]* 12.4 Write property test for queue processing limit
    - **Property 17: GSC queue processor respects the 10-item limit**
    - **Validates: Requirements 10.3**

  - [ ]* 12.5 Write property test for exponential backoff
    - **Property 18: GSC exponential backoff delay is correct**
    - **Validates: Requirements 10.4**

  - [x] 12.6 Create GSC data storage and REST API
    - Store performance data in meowseo_gsc_data table
    - Provide REST endpoints for GSC data access
    - Display performance summary in Gutenberg sidebar
    - _Requirements: 10.5, 10.6, 10.7_

- [x] 13. Implement Social Module for Open Graph and Twitter Cards
  - [x] 13.1 Create social meta tag output system
    - Output Open Graph meta tags in wp_head
    - Output Twitter Card meta tags in wp_head
    - Support per-post social title, description, image overrides
    - _Requirements: 11.1, 11.2, 11.3_

  - [x] 13.2 Add social image fallback logic
    - Fall back from per-post to featured image to global default
    - Expose social meta fields via REST API
    - _Requirements: 11.4, 11.5_

- [x] 14. Implement WooCommerce Module (conditional)
  - [x] 14.1 Create WooCommerce-specific SEO enhancements
    - Extend Meta module for product post type support
    - Add SEO score columns to WooCommerce product list
    - _Requirements: 12.1, 12.4_

  - [x] 14.2 Add WooCommerce Product schema support
    - Output Product JSON-LD with price, availability, reviews
    - Include product URLs in sitemaps with stock filtering
    - _Requirements: 12.2, 12.3_

- [x] 15. Implement REST API layer for headless support
  - [x] 15.1 Create meowseo/v1 REST namespace
    - Register all REST endpoints under meowseo/v1
    - Implement meta CRUD endpoints with proper authentication
    - Add schema endpoint for JSON-LD access
    - _Requirements: 13.1, 13.2, 13.4_

  - [x] 15.2 Add caching headers for headless deployments
    - Include Cache-Control headers on GET responses
    - Support CDN and edge caching for headless sites
    - _Requirements: 13.6_

  - [x] 15.3 Create WPGraphQL integration (conditional)
    - Register seo field on all queryable post types
    - Expose title, description, robots, canonical, schema data
    - Include OpenGraph and Twitter Card sub-fields
    - _Requirements: 13.5_

- [x] 16. Implement Gutenberg integration and JavaScript layer
  - [x] 16.1 Create meowseo/data Redux store
    - Register store via @wordpress/data
    - Implement state shape for meta, analysis, UI
    - Add selectors and actions for SEO data management
    - _Requirements: 3.2, 3.3_

  - [x] 16.2 Create ContentSyncHook for editor integration
    - Single useEffect subscribing to core/editor
    - Read post content, title, excerpt, slug
    - Dispatch derived SEO signals to meowseo/data only
    - Never dispatch back to core/editor from useEffect
    - _Requirements: 3.3, 4.1, 4.6_

  - [x] 16.3 Create Gutenberg sidebar with tabbed interface
    - Register PluginSidebar with tab navigation
    - Implement MetaTab for SEO title, description, robots, canonical
    - Add AnalysisTab for SEO score and readability indicators
    - Create SocialTab for Open Graph and Twitter Card overrides
    - Add SchemaTab for schema type selection
    - _Requirements: 3.2, 4.4, 11.3_

  - [x] 16.4 Add advanced sidebar tabs
    - Create LinksTab for internal link suggestions
    - Add GscTab for Google Search Console performance data
    - Implement real-time SEO analysis updates
    - _Requirements: 9.4, 10.7_

  - [x] 16.5 Implement postmeta persistence
    - Use useEntityProp for WordPress postmeta integration
    - Ensure all sidebar components read from meowseo/data store
    - Handle save state and error conditions gracefully
    - _Requirements: 3.4_

- [x] 17. Checkpoint - Ensure frontend integration works
  - Ensure all tests pass, ask the user if questions arise.

- [x] 18. Implement admin interface and settings
  - [x] 18.1 Create admin menu and settings page
    - Add top-level admin menu page
    - Render React-based settings UI
    - Load meowseo-editor asset handle
    - _Requirements: 2.4_

  - [x] 18.2 Add settings validation and nonce verification
    - Validate settings via REST API with nonce checks
    - Verify manage_options capability for all admin operations
    - Include WooCommerce-specific settings when active
    - _Requirements: 2.3, 2.5_

- [x] 19. Implement security measures
  - [x] 19.1 Add comprehensive input validation and sanitization
    - Use $wpdb->prepare() for all database queries
    - Verify WordPress nonces on all REST mutation endpoints
    - Check user capabilities before processing requests
    - _Requirements: 15.1, 15.2, 15.3_

  - [x] 19.2 Add output escaping and XSS prevention
    - Escape all user-supplied values in HTML output
    - Use appropriate WordPress escaping functions
    - Secure credential storage with encryption
    - _Requirements: 15.4, 15.6_

- [x] 20. Implement performance optimizations
  - [x] 20.1 Add comprehensive caching strategy
    - Cache SEO meta in Object Cache to eliminate DB queries
    - Implement cache group isolation for meowseo data
    - Add transient fallback when Object Cache unavailable
    - _Requirements: 14.1, 14.2, 14.3_

  - [ ]* 20.2 Write property test for cached post performance
    - **Property 20: Cached posts require zero DB queries on frontend**
    - **Validates: Requirements 14.1**

  - [x] 20.3 Optimize asset loading
    - Load frontend assets only when required by active modules
    - Serve sitemap files directly from filesystem
    - Avoid loading redirect rules into PHP memory
    - _Requirements: 14.4, 14.5, 14.6_

- [x] 21. Add comprehensive error handling
  - [x] 21.1 Implement PHP error handling strategy
    - Add version compatibility checks in plugin entry point
    - Wrap module boot() calls in try/catch blocks
    - Handle Object Cache unavailability gracefully
    - Add sitemap lock contention handling
    - _Requirements: 1.6_

  - [x] 21.2 Add JavaScript error handling
    - Handle store initialization failures with fallback UI
    - Wrap ContentSyncHook in try/catch for analysis errors
    - Add REST API error handling with user feedback
    - Handle WPGraphQL registration errors gracefully

- [x] 22. Create comprehensive test suite
  - [x] 22.1 Write unit tests for all core classes
    - Test Plugin singleton and Module_Manager
    - Test Options class and Installer functionality
    - Test all helper classes (Cache, DB, Schema_Builder)
    - Test each module's core functionality

  - [x] 22.2 Write integration tests for module interactions
    - Test redirect module with database seeding
    - Test sitemap generation with multiple post types
    - Test GSC queue processing across multiple cron cycles
    - Test 404 flush with concurrent hit simulation
    - Test WPGraphQL field registration and queries

  - [ ]* 22.3 Complete property-based test implementation
    - Implement all 21 correctness properties using eris/eris (PHP)
    - Add JavaScript property tests using fast-check
    - Ensure minimum 100 iterations per property test
    - Tag all property tests with feature and property references

- [ ] 23. Final integration and deployment preparation
  - [ ] 23.1 Create plugin activation and deactivation hooks
    - Run dbDelta() for all custom tables on activation
    - Handle plugin deactivation cleanup
    - Implement uninstall.php with conditional data deletion
    - _Requirements: 1.5_

  - [ ] 23.2 Add plugin metadata and documentation
    - Create plugin header with version, description, requirements
    - Add README with installation and configuration instructions
    - Document REST API endpoints and WPGraphQL schema
    - Include performance optimization guidelines

  - [ ] 23.3 Final testing and quality assurance
    - Run complete test suite including property-based tests
    - Test plugin with various WordPress and PHP versions
    - Verify WooCommerce integration when plugin is active
    - Test headless deployment scenarios with REST API and WPGraphQL

- [ ] 24. Final checkpoint - Complete plugin ready for deployment
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional property-based tests and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation and user feedback
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The plugin uses PHP 8.0+ and WordPress 6.0+ as minimum requirements
- All modules follow the modular loading pattern - only enabled modules are instantiated
- Performance is prioritized with Object Cache usage, database-level operations, and minimal memory usage