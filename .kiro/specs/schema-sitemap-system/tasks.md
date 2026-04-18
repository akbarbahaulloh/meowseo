# Implementation Plan: Schema Generator and XML Sitemap System

## Overview

This implementation plan covers the Schema Generator and XML Sitemap System for MeowSEO. The core schema system and basic sitemap functionality have been implemented. This updated plan focuses on completing missing features, refactoring the sitemap system to match the design specifications, and adding advanced functionality.

## Current Status

**Completed:**
- Schema system foundation (Abstract_Schema_Node, Schema_Builder)
- Core schema node builders (WebSite, Organization, WebPage, Article, Product, FAQ, Breadcrumb)
- Schema_Module with caching, REST API, and WPGraphQL integration
- Breadcrumbs system with shortcode and template function
- Basic sitemap generation (Sitemap module and Sitemap_Generator)

**Remaining:**
- Refactor sitemap system to use lock pattern and filesystem caching
- Add missing schema nodes (HowTo, LocalBusiness)
- Implement video and news sitemaps
- Add Gutenberg sidebar integration for schema configuration
- Implement WP-CLI commands
- Add comprehensive error handling and validation
- Implement filter and action hooks

## Tasks

- [x] 1. Set up Schema System foundation
  - [x] 1.1 Create Abstract_Schema_Node base class
    - _Requirements: 1.1, 1.7_
  
  - [x] 1.2 Write property test for Abstract_Schema_Node
    - **Property 6: Consistent @id Format**
    - **Validates: Requirements 1.7**
  
  - [x] 1.3 Create Schema_Builder core engine
    - _Requirements: 1.1, 1.2, 1.3_
  
  - [ ] 1.4 Write property tests for Schema_Builder
    - **Property 1: Schema Output is Valid JSON-LD Script Tag**
    - **Validates: Requirements 1.1, 2.3**
    - **Property 2: Required Schema Nodes Always Present**
    - **Validates: Requirements 1.3**

- [x] 2. Implement core schema node builders
  - [x] 2.1 Create WebSite_Node builder
    - _Requirements: 1.3, 1.8_
  
  - [ ] 2.2 Write property test for WebSite_Node
    - **Property 7: WebSite Node Contains SearchAction**
    - **Validates: Requirements 1.8**
  
  - [x] 2.3 Create Organization_Node builder
    - _Requirements: 1.3, 1.9_
  
  - [ ] 2.4 Write property test for Organization_Node
    - **Property 8: Organization Node Contains Required Properties**
    - **Validates: Requirements 1.9**
  
  - [x] 2.5 Create WebPage_Node builder
    - _Requirements: 1.3, 1.10_
  
  - [ ] 2.6 Write property test for WebPage_Node
    - **Property 9: WebPage Type Varies by Context**
    - **Validates: Requirements 1.10**
    - **Property 33: Date Properties Use ISO 8601 Format**
    - **Validates: Requirements 17.4**

- [x] 3. Implement content-specific schema node builders
  - [x] 3.1 Create Article_Node builder
    - _Requirements: 1.4, 1.11, 20.1, 20.2_
  
  - [ ] 3.2 Write property tests for Article_Node
    - **Property 3: Article Node Conditional Inclusion**
    - **Validates: Requirements 1.4**
    - **Property 10: Article Node Contains Speakable**
    - **Validates: Requirements 1.11, 20.1, 20.2**
  
  - [x] 3.3 Create Product_Node builder for WooCommerce
    - _Requirements: 1.5, 11.1, 11.2, 11.3_
  
  - [ ] 3.4 Write property tests for Product_Node
    - **Property 4: Product Node Conditional Inclusion**
    - **Validates: Requirements 1.5, 11.1**
    - **Property 28: Product Schema Contains Required Properties**
    - **Validates: Requirements 11.2**
    - **Property 29: Product Offers Contains Price and Availability**
    - **Validates: Requirements 11.3**
  
  - [x] 3.5 Create FAQ_Node builder
    - _Requirements: 1.6, 9.2_
  
  - [ ] 3.6 Write property tests for FAQ_Node
    - **Property 5: FAQ Node Conditional Inclusion**
    - **Validates: Requirements 1.6**
    - **Property 27: Multiple Schema Types Coexist**
    - **Validates: Requirements 10.1**

