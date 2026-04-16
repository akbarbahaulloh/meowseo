# Requirements Document

## Introduction

This document specifies the requirements for the Redirects Module, 404 Monitor, and Google Search Console (GSC) API Integration feature. These three operational modules handle traffic management and Google Search Console connectivity for the MeowSEO plugin.

### Context and Architectural Justification

**Redirect Manager Performance Analysis:**
After analyzing Yoast SEO Premium's redirect implementation (reference-plugins/yoast-seo-premium/classes/redirect/redirect-option.php), we identified that their approach loads ALL redirect rules from WordPress options into PHP memory on every request via `get_option()` and `get_all()`. This becomes a performance bottleneck at scale when sites have hundreds or thousands of redirect rules, as:
1. The entire redirect array must be unserialized from the database
2. All redirects are loaded into PHP memory regardless of whether they match
3. Regex matching requires iterating through all regex rules in PHP

Our solution uses database-level matching with indexed queries, ensuring O(log n) performance for exact matches and only loading regex rules when necessary.

**404 Monitor Performance Analysis:**
After analyzing Rank Math's 404 monitor implementation (reference-plugins/seo-by-rank-math/includes/modules/404-monitor/class-db.php), we identified that their `capture_404()` method calls `DB::add()` or `DB::update()` synchronously on every 404 request. This means:
1. Every 404 hit triggers an immediate database write operation
2. The user's request is blocked until the database write completes
3. High-traffic sites with many 404s experience database write contention

Our solution uses Object Cache buffering with per-minute buckets and WP-Cron batch processing, ensuring 404 hits are logged asynchronously without blocking user requests.

**GSC Integration Analysis:**
While we could not locate a complete Search Console implementation in the reference plugins, standard OAuth 2.0 patterns require secure token storage and rate-limited API calls. Our solution implements encrypted credential storage and queue-based API processing with exponential backoff for rate limits.

## Glossary

- **Redirect_Manager**: The module responsible for URL redirect matching and execution
- **404_Monitor**: The module responsible for detecting and logging 404 errors
- **GSC_Module**: The module responsible for Google Search Console API integration
- **Redirect_Rule**: A database record mapping a source URL to a target URL with a redirect type
- **Exact_Match**: A redirect rule where the source URL must match the request URL exactly
- **Regex_Rule**: A redirect rule where the source URL is a regular expression pattern
- **Object_Cache**: WordPress transient cache system for temporary data storage
- **Bucket**: A per-minute cache key containing buffered 404 hits
- **Queue_Entry**: A database record representing a pending Google API call
- **OAuth_Token**: An encrypted access token for Google API authentication
- **Exponential_Backoff**: A retry strategy where wait time doubles after each failure

## Requirements

### Requirement 1: Redirect Matching Performance

**User Story:** As a site administrator, I want redirect matching to be fast even with thousands of redirect rules, so that my site performance is not degraded.

#### Acceptance Criteria

1. WHEN a request is received, THE Redirect_Manager SHALL query the database for an exact match using the indexed source_url column
2. THE Redirect_Manager SHALL execute the exact match query with LIMIT 1 and include is_active = 1 and is_regex = 0 in the WHERE clause
3. IF no exact match is found AND the has_regex_rules flag is true, THEN THE Redirect_Manager SHALL load only regex rules from the database
4. IF the has_regex_rules flag is false, THEN THE Redirect_Manager SHALL skip regex matching entirely
5. THE Redirect_Manager SHALL cache regex rules in Object_Cache with a 5 minute TTL
6. THE Redirect_Manager SHALL never load all redirect rules into PHP memory simultaneously

### Requirement 2: Redirect Execution

**User Story:** As a site administrator, I want redirects to execute correctly with the appropriate HTTP status codes, so that search engines and browsers handle them properly.

#### Acceptance Criteria

