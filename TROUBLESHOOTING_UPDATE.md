# 🔧 Troubleshooting Update System MeowSEO

## ❌ Masalah: Update Tidak Terdeteksi

### Gejala:
- Muncul notice: "MeowSEO berhasil memeriksa pembaruan di GitHub"
- Tapi tombol "Update Now" **TIDAK muncul** di halaman Plugins
- Padahal sudah ada commit baru di GitHub

---

## 🔍 Penyebab Utama

### 1. **Version di `meowseo.php` Tidak Diupdate**

**Masalah**: Version masih menggunakan commit lama, tidak sinkron dengan commit terbaru di GitHub.

**Contoh**:
```php
// File: meowseo.php
// Version: 1.0.0-b1b0d0d  ← COMMIT LAMA
// Commit terbaru di GitHub: 50969fe  ← TIDAK SINKRON!
```

**Cara Kerja Update Detection**:
1. WordPress memanggil hook `pre_set_site_transient_update_plugins`
2. MeowSEO updater mengambil **current commit** dari:
   - Option `meowseo_installed_commit` (jika ada)
   - Atau extract dari `MEOWSEO_VERSION` constant
3. Updater mengambil **latest commit** dari GitHub API
4. Jika **current ≠ latest**, maka update tersedia
5. WordPress menampilkan tombol "Update Now"

**Jika version tidak diupdate**:
```
Current: b1b0d0d (dari meowseo.php)
Latest:  50969fe (dari GitHub)
Result:  b1b0d0d ≠ 50969fe → Update tersedia ✓

TAPI... setelah user update:
Current: b1b0d0d (masih sama! karena file tidak berubah)
Latest:  50969fe (dari GitHub)
Result:  b1b0d0d ≠ 50969fe → Update tersedia lagi! (LOOP!)
```

---

## ✅ Solusi

### Solusi 1: Update Version di `meowseo.php` (RECOMMENDED)

**Langkah**:
1. Buka file `meowseo.php`
2. Update version dengan commit terbaru:

```php
/**
 * Plugin Name: MeowSEO
 * Version: 1.0.0-50969fe  ← UPDATE INI
 */

define( 'MEOWSEO_VERSION', '1.0.0-50969fe' );  ← DAN INI
```

3. Commit dan push ke GitHub:
```bash
git add meowseo.php
git commit -m "Update version to 50969fe"
git push origin main
```

4. Di WordPress, clear cache dan cek update lagi

**Keuntungan**:
- ✅ Version selalu sinkron dengan commit
- ✅ Tidak ada update loop
- ✅ User bisa lihat version yang terinstall

---

### Solusi 2: Set Option `meowseo_installed_commit` Manual

**Langkah**:
1. Upload file `debug-update-detection.php` ke root WordPress
2. Akses: `http://yoursite.com/debug-update-detection.php`
3. Scroll ke bagian "💡 Solusi"
4. Masukkan commit ID terbaru: `50969fe`
5. Klik "Set Commit ID"

**Keuntungan**:
- ✅ Cepat, tidak perlu commit baru
- ✅ Bisa set commit ID kapan saja

**Kekurangan**:
- ❌ Harus manual setiap kali update
- ❌ Option bisa hilang jika plugin di-reinstall

---

### Solusi 3: Automatic Version Update (BEST PRACTICE)

**Implementasi**: Gunakan Git hook untuk auto-update version saat commit.

**File**: `.git/hooks/pre-commit`

```bash
#!/bin/bash
# Auto-update version in meowseo.php with short commit hash

# Get short commit hash (7 characters)
COMMIT_HASH=$(git rev-parse --short=7 HEAD)

# Update version in meowseo.php
sed -i "s/Version: 1\.0\.0-[a-f0-9]\{7\}/Version: 1.0.0-$COMMIT_HASH/" meowseo/meowseo.php
sed -i "s/MEOWSEO_VERSION', '1\.0\.0-[a-f0-9]\{7\}/MEOWSEO_VERSION', '1.0.0-$COMMIT_HASH/" meowseo/meowseo.php

# Add updated file to commit
git add meowseo/meowseo.php

echo "✓ Version updated to 1.0.0-$COMMIT_HASH"
```

