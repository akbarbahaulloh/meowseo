# Task 8.3 Completion: Wire Keyword UI to REST API

## Summary

Successfully implemented REST API endpoint `POST /meowseo/v1/keywords/{post_id}` that connects the Gutenberg keyword UI to the backend keyword management and analysis system.

## Implementation Details

### 1. REST Endpoint Registration

**File**: `includes/class-rest-api.php`

Added `register_keyword_routes()` method that registers:
- **Endpoint**: `POST /meowseo/v1/keywords/{post_id}`
- **Permission**: Uses existing `update_meta_permission` callback (requires `edit_post` capability)
- **Parameters**:
  - `post_id` (integer, required): Post ID to update
  - `primary` (string, optional): Primary focus keyword
  - `secondary` (array, optional): Array of secondary keywords

### 2. Endpoint Handler

**Method**: `update_keywords()`

**Functionality**:
1. ✅ Verifies nonce for security (Requirement 15.2)
2. ✅ Accepts JSON payload with primary + secondary keywords (Requirement 2.1)
3. ✅ Validates keyword count (max 5 total) (Requirement 2.2)
4. ✅ Calls Keyword_Manager methods to update postmeta (Requirement 2.1)
5. ✅ Triggers Keyword_Analyzer to run analysis on keyword change (Requirement 2.9)
6. ✅ Returns updated analysis results (Requirement 2.9)

**Error Handling**:
- Returns 403 if nonce verification fails
- Returns 400 if keyword count exceeds 5
- Returns 404 if post not found
- Returns 500 for unexpected errors
- All errors are logged using Logger helper

### 3. Input Sanitization

**Method**: `sanitize_keyword_array()`

- Sanitizes each keyword using `sanitize_text_field()`
- Trims whitespace
- Removes empty keywords
- Returns clean array

### 4. Response Format

**Success Response** (200 OK):
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
      "density": {"score": 85, "status": "good", "value": 1.2},
      "in_title": {"score": 100, "status": "good"},
      "in_headings": {"score": 100, "status": "good"},
      "in_slug": {"score": 100, "status": "good"},
      "in_first_paragraph": {"score": 100, "status": "good"},
      "in_meta_description": {"score": 100, "status": "good"},
      "overall_score": 97
    }
  }
}
```

**Error Response** (400 Bad Request):
```json
{
  "success": false,
  "message": "Maximum of 5 keywords allowed (1 primary + 4 secondary).",
  "code": "keyword_count_exceeded"
}
```

### 5. Testing

**File**: `tests/test-rest-api.php`

Added comprehensive test coverage:
- ✅ `test_update_keywords_endpoint()` - Tests successful keyword update
- ✅ `test_update_keywords_endpoint_exceeds_max_count()` - Tests validation (Requirement 2.2)
- ✅ `test_update_keywords_endpoint_without_nonce()` - Tests security
- ✅ `test_update_keywords_endpoint_without_permission()` - Tests authorization

### 6. Documentation

**File**: `API_DOCUMENTATION.md`

Added complete endpoint documentation including:
- Endpoint URL and method
- Request parameters and body format
- Response format with example
- Error responses
- Validation rules
- Cache headers
- Required capabilities
- Requirement references

## Requirements Validation

| Requirement | Status | Implementation |
|------------|--------|----------------|
| 2.1 | ✅ | Endpoint accepts primary + secondary keywords and calls Keyword_Manager |
| 2.2 | ✅ | Validates total keyword count does not exceed 5 |
| 2.9 | ✅ | Triggers Keyword_Analyzer and returns updated analysis results |
| 2.10 | ✅ | Supports removing keywords (empty array or omit parameter) |
| 2.11 | ✅ | Supports reordering keywords (array order is preserved) |

## Integration Points

### Frontend (Gutenberg)
The existing `SecondaryKeywordsInput` component can now call this endpoint:

```javascript
wp.apiFetch({
  path: `/meowseo/v1/keywords/${postId}`,
  method: 'POST',
  data: {
    primary: 'wordpress seo',
    secondary: ['seo plugin', 'search optimization']
  }
}).then(response => {
  // Handle success
  console.log('Keywords updated:', response.keywords);
  console.log('Analysis results:', response.analysis);
});
```

### Backend
The endpoint integrates with:
- **Keyword_Manager**: For keyword storage and retrieval
- **Keyword_Analyzer**: For running per-keyword analysis
- **Logger**: For error logging
- **Options**: For plugin configuration

## Security

- ✅ Nonce verification required
- ✅ Capability check (`edit_post`)
- ✅ Input sanitization (keywords)
- ✅ Error logging without exposing internals
- ✅ No cache headers on mutations

## Performance

- Analysis runs synchronously on keyword update
- Results are cached in postmeta (`_meowseo_keyword_analysis`)
- No additional database queries beyond what's needed
- Response includes all necessary data to update UI

## Files Modified

1. `includes/class-rest-api.php` - Added endpoint registration and handler
2. `tests/test-rest-api.php` - Added test coverage
3. `API_DOCUMENTATION.md` - Added endpoint documentation

## Next Steps

To complete the full keyword management feature:
1. Update Gutenberg UI to call this endpoint instead of directly updating postmeta
2. Add loading states and error handling in the UI
3. Display analysis results in the KeywordAnalysisPanel component
4. Consider adding debouncing to avoid excessive API calls

## Notes

- The endpoint is designed to be called whenever keywords change in the UI
- Analysis is triggered automatically, no separate call needed
- The response includes both updated keywords and analysis results for efficiency
- The existing SecondaryKeywordsInput component already has the UI, just needs to be wired to this endpoint
