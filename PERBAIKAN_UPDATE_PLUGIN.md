# Perbaikan Sistem Update Plugin MeowSEO

## Masalah yang Diperbaiki

Plugin MeowSEO tidak bisa mendapatkan informasi update dari GitHub saat tombol pembaruan ditekan di dashboard WordPress.

### Akar Masalah

Setelah membandingkan dengan MeowPack yang berhasil, ditemukan bahwa:

1. **Inisialisasi Terlambat**: MeowSEO menginisialisasi updater pada hook `admin_init`, sedangkan WordPress sudah melakukan pengecekan update sebelum hook tersebut dipanggil.

2. **Pengecekan Capability Berlebihan**: Updater hanya diinisialisasi jika user memiliki capability `update_plugins`, sehingga background update checker WordPress tidak bisa berjalan.

3. **Hook Tidak Terdaftar**: Hook `pre_set_site_transient_update_plugins` tidak terdaftar saat WordPress melakukan pengecekan update.

## Perubahan yang Dilakukan

### 1. File: `meowseo/meowseo.php`

**Perubahan**: Menambahkan inisialisasi updater pada hook `plugins_loaded` dengan priority 5.

```php
// Initialize GitHub updater early (before WordPress checks for updates).
add_action( 'plugins_loaded', function() {
    // Only initialize if updater classes exist.
    if ( ! class_exists( 'MeowSEO\Updater\GitHub_Update_Checker' ) ) {
        return;
    }

    try {
        $config = new \MeowSEO\Updater\Update_Config();
        $logger = new \MeowSEO\Updater\Update_Logger();
        $checker = new \MeowSEO\Updater\GitHub_Update_Checker( MEOWSEO_FILE, $config, $logger );
        $checker->init();
        
        // Store in global for access by Plugin class and admin.
        $GLOBALS['meowseo_updater_checker'] = $checker;
        $GLOBALS['meowseo_updater_config'] = $config;
        $GLOBALS['meowseo_updater_logger'] = $logger;
        
        // Register settings page if in admin and user can manage options.
        if ( is_admin() && current_user_can( 'update_plugins' ) ) {
            add_action( 'admin_menu', function() use ( $config, $checker, $logger ) {
                $settings_page = new \MeowSEO\Updater\Update_Settings_Page( $config, $checker, $logger );
                $settings_page->register();
            }, 20 );
        }
    } catch ( \Exception $e ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'MeowSEO: Failed to initialize GitHub updater: ' . $e->getMessage() );
        }
    }
}, 5 ); // Priority 5 to run early, before most plugins
```

**Alasan**:
- Hook `plugins_loaded` dipanggil lebih awal dari `init` dan `admin_init`
- Priority 5 memastikan updater diinisialisasi sebelum plugin lain
- Updater diinisialisasi tanpa pengecekan capability, sehingga hook selalu terdaftar
- Settings page tetap hanya ditampilkan untuk user dengan capability yang sesuai

### 2. File: `includes/class-plugin.php`

**Perubahan 1**: Menghapus inisialisasi updater dari method `boot()` dan menggantinya dengan pengambilan instance dari global.

```php
// Initialize Admin interface (only in admin context).
if ( is_admin() ) {
    try {
        $this->admin = new Admin( $this->options, $this->module_manager );
        $this->admin->boot();

        // Get updater from global if it was initialized early.
        if ( isset( $GLOBALS['meowseo_updater_checker'] ) ) {
            $this->updater_checker = $GLOBALS['meowseo_updater_checker'];
        }
    } catch ( \Exception $e ) {
        // Log admin initialization error but don't break the plugin.
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'MeowSEO: Failed to initialize admin interface: ' . $e->getMessage() );
        }
    }
}
```

**Perubahan 2**: Mengubah method `initialize_updater()` menjadi deprecated dan hanya mengambil instance dari global.

```php
/**
 * Initialize the GitHub update system (DEPRECATED)
 *
 * This method is now deprecated. The updater is initialized early on plugins_loaded hook
 * in meowseo.php to ensure hooks are registered before WordPress checks for updates.
 *
 * @deprecated 1.0.1 Updater now initialized on plugins_loaded hook
 * @return void
 */
public function initialize_updater(): void {
    // This method is kept for backward compatibility but does nothing.
    // The updater is now initialized in meowseo.php on plugins_loaded hook.
    
    // Get updater from global if available.
    if ( isset( $GLOBALS['meowseo_updater_checker'] ) ) {
        $this->updater_checker = $GLOBALS['meowseo_updater_checker'];
    }
}
```

**Alasan**:
- Method `initialize_updater()` tidak lagi dipanggil, tapi tetap ada untuk backward compatibility
- Instance updater diambil dari global variable yang sudah diinisialisasi lebih awal
- Tidak ada breaking changes untuk kode yang mungkin memanggil method ini

## Cara Testing

### 1. Persiapan

Pastikan Anda sudah commit dan push perubahan ke GitHub repository `akbarbahaulloh/meowseo` branch `main`.

### 2. Clear Cache

Jalankan perintah berikut di WordPress (bisa melalui WP-CLI atau plugin seperti Query Monitor):

