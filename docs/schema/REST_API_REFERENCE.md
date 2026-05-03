# Schema Generator REST API Reference

Complete reference for the MeowSEO Schema Generator REST API.

**Base URL:** `/wp-json/meowseo/v1`  
**Authentication:** WordPress nonce (`X-WP-Nonce` header)  
**Authorization:** `edit_posts` capability required

---

## 📋 Table of Contents

1. [CRUD Operations](#crud-operations)
2. [Schema Types](#schema-types)
3. [Utilities](#utilities)
4. [Response Format](#response-format)
5. [Error Handling](#error-handling)
6. [Examples](#examples)

---

## CRUD Operations

### Get All Schemas

Get all schemas for a specific post.

**Endpoint:** `GET /schemas/{post_id}`

**Parameters:**
- `post_id` (integer, required) - Post ID

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "schema-abc123",
      "type": "Article",
      "data": {
        "headline": "My Article",
        "description": "Article description"
      },
      "shortcode": "s-abc123"
    }
  ]
}
```

---

### Get Single Schema

Get a specific schema by ID.

**Endpoint:** `GET /schemas/{post_id}/{schema_id}`

**Parameters:**
- `post_id` (integer, required) - Post ID
- `schema_id` (string, required) - Schema ID

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "schema-abc123",
    "type": "Article",
    "data": {
      "headline": "My Article",
      "description": "Article description"
    },
    "shortcode": "s-abc123"
  }
}
```

**Error Response:**
```json
{
  "code": "schema_not_found",
  "message": "Schema not found",
  "data": {
    "status": 404
  }
}
```

---

### Create Schema

Create a new schema for a post.

**Endpoint:** `POST /schemas/{post_id}`

**Parameters:**
- `post_id` (integer, required) - Post ID
- `schema` (object, required) - Schema data

**Request Body:**
```json
{
  "schema": {
    "type": "Article",
    "data": {
      "headline": "My Article",
      "description": "Article description",
      "author": {
        "@type": "Person",
        "name": "John Doe"
      }
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Schema saved successfully",
  "data": {
    "id": "schema-abc123",
    "type": "Article",
    "data": {
      "headline": "My Article",
      "description": "Article description",
      "author": {
        "@type": "Person",
        "name": "John Doe"
      }
    },
    "shortcode": "s-abc123"
  }
}
```

---

### Update Schema

Update an existing schema.

**Endpoint:** `PUT /schemas/{post_id}/{schema_id}`

**Parameters:**
- `post_id` (integer, required) - Post ID
- `schema_id` (string, required) - Schema ID
- `schema` (object, required) - Updated schema data

**Request Body:**
```json
{
  "schema": {
    "type": "Article",
    "data": {
      "headline": "Updated Article Title",
      "description": "Updated description"
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Schema updated successfully",
  "data": {
    "id": "schema-abc123",
    "type": "Article",
    "data": {
      "headline": "Updated Article Title",
      "description": "Updated description"
    },
    "shortcode": "s-abc123"
  }
}
```

---

### Delete Schema

Delete a schema.

**Endpoint:** `DELETE /schemas/{post_id}/{schema_id}`

**Parameters:**
- `post_id` (integer, required) - Post ID
- `schema_id` (string, required) - Schema ID

**Response:**
```json
{
  "success": true,
  "message": "Schema deleted successfully"
}
```

---

## Schema Types

### Get All Schema Types

Get list of all available schema types.

**Endpoint:** `GET /schema-types`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "article",
      "type": "Article",
      "label": "Article",
      "description": "An article, such as a news article or piece of investigative report.",
      "icon": "media-document"
    },
    {
      "id": "product",
      "type": "Product",
      "label": "Product",
      "description": "Any offered product or service.",
      "icon": "products"
    }
  ]
}
```

---

### Get Schema Type Fields

Get field configuration for a specific schema type.

**Endpoint:** `GET /schema-types/{type}/fields`

**Parameters:**
- `type` (string, required) - Schema type (e.g., "Article", "Product")

**Response:**
```json
{
  "success": true,
  "data": {
    "headline": {
      "type": "text",
      "label": "Headline",
      "description": "The headline of the article",
      "default": "%title%",
      "required": true
    },
    "description": {
      "type": "textarea",
      "label": "Description",
      "description": "A short description of the article",
      "default": "%excerpt%"
    }
  }
}
```

---

### Get Schema Type Defaults

Get default values for a schema type.

**Endpoint:** `GET /schema-types/{type}/defaults`

**Parameters:**
- `type` (string, required) - Schema type
- `post_id` (integer, optional) - Post ID for context-aware defaults

**Response:**
```json
{
  "success": true,
  "data": {
    "headline": "%title%",
    "description": "%excerpt%",
    "author": {
      "@type": "Person",
      "name": "%author%"
    },
    "datePublished": "%date(Y-m-d\\TH:i:sP)%"
  }
}
```

---

## Utilities

### Validate Schema

Validate schema data before saving.

**Endpoint:** `POST /schemas/validate`

**Request Body:**
```json
{
  "schema": {
    "type": "Article",
    "data": {
      "headline": "My Article"
    }
  }
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Schema is valid"
}
```

**Error Response:**
```json
{
  "success": false,
  "errors": [
    "Description is required",
    "Author is required"
  ]
}
```

---

### Preview Schema JSON-LD

Preview the JSON-LD output for a schema.

**Endpoint:** `POST /schemas/preview`

**Request Body:**
```json
{
  "schema": {
    "type": "Article",
    "data": {
      "headline": "%title%",
      "description": "%excerpt%",
      "author": {
        "@type": "Person",
        "name": "%author%"
      }
    }
  },
  "post_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "My Actual Post Title",
    "description": "My actual post excerpt",
    "author": {
      "@type": "Person",
      "name": "John Doe"
    }
  }
}
```

---

### Get Available Variables

Get list of all available variables.

**Endpoint:** `GET /schema-variables`

**Response:**
```json
{
  "success": true,
  "data": {
    "post": [
      {
        "variable": "%title%",
        "description": "Post title"
      },
      {
        "variable": "%excerpt%",
        "description": "Post excerpt"
      }
    ],
    "site": [
      {
        "variable": "%sitename%",
        "description": "Site name"
      }
    ]
  }
}
```

---

## Response Format

### Success Response

All successful responses follow this format:

```json
{
  "success": true,
  "data": { /* response data */ },
  "message": "Optional success message"
}
```

### Error Response

All error responses use WordPress `WP_Error` format:

```json
{
  "code": "error_code",
  "message": "Error message",
  "data": {
    "status": 400
  }
}
```

---

## Error Handling

### Common Error Codes

| Code | Status | Description |
|------|--------|-------------|
| `invalid_post` | 404 | Post ID not found |
| `schema_not_found` | 404 | Schema ID not found |
| `invalid_type` | 404 | Invalid schema type |
| `missing_type` | 400 | Schema type is required |
| `validation_failed` | 400 | Schema validation failed |
| `save_failed` | 500 | Failed to save schema |
| `update_failed` | 500 | Failed to update schema |
| `delete_failed` | 500 | Failed to delete schema |

---

## Examples

### JavaScript (Fetch API)

```javascript
// Get all schemas
async function getSchemas(postId) {
  const response = await fetch(`/wp-json/meowseo/v1/schemas/${postId}`, {
    headers: {
      'X-WP-Nonce': wpApiSettings.nonce
    }
  });
  const result = await response.json();
  return result.data;
}

