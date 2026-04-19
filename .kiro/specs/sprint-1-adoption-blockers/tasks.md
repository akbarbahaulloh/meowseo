# Implementation Plan: Sprint 1 - Adoption Blockers

## Overview

This implementation plan breaks down the five critical adoption blocker features into discrete coding tasks. The plan follows a logical progression: infrastructure first (import system), then core functionality (keywords, scoring), then UI enhancements (list columns), and finally configuration (archive settings).

Each task builds incrementally, with checkpoints to validate functionality before moving forward. Testing tasks are marked as optional to allow for faster MVP delivery while maintaining quality standards.

**Implementation Language:** PHP (WordPress plugin architecture)

## Tasks

### Phase 1: Import System Foundation

- [x] 1. Create import module structure and base classes
  - Create directory structure: `includes/modules/import/` and `includes/modules/import/importers/`
  - Create `class-import-manager.php` with plugin detection methods
  - Create abstract `class-base-importer.php` with shared import logic
  - Create `class-batch-processor.php` with chunked processing logic
  - Define public interfaces for Import_Manager, Base_Importer, and Batch_Processor
  - _Requirements: 1.26, 1.28_

- [ ]* 1.1 Write unit tests for batch processor
  - Test batch size configuration
  - Test progress tracking
  - Test pagination logic
  - Mock WP_Query for batch iteration
  - _Requirements: 1.28_

- [x] 2. Implement Yoast SEO importer
  - [x] 2.1 Create `class-yoast-importer.php` extending Base_Importer
    - Implement `get_plugin_name()` returning "Yoast SEO"
    - Implement `is_plugin_installed()` checking for Yoast option keys
    - Define postmeta mappings array (10 mappings from design)
    - Define termmeta mappings array (2 mappings from design)
    - Define options mappings array (separator, homepage, title patterns)
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 1.20, 1.22_

  - [x] 2.2 Implement Yoast postmeta import
    - Implement `import_postmeta()` method using Batch_Processor
    - Add validation and transformation logic in `validate_and_transform()`
    - Handle empty values and invalid UTF-8
    - Log errors without stopping batch
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 1.27, 1.29_

  - [x] 2.3 Implement Yoast termmeta and options import
    - Implement `import_termmeta()` method for taxonomy terms
    - Implement `import_options()` method for plugin settings
    - Transform Yoast title patterns to MeowSEO format
    - _Requirements: 1.20, 1.22_

  - [x] 2.4 Implement Yoast redirect import
    - Implement `import_redirects()` method
    - Query `wpseo_redirect` custom post type
    - Transform to MeowSEO redirect format
    - Preserve redirect type (301/302/307/410)
    - _Requirements: 1.24_

  - [ ]* 2.5 Write unit tests for Yoast importer
    - Test postmeta mapping transformations
    - Test termmeta mapping transformations
    - Test options mapping transformations
    - Test redirect data transformation
    - Test error handling for invalid data
    - Mock WordPress postmeta and options functions
    - _Requirements: 1.1-1.10, 1.20, 1.22, 1.24, 1.27, 1.29_

- [x] 3. Implement RankMath importer
  - [x] 3.1 Create `class-rankmath-importer.php` extending Base_Importer
    - Implement `get_plugin_name()` returning "RankMath"
    - Implement `is_plugin_installed()` checking for RankMath option keys
    - Define postmeta mappings array (9 mappings from design)
    - Define termmeta mappings array (2 mappings from design)
    - Define options mappings array (separator, homepage, title patterns)
    - _Requirements: 1.11, 1.12, 1.13, 1.14, 1.15, 1.16, 1.17, 1.18, 1.19, 1.21, 1.23_

  - [x] 3.2 Implement RankMath postmeta import with special handling
    - Implement `import_postmeta()` method using Batch_Processor
    - Add special handling for `rank_math_robots` array splitting
    - Add special handling for `rank_math_focus_keyword` comma-separated string
    - Add validation and transformation logic
    - _Requirements: 1.11, 1.12, 1.13, 1.14, 1.15, 1.16, 1.17, 1.18, 1.19, 1.27, 1.29_

  - [x] 3.3 Implement RankMath termmeta and options import
    - Implement `import_termmeta()` method for taxonomy terms
    - Implement `import_options()` method for plugin settings
    - Transform RankMath title patterns to MeowSEO format
    - _Requirements: 1.21, 1.23_

  - [x] 3.4 Implement RankMath redirect import
    - Implement `import_redirects()` method
    - Query `rank_math_redirections` database table
    - Map columns: `url_to` → `source_url`, `url_from` → `target_url`, `header_code` → `redirect_type`
    - _Requirements: 1.25_

  - [ ]* 3.5 Write unit tests for RankMath importer
    - Test postmeta mapping transformations
    - Test robots array splitting logic
    - Test focus keyword comma-separated parsing
    - Test termmeta mapping transformations
    - Test options mapping transformations
    - Test redirect data transformation
    - Mock WordPress database queries
    - _Requirements: 1.11-1.19, 1.21, 1.23, 1.25, 1.27, 1.29_

