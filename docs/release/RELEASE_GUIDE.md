# 📦 MeowSEO Release Guide

## ❓ Apakah Unit Test Perlu Dibawa ke Release?

### Jawaban Singkat: **TIDAK** ❌

Unit tests **HARUS disimpan di Git repository**, tapi **TIDAK perlu** dibawa ke release ZIP yang didownload user.

## 🎯 Best Practice

### ✅ Simpan di Git Repository
```
✅ tests/                  - Untuk development & CI/CD
✅ phpunit.xml            - Config PHPUnit
✅ jest.config.js         - Config Jest
✅ composer.json          - Dependencies (termasuk dev)
✅ package.json           - NPM dependencies
✅ .github/workflows/     - CI/CD automation
```

**Kenapa?**
- Developer lain perlu tests untuk contribute
- CI/CD pipeline butuh tests untuk automated testing
- Tests adalah dokumentasi hidup
- Mencegah regression bugs
- Code review lebih mudah

### ❌ Exclude dari Release ZIP
```
❌ tests/                  - User tidak perlu
❌ phpunit.xml            - User tidak perlu
❌ jest.config.js         - User tidak perlu
❌ node_modules/          - Terlalu besar
❌ src/ (source files)    - Sudah di-build ke build/
❌ .git/                  - User tidak perlu
❌ .github/               - User tidak perlu
❌ .claude/               - Development only
❌ *.md (kecuali README)  - Documentation
```

**Kenapa?**
- User hanya perlu plugin yang siap pakai
- Menghemat ukuran file (50-70% lebih kecil)
- Download & install lebih cepat
- Lebih professional & clean

## 📊 Perbandingan Ukuran

| Versi | Ukuran | Isi |
|-------|--------|-----|
| **Development** | ~10-15 MB | Semua files termasuk tests, node_modules, src |
| **Release** | ~2-3 MB | Hanya files production (build/, includes/, assets/) |
| **Hemat** | ~7-12 MB | 70-80% lebih kecil |

## 🔧 Cara Build Release

### Opsi 1: Gunakan Build Script (Recommended)

#### Windows (PowerShell):
```powershell
# Run build script
.\build-release.ps1

# Output: meowseo.zip (siap upload)
```

#### Linux/Mac (Bash):
```bash
# Make executable
chmod +x build-release.sh

# Run build script
./build-release.sh

# Output: meowseo.zip (siap upload)
```

**Script akan otomatis**:
1. ✅ Run semua tests (PHP + JavaScript)
2. ✅ Build assets (npm run build)
3. ✅ Install production dependencies only
4. ✅ Copy files (exclude tests & dev files)
5. ✅ Create ZIP file
6. ✅ Reinstall dev dependencies (untuk development lagi)

### Opsi 2: Manual

```bash
# 1. Run tests
composer test
npm test

# 2. Build assets
npm run build

# 3. Install production deps
composer install --no-dev --optimize-autoloader

# 4. Create ZIP manually (exclude tests, node_modules, src, dll)
# ... (manual zip creation)

# 5. Reinstall dev deps
composer install
```

## 📋 Checklist Release

### Pre-Release
- [ ] Semua tests passing (PHP + JavaScript)
- [ ] Update version number di `meowseo.php`
- [ ] Update `CHANGELOG.md`
- [ ] Update `README.md`
- [ ] Commit semua changes
- [ ] Create Git tag (e.g., `v1.0.0`)

### Build Release
- [ ] Run build script (`build-release.ps1` atau `build-release.sh`)
- [ ] Verify ZIP file created
- [ ] Check ZIP size (~2-3 MB)
- [ ] Extract & test ZIP di clean WordPress install

### Post-Release
- [ ] Upload ke WordPress.org (jika applicable)
- [ ] Create GitHub release
- [ ] Attach ZIP file ke GitHub release
- [ ] Update documentation
- [ ] Announce release

## 🗂️ Struktur Release ZIP

```
meowseo.zip
├── meowseo.php              ✅ Main plugin file
├── README.md                ✅ User documentation
├── includes/                ✅ PHP classes
│   ├── class-plugin.php
│   ├── class-options.php
│   └── ...
├── build/                   ✅ Built JavaScript/CSS
│   ├── index.js
│   ├── index.css
│   └── ...
├── assets/                  ✅ Static assets
│   ├── css/
│   ├── js/
│   └── images/
├── languages/               ✅ Translation files
└── vendor/                  ✅ Production dependencies only
    └── (no dev dependencies)
```

