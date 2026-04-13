# GSC Module Implementation

## Task 12: Implement GSC Module with rate-limited API integration

### Subtask 12.1: Create GSC authentication and credential storage ✓

**Implementation:**
- OAuth 2.0 credential storage in `includes/class-options.php`
- Methods: `get_gsc_credentials()`, `set_gsc_credentials()`, `delete_gsc_credentials()`
- Encryption using AES-256-CBC with WordPress secret keys (AUTH_KEY + SECURE_AUTH_KEY)
- Credentials stored in `meowseo_gsc_credentials` option
- Never exposed via REST endpoints (only connection status boolean returned)

**Files:**
- `includes/class-options.php` - Added credential encryption/decryption methods
- `includes/modules/gsc/class-gsc-rest.php` - REST endpoints for auth management

**Requirements:** 10.1, 15.6

---

### Subtask 12.3: Create GSC queue processing system ✓

**Implementation:**
- Queue processing in `includes/modules/gsc/class-gsc.php`
- WP-Cron hook: `meowseo_process_gsc_queue` (runs hourly)
- Fetches maximum 10 queue entries per execution using `DB::get_gsc_queue(10)`
- Updates status to 'processing' before execution
- Handles HTTP responses:
  - **200**: Store data, mark as 'done'
  - **429**: Exponential backoff - `retry_after = NOW() + POW(2, attempts) * 60`
  - **5xx**: Retry after 5 minutes, mark as 'failed' after 5 attempts

**Files:**
- `includes/modules/gsc/class-gsc.php` - Main queue processor
- `includes/helpers/class-db.php` - DB methods (already implemented)

**Requirements:** 10.2, 10.3, 10.4

---

### Subtask 12.6: Create GSC data storage and REST API ✓

**Implementation:**
- Data storage using `DB::upsert_gsc_data()` method
- Stores: url, url_hash, date, clicks, impressions, ctr, position
- REST endpoints in `includes/modules/gsc/class-gsc-rest.php`:
  - `GET /meowseo/v1/gsc` - Get GSC data with `?url=` or `?start=&end=` params
  - `POST /meowseo/v1/gsc/auth` - Save OAuth credentials
  - `DELETE /meowseo/v1/gsc/auth` - Remove credentials
  - `GET /meowseo/v1/gsc/status` - Check connection status
- All endpoints require `manage_options` capability
- Cache-Control headers: `public, max-age=300` for GET requests

**Files:**
- `includes/modules/gsc/class-gsc-rest.php` - REST API implementation
- `includes/modules/gsc/class-gsc.php` - Data storage logic

**Requirements:** 10.5, 10.6, 10.7

---

## Architecture

### Module Structure

```
includes/modules/gsc/
├── class-gsc.php           # Main module (implements Module interface)
├── class-gsc-rest.php      # REST API endpoints
├── README.md               # Module documentation
└── IMPLEMENTATION.md       # This file
```

### Database Tables

Both tables are defined in `includes/class-installer.php` schema:

1. **meowseo_gsc_queue** - API call queue
2. **meowseo_gsc_data** - Performance data storage

### Key Classes

- `MeowSEO\Modules\GSC\GSC` - Main module class
- `MeowSEO\Modules\GSC\GSC_REST` - REST API handler
- `MeowSEO\Options` - Credential encryption/decryption
- `MeowSEO\Helpers\DB` - Database operations

---

## Security Implementation

### Credential Encryption (Requirement 15.6)

```php
// Encryption
$key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true);
$iv = openssl_random_pseudo_bytes(16);
$encrypted = openssl_encrypt($json, 'AES-256-CBC', $key, 0, $iv);
$stored = base64_encode($iv . $encrypted);

// Decryption
$raw = base64_decode($stored);
$iv = substr($raw, 0, 16);
$encrypted = substr($raw, 16);
$decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
```

### REST API Security

- All endpoints verify `manage_options` capability
- Mutation endpoints verify WordPress nonce via `X-WP-Nonce` header
- Status endpoint returns only boolean, never raw credentials
- All database queries use `$wpdb->prepare()` with parameterized placeholders

