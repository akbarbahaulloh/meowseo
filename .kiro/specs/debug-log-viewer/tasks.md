# Implementation Plan: Debug Log and Error Viewer

## Overview

This implementation plan creates a centralized logging system for the MeowSEO WordPress plugin with database storage, automatic error capture, admin UI, and AI-friendly export capabilities. The tasks are organized to build from core infrastructure (database, Logger class) up to UI and module integrations.

## Tasks

- [x] 1. Update database schema in Installer class
  - Add meowseo_logs table definition to get_schema() method
  - Include all columns: id, level, module, message, message_hash, context, stack_trace, hit_count, created_at
  - Add indexes: idx_level, idx_module, idx_created_at, idx_dedup (unique)
  - Use JSON column type for context field
  - _Requirements: 2.1, 2.2, 2.5, 6.5_

- [x] 2. Implement Logger singleton class
  - [x] 2.1 Create Logger class with singleton pattern
    - Implement private constructor and static get_instance() method
    - Create static wrapper methods: debug(), info(), warning(), error(), critical()
    - Implement private log() method that routes to instance methods
    - _Requirements: 1.1, 1.2_

  - [ ]* 2.2 Write property test for Logger singleton
    - **Property 1: Log Storage**
    - **Validates: Requirements 1.3, 2.1**

  - [x] 2.3 Implement automatic field capture
    - Capture timestamp using current_time('mysql')
    - Detect calling module from debug_backtrace()
    - Store log level from method parameter
    - _Requirements: 1.5_

  - [ ]* 2.4 Write property test for automatic field capture
    - **Property 2: Automatic Field Capture**
    - **Validates: Requirements 1.5**

  - [x] 2.5 Implement database storage with prepared statements
    - Use $wpdb->prepare() for all queries
    - Serialize context array to JSON using wp_json_encode()
    - Generate message_hash using hash('sha256', $message)
    - _Requirements: 2.3, 2.4_

  - [ ]* 2.6 Write property test for context serialization
    - **Property 3: Context Serialization Round-Trip**
    - **Validates: Requirements 2.3**

- [x] 3. Implement log deduplication and cleanup
  - [x] 3.1 Implement deduplication logic
    - Check for existing entry with same level, module, message_hash within 5 minutes
    - Increment hit_count and update created_at if duplicate found
    - Use unique index idx_dedup for efficient matching
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [ ]* 3.2 Write property test for deduplication
    - **Property 12: Deduplication**
    - **Validates: Requirements 6.1**

  - [ ]* 3.3 Write property test for deduplication matching
    - **Property 13: Deduplication Matching**
    - **Validates: Requirements 6.2**

  - [x] 3.4 Implement automatic log cleanup
    - Check entry count after each insertion
    - Delete oldest entries when count exceeds 1000
    - Use ORDER BY created_at ASC LIMIT in DELETE query
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ]* 3.5 Write property test for log entry limit
    - **Property 10: Log Entry Limit Invariant**
    - **Validates: Requirements 5.1, 5.4, 5.5**

  - [ ]* 3.6 Write property test for cleanup trigger
    - **Property 11: Cleanup Trigger**
    - **Validates: Requirements 5.2**

- [x] 4. Implement sensitive data sanitization
  - [x] 4.1 Implement sanitize_context() method
    - Scan context keys for sensitive patterns (token, key, password, secret)
    - Replace sensitive values with '[REDACTED]'
    - Implement recursive sanitization for nested arrays
    - Preserve non-sensitive values unchanged
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5_

  - [ ]* 4.2 Write property test for sensitive key redaction
    - **Property 29: Sensitive Key Redaction**
    - **Validates: Requirements 17.1, 17.2**

  - [ ]* 4.3 Write property test for nested sanitization
    - **Property 30: Nested Sanitization**
    - **Validates: Requirements 17.3**

  - [ ]* 4.4 Write property test for non-sensitive preservation
    - **Property 31: Non-Sensitive Preservation**
    - **Validates: Requirements 17.4**