- [x] 4. Implement import admin UI and AJAX handlers
  - [x] 4.1 Create `class-import-admin.php` with UI rendering
    - Register admin menu page under MeowSEO settings
    - Render import wizard UI with plugin detection
    - Display detected plugins (Yoast/RankMath)
    - Add "Start Import" buttons for each detected plugin
    - Add nonce verification for form submission
    - _Requirements: 1.26_

  - [x] 4.2 Implement import progress tracking and display
    - Create AJAX endpoint `wp_ajax_meowseo_import_status`
    - Store import job state in transient `meowseo_import_{import_id}`
    - Return JSON with progress (processed/total counts per phase)
    - Display progress bar and current phase in UI
    - Add "Cancel Import" button with AJAX handler
    - _Requirements: 1.26, 1.28_

  - [x] 4.3 Implement import completion summary
    - Display summary showing counts of imported posts, terms, settings, redirects
    - Display error log with post IDs and error messages
    - Add "Retry Failed Items" button
    - Add "Export Error Log" button
    - Clean up transients after completion
    - _Requirements: 1.26, 1.27_

- [x] 5. Checkpoint - Test import system end-to-end
  - Ensure all tests pass, ask the user if questions arise.

### Phase 2: Multiple Focus Keywords

- [x] 6. Implement keyword storage and management
  - [x] 6.1 Create `class-keyword-manager.php` in `includes/modules/keywords/`
    - Implement `get_keywords()` returning array with primary + secondary keywords
    - Implement `set_primary_keyword()` updating `_meowseo_focus_keyword` postmeta
    - Implement `add_secondary_keyword()` appending to `_meowseo_secondary_keywords` JSON array
    - Implement `remove_secondary_keyword()` removing from array
    - Implement `reorder_secondary_keywords()` updating array order
    - Implement `validate_keyword_count()` enforcing 5 keyword maximum
    - _Requirements: 2.1, 2.2, 2.10, 2.11_

  - [ ]* 6.2 Write unit tests for keyword manager
    - Test keyword storage and retrieval
    - Test keyword count validation (max 5)
    - Test duplicate detection
    - Test keyword reordering
    - Mock postmeta functions
    - _Requirements: 2.1, 2.2, 2.10, 2.11, 2.12_

- [x] 7. Implement per-keyword analysis engine
  - [x] 7.1 Create `class-keyword-analyzer.php` in `includes/modules/keywords/`
    - Extend existing Analysis_Engine class
    - Implement `analyze_all_keywords()` iterating over all keywords
    - Implement `analyze_single_keyword()` running all checks for one keyword
    - Run keyword density analysis per keyword
    - Run keyword-in-title analysis per keyword
    - Run keyword-in-heading analysis per keyword
    - Run keyword-in-slug analysis per keyword
    - Run keyword-in-first-paragraph analysis per keyword
    - Run keyword-in-meta-description analysis per keyword
    - Store results in `_meowseo_keyword_analysis` postmeta as JSON object
    - _Requirements: 2.3, 2.4, 2.5, 2.6, 2.7, 2.8_

  - [ ]* 7.2 Write unit tests for keyword analyzer
    - Test per-keyword analysis execution
    - Test result aggregation
    - Test score calculation
    - Use sample content fixtures
    - Mock Analysis_Engine methods
    - _Requirements: 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.12_

