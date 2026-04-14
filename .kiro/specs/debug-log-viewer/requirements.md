# Requirements Document

## Introduction

The Debug Log and Error Viewer module provides centralized logging, error capture, and AI-friendly log export capabilities for the MeowSEO WordPress plugin. This module enables developers to diagnose issues across all plugin modules through a unified logging system with database storage, automatic error capture, and formatted export for AI-powered debugging tools.

## Glossary

- **Logger**: Singleton class providing centralized logging entry point
- **Log_Viewer**: Admin UI component displaying log entries with filtering and pagination
- **Log_Formatter**: Component formatting log entries for AI editor consumption
- **Module_Manager**: Core plugin component managing module lifecycle
- **REST_Logs**: REST API handler for asynchronous log operations
- **Log_Entry**: Single record in the meowseo_logs database table
- **Log_Level**: Severity classification (DEBUG, INFO, WARNING, ERROR, CRITICAL)
- **Context_Data**: Additional structured data attached to log entries
- **Stack_Trace**: Call stack information captured with errors
- **Sensitive_Data**: API keys, tokens, passwords requiring sanitization

## Requirements

### Requirement 1: Centralized Logging System

**User Story:** As a developer, I want a single entry point for all logging activities, so that I can consistently log events across all plugin modules.

#### Acceptance Criteria

1. THE Logger SHALL implement the singleton pattern
2. THE Logger SHALL provide methods for each log level: debug(), info(), warning(), error(), critical()
3. WHEN a log method is called, THE Logger SHALL store the entry in the meowseo_logs table
4. THE Logger SHALL accept message string and optional context array parameters
5. THE Logger SHALL automatically capture timestamp, module name, and log level for each entry

### Requirement 2: Database Storage

**User Story:** As a developer, I want logs stored in a database table, so that I can display and manage logs through the WordPress admin UI.

#### Acceptance Criteria

1. THE Logger SHALL store log entries in the meowseo_logs custom table
2. THE meowseo_logs table SHALL include columns: id, level, module, message, context, stack_trace, created_at
3. THE Logger SHALL serialize context data as JSON before storage
4. THE Logger SHALL use prepared statements for all database operations
5. THE Logger SHALL create indexes on level, module, and created_at columns

### Requirement 3: Auto-Capture PHP Errors

**User Story:** As a developer, I want PHP errors automatically logged, so that I can diagnose runtime issues without manual error handling.

#### Acceptance Criteria

1. THE Logger SHALL register a custom error handler using set_error_handler()
2. WHEN a PHP error occurs within the MeowSEO namespace, THE Logger SHALL capture and log it
3. THE Logger SHALL capture error level, message, file path, and line number
4. THE Logger SHALL register a shutdown function to capture fatal errors
5. THE Logger SHALL map PHP error levels to appropriate log levels (E_WARNING → WARNING, E_ERROR → ERROR)

### Requirement 4: Auto-Capture Exceptions

**User Story:** As a developer, I want uncaught exceptions automatically logged, so that I can identify module failures without explicit try-catch blocks.

#### Acceptance Criteria

1. THE Module_Manager SHALL wrap module boot() calls in try-catch blocks
2. WHEN an exception is thrown during module boot, THE Module_Manager SHALL log it via Logger
3. THE Logger SHALL capture exception message, class name, file, line, and full stack trace
4. THE Module_Manager SHALL continue booting other modules after logging an exception
5. THE Logger SHALL store stack traces in the stack_trace column

### Requirement 5: Log Entry Limit

**User Story:** As a system administrator, I want automatic log cleanup, so that the database does not grow unbounded.

#### Acceptance Criteria

1. WHEN the meowseo_logs table exceeds 1000 entries, THE Logger SHALL delete the oldest entries
2. THE Logger SHALL perform cleanup after each new log insertion
3. THE Logger SHALL use a single DELETE query with ORDER BY created_at ASC LIMIT
4. THE Logger SHALL maintain exactly 1000 entries maximum
5. THE Logger SHALL preserve the most recent 1000 entries

