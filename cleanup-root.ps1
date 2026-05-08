# Cleanup script untuk organize root directory

Write-Host "`n=== MeowSEO Root Directory Cleanup ===" -ForegroundColor Cyan

# Create archive directories
Write-Host "`n[1/5] Creating archive directories..." -ForegroundColor Blue
$dirs = @(
    "docs\archive\session-notes",
    "docs\archive\fixes",
    "docs\archive\testing",
    "docs\archive\schema",
    "docs\archive\gemini",
    "tests\debug"
)

foreach ($dir in $dirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "  ✅ Created: $dir" -ForegroundColor Green
    }
}

# Move session notes
Write-Host "`n[2/5] Moving session notes..." -ForegroundColor Blue
$sessionFiles = @(
    "SCHEMA_SESSION_2_SUMMARY.md",
    "SCHEMA_SESSION_3_SUMMARY.md",
    "CHANGES_SUMMARY.md",
    "IMPORT_FIXES_SUMMARY.md",
    "SCHEMA_IMPLEMENTATION_SUMMARY.md"
)

foreach ($file in $sessionFiles) {
    if (Test-Path $file) {
        Move-Item $file "docs\archive\session-notes\" -Force
        Write-Host "  ✅ Moved: $file" -ForegroundColor Green
    }
}

# Move fix documentation
Write-Host "`n[3/5] Moving fix documentation..." -ForegroundColor Blue
$fixFiles = @(
    "FIX_API_TEST_CONNECTION_403.md",
    "FIX_IMPORT_FILTER_INCONSISTENCY.md",
    "FIX_IMPORT_PRESERVE_FILTER_STATUS.md",
    "SOLUTION_API_403_ERROR.md",
    "PERBAIKAN_AI_SETTINGS.md",
    "PERBAIKAN_DUPLIKASI_METHOD.md",
    "AUTOLOADER_CONFLICT_FIX.md",
    "COMPOSER_TEST_FIXED.md"
)

foreach ($file in $fixFiles) {
    if (Test-Path $file) {
        Move-Item $file "docs\archive\fixes\" -Force
        Write-Host "  ✅ Moved: $file" -ForegroundColor Green
    }
}

# Move testing documentation
Write-Host "`n[4/5] Moving testing documentation..." -ForegroundColor Blue
$testDocs = @(
    "ALL_TESTS_PASSING_COMPLETE.md",
    "JAVASCRIPT_TESTS_FIXED.md",
    "RUN_TESTS.md",
    "TEST_COMMANDS.md",
    "TEST_STATUS.md",
    "RINGKASAN_LENGKAP_TESTS.md"
)

foreach ($file in $testDocs) {
    if (Test-Path $file) {
        Move-Item $file "docs\archive\testing\" -Force
        Write-Host "  ✅ Moved: $file" -ForegroundColor Green
    }
}

# Move schema documentation
Write-Host "`n[5/5] Moving schema documentation..." -ForegroundColor Blue
$schemaFiles = @(
    "SCHEMA_PHASE_2_COMPLETE.md",
    "SCHEMA_PHASE_2_REACT_COMPLETE.md",
    "SCHEMA_PHASE_2_SUMMARY.md",
    "SCHEMA_GENERATOR_ANALYSIS.md"
)

foreach ($file in $schemaFiles) {
    if (Test-Path $file) {
        Move-Item $file "docs\archive\schema\" -Force
        Write-Host "  ✅ Moved: $file" -ForegroundColor Green
    }
}

# Move Gemini documentation
Write-Host "`n[6/7] Moving Gemini documentation..." -ForegroundColor Blue
$geminiFiles = @(
    "CHANGELOG_GEMINI_MODEL_DROPDOWN.md",
    "DEV_NOTES_GEMINI_DROPDOWN.md",
    "FITUR_DROPDOWN_MODEL_GEMINI.md",
    "QUICK_REF_GEMINI_MODELS.md",
    "README_GEMINI_DROPDOWN_UPDATE.md",
    "SUMMARY_GEMINI_DROPDOWN.md",
    "TESTING_GEMINI_DROPDOWN.md"
)

foreach ($file in $geminiFiles) {
    if (Test-Path $file) {
        Move-Item $file "docs\archive\gemini\" -Force
        Write-Host "  ✅ Moved: $file" -ForegroundColor Green
    }
}

# Move debug/test PHP files
Write-Host "`n[7/7] Moving debug PHP files..." -ForegroundColor Blue
$debugFiles = @(
    "debug-test-connection.php",
    "force-update-check.php",
    "test-api-connection.php",
    "test-article-node-manual.php",
    "test-autoload.php",
    "test-debug.php",
    "test-debug2.php",
    "test-github-updater.php",
    "test-logger-dedup-debug.php",
    "test-logger-property-debug.php",
    "test-property11-minimal.php",
    "test-reset-logger.php"
)

foreach ($file in $debugFiles) {
    if (Test-Path $file) {
        Move-Item $file "tests\debug\" -Force
        Write-Host "  ✅ Moved: $file" -ForegroundColor Green
    }
}

# Move build output files
Write-Host "`n[8/8] Moving build output files..." -ForegroundColor Blue
$outputFiles = @(
    "build_output.txt",
    "test_output.txt",
    "DEBUG_LOG_API_TEST.md",
    "BUILD.md"
)

foreach ($file in $outputFiles) {
    if (Test-Path $file) {
        Move-Item $file "docs\archive\" -Force
        Write-Host "  ✅ Moved: $file" -ForegroundColor Green
    }
}

# Summary
Write-Host "`n=== Cleanup Complete! ===" -ForegroundColor Green
Write-Host "`nFiles organized into:" -ForegroundColor Cyan
Write-Host "  📁 docs\archive\session-notes\" -ForegroundColor Yellow
Write-Host "  📁 docs\archive\fixes\" -ForegroundColor Yellow
Write-Host "  📁 docs\archive\testing\" -ForegroundColor Yellow
Write-Host "  📁 docs\archive\schema\" -ForegroundColor Yellow
Write-Host "  📁 docs\archive\gemini\" -ForegroundColor Yellow
Write-Host "  📁 tests\debug\" -ForegroundColor Yellow

Write-Host "`nRoot directory is now clean! ✨" -ForegroundColor Green
