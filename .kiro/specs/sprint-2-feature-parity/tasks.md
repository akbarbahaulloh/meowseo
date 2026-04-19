# Implementation Plan: Sprint 2 - Feature Parity

## Overview

This implementation plan covers six core SEO features that are standard in premium SEO plugins: Global Schema Identity (WebSite + Organization), Real-Time SERP Preview with character counting, Webmaster Tools Verification, Robots.txt Editor UI, Redirect CSV Import/Export, and Cornerstone Content Management.

The implementation follows MeowSEO's module-based architecture with options-based configuration, WordPress integration hooks, and validation-first approach. Backend components use PHP, frontend components use TypeScript/React.

## Tasks

### 1. Global Schema Identity Markup

- [x] 1.1 Create Global Schema Generator infrastructure
  - Create `includes/modules/schema/class-global-schema-generator.php`
  - Implement constructor accepting Options dependency
  - Implement `generate_global_schema()` method returning array of schema objects
  - Implement `should_output_schema()` method checking organization settings
  - Hook into existing `Schema_Output` class via `meowseo_schema_output` filter
  - _Requirements: 1.1, 1.2, 1.6_

- [x] 1.2 Implement WebSite schema generator
  - Create `includes/modules/schema/generators/class-website-schema.php`
  - Generate WebSite schema with @type, @id, url, name properties
  - Add potentialAction with SearchAction for sitelinks search box
  - Use `home_url()` for URL and `get_bloginfo('name')` for site name
  - Return properly structured schema array
  - _Requirements: 1.1_

- [x] 1.3 Implement Organization schema generator
  - Create `includes/modules/schema/generators/class-organization-schema.php`
  - Generate Organization schema with @type, @id, name, url properties
  - Add logo property with ImageObject when configured
  - Add contactPoint property when email configured
  - Add sameAs array with configured social profile URLs
  - Omit logo property when not configured
  - Validate and sanitize all URLs with `esc_url()`
  - _Requirements: 1.2, 1.3, 1.5_

- [x] 1.4 Create Organization settings UI
  - Extend `includes/admin/class-settings-manager.php`
  - Add "Organization" tab to settings page
  - Add input fields: organization name, logo URL, logo width, logo height, contact email
  - Add input fields for social profiles: Facebook, Twitter, Instagram, LinkedIn, YouTube
  - Add help text explaining schema.org requirements
  - Store settings in `meowseo_options['organization']`
  - Validate URLs and email format on save
  - _Requirements: 1.4_

- [x] 1.5 Integrate global schema output with wp_head
  - Modify `includes/modules/schema/class-schema-output.php`
  - Hook `generate_global_schema()` into wp_head (priority 1)
  - Output WebSite and Organization as separate JSON-LD script blocks
  - Add schemas to @graph array for existing schema output
  - Ensure proper JSON encoding and escaping
  - _Requirements: 1.6_

- [ ]* 1.6 Write property test for schema round-trip preservation
  - **Property 1: Schema JSON-LD Round-Trip Preservation**
  - **Validates: Requirements 1.1, 1.2, 1.3, 1.7**
  - Generate random organization settings (name, logo, social profiles)
  - Serialize to JSON-LD, parse back, verify equivalence
  - Test with empty fields, missing fields, special characters
  - Minimum 100 iterations

### 2. Real-Time SERP Preview

- [ ] 2.1 Create SERP Preview React component
  - Create `assets/src/gutenberg/components/SERPPreview.tsx`
  - Implement component with props: title, description, url, mode, onModeChange
  - Display Google-style search result card with blue title, green URL, gray description
  - Implement mobile (360px) and desktop (600px) width modes
  - Add mode toggle button in preview header
  - Store mode preference in localStorage
  - _Requirements: 2.1, 2.12, 2.13, 2.14_

- [ ] 2.2 Implement text truncation with ellipsis
  - Add truncation logic for title at 60 characters
  - Add truncation logic for description at 155 characters
  - Display ellipsis (...) for truncated text
  - Handle Unicode and emoji correctly
  - _Requirements: 2.15, 2.16_

