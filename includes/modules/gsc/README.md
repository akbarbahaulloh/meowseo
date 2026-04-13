# GSC (Google Search Console) Module

## Overview

The GSC module integrates with Google Search Console API via a rate-limited queue processing system. All API calls are enqueued and processed asynchronously via WP-Cron to prevent rate limiting and ensure reliable data fetching.

## Architecture

### Components

1. **class-gsc.php** - Main module implementing the Module interface
   - Queue processing via WP-Cron hook `meowseo_process_gsc_queue`
   - OAuth credential management (encrypted storage)
   - API call execution with exponential backoff
   - Data storage in `meowseo_gsc_data` table

2. **class-gsc-rest.php** - REST API endpoints
   - `GET /meowseo/v1/gsc` - Retrieve GSC performance data
   - `POST /meowseo/v1/gsc/auth` - Save OAuth credentials
   - `DELETE /meowseo/v1/gsc/auth` - Remove OAuth credentials
   - `GET /meowseo/v1/gsc/status` - Check connection status

### Database Tables

#### meowseo_gsc_queue
Stores pending API calls for asynchronous processing.

```sql
CREATE TABLE {prefix}meowseo_gsc_queue (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_type     VARCHAR(50)     NOT NULL,
    payload      JSON            NOT NULL,
    status       VARCHAR(20)     NOT NULL DEFAULT 'pending',
    attempts     TINYINT         NOT NULL DEFAULT 0,
    retry_after  DATETIME        NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME        NULL,
    PRIMARY KEY (id),
    KEY idx_status_retry (status, retry_after)
);
```

#### meowseo_gsc_data
Stores fetched performance data from Google Search Console.

```sql
CREATE TABLE {prefix}meowseo_gsc_data (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    url         VARCHAR(2048)   NOT NULL,
    url_hash    CHAR(64)        NOT NULL,
    date        DATE            NOT NULL,
    clicks      INT UNSIGNED    NOT NULL DEFAULT 0,
    impressions INT UNSIGNED    NOT NULL DEFAULT 0,
    ctr         DECIMAL(5,4)    NOT NULL DEFAULT 0.0000,
    position    DECIMAL(6,2)    NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    UNIQUE KEY idx_url_hash_date (url_hash(64), date),
    KEY idx_date (date)
);
```

## Queue Processing Pipeline

### Flow

1. **Enqueue**: API calls are added to `meowseo_gsc_queue` with status='pending'
2. **Process**: WP-Cron hook `meowseo_process_gsc_queue` runs hourly
3. **Fetch**: Maximum 10 queue entries are fetched per execution
4. **Execute**: Each entry is processed with OAuth authentication
5. **Handle Response**:
   - **HTTP 200**: Store data in `meowseo_gsc_data`, mark as 'done'
   - **HTTP 429**: Apply exponential backoff, update `retry_after`
   - **HTTP 5xx**: Retry after 5 minutes, mark as 'failed' after 5 attempts

### Exponential Backoff

When rate limited (HTTP 429), the retry delay follows:
```
retry_after = NOW() + POW(2, attempts) * 60 seconds
```

Attempts 1-5 yield delays of: 2, 4, 8, 16, 32 minutes.

## Security

### Credential Encryption

OAuth credentials are encrypted using AES-256-CBC before storage:

```php
// Encryption key derived from WordPress secret keys
$key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true);

// Random IV generated for each encryption
$iv = openssl_random_pseudo_bytes(16);

// Encrypt and store
$encrypted = openssl_encrypt($json, 'AES-256-CBC', $key, 0, $iv);
$stored = base64_encode($iv . $encrypted);
update_option('meowseo_gsc_credentials', $stored);
```

### REST API Security

- All endpoints require `manage_options` capability
- Mutation endpoints verify WordPress nonce via `X-WP-Nonce` header
- Raw credentials are never exposed via REST endpoints
- Status endpoint returns only boolean `connected` flag

## REST API Usage

### Get GSC Data

```http
GET /wp-json/meowseo/v1/gsc?url=https://example.com/page
GET /wp-json/meowseo/v1/gsc?start=2024-01-01&end=2024-01-31
```

Response:
```json
{
  "data": [
    {
      "url": "https://example.com/page",
      "date": "2024-01-15",
      "clicks": 42,
      "impressions": 1250,
      "ctr": 0.0336,
      "position": 8.5
    }
  ],
  "filters": {
    "url": "https://example.com/page",
    "start": null,
    "end": null
  }
}
```

### Save OAuth Credentials

```http
POST /wp-json/meowseo/v1/gsc/auth
X-WP-Nonce: {nonce}
Content-Type: application/json

{
  "access_token": "ya29.a0...",
  "refresh_token": "1//0g...",
  "expires_in": 3600
}
```

### Check Connection Status

```http
GET /wp-json/meowseo/v1/gsc/status
```

Response:
```json
{
  "connected": true
}
```

## Requirements Mapping

- **10.1**: OAuth 2.0 authentication with encrypted credential storage
- **10.2**: All API calls enqueued instead of synchronous execution
- **10.3**: Maximum 10 queue entries processed per WP-Cron execution
- **10.4**: Exponential backoff for HTTP 429 responses
- **10.5**: Performance data stored in `meowseo_gsc_data` table
- **10.6**: REST endpoints for GSC data access
- **10.7**: Performance summary available for Gutenberg sidebar (via REST API)
- **15.6**: Credentials encrypted using WordPress secret keys, never exposed via REST

## Usage Example

### Enqueue an API Call

```php
$gsc_module = $module_manager->get_module('gsc');

if ($gsc_module) {
    $gsc_module->enqueue_api_call('fetch_url', [
        'site_url' => 'https://example.com',
        'url' => 'https://example.com/page',
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31'
    ]);
}
```

### Retrieve Data via REST

```javascript
const response = await fetch('/wp-json/meowseo/v1/gsc?url=' + encodeURIComponent(pageUrl), {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});

const { data } = await response.json();
```

## Performance Considerations

- Queue processing limited to 10 entries per cron execution to prevent timeout
- Exponential backoff prevents API rate limit violations
- Data cached with 5-minute TTL via `Cache-Control` headers
- URL hashing (SHA-256) enables efficient database lookups
- Unique constraint on `url_hash + date` prevents duplicate data storage

## Future Enhancements

- Automatic token refresh using refresh_token
- Sitemap submission via GSC API
- URL inspection API integration
- Performance alerts for significant metric changes
- Bulk data export functionality
