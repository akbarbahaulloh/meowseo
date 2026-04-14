# Implementation Plan: Meta Module Rebuild

## Overview

This implementation plan transforms the current basic meta tag implementation into a sophisticated, maintainable architecture with 7 specialized classes. The rebuild follows a phased approach: Core Classes → Output & Registration → Global & Robots → Integration & Testing. Each task builds incrementally, with property-based tests validating the 29 correctness properties defined in the design document.

## Tasks

- [x] 1. Set up Meta Module directory structure and base files
  - Create `includes/modules/meta/` directory structure
  - Create empty class files for all 7 components
  - Set up PHPUnit test directory structure at `tests/modules/meta/`
  - Set up property test directory at `tests/properties/`
  - Install eris/eris for property-based testing via composer
  - _Requirements: 1.1_

- [x] 2. Implement Meta_Module entry point with hook registration
  - [x] 2.1 Create Meta_Module class implementing Module interface
    - Implement `boot()` method with all hook registrations
    - Implement `get_id()` returning 'meta'
    - Register wp_head hook with priority 1
    - Register document_title_parts filter
    - Register save_post, rest_api_init, enqueue_block_editor_assets hooks
    - Call `remove_theme_support('title-tag')` to prevent duplicate titles
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_
  
  - [x] 2.2 Write unit tests for Meta_Module
    - Test hook registration verification
    - Test Module interface implementation
    - Test get_id() returns correct value
    - _Requirements: 1.1_

- [x] 3. Implement Title_Patterns class with parser and variable system
  - [x] 3.1 Create Title_Patterns class with variable replacement
    - Define VARIABLES constant with all 10 supported variables
    - Implement `resolve(pattern, context)` method
    - Implement `replace_variables()` private method
    - Implement `get_variable_value()` for each variable type
    - Implement `get_pattern_for_post_type()` and `get_pattern_for_page_type()`
    - Implement `get_default_patterns()` returning all default patterns
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7_
  
  - [x] 3.2 Implement pattern parser and pretty printer
    - Implement `parse(pattern)` method returning structured representation
    - Implement `print(structured)` method for round-trip
    - Implement `validate(pattern)` checking balanced braces and valid variables
    - Return error objects for invalid patterns
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_
  
  - [x] 3.3 Write property test for variable replacement completeness
    - **Property 18: Variable Replacement Completeness**
    - **Validates: Requirements 8.1, 8.4, 8.6**
  
  - [x] 3.4 Write property test for missing variable handling
    - **Property 19: Missing Variable Handling**
    - **Validates: Requirements 8.5**
  
  - [x] 3.5 Write property test for pagination variable conditional
    - **Property 20: Pagination Variable Conditional**
    - **Validates: Requirements 8.7**
  
  - [x] 3.6 Write property test for title pattern round-trip
    - **Property 28: Title Pattern Round-Trip**
    - **Validates: Requirements 12.1, 12.2, 12.3**
  
  - [x] 3.7 Write property test for invalid pattern error handling
    - **Property 29: Invalid Pattern Error Handling**
    - **Validates: Requirements 12.4, 12.5, 12.6**
  
  - [x] 3.8 Write unit tests for Title_Patterns
    - Test default patterns for all page types
    - Test variable replacement with specific examples
    - Test parser with valid and invalid patterns
    - _Requirements: 8.1, 8.2, 12.1_