- [x] 5. Implement PHP error and exception capture
  - [x] 5.1 Register custom error handler
    - Implement error_handler() method
    - Register with set_error_handler() in constructor
    - Filter errors to MeowSEO namespace only
    - Capture error level, message, file, line number
    - _Requirements: 3.1, 3.2, 3.3_

  - [ ]* 5.2 Write property test for PHP error capture
    - **Property 4: PHP Error Capture**
    - **Validates: Requirements 3.2**

  - [ ]* 5.3 Write property test for error field capture
    - **Property 5: Error Field Capture**
    - **Validates: Requirements 3.3**

  - [x] 5.4 Implement PHP error level mapping
    - Map E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR to CRITICAL
    - Map E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING to WARNING
    - Map E_NOTICE, E_USER_NOTICE to INFO
    - Map E_DEPRECATED, E_USER_DEPRECATED to DEBUG
    - _Requirements: 3.5_

  - [ ]* 5.5 Write property test for error level mapping
    - **Property 6: PHP Error Level Mapping**
    - **Validates: Requirements 3.5**

  - [x] 5.6 Register shutdown function for fatal errors
    - Implement shutdown_handler() method
    - Register with register_shutdown_function() in constructor
    - Check error_get_last() for fatal errors
    - Log fatal errors with CRITICAL level
    - _Requirements: 3.4_

- [x] 6. Update Module_Manager for exception capture
  - [x] 6.1 Wrap module boot() calls in try-catch
    - Add try-catch block around each module->boot() call
    - Log exception via Logger::error() with full details
    - Continue booting remaining modules after exception
    - Include exception class, message, file, line, stack trace in context
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [ ]* 6.2 Write property test for exception logging
    - **Property 7: Exception Logging**
    - **Validates: Requirements 4.2**

  - [ ]* 6.3 Write property test for exception field capture
    - **Property 8: Exception Field Capture**
    - **Validates: Requirements 4.3**

  - [ ]* 6.4 Write property test for module boot continuation
    - **Property 9: Module Boot Continuation**
    - **Validates: Requirements 4.4**

- [ ] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 8. Implement Log_Formatter class
  - [ ] 8.1 Create Log_Formatter class with static methods
    - Implement format_for_ai() method for multiple entries
    - Implement format_single_entry() method for one entry
    - Implement parse_context() method for JSON parsing
    - Implement get_system_context() private method
    - Implement get_active_modules() private method
    - Implement format_stack_trace() private method
    - _Requirements: 18.1, 18.2, 18.3, 18.4_

  - [ ]* 8.2 Write property test for context parsing
    - **Property 32: Context Parsing**
    - **Validates: Requirements 18.1**

  - [ ] 8.3 Implement markdown formatting with system context
    - Include plugin version, WordPress version, PHP version
    - Include active module list
    - Format each entry with level, module, message, timestamp
    - Include context as JSON code block
    - Include stack trace as code block when available
    - Format stack frames with file path and line number
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8_

  - [ ]* 8.4 Write property test for system info inclusion
    - **Property 17: System Info Inclusion**
    - **Validates: Requirements 10.1**

  - [ ]* 8.5 Write property test for module list inclusion
    - **Property 18: Module List Inclusion**
    - **Validates: Requirements 10.2**

  - [ ]* 8.6 Write property test for entry field inclusion
    - **Property 19: Entry Field Inclusion**
    - **Validates: Requirements 10.3**

  - [ ]* 8.7 Write property test for stack trace inclusion
    - **Property 20: Stack Trace Inclusion**
    - **Validates: Requirements 10.4**

  - [ ]* 8.8 Write property test for stack frame formatting
    - **Property 21: Stack Frame Formatting**
    - **Validates: Requirements 10.5**

  - [ ]* 8.9 Write property test for format-parse round-trip
    - **Property 33: Format-Parse Round-Trip**
    - **Validates: Requirements 18.5**

