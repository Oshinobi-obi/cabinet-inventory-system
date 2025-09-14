@echo off
title Cabinet Information System - Mobile Server
color 0A

echo.
echo ========================================
echo  Cabinet Information System
echo  Mobile-Accessible Development Server
echo ========================================
echo.

REM Check if PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Error: PHP is not installed or not in PATH
    echo.
    echo Please install PHP and add it to your system PATH
    echo Download PHP from: https://www.php.net/downloads
    pause
    exit /b 1
)

echo ✅ PHP is installed
echo.
echo 🚀 Starting mobile-accessible server...
echo.

REM Start the PHP server
php server.php

pause