### Requirement 6: Log Deduplication

**User Story:** As a developer, I want duplicate log entries consolidated, so that I can identify recurring issues without log spam.

#### Acceptance Criteria

1. WHEN a log entry matches an existing entry within 5 minutes, THE Logger SHALL increment a counter instead of creating a new entry
2. THE Logger SHALL match entries by level, module, and message hash
3. THE meowseo_logs table SHALL include a hit_count column defaulting to 1
4. THE Logger SHALL update hit_count and created_at timestamp for duplicate entries
5. THE Logger SHALL use a unique index on (level, module, message_hash, created_at) for deduplication

### Requirement 7: Admin UI - Log Viewer Page

**User Story:** As a developer, I want a log viewer admin page, so that I can review logged events through the WordPress admin interface.

#### Acceptance Criteria

1. THE Log_Viewer SHALL register a submenu under MeowSEO admin menu
2. WHEN WP_DEBUG is false and debug mode is not explicitly enabled, THE Log_Viewer SHALL hide the submenu
3. THE Log_Viewer SHALL display log entries in a table with columns: level, module, message, timestamp, hit_count
4. THE Log_Viewer SHALL implement pagination with 50 entries per page
5. THE Log_Viewer SHALL provide expandable rows showing context data and stack traces

### Requirement 8: Log Filtering

**User Story:** As a developer, I want to filter logs by criteria, so that I can focus on relevant entries.

#### Acceptance Criteria

1. THE Log_Viewer SHALL provide filter controls for log level, module, and time range
2. WHEN a filter is applied, THE Log_Viewer SHALL fetch filtered results via REST API without page reload
3. THE Log_Viewer SHALL support multiple log level selection (checkboxes)
4. THE Log_Viewer SHALL provide a date range picker for time filtering
5. THE Log_Viewer SHALL persist filter state in browser session storage

### Requirement 9: Bulk Operations

**User Story:** As a developer, I want to perform bulk operations on logs, so that I can efficiently manage multiple entries.

#### Acceptance Criteria

1. THE Log_Viewer SHALL provide checkboxes for selecting multiple log entries
2. THE Log_Viewer SHALL provide a "Select All" checkbox for current page
3. THE Log_Viewer SHALL provide bulk actions: Delete, Copy for AI Editor
4. WHEN bulk delete is triggered, THE Log_Viewer SHALL send selected IDs to REST API for deletion
5. WHEN bulk copy is triggered, THE Log_Viewer SHALL format all selected entries and copy to clipboard

### Requirement 10: AI-Friendly Export

**User Story:** As a developer, I want to copy logs in AI-friendly format, so that I can paste them directly into AI coding assistants.

#### Acceptance Criteria

1. THE Log_Formatter SHALL include plugin version, WordPress version, PHP version in formatted output
2. THE Log_Formatter SHALL include active module list in formatted output
3. THE Log_Formatter SHALL include error message, level, module, timestamp for each entry
4. THE Log_Formatter SHALL include full stack trace when available
5. THE Log_Formatter SHALL include file path and line number for each stack frame
6. THE Log_Formatter SHALL format output as markdown with code blocks
7. WHEN "Copy for AI Editor" is clicked, THE Log_Viewer SHALL copy formatted text to clipboard using Clipboard API
8. WHEN clipboard copy succeeds, THE Log_Viewer SHALL display visual feedback (success message)

### Requirement 11: GSC Module Integration

**User Story:** As a developer, I want GSC module events logged, so that I can diagnose OAuth and API issues.

#### Acceptance Criteria

1. WHEN OAuth authentication fails, THE GSC module SHALL log the error with error level
2. WHEN a rate limit response (HTTP 429) is received, THE GSC module SHALL log it with warning level
3. WHEN batch processing completes, THE GSC module SHALL log the result with info level
4. THE GSC module SHALL include job type and payload summary in log context
5. THE GSC module SHALL sanitize access tokens from log context

