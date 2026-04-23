# Fix: GitHub Update System Not Working

## Problem
Update dari GitHub tidak terdeteksi di MeowSEO, meskipun ada commit baru di GitHub. Tombol "Update Now" tidak muncul di plugin list.

**Gejala:**
- Tidak ada notifikasi update di plugin list
- Harus manual `git pull` via SSH terminal
- MeowPack berfungsi dengan baik, MeowSEO tidak

## Root Causes Identified

### 1. **Menyimpan Short SHA (7 karakter) Bukan Full SHA (40 karakter)**
**Masalah Utama**: 
- MeowSEO menyimpan short SHA (7 karakter) ke database
- GitHub API mengembalikan full SHA (40 karakter)
- Perbandingan: `abc1234` ≠ `abc1234567890abcdef1234567890abcdef1234` → Selalu tidak cocok!

**Contoh:**
```
Current commit (dari database): abc1234 (7 karakter)
Latest commit (dari GitHub): abc1234567890abcdef1234567890abcdef1234 (40 karakter)
Perbandingan: abc1234 !== abc1234567890abcdef1234567890abcdef1234
Hasil: Tidak ada update (SALAH!)
```

### 2. **Duplikasi Method `update_installed_commit`**
- Ada 2 method dengan nama sama
- Method pertama: Menyimpan short SHA (SALAH)
- Method kedua: Menyimpan full SHA (BENAR)
- Inkonsistensi menyebabkan bug

### 3. **Tidak Ada "Check for Update" Link**
- MeowPack memiliki link "Cek Pembaruan" di plugin list
- MeowSEO tidak memiliki link ini
- User harus menunggu 12 jam untuk update check otomatis

## Solutions Implemented

### 1. **Perbaiki Penyimpanan SHA**
**File**: `includes/updater/class-git-hub-update-checker.php`

**Sebelum (SALAH):**
```php
// Menyimpan short SHA (7 karakter)
update_option( 'meowseo_installed_commit', sanitize_text_field( $latest['short_sha'] ) );
```

**Sesudah (BENAR):**
```php
// Menyimpan full SHA (40 karakter)
update_option( 'meowseo_installed_commit', sanitize_text_field( $latest['sha'] ) );
```

### 2. **Hapus Duplikasi Method**
- Menghapus method pertama yang salah
- Mempertahankan method kedua yang benar
- Hanya ada 1 method `update_installed_commit` sekarang

### 3. **Tambah "Check for Update" Link**
**File**: `includes/updater/class-git-hub-update-checker.php`

**Method Baru:**
```php
public function add_manual_check_link( $links ) {
    $check_link = array(
        '<a href="' . esc_url( admin_url( 'plugins.php?meowseo_check_update=1' ) ) . '">' 
        . __( 'Cek Pembaruan', 'meowseo' ) . '</a>',
    );
    return array_merge( $check_link, $links );
}

public function handle_manual_check() {
    if ( ! is_admin() || ! isset( $_GET['meowseo_check_update'] ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    // Clear cache dan force update check
    delete_transient( 'meowseo_github_update_info' );
    delete_transient( 'meowseo_github_changelog' );
    delete_site_transient( 'update_plugins' );
    wp_safe_redirect( admin_url( 'plugins.php?meowseo_update_checked=1' ) );
    exit;
}

public function display_update_notice() {
    if ( isset( $_GET['meowseo_update_checked'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' 
            . esc_html__( 'MeowSEO berhasil memeriksa pembaruan di GitHub. Jika ada versi baru, tombol "Update Now" akan muncul di bawah deskripsi plugin.', 'meowseo' ) 
            . '</p></div>';
    }
}
```

### 4. **Perbaiki Logika Perbandingan SHA**
**Sebelum (SALAH):**
```php
// Membandingkan short SHA dengan short SHA
$latest_commit = $latest_commit_data['short_sha'] ?? '';
if ( ! $this->is_update_available( $current_commit, $latest_commit ) ) {
    // Tidak ada update
}
```

**Sesudah (BENAR):**
```php
// Membandingkan full SHA dengan full SHA
$latest_commit = $latest_commit_data['sha'] ?? '';
if ( ! $this->is_update_available( $current_commit, $latest_commit ) ) {
    // Tidak ada update
}
```

## How It Works Now

### Update Detection Flow
```
1. WordPress checks for updates (every 12 hours or manual)
   ↓
2. MeowSEO calls check_for_update()
   ↓
3. Get current commit from database (full SHA, 40 chars)
   ↓
4. Get latest commit from GitHub API (full SHA, 40 chars)
   ↓
5. Compare: abc1234567890abcdef1234567890abcdef1234 === abc1234567890abcdef1234567890abcdef1234
   ↓
6. If different → Update available! Show "Update Now" button
   ↓
7. User clicks "Update Now"
   ↓
8. Download ZIP from GitHub
   ↓
9. Extract and install
   ↓
10. Save new commit SHA to database
    ↓
11. Next check: No update (commits match)
```

### Manual Check Flow
```
1. User clicks "Cek Pembaruan" link in plugin list
   ↓
2. Clear all update caches
   ↓
3. Force WordPress to re-check all plugins
   ↓
4. Redirect back with success notice
   ↓
5. WordPress checks for updates immediately
   ↓
6. If update available, show "Update Now" button
```

