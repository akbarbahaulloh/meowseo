# Perbaikan Duplikasi Method update_installed_commit

## Ringkasan Masalah
File `meowseo/includes/updater/class-git-hub-update-checker.php` memiliki **duplikasi method `update_installed_commit`** yang menyebabkan:
1. Confusion dalam kode
2. Inkonsistensi dalam penyimpanan commit SHA
3. Potensi bug dalam update tracking

## Duplikasi yang Ditemukan

### Method Pertama (DIHAPUS - SALAH)
**Lokasi**: Sekitar baris 700-720
```php
public function update_installed_commit( $upgrader_object, $options ) {
    // ...
    if ( $latest && ! empty( $latest['short_sha'] ) ) {
        // SALAH: Menyimpan short_sha (7 karakter)
        update_option( 'meowseo_installed_commit', sanitize_text_field( $latest['short_sha'] ) );
    }
}
```

**Masalah**: Menyimpan `short_sha` (7 karakter) ke database, padahal seharusnya menyimpan full SHA (40 karakter).

### Method Kedua (DIPERTAHANKAN - BENAR)
**Lokasi**: Sekitar baris 1300-1320
```php
public function update_installed_commit( $upgrader_object, $options ) {
    // ...
    if ( $latest && ! empty( $latest['sha'] ) ) {
        // BENAR: Menyimpan full SHA (40 karakter)
        update_option( 'meowseo_installed_commit', sanitize_text_field( $latest['sha'] ) );
        $this->logger->log_check( true, 'Updated installed commit to: ' . substr( $latest['sha'], 0, 7 ) );
    }
}
```

**Keunggulan**: 
- Menyimpan full SHA (40 karakter) ke database
- Memiliki logging yang lebih baik
- Menghapus cache dengan lebih konsisten

## Perbaikan yang Dilakukan

1. ✅ **Menghapus duplikasi method pertama** yang menyimpan short_sha
2. ✅ **Mempertahankan method kedua** yang menyimpan full SHA
3. ✅ **Verifikasi syntax PHP** - Tidak ada error
4. ✅ **Verifikasi hanya ada 1 method** - Duplikasi sudah dihapus

## Hasil Verifikasi

### Diagnostics
```
meowseo/includes/updater/class-git-hub-update-checker.php: No diagnostics found
```
✅ File tidak memiliki syntax error

### Method yang Tersisa
```php
public function update_installed_commit( $upgrader_object, $options ) {
    $plugin_basename = plugin_basename( $this->plugin_file );
    if ( 'update' === $options['action'] && 'plugin' === $options['type'] && ! empty( $options['plugins'] ) ) {
        if ( in_array( $plugin_basename, $options['plugins'], true ) ) {
            // Clear the cache to force a fresh fetch on next check.
            delete_transient( 'meowseo_github_update_info' );
            delete_transient( 'meowseo_github_changelog' );

            // Fetch the absolute newest commit (which we just installed via the main branch zip).
            // We force bypassing the cache.
            $latest = $this->get_latest_commit();

            if ( $latest && ! empty( $latest['sha'] ) ) {
                // Save full SHA to database so we know what is currently installed.
                update_option( 'meowseo_installed_commit', sanitize_text_field( $latest['sha'] ) );
                $this->logger->log_check( true, 'Updated installed commit to: ' . substr( $latest['sha'], 0, 7 ) );
            }
        }
    }
}
```

✅ Method yang benar menyimpan **full SHA (40 karakter)** ke database

## Dampak Perbaikan

### Sebelum Perbaikan
- Ada 2 method dengan nama sama
- Inconsistency dalam penyimpanan commit SHA
- Potensi bug dalam update tracking

### Sesudah Perbaikan
- Hanya ada 1 method `update_installed_commit`
- Konsisten menyimpan full SHA (40 karakter)
- Logging yang jelas untuk tracking
- Syntax PHP yang valid

## Testing Rekomendasi

1. Test update plugin dari GitHub
2. Verifikasi bahwa `meowseo_installed_commit` option menyimpan full SHA (40 karakter)
3. Verifikasi bahwa update detection bekerja dengan benar setelah update

## File yang Dimodifikasi
- `meowseo/includes/updater/class-git-hub-update-checker.php`
  - Dihapus: Duplikasi method pertama (baris ~700-720)
  - Dipertahankan: Method kedua yang benar (baris ~1300-1320)
