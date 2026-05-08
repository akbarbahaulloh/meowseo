# 🔌 REST API Documentation

MeowSEO provides a comprehensive REST API for programmatic access to SEO data.

## 📋 Base URL

```
https://yoursite.com/wp-json/meowseo/v1/
```

## 🔒 Authentication

### Public Endpoints
Most GET endpoints are public and don't require authentication.

### Protected Endpoints
POST, PUT, DELETE endpoints require authentication.

#### Using Application Passwords

```bash
# Create application password in WordPress
# User Profile > Application Passwords

# Use in requests
curl -u "username:password" \
  https://yoursite.com/wp-json/meowseo/v1/meta/123
```

#### Using Nonce (for AJAX)

```javascript
// Get nonce from localized script
const nonce = meowseo.nonce;

// Use in fetch
fetch('/wp-json/meowseo/v1/meta/123', {
  headers: {
    'X-WP-Nonce': nonce,
  },
});
```

---

## 📊 Endpoints

### 1. Get SEO Meta

Get SEO metadata for a post.

#### Request

```http
GET /wp-json/meowseo/v1/meta/{post_id}
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | Yes | Post ID |

#### Example

```bash
curl https://yoursite.com/wp-json/meowseo/v1/meta/123
```

#### Response

```json
{
  "success": true,
  "data": {
    "post_id": 123,
    "title": "Custom SEO Title",
    "description": "Custom meta description",
    "robots": "index,follow",
    "canonical": "https://example.com/post/",
    "focus_keyword": "seo optimization",
    "social": {
      "title": "Social Title",
      "description": "Social Description",
      "image_id": 456,
      "image_url": "https://example.com/image.jpg"
    },
    "schema_type": "Article"
  }
}
```

### 2. Update SEO Meta

Update SEO metadata for a post.

#### Request

```http
POST /wp-json/meowseo/v1/meta/{post_id}
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | Yes | Post ID |
| `title` | string | No | SEO title |
| `description` | string | No | Meta description |
| `robots` | string | No | Robots meta |
| `canonical` | string | No | Canonical URL |
| `focus_keyword` | string | No | Focus keyword |

#### Example

```bash
curl -X POST \
  -u "username:password" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "New SEO Title",
    "description": "New description",
    "robots": "index,follow"
  }' \
  https://yoursite.com/wp-json/meowseo/v1/meta/123
```

#### Response

```json
{
  "success": true,
  "message": "SEO meta updated successfully",
  "data": {
    "post_id": 123,
    "title": "New SEO Title",
    "description": "New description",
    "robots": "index,follow"
  }
}
```

### 3. Get Schema Data

Get schema.org data for a post.

#### Request

```http
GET /wp-json/meowseo/v1/schema/{post_id}
```

#### Example

```bash
curl https://yoursite.com/wp-json/meowseo/v1/schema/123
```

#### Response

```json
{
  "success": true,
  "data": {
    "post_id": 123,
    "schema_type": "Article",
    "json_ld": {
      "@context": "https://schema.org",
      "@type": "Article",
      "headline": "Post Title",
      "author": {
        "@type": "Person",
        "name": "John Doe"
      },
      "datePublished": "2026-05-08T10:00:00Z",
      "dateModified": "2026-05-08T12:00:00Z"
    }
  }
}
```

### 4. Get Breadcrumbs

Get breadcrumb trail for a post.

#### Request

```http
GET /wp-json/meowseo/v1/breadcrumbs/{post_id}
```

#### Example

```bash
curl https://yoursite.com/wp-json/meowseo/v1/breadcrumbs/123
```

#### Response

```json
{
  "success": true,
  "data": {
    "items": [
      {
        "text": "Home",
        "url": "https://example.com/",
        "position": 1
      },
      {
        "text": "Blog",
        "url": "https://example.com/blog/",
        "position": 2
      },
      {
        "text": "Post Title",
        "url": "https://example.com/post/",
        "position": 3
      }
    ],
    "schema": {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [...]
    }
  }
}
```

### 5. Get Settings

Get global SEO settings.

#### Request

```http
GET /wp-json/meowseo/v1/settings
```

#### Example

```bash
curl https://yoursite.com/wp-json/meowseo/v1/settings
```

#### Response

```json
{
  "success": true,
  "data": {
    "general": {
      "separator": "|",
      "homepage_title": "My Site",
      "homepage_description": "Welcome"
    },
    "social": {
      "facebook_app_id": "123456",
      "twitter_username": "@mysite"
    },
    "organization": {
      "name": "My Company",
      "type": "Organization",
      "logo_id": 789
    }
  }
}
```

### 6. Update Settings

Update global SEO settings.

#### Request

```http
POST /wp-json/meowseo/v1/settings
```