- [x] 4. Implement Meta_Resolver with all fallback chains
  - [x] 4.1 Implement title resolution with fallback chain
    - Implement `resolve_title(post_id)` method
    - Check `_meowseo_title` postmeta first
    - Fall back to title pattern via Title_Patterns
    - Fall back to `post_title + separator + site_name`
    - Ensure never returns empty string
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_
  
  - [x] 4.2 Implement description resolution with fallback chain
    - Implement `resolve_description(post_id)` method
    - Check `_meowseo_description` postmeta first
    - Fall back to excerpt (160 chars, HTML stripped)
    - Fall back to content (160 chars, HTML stripped)
    - Implement `truncate_text()` helper with HTML/shortcode stripping
    - Return empty string when no source available
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_
  
  - [x] 4.3 Implement Open Graph image resolution with fallback chain
    - Implement `resolve_og_image(post_id)` method
    - Check `_meowseo_og_image` postmeta (attachment ID) first
    - Fall back to featured image if width >= 1200px
    - Fall back to first content image if width >= 1200px
    - Implement `get_first_content_image()` helper
    - Implement `get_image_dimensions()` helper
    - Fall back to global default from settings
    - Return array with URL and dimensions (width, height)
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_
  
  - [x] 4.4 Implement canonical URL resolution with pagination stripping
    - Implement `resolve_canonical(post_id)` method
    - Check `_meowseo_canonical` postmeta first
    - Fall back to `get_permalink()` for singular
    - Fall back to `get_term_link()` for term archives
    - Fall back to `home_url()` for homepage
    - Implement `strip_pagination_params()` removing /page/N/, ?paged=N, ?page=N
    - Ensure always returns non-empty URL
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_
  
  - [x] 4.5 Implement robots directive resolution with merging
    - Implement `resolve_robots(post_id)` method
    - Start with base directives: index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1
    - Check `_meowseo_robots_noindex` and `_meowseo_robots_nofollow` postmeta
    - Apply automatic rules: noindex for is_search(), is_attachment(), date archives
    - Implement `merge_robots_directives()` helper
    - Always include Google Discover directives
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_
  
  - [x] 4.6 Implement Twitter Card field resolution
    - Implement `resolve_twitter_title(post_id)` method
    - Implement `resolve_twitter_description(post_id)` method
    - Implement `resolve_twitter_image(post_id)` method
    - Ensure independence from Open Graph values
    - _Requirements: 2.8_
  
  - [x] 4.7 Implement hreflang alternates resolution
    - Implement `get_hreflang_alternates()` method
    - Implement `is_wpml_active()` helper checking ICL_SITEPRESS_VERSION
    - Implement `is_polylang_active()` helper checking pll_the_languages
    - Return array of language => URL mappings
    - _Requirements: 2.9_
  
  - [x] 4.8 Write property test for title fallback chain completeness
    - **Property 10: Title Fallback Chain Completeness**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.5, 3.6**
  
  - [x] 4.9 Write property test for description fallback chain completeness
    - **Property 11: Description Fallback Chain Completeness**
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.6**
  
  - [x] 4.10 Write property test for description truncation with HTML stripping
    - **Property 12: Description Truncation with HTML Stripping**
    - **Validates: Requirements 4.5**
  
  - [x] 4.11 Write property test for OG image fallback chain completeness
    - **Property 13: OG Image Fallback Chain Completeness**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6**
  
  - [x] 4.12 Write property test for OG image dimension validation
    - **Property 14: OG Image Dimension Validation**
    - **Validates: Requirements 5.3, 5.4**
  
  - [x] 4.13 Write property test for OG image return structure
    - **Property 15: OG Image Return Structure**
    - **Validates: Requirements 5.7**
  
  - [x] 4.14 Write property test for canonical fallback chain completeness
    - **Property 16: Canonical Fallback Chain Completeness**
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5**
  
  - [x] 4.15 Write property test for canonical pagination stripping
    - **Property 4: Canonical Pagination Stripping**
    - **Validates: Requirements 2.5, 6.6**
  
  - [x] 4.16 Write property test for canonical always present
    - **Property 5: Canonical Always Present**
    - **Validates: Requirements 2.5, 6.7**
  
  - [x] 4.17 Write property test for robots directive merging
    - **Property 17: Robots Directive Merging**
    - **Validates: Requirements 7.1, 7.3, 7.4, 7.5, 7.6**
  
  - [x] 4.18 Write property test for Google Discover directives always present
    - **Property 3: Google Discover Directives Always Present**
    - **Validates: Requirements 2.4, 7.2, 7.7**
  
  - [x] 4.19 Write property test for Twitter Card independence
    - **Property 8: Twitter Card Independence**
    - **Validates: Requirements 2.8**
  
  - [x] 4.20 Write property test for conditional hreflang output
    - **Property 9: Conditional Hreflang Output**
    - **Validates: Requirements 2.9**
  
  - [x] 4.21 Write unit tests for Meta_Resolver
    - Test each fallback chain with specific examples
    - Test edge cases: empty strings, null values, invalid IDs
    - Test helper methods: truncate_text, strip_pagination_params
    - _Requirements: 3.1, 4.1, 5.1, 6.1, 7.1_

