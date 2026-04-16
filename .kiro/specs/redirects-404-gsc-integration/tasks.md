# Implementation Plan: Redirects Module, 404 Monitor, and GSC API Integration

## Overview

This implementation plan breaks down the Redirects Module, 404 Monitor, and Google Search Console (GSC) API Integration into discrete coding tasks. The implementation follows a modular architecture with three independent modules that can be built incrementally. Each module implements the Module_Interface pattern and includes REST API endpoints for external integration.

## Tasks

- [x] 1. Verify and update database schema
  - Review the existing schema in `includes/class-installer.php`
  - Verify that all required tables exist: `meowseo_redirects`, `meowseo_404_log`, `meowseo_gsc_queue`, `meowseo_gsc_data`
  - Add missing columns if needed: `is_active` to redirects table, ensure proper indexes
  - Update the `deactivate()` method to clear the new cron hooks: `meowseo_flush_404_cron` and `meowseo_process_gsc_queue`
  - _Requirements: 1.1, 1.2, 7.1, 7.6, 10.1_

- [x] 2. Implement Redirects Module core functionality
  - [x] 2.1 Create `includes/modules/redirects/class-redirects.php` (Redirects_Module)
    - Implement Module_Interface with `boot()` and `get_id()` methods
    - Add properties for admin, REST, and options instances
    - Register hooks: `template_redirect` (priority 1), `post_updated`, `shutdown` (priority 999)
    - Implement `handle_redirect()` method with exact match query using indexed source_url
    - Implement regex fallback with Object Cache (5 min TTL) and has_regex_rules flag check
    - Execute redirects using `wp_redirect()` with proper status codes (301, 302, 307, 410, 451)
    - Implement `record_hit_async()` for asynchronous hit tracking on shutdown hook
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4_

  - [x] 2.2 Implement automatic slug change redirects
    - Add `handle_post_updated()` method hooked to `post_updated`
    - Check if post status is 'publish' and slug has changed
    - Query database to check if redirect already exists for old URL
    - Check if old URL is target of another redirect to avoid chains
    - Create 301 redirect from old permalink to new permalink
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [x] 2.3 Implement regex pattern matching with backreferences
    - Add regex matching logic in `handle_redirect()` method
    - Support backreferences ($1, $2, etc.) in target URLs
    - Add # delimiters automatically if pattern lacks delimiters
    - Use @ operator to suppress warnings for invalid patterns
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

  - [x] 2.4 Implement redirect loop detection
    - Maintain redirect chain array tracking visited URLs
    - Check if target URL is already in chain before executing redirect
    - Log warning with source URL, target URL, and chain when loop detected
    - Stop processing when loop is detected
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [x] 3. Implement Redirects Admin interface
  - [x] 3.1 Create `includes/modules/redirects/class-redirects-admin.php` (Redirects_Admin)
    - Implement `register_menu()` to add admin page under MeowSEO menu
    - Implement `render_page()` with form, table, and CSV import/export sections
    - Implement `render_form()` for creating new redirects with source URL, target URL, type dropdown, and regex checkbox
    - Implement `render_table()` with pagination (50 per page), search, and bulk actions
    - Add inline edit and delete actions for each redirect row
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

  - [x] 3.2 Implement CSV import and export functionality
    - Implement `render_csv_import()` with file upload form
    - Implement `render_csv_export()` with download button
    - Add validation for CSV columns: source_url, target_url, redirect_type, is_regex
    - Skip empty rows and rows with missing required fields
    - Default redirect_type to 301 if not provided or invalid
    - Log import results with imported count, skipped count, and errors
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

