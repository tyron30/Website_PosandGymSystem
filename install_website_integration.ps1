<#
.SYNOPSIS
    Installs/updates the website-integration files into your PosandGymSystem project.

.DESCRIPTION
    Run this script from inside the extracted "changed_files" folder
    (the one containing the admin\, cashier\, and website\ subfolders).

    It will:
      1. Ask for (or accept via -ProjectPath) the full path to your
         PosandGymSystem project folder.
      2. Back up any files it's about to overwrite into a timestamped
         backup folder, just in case.
      3. Copy the updated admin\*.php and cashier\*.php files in.
      4. Create website\api\ and copy config.php, settings.php,
         membership_fees.php, promos.php, contact.php, book.php into it.
      5. Copy the updated website\README.txt in.
      6. Delete the old stale copies of those 6 API files that used to
         sit directly in website\ (now superseded by website\api\).

.EXAMPLE
    .\install_website_integration.ps1

.EXAMPLE
    .\install_website_integration.ps1 -ProjectPath "C:\xampp\htdocs\PosandGymSystem"
#>

param(
    [string]$ProjectPath
)

$ErrorActionPreference = "Stop"

Write-Host "=====================================================" -ForegroundColor Cyan
Write-Host " PosandGymSystem - Website Integration Installer" -ForegroundColor Cyan
Write-Host "=====================================================" -ForegroundColor Cyan
Write-Host ""

# ── Locate the source files (this script must sit next to admin\, cashier\, website\) ──
$SourceRoot = $PSScriptRoot
$SourceAdmin   = Join-Path $SourceRoot "admin"
$SourceCashier = Join-Path $SourceRoot "cashier"
$SourceWebsite = Join-Path $SourceRoot "website"

if (-not (Test-Path $SourceAdmin) -or -not (Test-Path $SourceCashier) -or -not (Test-Path $SourceWebsite)) {
    Write-Host "ERROR: Could not find admin\, cashier\, and website\ folders next to this script." -ForegroundColor Red
    Write-Host "Make sure install_website_integration.ps1 is inside the extracted 'changed_files' folder." -ForegroundColor Red
    exit 1
}

# ── Ask for the project path if not supplied ──────────────────────────────
if (-not $ProjectPath) {
    $DefaultPath = "C:\xampp\htdocs\PosandGymSystem"
    $ProjectPath = Read-Host "Enter the full path to your PosandGymSystem project folder [$DefaultPath]"
    if ([string]::IsNullOrWhiteSpace($ProjectPath)) {
        $ProjectPath = $DefaultPath
    }
}

if (-not (Test-Path $ProjectPath)) {
    Write-Host "ERROR: Project folder not found: $ProjectPath" -ForegroundColor Red
    exit 1
}

