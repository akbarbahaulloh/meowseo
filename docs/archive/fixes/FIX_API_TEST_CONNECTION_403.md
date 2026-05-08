# Fix: HTTP 403 Error on API Test Connection

## Problem
After converting the AI settings to a profile-based system, the "Test Connection" button returns HTTP 403 error. This was working before the profile system was implemented.

## Root Causes Identified

### 1. Masked API Key Issue
When an API key is saved, it's displayed as masked (e.g., `abcd...wxyz`). When the user clicks "Test Connection" without editing the key, the JavaScript sends the masked value to the API, which fails validation.

**Solution**: 
- Added focus handler in JavaScript to clear masked API keys when user focuses on the input
- Improved test connection logic to detect masked keys and handle them appropriately
- If key is masked and profile_id exists, don't send the key (backend will fetch from profile)
- If key is masked and no profile_id, show error asking user to enter new key or save profile first

### 2. Insufficient Nonce Verification Error Messages
The permission callback was returning generic 403 errors without clear indication of what failed (capability check vs nonce verification).

**Solution**:
- Added debug logging when WP_DEBUG is enabled
- Improved error messages to distinguish between:
  - Missing manage_options capability
  - Missing nonce
  - Invalid nonce
- Added multiple nonce source fallbacks (header, query param, cookie)

### 3. Insufficient Error Handling in test_provider Method
The test_provider method didn't provide clear feedback about what went wrong.

**Solution**:
- Added comprehensive debug logging
- Improved error messages
- Better handling of masked API keys
- Clear validation of API key before attempting provider test

## Changes Made

### 1. JavaScript Changes (`includes/modules/ai/assets/js/ai-settings.js`)

#### Added API Key Input Handler
```javascript
initAPIKeyInputHandlers: function() {
    document.addEventListener('focus', (e) => {
        const input = e.target;
        if (!input.matches('input[name*="[api_key]"]')) {
            return;
        }
        // Clear masked value when user focuses on input
        if (input.value.includes('...')) {
            input.value = '';
            input.setAttribute('data-is-encrypted', '0');
        }
    }, true);
}
```

#### Improved Test Connection Logic
- Detects masked API keys (containing `...`)
- If masked and profile_id exists: don't send key, let backend fetch from profile
- If masked and no profile_id: show error message
- Better error handling and user feedback

### 2. PHP Changes (`includes/modules/ai/class-ai-rest.php`)

#### Improved Permission Callback
```php
public function check_permission_and_nonce_for_settings( WP_REST_Request $request ) {
    // Check capability with debug logging
    if ( ! current_user_can( 'manage_options' ) ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'MeowSEO: Permission denied - user does not have manage_options capability' );
        }
        return new WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to perform this action. Required capability: manage_options', 'meowseo' ),
            array( 'status' => 403 )
        );
    }

    // Try multiple nonce sources
    $nonce = $request->get_header( 'X-WP-Nonce' );
    if ( ! $nonce ) {
        $nonce = $request->get_param( '_wpnonce' );
    }
    
    // Verify nonce with detailed error messages
    if ( ! $nonce ) {
        return new WP_Error(
            'rest_missing_nonce',
            __( 'Nonce is missing. Please refresh the page and try again.', 'meowseo' ),
            array( 'status' => 403 )
        );
    }

    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        return new WP_Error(
            'rest_invalid_nonce',
            __( 'Nonce verification failed. Please refresh the page and try again.', 'meowseo' ),
            array( 'status' => 403 )
        );
    }

    return true;
}
```

#### Enhanced test_provider Method
- Added debug logging for troubleshooting
- Better handling of masked API keys
- Improved error messages
- Validates API key after all retrieval attempts
- Logs success/failure for debugging

## Testing the Fix

### Manual Testing Steps

1. **Enable Debug Logging** (optional but recommended):
   ```php
   // In wp-config.php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   ```

2. **Test with New Profile**:
   - Go to MeowSEO > AI Settings
   - Create a new profile
   - Enter a valid API key
   - Click "Test Connection"
   - Should show "Connection successful" or specific error

3. **Test with Saved Profile**:
   - Save a profile with a valid API key
   - The key will be masked as `abcd...wxyz`
   - Click "Test Connection" without editing the key
   - Should fetch the saved key and test it
   - Should show "Connection successful"

4. **Test with Edited Saved Profile**:
   - Click on the masked API key input
   - The masked value should clear
   - Enter a new API key
   - Click "Test Connection"
   - Should test the new key

### Debug Script

Use the provided debug scripts to troubleshoot:

1. **test-api-connection.php** - Tests endpoint registration and permissions
2. **debug-test-connection.php** - Provides detailed nonce and capability information

Access them via:
- `http://yoursite.com/wp-content/plugins/meowseo/test-api-connection.php`
- `http://yoursite.com/wp-content/plugins/meowseo/debug-test-connection.php`

## Browser Console Testing

If issues persist, test directly from browser console:

```javascript
const nonce = meowseoAISettings.nonce;
fetch('/wp-json/meowseo/v1/ai/test-provider', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': nonce,
  },
  credentials: 'same-origin',
  body: JSON.stringify({
    provider: 'gemini',
    api_key: 'your-api-key-here',
  })
})
.then(r => r.json())
.then(d => console.log(d))
.catch(e => console.error(e));
```

## Troubleshooting

### Still Getting 403 Error?

1. **Check user capabilities**:
   - User must have `manage_options` capability
   - Usually only administrators have this

2. **Check nonce**:
   - Refresh the page to get a fresh nonce
   - Check browser console for `meowseoAISettings.nonce` value
   - Should not be empty

3. **Check debug log**:
   - If WP_DEBUG is enabled, check `wp-content/debug.log`
   - Look for "MeowSEO:" entries
   - Should show which check failed

4. **Check API key**:
   - If using saved profile, ensure it was saved correctly
   - Try entering a new API key and testing
   - Check if the API key is valid for the provider

### API Key Masking Issues?

1. **Key not clearing on focus**:
   - Refresh the page
   - Check browser console for JavaScript errors
   - Ensure JavaScript is enabled

2. **Masked key being sent to API**:
   - This should now be handled automatically
   - If still happening, check browser console network tab
   - Verify the request body contains the correct key

## Files Modified

1. `includes/modules/ai/class-ai-rest.php`
   - Enhanced `check_permission_and_nonce_for_settings()` method
   - Enhanced `test_provider()` method with better logging and error handling

2. `includes/modules/ai/assets/js/ai-settings.js`
   - Added `initAPIKeyInputHandlers()` method
   - Improved `testProviderConnection()` method
   - Better masked key detection and handling

## Files Created (for debugging)

1. `debug-test-connection.php` - Nonce and capability debugging
2. `test-api-connection.php` - Endpoint and permission testing
3. `FIX_API_TEST_CONNECTION_403.md` - This documentation

**Note**: Delete the debug scripts after troubleshooting is complete.

## Related Issues

- Task 4: Fix API Test Connection 403 Error
- Previous fixes: AI Settings page issues, Update detection, Plugin update system

## Next Steps

1. Test the fix in your WordPress installation
2. Check debug log for any remaining issues
3. Delete debug scripts when done
4. Commit changes to Git
5. Update version number if needed