- [ ] 4. Add missing schema node builders
  - [ ] 4.1 Create HowTo_Node builder
    - Extend Abstract_Schema_Node
    - Implement is_needed() checking schema_type "HowTo"
    - Read HowTo steps from _meowseo_schema_config postmeta
    - Generate step array with name, text, and image fields
    - Include in @graph when schema_type is "HowTo"
    - _Requirements: 9.3, 10.2_
  
  - [ ] 4.2 Write property test for HowTo_Node
    - **Property: HowTo Node Conditional Inclusion**
    - **Validates: Requirements 10.2**
  
  - [ ] 4.3 Create LocalBusiness_Node builder
    - Extend Abstract_Schema_Node
    - Implement is_needed() checking schema_type "LocalBusiness"
    - Read business information from _meowseo_schema_config postmeta
    - Include address, phone, hours, geo coordinates
    - Generate PostalAddress and GeoCoordinates sub-objects
    - _Requirements: 9.4_
  
  - [ ] 4.4 Write property test for LocalBusiness_Node
    - **Property: LocalBusiness Node Contains Required Properties**
    - **Validates: Requirements 9.4**

- [x] 5. Implement Schema_Module integration
  - [x] 5.1 Create Schema_Module class
    - _Requirements: 2.1, 2.2, 2.7_
  
  - [x] 5.2 Implement schema caching in Schema_Module
    - _Requirements: 2.6_
  
  - [ ] 5.3 Write property test for schema caching
    - **Property 11: Schema Cache Reuse**
    - **Validates: Requirements 2.6**
  
  - [x] 5.4 Implement REST API endpoints in Schema_Module
    - _Requirements: 2.4, 2.5, 14.1, 14.2, 14.3, 14.4_
  
  - [ ] 5.5 Write property test for REST API
    - **Property 12: REST API Response Structure**
    - **Validates: Requirements 2.5**
  
  - [x] 5.6 Implement WPGraphQL integration
    - _Requirements: 14.6_

- [x] 6. Implement Breadcrumbs system
  - [x] 6.1 Create Breadcrumbs class
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_
  
  - [ ] 6.2 Write property tests for Breadcrumbs
    - **Property 24: Breadcrumb Trail Structure for Posts**
    - **Validates: Requirements 8.2**
    - **Property 25: Breadcrumb Trail Structure for Pages**
    - **Validates: Requirements 8.3**
  
  - [x] 6.3 Implement Breadcrumbs render() method
    - _Requirements: 8.7, 18.1, 18.2, 18.5, 18.6_
  
  - [ ] 6.4 Write property tests for Breadcrumbs HTML
    - **Property 26: Breadcrumb HTML Contains Schema.org Microdata**
    - **Validates: Requirements 8.7, 18.6**
    - **Property 35: Breadcrumb Separator Customization**
    - **Validates: Requirements 18.2**
  
  - [x] 6.5 Create Breadcrumb_Node builder
    - _Requirements: 1.3, 8.10_
  
  - [x] 6.6 Register breadcrumb shortcode and template function
    - _Requirements: 8.8, 8.9_

- [x] 7. Checkpoint - Review schema system
  - Ensure all schema nodes are working correctly
  - Verify caching is functioning properly
  - Test REST API and WPGraphQL integration

- [x] 8. Refactor Sitemap System to use lock pattern
  - [x] 8.1 Create Sitemap_Cache class with lock pattern
    - Create new class in includes/modules/sitemap/class-sitemap-cache.php
    - Add properties: cache_dir, lock_timeout
    - Implement ensure_directory_exists() with wp_mkdir_p()
    - Implement get_file_path() helper method
    - Implement is_fresh() checking file modification time (24 hour TTL)
    - Add .htaccess to deny direct access to cache directory
    - _Requirements: 4.1_
  
  - [x] 8.2 Implement Sitemap_Cache basic operations
    - Implement get(name) reading XML from filesystem
    - Implement set(name, xml_content) writing XML to filesystem
    - Implement invalidate(name) deleting specific file
    - Implement invalidate_all() deleting all files
    - Store file paths in Object Cache, not content
    - _Requirements: 4.2, 4.3, 4.4, 4.5, 4.6_
  
  - [ ] 8.3 Write property tests for Sitemap_Cache
    - **Property 13: Sitemap Files Stored in Correct Directory**
    - **Validates: Requirements 4.1**
    - **Property 14: Cache Stores Paths Not Content**
    - **Validates: Requirements 4.2**
    - **Property 15: Set Then Get Round Trip**
    - **Validates: Requirements 4.4**
    - **Property 16: Invalidate Deletes Files**
    - **Validates: Requirements 4.5**
  
  - [x] 8.4 Implement Sitemap_Cache lock pattern
    - Implement acquire_lock(name) using Cache::add()
    - Implement release_lock(name) using Cache::delete()
    - Implement get_stale_file(name) for stale-while-revalidate
    - Implement get_or_generate(name, generator) with lock logic
    - Return stale file on lock failure, 503 if no stale file
    - _Requirements: 4.7, 4.8, 4.9_
  
  - [ ] 8.5 Write property tests for lock pattern
    - **Property 17: Stale File Served on Lock Failure**
    - **Validates: Requirements 4.8**
    - **Property 18: 503 Response When No Stale File**
    - **Validates: Requirements 4.9**

