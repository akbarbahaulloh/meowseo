# MeowSEO Security Implementation

This document outlines the comprehensive security measures implemented in the MeowSEO plugin to meet WordPress security best practices and requirements 15.1-15.6.

## Table of Contents

1. [Input Validation and Sanitization](#input-validation-and-sanitization)
2. [Database Security](#database-security)
3. [Authentication and Authorization](#authentication-and-authorization)
4. [Output Escaping](#output-escaping)
5. [Credential Encryption](#credential-encryption)
6. [Security Checklist](#security-checklist)

---

## Input Validation and Sanitization

**Requirement 15.1, 15.2, 15.3**

### REST API Endpoints

All REST API endpoints implement comprehensive input validation and sanitization:

#### Nonce Verification Pattern

All mutation endpoints (POST, PUT, DELETE) verify WordPress nonces:

```php
private function verify_nonce( \WP_REST_Request $request ): bool {
    $nonce = $request->get_header( 'X-WP-Nonce' );
    
    if ( empty( $nonce ) ) {
        return false;
    }
    
    return wp_verify_nonce( $nonce, 'wp_rest' );
}
```

#### Capability Checks

All endpoints verify user capabilities before processing:

- **edit_post**: Required for updating post-specific SEO meta
- **manage_options**: Required for plugin settings, redirects, 404 log, GSC data

#### Input Sanitization

All user inputs are sanitized using WordPress functions:

| Input Type | Sanitization Function |
|-----------|----------------------|
| Text fields | `sanitize_text_field()` |
| Textarea | `sanitize_textarea_field()` |
| URLs | `esc_url_raw()` |
| HTML content | `wp_kses_post()` |
| Integers | `absint()` |
| Booleans | `rest_sanitize_boolean()` |

### Endpoints Security Matrix

| Endpoint | Method | Nonce | Capability | Sanitization |
|----------|--------|-------|------------|--------------|
| `/meta/{post_id}` | GET | ❌ | Public | ✅ |
| `/meta/{post_id}` | POST | ✅ | edit_post | ✅ |
| `/analysis/{post_id}` | POST | ✅ | edit_post | ✅ |
| `/settings` | GET | ❌ | manage_options | ✅ |
| `/settings` | POST | ✅ | manage_options | ✅ |
| `/redirects` | GET | ❌ | manage_options | ✅ |
| `/redirects` | POST | ✅ | manage_options | ✅ |
| `/redirects/{id}` | PUT | ✅ | manage_options | ✅ |
| `/redirects/{id}` | DELETE | ✅ | manage_options | ✅ |
| `/404-log` | GET | ❌ | manage_options | ✅ |
| `/404-log/{id}` | DELETE | ✅ | manage_options | ✅ |
| `/gsc` | GET | ❌ | manage_options | ✅ |
| `/gsc/auth` | POST | ✅ | manage_options | ✅ |
| `/gsc/auth` | DELETE | ✅ | manage_options | ✅ |
| `/internal-links` | GET | ❌ | edit_posts | ✅ |
| `/social/{post_id}` | GET | ❌ | Public | ✅ |
| `/social/{post_id}` | POST | ✅ | edit_post | ✅ |
| `/social/{post_id}` | DELETE | ✅ | edit_post | ✅ |
| `/schema/{post_id}` | GET | ❌ | Public | ✅ |

---

## Database Security

**Requirement 15.1**

### Prepared Statements

All database queries use `$wpdb->prepare()` with parameterized placeholders. The `DB` helper class wraps all database interactions:

```php
// Example: Exact redirect match
$query = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE source_url = %s AND status = 'active' LIMIT 1",
    $url
);
```

### Query Validation

Dynamic query components (ORDER BY, LIMIT) are validated against whitelists:

```php
// Example: 404 log orderby validation
$allowed_orderby = [ 'id', 'url', 'hit_count', 'first_seen', 'last_seen' ];
$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'last_seen';
```

### Bulk Operations

Bulk INSERT/UPDATE operations use prepared statements with dynamic placeholders:

```php
// Example: Bulk 404 upsert
$placeholders[] = '(%s, %s, %s, %s, %d, %s, %s)';
$query = "INSERT INTO {$table} (...) VALUES " . implode( ', ', $placeholders );
$prepared = $wpdb->prepare( $query, $values );
$wpdb->query( $prepared );
```

---

## Authentication and Authorization

**Requirement 15.2, 15.3**

### REST API Authentication

All REST endpoints use WordPress's built-in authentication:

1. **Cookie Authentication**: For logged-in users in the WordPress admin
2. **Nonce Verification**: X-WP-Nonce header checked on all mutations
3. **Capability Checks**: User capabilities verified before processing

### Permission Callbacks

Each endpoint defines explicit permission callbacks:

```php
// Example: Meta update permission
public function update_meta_permission( \WP_REST_Request $request ): bool {
    $post_id = (int) $request['post_id'];
    return current_user_can( 'edit_post', $post_id );
}
```

### Nonce + Capability Pattern

Mutation endpoints implement both nonce and capability checks:

```php
public function check_manage_options_and_nonce( WP_REST_Request $request ) {
    // Check capability first
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'rest_forbidden', __( 'Forbidden', 'meowseo' ), [ 'status' => 403 ] );
    }
    
    // Verify nonce
    $nonce = $request->get_header( 'X-WP-Nonce' );
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        return new WP_Error( 'rest_cookie_invalid_nonce', __( 'Invalid nonce', 'meowseo' ), [ 'status' => 403 ] );
    }
    
    return true;
}
```

---

## Output Escaping

**Requirement 15.4**

### HTML Output Escaping

All user-supplied values output to HTML are escaped using appropriate WordPress functions:

| Context | Escaping Function | Example |
|---------|------------------|---------|
| HTML content | `esc_html()` | `<title><?php echo esc_html( $title ); ?></title>` |
| HTML attributes | `esc_attr()` | `<meta content="<?php echo esc_attr( $desc ); ?>">` |
| URLs | `esc_url()` | `<link href="<?php echo esc_url( $url ); ?>">` |
| JSON in script tags | `wp_json_encode()` | `<script>var data = <?php echo wp_json_encode( $data ); ?>;</script>` |

### Module Output Security

#### Meta Module (class-meta.php)

```php
echo '<title>' . esc_html( $title ) . '</title>' . "\n";
echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
```

#### Social Module (class-social.php)

```php
echo '<meta property="og:title" content="' . esc_attr( $data['title'] ) . '">' . "\n";
echo '<meta property="og:image" content="' . esc_url( $data['image'] ) . '">' . "\n";
```

#### Schema Module (class-schema.php)

```php
// JSON-LD output uses wp_json_encode() which handles HTML entity encoding
$json = wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
echo '<script type="application/ld+json">' . "\n";
echo $json . "\n";
echo '</script>' . "\n";
```

---

## Credential Encryption

**Requirement 15.6**

### GSC OAuth Credentials

Google Search Console OAuth credentials are encrypted before storage using AES-256-CBC:

#### Encryption Process

```php
private function encrypt_credentials( string $data ) {
    // Derive encryption key from WordPress secret keys
    $key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true );
    
    // Generate random IV
    $iv = openssl_random_pseudo_bytes( 16 );
    
    // Encrypt data
    $encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
    
    // Return base64-encoded IV + encrypted data
    return base64_encode( $iv . $encrypted );
}
```

#### Decryption Process

```php
private function decrypt_credentials( string $encrypted_data ) {
    // Derive encryption key from WordPress secret keys
    $key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true );
    
    // Decode base64
    $raw = base64_decode( $encrypted_data, true );
    
    // Extract IV and encrypted data
    $iv = substr( $raw, 0, 16 );
    $encrypted = substr( $raw, 16 );
    
    // Decrypt data
    return openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
}
```

#### Security Features

1. **AES-256-CBC**: Industry-standard encryption algorithm
2. **Random IV**: Each encryption uses a unique initialization vector
3. **Key Derivation**: Encryption key derived from WordPress secret keys (AUTH_KEY + SECURE_AUTH_KEY)
4. **No Raw Exposure**: Raw credentials never returned via REST endpoints
5. **Status Only**: REST endpoints return only boolean connection status

#### REST API Protection

```php
// GET /meowseo/v1/gsc/status returns only:
{
    "connected": true  // Boolean only, no credentials
}

// Settings endpoint removes sensitive data:
unset( $settings['gsc_credentials'] );
```

---

## Security Checklist

### ✅ Requirement 15.1: Database Security

- [x] All queries use `$wpdb->prepare()` with parameterized placeholders
- [x] No raw SQL queries with user input
- [x] Dynamic query components validated against whitelists
- [x] Bulk operations use prepared statements
- [x] DB helper class wraps all database interactions

### ✅ Requirement 15.2: Nonce Verification

- [x] All REST mutation endpoints verify X-WP-Nonce header
- [x] Nonce checked using `wp_verify_nonce( $nonce, 'wp_rest' )`
- [x] Invalid nonces return 403 Forbidden
- [x] GET endpoints exempt from nonce checks (read-only)

### ✅ Requirement 15.3: Capability Checks

- [x] All REST endpoints verify user capabilities
- [x] `edit_post` capability for post-specific operations
- [x] `manage_options` capability for admin operations
- [x] `edit_posts` capability for internal links
- [x] Public endpoints check post visibility

### ✅ Requirement 15.4: Output Escaping

- [x] All HTML content escaped with `esc_html()`
- [x] All HTML attributes escaped with `esc_attr()`
- [x] All URLs escaped with `esc_url()`
- [x] JSON output uses `wp_json_encode()`
- [x] No raw user input in HTML output

### ✅ Requirement 15.5: Schema Changes

- [x] All schema changes use `dbDelta()`
- [x] No raw CREATE TABLE or ALTER TABLE statements
- [x] Installer class handles all schema operations

### ✅ Requirement 15.6: Credential Encryption

- [x] GSC credentials encrypted with AES-256-CBC
- [x] Random IV for each encryption
- [x] Key derived from WordPress secret keys
- [x] No raw credentials via REST endpoints
- [x] Status endpoint returns boolean only

---

## Testing Security

### Manual Testing

1. **Nonce Verification**: Test REST mutations without X-WP-Nonce header (should fail)
2. **Capability Checks**: Test endpoints as non-admin user (should fail)
3. **Input Sanitization**: Test with malicious input (should be sanitized)
4. **Output Escaping**: Inspect HTML source for proper escaping
5. **SQL Injection**: Test with SQL injection payloads (should be blocked)

### Automated Testing

Run security-focused tests:

```bash
# Test REST API security
php tests/security/test-rest-security.php

# Test database security
php tests/security/test-db-security.php

# Test output escaping
php tests/security/test-output-escaping.php
```

---

## Security Incident Response

If a security vulnerability is discovered:

1. **Report**: Email security@meowseo.com with details
2. **Assessment**: Security team assesses severity and impact
3. **Patch**: Develop and test security patch
4. **Release**: Release patch as priority update
5. **Disclosure**: Responsible disclosure after patch release

---

## References

- [WordPress Plugin Security Best Practices](https://developer.wordpress.org/plugins/security/)
- [WordPress Data Validation](https://developer.wordpress.org/apis/security/data-validation/)
- [WordPress Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/)
- [WordPress Escaping Data](https://developer.wordpress.org/apis/security/escaping/)
- [WordPress Nonces](https://developer.wordpress.org/apis/security/nonces/)