- [ ] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Implement Meta_Output for tag output in correct order
  - [ ] 6.1 Create Meta_Output class with main output method
    - Implement `output_head_tags()` method hooked to wp_head
    - Call all 7 tag group output methods in order
    - Implement `esc_meta_content()` helper for escaping
    - Implement `format_iso8601()` helper for date formatting
    - _Requirements: 2.1, 2.10_
  
  - [ ] 6.2 Implement Group A: Title tag output
    - Implement `output_title()` private method
    - Call `Meta_Resolver::resolve_title()`
    - Output `<title>` tag with esc_html()
    - _Requirements: 2.1, 2.2_
  
  - [ ] 6.3 Implement Group B: Meta description output
    - Implement `output_description()` private method
    - Call `Meta_Resolver::resolve_description()`
    - Only output tag if description is non-empty
    - Use esc_attr() for content attribute
    - _Requirements: 2.1, 2.3_
  
  - [ ] 6.4 Implement Group C: Robots meta tag output
    - Implement `output_robots()` private method
    - Call `Meta_Resolver::resolve_robots()`
    - Output `<meta name="robots">` with all directives
    - Ensure Google Discover directives always present
    - _Requirements: 2.1, 2.4_
  
  - [ ] 6.5 Implement Group D: Canonical link output
    - Implement `output_canonical()` private method
    - Call `Meta_Resolver::resolve_canonical()`
    - Output `<link rel="canonical">` with esc_url()
    - _Requirements: 2.1, 2.5_
  
  - [ ] 6.6 Implement Group E: Open Graph tags output
    - Implement `output_open_graph()` private method
    - Output tags in exact order: og:type, og:title, og:description, og:url, og:image, og:site_name, article:published_time, article:modified_time
    - Format dates with `format_iso8601()` in ISO 8601 format
    - Include og:image:width and og:image:height when available
    - _Requirements: 2.1, 2.6, 2.7_
  
  - [ ] 6.7 Implement Group F: Twitter Card tags output
    - Implement `output_twitter_card()` private method
    - Output twitter:card, twitter:title, twitter:description, twitter:image
    - Use Twitter-specific values from Meta_Resolver
    - _Requirements: 2.1, 2.8_
  
  - [ ] 6.8 Implement Group G: Hreflang alternates output
    - Implement `output_hreflang()` private method
    - Only output when WPML or Polylang detected
    - Call `Meta_Resolver::get_hreflang_alternates()`
    - Output `<link rel="alternate" hreflang="">` for each language
    - _Requirements: 2.1, 2.9_
  
  - [ ]* 6.9 Write property test for tag output order
    - **Property 1: Tag Output Order**
    - **Validates: Requirements 2.1**
  
  - [ ]* 6.10 Write property test for conditional description output
    - **Property 2: Conditional Description Output**
    - **Validates: Requirements 2.3**
  
  - [ ]* 6.11 Write property test for Open Graph tag order
    - **Property 6: Open Graph Tag Order**
    - **Validates: Requirements 2.6**
  
  - [ ]* 6.12 Write property test for ISO 8601 date formatting
    - **Property 7: ISO 8601 Date Formatting**
    - **Validates: Requirements 2.7**
  
  - [ ]* 6.13 Write unit tests for Meta_Output
    - Test each tag group output with mocked resolver
    - Test escaping functions
    - Test conditional output logic
    - _Requirements: 2.1, 2.2, 2.3_