1. WHEN a redirect rule matches, THE Redirect_Manager SHALL issue an HTTP redirect using wp_redirect() with the redirect_type status code
2. THE Redirect_Manager SHALL support redirect types 301, 302, 307, 410, and 451
3. WHEN the redirect type is 410 or 451, THE Redirect_Manager SHALL output the status code without a Location header
4. WHEN the redirect type is 301, 302, or 307, THE Redirect_Manager SHALL include the target URL in the Location header
5. THE Redirect_Manager SHALL call exit after issuing the redirect

### Requirement 3: Redirect Hit Tracking

**User Story:** As a site administrator, I want to track how many times each redirect is used, so that I can identify popular redirects and clean up unused ones.

#### Acceptance Criteria

1. WHEN a redirect is executed, THE Redirect_Manager SHALL increment the hit_count column for that redirect rule
2. WHEN a redirect is executed, THE Redirect_Manager SHALL update the last_hit column with the current timestamp
3. THE Redirect_Manager SHALL perform hit tracking asynchronously using the shutdown hook
4. THE Redirect_Manager SHALL not block the redirect execution to wait for hit tracking to complete

### Requirement 4: Automatic Slug Change Redirects

**User Story:** As a content editor, I want automatic redirects created when I change a post slug, so that old links continue to work without manual intervention.

#### Acceptance Criteria

1. WHEN a published post's slug changes, THE Redirect_Manager SHALL create a 301 redirect from the old permalink to the new permalink
2. THE Redirect_Manager SHALL check whether a redirect for the old URL already exists before creating a new one
3. THE Redirect_Manager SHALL check whether the old URL is the target of another redirect to avoid creating redirect chains
4. THE Redirect_Manager SHALL only create automatic redirects for posts with status publish

### Requirement 5: Regex Redirect Pattern Matching

**User Story:** As a site administrator, I want to use regular expressions in redirect rules, so that I can redirect multiple URLs with a single pattern.

#### Acceptance Criteria

1. WHEN a regex rule matches the request URL, THE Redirect_Manager SHALL support backreferences in the target URL
2. THE Redirect_Manager SHALL replace $1, $2, etc. in the target URL with captured groups from the regex match
3. WHEN a regex pattern does not have delimiters, THE Redirect_Manager SHALL add # delimiters automatically
4. THE Redirect_Manager SHALL suppress warnings for invalid regex patterns using the @ operator

### Requirement 6: Redirect Loop Detection

**User Story:** As a site administrator, I want redirect loops to be detected and prevented, so that my site does not become inaccessible.

#### Acceptance Criteria

1. THE Redirect_Manager SHALL maintain a redirect chain array tracking visited URLs in the current request
2. WHEN a redirect target URL is already in the redirect chain, THE Redirect_Manager SHALL log a warning and stop processing
3. THE Redirect_Manager SHALL not execute a redirect that would create a loop
4. THE Redirect_Manager SHALL include the source URL, target URL, and chain in the loop detection warning log

### Requirement 7: 404 Detection and Buffering

**User Story:** As a site administrator, I want 404 errors to be logged without slowing down my site, so that I can identify broken links without performance impact.

#### Acceptance Criteria

1. WHEN a request results in a 404 response, THE 404_Monitor SHALL buffer the URL in Object_Cache
2. THE 404_Monitor SHALL skip requests with an empty User-Agent header
3. THE 404_Monitor SHALL skip requests where the URL path has a file extension indicating a static asset (jpg, jpeg, png, gif, css, js, ico, woff, woff2, svg, pdf)
4. THE 404_Monitor SHALL skip URLs on the ignore list stored in plugin options
5. THE 404_Monitor SHALL use per-minute bucket keys in the format 404_YYYYMMDD_HHmm
6. THE 404_Monitor SHALL store each bucket in Object_Cache with a 120 second TTL

### Requirement 8: 404 Batch Processing

**User Story:** As a site administrator, I want 404 hits to be written to the database in batches, so that database write operations are minimized.

#### Acceptance Criteria