```php
// Clear update cache
delete_transient( 'meowseo_github_update_info' );
delete_transient( 'meowseo_github_changelog' );
delete_transient( 'meowseo_github_rate_limit' );
delete_option( 'meowseo_github_last_check' );

// Clear WordPress update cache
delete_site_transient( 'update_plugins' );
```

Atau bisa juga dengan menambahkan file PHP sementara di root WordPress:

```php
<?php
// clear-update-cache.php
require_once 'wp-load.php';

delete_transient( 'meowseo_github_update_info' );
delete_transient( 'meowseo_github_changelog' );
delete_transient( 'meowseo_github_rate_limit' );
delete_option( 'meowseo_github_last_check' );
delete_site_transient( 'update_plugins' );

echo "Cache cleared successfully!";
```

Akses file tersebut melalui browser, lalu hapus file setelah selesai.

### 3. Test Manual Update Check

1. Login ke WordPress admin
2. Buka halaman **Plugins** (`/wp-admin/plugins.php`)
3. Cari plugin **MeowSEO**
4. Klik link **"Cek Pembaruan"** di bawah nama plugin
5. Seharusnya muncul notice: "MeowSEO berhasil memeriksa pembaruan di GitHub..."
6. Jika ada update, tombol **"Update Now"** akan muncul di bawah deskripsi plugin

### 4. Test Automatic Update Check

1. Buka halaman **Dashboard > Updates** (`/wp-admin/update-core.php`)
2. Klik tombol **"Check Again"** di bagian atas
3. Jika ada update MeowSEO, akan muncul di daftar plugin updates

### 5. Verifikasi Hook Terdaftar

Tambahkan kode berikut di `functions.php` theme atau plugin testing:

```php
add_action( 'admin_init', function() {
    global $wp_filter;
    
    // Check if our hook is registered
    if ( isset( $wp_filter['pre_set_site_transient_update_plugins'] ) ) {
        $hooks = $wp_filter['pre_set_site_transient_update_plugins']->callbacks;
        
        foreach ( $hooks as $priority => $callbacks ) {
            foreach ( $callbacks as $callback ) {
                if ( is_array( $callback['function'] ) ) {
                    $class = get_class( $callback['function'][0] );
                    if ( strpos( $class, 'GitHub_Update_Checker' ) !== false ) {
                        echo '<div class="notice notice-success"><p>✓ MeowSEO updater hook is registered at priority ' . $priority . '</p></div>';
                        return;
                    }
                }
            }
        }
    }
    
    echo '<div class="notice notice-error"><p>✗ MeowSEO updater hook is NOT registered!</p></div>';
}, 999 );
```

### 6. Debug Logging

Jika masih ada masalah, aktifkan WP_DEBUG dan periksa log:

```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Log akan tersimpan di `wp-content/debug.log`. Cari baris yang mengandung "MeowSEO" untuk melihat error atau informasi updater.

## Troubleshooting

### Masalah: Tombol "Update Now" tidak muncul

**Solusi**:
1. Pastikan ada commit baru di GitHub yang berbeda dengan versi installed
2. Clear semua cache (lihat langkah 2 di atas)
3. Periksa `wp-content/debug.log` untuk error
4. Verifikasi repository settings di `includes/updater/class-update-config.php`:
   - `repo_owner`: `akbarbahaulloh`
   - `repo_name`: `meowseo`
   - `branch`: `main`

### Masalah: Error "Rate limit exceeded"

**Solusi**:
GitHub API memiliki rate limit 60 requests/hour untuk unauthenticated requests. Tunggu 1 jam atau tambahkan GitHub token (fitur belum diimplementasi).

### Masalah: Hook tidak terdaftar

**Solusi**:
1. Pastikan file `meowseo/meowseo.php` sudah diupdate dengan benar
2. Deactivate dan activate ulang plugin
3. Clear opcache jika menggunakan PHP opcache:
   ```php
   opcache_reset();
   ```

## Perbandingan Sebelum dan Sesudah

### Sebelum (❌ Tidak Bekerja)

```
WordPress Load
  └─> plugins_loaded
  └─> init
      └─> Plugin::boot()
          └─> admin_init
              └─> initialize_updater() ← TERLAMBAT!
                  └─> GitHub_Update_Checker::init()
                      └─> add_filter('pre_set_site_transient_update_plugins')
  └─> pre_set_site_transient_update_plugins ← Hook belum terdaftar!
```

### Sesudah (✅ Bekerja)

```
WordPress Load
  └─> plugins_loaded (priority 5)
      └─> GitHub_Update_Checker::init() ← LEBIH AWAL!
          └─> add_filter('pre_set_site_transient_update_plugins')
  └─> init
      └─> Plugin::boot()
  └─> admin_init
  └─> pre_set_site_transient_update_plugins ← Hook sudah terdaftar!
```

## Kesimpulan

Perbaikan ini mengikuti pola yang sama dengan MeowPack yang sudah terbukti berhasil:
- Inisialisasi updater lebih awal (plugins_loaded priority 5)
- Tidak ada pembatasan capability untuk inisialisasi hook
- Hook terdaftar sebelum WordPress melakukan pengecekan update

Dengan perubahan ini, MeowSEO seharusnya bisa mendapatkan informasi update dari GitHub dengan benar.