- [ ] 2.3 Implement real-time preview updates
  - Add debounced updates (300ms delay) for title/description changes
  - Connect to Gutenberg store for SEO title and meta description
  - Update preview within 500ms of field changes
  - Use React.memo for performance optimization
  - _Requirements: 2.2, 2.3_

- [ ] 2.4 Create Character Counter component
  - Create `assets/src/gutenberg/components/CharacterCounter.tsx`
  - Implement component with props: value, maxLength, optimalMin, optimalMax, label
  - Display current character count and maximum
  - Calculate status: good (green), warning (orange), error (red)
  - Apply color-coded CSS classes based on status
  - _Requirements: 2.4, 2.5_

- [ ] 2.5 Implement character counter status logic
  - For SEO title: red >60, green 50-60, orange <50
  - For meta description: red >155, green 120-155, orange <120
  - Update counter immediately on input change (no debounce)
  - _Requirements: 2.6, 2.7, 2.8, 2.9, 2.10, 2.11_

- [ ] 2.6 Integrate SERP Preview into Gutenberg sidebar
  - Extend `assets/src/gutenberg/components/tabs/GeneralTabContent.tsx`
  - Add SERP Preview component above title/description fields
  - Add Character Counter below each field
  - Connect to existing SEO title and meta description state
  - Ensure proper styling and layout

- [ ]* 2.7 Write property test for character count accuracy
  - **Property 2: Character Count Accuracy**
  - **Validates: Requirements 2.4, 2.5**
  - Generate random strings (0-500 chars) with ASCII, Unicode, emoji
  - Verify count exactly equals string length
  - Minimum 100 iterations

- [ ]* 2.8 Write property test for title color status
  - **Property 3: Title Color Status Correctness**
  - **Validates: Requirements 2.6, 2.7, 2.8**
  - Generate strings of varying lengths
  - Verify red >60, orange 50-60, green <50
  - Minimum 100 iterations

- [ ]* 2.9 Write property test for description color status
  - **Property 4: Description Color Status Correctness**
  - **Validates: Requirements 2.9, 2.10, 2.11**
  - Generate strings of varying lengths
  - Verify red >155, green 120-155, orange <120
  - Minimum 100 iterations

- [ ]* 2.10 Write property test for text truncation
  - **Property 5: Text Truncation with Ellipsis**
  - **Validates: Requirements 2.15, 2.16**
  - Generate random strings and truncation limits
  - Verify full display when length <= limit
  - Verify truncation + "..." when length > limit
  - Test with ASCII, Unicode, emoji
  - Minimum 100 iterations

### 3. Webmaster Tools Verification

- [ ] 3.1 Create Webmaster Verification component
  - Create `includes/modules/meta/class-webmaster-verification.php`
  - Implement constructor accepting Options dependency
  - Implement `output_verification_tags()` method
  - Implement private methods for each service: Google, Bing, Yandex
  - Implement `sanitize_verification_code()` method
  - _Requirements: 3.4, 3.5, 3.6, 3.8_

- [ ] 3.2 Implement verification code sanitization
  - Strip HTML tags with `wp_strip_all_tags()`
  - Remove whitespace with `trim()`
  - Validate alphanumeric + hyphens only (regex: `/^[a-zA-Z0-9_-]{0,100}$/`)
  - Return WP_Error for invalid codes
  - _Requirements: 3.8_

- [ ] 3.3 Implement meta tag output logic
  - Output Google meta tag with name="google-site-verification" when configured
  - Output Bing meta tag with name="msvalidate.01" when configured
  - Output Yandex meta tag with name="yandex-verification" when configured
  - Omit meta tags when verification code is empty
  - Hook into wp_head (priority 1) before other meta tags
  - _Requirements: 3.4, 3.5, 3.6, 3.7, 3.9_

- [ ] 3.4 Create Webmaster Tools settings UI
  - Extend `includes/admin/class-settings-manager.php`
  - Add "Webmaster Tools" section to Advanced tab
  - Add text input for Google Search Console verification code
  - Add text input for Bing Webmaster Tools verification code
  - Add text input for Yandex Webmaster verification code
  - Add help text with instructions and links to each service
  - Store settings in `meowseo_options['webmaster_verification']`
  - _Requirements: 3.1, 3.2, 3.3_

