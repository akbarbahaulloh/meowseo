# Design Document: Redirects Module, 404 Monitor, and GSC API Integration

## Overview

This design document specifies the technical architecture for three operational modules that handle traffic management and Google Search Console connectivity for the MeowSEO plugin.

### Design Goals

- **Performance**: O(log n) redirect matching with indexed database queries; asynchronous 404 logging with Object Cache buffering
- **Scalability**: Handle thousands of redirect rules and high-traffic 404 patterns without performance degradation
- **Reliability**: Queue-based GSC API processing with exponential backoff and rate limiting
- **Security**: Encrypted OAuth token storage, nonce verification, and capability checks on all endpoints
- **Maintainability**: Clean separation of concerns with Module_Interface pattern and REST API abstraction

## Architecture Overview

### High-Level Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     WordPress Request                           │
└────────────────────────────┬────────────────────────────────────┘
                             │
                    ┌────────▼────────┐
                    │ template_redirect│
                    │   (priority 1)   │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
   ┌────▼─────┐      ┌──────▼──────┐      ┌─────▼──────┐
   │ Redirects│      │ 404 Monitor │      │ GSC Module │
   │  Module  │      │   Module    │      │   Module   │
   └────┬─────┘      └──────┬──────┘      └─────┬──────┘
        │                   │                    │
   ┌────▼──────────┐   ┌────▼──────────┐   ┌────▼──────────┐
   │ Exact Match   │   │ Buffer to     │   │ Enqueue Job  │
   │ Query (O(1))  │   │ Object Cache  │   │ in Queue     │
   └────┬──────────┘   └────┬──────────┘   └────┬──────────┘
        │                   │                    │
   ┌────▼──────────┐   ┌────▼──────────┐   ┌────▼──────────┐
   │ Regex Fallback│   │ WP-Cron Flush │   │ WP-Cron Batch │
   │ (if needed)   │   │ (every 60s)   │   │ (every 5min)  │
   └────┬──────────┘   └────┬──────────┘   └────┬──────────┘
        │                   │                    │
   ┌────▼──────────┐   ┌────▼──────────┐   ┌────▼──────────┐
   │ wp_redirect() │   │ Batch Upsert  │   │ Google APIs  │
   │ + shutdown    │   │ to Database   │   │ (with retry) │
   │ hit tracking  │   │               │   │              │
   └───────────────┘   └───────────────┘   └──────────────┘
```

### Data Flow: Redirect Matching

```
Request: /old-page/
    │
    ├─ Step 1: Exact Match Query
    │  SELECT * FROM meowseo_redirects
    │  WHERE source_url = '/old-page/'
    │  AND is_active = 1 AND is_regex = 0
    │  LIMIT 1
    │
    ├─ Match Found? YES
    │  └─ Execute redirect with status code
    │     └─ Hook shutdown: increment hit_count
    │
    └─ Match Not Found?
       ├─ Step 2: Check Regex Rules Count
       │  SELECT COUNT(*) FROM meowseo_redirects
       │  WHERE is_active = 1 AND is_regex = 1
       │
       ├─ Count > 0?
       │  ├─ Load from Object Cache (5min TTL)
       │  │  OR query database if cache miss
       │  │
       │  └─ Loop through regex patterns
       │     ├─ preg_match() against /old-page/
       │     └─ Match Found?
       │        └─ Execute redirect
       │
       └─ No Match
          └─ Continue normal WordPress flow
```

### Data Flow: 404 Buffering and Batch Processing

```
404 Request: /missing-page/
    │
    ├─ Capture Method (template_redirect)
    │  ├─ is_404()? YES
    │  ├─ User-Agent empty? NO
    │  ├─ Static asset? NO
    │  ├─ On ignore list? NO
    │  └─ Buffer to Object Cache
    │     └─ Key: 404_20240101_1430 (per-minute bucket)
    │        Value: ['/missing-page/', '/another-404/', ...]
    │        TTL: 120 seconds
    │
    └─ WP-Cron Flush (every 60 seconds)
       ├─ Retrieve buckets for -1 and -2 minutes
       ├─ Aggregate URLs by counting occurrences
       ├─ For each unique URL:
       │  └─ INSERT ... ON DUPLICATE KEY UPDATE
       │     meowseo_404_log (url, hit_count, last_seen)
       └─ Delete processed buckets from Object Cache