#### Example

```bash
curl -X POST \
  -u "username:password" \
  -H "Content-Type: application/json" \
  -d '{
    "general": {
      "separator": "-",
      "homepage_title": "New Title"
    }
  }' \
  https://yoursite.com/wp-json/meowseo/v1/settings
```

### 7. Analyze Content

Analyze content for SEO.

#### Request

```http
POST /wp-json/meowseo/v1/analyze
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `content` | string | Yes | Content to analyze |
| `focus_keyword` | string | No | Focus keyword |

#### Example

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Your content here...",
    "focus_keyword": "seo"
  }' \
  https://yoursite.com/wp-json/meowseo/v1/analyze
```

#### Response

```json
{
  "success": true,
  "data": {
    "seo_score": 85,
    "readability_score": 78,
    "checks": [
      {
        "id": "keyword_density",
        "status": "good",
        "message": "Keyword density is optimal"
      },
      {
        "id": "content_length",
        "status": "good",
        "message": "Content length is sufficient"
      }
    ]
  }
}
```

### 8. Get Sitemap Index

Get sitemap index.

#### Request

```http
GET /wp-json/meowseo/v1/sitemap
```

#### Response

```json
{
  "success": true,
  "data": {
    "sitemaps": [
      {
        "type": "post",
        "url": "https://example.com/sitemap-post.xml",
        "last_modified": "2026-05-08T10:00:00Z"
      },
      {
        "type": "page",
        "url": "https://example.com/sitemap-page.xml",
        "last_modified": "2026-05-08T09:00:00Z"
      }
    ]
  }
}
```

---

## 🎯 Usage Examples

### JavaScript (Fetch API)

```javascript
// Get SEO meta
async function getSeoMeta(postId) {
  const response = await fetch(
    `https://yoursite.com/wp-json/meowseo/v1/meta/${postId}`
  );
  const data = await response.json();
  return data.data;
}

// Update SEO meta
async function updateSeoMeta(postId, meta) {
  const response = await fetch(
    `https://yoursite.com/wp-json/meowseo/v1/meta/${postId}`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': meowseo.nonce,
      },
      body: JSON.stringify(meta),
    }
  );
  const data = await response.json();
  return data;
}
```

### PHP (WordPress)

```php
// Get SEO meta
$response = wp_remote_get( 
    'https://yoursite.com/wp-json/meowseo/v1/meta/123'
);
$data = json_decode( wp_remote_retrieve_body( $response ), true );

// Update SEO meta
$response = wp_remote_post(
    'https://yoursite.com/wp-json/meowseo/v1/meta/123',
    [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode( [
            'title' => 'New Title',
            'description' => 'New Description',
        ] ),
    ]
);
```

### Python

```python
import requests

# Get SEO meta
response = requests.get(
    'https://yoursite.com/wp-json/meowseo/v1/meta/123'
)
data = response.json()

# Update SEO meta
response = requests.post(
    'https://yoursite.com/wp-json/meowseo/v1/meta/123',
    json={
        'title': 'New Title',
        'description': 'New Description'
    },
    auth=('username', 'password')
)
```

### cURL

```bash
# Get SEO meta
curl https://yoursite.com/wp-json/meowseo/v1/meta/123

# Update SEO meta
curl -X POST \
  -u "username:password" \
  -H "Content-Type: application/json" \
  -d '{"title":"New Title"}' \
  https://yoursite.com/wp-json/meowseo/v1/meta/123

# Analyze content
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"content":"Your content","focus_keyword":"seo"}' \
  https://yoursite.com/wp-json/meowseo/v1/analyze
```

---

## 📝 Error Responses

### 400 Bad Request

```json
{
  "success": false,
  "code": "invalid_parameter",
  "message": "Invalid post ID",
  "data": {
    "status": 400
  }
}
```

### 401 Unauthorized

```json
{
  "success": false,
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that",
  "data": {
    "status": 401
  }
}
```

### 404 Not Found

```json
{
  "success": false,
  "code": "post_not_found",
  "message": "Post not found",
  "data": {
    "status": 404
  }
}
```

### 500 Internal Server Error

```json
{
  "success": false,
  "code": "internal_error",
  "message": "An error occurred",
  "data": {
    "status": 500
  }
}
```

---

## 🔧 Rate Limiting

### Default Limits
- **Authenticated**: 1000 requests/hour
- **Unauthenticated**: 100 requests/hour

### Headers

```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1620000000
```

---

## 📚 Additional Resources

- [GraphQL API Documentation](GRAPHQL.md)
- [Developer Guide](../developer/GETTING_STARTED.md)
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)

---

**Status**: ✅ Fully Documented  
**Version**: v1  
**Support**: Full REST API coverage