- [x] 9. Refactor Sitemap_Builder to use Sitemap_Cache
  - [x] 9.1 Update Sitemap_Builder class
    - Add Sitemap_Cache dependency
    - Route all generation through Sitemap_Cache get_or_generate()
    - Update build_index() to use cache
    - Update build_posts() to use cache
    - _Requirements: 5.1_
  
  - [x] 9.2 Optimize build_posts() with performance improvements
    - Use direct database query with LEFT JOIN to exclude noindex
    - Call update_post_meta_cache() before loops
    - Paginate at 1000 URLs per sitemap
    - Include lastmod in ISO 8601 format
    - _Requirements: 5.3, 5.5, 5.6, 5.10, 12.1, 12.2, 12.3_
  
  - [ ] 9.3 Write property tests for build_posts()
    - **Property 20: Noindex Posts Excluded from Sitemap**
    - **Validates: Requirements 5.4, 19.1**
    - **Property 21: Sitemap Pagination at 1000 URLs**
    - **Validates: Requirements 5.5**
    - **Property 34: Sitemap Excludes Unpublished Posts**
    - **Validates: Requirements 19.2**

- [x] 10. Implement advanced sitemap features
  - [x] 10.1 Implement build_news() method
    - Query posts from last 48 hours only
    - Use news:news element with news:publication
    - Include news:name, news:language, news:title
    - Exclude noindex posts
    - Route through Sitemap_Cache
    - _Requirements: 5.7, 16.1, 16.2, 16.3, 16.4, 16.5, 16.6_
  
  - [ ] 10.2 Write property test for news sitemap
    - **Property 22: News Sitemap Contains Only Recent Posts**
    - **Validates: Requirements 5.7, 16.1**
  
  - [x] 10.3 Implement video embed detection
    - Create detect_video_embeds() scanning for YouTube/Vimeo
    - Use regex patterns for YouTube and Vimeo URLs
    - Implement get_youtube_metadata() using oEmbed API
    - Implement get_vimeo_metadata() using oEmbed API
    - Cache video metadata for 24 hours
    - _Requirements: 5.8, 5.9, 15.2, 15.3, 15.5_
  
  - [x] 10.4 Implement build_video() method
    - Scan post content for video embeds
    - Include video:video element with metadata
    - Add video:title, video:description, video:thumbnail_loc, video:content_loc
    - Only include posts with at least one video
    - Route through Sitemap_Cache
    - _Requirements: 5.8, 15.4, 15.6, 15.7_
  
  - [ ] 10.5 Write property tests for video sitemap
    - **Property 23: Video Sitemap Contains Only Posts with Videos**
    - **Validates: Requirements 5.8, 15.7**
    - **Property 31: Video Sitemap Includes Metadata**
    - **Validates: Requirements 15.6**
  
  - [x] 10.6 Enhance image extension for sitemaps
    - Verify featured image handling in Sitemap_Generator
    - Ensure image:image element with image:loc is included
    - _Requirements: 15.1_
  
  - [ ] 10.7 Write property test for image extension
    - **Property 30: Sitemap Image Extension for Featured Images**
    - **Validates: Requirements 15.1**

- [x] 11. Update Sitemap_Module to use new cache system
  - [x] 11.1 Refactor Sitemap_Module class
    - Update to use Sitemap_Cache instead of direct file operations
    - Update intercept_request() to use cache get_or_generate()
    - Ensure proper Content-Type and X-Robots-Tag headers
    - _Requirements: 3.7, 3.8, 12.7, 14.5_
  
  - [x] 11.2 Update cache invalidation hooks
    - Update save_post hook to use Sitemap_Cache invalidate()
    - Update delete_post hook to use Sitemap_Cache invalidate()
    - Update term hooks to use Sitemap_Cache invalidate()
    - Implement stale-while-revalidate behavior
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.7_
  
  - [x] 11.3 Update scheduled regeneration
    - Ensure WP-Cron event uses Sitemap_Cache
    - Pre-generate all sitemaps to ensure fresh cache
    - _Requirements: 6.5, 6.6_

