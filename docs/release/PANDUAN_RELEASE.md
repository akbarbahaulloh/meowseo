# 📦 Panduan Release MeowSEO

## ❓ Apakah Unit Test Perlu Dibawa ke Release?

### Jawaban: **TIDAK** ❌

**Unit tests HARUS disimpan di Git, tapi TIDAK perlu dibawa ke release ZIP.**

## 🎯 Ringkasan

### ✅ Simpan di Git
```
✅ tests/          - Untuk development
✅ phpunit.xml     - Config test
✅ composer.json   - Dependencies
✅ package.json    - NPM packages
```

**Kenapa?**
- Developer lain perlu untuk contribute
- CI/CD butuh untuk automated testing
- Mencegah bugs masuk production

### ❌ Jangan Bawa ke Release
```
❌ tests/          - User tidak perlu
❌ node_modules/   - Terlalu besar
❌ src/            - Sudah di-build
❌ .git/           - Development only
❌ .claude/        - Development only
```

**Kenapa?**
- User hanya perlu plugin siap pakai
- Hemat ukuran 70% (dari ~10MB jadi ~3MB)
- Download & install lebih cepat

## 📊 Perbandingan

| Versi | Ukuran | Isi |
|-------|--------|-----|
| Development | ~10-15 MB | Semua files + tests |
| Release | ~2-3 MB | Hanya production files |
| **Hemat** | **~7-12 MB** | **70-80% lebih kecil** |

## 🚀 Cara Build Release

### Windows (PowerShell):
```powershell
# Run script
.\build-release.ps1

# Output: meowseo.zip (siap upload)
```

### Linux/Mac (Bash):
```bash
# Make executable
chmod +x build-release.sh

# Run script
./build-release.sh

# Output: meowseo.zip (siap upload)
```

## ⚠️ Penting: Build Assets

### Masalah
`/build/` ada di `.gitignore`, jadi setelah `git clone` plugin **tidak bisa langsung dipakai**.

### Solusi 1: Development Setup (Recommended)
```bash
# 1. Clone repo
git clone https://github.com/your/meowseo.git
cd meowseo

# 2. Install dependencies
npm install
composer install

# 3. Build assets (PENTING!)
npm run build

# 4. Sekarang plugin siap dipakai
```

### Solusi 2: Download Pre-built Release
```bash
# Download dari GitHub Releases (sudah include /build/)
# https://github.com/your/meowseo/releases

# Extract & activate di WordPress
```

### Solusi 3: Commit /build/ (Tidak Recommended)
Kalau mau `/build/` di-commit ke Git, hapus dari `.gitignore`:
```bash
# Edit .gitignore, hapus baris:
# /build/

# Lalu commit build files
npm run build
git add build/
git commit -m "Add build files"
```

**Tapi ini TIDAK recommended** karena:
- ❌ Git repo jadi besar
- ❌ Merge conflicts di build files
- ❌ Commit noise

## 🔧 Apa yang Dilakukan Script?

1. ✅ Run semua tests (PHP + JavaScript)
2. ✅ Build assets (npm run build)
3. ✅ Install production dependencies only
4. ✅ Copy files (exclude tests & dev files)
5. ✅ Create ZIP file
6. ✅ Reinstall dev dependencies

**Kalau tests gagal, script akan stop!** Ini mencegah release yang buggy.

## 📋 Checklist Release

### Sebelum Release
- [ ] `composer test` - Semua passing ✅
- [ ] `npm test` - Semua passing ✅
- [ ] Update version di `meowseo.php`
- [ ] Update `CHANGELOG.md`
- [ ] Commit & push ke Git

### Build Release
- [ ] Run `.\build-release.ps1`
- [ ] Check `meowseo.zip` created
- [ ] Verify ukuran ~2-3 MB
- [ ] Test ZIP di clean WordPress

### Setelah Release
- [ ] Upload ke WordPress.org
- [ ] Create GitHub release
- [ ] Update documentation

## 🗂️ Isi Release ZIP

### ✅ Yang Termasuk:
```
meowseo.zip
├── meowseo.php       ✅ Main file
├── README.md         ✅ Documentation
├── includes/         ✅ PHP classes
├── build/            ✅ Built JS/CSS
├── assets/           ✅ Static files
└── vendor/           ✅ Production deps only
```

### ❌ Yang TIDAK Termasuk:
```
❌ tests/             - Development only
❌ node_modules/      - Terlalu besar
❌ src/               - Sudah di-build
❌ .git/              - Version control
❌ .github/           - CI/CD config
❌ .claude/           - Worktree
❌ phpunit.xml        - Test config
❌ jest.config.js     - Test config
❌ composer.json      - Dev dependencies
❌ package.json       - NPM config
```

## 💡 Workflow

### Development (Sehari-hari):
```bash
# 1. Clone repo (TIDAK ada /build/)
git clone https://github.com/your/meowseo.git
cd meowseo

# 2. Install dependencies
composer install
npm install

# 3. Build assets (PENTING!)
npm run build

# 4. Develop & test
# ... edit code ...
npm run build  # Build lagi kalau edit JS/CSS
composer test  # ✅ Test!

# 5. Commit (JANGAN commit /build/)
git add .
git commit -m "Add feature"
git push
```

### Release (Saat mau publish):
```bash
# 1. Test dulu!
composer test && npm test

# 2. Update version & changelog
# ... edit files ...

# 3. Build release (script akan build assets otomatis)
.\build-release.ps1

# 4. Upload meowseo.zip ke GitHub Releases
# User download ZIP ini (sudah include /build/)
```

## ❓ FAQ

### Q: User perlu tests?
**A**: Tidak. User hanya perlu plugin siap pakai.

### Q: Tests perlu di Git?
**A**: Ya! Sangat penting untuk development & CI/CD.

### Q: Kalau user mau contribute?
**A**: Mereka clone dari Git (yang ada tests), bukan download release ZIP.

### Q: Vendor/ perlu di release?
**A**: Ya, tapi hanya production dependencies. Exclude PHPUnit, Brain Monkey, dll.

### Q: Ukuran ideal release?
**A**: 2-5 MB untuk plugin seperti MeowSEO.

## 🎯 Kesimpulan

### DO ✅
- ✅ Simpan tests di Git
- ✅ Run tests sebelum release
- ✅ Exclude tests dari release ZIP
- ✅ Gunakan build script
- ✅ Test release ZIP sebelum publish

### DON'T ❌
- ❌ Hapus tests dari Git
- ❌ Include tests di release ZIP
- ❌ Include node_modules di release
- ❌ Release tanpa run tests

---

**Intinya**: Tests sangat berharga untuk development, tapi user tidak perlu. Simpan di Git, exclude dari release.

**Command**: `.\build-release.ps1` untuk create release ZIP yang clean! ✅
