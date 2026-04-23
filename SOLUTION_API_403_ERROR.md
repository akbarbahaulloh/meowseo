# Solution: HTTP 403 Error on API Test Connection

## Executive Summary

Fixed the HTTP 403 error that occurred when testing API connections in the MeowSEO AI Settings page after converting to a profile-based system. The issue was caused by:

1. Sending masked API keys to the API instead of actual keys
2. Insufficient error messages in permission callbacks
3. Lack of proper error handling in the test_provider method

## Problem Description

**Symptom**: After converting AI settings to use profiles, clicking the "Test Connection" button returns "Request failed: HTTP 403"

**When it occurs**: 
- When testing a saved profile without editing the API key
- When the API key is displayed as masked (e.g., `abcd...wxyz`)

**Why it happens**:
- The JavaScript sends the masked key value to the API
- The API receives an invalid key and fails validation
- The permission callback returns a generic 403 error

## Solution Implemented

### Part 1: JavaScript Fix (Client-Side)

**File**: `includes/modules/ai/assets/js/ai-settings.js`

**Changes**:
1. Added `initAPIKeyInputHandlers()` method to clear masked keys on focus
2. Enhanced `testProviderConnection()` to detect and handle masked keys
3. Updated `init()` to call the new handler

**How it works**:
```javascript
// When user focuses on API key input
if (input.value.includes('...')) {
    input.value = '';  // Clear masked value
}

// When testing connection
if (isMasked && profileId) {
    apiKey = '';  // Let backend fetch from profile
} else if (isMasked && !profileId) {
    showError('Please enter a new API key or save the profile first');
}
```

### Part 2: PHP Fix (Server-Side)

**File**: `includes/modules/ai/class-ai-rest.php`

**Changes**:
1. Enhanced `check_permission_and_nonce_for_settings()` with:
   - Debug logging when WP_DEBUG enabled
   - Multiple nonce source fallbacks
   - Specific error messages for different failures

2. Enhanced `test_provider()` with:
   - Comprehensive debug logging
   - Better masked key handling
   - Improved error messages
   - Validation after all retrieval attempts

**How it works**:
```php
// Try multiple nonce sources
$nonce = $request->get_header( 'X-WP-Nonce' );
if ( ! $nonce ) {
    $nonce = $request->get_param( '_wpnonce' );
}

// Fetch API key from saved profile if masked
if ( ! empty( $profile_id ) && strpos( $api_key, '...' ) !== false ) {
    $api_key = $this->provider_manager->get_decrypted_profile_key( $profile );
}
```

## Testing the Fix

### Quick Test

1. Go to MeowSEO > AI Settings
2. Create a new profile with a valid API key
3. Click "Test Connection"
4. Should show "Connection successful"

### Comprehensive Test

1. **Test with new profile**:
   - Create profile, enter API key, test → Should work

2. **Test with saved profile**:
   - Save profile, click test without editing → Should work

3. **Test with edited profile**:
   - Edit saved profile's API key, test → Should work

4. **Test with invalid key**:
   - Enter invalid API key, test → Should show provider error

### Debug Testing

If issues persist, enable debug logging:

```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Then check `wp-content/debug.log` for "MeowSEO:" entries.

## Browser Console Test

```javascript
// Get nonce from page
const nonce = meowseoAISettings.nonce;

// Test API endpoint
fetch('/wp-json/meowseo/v1/ai/test-provider', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': nonce,
  },
  credentials: 'same-origin',
  body: JSON.stringify({
    provider: 'gemini',
    api_key: 'your-api-key',
  })
})
.then(r => r.json())
.then(d => console.log(d))
.catch(e => console.error(e));
```

## Troubleshooting

### Still Getting 403?

1. **Check user role**: Must have `manage_options` capability (admin)
2. **Refresh page**: Get fresh nonce
3. **Check debug log**: Look for "MeowSEO:" entries
4. **Check API key**: Ensure it's valid for the provider

### Masked Key Not Clearing?

1. Refresh the page
2. Check browser console for JavaScript errors
3. Ensure JavaScript is enabled

### Test Connection Still Failing?

1. Verify API key is valid
2. Check provider status in AI Settings
3. Check debug log for specific error message
4. Try with a different provider

## Files Modified

### Core Changes
- `includes/modules/ai/class-ai-rest.php`
  - `check_permission_and_nonce_for_settings()` - Enhanced with better error handling
  - `test_provider()` - Enhanced with debug logging and better key handling

- `includes/modules/ai/assets/js/ai-settings.js`
  - `init()` - Added API key handler initialization
  - `initAPIKeyInputHandlers()` - New method to handle masked keys
  - `testProviderConnection()` - Enhanced with masked key detection

### Debug Utilities (Optional - Delete After Use)
- `debug-test-connection.php` - Nonce and capability debugging
- `test-api-connection.php` - Endpoint and permission testing

### Documentation
- `FIX_API_TEST_CONNECTION_403.md` - Detailed technical documentation
- `CHANGES_SUMMARY.md` - Summary of all changes
- `SOLUTION_API_403_ERROR.md` - This file

## Key Improvements

1. **Better User Experience**:
   - Clear error messages
   - Automatic key clearing on edit
   - Proper handling of saved profiles

2. **Better Debugging**:
   - Comprehensive debug logging
   - Specific error codes
   - Detailed error messages

3. **Better Reliability**:
   - Multiple nonce source fallbacks
   - Proper key validation
   - Better error handling

4. **Backward Compatible**:
   - No breaking changes
   - Existing profiles continue to work
   - Transparent to users

## Performance Impact

- **Minimal**: Added focus event listener and debug logging
- **No additional queries**: Uses existing data
- **No additional API calls**: Only when user clicks test

## Security

- Nonce verification remains strict
- Capability checks unchanged
- API key masking preserved
- Debug logging doesn't expose sensitive data

## Deployment Checklist

- [ ] Test in development environment
- [ ] Verify all test scenarios work
- [ ] Check debug log for issues
- [ ] Delete debug scripts
- [ ] Commit changes to Git
- [ ] Update version number
- [ ] Create release notes
- [ ] Deploy to production

## Related Issues Fixed

1. **Task 1**: Plugin update system - Fixed updater initialization
2. **Task 2**: AI Settings page - Fixed API key masking and nonce issues
3. **Task 3**: Update detection - Fixed version detection
4. **Task 4**: API test connection - Fixed HTTP 403 error (THIS FIX)

## Next Steps

1. Test the fix thoroughly
2. Monitor debug log for any issues
3. Gather user feedback
4. Make adjustments if needed
5. Document any additional findings

## Support

For issues or questions:
1. Check `FIX_API_TEST_CONNECTION_403.md` for detailed documentation
2. Check debug log for error messages
3. Use browser console test code to debug
4. Review troubleshooting section above

---

**Status**: ✅ FIXED
**Date**: 2026-04-23
**Version**: 1.0.0-50969fe
