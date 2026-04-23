# 🧪 Panduan Testing Update MeowSEO

## 📋 Daftar File

Setelah perbaikan, berikut file-file yang tersedia:

### File Dokumentasi
1. **PERBANDINGAN_MEOWSEO_VS_MEOWPACK.md** - Analisis detail perbedaan implementasi
2. **PERBAIKAN_UPDATE_PLUGIN.md** - Dokumentasi lengkap perubahan yang dilakukan
3. **README_TESTING_UPDATE.md** - File ini (panduan testing)

### File Testing
1. **test-updater-hooks.php** - Script untuk verifikasi hook terdaftar
2. **clear-update-cache.php** - Script untuk clear cache update

### File yang Diubah
1. **meowseo/meowseo.php** - Inisialisasi updater dipindah ke `plugins_loaded`
2. **includes/class-plugin.php** - Method `initialize_updater()` diubah

---

## 🚀 Langkah-langkah Testing

### Langkah 1: Upload File Testing

Upload 2 file testing ke root WordPress (folder yang sama dengan `wp-load.php`):
- `test-updater-hooks.php`
- `clear-update-cache.php`

### Langkah 2: Clear Cache

1. Akses: `http://yoursite.com/clear-update-cache.php`
2. Verifikasi bahwa cache berhasil dibersihkan
3. Periksa status updater dan informasi commit

**Yang harus terlihat:**
- ✓ Cache berhasil dibersihkan
- ✓ Updater berhasil diinisialisasi
- ✓ Hook terdaftar
- Informasi commit terbaru dari GitHub

### Langkah 3: Verifikasi Hooks

1. Akses: `http://yoursite.com/test-updater-hooks.php`
2. Periksa tabel hooks yang terdaftar

**Yang harus terlihat:**
- ✓ Updater Instance Found
- ✓ Global Variable Set
- ✓ MeowSEO updater hook is registered (dengan highlight kuning)
- Tabel menampilkan `MeowSEO\Updater\GitHub_Update_Checker::check_for_update()`

### Langkah 4: Test Manual Update Check

1. Login ke WordPress admin
2. Buka **Plugins** (`/wp-admin/plugins.php`)
3. Cari plugin **MeowSEO**
4. Klik link **"Cek Pembaruan"**

**Yang harus terjadi:**
- Muncul notice: "MeowSEO berhasil memeriksa pembaruan di GitHub..."
- Jika ada commit baru di GitHub, tombol **"Update Now"** akan muncul

### Langkah 5: Test Automatic Update Check

1. Buka **Dashboard > Updates** (`/wp-admin/update-core.php`)
2. Klik tombol **"Check Again"**

**Yang harus terjadi:**
- WordPress melakukan pengecekan update untuk semua plugin
- Jika ada update MeowSEO, akan muncul di daftar

### Langkah 6: Cleanup

**PENTING!** Hapus file testing setelah selesai:
```bash
rm test-updater-hooks.php
rm clear-update-cache.php
```

Atau hapus manual melalui FTP/File Manager.

---

## ✅ Checklist Verifikasi

Gunakan checklist ini untuk memastikan semua berjalan dengan baik:

- [ ] File `meowseo/meowseo.php` sudah diupdate
- [ ] File `includes/class-plugin.php` sudah diupdate
- [ ] Cache sudah dibersihkan
- [ ] Hook `pre_set_site_transient_update_plugins` terdaftar
- [ ] Updater instance tersedia di `$GLOBALS['meowseo_updater_checker']`
- [ ] Link "Cek Pembaruan" berfungsi
- [ ] Notice "berhasil memeriksa pembaruan" muncul
- [ ] Tombol "Update Now" muncul (jika ada update)
- [ ] File testing sudah dihapus

---

## 🐛 Troubleshooting

### Masalah: Hook tidak terdaftar

**Gejala:**
- `test-updater-hooks.php` menampilkan "✗ MeowSEO updater hook is NOT registered!"

**Solusi:**
1. Pastikan file `meowseo/meowseo.php` sudah diupdate dengan benar
2. Deactivate dan activate ulang plugin MeowSEO
3. Clear opcache jika menggunakan:
   ```php
   <?php opcache_reset(); ?>
   ```
4. Periksa `wp-content/debug.log` untuk error

### Masalah: Updater instance null

**Gejala:**
- `clear-update-cache.php` menampilkan "⚠️ Updater belum diinisialisasi!"