- [ ]* 3.5 Write property test for verification tag conditional output
  - **Property 6: Verification Meta Tag Conditional Output**
  - **Validates: Requirements 3.4, 3.5, 3.6, 3.7**
  - Generate random verification codes (including empty)
  - Verify correct meta tag output for each service
  - Verify omission when code is empty
  - Minimum 100 iterations

- [ ]* 3.6 Write property test for verification code sanitization
  - **Property 7: Verification Code Sanitization**
  - **Validates: Requirements 3.8**
  - Generate strings with HTML tags, scripts, special characters
  - Verify sanitization produces only alphanumeric, hyphens, underscores
  - Test XSS injection attempts
  - Minimum 100 iterations

### 4. Robots.txt Editor UI

- [ ] 4.1 Create Robots.txt Editor component
  - Create `includes/modules/seo/class-robots-txt-editor.php`
  - Implement constructor accepting Options and Robots_Txt dependencies
  - Implement `render_editor_ui()` method for admin interface
  - Implement `save_robots_txt()` method with validation
  - Implement `reset_to_default()` method
  - Implement `get_current_content()` method
  - _Requirements: 4.1, 4.2, 4.3, 4.6, 4.7_

- [ ] 4.2 Implement robots.txt syntax validation
  - Implement `validate_syntax()` method returning bool or WP_Error
  - Check for at least one "User-agent:" directive
  - Validate directive types: User-agent, Disallow, Allow, Sitemap, Crawl-delay
  - Validate paths start with / or are *
  - Check for HTML tags (reject if present)
  - Check size limit (500KB max)
  - Return detailed error messages with line numbers
  - _Requirements: 4.4, 4.5_

- [ ] 4.3 Create Robots.txt settings UI
  - Extend `includes/admin/class-settings-manager.php`
  - Add "Robots.txt" section to Advanced tab
  - Add large textarea (20 rows) for editing content
  - Add "Save Changes" button with validation
  - Add "Reset to Default" button with confirmation dialog
  - Add "Preview" link to /robots.txt
  - Add help text explaining syntax and common directives
  - Display validation errors with line numbers
  - _Requirements: 4.1, 4.2, 4.7, 4.8, 4.9_

- [ ] 4.4 Implement robots.txt content storage and output
  - Store content in `meowseo_options['robots_txt_content']`
  - Extend existing `includes/modules/seo/class-robots-txt.php`
  - Add `get_custom_content()` and `set_custom_content()` methods
  - Hook into `do_robots` action to output custom content
  - Use default WordPress content if custom content is empty
  - _Requirements: 4.3, 4.6_

- [ ] 4.5 Implement default robots.txt content generation
  - Generate default content with User-agent: *, Disallow: /wp-admin/, Allow: /wp-admin/admin-ajax.php
  - Add Sitemap directive with sitemap URL
  - Use default content for reset functionality
  - _Requirements: 4.7_

- [ ]* 4.6 Write property test for robots.txt round-trip preservation
  - **Property 8: Robots.txt Content Round-Trip Preservation**
  - **Validates: Requirements 4.3**
  - Generate random valid robots.txt content
  - Save content, load back, verify equivalence
  - Test with various directives and paths
  - Minimum 100 iterations

- [ ]* 4.7 Write property test for robots.txt syntax validation
  - **Property 9: Robots.txt Syntax Validation**
  - **Validates: Requirements 4.4**
  - Generate valid and invalid robots.txt content
  - Verify validation correctly identifies valid syntax
  - Verify rejection of invalid syntax (missing User-agent, invalid directives, HTML, oversized)
  - Minimum 100 iterations

### 5. Redirect CSV Import and Export

- [ ] 5.1 Create CSV Importer component
  - Create `includes/modules/redirects/class-csv-importer.php`
  - Implement constructor accepting Redirect_Manager dependency
  - Implement `import_from_file()` method accepting $_FILES array
  - Implement `import_from_string()` method accepting CSV content
  - Implement `parse_csv()` private method
  - Implement `validate_row()` private method
  - Implement `create_redirect()` private method
  - _Requirements: 5.1, 5.3_