- [x] 8. Integrate keyword UI in Gutenberg sidebar
  - [x] 8.1 Add secondary keyword input fields to Gutenberg sidebar
    - Add "Secondary Keywords" section below primary keyword
    - Render input fields for up to 4 secondary keywords
    - Add "Add Keyword" button (disabled when count = 5)
    - Add "Remove" button for each secondary keyword
    - Add drag handles for reordering
    - _Requirements: 2.2, 2.10, 2.11_

  - [x] 8.2 Display per-keyword analysis results in sidebar
    - Render separate score row for each keyword
    - Display keyword name as row header
    - Display overall score for that keyword
    - Display expandable details with individual check scores
    - Use color coding (red/orange/green) based on score
    - _Requirements: 2.9_

  - [x] 8.3 Wire keyword UI to REST API
    - Create REST endpoint `POST /meowseo/v1/keywords/{post_id}`
    - Accept JSON payload with primary + secondary keywords array
    - Validate keyword count (max 5)
    - Call Keyword_Manager methods to update postmeta
    - Trigger analysis on keyword change
    - Return updated analysis results
    - _Requirements: 2.1, 2.2, 2.9, 2.10, 2.11_

- [x] 9. Checkpoint - Test multiple keywords end-to-end
  - Ensure all tests pass, ask the user if questions arise.

### Phase 3: SEO Score Column in Post Lists

