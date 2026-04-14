# Performance Optimizations

This document describes the comprehensive performance optimizations implemented in MeowSEO plugin to ensure minimal impact on page load time and server resources.

## Overview

The MeowSEO plugin is designed with performance as a core principle. All optimizations follow the requirements outlined in Requirement 14 (Performance and Caching).

## Caching Strategy

### Cache Key Conventions (Requirement 14.2)

All Object Cache keys use the `meowseo_` prefix and are stored in the `meowseo` cache group for isolation:

| Key Pattern | Content | TTL | Purpose |
|---|---|---|---|
| `meowseo_meta_{post_id}` | SEO meta array (title, description, robots, canonical) | 3600s (1 hour) | Eliminate DB queries for SEO meta |
| `meowseo_schema_{post_id}` | Generated JSON-LD string | 3600s (1 hour) | Avoid regenerating schema on each request |
| `meowseo_social_{post_id}` | Social meta data array | 3600s (1 hour) | Cache Open Graph and Twitter Card data |
| `meowseo_sitemap_path_{type}` | Filesystem path to sitemap file | 86400s (24 hours) | Serve sitemaps from filesystem |
| `meowseo_sitemap_lock_{type}` | Lock flag (value: 1) | 60s | Prevent cache stampede during generation |
| `meowseo_404_{YYYYMMDD_HHmm}` | Buffered 404 hits array | 120s | Batch 404 logging to avoid sync DB writes |

### Transient Fallback (Requirement 14.3)

The `Cache` helper class automatically falls back to WordPress transients when Object Cache is unavailable:

```php
if ( self::is_object_cache_available() ) {
    return wp_cache_get( self::PREFIX . $key, self::GROUP );
}
return get_transient( self::PREFIX . $key );
```

This ensures the plugin works correctly on all WordPress installations, even those without persistent object caching.

## Zero-Query Frontend (Requirement 14.1)

### Meta Module Optimization

The Meta module implements cache warming to eliminate all database queries on the frontend for cached posts:

1. **Cache Warming**: When `output_head_tags()` is called, it first calls `warm_cache()` to pre-load all SEO meta fields in a single operation
2. **Unified Cache Storage**: All meta fields (title, description, robots, canonical) are stored in a single cache entry
3. **Single Cache Lookup**: Each getter method (`get_title()`, `get_description()`, etc.) performs a single cache lookup via `get_cached_meta()`
4. **Result**: Zero database queries for fully cached posts on frontend

### Implementation

```php
// In output_head_tags()
$this->warm_cache( $post_id );  // Pre-loads all meta fields

// Each getter checks cache first
$cached_meta = $this->get_cached_meta( $post_id );
if ( isset( $cached_meta['title'] ) ) {
    return $cached_meta['title'];  // No DB query
}
```

### Schema and Social Modules

Both Schema and Social modules implement similar caching strategies:

- **Schema Module**: Caches generated JSON-LD string for 1 hour
- **Social Module**: Caches all social meta data (title, description, image, type, URL) for 1 hour

## Asset Loading Optimization (Requirement 14.4)

### Conditional Asset Loading

Frontend assets are only loaded when required by active modules:

1. **Gutenberg Assets**: Only loaded in block editor context
   ```php
   // In Gutenberg::enqueue_editor_assets()
   $screen = get_current_screen();
   if ( ! $screen || ! $screen->is_block_editor() ) {
       return;  // Don't load assets outside block editor
   }
   ```

2. **Admin Assets**: Only loaded on MeowSEO settings page
   ```php
   // In Admin::enqueue_admin_assets()
   if ( 'toplevel_page_meowseo-settings' !== $hook_suffix ) {
       return;  // Don't load assets on other admin pages
   }
   ```

3. **No Frontend Assets**: The plugin does not enqueue any CSS or JavaScript on the public-facing frontend

## Sitemap Optimization (Requirement 14.5)

### Direct Filesystem Serving

Sitemaps are served directly from the filesystem, bypassing WordPress template loading entirely:

1. **File Generation**: Sitemaps are generated as physical XML files in `wp-content/uploads/meowseo-sitemaps/`
2. **Path Caching**: Only the file path is stored in Object Cache, not the XML content
3. **Direct Output**: Files are served using `readfile()` with immediate `exit`, avoiding WordPress query overhead
4. **Lock Pattern**: Atomic locks prevent cache stampede during concurrent sitemap requests

### Performance Benefits

- No WordPress query execution
- No template loading overhead
- Minimal memory usage (streaming file output)
- Concurrent request protection

## Redirect Optimization (Requirement 14.6)

### Database-Level Matching

The Redirect module never loads all redirect rules into PHP memory:

1. **Exact Match First**: Single indexed query on `source_url` column (O(log n) lookup)
   ```sql
   SELECT * FROM meowseo_redirects 
   WHERE source_url = %s AND status = 'active' 
   LIMIT 1
   ```

