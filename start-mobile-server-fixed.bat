@echo off
title Cabinet Information System - Mobile Server (Fixed)
color 0A

echo.
echo ========================================
echo  Cabinet Information System
echo  Mobile-Accessible Development Server
echo  [Windows Compatible Version]
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

REM Show current IP address for reference
echo 📡 Detecting your network IP address...
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do (
    for /f "tokens=1" %%b in ("%%a") do (
        echo    Possible IP: %%b
    )
)
echo.

echo 💡 Manual Setup Instructions:
echo    1. Note one of the IP addresses above (usually starts with 192.168 or 10.)
echo    2. The server will start on port 8080
echo    3. Use http://[YOUR-IP]:8080 on your phone
echo    Example: http://192.168.1.100:8080
echo.

echo 🚀 Starting mobile-accessible server...
echo.

REM Start the PHP server
php server.php

pause