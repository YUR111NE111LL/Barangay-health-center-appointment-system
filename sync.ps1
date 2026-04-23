param(
    [string]$Branch = "main",
    [switch]$NoBuild,
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

$scriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $scriptRoot

$logDir = Join-Path $scriptRoot "storage\logs\sync"
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}

$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$logFile = Join-Path $logDir "sync_$timestamp.log"

function Write-Log {
    param([string]$Message)
    $line = "[{0}] {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $Message
    Write-Host $line
    Add-Content -Path $logFile -Value $line
}

function Invoke-Step {
    param(
        [string]$Description,
        [scriptblock]$Action
    )
    Write-Log "START: $Description"
    if ($DryRun) {
        Write-Log "DRY RUN: skipped execution for '$Description'"
        return
    }

    & $Action
    Write-Log "DONE: $Description"
}

try {
    Write-Log "Sync started in $scriptRoot"

    Invoke-Step "Validate git repository" {
        git rev-parse --is-inside-work-tree | Out-Null
    }

    $dirty = git status --porcelain
    if (-not [string]::IsNullOrWhiteSpace(($dirty -join ""))) {
        throw "Working tree has local changes. Commit/stash first before auto sync."
    }

    $currentBranch = (git rev-parse --abbrev-ref HEAD).Trim()
    if ($currentBranch -ne $Branch) {
        throw "Current branch is '$currentBranch'. Checkout '$Branch' before running sync."
    }

    Invoke-Step "Fetch latest commits" {
        git fetch origin $Branch --prune
    }

    Invoke-Step "Pull latest code" {
        git pull --ff-only origin $Branch
    }

    Invoke-Step "Install PHP dependencies" {
        composer install --no-interaction --prefer-dist --no-progress
    }

    Invoke-Step "Install Node dependencies" {
        npm install --no-audit --no-fund
    }

    Invoke-Step "Run database migrations" {
        php artisan migrate --force
    }

    Invoke-Step "Clear and rebuild Laravel caches" {
        php artisan optimize:clear
    }

    if (-not $NoBuild) {
        Invoke-Step "Build frontend assets" {
            npm run build
        }
    } else {
        Write-Log "SKIP: Frontend build disabled by -NoBuild"
    }

    Write-Log "Sync completed successfully."
    exit 0
} catch {
    Write-Log "ERROR: $($_.Exception.Message)"
    exit 1
}