# Resolve to full, comparable paths
$SourceRootFull   = (Resolve-Path $SourceRoot).Path.TrimEnd('\')
$ProjectPathFull  = (Resolve-Path $ProjectPath).Path.TrimEnd('\')
$InPlace          = ($SourceRootFull -ieq $ProjectPathFull)

if ($InPlace) {
    Write-Host "Detected that the update files were extracted directly into your project folder." -ForegroundColor Yellow
    Write-Host "The admin\, cashier\, and website\api\ files are already in place from extraction -" -ForegroundColor Yellow
    Write-Host "this script will just clean up the old stale website\ files below." -ForegroundColor Yellow
    Write-Host "(Note: since extraction already overwrote the originals, the 'backup' step" -ForegroundColor Yellow
    Write-Host " below can only save the already-updated versions, not your pre-update files.)" -ForegroundColor Yellow
    Write-Host ""
}

$AdminTarget   = Join-Path $ProjectPath "admin"
$CashierTarget = Join-Path $ProjectPath "cashier"
$WebsiteTarget = Join-Path $ProjectPath "website"
$ApiTarget     = Join-Path $WebsiteTarget "api"

if (-not (Test-Path $AdminTarget) -or -not (Test-Path $CashierTarget) -or -not (Test-Path $WebsiteTarget)) {
    Write-Host "ERROR: '$ProjectPath' doesn't look like a PosandGymSystem project (missing admin\, cashier\, or website\)." -ForegroundColor Red
    exit 1
}

Write-Host "Project folder: $ProjectPath" -ForegroundColor Green
Write-Host ""

$Confirm = Read-Host "This will overwrite files in that project. Continue? (Y/N)"
if ($Confirm -notmatch '^[Yy]') {
    Write-Host "Cancelled." -ForegroundColor Yellow
    exit 0
}

# ── Backup ──────────────────────────────────────────────────────────────────
$Timestamp   = Get-Date -Format "yyyyMMdd_HHmmss"
$BackupRoot  = Join-Path $ProjectPath "_backup_before_website_integration_$Timestamp"
New-Item -ItemType Directory -Path $BackupRoot -Force | Out-Null
Write-Host "Backing up existing files to: $BackupRoot" -ForegroundColor Yellow

function Backup-IfExists {
    param([string]$FilePath, [string]$RelativeName)
    if (Test-Path $FilePath) {
        $dest = Join-Path $BackupRoot $RelativeName
        $destDir = Split-Path $dest -Parent
        if (-not (Test-Path $destDir)) { New-Item -ItemType Directory -Path $destDir -Force | Out-Null }
        Copy-Item $FilePath $dest -Force
    }
}

function Copy-IfDifferent {
    param([string]$SourceFile, [string]$DestFile)
    $srcFull  = (Resolve-Path $SourceFile).Path
    $destFull = if (Test-Path $DestFile) { (Resolve-Path $DestFile).Path } else { $DestFile }
    if ($srcFull -ieq $destFull) {
        return  # same physical file (in-place extraction) - nothing to copy
    }
    Copy-Item $SourceFile $DestFile -Force
}

# ── Copy admin\*.php ────────────────────────────────────────────────────────
Write-Host ""
Write-Host "Updating admin files..." -ForegroundColor Cyan
Get-ChildItem -Path $SourceAdmin -Filter *.php | ForEach-Object {
    $destFile = Join-Path $AdminTarget $_.Name
    Backup-IfExists -FilePath $destFile -RelativeName "admin\$($_.Name)"
    Copy-IfDifferent -SourceFile $_.FullName -DestFile $destFile
    Write-Host "  admin\$($_.Name)" -ForegroundColor Gray
}

# ── Copy cashier\*.php ──────────────────────────────────────────────────────
Write-Host ""
Write-Host "Updating cashier files..." -ForegroundColor Cyan
Get-ChildItem -Path $SourceCashier -Filter *.php | ForEach-Object {
    $destFile = Join-Path $CashierTarget $_.Name
    Backup-IfExists -FilePath $destFile -RelativeName "cashier\$($_.Name)"
    Copy-IfDifferent -SourceFile $_.FullName -DestFile $destFile
    Write-Host "  cashier\$($_.Name)" -ForegroundColor Gray
}

# ── Copy website\api\*.php (create the folder if needed) ───────────────────
Write-Host ""
Write-Host "Setting up website\api\ ..." -ForegroundColor Cyan
if (-not (Test-Path $ApiTarget)) {
    New-Item -ItemType Directory -Path $ApiTarget -Force | Out-Null
}
$SourceApi = Join-Path $SourceWebsite "api"
Get-ChildItem -Path $SourceApi -Filter *.php | ForEach-Object {
    $destFile = Join-Path $ApiTarget $_.Name
    Backup-IfExists -FilePath $destFile -RelativeName "website\api\$($_.Name)"
    Copy-IfDifferent -SourceFile $_.FullName -DestFile $destFile
    Write-Host "  website\api\$($_.Name)" -ForegroundColor Gray
}

# ── Copy website\README.txt ─────────────────────────────────────────────────
$ReadmeSource = Join-Path $SourceWebsite "README.txt"
if (Test-Path $ReadmeSource) {
    $ReadmeTarget = Join-Path $WebsiteTarget "README.txt"
    Backup-IfExists -FilePath $ReadmeTarget -RelativeName "website\README.txt"
    Copy-IfDifferent -SourceFile $ReadmeSource -DestFile $ReadmeTarget
    Write-Host "  website\README.txt" -ForegroundColor Gray
}

# ── Remove the old stale copies that used to sit directly in website\ ──────
Write-Host ""
Write-Host "Removing old stale files from website\ (now superseded by website\api\)..." -ForegroundColor Cyan
$StaleFiles = @("config.php", "settings.php", "membership_fees.php", "promos.php", "contact.php", "book.php")
foreach ($f in $StaleFiles) {
    $staleFile = Join-Path $WebsiteTarget $f
    if (Test-Path $staleFile) {
        Backup-IfExists -FilePath $staleFile -RelativeName "website\_old_root_$f"
        Remove-Item $staleFile -Force
        Write-Host "  Removed website\$f" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "=====================================================" -ForegroundColor Green
Write-Host " Done! Website integration files installed." -ForegroundColor Green
Write-Host " Backup of anything overwritten/removed is here:" -ForegroundColor Green
Write-Host " $BackupRoot" -ForegroundColor Green
Write-Host "=====================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host " 1. If you haven't already, run website\install.sql in phpMyAdmin (gym_db)." -ForegroundColor Yellow
Write-Host " 2. Visit http://localhost/PosandGymSystem/website/ to view the site." -ForegroundColor Yellow
Write-Host " 3. Log in as admin or cashier and click 'Website' in the sidebar to manage it." -ForegroundColor Yellow