### Requirement 12: Sitemap Module Integration

**User Story:** As a developer, I want sitemap generation events logged, so that I can diagnose cache and generation failures.

#### Acceptance Criteria

1. WHEN sitemap generation fails, THE Sitemap module SHALL log the error with error level
2. WHEN sitemap cache is regenerated, THE Sitemap module SHALL log it with info level
3. THE Sitemap module SHALL include post type and entry count in log context
4. WHEN file write fails, THE Sitemap module SHALL log the error with file path in context

### Requirement 13: Redirects Module Integration

**User Story:** As a developer, I want redirect events logged, so that I can diagnose loop detection and import errors.

#### Acceptance Criteria

1. WHEN a redirect loop is detected, THE Redirects module SHALL log it with warning level
2. WHEN CSV import fails, THE Redirects module SHALL log the error with error level
3. THE Redirects module SHALL include source URL and target URL in log context
4. WHEN CSV import succeeds, THE Redirects module SHALL log the result with info level including row count

### Requirement 14: REST API Endpoints

**User Story:** As a frontend developer, I want REST API endpoints for log operations, so that I can build asynchronous UI interactions.

#### Acceptance Criteria

1. THE REST_Logs SHALL register GET /meowseo/v1/logs endpoint for fetching logs
2. THE REST_Logs SHALL register DELETE /meowseo/v1/logs endpoint for deleting logs
3. THE REST_Logs SHALL register GET /meowseo/v1/logs/{id}/formatted endpoint for single entry export
4. THE REST_Logs GET endpoint SHALL accept query parameters: level, module, start_date, end_date, page, per_page
5. THE REST_Logs SHALL return JSON response with logs array and pagination metadata

### Requirement 15: Security - Capability Checks

**User Story:** As a security-conscious administrator, I want log operations restricted to administrators, so that sensitive debug information is protected.

#### Acceptance Criteria

1. THE REST_Logs SHALL require manage_options capability for all endpoints
2. THE Log_Viewer SHALL verify current_user_can('manage_options') before rendering
3. WHEN a user lacks manage_options capability, THE REST_Logs SHALL return 403 Forbidden
4. WHEN a user lacks manage_options capability, THE Log_Viewer SHALL display access denied message

### Requirement 16: Security - Nonce Verification

**User Story:** As a security-conscious administrator, I want CSRF protection on log operations, so that malicious sites cannot manipulate logs.

#### Acceptance Criteria

1. THE REST_Logs SHALL verify nonce for DELETE endpoint
2. THE Log_Viewer SHALL include nonce in all REST API requests
3. WHEN nonce verification fails, THE REST_Logs SHALL return 403 Forbidden
4. THE Log_Viewer SHALL generate nonce using wp_create_nonce('wp_rest')

### Requirement 17: Security - Sensitive Data Sanitization

**User Story:** As a security-conscious administrator, I want sensitive data removed from logs, so that credentials are not exposed in debug output.

#### Acceptance Criteria

1. THE Logger SHALL scan context data for keys matching sensitive patterns (token, key, password, secret)
2. WHEN a sensitive key is detected, THE Logger SHALL replace its value with '[REDACTED]'
3. THE Logger SHALL sanitize both top-level and nested context data
4. THE Logger SHALL preserve non-sensitive context data unchanged
5. THE Logger SHALL apply sanitization before database storage

### Requirement 18: Parser and Pretty Printer for Log Entries

**User Story:** As a developer, I want to parse and format log entries, so that I can reliably export and import log data.

#### Acceptance Criteria

1. WHEN a log entry is retrieved from database, THE Log_Formatter SHALL parse JSON context into structured data
2. WHEN context parsing fails, THE Log_Formatter SHALL return empty array and log parsing error
3. THE Log_Formatter SHALL format log entries into human-readable markdown
4. THE Log_Formatter SHALL format stack traces with file paths and line numbers
5. FOR ALL valid log entries, parsing then formatting then parsing SHALL produce equivalent structured data (round-trip property)
