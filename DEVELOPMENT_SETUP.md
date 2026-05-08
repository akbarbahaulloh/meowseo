# 🛠️ MeowSEO Development Setup

## ⚠️ Penting: Build Assets Required

**`/build/` directory ada di `.gitignore`**, jadi setelah `git clone` kamu **HARUS build assets** dulu sebelum plugin bisa dipakai.

## 🚀 Quick Start

### 1. Clone Repository
```bash
git clone https://github.com/your/meowseo.git
cd meowseo
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Build Assets (PENTING!)
```bash
# Build JavaScript & CSS
npm run build
```

**Tanpa step ini, plugin TIDAK akan bekerja!**

### 4. Activate Plugin
```bash
# Copy/symlink ke WordPress plugins directory
# Atau develop langsung di wp-content/plugins/meowseo/

# Activate di WordPress admin
```

### 5. Verify Installation
```bash
# Run tests untuk verify semua OK
composer test
npm test
```

## 📁 Directory Structure

### Setelah Git Clone (TIDAK ada /build/):
```
meowseo/
├── src/                    ✅ Source files (React, TypeScript)
├── includes/               ✅ PHP classes
├── assets/                 ✅ Static assets
├── tests/                  ✅ Test files
├── node_modules/           ❌ (belum ada, run npm install)
├── vendor/                 ❌ (belum ada, run composer install)
└── build/                  ❌ (belum ada, run npm run build)
```

### Setelah Setup (Siap Development):
```
meowseo/
├── src/                    ✅ Source files
├── includes/               ✅ PHP classes
├── assets/                 ✅ Static assets
├── tests/                  ✅ Test files
├── node_modules/           ✅ (npm install)
├── vendor/                 ✅ (composer install)
└── build/                  ✅ (npm run build) - GENERATED
    ├── index.js            ✅ Built JavaScript
    ├── index.css           ✅ Built CSS
    └── ...
```

## 🔧 Development Commands

### Build Assets
```bash
# Production build (minified)
npm run build

# Development build (watch mode)
npm run start

# Build akan generate files di /build/
```

### Run Tests
```bash
# PHP tests
composer test

# JavaScript tests
npm test

# Watch mode (auto-rerun)
npm run test:watch
```

### Linting & Formatting
```bash
# Lint JavaScript
npm run lint:js

# Format JavaScript
npm run format:js

# Type check
npm run type-check
```

## 🎯 Development Workflow

### Daily Development
```bash
# 1. Start watch mode (auto-rebuild on changes)
npm run start

# 2. Edit files in src/
# ... make changes ...

# 3. Webpack will auto-rebuild to build/

# 4. Refresh browser to see changes

# 5. Run tests
composer test
npm test

# 6. Commit (DON'T commit /build/)
git add src/ includes/
git commit -m "Add feature X"
git push
```

### Before Commit
```bash
# 1. Run all tests
composer test && npm test

# 2. Lint & format
npm run lint:js
npm run format:js

# 3. Type check
npm run type-check

# 4. Commit (exclude /build/)
git add .
git commit -m "Your message"
```

## ❓ FAQ

### Q: Kenapa /build/ tidak di-commit?
**A**: Best practice modern development:
- ✅ Git repo lebih clean
- ✅ Tidak ada merge conflicts di generated files
- ✅ Developer build sendiri dari source
- ✅ Sesuai standard React/WordPress development

### Q: Bagaimana user download plugin?
**A**: User download dari **GitHub Releases** (pre-built ZIP yang sudah include /build/), bukan clone dari Git.

### Q: Kalau mau langsung pakai tanpa build?
**A**: Download pre-built release dari:
- GitHub Releases: https://github.com/your/meowseo/releases
- WordPress.org: https://wordpress.org/plugins/meowseo/

### Q: Apakah bisa commit /build/?
**A**: Bisa, tapi **TIDAK recommended**. Kalau mau:
```bash
# Edit .gitignore, hapus baris: /build/
npm run build
git add build/
git commit -m "Add build files"
```

Tapi ini akan:
- ❌ Membuat Git repo besar
- ❌ Menyebabkan merge conflicts
- ❌ Commit noise setiap build

### Q: npm run start vs npm run build?
**A**: 
- `npm run start` - Development mode (watch, auto-rebuild, source maps)
- `npm run build` - Production mode (minified, optimized)

## 🔄 CI/CD Integration

### GitHub Actions akan otomatis:
1. ✅ Install dependencies
2. ✅ Build assets
3. ✅ Run tests
4. ✅ Create release ZIP (include /build/)

File: `.github/workflows/tests.yml`

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '18'
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: |
          npm install
          composer install
      
      - name: Build assets
        run: npm run build
      
      - name: Run tests
        run: |
          composer test
          npm test
```

## 📦 Release Process

### Create Release
```bash
# 1. Update version
# Edit meowseo.php, package.json, composer.json

# 2. Update changelog
# Edit CHANGELOG.md

# 3. Commit & tag
git add .
git commit -m "Release v1.0.0"
git tag v1.0.0
git push origin main --tags

# 4. Build release ZIP (include /build/)
.\build-release.ps1

# 5. Upload to GitHub Releases
# Attach meowseo.zip to release
```

### What's in Release ZIP
```
meowseo.zip
├── build/              ✅ Built assets (INCLUDED)
├── includes/           ✅ PHP classes
├── assets/             ✅ Static files
├── vendor/             ✅ Production deps
└── meowseo.php         ✅ Main file

NOT included:
❌ src/                 - Source files
❌ tests/               - Test files
❌ node_modules/        - Dev dependencies
❌ .git/                - Git files
```

## 🎯 Summary

### For Development:
1. Clone repo
2. `npm install && composer install`
3. **`npm run build`** (PENTING!)
4. Develop & test
5. Commit (exclude /build/)

### For Users:
1. Download pre-built ZIP from GitHub Releases
2. Extract to wp-content/plugins/
3. Activate
4. Done! (no build required)

### For Release:
1. Run `.\build-release.ps1`
2. Upload meowseo.zip to GitHub Releases
3. Users download ZIP (include /build/)

---

**Key Point**: `/build/` tidak di-commit ke Git, tapi **INCLUDED di release ZIP**. Developer build sendiri, user download pre-built.

**Command**: `npm run build` setelah clone! ✅