2. **Regex Fallback**: Only loads regex rules when `has_regex_rules` flag is true
   ```sql
   SELECT * FROM meowseo_redirects 
   WHERE is_regex = 1 AND status = 'active'
   ```

3. **Memory Efficiency**: Only regex rules (typically < 50) are loaded into memory, never all rules

### Performance Benefits

- O(log n) exact match lookup via indexed column
- Regex evaluation only when necessary
- Minimal memory footprint
- Scales to thousands of redirect rules

## 404 Monitoring Optimization

### Buffered Logging

404 hits are buffered in Object Cache and flushed asynchronously:

1. **Per-Minute Buckets**: Hits are stored in cache keys like `meowseo_404_{YYYYMMDD_HHmm}`
2. **No Synchronous Writes**: Zero database writes during 404 requests
3. **Batch Flushing**: WP-Cron flushes buffers every 60 seconds using bulk INSERT
4. **Hit Count Preservation**: Uses `ON DUPLICATE KEY UPDATE` to maintain accurate counts

### Performance Benefits

- No database writes during user requests
- Reduced database load via batching
- Minimal request latency impact

## Module Loading Optimization

### Conditional Module Instantiation

Only enabled modules are loaded and instantiated:

1. **Module Manager**: Reads enabled modules from Options
2. **Selective Loading**: Only `require_once` files for enabled modules
3. **No Overhead**: Disabled modules have zero performance impact

### Example

If only Meta and Schema modules are enabled:
- Redirects module: Not loaded
- 404 Monitor module: Not loaded
- GSC module: Not loaded
- Internal Links module: Not loaded

## Cache Invalidation Strategy

### Automatic Invalidation

Caches are automatically invalidated when content changes:

1. **Meta Cache**: Invalidated on post save via `update_rest_meta()`
2. **Schema Cache**: Invalidated on post save via `invalidate_cache_on_save()`
3. **Social Cache**: Invalidated on post save via `invalidate_cache_on_save()`
4. **Sitemap Cache**: Invalidated on post publish/update, regenerated asynchronously

### Manual Invalidation

Cache can be manually cleared via:
- WordPress object cache flush (if using persistent cache)
- Transient cleanup (automatic via WordPress cron)

## Performance Metrics

### Expected Performance Characteristics

With full caching enabled:

- **Frontend Page Load**: 0 additional database queries for cached posts
- **Sitemap Requests**: Direct file serving, < 10ms response time
- **Redirect Checks**: Single indexed query, < 5ms overhead
- **404 Logging**: Zero synchronous database writes
- **Memory Usage**: < 5MB additional memory per request

### Scalability

The plugin is designed to scale efficiently:

- **10,000+ posts**: No performance degradation (caching eliminates N+1 queries)
- **1,000+ redirects**: O(log n) lookup via indexed column
- **High traffic**: Object Cache prevents database overload
- **Concurrent requests**: Lock patterns prevent cache stampede

## Monitoring and Debugging

### Cache Hit Rate

Monitor cache effectiveness using:

```php
// Check if meta is cached
$cached = Cache::get( "meta_{$post_id}" );
if ( $cached ) {
    // Cache hit
} else {
    // Cache miss - will query database
}
```

### Performance Profiling

Use WordPress debugging tools:

```php
// Enable query monitoring
define( 'SAVEQUERIES', true );

// Check query count
global $wpdb;
echo count( $wpdb->queries );  // Should be minimal on cached pages
```

## Best Practices

### For Site Administrators

1. **Enable Persistent Object Cache**: Install Redis or Memcached for optimal performance
2. **Monitor Cache Hit Rate**: Ensure caching is working correctly
3. **Use Exact Redirects**: Prefer exact-match redirects over regex for better performance
4. **Regular Cache Warming**: Consider pre-warming cache for popular posts

### For Developers

1. **Always Use Cache Helper**: Never bypass the Cache class
2. **Respect TTL Values**: Don't cache indefinitely unless necessary
3. **Invalidate on Changes**: Always invalidate cache when data changes
4. **Test Without Cache**: Ensure functionality works even when cache is unavailable

## Requirements Mapping

This implementation satisfies the following requirements:

- **Requirement 14.1**: Zero database queries on frontend for cached posts ✅
- **Requirement 14.2**: Consistent cache key prefix and group isolation ✅
- **Requirement 14.3**: Transient fallback when Object Cache unavailable ✅
- **Requirement 14.4**: Conditional asset loading only when required ✅
- **Requirement 14.5**: Direct filesystem serving for sitemaps ✅
- **Requirement 14.6**: Database-level redirect matching, never load all rules ✅

## Conclusion

The MeowSEO plugin implements comprehensive performance optimizations that ensure minimal impact on site performance while providing full SEO functionality. The caching strategy, conditional loading, and database-level operations work together to create a fast, scalable SEO solution suitable for high-traffic WordPress sites.
