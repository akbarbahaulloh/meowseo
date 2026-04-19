# MeowSEO API Documentation

Complete API reference for REST endpoints and WPGraphQL schema.

## Table of Contents

- [REST API](#rest-api)
  - [Authentication](#authentication)
  - [Core Endpoints](#core-endpoints)
  - [Module Endpoints](#module-endpoints)
- [WPGraphQL](#wpgraphql)
  - [Schema Types](#schema-types)
  - [Query Examples](#query-examples)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)

---

## REST API

All REST endpoints are registered under the `meowseo/v1` namespace.

**Base URL**: `https://yoursite.com/wp-json/meowseo/v1`

### Authentication

#### GET Endpoints (Read Operations)

- **Public Posts**: No authentication required for publicly viewable posts
- **Private Content**: Requires `read` capability or appropriate post access

#### POST/PUT/DELETE Endpoints (Write Operations)

All mutation endpoints require:

1. **Valid WordPress Nonce**: Include `X-WP-Nonce` header
2. **Appropriate Capability**: User must have required capability
3. **Authenticated Session**: User must be logged in

**Example Request Headers**:
```http
X-WP-Nonce: abc123def456
Content-Type: application/json
```

**Getting a Nonce** (JavaScript):
```javascript
const nonce = wpApiSettings.nonce; // Available in WordPress admin
// or
const nonce = wp.apiFetch.nonceMiddleware.nonce;
```

### Core Endpoints

#### Get SEO Meta

Retrieve all SEO metadata for a post.

```http
GET /meowseo/v1/meta/{post_id}
```

**Parameters**:
- `post_id` (integer, required): Post ID

**Response** (200 OK):
```json
{
  "post_id": 123,
  "title": "Custom SEO Title",
  "description": "Custom meta description for this post",
  "robots": "index,follow",
  "canonical": "https://example.com/custom-url",
  "openGraph": {
    "title": "OG Title",
    "description": "OG Description",
    "image": "https://example.com/image.jpg",
    "type": "article",
    "url": "https://example.com/post"
  },
  "twitterCard": {
    "card": "summary_large_image",
    "title": "Twitter Title",
    "description": "Twitter Description",
    "image": "https://example.com/image.jpg"
  },
  "schemaJsonLd": "{\"@context\":\"https://schema.org\",\"@type\":\"Article\",...}"
}
```

**Cache Headers**: `Cache-Control: public, max-age=300`

---

#### Update SEO Meta

Update SEO metadata for a post.

```http
POST /meowseo/v1/meta/{post_id}
```

**Parameters**:
- `post_id` (integer, required): Post ID
- `title` (string, optional): SEO title
- `description` (string, optional): Meta description
- `robots` (string, optional): Robots directive (e.g., "index,follow")
- `canonical` (string, optional): Canonical URL

**Request Body**:
```json
{
  "title": "New SEO Title",
  "description": "New meta description",
  "robots": "index,follow",
  "canonical": "https://example.com/canonical-url"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Meta updated successfully.",
  "post_id": 123
}
```

**Required**: `edit_post` capability + valid nonce

**Cache Headers**: `Cache-Control: no-store`

---

#### Get Plugin Settings

Retrieve all plugin settings.

```http
GET /meowseo/v1/settings
```

**Response** (200 OK):
```json
{
  "success": true,
  "settings": {
    "enabled_modules": ["meta", "schema", "sitemap"],
    "separator": "|",
    "default_social_image": 42,
    "delete_on_uninstall": false,
    "has_regex_rules": false
  }
}
```

**Required**: `manage_options` capability

**Cache Headers**: `Cache-Control: public, max-age=300`

---

#### Update Plugin Settings

Save plugin settings.

```http
POST /meowseo/v1/settings
```

**Request Body**:
```json
{
  "enabled_modules": ["meta", "schema", "sitemap", "redirects"],
  "separator": "-",
  "default_social_image": 42,
  "delete_on_uninstall": false
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Settings updated successfully."
}
```

**Required**: `manage_options` capability + valid nonce

**Cache Headers**: `Cache-Control: no-store`

---

### Module Endpoints

#### Keywords Module

##### Update Keywords

Update primary and secondary keywords for a post and trigger analysis.

```http
POST /meowseo/v1/keywords/{post_id}
```

**Parameters**:
- `post_id` (integer, required): Post ID

**Request Body**:
```json
{
  "primary": "wordpress seo",
  "secondary": ["seo plugin", "search optimization"]
}
```

**Request Fields**:
- `primary` (string, optional): Primary focus keyword
- `secondary` (array, optional): Array of secondary keywords (max 4)

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Keywords updated successfully.",
  "keywords": {
    "primary": "wordpress seo",
    "secondary": ["seo plugin", "search optimization"]
  },
  "analysis": {
    "wordpress seo": {
      "density": {
        "score": 85,
        "status": "good",
        "value": 1.2
      },
      "in_title": {
        "score": 100,
        "status": "good"
      },
      "in_headings": {
        "score": 100,
        "status": "good"
      },
      "in_slug": {
        "score": 100,
        "status": "good"
      },
      "in_first_paragraph": {
        "score": 100,
        "status": "good"
      },
      "in_meta_description": {
        "score": 100,
        "status": "good"
      },
      "overall_score": 97
    },
    "seo plugin": {
      "density": {
        "score": 70,
        "status": "ok",
        "value": 0.8
      },
      "in_title": {
        "score": 0,
        "status": "poor"
      },
      "in_headings": {
        "score": 100,
        "status": "good"
      },
      "in_slug": {
        "score": 0,
        "status": "poor"
      },
      "in_first_paragraph": {
        "score": 100,
        "status": "good"
      },
      "in_meta_description": {
        "score": 0,
        "status": "poor"
      },
      "overall_score": 62
    }
  }
}
```

**Error Response** (400 Bad Request - Keyword Count Exceeded):
```json
{
  "success": false,
  "message": "Maximum of 5 keywords allowed (1 primary + 4 secondary).",
  "code": "keyword_count_exceeded"
}
```

**Validation Rules**:
- Maximum 5 total keywords (1 primary + 4 secondary)
- Keywords are trimmed and sanitized
- Empty keywords are ignored
- Duplicate keywords are allowed but not recommended

**Cache Headers**: `Cache-Control: no-store`

**Required**: `edit_post` capability for the specified post

**Requirements**: 2.1, 2.2, 2.9, 2.10, 2.11

---

#### Redirects Module

##### List Redirects

```http
GET /meowseo/v1/redirects
```

**Query Parameters**:
- `page` (integer, optional, default: 1): Page number
- `per_page` (integer, optional, default: 50, max: 100): Items per page

**Response** (200 OK):
```json
[
  {
    "id": 1,
    "source_url": "/old-page",
    "target_url": "/new-page",
    "redirect_type": 301,
    "is_regex": 0,
    "status": "active",
    "hit_count": 42,
    "last_accessed": "2024-01-15 10:30:00",
    "created_at": "2024-01-01 00:00:00",
    "updated_at": "2024-01-15 10:30:00"
  }
]
```

**Response Headers**:
- `X-WP-Total`: Total number of redirects
- `X-WP-TotalPages`: Total number of pages
- `Cache-Control: public, max-age=300`

**Required**: `manage_options` capability

---

##### Create Redirect

```http
POST /meowseo/v1/redirects
```

**Request Body**:
```json
{
  "source_url": "/old-page",
  "target_url": "/new-page",
  "redirect_type": 301,
  "is_regex": false,
  "status": "active"
}
```

**Redirect Types**:
- `301`: Permanent redirect
- `302`: Temporary redirect
- `307`: Temporary redirect (preserves method)
- `410`: Gone (no redirect)

**Response** (201 Created):
```json
{
  "id": 2,
  "source_url": "/old-page",
  "target_url": "/new-page",
  "redirect_type": 301,
  "is_regex": 0,
  "status": "active",
  "hit_count": 0,
  "last_accessed": null,
  "created_at": "2024-01-15 12:00:00",
  "updated_at": "2024-01-15 12:00:00"
}
```

**Required**: `manage_options` capability + valid nonce

---

##### Update Redirect

```http
PUT /meowseo/v1/redirects/{id}
```

**Parameters**:
- `id` (integer, required): Redirect ID

**Request Body** (all fields optional):
```json
{
  "source_url": "/updated-old-page",
  "target_url": "/updated-new-page",
  "redirect_type": 302,
  "is_regex": false,
  "status": "inactive"
}
```

**Response** (200 OK):
```json
{
  "id": 2,
  "source_url": "/updated-old-page",
  "target_url": "/updated-new-page",
  "redirect_type": 302,
  "is_regex": 0,
  "status": "inactive",
  "hit_count": 0,
  "last_accessed": null,
  "created_at": "2024-01-15 12:00:00",
  "updated_at": "2024-01-15 12:30:00"
}
```

**Required**: `manage_options` capability + valid nonce

---

##### Delete Redirect

```http
DELETE /meowseo/v1/redirects/{id}
```

**Parameters**:
- `id` (integer, required): Redirect ID

**Response** (200 OK):
```json
{
  "deleted": true,
  "redirect": {
    "id": 2,
    "source_url": "/old-page",
    "target_url": "/new-page",
    ...
  }
}
```

**Required**: `manage_options` capability + valid nonce

---

#### 404 Monitor Module

##### Get 404 Log

```http
GET /meowseo/v1/404-log
```

**Query Parameters**:
- `page` (integer, optional, default: 1): Page number
- `per_page` (integer, optional, default: 50, max: 100): Items per page
- `orderby` (string, optional, default: "last_seen"): Sort field
  - Options: `id`, `url`, `hit_count`, `first_seen`, `last_seen`
- `order` (string, optional, default: "DESC"): Sort order
  - Options: `ASC`, `DESC`

**Response** (200 OK):
```json
{
  "entries": [
    {
      "id": 1,
      "url": "/missing-page",
      "url_hash": "abc123...",
      "referrer": "https://google.com",
      "user_agent": "Mozilla/5.0...",
      "hit_count": 15,
      "first_seen": "2024-01-10",
      "last_seen": "2024-01-15"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 100,
    "total_pages": 2
  }
}
```

**Required**: `manage_options` capability

**Cache Headers**: `Cache-Control: public, max-age=300`

---

##### Delete 404 Entry

```http
DELETE /meowseo/v1/404-log/{id}
```

**Parameters**:
- `id` (integer, required): 404 log entry ID

**Response** (200 OK):
```json
{
  "success": true,
  "message": "404 log entry deleted successfully."
}
```

**Required**: `manage_options` capability + valid nonce

---

#### Internal Links Module

##### Get Link Health

```http
GET /meowseo/v1/internal-links?post_id={post_id}
```

**Query Parameters**:
- `post_id` (integer, required): Post ID

**Response** (200 OK):
```json
{
  "post_id": 123,
  "links": [
    {
      "id": 1,
      "source_post_id": 123,
      "target_url": "https://example.com/internal-page",
      "target_url_hash": "def456...",
      "anchor_text": "Click here",
      "http_status": 200,
      "last_checked": "2024-01-15 10:00:00"
    }
  ],
  "stats": {
    "total": 10,
    "checked": 8,
    "healthy": 7,
    "broken": 1,
    "redirects": 0,
    "pending": 2
  }
}
```

**Required**: `edit_posts` capability

**Cache Headers**: `Cache-Control: public, max-age=300`

---

##### Get Link Suggestions

```http
GET /meowseo/v1/internal-links/suggestions?post_id={post_id}
```

**Query Parameters**:
- `post_id` (integer, required): Post ID

**Response** (200 OK):
```json
{
  "post_id": 123,
  "suggestions": [
    {
      "post_id": 456,
      "title": "Related Post Title",
      "url": "https://example.com/related-post",
      "excerpt": "This post is related because...",
      "relevance_score": 0.85
    }
  ]
}
```

**Required**: `edit_posts` capability

**Cache Headers**: `Cache-Control: public, max-age=300`

---

#### Google Search Console Module

##### Get GSC Data

```http
GET /meowseo/v1/gsc
```

**Query Parameters**:
- `url` (string, optional): Filter by specific URL
- `start` (string, optional): Start date (YYYY-MM-DD)
- `end` (string, optional): End date (YYYY-MM-DD)

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "url": "https://example.com/post",
      "url_hash": "ghi789...",
      "date": "2024-01-15",
      "clicks": 42,
      "impressions": 1000,
      "ctr": 0.0420,
      "position": 5.25
    }
  ],
  "filters": {
    "url": "https://example.com/post",
    "start": "2024-01-01",
    "end": "2024-01-31"
  }
}
```

**Required**: `manage_options` capability

**Cache Headers**: `Cache-Control: public, max-age=300`

---

##### Save GSC Credentials

```http
POST /meowseo/v1/gsc/auth
```

**Request Body**:
```json
{
  "access_token": "ya29.a0...",
  "refresh_token": "1//0g...",
  "expires_in": 3600
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "GSC credentials saved successfully."
}
```

**Required**: `manage_options` capability + valid nonce

**Security**: Credentials are encrypted using AES-256-CBC before storage

---

##### Delete GSC Credentials

```http
DELETE /meowseo/v1/gsc/auth
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "GSC credentials deleted successfully."
}
```

**Required**: `manage_options` capability + valid nonce

---

##### Get Connection Status

```http
GET /meowseo/v1/gsc/status
```

**Response** (200 OK):
```json
{
  "connected": true
}
```

**Required**: `manage_options` capability

**Cache Headers**: `Cache-Control: public, max-age=60`

**Note**: This endpoint never exposes raw credentials, only connection status

---

#### Social Module

##### Get Social Meta

```http
GET /meowseo/v1/social/{post_id}
```

**Parameters**:
- `post_id` (integer, required): Post ID

**Response** (200 OK):
```json
{
  "post_id": 123,
  "social_title": "Custom Social Title",
  "social_description": "Custom social description",
  "social_image_id": 42,
  "social_image_url": "https://example.com/image.jpg"
}
```

**Required**: Public for publicly viewable posts

**Cache Headers**: `Cache-Control: public, max-age=300`

---

##### Update Social Meta

```http
POST /meowseo/v1/social/{post_id}
```

**Parameters**:
- `post_id` (integer, required): Post ID

**Request Body** (all fields optional):
```json
{
  "social_title": "New Social Title",
  "social_description": "New social description",
  "social_image_id": 42
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Social meta updated successfully.",
  "post_id": 123
}
```

**Required**: `edit_post` capability + valid nonce

---

##### Delete Social Meta

```http
DELETE /meowseo/v1/social/{post_id}
```

**Parameters**:
- `post_id` (integer, required): Post ID

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Social meta deleted successfully.",
  "post_id": 123
}
```

**Required**: `edit_post` capability + valid nonce

---

## WPGraphQL

When WPGraphQL is active, MeowSEO extends the GraphQL schema with SEO fields on all queryable post types.

### Schema Types

#### MeowSeoData

Main SEO data type containing all SEO metadata.

```graphql
type MeowSeoData {
  title: String
  description: String
  robots: String
  canonical: String
  openGraph: MeowSeoOpenGraph
  twitterCard: MeowSeoTwitterCard
  schemaJsonLd: String
}
```

#### MeowSeoOpenGraph

Open Graph metadata for social sharing.

```graphql
type MeowSeoOpenGraph {
  title: String
  description: String
  image: String
  type: String
  url: String
}
```

#### MeowSeoTwitterCard

Twitter Card metadata for Twitter sharing.

```graphql
type MeowSeoTwitterCard {
  card: String
  title: String
  description: String
  image: String
}
```

### Query Examples

#### Get SEO Data for a Single Post

```graphql
query GetPostSEO {
  post(id: "123", idType: DATABASE_ID) {
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

**Response**:
```json
{
  "data": {
    "post": {
      "id": "cG9zdDoxMjM=",
      "title": "My Post Title",
      "seo": {
        "title": "Custom SEO Title",
        "description": "Custom meta description",
        "robots": "index,follow",
        "canonical": "https://example.com/my-post",
        "openGraph": {
          "title": "OG Title",
          "description": "OG Description",
          "image": "https://example.com/image.jpg",
          "type": "article",
          "url": "https://example.com/my-post"
        },
        "twitterCard": {
          "card": "summary_large_image",
          "title": "Twitter Title",
          "description": "Twitter Description",
          "image": "https://example.com/image.jpg"
        },
        "schemaJsonLd": "{\"@context\":\"https://schema.org\",\"@type\":\"Article\",...}"
      }
    }
  }
}
```

---

#### Get SEO Data for Multiple Posts

```graphql
query GetPostsSEO {
  posts(first: 10) {
    nodes {
      id
      title
      seo {
        title
        description
        canonical
      }
    }
  }
}
```

---

#### Get SEO Data for a Page

```graphql
query GetPageSEO {
  page(id: "/about", idType: URI) {
    id
    title
    seo {
      title
      description
      robots
      canonical
      schemaJsonLd
    }
  }
}
```

---

#### Get SEO Data for Custom Post Type

```graphql
query GetProductSEO {
  product(id: "456", idType: DATABASE_ID) {
    id
    title
    seo {
      title
      description
      openGraph {
        title
        description
        image
      }
      schemaJsonLd
    }
  }
}
```

---

## Error Handling

### Error Response Format

All error responses follow WordPress REST API conventions:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400
  }
}
```

### Common Error Codes

| Code | Status | Description |
|------|--------|-------------|
| `rest_forbidden` | 403 | User lacks required capability |
| `rest_cookie_invalid_nonce` | 403 | Invalid or missing nonce |
| `post_not_found` | 404 | Post ID does not exist |
| `redirect_not_found` | 404 | Redirect ID does not exist |
| `not_found` | 404 | Resource not found |
| `invalid_type` | 400 | Invalid parameter type |
| `invalid_enum` | 400 | Invalid enum value |
| `invalid_array_item` | 400 | Invalid array item value |
| `db_insert_error` | 500 | Database insert failed |
| `db_update_error` | 500 | Database update failed |
| `db_delete_error` | 500 | Database delete failed |
| `delete_failed` | 500 | Delete operation failed |
| `save_failed` | 500 | Save operation failed |
| `module_not_loaded` | 500 | Required module not active |

### Example Error Response

```json
{
  "code": "rest_forbidden",
  "message": "You do not have permission to manage redirects.",
  "data": {
    "status": 403
  }
}
```

---

## Rate Limiting

### WordPress Default Rate Limiting

MeowSEO respects WordPress's built-in REST API rate limiting. No additional rate limiting is applied by the plugin.

### Caching Strategy

To optimize performance and reduce server load:

- **GET endpoints**: Include `Cache-Control: public, max-age=300` (5 minutes)
- **Mutation endpoints**: Include `Cache-Control: no-store` (no caching)

### Best Practices

1. **Cache GET Responses**: Implement client-side caching for GET requests
2. **Batch Operations**: Use bulk operations when available
3. **Pagination**: Use pagination parameters for large datasets
4. **CDN Integration**: Configure CDN to respect `Cache-Control` headers

---

## Additional Resources

- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [WPGraphQL Documentation](https://www.wpgraphql.com/docs/introduction)
- [MeowSEO GitHub Repository](https://github.com/akbarbahaulloh/meowseo)

---

## Support

For API-related questions or issues:

- **GitHub Issues**: [https://github.com/akbarbahaulloh/meowseo/issues](https://github.com/akbarbahaulloh/meowseo/issues)
- **GitHub Discussions**: [https://github.com/akbarbahaulloh/meowseo/discussions](https://github.com/akbarbahaulloh/meowseo/discussions)

---

**Last Updated**: January 2024  
**Plugin Version**: 1.0.0