- [ ] 5.2 Implement CSV parsing and validation
  - Parse CSV with header row detection
  - Validate required columns: source_url, target_url, status_code
  - Validate status_code is one of: 301, 302, 307, 410
  - Validate source_url and target_url are non-empty
  - Validate regex patterns with `preg_match` test
  - Return detailed errors with line numbers
  - _Requirements: 5.4, 5.5_

- [ ] 5.3 Implement duplicate redirect handling
  - Check for existing redirects with same source_url
  - Skip duplicate entries during import
  - Track count of skipped duplicates
  - Include skipped count in import summary
  - _Requirements: 5.12_

- [ ] 5.4 Implement CSV import process
  - Create redirect records for each valid row
  - Display import summary: imported count, skipped count, errors
  - Display detailed error messages with line numbers
  - Display warnings for skipped duplicates
  - Log import action with user ID and timestamp
  - _Requirements: 5.6, 5.7_

- [ ] 5.5 Create CSV Exporter component
  - Create `includes/modules/redirects/class-csv-exporter.php`
  - Implement constructor accepting Redirect_Manager dependency
  - Implement `export_to_file()` method returning file path
  - Implement `export_to_string()` method returning CSV content
  - Implement `get_all_redirects()` private method
  - Implement `format_redirect_row()` private method
  - Implement `generate_csv_content()` private method
  - _Requirements: 5.2, 5.8_

- [ ] 5.6 Implement CSV export format
  - Export columns: source_url, target_url, status_code, hits, created_date, last_accessed
  - Include header row
  - Format dates as YYYY-MM-DD HH:MM:SS
  - Include all existing redirects
  - _Requirements: 5.8, 5.9_

- [ ] 5.7 Implement CSV export filename generation
  - Generate filename: "meowseo-redirects-YYYY-MM-DD.csv"
  - Use current date for YYYY-MM-DD
  - Set appropriate headers for download
  - Stream CSV content to browser
  - _Requirements: 5.10_

- [ ] 5.8 Create CSV Import/Export UI
  - Extend `includes/admin/class-tools-manager.php`
  - Add "Import/Export" section to Tools page
  - Add file upload control for CSV import
  - Add "Export Redirects" button for CSV export
  - Display import summary after upload
  - Display error messages for failed imports
  - Handle admin_post actions for import/export
  - _Requirements: 5.1, 5.2_

- [ ]* 5.9 Write property test for CSV column validation
  - **Property 10: CSV Column Validation**
  - **Validates: Requirements 5.4**
  - Generate CSV with various column combinations
  - Verify validation accepts CSV with all required columns
  - Verify rejection of CSV missing required columns
  - Minimum 100 iterations

- [ ]* 5.10 Write property test for CSV filename format
  - **Property 11: CSV Filename Format**
  - **Validates: Requirements 5.10**
  - Export redirects multiple times
  - Verify filename matches pattern "meowseo-redirects-YYYY-MM-DD.csv"
  - Verify date is current date
  - Minimum 100 iterations

- [ ]* 5.11 Write property test for redirect CSV round-trip preservation
  - **Property 12: Redirect CSV Round-Trip Preservation**
  - **Validates: Requirements 5.11**
  - Generate random redirect records
  - Export to CSV, import CSV, verify equivalence
  - Test with regex patterns, special characters in URLs
  - Minimum 100 iterations

- [ ]* 5.12 Write property test for duplicate redirect handling
  - **Property 13: Duplicate Redirect Handling**
  - **Validates: Requirements 5.12**
  - Generate CSV with duplicate source URLs
  - Verify all duplicates after first are skipped
  - Verify count of skipped duplicates is reported
  - Minimum 100 iterations

### 6. Cornerstone Content Management