---

## Queue Processing Flow

```
1. Enqueue API Call
   ↓
2. WP-Cron: meowseo_process_gsc_queue (hourly)
   ↓
3. Fetch max 10 pending entries (DB::get_gsc_queue)
   ↓
4. Update status to 'processing'
   ↓
5. For each entry:
   ├─ Execute API call with OAuth token
   ├─ Handle response:
   │  ├─ 200: Store data → mark 'done'
   │  ├─ 429: Exponential backoff → update retry_after
   │  └─ 5xx: Retry or mark 'failed'
   └─ Continue to next entry
```

---

## Testing

### Unit Tests

Location: `tests/modules/gsc/GSCModuleTest.php`

Tests:
- Module ID verification
- Module interface implementation
- Boot method execution
- Method existence checks

### Manual Testing

1. **Credential Storage:**
   ```bash
   # Save credentials
   curl -X POST http://localhost/wp-json/meowseo/v1/gsc/auth \
     -H "X-WP-Nonce: {nonce}" \
     -H "Content-Type: application/json" \
     -d '{"access_token":"test","refresh_token":"test","expires_in":3600}'
   
   # Check status
   curl http://localhost/wp-json/meowseo/v1/gsc/status
   ```

2. **Queue Processing:**
   ```php
   // Enqueue a test job
   $gsc = $module_manager->get_module('gsc');
   $gsc->enqueue_api_call('fetch_url', [
       'site_url' => 'https://example.com',
       'url' => 'https://example.com/page'
   ]);
   
   // Trigger cron manually
   do_action('meowseo_process_gsc_queue');
   ```

3. **Data Retrieval:**
   ```bash
   # Get data by URL
   curl http://localhost/wp-json/meowseo/v1/gsc?url=https://example.com/page
   
   # Get data by date range
   curl http://localhost/wp-json/meowseo/v1/gsc?start=2024-01-01&end=2024-01-31
   ```

---

## Performance Characteristics

- **Queue Processing**: O(10) per cron execution (constant time)
- **Data Lookup**: O(log n) via indexed url_hash + date
- **Memory Usage**: Minimal - processes 10 entries at a time
- **API Rate Limiting**: Exponential backoff prevents violations
- **Cache TTL**: 5 minutes for GET endpoints

---

## Future Enhancements

1. **Automatic Token Refresh**
   - Use refresh_token to obtain new access_token
   - Schedule refresh before expiration

2. **Sitemap Submission**
   - Submit sitemaps via GSC API
   - Track submission status

3. **URL Inspection**
   - Integrate URL Inspection API
   - Check indexing status per URL

4. **Performance Alerts**
   - Monitor significant metric changes
   - Send notifications for drops in clicks/impressions

5. **Bulk Export**
   - Export GSC data to CSV
   - Generate performance reports

---

## Compliance

### Requirements Coverage

- ✓ 10.1: OAuth 2.0 authentication with encrypted storage
- ✓ 10.2: All API calls enqueued (no synchronous execution)
- ✓ 10.3: Maximum 10 queue entries per cron execution
- ✓ 10.4: Exponential backoff for HTTP 429
- ✓ 10.5: Performance data stored in meowseo_gsc_data
- ✓ 10.6: REST endpoints for data access
- ✓ 10.7: Data available for Gutenberg sidebar (via REST)
- ✓ 15.6: Credentials encrypted, never exposed via REST

### Security Requirements

- ✓ 15.1: All queries use `$wpdb->prepare()`
- ✓ 15.2: REST endpoints verify nonce
- ✓ 15.3: REST endpoints verify capabilities
- ✓ 15.6: Credentials encrypted with WordPress secret keys

---

## Completion Status

**Task 12: Implement GSC Module** - ✓ COMPLETE

All three subtasks implemented:
- 12.1: GSC authentication and credential storage ✓
- 12.3: GSC queue processing system ✓
- 12.6: GSC data storage and REST API ✓

Optional property-based tests (12.2, 12.4, 12.5) skipped as per task instructions.
