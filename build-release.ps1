# Build script untuk MeowSEO release (PowerShell)

Write-Host "`n=== Building MeowSEO Release ===" -ForegroundColor Cyan

# 1. Run tests first
Write-Host "`n[1/7] Running PHP tests..." -ForegroundColor Blue
composer test
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ PHP tests failed! Aborting release." -ForegroundColor Red
    exit 1
}

Write-Host "`n[2/7] Running JavaScript tests..." -ForegroundColor Blue
npm test
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ JavaScript tests failed! Aborting release." -ForegroundColor Red
    exit 1
}

Write-Host "✅ All tests passed!" -ForegroundColor Green

# 2. Build assets
Write-Host "`n[3/7] Building assets..." -ForegroundColor Blue
npm run build

# 3. Install production dependencies only
Write-Host "`n[4/7] Installing production dependencies..." -ForegroundColor Blue
composer install --no-dev --optimize-autoloader

# 4. Create release directory
Write-Host "`n[5/7] Creating release directory..." -ForegroundColor Blue
$releaseDir = "meowseo-release"
if (Test-Path $releaseDir) {
    Remove-Item -Recurse -Force $releaseDir
}
New-Item -ItemType Directory -Path $releaseDir | Out-Null

# 5. Copy files (exclude tests and dev files)
Write-Host "`n[6/7] Copying files..." -ForegroundColor Blue

$excludePatterns = @(
    '.git',
    '.github',
    '.claude',
    'tests',
    'node_modules',
    'src',
    'vendor',
    '*.log',
    '*.md',
    'phpunit.xml',
    '.phpunit.result.cache',
    'jest.config.js',
    'composer.json',
    'composer.lock',
    'package.json',
    'package-lock.json',
    'webpack.config.js',
    'tsconfig.json',
    '.vscode',
    '.idea',
    'build-release.ps1',
    'build-release.sh',
    'cleanup-root.ps1',
    'run-tests.sh',
    'docs\archive'
)

# Copy all files except excluded
Get-ChildItem -Path . -Recurse | Where-Object {
    $item = $_
    $shouldExclude = $false
    
    foreach ($pattern in $excludePatterns) {
        if ($item.FullName -like "*$pattern*") {
            $shouldExclude = $true
            break
        }
    }
    
    -not $shouldExclude
} | ForEach-Object {
    $targetPath = $_.FullName.Replace($PWD.Path, $releaseDir)
    $targetDir = Split-Path -Parent $targetPath
    
    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
    }
    
    if (-not $_.PSIsContainer) {
        Copy-Item $_.FullName -Destination $targetPath -Force
    }
}

# Copy README.md (keep this one)
Copy-Item "README.md" -Destination "$releaseDir/README.md" -Force

# 7. Create ZIP
Write-Host "`n[7/7] Creating ZIP file..." -ForegroundColor Blue
if (Test-Path "meowseo.zip") {
    Remove-Item "meowseo.zip" -Force
}

Compress-Archive -Path "$releaseDir\*" -DestinationPath "meowseo.zip" -CompressionLevel Optimal

# 8. Reinstall dev dependencies
Write-Host "`nReinstalling dev dependencies..." -ForegroundColor Blue
composer install

# 9. Show results
Write-Host "`n✅ Release built successfully!" -ForegroundColor Green
Write-Host "📦 File: meowseo.zip" -ForegroundColor Green
$zipFile = Get-Item "meowseo.zip"
Write-Host "📊 Size: $([math]::Round($zipFile.Length / 1MB, 2)) MB" -ForegroundColor Cyan

# Cleanup
Write-Host "`nCleaning up..." -ForegroundColor Blue
Remove-Item -Recurse -Force $releaseDir

Write-Host "`n🎉 Done! Ready to release." -ForegroundColor Green