- [ ] 6.1 Create Cornerstone Manager component
  - Create `includes/modules/cornerstone/class-cornerstone-manager.php`
  - Implement constructor accepting Options dependency
  - Implement `is_cornerstone()` method checking postmeta
  - Implement `set_cornerstone()` method setting/deleting postmeta
  - Implement `get_cornerstone_posts()` method with query args
  - Implement `get_cornerstone_count()` method
  - Implement `apply_cornerstone_weight()` method for link suggestions
  - _Requirements: 6.2, 6.3, 6.9, 6.10_

- [ ] 6.2 Implement cornerstone postmeta storage
  - Store value "1" in postmeta key "_meowseo_is_cornerstone" when checked
  - Delete postmeta key when unchecked
  - Add database index for efficient queries
  - Validate post ID exists before setting
  - _Requirements: 6.2, 6.3_

- [ ] 6.3 Create cornerstone checkbox in Gutenberg sidebar
  - Extend Gutenberg sidebar Advanced tab
  - Add checkbox control labeled "Mark as Cornerstone Content"
  - Add help text: "Cornerstone content represents your most important pages. These will be prioritized in internal link suggestions."
  - Sync checkbox state with postmeta
  - Save on post save
  - _Requirements: 6.1_

- [ ] 6.4 Create List Table Integration component
  - Create `includes/modules/cornerstone/class-list-table-integration.php`
  - Implement constructor accepting Cornerstone_Manager dependency
  - Implement `register_hooks()` method
  - Implement `add_cornerstone_column()` method
  - Implement `render_cornerstone_column()` method
  - Implement `add_cornerstone_filter()` method
  - Implement `filter_by_cornerstone()` method
  - Implement `register_sortable_column()` method
  - _Requirements: 6.4, 6.5, 6.6, 6.7, 6.8_

- [ ] 6.5 Implement cornerstone column in post list table
  - Hook into `manage_{post_type}_posts_columns` filter
  - Add "Cornerstone" column to all public post types
  - Display star icon (⭐ or dashicon-star-filled) for cornerstone posts
  - Display empty cell for non-cornerstone posts
  - Add tooltip: "Cornerstone Content"
  - Make column sortable
  - _Requirements: 6.4, 6.7, 6.8_

- [ ] 6.6 Implement cornerstone filter dropdown
  - Hook into `restrict_manage_posts` action
  - Add filter dropdown with options: "All Posts", "Cornerstone Only", "Non-Cornerstone"
  - Hook into `pre_get_posts` filter
  - Apply meta_query filtering by "_meowseo_is_cornerstone" postmeta
  - _Requirements: 6.5, 6.6_

- [ ] 6.7 Implement cornerstone link suggestion weighting
  - Modify existing Link_Suggestions_Engine
  - Apply 2x weight to cornerstone posts in scoring
  - Scoring formula: `base_score * (is_cornerstone ? 2 : 1)`
  - Ensure cornerstone posts appear higher in suggestion list
  - _Requirements: 6.9_

- [ ] 6.8 Create cornerstone dashboard widget
  - Add "Cornerstone Content" widget to MeowSEO dashboard
  - Display count of total cornerstone posts
  - Display list of cornerstone posts with edit links
  - Add link to filtered post list showing only cornerstone posts
  - _Requirements: 6.10_

- [ ]* 6.9 Write property test for cornerstone postmeta storage
  - **Property 14: Cornerstone Postmeta Storage**
  - **Validates: Requirements 6.2, 6.3**
  - Generate random posts
  - Set cornerstone checked, verify postmeta value "1"
  - Set cornerstone unchecked, verify postmeta deleted
  - Minimum 100 iterations

- [ ]* 6.10 Write property test for cornerstone indicator display
  - **Property 15: Cornerstone Indicator Display**
  - **Validates: Requirements 6.4**
  - Generate posts with and without cornerstone postmeta
  - Verify indicator displayed only for posts with "_meowseo_is_cornerstone" = "1"
  - Verify no indicator for posts without postmeta
  - Minimum 100 iterations

- [ ]* 6.11 Write property test for cornerstone filter accuracy
  - **Property 16: Cornerstone Filter Accuracy**
  - **Validates: Requirements 6.6**
  - Generate mixed set of cornerstone and non-cornerstone posts
  - Apply cornerstone filter
  - Verify only posts with "_meowseo_is_cornerstone" = "1" are displayed
  - Minimum 100 iterations