- [x] 4. Implement Redirects REST API
  - [x] 4.1 Create `includes/modules/redirects/class-redirects-rest.php` (Redirects_REST)
    - Register REST routes: POST /redirects, PUT /redirects/{id}, DELETE /redirects/{id}
    - Register CSV routes: POST /redirects/import, GET /redirects/export
    - Implement `create_redirect()` with validation and nonce verification
    - Implement `update_redirect()` with ID validation and capability check
    - Implement `delete_redirect()` with ID validation and capability check
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6_

  - [x] 4.2 Implement redirect validation and chain detection
    - Implement `validate_redirect_data()` to check required fields and valid redirect types
    - Implement `check_redirect_chain()` to prevent creating redirect loops
    - Return proper error responses with HTTP status codes
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 16.6_

  - [x] 4.3 Implement CSV import and export endpoints
    - Implement `import_redirects()` to parse CSV data and bulk insert
    - Implement `export_redirects()` to return all rules in CSV format
    - Set proper Content-Type headers for CSV download
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 16.4, 16.5_

- [x] 5. Checkpoint - Test Redirects Module
  - Ensure all tests pass, verify redirect matching works with exact and regex rules
  - Test automatic slug change redirects
  - Test CSV import/export functionality
  - Ask the user if questions arise

- [x] 6. Implement 404 Monitor core functionality
  - [x] 6.1 Create `includes/modules/monitor_404/class-monitor-404.php` (Monitor_404_Module)
    - Implement Module_Interface with `boot()` and `get_id()` methods
    - Add properties for admin, REST, options, and ASSET_EXTENSIONS constant
    - Register hooks: `template_redirect` (priority 999)
    - Implement `capture_404()` method to detect 404 responses
    - Skip requests with empty User-Agent, static assets, and URLs on ignore list
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

  - [x] 6.2 Implement 404 buffering with Object Cache
    - Implement `buffer_404()` method to store URLs in per-minute buckets
    - Use bucket key format: `404_YYYYMMDD_HHmm`
    - Set TTL to 120 seconds for each bucket
    - _Requirements: 7.5, 7.6_

  - [x] 6.3 Implement WP-Cron batch processing
    - Implement `schedule_flush()` to register cron event (every 60 seconds)
    - Implement `flush_buffer()` to retrieve buckets for -1 and -2 minutes
    - Aggregate URLs by counting occurrences
    - Perform single upsert per unique URL using INSERT ... ON DUPLICATE KEY UPDATE
    - Increment hit_count and update last_seen on existing rows
    - Delete processed buckets from Object Cache
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

- [x] 7. Implement 404 Monitor Admin interface
  - [x] 7.1 Create `includes/modules/monitor_404/class-monitor-404-admin.php` (Monitor_404_Admin)
    - Implement `register_menu()` to add admin page under MeowSEO menu
    - Implement `render_page()` with table, bulk actions, and clear all button
    - Implement `render_table()` with pagination (50 per page) and sorting
    - Display columns: URL, Hits, First Seen, Last Seen, Actions
    - _Requirements: 13.1, 13.2, 13.3_

  - [x] 7.2 Implement admin actions for 404 entries
    - Add Create Redirect action with inline form (target URL and redirect type fields)
    - Remove URL from 404 log when redirect is created
    - Add Ignore action to add URL to ignore list in plugin options
    - Add Clear All button with JavaScript confirmation dialog
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

- [x] 8. Implement 404 Monitor REST API
  - [x] 8.1 Create `includes/modules/monitor_404/class-monitor-404-rest.php` (Monitor_404_REST)
    - Register REST routes: GET /404-log, DELETE /404-log/{id}
    - Register action routes: POST /404-log/ignore, POST /404-log/clear-all
    - Implement `get_log()` with pagination (page, per_page) and sorting (orderby, order)
    - Implement `delete_entry()` with ID validation and capability check
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5_

  - [x] 8.2 Implement ignore and clear all endpoints
    - Implement `ignore_url()` to add URL to ignore list in options
    - Implement `clear_all()` to delete all rows from 404 log table
    - Verify nonce and check manage_options capability
    - _Requirements: 13.4, 13.5, 17.5_

- [x] 9. Checkpoint - Test 404 Monitor Module
  - Ensure all tests pass, verify 404 capture and buffering works
  - Test batch processing with WP-Cron
  - Test admin actions and REST API endpoints
  - Ask the user if questions arise