- [ ] 7. Implement Meta_Postmeta for field registration
  - [ ] 7.1 Create Meta_Postmeta class with registration logic
    - Define META_KEYS constant with all 16 postmeta keys and types
    - Implement `register()` method calling register_post_meta() for all public post types
    - Implement `get_post_types()` helper returning all public post types
    - Implement `get_meta_args()` helper building registration args with show_in_rest: true
    - Set correct type and sanitize_callback for each field type
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7_
  
  - [ ]* 7.2 Write unit tests for Meta_Postmeta
    - Test registration for all post types
    - Test show_in_rest is true for all fields
    - Test correct type mapping
    - _Requirements: 9.1, 9.2_

- [ ] 8. Implement Global_SEO for non-singular pages
  - [ ] 8.1 Create Global_SEO class with page type detection
    - Implement `get_current_page_type()` method detecting all 10 page types
    - Implement `get_title()` method delegating to page type handlers
    - Implement `get_description()` method delegating to page type handlers
    - Implement `get_robots()` method delegating to page type handlers
    - Implement `get_canonical()` method delegating to page type handlers
    - _Requirements: 10.1_
  
  - [ ] 8.2 Implement page type handlers for all non-singular pages
    - Implement `handle_homepage()` using homepage pattern and tagline
    - Implement `handle_blog_index()` for blog index page
    - Implement `handle_category()` using category name and description
    - Implement `handle_tag()` using tag name and description
    - Implement `handle_custom_taxonomy()` for custom taxonomies
    - Implement `handle_author()` using author name and bio
    - Implement `handle_date_archive()` using date pattern
    - Implement `handle_search()` with automatic noindex
    - Implement `handle_404()` for error pages
    - Implement `handle_post_type_archive()` for CPT archives
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.7_
  
  - [ ] 8.3 Implement automatic noindex rules
    - Implement `should_noindex_author()` checking if author has < 2 published posts
    - Implement `should_noindex_date_archive()` checking settings option
    - Apply noindex to search pages automatically
    - _Requirements: 10.5, 10.6_
  
  - [ ]* 8.4 Write property test for Global SEO page type coverage
    - **Property 21: Global SEO Page Type Coverage**
    - **Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.7**
  
  - [ ]* 8.5 Write property test for author page noindex rule
    - **Property 22: Author Page Noindex Rule**
    - **Validates: Requirements 10.5**
  
  - [ ]* 8.6 Write property test for search page noindex invariant
    - **Property 23: Search Page Noindex Invariant**
    - **Validates: Requirements 10.6**
  
  - [ ]* 8.7 Write unit tests for Global_SEO
    - Test page type detection for all types
    - Test each page type handler
    - Test automatic noindex rules
    - _Requirements: 10.1, 10.5, 10.6_

- [ ] 9. Implement Robots_Txt for virtual robots.txt management
  - [ ] 9.1 Create Robots_Txt class with filter hook
    - Implement `register()` method hooking into robots_txt filter
    - Implement `filter_robots_txt()` callback
    - Implement `get_default_directives()` returning default rules
    - Implement `get_custom_directives()` from settings
    - Implement `get_sitemap_url()` returning sitemap index URL
    - Implement `format_robots_txt()` with proper formatting
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6_
  
  - [ ]* 9.2 Write property test for robots.txt sitemap URL presence
    - **Property 24: Robots.txt Sitemap URL Presence**
    - **Validates: Requirements 11.2**
  
  - [ ]* 9.3 Write property test for robots.txt custom directives inclusion
    - **Property 25: Robots.txt Custom Directives Inclusion**
    - **Validates: Requirements 11.3, 11.6**
  
  - [ ]* 9.4 Write property test for robots.txt default directives presence
    - **Property 26: Robots.txt Default Directives Presence**
    - **Validates: Requirements 11.4**
  
  - [ ]* 9.5 Write property test for robots.txt formatting
    - **Property 27: Robots.txt Formatting**
    - **Validates: Requirements 11.5**
  
  - [ ]* 9.6 Write unit tests for Robots_Txt
    - Test filter hook registration
    - Test default directives output
    - Test custom directives appending
    - Test sitemap URL inclusion
    - _Requirements: 11.1, 11.2, 11.3_

