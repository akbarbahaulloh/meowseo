# Debug Log untuk API Connection Test

## Overview
Fitur debug log real-time yang menampilkan proses lengkap saat testing API connection di browser. Ini membantu mengidentifikasi di mana masalahnya: di web kita, di provider (Google/OpenAI/dll), atau terhalang firewall.

## Fitur

### 1. **Real-time Debug Log**
- Menampilkan setiap langkah proses test connection
- Format seperti terminal dengan emoji untuk mudah dibaca
- Bisa di-expand/collapse untuk menghemat ruang

### 2. **Informasi Detail**
- Timestamp test
- Provider yang ditest
- Panjang API key
- Status setiap validasi
- HTTP response code (jika ada error)
- Pesan error detail dari provider

### 3. **Visual Indicators**
- ✅ / ✓ = Success (hijau)
- ❌ = Error (merah)
- ⚠️  = Warning (orange)
- → = Process step (biru)
- === = Section header (bold)

## Cara Menggunakan

### 1. Test API Connection
1. Buka MeowSEO > AI Settings
2. Masukkan API key atau pilih profile yang sudah disimpan
3. Klik tombol "Test Connection"
4. Tunggu proses selesai

### 2. Lihat Debug Log
1. Setelah test selesai, akan muncul tombol "Show Debug Log"
2. Klik tombol tersebut untuk melihat log detail
3. Log akan menampilkan semua langkah proses
4. Klik "Hide Debug Log" untuk menyembunyikan

## Contoh Output

### Success Case
```
=== MeowSEO API Connection Test ===
Time: 2026-04-23 15:30:45
Provider: gemini
Profile ID: profile_abc123
API Key provided: yes (39 chars)

✓ Provider validation passed

→ Fetching API key from saved profile...
✓ Profile found and API key retrieved
Provider from profile: gemini
API key length: 39 chars
✓ API key validation passed

→ Getting provider instance...
✓ Provider instance created: MeowSEO\Modules\AI\Providers\Provider_Gemini

→ Testing API connection to gemini...
Calling validate_api_key() method...

✅ SUCCESS: Connection test passed!
Provider: gemini
Status: Connected
```

### Error Case (HTTP 403)
```
=== MeowSEO API Connection Test ===
Time: 2026-04-23 15:32:10
Provider: gemini
Profile ID: profile_abc123
API Key provided: yes (39 chars)

✓ Provider validation passed

→ Fetching API key from saved profile...
✓ Profile found and API key retrieved
Provider from profile: gemini
API key length: 39 chars
✓ API key validation passed

→ Getting provider instance...
✓ Provider instance created: MeowSEO\Modules\AI\Providers\Provider_Gemini

→ Testing API connection to gemini...
Calling validate_api_key() method...

❌ FAILED: Connection test failed
Error from provider: API request failed with status 403
HTTP Response Code: 403

⚠️  HTTP 403 Forbidden - Possible causes:
  1. API key is invalid or expired
  2. API key does not have required permissions
  3. IP address is blocked by provider
  4. Rate limit exceeded
  5. Firewall blocking the request
```

### Error Case (Empty API Key)
```
=== MeowSEO API Connection Test ===
Time: 2026-04-23 15:33:25
Provider: gemini
Profile ID: none
API Key provided: no

✓ Provider validation passed

❌ ERROR: API key is empty after all attempts
```

## Troubleshooting dengan Debug Log

### HTTP 403 Forbidden
**Kemungkinan Penyebab:**
1. **API key invalid** - Cek di console provider (Google AI Studio, OpenAI, dll)
2. **Permissions kurang** - Pastikan API key punya akses ke endpoint yang dibutuhkan
3. **IP blocked** - Provider mungkin memblokir IP server Anda
4. **Rate limit** - Terlalu banyak request dalam waktu singkat
5. **Firewall** - Server firewall memblokir outgoing request

**Solusi:**
- Regenerate API key di provider console
- Cek quota dan billing di provider
- Whitelist IP server di provider (jika ada fitur)
- Tunggu beberapa menit jika rate limit
- Cek firewall server dengan `curl` test

### HTTP 401 Unauthorized
**Penyebab:**
- API key salah atau expired