- [ ]* 6.12 Write property test for cornerstone link suggestion weighting
  - **Property 17: Cornerstone Link Suggestion Weighting**
  - **Validates: Requirements 6.9**
  - Generate posts with base scores
  - Verify cornerstone posts have exactly 2x score of non-cornerstone posts with equivalent relevance
  - Minimum 100 iterations

- [ ]* 6.13 Write property test for cornerstone count accuracy
  - **Property 18: Cornerstone Count Accuracy**
  - **Validates: Requirements 6.10**
  - Generate random number of cornerstone posts
  - Verify dashboard widget count exactly equals number of posts with "_meowseo_is_cornerstone" = "1"
  - Minimum 100 iterations

### 7. Integration and Testing

- [ ] 7.1 Checkpoint - Ensure all tests pass
  - Run all property-based tests
  - Run all unit tests
  - Fix any failing tests
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7.2 Integration testing with WordPress environment
  - Test schema output on real WordPress site
  - Test SERP preview in Gutenberg editor
  - Test webmaster verification meta tags in page source
  - Test robots.txt editor with /robots.txt endpoint
  - Test CSV import/export with real redirects
  - Test cornerstone management in post list table

- [ ]* 7.3 Write integration tests for schema output
  - Create test site with organization settings
  - Generate page output
  - Verify JSON-LD script blocks in head
  - Verify schema structure with real WordPress environment

- [ ]* 7.4 Write integration tests for SERP preview
  - Render Gutenberg sidebar
  - Verify SERP preview component appears
  - Test real-time updates with user input
  - Verify character counters update

- [ ]* 7.5 Write integration tests for webmaster verification
  - Configure verification codes
  - Load frontend page
  - Verify meta tags in head
  - Test with real WordPress environment

- [ ]* 7.6 Write integration tests for robots.txt editor
  - Save robots.txt content via admin UI
  - Load /robots.txt endpoint
  - Verify content matches saved content
  - Test reset functionality

- [ ]* 7.7 Write integration tests for CSV import/export
  - Create test redirects
  - Export to CSV
  - Import CSV
  - Verify redirects match
  - Test with real database

- [ ]* 7.8 Write integration tests for cornerstone management
  - Mark posts as cornerstone
  - Load post list table
  - Verify column and filter work
  - Test link suggestions with cornerstone posts
  - Test with real WordPress environment

- [ ] 7.9 Final checkpoint - Ensure all tests pass
  - Run all tests (property-based, unit, integration)
  - Verify all features work in WordPress environment
  - Fix any remaining issues
  - Ensure all tests pass, ask the user if questions arise.

## Notes

### Property-Based Testing

Tasks marked with `*` are optional property-based test tasks. These validate universal correctness properties defined in the design document. Each property test should:
- Generate diverse random inputs (minimum 100 iterations)
- Test edge cases (empty strings, special characters, Unicode, emoji)
- Verify the property holds for all valid inputs
- Use appropriate PBT library (PHPUnit with Eris for PHP, fast-check for TypeScript)

### Testing Strategy

- **Property tests** validate universal behaviors (round-trip, character counting, truncation, validation)
- **Unit tests** validate specific examples and edge cases
- **Integration tests** validate end-to-end flows in real WordPress environment
- All tests are optional sub-tasks and can be skipped for faster MVP

### Implementation Order

The tasks are ordered to enable incremental progress:
1. Each feature is self-contained and can be implemented independently
2. Backend components (PHP) are implemented before frontend components (TypeScript/React)
3. Core functionality is implemented before testing tasks
4. Property tests are placed close to implementation to catch errors early

### Requirements Traceability

Each task explicitly references the requirements it implements, ensuring complete coverage of all acceptance criteria from the requirements document.

### Checkpoints

Two checkpoint tasks ensure validation at reasonable breaks:
- Checkpoint 7.1: After all implementation and property tests
- Checkpoint 7.9: After all integration tests

These checkpoints provide opportunities to verify progress and address any issues before proceeding.