- [x] 12. Implement Sitemap_Ping notifications
  - [x] 12.1 Create Sitemap_Ping class
    - Create new class in includes/modules/sitemap/class-sitemap-ping.php
    - Add property: rate_limit (3600 seconds)
    - Implement ping(sitemap_url) method
    - Implement should_ping() checking last ping time
    - Implement update_last_ping_time() storing timestamp
    - Implement get_ping_urls() returning Google and Bing endpoints
    - _Requirements: 7.1, 7.4, 7.5_
  
  - [x] 12.2 Hook Sitemap_Ping into events
    - Hook into daily regeneration cron event
    - Hook into new post publication
    - Use wp_remote_get() for HTTP requests
    - _Requirements: 7.2, 7.3, 7.6_

- [x] 13. Checkpoint - Test sitemap system
  - Verify lock pattern prevents cache stampede
  - Test stale-while-revalidate behavior
  - Verify sitemap ping functionality
  - Test cache invalidation triggers

- [x] 14. Implement Gutenberg sidebar integration
  - [x] 14.1 Add schema type selector to sidebar
    - Display dropdown with options: Article, WebPage, FAQPage, HowTo, LocalBusiness, Product
    - Save selection to _meowseo_schema_type postmeta
    - Load current value on editor load
    - _Requirements: 9.1, 9.5_
  
  - [x] 14.2 Add FAQ editor for FAQPage schema type
    - Show FAQ item editor when FAQPage selected
    - Provide fields for question and answer
    - Allow adding/removing FAQ items
    - Save to _meowseo_schema_config postmeta as JSON
    - _Requirements: 9.2, 9.6_
  
  - [x] 14.3 Add HowTo editor for HowTo schema type
    - Show step editor when HowTo selected
    - Provide fields for name, text, and image
    - Allow adding/removing steps
    - Save to _meowseo_schema_config postmeta as JSON
    - _Requirements: 9.3, 9.6_
  
  - [x] 14.4 Add LocalBusiness fields for LocalBusiness schema type
    - Show business information fields when LocalBusiness selected
    - Include address, phone, hours fields
    - Save to _meowseo_schema_config postmeta as JSON
    - _Requirements: 9.4, 9.6_
  
  - [x] 14.5 Add speakable content toggle
    - Provide toggle to mark block as speakable
    - Add id="meowseo-direct-answer" to marked block
    - Save block ID to _meowseo_speakable_block postmeta
    - _Requirements: 20.3, 20.4, 20.5_

- [x] 15. Implement comprehensive error handling
  - [x] 15.1 Add error handling to Schema_Builder
    - Implement validate_node() checking @type and @id
    - Log warnings for missing required properties
    - Skip invalid nodes from @graph
    - Handle invalid date formats with fallback
    - Handle missing images gracefully
    - _Requirements: 13.3, 17.1, 17.2, 17.3_
  
  - [ ] 15.2 Write property test for schema validation
    - **Property 32: Schema Validation Skips Invalid Nodes**
    - **Validates: Requirements 17.1, 17.2**
  
  - [x] 15.3 Add error handling to Sitemap_Cache
    - Log errors on directory creation failure
    - Log errors on file write failure
    - Log warnings on lock acquisition failure
    - Log errors when no stale file available
    - Include context data in all logs
    - _Requirements: 13.1, 13.2, 13.4_
  
  - [x] 15.4 Add error handling to Sitemap_Builder
    - Log errors on database query failure
    - Handle empty result sets gracefully
    - Log lock timeout events
    - _Requirements: 13.1_
  
  - [x] 15.5 Add error handling to Breadcrumbs
    - Handle invalid post hierarchy gracefully
    - Log warnings on get_post_ancestors() errors
    - Provide fallback trail on errors
    - _Requirements: 13.1_