- [ ] 9. Implement REST_Logs API class
  - [ ] 9.1 Create REST_Logs class and register routes
    - Register GET /meowseo/v1/logs endpoint
    - Register DELETE /meowseo/v1/logs endpoint
    - Register GET /meowseo/v1/logs/{id}/formatted endpoint
    - Implement manage_options_permission() callback
    - _Requirements: 14.1, 14.2, 14.3_

  - [ ] 9.2 Implement GET /logs endpoint with filtering
    - Accept query parameters: level, module, start_date, end_date, page, per_page
    - Build WHERE clause dynamically based on provided filters
    - Implement pagination with LIMIT and OFFSET
    - Return JSON with logs array, total, pages, page, per_page
    - _Requirements: 14.4, 14.5, 8.2_

  - [ ]* 9.3 Write property test for REST response structure
    - **Property 26: REST Response Structure**
    - **Validates: Requirements 14.5**

  - [ ]* 9.4 Write property test for filter matching
    - **Property 15: Filter Matching**
    - **Validates: Requirements 8.2**

  - [ ]* 9.5 Write property test for pagination
    - **Property 14: Pagination**
    - **Validates: Requirements 7.4**

  - [ ] 9.3 Implement DELETE /logs endpoint
    - Accept array of log IDs in request body
    - Verify nonce using verify_nonce() method
    - Delete entries using prepared statement with IN clause
    - Return success response with deleted count
    - _Requirements: 9.4, 16.1, 16.2_

  - [ ]* 9.6 Write property test for nonce verification
    - **Property 28: Nonce Verification**
    - **Validates: Requirements 16.1, 16.3**

  - [ ] 9.4 Implement GET /logs/{id}/formatted endpoint
    - Fetch single log entry by ID
    - Format using Log_Formatter::format_single_entry()
    - Return JSON with formatted markdown string
    - _Requirements: 14.3_

  - [ ] 9.5 Implement capability checks for all endpoints
    - Verify manage_options capability in permission callback
    - Return 403 Forbidden if capability check fails
    - _Requirements: 15.1, 15.2, 15.3_

  - [ ]* 9.7 Write property test for capability check
    - **Property 27: Capability Check**
    - **Validates: Requirements 15.1, 15.3**

- [ ] 10. Implement Log_Viewer admin page class
  - [ ] 10.1 Create Log_Viewer class with admin menu registration
    - Implement boot() method to register hooks
    - Implement register_admin_menu() to add submenu under MeowSEO
    - Implement should_show_menu() to check WP_DEBUG or debug mode option
    - Conditionally register menu based on should_show_menu()
    - _Requirements: 7.1, 7.2_

  - [ ] 10.2 Implement render_log_viewer_page() method
    - Verify manage_options capability
    - Render wrapper div with React root element
    - Display access denied message if capability check fails
    - _Requirements: 7.3, 15.4_

  - [ ] 10.3 Implement enqueue_admin_assets() method
    - Check hook suffix to only load on log viewer page
    - Enqueue JavaScript and CSS assets
    - Localize script with REST URL, nonce, and initial data
    - _Requirements: 7.3_

- [ ] 11. Implement LogViewer.js React component
  - [ ] 11.1 Create LogViewer component with state management
    - Initialize state for logs, filters, pagination, selectedIds, expandedRows
    - Implement useEffect hook to fetch logs on mount and filter changes
    - _Requirements: 7.3, 8.1_

  - [ ] 11.2 Implement log fetching with REST API
    - Use apiFetch to call GET /meowseo/v1/logs
    - Pass filter parameters as query string
    - Update logs and pagination state with response
    - _Requirements: 8.2_

  - [ ] 11.3 Implement filter controls UI
    - Add SelectControl for log level with multiple selection
    - Add SelectControl for module filter
    - Add DatePicker components for start_date and end_date
    - Trigger fetch on filter change
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

  - [ ] 11.4 Implement session storage for filter persistence
    - Save filter state to sessionStorage on change
    - Load filter state from sessionStorage on mount
    - _Requirements: 8.5_

  - [ ]* 11.5 Write property test for session storage round-trip
    - **Property 16: Session Storage Round-Trip**
    - **Validates: Requirements 8.5**

  - [ ] 11.6 Implement log table with expandable rows
    - Display columns: level, module, message, timestamp, hit_count
    - Add expand/collapse button for each row
    - Show context and stack trace in expanded view
    - _Requirements: 7.3, 7.5_

  - [ ] 11.7 Implement pagination controls
    - Display current page, total pages, total entries
    - Add Previous/Next buttons
    - Update page state and trigger fetch on navigation
    - _Requirements: 7.4_

  - [ ] 11.8 Implement bulk selection checkboxes
    - Add checkbox for each log entry
    - Add "Select All" checkbox for current page
    - Track selected IDs in state
    - _Requirements: 9.1, 9.2_

  - [ ] 11.9 Implement bulk delete operation
    - Add "Delete" button for bulk action
    - Show confirmation dialog before delete
    - Call DELETE /meowseo/v1/logs with selected IDs
    - Refresh log list after successful delete
    - _Requirements: 9.3, 9.4_

  - [ ] 11.10 Implement "Copy for AI Editor" operation
    - Add "Copy for AI Editor" button for bulk action
    - Format selected entries using Log_Formatter
    - Copy formatted text to clipboard using Clipboard API
    - Show success message after copy
    - _Requirements: 9.3, 9.5, 10.7, 10.8_