**TIDAK termasuk**:
```
❌ tests/
❌ node_modules/
❌ src/
❌ .git/
❌ .github/
❌ .claude/
❌ phpunit.xml
❌ jest.config.js
❌ composer.json
❌ package.json
❌ *.md (except README.md)
```

## 🚀 Workflow Development vs Release

### Development Workflow
```bash
# 1. Clone repo
git clone https://github.com/your/meowseo.git
cd meowseo

# 2. Install dependencies (termasuk dev)
composer install
npm install

# 3. Run tests
composer test
npm test

# 4. Develop & test
# ... make changes ...
composer test  # Test lagi

# 5. Commit
git add .
git commit -m "Add feature X"
git push
```

### Release Workflow
```bash
# 1. Pastikan semua tests passing
composer test
npm test

# 2. Update version & changelog
# ... edit files ...

# 3. Commit & tag
git add .
git commit -m "Release v1.0.0"
git tag v1.0.0
git push origin main --tags

# 4. Build release
.\build-release.ps1  # Windows
# atau
./build-release.sh   # Linux/Mac

# 5. Upload meowseo.zip
# Upload ke WordPress.org atau GitHub releases
```

## 🎯 CI/CD Integration

### GitHub Actions (Automated Testing)

File: `.github/workflows/tests.yml`

```yaml
name: Tests

on: [push, pull_request]

jobs:
  php-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: cd .claude/worktrees/reverent-beaver-c7065a
      - run: composer install
      - run: composer test

  js-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '18'
      - run: npm install
      - run: npm test

  build-release:
    needs: [php-tests, js-tests]
    runs-on: ubuntu-latest
    if: startsWith(github.ref, 'refs/tags/')
    steps:
      - uses: actions/checkout@v4
      - name: Build release
        run: ./build-release.sh
      - name: Upload release
        uses: actions/upload-artifact@v3
        with:
          name: meowseo-release
          path: meowseo.zip
```

**Manfaat**:
- ✅ Auto-run tests pada setiap push
- ✅ Block merge jika tests gagal
- ✅ Auto-build release saat create tag
- ✅ Confidence tinggi sebelum release

## 💡 Tips

### 1. Selalu Test Sebelum Release
```bash
# Jangan skip ini!
composer test && npm test
```

### 2. Test Release ZIP
```bash
# Extract & test di clean WordPress
unzip meowseo.zip -d /path/to/wordpress/wp-content/plugins/
# Activate & test di browser
```

### 3. Versioning
```
v1.0.0 - Major release (breaking changes)
v1.1.0 - Minor release (new features)
v1.1.1 - Patch release (bug fixes)
```

### 4. Keep Tests Updated
```bash
# Setiap add feature baru, add test juga
# Setiap fix bug, add test untuk prevent regression
```

## ❓ FAQ

### Q: Apakah user perlu tests?
**A**: Tidak. User hanya perlu plugin yang siap pakai, bukan development files.

### Q: Apakah tests perlu di Git?
**A**: Ya! Tests sangat penting untuk development, CI/CD, dan collaboration.

### Q: Bagaimana kalau user mau contribute?
**A**: Mereka clone dari Git (yang ada tests), bukan download release ZIP.

### Q: Apakah vendor/ perlu di release?
**A**: Ya, tapi hanya production dependencies. Exclude dev dependencies (PHPUnit, Brain Monkey, dll).

### Q: Berapa ukuran ideal release ZIP?
**A**: Untuk plugin seperti MeowSEO: 2-5 MB. Kalau lebih dari 10 MB, ada yang salah (mungkin include node_modules atau tests).

## 🎉 Kesimpulan

### DO ✅
- ✅ Simpan tests di Git repository
- ✅ Run tests sebelum release
- ✅ Exclude tests dari release ZIP
- ✅ Use build script untuk consistency
- ✅ Test release ZIP sebelum publish

### DON'T ❌
- ❌ Hapus tests dari Git
- ❌ Include tests di release ZIP
- ❌ Include node_modules di release
- ❌ Include src/ di release (sudah di-build)
- ❌ Release tanpa run tests

---

**Summary**: Tests adalah aset development yang sangat berharga. Simpan di Git, tapi jangan bawa ke release. User tidak perlu tests, mereka hanya perlu plugin yang siap pakai.

**Command**: `.\build-release.ps1` untuk create release ZIP yang clean dan production-ready! ✅