- [x] 10. Implement GSC Authentication
  - [x] 10.1 Create `includes/modules/gsc/class-gsc-auth.php` (GSC_Auth)
    - Add properties for options, GOOGLE_AUTH_URL, GOOGLE_TOKEN_URL, and SCOPES constants
    - Implement `get_auth_url()` to generate OAuth consent URL with redirect_uri
    - Implement `handle_callback()` to exchange authorization code for tokens
    - Implement `get_valid_token()` to return access token or refresh if expired
    - _Requirements: 9.1, 9.5, 9.6_

  - [x] 10.2 Implement token encryption and storage
    - Implement `encrypt_token()` using openssl_encrypt with AES-256-CBC and AUTH_KEY
    - Implement `decrypt_token()` using openssl_decrypt with AES-256-CBC and AUTH_KEY
    - Implement `store_credentials()` to save encrypted tokens in options
    - Store: client_id, client_secret, access_token (encrypted), refresh_token (encrypted), token_expiry
    - _Requirements: 9.2_

  - [x] 10.3 Implement token refresh logic
    - Implement `refresh_token()` to request new access token using refresh token
    - Update access_token and token_expiry on successful refresh
    - Set meowseo_gsc_auth_status to 'revoked' on refresh failure
    - _Requirements: 9.3, 9.4_

- [x] 11. Implement GSC Queue Processing
  - [x] 11.1 Create `includes/modules/gsc/class-gsc-queue.php` (GSC_Queue)
    - Add properties for options, api, MAX_BATCH_SIZE (10), RETRY_MULTIPLIER (2), BASE_RETRY_DELAY (60)
    - Implement `enqueue()` to insert jobs into meowseo_gsc_queue table
    - Implement `check_duplicate()` to prevent duplicate pending jobs
    - _Requirements: 10.1, 10.2_

  - [x] 11.2 Implement batch processing with exponential backoff
    - Implement `process_batch()` to query up to 10 pending jobs with retry_after < NOW()
    - Update status to 'processing' before making API call
    - Handle HTTP 429 rate limit: set status to 'pending', increment attempts, calculate retry_after
    - Handle success: set status to 'done', store response data
    - Handle errors: set status to 'failed', store error response
    - _Requirements: 10.3, 10.4, 10.5, 10.6_

  - [x] 11.3 Implement retry delay calculation
    - Implement `calculate_retry_delay()` using formula: 60 * 2^attempts
    - Implement `schedule_next_batch()` to schedule cron event if pending jobs remain
    - _Requirements: 10.5_

- [x] 12. Implement GSC API wrapper
  - [x] 12.1 Create `includes/modules/gsc/class-gsc-api.php` (GSC_API)
    - Add properties for auth, INSPECTION_API_URL, INDEXING_API_URL, ANALYTICS_API_URL constants
    - Implement `make_request()` to call Google APIs with Authorization Bearer header
    - Implement `handle_response()` to parse response and return consistent array shape
    - Return format: ['success' => bool, 'data' => array, 'http_code' => int]
    - _Requirements: 14.3, 14.4_

  - [x] 12.2 Implement URL Inspection API
    - Implement `inspect_url()` to call URL Inspection API endpoint
    - Return indexing status, coverage state, crawled date, and issues
    - Return error array if get_valid_token() returns false
    - _Requirements: 14.1, 14.2, 14.4_

  - [x] 12.3 Implement Indexing API
    - Implement `submit_for_indexing()` to call Indexing API endpoint
    - Send POST request with URL and type='URL_UPDATED'
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

  - [x] 12.4 Implement Search Analytics API
    - Implement `get_search_analytics()` to call Search Analytics query endpoint
    - Accept parameters: site_url, start_date, end_date, dimensions, data_state
    - Include Google Discover data when data_state='all'
    - Store analytics data in meowseo_gsc_data table
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5_

