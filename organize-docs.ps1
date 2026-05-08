#!/usr/bin/env pwsh
# Script to organize documentation files from root to docs/

Write-Host "🗂️  Organizing documentation files..." -ForegroundColor Cyan

# Create directories if they don't exist
$directories = @(
    "docs/development",
    "docs/release",
    "docs/testing",
    "docs/summaries"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "✅ Created directory: $dir" -ForegroundColor Green
    }
}

# Define file movements
$moves = @{
    # Development docs
    "DEVELOPMENT_SETUP.md" = "docs/development/"
    "DEVELOPER_DOCS_COMPLETE.md" = "docs/development/"
    
    # Release docs
    "PANDUAN_RELEASE.md" = "docs/release/"
    "RELEASE_GUIDE.md" = "docs/release/"
    "BUILD_ASSETS_SOLUTION.md" = "docs/release/"
    
    # Testing docs
    "README_TESTING.md" = "docs/testing/"
    "QUICK_TEST_GUIDE.md" = "docs/testing/"
    "ALL_TESTS_PASSING.md" = "docs/testing/"
    "TESTING_IMPLEMENTATION_SUMMARY.md" = "docs/testing/"
    "TEST_COVERAGE_EXPANSION_SUMMARY.md" = "docs/testing/"
    "ZERO_TEST_COVERAGE_FIXED.md" = "docs/testing/"
    
    # Summary docs
    "BETA_FEATURES_SUMMARY.md" = "docs/summaries/"
    "CI_CD_SETUP_COMPLETE.md" = "docs/summaries/"
    "CI_CD_SUMMARY.md" = "docs/summaries/"
    "CLEANUP_SUMMARY.md" = "docs/summaries/"
    "ROOT_CLEANUP_COMPLETE.md" = "docs/summaries/"
    "WORKTREE_COMMIT_SUMMARY.md" = "docs/summaries/"
    "FINAL_COMMIT_STATUS.md" = "docs/summaries/"
}

# Move files
$movedCount = 0
$skippedCount = 0

foreach ($file in $moves.Keys) {
    if (Test-Path $file) {
        $destination = $moves[$file]
        Move-Item -Path $file -Destination $destination -Force
        Write-Host "✅ Moved: $file → $destination" -ForegroundColor Green
        $movedCount++
    } else {
        Write-Host "⚠️  Skipped (not found): $file" -ForegroundColor Yellow
        $skippedCount++
    }
}

Write-Host ""
Write-Host "📊 Summary:" -ForegroundColor Cyan
Write-Host "   Moved: $movedCount files" -ForegroundColor Green
Write-Host "   Skipped: $skippedCount files" -ForegroundColor Yellow
Write-Host ""
Write-Host "✅ Documentation organization complete!" -ForegroundColor Green
Write-Host ""
Write-Host "📁 Files remaining in root:" -ForegroundColor Cyan
Get-ChildItem -Filter "*.md" | Select-Object Name | Format-Table -AutoSize
