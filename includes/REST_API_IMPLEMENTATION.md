# REST API Layer Implementation

## Overview

This document describes the implementation of Task 15: "Implement REST API layer for headless support" for the MeowSEO plugin.

## Implementation Summary

### Task 15.1: Create meowseo/v1 REST namespace ✅

**File:** `includes/class-rest-api.php`

Created a centralized REST API class that:
- Registers all REST endpoints under the `meowseo/v1` namespace
- Implements meta CRUD endpoints with proper authentication:
  - `GET /meowseo/v1/meta/{post_id}` - Get all SEO meta for a post
  - `POST /meowseo/v1/meta/{post_id}` - Update SEO meta for a post
- Implements settings endpoints:
  - `GET /meowseo/v1/settings` - Get all plugin settings
  - `POST /meowseo/v1/settings` - Save plugin settings
- Provides comprehensive SEO data access including:
  - SEO title, description, robots, canonical
  - Open Graph meta data
  - Twitter Card meta data
  - JSON-LD schema data

**Security Features:**
- Nonce verification on all mutation endpoints (Requirement 15.2)
- Capability checks: `edit_post` for meta updates, `manage_options` for settings (Requirement 15.3)
- Public access only for publicly viewable posts (Requirement 13.2)

**Integration:**
- Integrated into `Plugin` class via `rest_api_init` hook
- Coordinates with active modules (Meta, Social, Schema) to provide comprehensive data

### Task 15.2: Add caching headers for headless deployments ✅

**Implementation:**
- All GET endpoints include `Cache-Control: public, max-age=300` headers (5 minutes)
- All mutation endpoints (POST, PUT, DELETE) include `Cache-Control: no-store` headers
- Supports CDN and edge caching for headless sites (Requirement 13.6)

**Existing Module Support:**
The following modules already implement cache headers:
- Schema module: `includes/modules/schema/class-schema.php`
- Social module: `includes/modules/social/class-social-rest.php`
- Redirects module: `includes/modules/redirects/class-redirects-rest.php`
- Monitor 404 module: `includes/modules/monitor_404/class-monitor-404-rest.php`
- GSC module: `includes/modules/gsc/class-gsc-rest.php`
- Internal Links module: `includes/modules/internal_links/class-internal-links-rest.php`

### Task 15.3: Create WPGraphQL integration (conditional) ✅

**File:** `includes/class-wpgraphql.php`

Created a WPGraphQL integration class that:
- Only loads when WPGraphQL plugin is active
- Registers custom GraphQL types:
  - `MeowSeoData` - Main SEO data type
  - `MeowSeoOpenGraph` - Open Graph meta data type
  - `MeowSeoTwitterCard` - Twitter Card meta data type
- Registers `seo` field on all queryable post types
- Exposes comprehensive SEO data:
  - `title` - SEO title
  - `description` - Meta description
  - `robots` - Robots directive
  - `canonical` - Canonical URL
  - `openGraph` - Open Graph sub-fields (title, description, image, type, url)
  - `twitterCard` - Twitter Card sub-fields (card, title, description, image)
  - `schemaJsonLd` - JSON-LD structured data

**Integration:**
- Integrated into `Plugin` class via `graphql_register_types` hook
- Conditional loading: only instantiated when `class_exists('WPGraphQL')` is true
- Coordinates with active modules to provide comprehensive data

## GraphQL Query Example

```graphql
query GetPost($id: ID!) {
  post(id: $id, idType: DATABASE_ID) {
    id
    title
    seo {
      title
      description
      robots
      canonical
      openGraph {
        title
        description
        image
        type
        url
      }
      twitterCard {
        card
        title
        description
        image
      }
      schemaJsonLd
    }
  }
}
```

## REST API Examples

### Get SEO Meta

```bash
GET /wp-json/meowseo/v1/meta/123
```

Response:
```json
{
  "post_id": 123,
  "title": "My SEO Title",
  "description": "My meta description",
  "robots": "index,follow",
  "canonical": "https://example.com/my-post",
  "openGraph": {
    "title": "My OG Title",
    "description": "My OG description",
    "image": "https://example.com/image.jpg",
    "type": "article",
    "url": "https://example.com/my-post"
  },
  "twitterCard": {
    "card": "summary_large_image",
    "title": "My Twitter Title",
    "description": "My Twitter description",
    "image": "https://example.com/image.jpg"
  },
  "schemaJsonLd": "{\"@context\":\"https://schema.org\",\"@graph\":[...]}"
}
```

### Update SEO Meta

```bash
POST /wp-json/meowseo/v1/meta/123
X-WP-Nonce: abc123...
Content-Type: application/json

{
  "title": "Updated SEO Title",
  "description": "Updated meta description",
  "robots": "noindex,nofollow",
  "canonical": "https://example.com/updated-post"
}
```

Response:
```json
{
  "success": true,
  "message": "Meta updated successfully.",
  "post_id": 123
}
```

## Testing

Test files created:
- `tests/test-rest-api.php` - REST API endpoint tests
- `tests/test-wpgraphql.php` - WPGraphQL integration tests

Tests cover:
- GET/POST meta endpoints
- Authentication and authorization
- Nonce verification
- Cache headers
- WPGraphQL type registration
- WPGraphQL field resolution

## Requirements Coverage

- ✅ **Requirement 13.1**: REST API registers all endpoints under meowseo/v1 namespace
- ✅ **Requirement 13.2**: GET meowseo/v1/meta/{post_id} endpoint returns all SEO meta fields
- ✅ **Requirement 13.4**: GET meowseo/v1/schema/{post_id} endpoint (already implemented in Schema module)
- ✅ **Requirement 13.5**: WPGraphQL seo field registered on all queryable post types
- ✅ **Requirement 13.6**: Cache-Control headers on all GET responses
- ✅ **Requirement 15.2**: Nonce verification on all REST mutation endpoints
- ✅ **Requirement 15.3**: Capability checks on all REST endpoints

## Files Modified

1. `includes/class-plugin.php` - Added REST API and WPGraphQL initialization
2. `includes/class-rest-api.php` - New centralized REST API class
3. `includes/class-wpgraphql.php` - New WPGraphQL integration class
4. `tests/test-rest-api.php` - New REST API tests
5. `tests/test-wpgraphql.php` - New WPGraphQL tests

## Notes

- The implementation follows the existing module pattern used throughout the plugin
- All security requirements are met (nonce verification, capability checks)
- Cache headers support CDN/edge caching for headless deployments
- WPGraphQL integration is conditional and only loads when WPGraphQL is active
- The centralized REST API class coordinates with active modules to provide comprehensive data
- All existing module REST endpoints already use the meowseo/v1 namespace and cache headers