**Solusi:**
1. Periksa apakah ada error di `wp-content/debug.log`
2. Aktifkan WP_DEBUG di `wp-config.php`:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   ```
3. Refresh halaman dan periksa log lagi
4. Pastikan class `GitHub_Update_Checker` bisa di-load

### Masalah: Gagal mengambil info dari GitHub

**Gejala:**
- "⚠️ Gagal mengambil informasi dari GitHub"

**Solusi:**
1. Periksa koneksi internet server
2. Cek rate limit GitHub API (60 requests/hour untuk unauthenticated)
3. Tunggu 1 jam jika rate limit exceeded
4. Verifikasi repository settings:
   - Owner: `akbarbahaulloh`
   - Repo: `meowseo`
   - Branch: `main`

### Masalah: Tombol "Update Now" tidak muncul

**Gejala:**
- Hook terdaftar, tapi tombol update tidak muncul

**Solusi:**
1. Pastikan ada commit baru di GitHub yang berbeda dengan installed version
2. Periksa option `meowseo_installed_commit`:
   ```php
   <?php
   echo get_option( 'meowseo_installed_commit' );
   ?>
   ```
3. Bandingkan dengan commit terbaru di GitHub
4. Jika sama, berarti sudah versi terbaru (tidak ada update)

---

## 📊 Expected Output

### test-updater-hooks.php

```
✓ Updater Instance Found: MeowSEO\Updater\GitHub_Update_Checker
✓ Global Variable Set: $GLOBALS['meowseo_updater_checker'] exists

Registered Hooks on 'pre_set_site_transient_update_plugins'
┌──────────┬────────────────────────────────────────────────┬─────────────────────────────────────┬──────────┐
│ Priority │ Callback                                       │ Class/Function                      │ Status   │
├──────────┼────────────────────────────────────────────────┼─────────────────────────────────────┼──────────┤
│ 10       │ MeowSEO\Updater\GitHub_Update_Checker::check_f│ MeowSEO\Updater\GitHub_Update_Check │ MeowSEO  │
│          │ or_update()                                    │ er                                  │          │
└──────────┴────────────────────────────────────────────────┴─────────────────────────────────────┴──────────┘

✓ MeowSEO updater hook is registered!
```

### clear-update-cache.php

```
✓ Cache berhasil dibersihkan:
  • meowseo_github_update_info (transient)
  • meowseo_github_changelog (transient)
  • meowseo_github_rate_limit (transient)
  • meowseo_github_last_check (option)
  • update_plugins (site transient)

Status Updater
✓ Updater berhasil diinisialisasi
✓ Hook terdaftar pada priority 10

Informasi Versi
Current Installed Commit: b1b0d0d
Plugin Version: 1.0.0-b1b0d0d

Latest GitHub Commit: a1b2c3d
Commit Message: Fix update system
Author: Akbar Bahaulloh
Date: 2024-01-15T10:30:00Z

🎉 Update tersedia!
Versi baru: a1b2c3d
```

---

## 📝 Catatan Penting

1. **Rate Limit GitHub API**: 60 requests/hour untuk unauthenticated requests
2. **Cache Duration**: Update info di-cache selama 1 jam (3600 detik)
3. **Background Updates**: WordPress melakukan pengecekan otomatis setiap 12 jam
4. **Security**: Hapus file testing setelah selesai!

---

## 🎯 Kesimpulan

Jika semua langkah di atas berhasil, berarti:

✅ Updater MeowSEO sudah bekerja dengan benar
✅ Hook terdaftar sebelum WordPress check update
✅ Plugin bisa mendapatkan informasi update dari GitHub
✅ Tombol "Update Now" akan muncul jika ada update

Perbaikan ini mengikuti pola yang sama dengan MeowPack yang sudah terbukti berhasil!

---

## 📞 Support

Jika masih ada masalah setelah mengikuti panduan ini:

1. Periksa `wp-content/debug.log` untuk error details
2. Verifikasi semua file sudah diupdate dengan benar
3. Pastikan tidak ada plugin lain yang conflict
4. Coba deactivate plugin lain sementara untuk testing

**File Dokumentasi Lengkap:**
- `PERBANDINGAN_MEOWSEO_VS_MEOWPACK.md` - Analisis teknis
- `PERBAIKAN_UPDATE_PLUGIN.md` - Detail perubahan kode
