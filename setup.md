# 🚀 Cabinet Management System - Setup Guide

This guide will help you set up and run the Cabinet Management System using PHP's built-in development server.

## 📋 Prerequisites

- **Windows 10/11** (this guide is Windows-specific)
- **Internet connection** for downloading PHP
- **VS Code** (or any terminal/command prompt)

## 🔧 Step 1: Install PHP

### Option A: Download PHP Manually (Recommended)

1. **Visit the official PHP website**: https://windows.php.net/download/
2. **Download PHP 8.1 or 8.2** (Thread Safe version)
3. **Extract the ZIP file** to `C:\php\`
4. **Your folder structure should look like**:
   ```
   C:\php\
   ├── php.exe
   ├── php.ini
   ├── ext\
   └── ... (other PHP files)
   ```

### Option B: Using XAMPP (Alternative)

1. **Download XAMPP**: https://www.apachefriends.org/download.html
2. **Install XAMPP** (includes PHP, MySQL, Apache)
3. **PHP will be located at**: `C:\xampp\php\`

## 🔧 Step 2: Configure Environment Variables

### Method 1: Using System Properties (Permanent)

1. **Open System Properties**:
   - Press `Windows + R`
   - Type `sysdm.cpl` and press Enter
   - Click "Environment Variables"

2. **Edit PATH variable**:
   - In "System Variables", find and select "Path"
   - Click "Edit"
   - Click "New"
   - Add: `C:\php` (or `C:\xampp\php` if using XAMPP)
   - Click "OK" on all dialogs

### Method 2: Using Command Prompt (Temporary)

```cmd
set PATH=%PATH%;C:\php
```

## 🔧 Step 3: Verify PHP Installation

1. **Open Command Prompt** or **VS Code Terminal**
2. **Run the following command**:
   ```bash
   php --version
   ```
3. **You should see output like**:
   ```
   PHP 8.1.2 (cli) (built: Jan 18 2022 10:10:44) ( NTS Visual C++ 2019 x64 )
   Copyright (c) The PHP Group
   Zend Engine v4.1.2, Copyright (c) Zend Technologies
   ```

## 🚀 Step 4: Run the Cabinet Management System

### Using VS Code Terminal (Recommended)

1. **Open VS Code**
2. **Open the project folder** (`cabinet-inventory-system`)
3. **Open Terminal**:
   - Press `Ctrl + Shift + `` (backtick)
   - Or go to `Terminal > New Terminal`
4. **Run the server**:
   ```bash
   php server.php
   ```

### Using Command Prompt

1. **Open Command Prompt** as Administrator
2. **Navigate to the project directory**:
   ```cmd
   cd C:\Users\Mico\Documents\GitHub\cabinet-inventory-system
   ```
3. **Run the server**:
   ```cmd
   php server.php
   ```

## 📱 Step 5: Access the Application

After running `php server.php`, you'll see output like:

```
🚀 Starting Cabinet Management System Server...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📱 Mobile Access URLs:
   Local:    http://localhost:8080
   Network:  http://192.168.1.100:8080
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 QR Code Scan URL: http://192.168.1.100:8080
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
💡 Tips:
   • Make sure your phone and computer are on the same WiFi network
   • Use the Network URL on your phone's browser
   • QR codes will automatically use the network URL
   • Press Ctrl+C to stop the server
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### Access URLs:

- **🖥️ Desktop**: `http://localhost:8080`
- **📱 Mobile**: `http://192.168.1.100:8080` (use your actual IP)
- **🔗 QR Codes**: Automatically use the network IP

## 🔧 Step 6: Database Setup (Required)

Before using the application, you need to set up the database:

1. **Install MySQL** (or use XAMPP's MySQL)
2. **Create a database** named `cabinet_info_system`
3. **Import the SQL file**:
   ```sql
   -- Run this in MySQL
   source sql/cabinet_info_system.sql;
   ```
4. **Update database configuration** in `includes/config.php`

## 🛠️ Troubleshooting

### PHP Not Found Error

**Error**: `'php' is not recognized as an internal or external command`

**Solution**:
1. **Check if PHP is installed**: Look for `C:\php\php.exe`
2. **Verify PATH variable**: Run `echo %PATH%` in Command Prompt
3. **Restart Command Prompt** after adding PHP to PATH
4. **Try full path**: `C:\php\php.exe server.php`

### Port Already in Use

**Error**: `Address already in use`

**Solution**:
1. **Find what's using port 8080**:
   ```cmd
   netstat -ano | findstr :8080
   ```
2. **Kill the process** (replace PID with actual process ID):
   ```cmd
   taskkill /PID <PID> /F
   ```
3. **Or change the port** in `server.php` (line 74)

### Mobile Device Can't Access

**Problem**: Phone can't connect to the server

**Solutions**:
1. **Check WiFi**: Ensure both devices are on the same network
2. **Check Firewall**: Allow PHP through Windows Firewall
3. **Try different IP**: Use `ipconfig` to find your actual IP
4. **Use localhost**: Try `http://localhost:8080` first

## 📁 Project Structure

```
cabinet-inventory-system/
├── server.php              # 🚀 Main server file
├── setup.md               # 📖 This setup guide
├── admin/                 # 🔐 Admin panel
├── public/                # 🌐 Public interface
├── includes/              # 📚 Core files
├── assets/               # 🎨 CSS, JS, Images
├── sql/                  # 🗄️ Database files
└── qrcodes/              # 📱 Generated QR codes
```

## 🎯 Quick Start Commands

```bash
# 1. Navigate to project directory
cd C:\Users\Mico\Documents\GitHub\cabinet-inventory-system

# 2. Start the server
php server.php

# 3. Open in browser
start http://localhost:8080
```

## 🔄 Stopping the Server

- **Press `Ctrl + C`** in the terminal where the server is running
- **Or close the terminal window**

## 📞 Support

If you encounter issues:

1. **Check PHP version**: `php --version`
2. **Check if port is free**: `netstat -ano | findstr :8080`
3. **Verify file permissions**: Ensure you can read/write in the project directory
4. **Check firewall settings**: Allow PHP through Windows Firewall

---

## 🎉 Success!

Once you see the server running message, you can:

- ✅ **Access the public interface** at `http://localhost:8080`
- ✅ **Access the admin panel** at `http://localhost:8080/admin/`
- ✅ **Use on mobile devices** with the network IP
- ✅ **Scan QR codes** that will work on mobile devices

**Happy coding! 🚀**
