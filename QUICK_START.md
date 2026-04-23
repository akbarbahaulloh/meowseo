# 🚀 Quick Start - Testing Update MeowSEO

## ⚡ 3 Langkah Cepat

### 1️⃣ Upload File Testing (1 menit)

Upload 2 file ini ke root WordPress (folder yang sama dengan `wp-load.php`):

```
📁 WordPress Root/
├── wp-load.php
├── test-updater-hooks.php      ← Upload ini
└── clear-update-cache.php      ← Upload ini
```

### 2️⃣ Clear Cache & Verifikasi (2 menit)

**A. Clear Cache:**
```
http://yoursite.com/clear-update-cache.php
```

**Harus muncul:**
- ✓ Cache berhasih dibersihkan
- ✓ Updater berhasil diinisialisasi
- ✓ Hook terdaftar pada priority 10

**B. Verifikasi Hooks:**
```
http://yoursite.com/test-updater-hooks.php
```

**Harus muncul:**
- ✓ Updater Instance Found
- ✓ Global Variable Set
- ✓ MeowSEO updater hook is registered
- Tabel dengan `GitHub_Update_Checker::check_for_update()`

### 3️⃣ Test Update (2 menit)

**A. Manual Check:**
1. Buka: `/wp-admin/plugins.php`
2. Cari plugin **MeowSEO**
3. Klik **"Cek Pembaruan"**
4. Harus muncul notice: "MeowSEO berhasil memeriksa pembaruan..."
5. Jika ada update, tombol **"Update Now"** akan muncul

**B. Automatic Check:**
1. Buka: `/wp-admin/update-core.php`
2. Klik **"Check Again"**
3. MeowSEO akan muncul di daftar jika ada update

---

## ✅ Checklist Sukses

- [ ] File testing sudah diupload
- [ ] Cache berhasil dibersihkan
- [ ] Hook terdaftar (terlihat di test-updater-hooks.php)
- [ ] Notice "berhasil memeriksa pembaruan" muncul
- [ ] Tombol "Update Now" muncul (jika ada update)
- [ ] **File testing sudah dihapus** ⚠️ PENTING!

---

## 🐛 Troubleshooting Cepat

### ❌ Hook tidak terdaftar
```bash
# Deactivate & activate plugin
wp plugin deactivate meowseo
wp plugin activate meowseo
```

### ❌ Updater instance null
```php
// Cek error di wp-content/debug.log
// Aktifkan WP_DEBUG di wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

### ❌ Gagal ambil info GitHub
- Cek koneksi internet
- Tunggu 1 jam (rate limit 60 req/hour)
- Verifikasi repo: `akbarbahaulloh/meowseo`

---

## 🗑️ Cleanup

**WAJIB!** Hapus file testing setelah selesai:

```bash
rm test-updater-hooks.php
rm clear-update-cache.php
```

Atau hapus manual via FTP/File Manager.

---

## 📚 Dokumentasi Lengkap

Jika butuh detail lebih lanjut:

1. **SUMMARY_PERBAIKAN.md** - Ringkasan lengkap perbaikan
2. **README_TESTING_UPDATE.md** - Panduan testing detail
3. **PERBANDINGAN_MEOWSEO_VS_MEOWPACK.md** - Analisis teknis
4. **PERBAIKAN_UPDATE_PLUGIN.md** - Detail perubahan kode

---

## 🎯 Expected Result

Jika semua berhasil:

```
✅ Hook: pre_set_site_transient_update_plugins ← REGISTERED
✅ Priority: 10 (atau lebih rendah)
✅ Class: MeowSEO\Updater\GitHub_Update_Checker
✅ Method: check_for_update()
✅ Status: WORKING
```

---

**Total waktu: ~5 menit** ⏱️

**Kesulitan: Mudah** 🟢

**Status: Siap ditest!** 🚀