**Solusi:**
- Copy-paste ulang API key dari provider
- Pastikan tidak ada spasi di awal/akhir
- Generate API key baru

### HTTP 429 Too Many Requests
**Penyebab:**
- Rate limit exceeded

**Solusi:**
- Tunggu beberapa menit
- Cek quota di provider console
- Upgrade plan jika perlu

### Provider Instance Not Found
**Penyebab:**
- Provider class tidak ada atau error saat instantiate

**Solusi:**
- Cek file provider ada di `includes/modules/ai/providers/`
- Cek syntax error di provider class
- Cek autoloader

### Profile Not Found
**Penyebab:**
- Profile ID tidak ada di database

**Solusi:**
- Save profile dulu sebelum test
- Cek database wp_options untuk `ai_profiles`

## Technical Details

### Backend (PHP)
**File**: `includes/modules/ai/class-ai-rest.php`

**Method**: `test_provider()`

**Debug Log Array**:
```php
$debug_log = array();
$debug_log[] = '=== MeowSEO API Connection Test ===';
$debug_log[] = 'Time: ' . current_time( 'Y-m-d H:i:s' );
// ... more log entries
```

**Response Format**:
```php
return new WP_REST_Response(
    array(
        'success' => true/false,
        'data'    => array(
            'valid'   => true/false,
            'status'  => 'connected'/'error',
            'message' => 'Success/error message',
            'debug_log' => $debug_log,  // Array of log lines
        ),
    ),
    200
);
```

### Frontend (JavaScript)
**File**: `includes/modules/ai/assets/js/ai-settings.js`

**Method**: `showDebugLog(provider, debugLog)`

**Features**:
- Creates expandable debug log container
- Formats log with colors based on emoji
- Escapes HTML to prevent XSS
- Toggle button to show/hide

**Color Coding**:
```javascript
if (line.startsWith('✅') || line.startsWith('✓')) {
    return `<span style="color: #46b450;">${line}</span>`;  // Green
} else if (line.startsWith('❌')) {
    return `<span style="color: #dc3232;">${line}</span>`;  // Red
} else if (line.startsWith('⚠️')) {
    return `<span style="color: #f56e28;">${line}</span>`;  // Orange
} else if (line.startsWith('→')) {
    return `<span style="color: #0073aa;">${line}</span>`;  // Blue
}
```

## Testing

### Manual Test
1. Go to MeowSEO > AI Settings
2. Enter invalid API key
3. Click "Test Connection"
4. Should see error with debug log
5. Click "Show Debug Log"
6. Should see detailed error information

### Test Different Scenarios
1. **Valid API key** - Should show success
2. **Invalid API key** - Should show HTTP 401/403
3. **Empty API key** - Should show validation error
4. **Masked key with profile** - Should fetch from database
5. **Masked key without profile** - Should show error

## Benefits

### For Users
- ✅ Tahu persis di mana masalahnya
- ✅ Tidak perlu akses SSH atau debug.log
- ✅ Bisa screenshot dan kirim ke support
- ✅ Troubleshooting lebih cepat

### For Developers
- ✅ Debugging lebih mudah
- ✅ Tidak perlu cek server log
- ✅ Bisa reproduce issue dengan mudah
- ✅ Support user lebih efisien

## Security Considerations

- ✅ API key tidak ditampilkan di log (hanya panjangnya)
- ✅ HTML di-escape untuk prevent XSS
- ✅ Log hanya tampil untuk user yang test
- ✅ Tidak disimpan di database
- ✅ Hilang setelah page refresh

## Future Improvements

1. **Export log** - Download log sebagai text file
2. **Copy to clipboard** - Copy log untuk paste ke support
3. **Persistent log** - Simpan log di session storage
4. **More details** - Tambah info server (PHP version, curl version, dll)
5. **Network test** - Test connectivity ke provider domain

## Files Modified

- `includes/modules/ai/class-ai-rest.php` - Added debug logging
- `includes/modules/ai/assets/js/ai-settings.js` - Added debug log display
- `DEBUG_LOG_API_TEST.md` - This documentation

---

**Status**: ✅ IMPLEMENTED
**Date**: 2026-04-23
**Version**: 1.0.0-50969fe