- [x] 16. Implement filter and action hooks
  - [x] 16.1 Add schema filter hooks
    - Add meowseo_schema_graph filter for modifying @graph
    - Add meowseo_schema_node_{type} filter for individual nodes
    - Add meowseo_schema_type filter for schema type detection
    - Add meowseo_schema_social_profiles filter
    - _Requirements: Design section "Filter Hooks"_
  
  - [x] 16.2 Add sitemap filter hooks
    - Add meowseo_sitemap_post_types filter
    - Add meowseo_sitemap_exclude_post filter
    - Add meowseo_sitemap_url_entry filter
    - Add meowseo_sitemap_xml filter
    - _Requirements: Design section "Filter Hooks", 19.4, 19.5_
  
  - [x] 16.3 Add breadcrumbs filter hooks
    - Add meowseo_breadcrumb_trail filter
    - Add meowseo_breadcrumb_html filter
    - Add meowseo_breadcrumb_separator filter
    - _Requirements: Design section "Filter Hooks", 18.3, 18.4_
  
  - [x] 16.4 Add schema action hooks
    - Add meowseo_before_schema_output action
    - Add meowseo_after_schema_output action
    - Add meowseo_schema_cache_invalidated action
    - _Requirements: Design section "Action Hooks"_
  
  - [x] 16.5 Add sitemap action hooks
    - Add meowseo_before_sitemap_generation action
    - Add meowseo_after_sitemap_generation action
    - Add meowseo_sitemap_cache_invalidated action
    - Add meowseo_sitemap_ping_sent action
    - _Requirements: Design section "Action Hooks"_

- [x] 17. Implement WP-CLI commands
  - [x] 17.1 Add schema WP-CLI commands
    - Implement wp meowseo schema generate <post_id>
    - Implement wp meowseo schema validate <post_id>
    - Implement wp meowseo schema clear-cache [--post_id=<id>]
    - _Requirements: Design section "WP-CLI Commands"_
  
  - [x] 17.2 Add sitemap WP-CLI commands
    - Implement wp meowseo sitemap generate
    - Implement wp meowseo sitemap generate <type> [--page=<page>]
    - Implement wp meowseo sitemap clear-cache [--type=<type>]
    - Implement wp meowseo sitemap ping
    - _Requirements: Design section "WP-CLI Commands"_

- [x] 18. Add debug mode and health checks
  - [x] 18.1 Implement debug mode for schema
    - Check WP_DEBUG constant
    - Output validation errors as HTML comments
    - Enable test mode for schema validation
    - _Requirements: 17.5, 17.6_
  
  - [x] 18.2 Implement debug mode for sitemaps
    - Output generation stats in XML comments
    - Include timing and memory usage
    - Log cache hit/miss rates
    - _Requirements: Design section "Monitoring and Debugging"_
  
  - [x] 18.3 Add health check commands
    - Implement wp meowseo health check-schema
    - Implement wp meowseo health check-sitemap-cache
    - Implement wp meowseo health check-permissions
    - _Requirements: Design section "Monitoring and Debugging"_

- [x] 19. Final integration and configuration
  - [x] 19.1 Create configuration options UI
    - Add schema settings to admin panel
    - Add sitemap settings to admin panel
    - Include organization name, logo, social profiles
    - Include sitemap post types, news/video toggles
    - _Requirements: Design section "Configuration Options"_
  
  - [x] 19.2 Add security measures
    - Validate file paths to prevent directory traversal
    - Sanitize all user input in schema configuration
    - Validate JSON structure before saving
    - Escape all output in schema JSON-LD
    - Check user capabilities for configuration
    - _Requirements: Design section "Security Considerations"_
  
  - [ ] 19.3 Write integration tests
    - Test Schema_Module wp_head integration
    - Test Sitemap_Module rewrite rules
    - Test cache invalidation triggers
    - Test REST API endpoints
    - Test WPGraphQL integration
    - Test WooCommerce Product schema

- [x] 20. Final checkpoint and documentation
  - Ensure all tests pass
  - Verify performance benchmarks are met
  - Update README documentation
  - Create migration guide from Yoast/RankMath

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property tests validate universal correctness properties from the design document
- The implementation uses PHP 7.4+ with typed properties
- Filesystem caching with lock pattern prevents cache stampede on high-traffic sites
- Direct database queries optimize performance for sites with 50,000+ posts
- All schema nodes use consistent @id format for Google Knowledge Graph resolution

## Priority Focus Areas

1. **Sitemap System Refactoring** (Tasks 8-11): Critical for performance and scalability
2. **Missing Schema Nodes** (Task 4): Required for feature completeness
3. **Gutenberg Integration** (Task 14): Important for user experience
4. **Error Handling** (Task 15): Essential for production reliability
5. **WP-CLI Commands** (Task 17): Useful for debugging and maintenance