1. THE 404_Monitor SHALL schedule a WP-Cron event to run every 60 seconds
2. WHEN the cron event runs, THE 404_Monitor SHALL retrieve buckets for the previous 1 and 2 minutes
3. THE 404_Monitor SHALL aggregate all collected URLs by counting occurrences of each unique URL
4. THE 404_Monitor SHALL perform a single database upsert for each unique URL using INSERT ... ON DUPLICATE KEY UPDATE
5. THE 404_Monitor SHALL increment hit_count and update last_seen on existing rows
6. THE 404_Monitor SHALL delete each bucket from Object_Cache after processing

### Requirement 9: GSC OAuth Authentication

**User Story:** As a site administrator, I want to authenticate with Google Search Console securely, so that my API credentials are protected.

#### Acceptance Criteria

1. THE GSC_Module SHALL store Client ID and Client Secret in plugin options
2. THE GSC_Module SHALL encrypt Access Token, Refresh Token, and Token Expiry using openssl_encrypt with the WordPress AUTH_KEY constant
3. WHEN the access token is expired, THE GSC_Module SHALL use the refresh token to request a new access token
4. WHEN the refresh request fails, THE GSC_Module SHALL set the meowseo_gsc_auth_status option to revoked
5. THE GSC_Module SHALL provide a get_auth_url() method that generates the Google OAuth consent URL
6. THE GSC_Module SHALL handle the OAuth callback at admin.php?page=meowseo-gsc&action=oauth_callback

### Requirement 10: GSC API Queue Processing

**User Story:** As a site administrator, I want Google API calls to be rate-limited and retried automatically, so that my site does not exceed API quotas.

#### Acceptance Criteria

1. THE GSC_Module SHALL enqueue API requests in the meowseo_gsc_queue database table
2. THE GSC_Module SHALL check whether an identical pending job exists before inserting to avoid duplicates
3. THE GSC_Module SHALL process up to 10 queue entries per batch
4. THE GSC_Module SHALL update job status to processing before making any API call
5. WHEN an HTTP 429 rate limit response is received, THE GSC_Module SHALL update the job status to pending and set retry_after to current time plus 60 seconds multiplied by 2 to the power of the attempts count
6. WHEN a successful API response is received, THE GSC_Module SHALL update the job status to done and store the response data

### Requirement 11: GSC Automatic Indexing Requests

**User Story:** As a content editor, I want new and updated posts to be submitted to Google for indexing automatically, so that my content appears in search results quickly.

#### Acceptance Criteria

1. WHEN a post transitions to publish from any other status, THE GSC_Module SHALL enqueue an indexing job for that post's permalink
2. WHEN a published post is updated, THE GSC_Module SHALL compare the current time to the _meowseo_gsc_last_submit postmeta value
3. WHEN a published post has been modified since the last submission, THE GSC_Module SHALL enqueue a new indexing job and update the postmeta timestamp
4. THE GSC_Module SHALL only process post types that are public and indexable

### Requirement 12: Redirect CSV Import and Export

**User Story:** As a site administrator, I want to import and export redirect rules via CSV, so that I can manage redirects in bulk.

#### Acceptance Criteria

1. THE Redirect_Manager SHALL provide a CSV import endpoint that accepts files with columns source_url, target_url, redirect_type, and is_regex
2. THE Redirect_Manager SHALL validate that each CSV row has at least source_url and target_url columns
3. THE Redirect_Manager SHALL skip empty rows and rows with missing required fields
4. THE Redirect_Manager SHALL default redirect_type to 301 if not provided or invalid
5. THE Redirect_Manager SHALL provide a CSV export endpoint that returns all redirect rules in CSV format
6. THE Redirect_Manager SHALL log the import result including imported count, skipped count, and errors

### Requirement 13: 404 Monitor Admin Actions

**User Story:** As a site administrator, I want to create redirects directly from logged 404 URLs, so that I can fix broken links quickly.

#### Acceptance Criteria

1. THE 404_Monitor SHALL provide a Create Redirect action for each logged 404 URL
2. WHEN the Create Redirect action is used, THE 404_Monitor SHALL open an inline form with target URL and redirect type fields
3. WHEN the redirect is created, THE 404_Monitor SHALL remove the URL from the 404 log
4. THE 404_Monitor SHALL provide an Ignore action that adds the URL to the ignore list in plugin options
5. THE 404_Monitor SHALL provide a Clear All button that deletes all rows from the 404 log table with a JavaScript confirmation dialog