**Cara Install**:
```bash
cd D:\meowseo
echo '#!/bin/bash
COMMIT_HASH=$(git rev-parse --short=7 HEAD)
sed -i "s/Version: 1\.0\.0-[a-f0-9]\{7\}/Version: 1.0.0-$COMMIT_HASH/" meowseo/meowseo.php
sed -i "s/MEOWSEO_VERSION'\'', '\''1\.0\.0-[a-f0-9]\{7\}/MEOWSEO_VERSION'\'', '\''1.0.0-$COMMIT_HASH/" meowseo/meowseo.php
git add meowseo/meowseo.php
echo "✓ Version updated to 1.0.0-$COMMIT_HASH"
' > .git/hooks/pre-commit

chmod +x .git/hooks/pre-commit
```

**Keuntungan**:
- ✅ Otomatis, tidak perlu manual
- ✅ Version selalu sinkron
- ✅ Tidak ada yang terlupa

---

## 🧪 Testing Update Detection

### 1. Upload Debug Script

Upload file `debug-update-detection.php` ke root WordPress.

### 2. Akses Debug Script

```
http://yoursite.com/debug-update-detection.php
```

### 3. Periksa Output

**Yang harus dicek**:

#### ✅ Updater Initialized
```
✓ Updater checker berhasil diinisialisasi
```

#### ✅ Current Commit Terdeteksi
```
Current Installed Commit: 50969fe
```

Jika **"Not set"** atau **"Not found"**:
- Version di `meowseo.php` tidak ada commit ID
- Option `meowseo_installed_commit` tidak ada
- **Solusi**: Update version atau set option manual

#### ✅ GitHub API Success
```
✓ Berhasil mengambil data dari GitHub
Latest Commit SHA (Short): 50969fe
```

Jika **gagal**:
- Cek koneksi internet
- Cek rate limit (60 req/hour)
- Cek repository settings

#### ✅ Update Comparison
```
Current Installed: b1b0d0d
Latest on GitHub:  50969fe
⚠️ Update tersedia!
```

Jika **"Plugin sudah menggunakan versi terbaru"**:
- Current = Latest
- Tidak ada update
- Normal jika memang sudah update

#### ✅ WordPress Transient
```
✓ Update terdeteksi di WordPress transient!
New Version: 1.0.0.50969fe
```

Jika **"Update TIDAK terdeteksi"**:
- Hook tidak terdaftar
- Current = Latest (tidak ada update)
- Error saat fetch GitHub

---

## 📋 Checklist Troubleshooting

Gunakan checklist ini untuk troubleshooting:

- [ ] **Updater initialized?**
  - Cek: `$plugin->get_updater_checker()` tidak null
  - Fix: Pastikan perubahan kode sudah diterapkan

- [ ] **Hook registered?**
  - Cek: `$wp_filter['pre_set_site_transient_update_plugins']` ada
  - Fix: Deactivate/activate plugin

- [ ] **Current commit detected?**
  - Cek: Option `meowseo_installed_commit` atau version di `meowseo.php`
  - Fix: Update version atau set option

- [ ] **GitHub API success?**
  - Cek: `get_latest_commit()` return data
  - Fix: Cek koneksi, rate limit, repository

- [ ] **Current ≠ Latest?**
  - Cek: Current commit berbeda dengan latest
  - Fix: Update version di `meowseo.php`

- [ ] **WordPress transient updated?**
  - Cek: `get_site_transient('update_plugins')` ada response
  - Fix: Clear cache, trigger update check

- [ ] **Tombol "Update Now" muncul?**
  - Cek: Di halaman Plugins
  - Fix: Refresh halaman (Ctrl+F5)

---

## 🔄 Workflow Update yang Benar

### Saat Development (Sebelum Commit)