- [x] 13. Implement GSC Module core functionality
  - [x] 13.1 Create `includes/modules/gsc/class-gsc.php` (GSC_Module)
    - Implement Module_Interface with `boot()` and `get_id()` methods
    - Add properties for auth, queue, api, rest, and options instances
    - Register hooks: `transition_post_status`
    - Implement `register_cron()` to schedule queue processing (every 5 minutes)
    - _Requirements: 10.1, 10.3_

  - [x] 13.2 Implement automatic indexing requests
    - Implement `handle_post_transition()` hooked to `transition_post_status`
    - Enqueue indexing job when post transitions to 'publish' from any other status
    - Check _meowseo_gsc_last_submit postmeta for published post updates
    - Enqueue new job and update postmeta timestamp if modified since last submission
    - Only process public and indexable post types
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

  - [x] 13.3 Implement queue processing method
    - Implement `process_queue()` to call GSC_Queue::process_batch()
    - Hook to WP-Cron event
    - _Requirements: 10.3, 10.4_

- [x] 14. Implement GSC REST API
  - [x] 14.1 Create `includes/modules/gsc/class-gsc-rest.php` (GSC_REST)
    - Register REST routes: GET /gsc/status, POST /gsc/auth, DELETE /gsc/auth, GET /gsc/data
    - Implement `get_status()` to return connection status and auth state
    - Implement `save_auth()` to save OAuth credentials with nonce and capability check
    - Implement `remove_auth()` to delete OAuth credentials with nonce and capability check
    - _Requirements: 18.3, 18.4, 18.6_

  - [x] 14.2 Implement GSC data endpoint
    - Implement `get_data()` to return GSC performance data
    - Support filtering by URL, start date, and end date query parameters
    - Query meowseo_gsc_data table with proper WHERE clauses
    - _Requirements: 18.1, 18.2_

  - [x] 14.3 Implement status endpoint
    - Implement `get_status()` to return auth status, site URL, and last sync time
    - Check if credentials exist and token is valid
    - _Requirements: 18.5_

- [x] 15. Checkpoint - Test GSC Module
  - Ensure all tests pass, verify OAuth flow works
  - Test queue processing with exponential backoff
  - Test automatic indexing requests on post publish
  - Test REST API endpoints
  - Ask the user if questions arise

- [x] 16. Register modules with Module_Manager
  - Update `includes/class-module-manager.php` to register the three new modules
  - Add Redirects_Module, Monitor_404_Module, and GSC_Module to the modules array
  - Ensure each module's `boot()` method is called during plugin initialization
  - _Requirements: All_

- [x] 17. Final integration and testing
  - [x] 17.1 Test redirect matching performance with large dataset
    - Create test with 1000+ redirect rules
    - Verify exact match query uses index and returns in < 10ms
    - Verify regex fallback only loads when has_regex_rules flag is true
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

  - [x] 17.2 Test 404 buffering under high traffic
    - Simulate 100+ concurrent 404 requests
    - Verify Object Cache buffering works correctly
    - Verify batch processing aggregates hits accurately
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

  - [x] 17.3 Test GSC queue with rate limiting
    - Enqueue 20+ jobs and process batch
    - Simulate HTTP 429 response and verify exponential backoff
    - Verify retry_after calculation is correct
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_

  - [x] 17.4 Test all REST API endpoints
    - Test redirect CRUD operations with proper authentication
    - Test 404 log access and actions
    - Test GSC auth and data endpoints
    - Verify nonce verification and capability checks work
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 17.1, 17.2, 17.3, 17.4, 17.5, 18.1, 18.2, 18.3, 18.4, 18.5, 18.6_

- [x] 18. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, verify all modules work correctly
  - Test integration between modules (e.g., creating redirects from 404 log)
  - Ask the user if questions arise

## Notes

- The database schema already exists in `includes/class-installer.php` but may need minor updates
- Each module is independent and can be implemented in parallel
- REST API endpoints require nonce verification and capability checks for security
- Performance optimizations are critical: indexed queries, Object Cache, and batch processing
- WP-Cron events must be scheduled on module boot and cleared on deactivation
- OAuth tokens must be encrypted using openssl_encrypt with AUTH_KEY
- Exponential backoff prevents API rate limit issues