- [ ] 10. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Wire all components together in Meta_Module
  - [ ] 11.1 Update Meta_Module constructor with dependency injection
    - Instantiate Options, Title_Patterns, Meta_Resolver, Meta_Output, Meta_Postmeta, Global_SEO, Robots_Txt
    - Pass dependencies to constructors
    - Store instances as private properties
    - _Requirements: 1.1_
  
  - [ ] 11.2 Connect Meta_Output to wp_head hook
    - Hook `Meta_Output::output_head_tags()` to wp_head with priority 1
    - Ensure Meta_Resolver is available to Meta_Output
    - _Requirements: 1.2_
  
  - [ ] 11.3 Connect Meta_Postmeta to rest_api_init hook
    - Hook `Meta_Postmeta::register()` to rest_api_init
    - _Requirements: 1.6_
  
  - [ ] 11.4 Connect Robots_Txt to robots_txt filter
    - Call `Robots_Txt::register()` in boot() method
    - _Requirements: 11.1_
  
  - [ ]* 11.5 Write integration tests for Meta_Module
    - Test all hooks are registered correctly
    - Test hook priorities
    - Test component wiring
    - _Requirements: 1.1, 1.2, 1.3_

- [ ] 12. Create migration script for existing installations
  - [ ] 12.1 Write migration function for options
    - Create `meowseo_migrate_meta_module_options()` function
    - Migrate old separator option to new structure
    - Migrate old default OG image to new structure
    - Initialize title_patterns with defaults
    - Delete old option keys
    - _Requirements: All (backward compatibility)_
  
  - [ ] 12.2 Add migration hook to plugin activation
    - Hook migration function to plugin activation
    - Add version check to prevent re-running
    - _Requirements: All (backward compatibility)_
  
  - [ ]* 12.3 Write unit tests for migration script
    - Test old options are migrated correctly
    - Test old options are deleted
    - Test new options structure is correct

- [ ] 13. Integration testing with WordPress
  - [ ]* 13.1 Test with real WordPress hooks (not mocked)
    - Create integration test suite using WP_UnitTestCase
    - Test wp_head output contains all expected tags
    - Test document_title_parts filter returns empty array
    - Test no duplicate title tags output
    - _Requirements: 1.2, 1.3, 2.1_
  
  - [ ]* 13.2 Test theme compatibility
    - Test with Twenty Twenty-Four theme
    - Test with Astra theme
    - Test with GeneratePress theme
    - Verify no duplicate meta tags
    - Verify correct hook priorities
    - _Requirements: 2.1, 10.8_
  
  - [ ]* 13.3 Test plugin compatibility
    - Test with WPML active (hreflang output)
    - Test with Polylang active (hreflang output)
    - Verify no conflicts with other SEO plugins
    - _Requirements: 2.9_
  
  - [ ]* 13.4 Test performance benchmarks
    - Measure database queries (should be 0 with cache)
    - Measure memory usage (< 1MB per request)
    - Measure execution time (< 10ms for output_head_tags)
    - Test cache hit rate (> 95%)
    - _Requirements: All (performance)_

- [ ] 14. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 15. Documentation and cleanup
  - [ ] 15.1 Add PHPDoc blocks to all classes and methods
    - Document all public methods with @param and @return
    - Add class-level PHPDoc with description and @package
    - Document all private methods
    - _Requirements: All (code quality)_
  
  - [ ] 15.2 Create README for Meta Module
    - Document architecture overview
    - Document all 7 classes and their responsibilities
    - Document fallback chains
    - Document title pattern system
    - _Requirements: All (documentation)_
  
  - [ ] 15.3 Create developer guide for extending patterns
    - Document how to add custom variables
    - Document how to add custom patterns
    - Document pattern validation rules
    - _Requirements: 8.1, 12.1_
  
  - [ ] 15.4 Create migration guide for existing users
    - Document option structure changes
    - Document migration script usage
    - Document rollback procedure
    - _Requirements: All (backward compatibility)_

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at reasonable breaks
- Property tests validate universal correctness properties (29 total)
- Unit tests validate specific examples and edge cases
- Integration tests validate WordPress compatibility and performance
- All 7 classes follow single responsibility principle
- Fallback chains ensure no empty output for critical fields
- Pattern system enables customization without code changes
- Migration script ensures backward compatibility with existing installations