### Requirement 14: GSC URL Inspection API

**User Story:** As a site administrator, I want to inspect URLs in Google Search Console, so that I can see their indexing status and coverage state.

#### Acceptance Criteria

1. THE GSC_Module SHALL provide an inspect_url() method that calls the URL Inspection API endpoint
2. THE GSC_Module SHALL return the indexing status, coverage state, crawled date, and any issues found for the given URL
3. THE GSC_Module SHALL use wp_remote_request() with the Authorization Bearer header set to the access token
4. WHEN get_valid_token() returns false, THE GSC_Module SHALL return an error array without making any HTTP request

### Requirement 15: GSC Search Analytics API

**User Story:** As a site administrator, I want to retrieve search analytics data from Google Search Console, so that I can see clicks, impressions, CTR, and position for my URLs.

#### Acceptance Criteria

1. THE GSC_Module SHALL provide a get_search_analytics() method that calls the Search Analytics query endpoint
2. THE GSC_Module SHALL accept parameters for site_url, start_date, end_date, dimensions, and data_state
3. WHEN the data_state parameter is set to all, THE GSC_Module SHALL include data from Google Discover in addition to web search
4. THE GSC_Module SHALL return a consistent array shape with keys for success, data, and http_code
5. THE GSC_Module SHALL store search analytics data in the meowseo_gsc_data table

### Requirement 16: Redirect REST API Endpoints

**User Story:** As a developer, I want REST API endpoints for redirect management, so that I can integrate redirects with external tools.

#### Acceptance Criteria

1. THE Redirect_Manager SHALL register a POST /meowseo/v1/redirects endpoint that creates a new redirect rule
2. THE Redirect_Manager SHALL register a PUT /meowseo/v1/redirects/{id} endpoint that updates an existing redirect rule
3. THE Redirect_Manager SHALL register a DELETE /meowseo/v1/redirects/{id} endpoint that deletes a redirect rule
4. THE Redirect_Manager SHALL register a POST /meowseo/v1/redirects/import endpoint that bulk imports from CSV data
5. THE Redirect_Manager SHALL register a GET /meowseo/v1/redirects/export endpoint that returns all rules for CSV download
6. THE Redirect_Manager SHALL verify a nonce and check current_user_can manage_options before performing any operation

### Requirement 17: 404 Monitor REST API Endpoints

**User Story:** As a developer, I want REST API endpoints for 404 log access, so that I can integrate 404 monitoring with external tools.

#### Acceptance Criteria

1. THE 404_Monitor SHALL register a GET /meowseo/v1/404-log endpoint that returns paginated 404 log entries
2. THE 404_Monitor SHALL support pagination with page and per_page query parameters
3. THE 404_Monitor SHALL support sorting with orderby and order query parameters
4. THE 404_Monitor SHALL register a DELETE /meowseo/v1/404-log/{id} endpoint that deletes a 404 log entry
5. THE 404_Monitor SHALL verify a nonce and check current_user_can manage_options before performing any operation

### Requirement 18: GSC REST API Endpoints

**User Story:** As a developer, I want REST API endpoints for GSC data access, so that I can display search performance in custom interfaces.

#### Acceptance Criteria

1. THE GSC_Module SHALL register a GET /meowseo/v1/gsc endpoint that returns GSC performance data
2. THE GSC_Module SHALL support filtering by URL, start date, and end date query parameters
3. THE GSC_Module SHALL register a POST /meowseo/v1/gsc/auth endpoint that saves OAuth credentials
4. THE GSC_Module SHALL register a DELETE /meowseo/v1/gsc/auth endpoint that removes OAuth credentials
5. THE GSC_Module SHALL register a GET /meowseo/v1/gsc/status endpoint that returns connection status
6. THE GSC_Module SHALL verify a nonce and check current_user_can manage_options before performing any mutation operation