- [x] 10. Implement list table column registration
  - [x] 10.1 Create `class-list-table-columns.php` in `includes/modules/admin/`
    - Hook into `manage_{post_type}_posts_columns` for all public post types
    - Implement `add_seo_score_column()` adding "SEO Score" column
    - Position column after "Title" column
    - Exclude attachment, revision, nav_menu_item post types
    - _Requirements: 3.1, 3.2, 3.3_

  - [x] 10.2 Implement score indicator rendering
    - Hook into `manage_{post_type}_posts_custom_column`
    - Implement `render_seo_score_column()` method
    - Retrieve `_meowseo_seo_score` postmeta
    - Render colored circle indicator based on score range
    - Use red (#dc3232) for 0-40, orange (#f56e28) for 41-70, green (#46b450) for 71-100
    - Render gray dash (#a7aaad) for null scores
    - Add ARIA labels for accessibility
    - _Requirements: 3.4, 3.5, 3.6, 3.7, 3.10_

  - [ ]* 10.3 Write unit tests for list table columns
    - Test column registration
    - Test score indicator rendering
    - Test color selection logic
    - Mock WP_List_Table and postmeta functions
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.10_

- [x] 11. Implement column sorting
  - [x] 11.1 Register SEO Score as sortable column
    - Hook into `manage_edit-{post_type}_sortable_columns`
    - Implement `register_sortable_column()` adding 'seo_score' key
    - _Requirements: 3.8, 3.9_

  - [x] 11.2 Handle sorting query modification
    - Hook into `pre_get_posts`
    - Implement `handle_seo_score_sorting()` method
    - Check if orderby parameter is 'seo_score'
    - Add meta_query for `_meowseo_seo_score`
    - Handle ASC/DESC ordering
    - Use numeric comparison for sorting
    - _Requirements: 3.8, 3.9, 3.11_

  - [ ]* 11.3 Write integration tests for sorting
    - Create test posts with various SEO scores
    - Trigger sorting via query parameters
    - Verify posts returned in correct order
    - Test both ASC and DESC ordering
    - _Requirements: 3.8, 3.9, 3.11_

- [x] 12. Add CSS styling for score indicators
  - Create `admin/css/list-table-columns.css`
  - Style `.meowseo-score-indicator` container
  - Style `.meowseo-score-circle` with border-radius
  - Add color classes: `.meowseo-score-good`, `.meowseo-score-ok`, `.meowseo-score-poor`, `.meowseo-score-none`
  - Enqueue stylesheet on admin list table pages
  - _Requirements: 3.4, 3.5, 3.6, 3.7, 3.10_

- [x] 13. Checkpoint - Test list table columns end-to-end
  - Ensure all tests pass, ask the user if questions arise.

### Phase 4: Global Archive Robots Settings

- [x] 14. Extend robots resolver for archive support
  - [x] 14.1 Add archive robots methods to `class-robots-resolver.php`
    - Implement `get_archive_robots()` method accepting archive type parameter
    - Implement `resolve_robots_for_archive()` detecting current archive type
    - Implement `get_global_robots_setting()` retrieving setting from options
    - Define archive type constants (author, date, category, tag, search, attachment, post_type_archive)
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [x] 14.2 Implement archive type detection logic
    - Use `is_author()` to detect author archives
    - Use `is_date()` to detect date archives
    - Use `is_category()` to detect category archives
    - Use `is_tag()` to detect tag archives
    - Use `is_search()` to detect search results
    - Use `is_attachment()` to detect media attachments
    - Use `is_post_type_archive()` to detect custom post type archives
    - _Requirements: 4.8, 4.9, 4.10, 4.11, 4.12, 4.13, 4.14_

  - [x] 14.3 Implement robots resolution with term override precedence
    - Check for term-specific robots meta in termmeta
    - Fall back to global archive robots setting
    - Format as comma-separated string (e.g., "noindex, nofollow")
    - Hook into `wp_head` with priority 1
    - _Requirements: 4.8, 4.9, 4.10, 4.11, 4.12, 4.13, 4.14, 4.15, 4.16_

  - [ ]* 14.4 Write unit tests for robots resolver
    - Test archive type detection
    - Test global setting resolution
    - Test term-specific override precedence
    - Mock WordPress conditionals (is_category, is_tag, etc.)
    - _Requirements: 4.8, 4.9, 4.10, 4.11, 4.12, 4.13, 4.14, 4.15, 4.16_

- [x] 15. Add archive robots settings UI
  - [x] 15.1 Create settings section in Advanced tab
    - Add "Archive Robots" section to Settings_Manager
    - Render checkbox grid: Archive Type × [noindex, nofollow]
    - Add labels for each archive type (Author Archives, Date Archives, etc.)
    - Add help text explaining global defaults vs. term-specific overrides
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [x] 15.2 Implement settings save and validation
    - Hook into `admin_post_meowseo_save_settings`
    - Verify nonce and `manage_options` capability
    - Validate checkbox values (boolean)
    - Store in `meowseo_options` array with keys like `robots_author_archive`
    - Display success/error admin notices
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

- [x] 16. Checkpoint - Test archive robots settings end-to-end
  - Ensure all tests pass, ask the user if questions arise.

### Phase 5: Archive Title/Description Patterns

- [x] 17. Extend title patterns engine for archives
  - [x] 17.1 Add archive pattern support to `class-title-patterns.php`
    - Define pattern types for category, tag, custom taxonomy, author, search, date, 404, homepage
    - Add default patterns for each archive type
    - Store patterns in `meowseo_options['title_patterns']` array
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12, 5.13, 5.14, 5.15, 5.16_

  - [x] 17.2 Implement archive variable substitution
    - Implement `resolve_archive_variables()` method
    - Replace `%%category%%` with category name using `get_queried_object()`
    - Replace `%%tag%%` with tag name using `get_queried_object()`
    - Replace `%%term%%` with generic term name
    - Replace `%%date%%` with formatted archive date
    - Replace `%%name%%` with author display name
    - Replace `%%searchphrase%%` with search query using `get_search_query()`
    - Replace `%%posttype%%` with post type label
    - Replace `%%sep%%` with configured separator
    - Replace `%%page%%` with page number using `get_query_var('paged')`
    - _Requirements: 5.17, 5.18, 5.19, 5.20, 5.21, 5.22, 5.23, 5.24, 5.25, 5.26, 5.27_

  - [x] 17.3 Integrate archive title output
    - Hook into `wp_title` filter with priority 10
    - Hook into `document_title_parts` filter with priority 10
    - Detect archive type using WordPress conditionals
    - Retrieve pattern for current archive type
    - Resolve variables and return formatted title
    - _Requirements: 5.28, 5.29, 5.30, 5.31, 5.32, 5.33, 5.34_

  - [ ]* 17.4 Write unit tests for title patterns
    - Test variable substitution for each variable type
    - Test archive type detection
    - Test pattern resolution
    - Use sample patterns and mock WordPress functions
    - _Requirements: 5.17-5.27, 5.35_

- [x] 18. Add archive patterns settings UI
  - [x] 18.1 Create settings section in General tab
    - Add "Archive Patterns" section to Settings_Manager class
    - Render text inputs for each archive type pattern (category, tag, author, search, date, 404, homepage)
    - Add separate text inputs for title and description patterns
    - Add variable reference tooltip showing available variables (%%category%%, %%tag%%, %%term%%, %%date%%, %%name%%, %%searchphrase%%, %%posttype%%, %%sep%%, %%page%%, %%sitename%%, %%title%%)
    - Add live preview showing example output with sample data
    - Display default patterns as placeholder text
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12, 5.13, 5.14, 5.15, 5.16_

  - [x] 18.2 Implement pattern validation and save
    - Hook into `admin_post_meowseo_save_settings` action
    - Verify nonce and check `manage_options` capability
    - Validate pattern syntax (check for unmatched %% delimiters)
    - Sanitize user input using `sanitize_text_field()`
    - Store validated patterns in `meowseo_options['title_patterns']` array
    - Display admin notice with validation errors if pattern syntax invalid
    - Display success notice on successful save
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12, 5.13, 5.14, 5.15, 5.16_

  - [ ]* 18.3 Write integration tests for archive patterns
    - Configure patterns via settings UI
    - Visit category archive page and verify title tag output
    - Visit tag archive page and verify title tag output
    - Visit author page and verify title tag output
    - Visit search results page and verify title tag output
    - Verify variable substitution works correctly for each variable type
    - Test with real WordPress environment
    - _Requirements: 5.28, 5.29, 5.30, 5.31, 5.32, 5.33, 5.34, 5.35_

- [x] 19. Checkpoint - Test archive patterns end-to-end
  - Ensure all tests pass, ask the user if questions arise.

### Phase 6: Integration and Polish

- [x] 20. Wire all modules into main plugin class
  - [x] 20.1 Register import module in autoloader
    - Add import module classes to `class-autoloader.php` class map
    - Register Import_Manager, Batch_Processor, Base_Importer, Yoast_Importer, RankMath_Importer, Import_Admin classes
    - Initialize Import_Manager instance in main plugin class constructor
    - Pass Options and Batch_Processor dependencies to Import_Manager
    - Call Import_Admin::register_hooks() to set up admin UI
    - _Requirements: 1.26, 1.27, 1.28_

  - [x] 20.2 Register keywords module in autoloader
    - Add keywords module classes to `class-autoloader.php` class map
    - Register Keyword_Manager and Keyword_Analyzer classes
    - Initialize Keyword_Manager instance in main plugin class constructor
    - Initialize Keyword_Analyzer instance with Analysis_Engine dependency
    - Hook into `save_post` action to trigger keyword analysis on post save
    - Hook into `rest_api_init` to register keyword REST endpoints
    - _Requirements: 2.1, 2.2, 2.3, 2.9_

  - [x] 20.3 Register admin module in autoloader
    - Add List_Table_Columns class to `class-autoloader.php` class map
    - Initialize List_Table_Columns instance in main plugin class constructor
    - Pass Options dependency to List_Table_Columns
    - Call List_Table_Columns::register_hooks() method to set up column hooks
    - Enqueue admin CSS for list table styling
    - _Requirements: 3.1, 3.2, 3.3_

  - [x] 20.4 Update meta module initialization
    - Verify Robots_Resolver is initialized in main plugin class
    - Verify Title_Patterns is initialized in main plugin class
    - Ensure archive-specific hooks are registered (wp_head, wp_title, document_title_parts)
    - Verify Settings_Manager includes new archive settings sections
    - _Requirements: 4.8, 4.9, 4.10, 4.11, 4.12, 4.13, 4.14, 5.28, 5.29, 5.30, 5.31, 5.32, 5.33, 5.34_

- [x] 21. Add database migrations for new postmeta keys
  - Create or update migration in `class-migration.php`
  - Add migration for `_meowseo_secondary_keywords` postmeta key (default: empty JSON array "[]")
  - Add migration for `_meowseo_keyword_analysis` postmeta key (default: empty JSON object "{}")
  - Run migration on plugin activation or version upgrade
  - Set default values for existing posts without these keys
  - Log migration progress and any errors
  - _Requirements: 2.1, 2.3_

- [x] 22. Update plugin documentation
  - [x] 22.1 Add import system documentation
    - Document import wizard UI flow in user guide
    - Document supported plugins (Yoast SEO, RankMath)
    - Document data mapping tables (postmeta, termmeta, options, redirects)
    - Document batch processing and progress tracking
    - Document error handling and recovery options
    - _Requirements: 1.26, 1.27, 1.28, 1.29_

  - [x] 22.2 Add multiple keywords documentation
    - Document how to add/remove secondary keywords in Gutenberg sidebar
    - Document 5 keyword maximum limit
    - Document per-keyword analysis results display
    - Document keyword reordering functionality
    - _Requirements: 2.1, 2.2, 2.9, 2.10, 2.11_

  - [x] 22.3 Add SEO score column documentation
    - Document SEO score column in post list tables
    - Document color coding system (red/orange/green)
    - Document sorting functionality
    - Document score calculation methodology
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10_

  - [x] 22.4 Add archive robots settings documentation
    - Document global archive robots settings in Advanced tab
    - Document supported archive types (author, date, category, tag, search, attachment, post type)
    - Document term-specific override precedence
    - Document robots meta tag output
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10, 4.11, 4.12, 4.13, 4.14, 4.15, 4.16_

  - [x] 22.5 Add archive patterns documentation
    - Document archive title/description patterns in General tab
    - Document available variables (%%category%%, %%tag%%, %%term%%, %%date%%, %%name%%, %%searchphrase%%, %%posttype%%, %%sep%%, %%page%%)
    - Document pattern syntax and validation rules
    - Document live preview functionality
    - _Requirements: 5.1-5.16, 5.17-5.27, 5.28-5.35_

  - [x] 22.6 Update API documentation
    - Document Import_Manager class and methods
    - Document Batch_Processor class and methods
    - Document Base_Importer, Yoast_Importer, RankMath_Importer classes
    - Document Keyword_Manager and Keyword_Analyzer classes
    - Document List_Table_Columns class
    - Document Robots_Resolver archive methods
    - Document Title_Patterns archive methods
    - _Requirements: All_

- [x] 23. Final checkpoint - Full sprint verification
  - [x] 23.1 Test import system end-to-end
    - Install Yoast SEO with sample data
    - Run import and verify all postmeta, termmeta, options, redirects migrated
    - Install RankMath with sample data
    - Run import and verify all data migrated correctly
    - Test error handling with invalid data
    - Test cancellation and resume functionality
    - _Requirements: 1.1-1.29_

  - [x] 23.2 Test multiple keywords end-to-end
    - Create post with 5 focus keywords
    - Verify analysis runs for each keyword
    - Verify per-keyword scores displayed in sidebar
    - Test keyword addition/removal/reordering
    - Verify validation prevents exceeding 5 keywords
    - _Requirements: 2.1-2.12_

  - [x] 23.3 Test SEO score column end-to-end
    - View posts list table and verify SEO Score column appears
    - Verify color indicators display correctly (red/orange/green/gray)
    - Test sorting by SEO Score (ascending and descending)
    - Test with custom post types
    - _Requirements: 3.1-3.11_

  - [x] 23.4 Test archive robots settings end-to-end
    - Configure global robots settings for all archive types
    - Visit author archive and verify robots meta tag output
    - Visit date archive and verify robots meta tag output
    - Visit category archive and verify robots meta tag output
    - Visit tag archive and verify robots meta tag output
    - Visit search results page and verify robots meta tag output
    - Set term-specific override and verify it takes precedence
    - _Requirements: 4.1-4.16_

  - [x] 23.5 Test archive title patterns end-to-end
    - Configure title patterns for all archive types
    - Visit category archive and verify title output with variable substitution
    - Visit tag archive and verify title output
    - Visit author page and verify title output
    - Visit search results page and verify title output
    - Visit date archive and verify title output
    - Visit 404 page and verify title output
    - Test pagination variable (%%page%%) on paginated archives
    - _Requirements: 5.1-5.35_

  - [x] 23.6 Run all automated tests
    - Run all unit tests for import system
    - Run all unit tests for keyword system
    - Run all unit tests for list table columns
    - Run all unit tests for robots resolver
    - Run all unit tests for title patterns
    - Run all integration tests
    - Verify all tests pass
    - _Requirements: All_

  - [x] 23.7 Final verification checkpoint
    - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at logical breakpoints
- Import system is built first as it's foundational for migration
- Keywords and scoring are core functionality built next
- UI enhancements (list columns) come after core functionality
- Configuration features (archive settings) are implemented last
- All modules follow MeowSEO's existing architectural patterns
- Security (nonce verification, capability checks) is built into each admin interface
- Performance optimizations (batch processing, caching) are included in initial implementation
- Implementation uses PHP following WordPress plugin development standards
- All completed tasks (marked with [x]) represent work already done and verified