```

### Data Flow: GSC Queue Processing

```
Post Published
    │
    ├─ transition_post_status hook
    │  ├─ Status: draft → publish
    │  └─ Enqueue indexing job
    │     └─ INSERT INTO meowseo_gsc_queue
    │        (job_type='indexing', payload={url}, status='pending')
    │
    └─ WP-Cron Process Batch (every 5 minutes)
       ├─ SELECT * FROM meowseo_gsc_queue
       │  WHERE status='pending' AND retry_after < NOW()
       │  LIMIT 10
       │
       ├─ For each job:
       │  ├─ UPDATE status='processing'
       │  ├─ Call Google API
       │  │
       │  ├─ Response: 200 OK
       │  │  └─ UPDATE status='done', response_data=...
       │  │
       │  ├─ Response: 429 Rate Limit
       │  │  └─ UPDATE status='pending'
       │  │     attempts++
       │  │     retry_after = NOW() + (60 * 2^attempts)
       │  │
       │  └─ Response: 4xx/5xx Error
       │     └─ UPDATE status='failed', error_response=...
       │
       └─ If pending jobs remain:
          └─ Schedule next batch in 60 seconds
```

## Component Design

### Part A: Redirect Manager

#### Class: Redirects_Module

**Implements**: Module_Interface

**Responsibility**: Module entry point, coordinates redirect matching and admin interface

**Properties**:
```php
private Redirects_Admin $admin;
private Redirects_REST $rest;
private Options $options;
```

**Methods**:
```php
public function boot(): void
public function get_id(): string
private function register_hooks(): void
private function handle_redirect(): void
private function handle_post_updated(int $post_id, WP_Post $post, WP_Post $old_post): void
private function record_hit_async(int $redirect_id): void
```

**Hooks**:
- `template_redirect` (priority 1): Redirect matching
- `post_updated` (priority 10): Auto-redirect on slug change
- `shutdown` (priority 999): Asynchronous hit tracking

---

#### Class: Redirects_Admin

**Responsibility**: Admin interface for managing redirect rules

**Properties**:
```php
private Options $options;
private Redirects_REST $rest;
private const REDIRECTS_PER_PAGE = 50;
```

**Methods**:
```php
public function register_menu(): void
public function render_page(): void
private function render_table(): void
private function render_form(): void
private function render_csv_import(): void
private function render_csv_export(): void
```

**Admin Page Structure**:
```
┌─────────────────────────────────────────┐
│ MeowSEO > Redirects                     │
├─────────────────────────────────────────┤
│ [Add New Redirect Form]                 │
│ Source URL: [_____________]             │
│ Target URL: [_____________]             │
│ Type: [301 ▼] [✓ Regex Mode]            │
│ [Create Redirect]                       │
├─────────────────────────────────────────┤
│ [CSV Import] [CSV Export]               │
├─────────────────────────────────────────┤
│ Search: [_____________] [Search]        │
├─────────────────────────────────────────┤
│ Source URL | Target | Type | Hits | ... │
│ /old-page/ | /new/  | 301  | 42   | ... │
│ ^/blog/... | /arch/ | 301  | 128  | ... │
├─────────────────────────────────────────┤
│ « 1 2 3 4 5 »                           │
└─────────────────────────────────────────┘
```

---

#### Class: Redirects_REST

**Responsibility**: REST API endpoints for redirect management

**Endpoints**:
```
POST   /meowseo/v1/redirects
PUT    /meowseo/v1/redirects/{id}
DELETE /meowseo/v1/redirects/{id}
POST   /meowseo/v1/redirects/import
GET    /meowseo/v1/redirects/export
```

**Methods**:
```php
public function register_routes(): void
public function create_redirect(WP_REST_Request $request): WP_REST_Response
public function update_redirect(WP_REST_Request $request): WP_REST_Response
public function delete_redirect(WP_REST_Request $request): WP_REST_Response
public function import_redirects(WP_REST_Request $request): WP_REST_Response
public function export_redirects(WP_REST_Request $request): WP_REST_Response
private function validate_redirect_data(array $data): array
private function check_redirect_chain(string $target_url): bool
```

**Request/Response Examples**:

POST /meowseo/v1/redirects
```json
{
  "source_url": "/old-page/",
  "target_url": "/new-page/",
  "redirect_type": 301,
  "is_regex": false
}
```

Response (201 Created):
```json
{
  "id": 42,
  "source_url": "/old-page/",
  "target_url": "/new-page/",
  "redirect_type": 301,
  "is_regex": false,
  "is_active": true,
  "hit_count": 0,
  "last_hit": null,
  "created_at": "2024-01-01T12:00:00Z"
}
```

---

### Part B: 404 Monitor

#### Class: Monitor_404_Module

**Implements**: Module_Interface

**Responsibility**: Module entry point, coordinates 404 capture and batch processing

**Properties**:
```php
private Monitor_404_Admin $admin;
private Monitor_404_REST $rest;
private Options $options;
private const ASSET_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'css', 'js', 'ico', 'woff', 'woff2', 'svg', 'pdf'];
```

**Methods**:
```php
public function boot(): void
public function get_id(): string
private function register_hooks(): void
private function capture_404(): void
private function buffer_404(string $url): void
private function schedule_flush(): void
public function flush_buffer(): void
```

**Hooks**:
- `template_redirect` (priority 999): Capture 404 requests
- `wp_scheduled_delete` or custom cron: Flush buffer every 60 seconds

---

#### Class: Monitor_404_Admin

**Responsibility**: Admin interface for 404 log management

**Properties**:
```php
private Options $options;
private Monitor_404_REST $rest;
private const LOG_ENTRIES_PER_PAGE = 50;
```

**Methods**:
```php
public function register_menu(): void
public function render_page(): void
private function render_table(): void
private function render_bulk_actions(): void
private function render_clear_all_button(): void
```

**Admin Page Structure**:
```
┌─────────────────────────────────────────┐
│ MeowSEO > 404 Monitor                   │
├─────────────────────────────────────────┤
│ [Bulk Actions ▼] [Apply]                │
│ [Clear All] (with confirmation)         │
├─────────────────────────────────────────┤
│ URL | Hits | First Seen | Last Seen | ...│
│ /missing/ | 42 | 2024-01-01 | 2024-01-05│
│ /old-post/ | 128 | 2024-01-02 | 2024-01-05│
├─────────────────────────────────────────┤
│ « 1 2 3 4 5 »                           │
└─────────────────────────────────────────┘
```

---

#### Class: Monitor_404_REST

**Responsibility**: REST API endpoints for 404 log access

**Endpoints**:
```
GET    /meowseo/v1/404-log
DELETE /meowseo/v1/404-log/{id}
POST   /meowseo/v1/404-log/ignore
POST   /meowseo/v1/404-log/clear-all
```

**Methods**:
```php
public function register_routes(): void
public function get_log(WP_REST_Request $request): WP_REST_Response
public function delete_entry(WP_REST_Request $request): WP_REST_Response
public function ignore_url(WP_REST_Request $request): WP_REST_Response
public function clear_all(WP_REST_Request $request): WP_REST_Response
```

---

### Part C: GSC Integration

#### Class: GSC_Module

**Implements**: Module_Interface

**Responsibility**: Module entry point, coordinates GSC authentication and queue processing

**Properties**:
```php
private GSC_Auth $auth;
private GSC_Queue $queue;
private GSC_API $api;
private GSC_REST $rest;
private Options $options;
```

**Methods**:
```php
public function boot(): void
public function get_id(): string
private function register_hooks(): void
private function register_cron(): void
private function handle_post_transition(string $new_status, string $old_status, WP_Post $post): void
public function process_queue(): void
```

**Hooks**:
- `transition_post_status`: Enqueue indexing jobs
- `wp_scheduled_delete` or custom cron: Process queue every 5 minutes

---

#### Class: GSC_Auth

**Responsibility**: OAuth 2.0 authentication and token management

**Properties**:
```php
private Options $options;
private const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
private const SCOPES = [
    'https://www.googleapis.com/auth/webmasters',
    'https://www.googleapis.com/auth/indexing'
];
```

**Methods**:
```php
public function get_auth_url(string $redirect_uri): string
public function handle_callback(string $code): bool
public function get_valid_token(): ?string
private function refresh_token(): bool
private function encrypt_token(string $token): string
private function decrypt_token(string $encrypted): string
private function store_credentials(array $credentials): void
```

**Token Storage**:
```php
// Encrypted in options table
meowseo_gsc_client_id: string
meowseo_gsc_client_secret: string
meowseo_gsc_access_token: string (encrypted)
meowseo_gsc_refresh_token: string (encrypted)
meowseo_gsc_token_expiry: int (unix timestamp)
meowseo_gsc_auth_status: string ('authenticated', 'revoked', 'pending')
```

---

#### Class: GSC_Queue

**Responsibility**: Manages queue of pending Google API requests

**Properties**:
```php
private Options $options;
private GSC_API $api;
private const MAX_BATCH_SIZE = 10;
private const RETRY_MULTIPLIER = 2;
private const BASE_RETRY_DELAY = 60;
```

**Methods**:
```php
public function enqueue(string $url, string $job_type): bool
public function process_batch(): void
private function check_duplicate(string $url, string $job_type): bool
private function calculate_retry_delay(int $attempts): int
private function schedule_next_batch(): void
```

**Queue Entry Structure**:
```php
[
    'id' => 1,
    'job_type' => 'indexing', // 'indexing', 'inspection', 'analytics'
    'payload' => json_encode(['url' => 'https://example.com/post/']),
    'status' => 'pending', // 'pending', 'processing', 'done', 'failed'
    'attempts' => 0,
    'retry_after' => null,
    'created_at' => '2024-01-01 12:00:00',
    'processed_at' => null
]
```

---

#### Class: GSC_API

**Responsibility**: Thin wrapper around Google Search Console and Indexing APIs

**Properties**:
```php
private GSC_Auth $auth;
private const INSPECTION_API_URL = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';
private const INDEXING_API_URL = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
private const ANALYTICS_API_URL = 'https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/searchAnalytics/query';
```

**Methods**:
```php
public function inspect_url(string $url): array
public function submit_for_indexing(string $url): array
public function get_search_analytics(string $site_url, string $start_date, string $end_date, array $dimensions, string $data_state = 'final'): array
private function make_request(string $method, string $url, array $body = []): array
private function handle_response(array $response): array
```

**Response Format**:
```php
[
    'success' => true,
    'data' => [...],
    'http_code' => 200
]
```

**Exception Classes**:
```php
class GSC_Rate_Limit_Exception extends Exception {}
class GSC_Auth_Exception extends Exception {}
class GSC_API_Exception extends Exception {}
```

---

#### Class: GSC_REST

**Responsibility**: REST API endpoints for GSC data access

**Endpoints**:
```
GET    /meowseo/v1/gsc/status
POST   /meowseo/v1/gsc/auth
DELETE /meowseo/v1/gsc/auth
GET    /meowseo/v1/gsc/data
```

**Methods**:
```php
public function register_routes(): void
public function get_status(WP_REST_Request $request): WP_REST_Response
public function save_auth(WP_REST_Request $request): WP_REST_Response
public function remove_auth(WP_REST_Request $request): WP_REST_Response
public function get_data(WP_REST_Request $request): WP_REST_Response
```

---

## Database Schema

### meowseo_redirects Table

```sql
CREATE TABLE wp_meowseo_redirects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_url VARCHAR(2048) NOT NULL,
    target_url VARCHAR(2048) NOT NULL,
    redirect_type SMALLINT NOT NULL DEFAULT 301,
    is_regex TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    hit_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_hit DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_source_url (source_url(191)),
    KEY idx_is_regex_active (is_regex, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Indexes**:
- `idx_source_url`: Fast exact match queries (O(log n))
- `idx_is_regex_active`: Fast regex rule count queries

---

### meowseo_404_log Table

```sql
CREATE TABLE wp_meowseo_404_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    url VARCHAR(2048) NOT NULL,
    url_hash CHAR(64) NOT NULL,
    hit_count BIGINT UNSIGNED NOT NULL DEFAULT 1,
    first_seen DATE NOT NULL,
    last_seen DATE NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_url_hash_date (url_hash(64), first_seen),
    KEY idx_last_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Indexes**:
- `idx_url_hash_date`: Unique constraint for upsert operations
- `idx_last_seen`: Fast sorting by date

---

### meowseo_gsc_queue Table

```sql
CREATE TABLE wp_meowseo_gsc_queue (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_type VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts TINYINT NOT NULL DEFAULT 0,
    retry_after DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_status_retry (status, retry_after)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Indexes**:
- `idx_status_retry`: Fast query for pending jobs ready to process

---

## Performance Optimizations

### Redirect Matching: O(log n) Performance

**Exact Match Query**:
```sql
SELECT * FROM wp_meowseo_redirects
WHERE source_url = %s
AND is_active = 1
AND is_regex = 0
LIMIT 1
```

**Index**: `idx_source_url` on `source_url` column
- B-tree index provides O(log n) lookup
- LIMIT 1 stops after first match
- No full table scan

**Regex Fallback**:
```sql
SELECT COUNT(*) FROM wp_meowseo_redirects
WHERE is_active = 1 AND is_regex = 1
```

**Index**: `idx_is_regex_active` on `(is_regex, is_active)` columns
- Composite index allows fast count
- Only loads regex rules if count > 0
- Cached in Object Cache for 5 minutes

---

### 404 Logging: Asynchronous Batch Processing

**Per-Minute Buffering**:
- Object Cache key: `404_20240101_1430` (YYYYMMDD_HHmm)
- TTL: 120 seconds
- No database writes on each request

**Batch Upsert**:
```sql
INSERT INTO wp_meowseo_404_log (url, url_hash, hit_count, first_seen, last_seen)
VALUES (%s, %s, %d, %s, %s)
ON DUPLICATE KEY UPDATE
    hit_count = hit_count + VALUES(hit_count),
    last_seen = VALUES(last_seen)
```

**Benefits**:
- Single query per unique URL
- No blocking on user requests
- Reduces database write contention

---

### GSC Queue: Exponential Backoff

**Retry Calculation**:
```php
retry_after = NOW() + (60 * 2^attempts)

Attempt 1: 60 seconds
Attempt 2: 120 seconds
Attempt 3: 240 seconds
Attempt 4: 480 seconds
Attempt 5: 960 seconds
```

**Benefits**:
- Respects rate limits
- Reduces API call volume
- Prevents thundering herd

---

## Security Considerations

### OAuth Token Encryption

**Storage**:
```php
$encrypted = openssl_encrypt(
    $token,
    'AES-256-CBC',
    AUTH_KEY,
    0,
    substr(AUTH_KEY, 0, 16)
);
update_option('meowseo_gsc_access_token', $encrypted);
```

**Retrieval**:
```php
$encrypted = get_option('meowseo_gsc_access_token');
$token = openssl_decrypt(
    $encrypted,
    'AES-256-CBC',
    AUTH_KEY,
    0,
    substr(AUTH_KEY, 0, 16)
);
```

---

### REST API Security

**All endpoints require**:
1. Nonce verification: `wp_verify_nonce($_REQUEST['_wpnonce'], 'meowseo_nonce')`
2. Capability check: `current_user_can('manage_options')`
3. HTTPS (enforced in production)

**Example**:
```php
public function create_redirect(WP_REST_Request $request): WP_REST_Response {
    if (!current_user_can('manage_options')) {
        return new WP_REST_Response(['error' => 'Unauthorized'], 403);
    }
    
    // Process request
}
```

---

## Integration Points

### WordPress Hooks

**Redirect Matching**:
- `template_redirect` (priority 1): Runs before WordPress processes the request

**404 Capture**:
- `template_redirect` (priority 999): Runs after all other handlers

**GSC Indexing**:
- `transition_post_status`: Triggered when post status changes

**Asynchronous Operations**:
- `shutdown`: Hit tracking for redirects
- `wp_scheduled_delete` or custom cron: 404 flush and GSC queue processing

---

### Module_Interface Implementation

All three modules implement the Module_Interface:

```php
interface Module {
    public function boot(): void;
    public function get_id(): string;
}
```

**Module Registration**:
```php
// In Module_Manager
$modules = [
    new Redirects_Module(),
    new Monitor_404_Module(),
    new GSC_Module(),
];

foreach ($modules as $module) {
    $module->boot();
}
```

---

## Correctness Properties

### Property 1: Redirect Matching Correctness

**Specification**: A request matching a redirect rule SHALL be redirected to the target URL with the correct HTTP status code.

**Implementation**:
1. Exact match query returns at most one rule (LIMIT 1)
2. Regex matching uses preg_match() with proper delimiters
3. HTTP status code comes from redirect_type column
4. wp_redirect() handles Location header correctly

---

### Property 2: 404 Logging Accuracy

**Specification**: Each unique 404 URL SHALL be logged exactly once per day with accurate hit count.

**Implementation**:
1. Per-minute buffering prevents duplicate writes
2. ON DUPLICATE KEY UPDATE increments hit_count
3. Unique key on (url_hash, first_seen) ensures one entry per day
4. Batch processing aggregates all hits in the minute

---

### Property 3: GSC Queue Reliability

**Specification**: Each queued job SHALL be processed exactly once and retried on rate limit with exponential backoff.

**Implementation**:
1. Duplicate check prevents duplicate jobs
2. Status transitions: pending → processing → done/failed
3. Exponential backoff formula: 60 * 2^attempts
4. Max attempts limit prevents infinite retries

---

## Testing Strategy

### Unit Tests

- Redirect matching logic (exact and regex)
- 404 buffering and aggregation
- GSC token encryption/decryption
- Exponential backoff calculation

### Integration Tests

- End-to-end redirect flow
- 404 capture and batch processing
- GSC OAuth flow
- REST API endpoints

### Performance Tests

- Redirect matching with 10,000 rules
- 404 logging under high traffic
- GSC queue processing with rate limits

---

## Deployment Considerations

### Database Migrations

- Create tables on plugin activation
- Add indexes for performance
- Handle existing data if upgrading

### Cron Scheduling

- Schedule 404 flush on module boot
- Schedule GSC queue processing on module boot
- Clear cron events on plugin deactivation

### Cache Invalidation

- Clear redirect cache on rule changes
- Clear 404 buckets after processing
- Clear GSC data cache on auth changes

