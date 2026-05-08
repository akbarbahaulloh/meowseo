# Release Documentation

This directory contains documentation for creating and managing releases.

## Files

- **[RELEASE_GUIDE.md](RELEASE_GUIDE.md)** - Complete release process guide (English)
- **[PANDUAN_RELEASE.md](PANDUAN_RELEASE.md)** - Panduan proses release (Bahasa Indonesia)
- **[BUILD_ASSETS_SOLUTION.md](BUILD_ASSETS_SOLUTION.md)** - Build assets strategy and solution

## Quick Start

### Create a Release

```bash
# 1. Run build script
./build-release.sh  # Linux/Mac
# or
./build-release.ps1  # Windows

# 2. Tag version
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0

# 3. GitHub Actions will automatically:
#    - Run all tests
#    - Build assets
#    - Create release ZIP
#    - Upload to GitHub Releases
```

## Build Scripts

Located in project root:
- `build-release.sh` - Bash script for Linux/Mac
- `build-release.ps1` - PowerShell script for Windows

## Related Documentation

- [CI/CD Workflows](../../.github/workflows/)
- [Testing Guide](../testing/)
- [Development Setup](../development/)
