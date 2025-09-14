@echo off
title Cabinet Information System - Simple Mobile Server
color 0A

echo.
echo ==========================================
echo  Cabinet Information System
echo  Simple Mobile Server (No Socket Required)
echo ==========================================
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

echo ✅ PHP is ready
echo.

REM Start the simple PHP server
php server-simple.php

echo.
echo Server stopped.
pause