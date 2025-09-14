# Cabinet Information System - Mobile Server Starter
# PowerShell script for cross-platform support

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host " Cabinet Information System" -ForegroundColor Green  
Write-Host " Mobile-Accessible Development Server" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Check if PHP is installed
try {
    $phpVersion = php --version 2>$null
    if ($phpVersion) {
        Write-Host "✅ PHP is installed" -ForegroundColor Green
    } else {
        throw "PHP not found"
    }
} catch {
    Write-Host "❌ Error: PHP is not installed or not in PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install PHP and add it to your system PATH" -ForegroundColor Yellow
    Write-Host "Download PHP from: https://www.php.net/downloads" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host ""
Write-Host "🚀 Starting mobile-accessible server..." -ForegroundColor Cyan
Write-Host ""

# Start the PHP server using our custom script
php server.php

Read-Host "Press Enter to exit"