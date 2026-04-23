# Analisis Masalah Update MeowSEO

## Masalah yang Ditemukan

### 1. Inisialisasi Updater Terlambat
**Lokasi**: `includes/class-plugin.php` baris 136-140

```php
// Initialize GitHub Update System (only if user can update plugins).
if ( current_user_can( 'update_plugins' ) ) {
    add_action( 'admin_init', array( $this, 'initialize_updater' ) );
}
```

**Masalah**:
- Updater hanya diinisialisasi pada hook `admin_init`
- Hook `pre_set_site_transient_update_plugins` yang digunakan oleh updater mungkin sudah dipanggil sebelum `admin_init`
- WordPress melakukan pengecekan update pada berbagai waktu, tidak hanya saat admin_init

### 2. Kondisi Capability yang Terlalu Ketat
**Lokasi**: `includes/class-plugin.php` baris 138 dan 163

```php
if ( current_user_can( 'update_plugins' ) ) {
    add_action( 'admin_init', array( $this, 'initialize_updater' ) );
}

public function initialize_updater(): void {
    // Only initialize if user has update_plugins capability.
    if ( ! current_user_can( 'update_plugins' ) ) {
        return;
    }
```

**Masalah**:
- Pengecekan capability dilakukan 2 kali
- Jika user tidak login atau tidak punya capability, updater tidak akan pernah diinisialisasi
- WordPress background update checker mungkin tidak berjalan dengan user context

### 3. Timing Issue dengan WordPress Update Checker
WordPress melakukan pengecekan update pada:
- Background cron job (wp_update_plugins)
- Manual check dari dashboard
- Saat membuka halaman plugins

Hook `pre_set_site_transient_update_plugins` dipanggil saat WordPress menyimpan hasil pengecekan update. Jika updater belum diinisialisasi saat hook ini dipanggil, plugin tidak akan mendapatkan informasi update.

## Solusi yang Disarankan

### Solusi 1: Inisialisasi Lebih Awal (RECOMMENDED)
Pindahkan inisialisasi updater ke hook `plugins_loaded` atau langsung di constructor Plugin, bukan di `admin_init`.

```php
// Di includes/class-plugin.php, method boot()
public function boot(): void {
    try {
        // Initialize Logger singleton early
        Logger::get_instance();

        // Initialize GitHub Update System EARLY (before admin_init)
        // This ensures the updater hooks are registered before WordPress checks for updates
        $this->initialize_updater();

        // Initialize Module_Manager
        $this->module_manager = new Module_Manager( $this->options );
        
        // ... rest of boot code
    }
}
```

### Solusi 2: Hapus Pengecekan Capability Ganda
Cukup cek capability di dalam method `initialize_updater()`, tidak perlu di luar.

```php
// Di includes/class-plugin.php
public function initialize_updater(): void {
    // Only initialize if in admin context and user can update plugins
    if ( ! is_admin() ) {
        return;
    }
    
    // Allow background updates even without user context
    if ( ! wp_doing_cron() && ! current_user_can( 'update_plugins' ) ) {
        return;
    }

    try {
        // ... rest of initialization
    }
}
```

### Solusi 3: Pastikan Hook Terdaftar Sebelum WordPress Check
Gunakan hook dengan prioritas tinggi untuk memastikan updater terdaftar lebih dulu.

```php
// Di includes/updater/class-git-hub-update-checker.php
public function init(): void {
    // Use priority 1 to ensure we hook before WordPress checks
    add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ), 1 );
    
    // ... rest of hooks
}
```

## Perbandingan dengan MeowPack (Asumsi)

Kemungkinan besar MeowPack:
1. Menginisialisasi updater lebih awal (di `plugins_loaded` atau langsung di boot)
2. Tidak membatasi inisialisasi hanya untuk user dengan capability tertentu
3. Menggunakan prioritas hook yang lebih tinggi
4. Mungkin menggunakan approach yang berbeda untuk mendeteksi update

## Langkah Testing

1. Cek apakah hook `pre_set_site_transient_update_plugins` terdaftar:
   ```php
   global $wp_filter;
   var_dump($wp_filter['pre_set_site_transient_update_plugins']);
   ```

2. Cek apakah updater diinisialisasi:
   ```php
   $plugin = \MeowSEO\Plugin::instance();
   $checker = $plugin->get_updater_checker();
   var_dump($checker); // Should not be null
   ```

3. Cek apakah method check_for_update dipanggil:
   - Tambahkan logging di awal method check_for_update
   - Trigger manual update check dari dashboard

## Rekomendasi Implementasi

Implementasikan Solusi 1 + Solusi 2 + Solusi 3 secara bersamaan untuk hasil terbaik.