## Testing the Fix

### Test 1: Manual Update Check
1. Go to Plugins page
2. Look for "Cek Pembaruan" link under MeowSEO
3. Click it
4. Should see success notice: "MeowSEO berhasil memeriksa pembaruan di GitHub..."
5. If update available, "Update Now" button should appear

### Test 2: Verify SHA Storage
1. Make a new commit on GitHub
2. Click "Cek Pembaruan"
3. Check database option `meowseo_installed_commit`
4. Should contain full SHA (40 characters), not short SHA (7 characters)

### Test 3: Update Installation
1. Make sure there's a new commit on GitHub
2. Click "Cek Pembaruan"
3. Click "Update Now"
4. Wait for update to complete
5. Verify new commit SHA is saved to database
6. Next check should show no update available

### Test 4: Compare with MeowPack
1. Both should have "Cek Pembaruan" link
2. Both should show update notification when new commit available
3. Both should save full SHA to database

## Files Modified

### Core Changes
- `includes/updater/class-git-hub-update-checker.php`
  - Fixed SHA comparison logic (use full SHA, not short SHA)
  - Removed duplicate `update_installed_commit` method
  - Added `add_manual_check_link()` method
  - Added `handle_manual_check()` method
  - Added `display_update_notice()` method
  - Updated `init()` method to register new hooks

### Documentation
- `FIX_GITHUB_UPDATE_SYSTEM.md` - This file
- `PERBAIKAN_DUPLIKASI_METHOD.md` - Duplikasi method fix details

## Comparison with MeowPack

### MeowPack (Working)
```php
$remote_sha = sanitize_text_field( $latest_commit->sha );  // Full SHA
$local_sha  = get_option( 'meowpack_installed_sha', '' );  // Full SHA
if ( $remote_sha !== $local_sha ) {
    // Update available!
}
```

### MeowSEO (Before Fix - BROKEN)
```php
$latest_commit = $latest_commit_data['short_sha'] ?? '';  // Short SHA (7 chars)
$current_commit = $this->get_current_commit_id();         // Short SHA (7 chars)
if ( ! $this->is_update_available( $current_commit, $latest_commit ) ) {
    // PROBLEM: Comparing short SHA with short SHA
    // But database might have full SHA from version string!
}
```

### MeowSEO (After Fix - WORKING)
```php
$latest_commit = $latest_commit_data['sha'] ?? '';        // Full SHA (40 chars)
$current_commit = $this->get_current_commit_id();         // Full SHA (40 chars)
if ( ! $this->is_update_available( $current_commit, $latest_commit ) ) {
    // CORRECT: Comparing full SHA with full SHA
}
```

## Troubleshooting

### Update Still Not Showing?

1. **Clear all caches:**
   ```php
   // In WordPress admin, run this in a custom plugin or theme functions.php
   delete_transient( 'meowseo_github_update_info' );
   delete_transient( 'meowseo_github_changelog' );
   delete_site_transient( 'update_plugins' );
   delete_option( 'meowseo_github_last_check' );
   ```

2. **Check database:**
   - Go to wp_options table
   - Look for `meowseo_installed_commit`
   - Should contain 40-character SHA, not 7-character

3. **Check GitHub API:**
   - Verify repository is public
   - Verify branch name is correct (usually "main" or "master")
   - Check GitHub API rate limit (60 requests/hour for unauthenticated)

4. **Enable debug logging:**
   ```php
   // In wp-config.php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   ```
   - Check wp-content/debug.log for "MeowSEO:" entries

### "Cek Pembaruan" Link Not Showing?

1. Verify plugin is activated
2. Verify user has `manage_options` capability
3. Check browser console for JavaScript errors
4. Clear browser cache

### Update Installation Fails?

1. Check file permissions on wp-content/plugins/meowseo/
2. Check disk space
3. Check PHP memory limit
4. Check debug log for specific error

## Performance Impact

- **Minimal**: No additional database queries
- **Caching**: Uses WordPress transients (12 hours default)
- **API calls**: Only when cache expires or manual check
- **No impact** on frontend performance

## Security Considerations

- Uses HTTPS for GitHub API calls
- Verifies SSL certificates
- Sanitizes all database values
- Checks user capabilities before allowing manual check
- No sensitive data exposed in logs

## Next Steps

1. ✅ Test update detection with new GitHub commit
2. ✅ Test manual "Cek Pembaruan" link
3. ✅ Test update installation
4. ✅ Verify SHA storage in database
5. ✅ Compare behavior with MeowPack
6. ✅ Monitor debug log for any issues
7. ✅ Commit changes to Git
8. ✅ Update version number if needed

## Related Issues Fixed

- Task 1: Plugin update system - Fixed updater initialization
- Task 3: Update detection - Fixed version detection
- Task 4: API test connection - Fixed HTTP 403 error
- **Task 5**: GitHub update system - Fixed SHA comparison (THIS FIX)

---

**Status**: ✅ FIXED
**Date**: 2026-04-23
**Version**: 1.0.0-50969fe