```bash
# 1. Buat perubahan kode
vim includes/modules/ai/class-ai-settings.php

# 2. Get short commit hash (untuk version)
git rev-parse --short=7 HEAD
# Output: 50969fe

# 3. Update version di meowseo.php
# Version: 1.0.0-50969fe
# MEOWSEO_VERSION: 1.0.0-50969fe

# 4. Commit semua perubahan
git add .
git commit -m "ai key settings"

# 5. Push ke GitHub
git push origin main
```

### Saat Testing Update (Di WordPress)

```bash
# 1. Clear cache
wp transient delete meowseo_github_update_info
wp transient delete meowseo_github_changelog
wp option delete meowseo_github_last_check
wp transient delete update_plugins

# 2. Trigger update check
wp plugin update-check

# 3. Cek apakah update tersedia
wp plugin list --name=meowseo

# 4. Update plugin
wp plugin update meowseo
```

### Saat User Update (Via Dashboard)

1. User buka **Plugins**
2. Klik **"Cek Pembaruan"** (jika ada)
3. Muncul notice: "MeowSEO berhasil memeriksa pembaruan..."
4. Tombol **"Update Now"** muncul di bawah deskripsi
5. Klik **"Update Now"**
6. WordPress download ZIP dari GitHub
7. Extract dan replace files
8. Hook `upgrader_process_complete` dipanggil
9. Option `meowseo_installed_commit` diupdate dengan commit terbaru
10. Selesai!

---

## 🐛 Common Issues

### Issue 1: Update Loop (Update terus muncul)

**Gejala**: Setelah update, tombol "Update Now" muncul lagi.

**Penyebab**: Version di `meowseo.php` tidak diupdate setelah download dari GitHub.

**Solusi**:
1. Pastikan version di `meowseo.php` di GitHub sudah benar
2. Atau gunakan hook `upgrader_process_complete` untuk set option

### Issue 2: Rate Limit Exceeded

**Gejala**: Error "GitHub rate limit exceeded"

**Penyebab**: GitHub API limit 60 requests/hour untuk unauthenticated.

**Solusi**:
1. Tunggu 1 jam
2. Atau tambahkan GitHub token (fitur belum diimplementasi)

### Issue 3: Hook Tidak Terdaftar

**Gejala**: Update tidak pernah terdeteksi

**Penyebab**: Updater tidak diinisialisasi atau hook tidak terdaftar.

**Solusi**:
1. Deactivate dan activate plugin
2. Clear opcache: `opcache_reset()`
3. Cek `wp-content/debug.log`

---

## 📝 Best Practices

### 1. Selalu Update Version Saat Commit

```bash
# BAD: Commit tanpa update version
git commit -m "fix bug"

# GOOD: Update version dulu
# Edit meowseo.php → Version: 1.0.0-abc1234
git commit -m "fix bug"
```

### 2. Gunakan Git Hook untuk Automation

Install pre-commit hook untuk auto-update version.

### 3. Test Update Sebelum Push

```bash
# 1. Commit local
git commit -m "new feature"

# 2. Test update detection
php debug-update-detection.php

# 3. Jika OK, push
git push origin main
```

### 4. Monitor GitHub API Rate Limit

```bash
# Check rate limit
curl -H "Accept: application/vnd.github.v3+json" \
  https://api.github.com/rate_limit
```

---

## 🎯 Summary

**Masalah Utama**: Version di `meowseo.php` tidak sinkron dengan commit GitHub.

**Solusi Cepat**:
1. Update version di `meowseo.php` dengan commit terbaru
2. Commit dan push ke GitHub
3. Clear cache di WordPress
4. Cek update lagi

**Solusi Jangka Panjang**:
- Install Git pre-commit hook untuk auto-update version
- Atau gunakan CI/CD untuk automation

**Tools**:
- `debug-update-detection.php` - Debug update detection
- `clear-update-cache.php` - Clear cache
- `test-updater-hooks.php` - Verify hooks

---

**Status**: Siap untuk troubleshooting! 🚀