// Create schema
async function createSchema(postId, schemaData) {
  const response = await fetch(`/wp-json/meowseo/v1/schemas/${postId}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({ schema: schemaData })
  });
  const result = await response.json();
  return result.data;
}

// Update schema
async function updateSchema(postId, schemaId, schemaData) {
  const response = await fetch(`/wp-json/meowseo/v1/schemas/${postId}/${schemaId}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({ schema: schemaData })
  });
  const result = await response.json();
  return result.data;
}

// Delete schema
async function deleteSchema(postId, schemaId) {
  const response = await fetch(`/wp-json/meowseo/v1/schemas/${postId}/${schemaId}`, {
    method: 'DELETE',
    headers: {
      'X-WP-Nonce': wpApiSettings.nonce
    }
  });
  const result = await response.json();
  return result.success;
}

// Preview JSON-LD
async function previewSchema(schemaData, postId) {
  const response = await fetch('/wp-json/meowseo/v1/schemas/preview', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({ 
      schema: schemaData,
      post_id: postId
    })
  });
  const result = await response.json();
  return result.data;
}
```

### WordPress API Fetch

```javascript
import apiFetch from '@wordpress/api-fetch';

// Get all schemas
const schemas = await apiFetch({
  path: `/meowseo/v1/schemas/${postId}`
});