- [ ] 12. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 13. Integrate Logger into GSC module
  - [ ] 13.1 Add logging for OAuth failures
    - Log with error level when authentication fails
    - Include job_type and error_code in context
    - Sanitize access_token from context
    - _Requirements: 11.1, 11.5_

  - [ ]* 13.2 Write property test for token sanitization
    - **Property 23: Token Sanitization**
    - **Validates: Requirements 11.5**

  - [ ] 13.2 Add logging for rate limit responses
    - Log with warning level when HTTP 429 received
    - Include job_type and retry_after in context
    - _Requirements: 11.2_

  - [ ] 13.3 Add logging for batch processing completion
    - Log with info level when batch completes
    - Include job_type and processed_count in context
    - _Requirements: 11.3_

  - [ ]* 13.4 Write property test for GSC context fields
    - **Property 22: GSC Context Fields**
    - **Validates: Requirements 11.4**

- [ ] 14. Integrate Logger into Sitemap module
  - [ ] 14.1 Add logging for generation failures
    - Log with error level when generation fails
    - Include post_type and error message in context
    - _Requirements: 12.1_

  - [ ] 14.2 Add logging for cache regeneration
    - Log with info level when cache regenerated
    - Include post_type and entry_count in context
    - _Requirements: 12.2, 12.3_

  - [ ]* 14.3 Write property test for Sitemap context fields
    - **Property 24: Sitemap Context Fields**
    - **Validates: Requirements 12.3**

  - [ ] 14.3 Add logging for file write failures
    - Log with error level when file write fails
    - Include file_path and error message in context
    - _Requirements: 12.4_

- [ ] 15. Integrate Logger into Redirects module
  - [ ] 15.1 Add logging for redirect loop detection
    - Log with warning level when loop detected
    - Include source_url and target_url in context
    - _Requirements: 13.1, 13.3_

  - [ ]* 15.2 Write property test for Redirects context fields
    - **Property 25: Redirects Context Fields**
    - **Validates: Requirements 13.3**

  - [ ] 15.2 Add logging for CSV import failures
    - Log with error level when import fails
    - Include file_name and error message in context
    - _Requirements: 13.2_

  - [ ] 15.3 Add logging for CSV import success
    - Log with info level when import succeeds
    - Include file_name and row_count in context
    - _Requirements: 13.4_

- [ ] 16. Wire Logger system into plugin initialization
  - [ ] 16.1 Update Admin class to initialize Log_Viewer
    - Add Log_Viewer instantiation in boot() method
    - Call log_viewer->boot() to register hooks
    - _Requirements: 7.1_

  - [ ] 16.2 Update REST_API class to register REST_Logs routes
    - Add REST_Logs instantiation in register_routes() method
    - Call rest_logs->register_routes()
    - _Requirements: 14.1_

  - [ ] 16.3 Verify Logger singleton initialization
    - Ensure Logger::get_instance() is called early in plugin lifecycle
    - Verify error handlers are registered before module boot
    - _Requirements: 1.1, 3.1_

- [ ] 17. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional property-based tests and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property tests validate universal correctness properties from the design document
- Implementation uses PHP for WordPress plugin development
- Checkpoints ensure incremental validation at logical breakpoints
- Module integrations (GSC, Sitemap, Redirects) add logging to existing functionality
- All 33 correctness properties from the design are covered by property tests
