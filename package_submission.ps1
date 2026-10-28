# ========================================================
# GolfTee — Vibe96 Submission Packaging Script (PowerShell)
# Generates a clean submission ZIP under 100MB excluding all
# build artifacts, .git, and secrets per Vibe96 audit rules.
# ========================================================

$ErrorActionPreference = "Stop"

$ProjectRoot = $PSScriptRoot
$ZipName = "GolfTee-Submission-v1.0.0.zip"
$ZipPath = Join-Path $ProjectRoot $ZipName

Write-Host "📦 Preparing GolfTee clean archive for Vibe96 submission..." -ForegroundColor Cyan

# Remove old zip if exists
if (Test-Path $ZipPath) {
    Remove-Item $ZipPath -Force
    Write-Host "🗑 Removed previous archive: $ZipName" -ForegroundColor Yellow
}

# Temporary staging directory
$StageDir = Join-Path $env:TEMP ("golf_tee_pkg_" + [System.Guid]::NewGuid().ToString().Substring(0, 8))
New-Item -ItemType Directory -Path $StageDir | Out-Null

try {
    Write-Host "📁 Copying repository files..." -ForegroundColor Gray

    # Exclusions per Vibe96 audit checklist
    $ExcludeFolders = @(
        'node_modules',
        'vendor',
        'build',
        '.dart_tool',
        '.git',
        '.idea',
        '.vscode',
        'bin',
        'obj',
        'dist',
        '.cache',
        '.system_generated',
        'scratch',
        'ephemeral',
        '.figma'
    )

    $ExcludeFiles = @(
        '.env',
        '.env.*',
        '*.zip',
        '*.log',
        '*.tmp',
        'audit_report_full.txt'
    )

    # Use robocopy for fast, clean copying with exclusions
    $RoboParams = @(
        $ProjectRoot,
        $StageDir,
        "/E",
        "/XD"
    ) + $ExcludeFolders + @("/XF") + $ExcludeFiles + @("/NFL", "/NDL", "/NJH", "/NJS", "/nc", "/ns", "/np")

    & robocopy @RoboParams
    if ($LASTEXITCODE -ge 8) {
        throw "Robocopy failed with exit code $LASTEXITCODE"
    }

    # Ensure .env.example files are preserved if robocopy excluded them with .env.*
    if (Test-Path (Join-Path $ProjectRoot ".env.example")) {
        Copy-Item (Join-Path $ProjectRoot ".env.example") -Destination (Join-Path $StageDir ".env.example") -Force
    }
    if (Test-Path (Join-Path $ProjectRoot "admin-php\.env.example")) {
        Copy-Item (Join-Path $ProjectRoot "admin-php\.env.example") -Destination (Join-Path $StageDir "admin-php\.env.example") -Force
    }

    # Ensure composer.json and composer.lock are explicitly preserved in root and admin-php/
    if (Test-Path (Join-Path $ProjectRoot "composer.json")) {
        Copy-Item (Join-Path $ProjectRoot "composer.json") -Destination (Join-Path $StageDir "composer.json") -Force
    }
    if (Test-Path (Join-Path $ProjectRoot "composer.lock")) {
        Copy-Item (Join-Path $ProjectRoot "composer.lock") -Destination (Join-Path $StageDir "composer.lock") -Force
    }
    if (Test-Path (Join-Path $ProjectRoot "admin-php\composer.json")) {
        Copy-Item (Join-Path $ProjectRoot "admin-php\composer.json") -Destination (Join-Path $StageDir "admin-php\composer.json") -Force
    }
    if (Test-Path (Join-Path $ProjectRoot "admin-php\composer.lock")) {
        Copy-Item (Join-Path $ProjectRoot "admin-php\composer.lock") -Destination (Join-Path $StageDir "admin-php\composer.lock") -Force
    }

    # Verify no real .env or secret files exist in staging (only .env.example allowed)
    Get-ChildItem -Path $StageDir -Filter ".env*" -Recurse -Force | ForEach-Object {
        if ($_.Name -ne ".env.example") {
            Remove-Item $_.FullName -Force
            Write-Host "🔒 Removed uncommitted live secret file from staging: $($_.Name)" -ForegroundColor Yellow
        }
    }

    Write-Host "🗜 Compressing into $ZipName..." -ForegroundColor Gray
    Compress-Archive -Path "$StageDir\*" -DestinationPath $ZipPath -CompressionLevel Optimal

    $ZipSizeMB = [math]::Round(((Get-Item $ZipPath).Length / 1MB), 2)
    Write-Host "✅ Submission archive created successfully!" -ForegroundColor Green
    Write-Host "📍 Path: $ZipPath" -ForegroundColor White
    Write-Host "📊 Size: $ZipSizeMB MB (Limit: 100 MB)" -ForegroundColor White

} finally {
    if (Test-Path $StageDir) {
        Remove-Item -Path $StageDir -Recurse -Force -ErrorAction SilentlyContinue
    }
}