// Create schema
const newSchema = await apiFetch({
  path: `/meowseo/v1/schemas/${postId}`,
  method: 'POST',
  data: { schema: schemaData }
});

// Update schema
const updatedSchema = await apiFetch({
  path: `/meowseo/v1/schemas/${postId}/${schemaId}`,
  method: 'PUT',
  data: { schema: schemaData }
});

// Delete schema
await apiFetch({
  path: `/meowseo/v1/schemas/${postId}/${schemaId}`,
  method: 'DELETE'
});
```

### React Hook Example

```javascript
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

function useSchemas(postId) {
  const [schemas, setSchemas] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadSchemas();
  }, [postId]);

  async function loadSchemas() {
    try {
      setLoading(true);
      const result = await apiFetch({
        path: `/meowseo/v1/schemas/${postId}`
      });
      setSchemas(result.data);
      setError(null);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function createSchema(schemaData) {
    const result = await apiFetch({
      path: `/meowseo/v1/schemas/${postId}`,
      method: 'POST',
      data: { schema: schemaData }
    });
    setSchemas([...schemas, result.data]);
    return result.data;
  }

  async function updateSchema(schemaId, schemaData) {
    const result = await apiFetch({
      path: `/meowseo/v1/schemas/${postId}/${schemaId}`,
      method: 'PUT',
      data: { schema: schemaData }
    });
    setSchemas(schemas.map(s => s.id === schemaId ? result.data : s));
    return result.data;
  }

  async function deleteSchema(schemaId) {
    await apiFetch({
      path: `/meowseo/v1/schemas/${postId}/${schemaId}`,
      method: 'DELETE'
    });
    setSchemas(schemas.filter(s => s.id !== schemaId));
  }

  return {
    schemas,
    loading,
    error,
    createSchema,
    updateSchema,
    deleteSchema,
    reload: loadSchemas
  };
}
```

---

## 🔒 Security

### Authentication
All endpoints require WordPress authentication via nonce.

```javascript
headers: {
  'X-WP-Nonce': wpApiSettings.nonce
}
```

### Authorization
User must have `edit_posts` capability.

### Input Validation
- All inputs are sanitized
- Required fields are validated
- Schema types are verified
- Post IDs are checked

### Error Messages
Error messages are user-friendly and don't expose sensitive information.

---

## 📊 Rate Limiting

No rate limiting is applied by default. Standard WordPress REST API rate limiting applies if configured.

---

## 🎯 Best Practices

1. **Always check response.success** before using data
2. **Handle errors gracefully** with try-catch
3. **Use wp.apiFetch** in WordPress context for automatic nonce handling
4. **Cache schema types** - they don't change often
5. **Debounce preview requests** - don't preview on every keystroke
6. **Validate before saving** - use the validate endpoint
7. **Show loading states** - API calls can take time

---

**API Version:** 1.0.0  
**Last Updated:** May 3, 2026  
**Status:** ✅ Production Ready

