# 📱 Mobile Access Setup Guide

This guide will help you set up your Cabinet Information System to be accessible from mobile devices on your local network.

## 🚀 Quick Start

### Option 1: Windows Batch File (Easiest)
1. Double-click `start-mobile-server.bat`
2. Follow the on-screen instructions
3. Use the displayed network URL on your phone

### Option 2: PowerShell Script
1. Right-click `start-mobile-server.ps1` → "Run with PowerShell"
2. If execution policy blocks it, run: `Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser`
3. Use the displayed network URL on your phone

### Option 3: Manual PHP Command
1. Open terminal/command prompt in the project folder
2. Run: `php server.php`
3. Use the displayed network URL on your phone

## 📋 Prerequisites

- ✅ PHP installed on your computer
- ✅ Your computer and phone on the same WiFi network
- ✅ Firewall allowing connections on port 8080

## 🔧 How It Works

1. **Network Detection**: The server automatically detects your local IP address
2. **QR Code Updates**: All QR codes are automatically generated with the network URL
3. **Mobile Access**: Your phone can access the site using the network IP address

## 📱 Mobile Access URLs

When you start the server, you'll see:
```
📱 Mobile Access URLs:
   Local:    http://localhost:8080
   Network:  http://192.168.1.100:8080  (example)

📋 QR Code Scan URL: http://192.168.1.100:8080
```

## 🎯 Testing Mobile Access

1. **Start the Server**
   ```bash
   php server.php
   ```

2. **Note the Network URL**
   - Look for the "Network:" URL in the output
   - Example: `http://192.168.1.100:8080`

3. **Test on Mobile**
   - Open your phone's browser
   - Navigate to the Network URL
   - You should see the Cabinet Information System login page

4. **Test QR Codes**
   - Generate a QR code for any cabinet
   - Scan it with your phone
   - It should open the cabinet details in your mobile browser

## 🔍 Troubleshooting

### Problem: Can't access from phone
- **Solution**: Make sure both devices are on the same WiFi network
- **Check**: Your computer's firewall settings (allow port 8080)

### Problem: Server shows 127.0.0.1 instead of network IP
- **Solution**: Make sure you're connected to WiFi, not just ethernet
- **Try**: Restart the server after connecting to WiFi

### Problem: QR codes don't work on mobile
- **Check**: The QR code should contain your network IP, not localhost
- **Solution**: Regenerate QR codes after starting the mobile server

### Problem: PHP not found
- **Solution**: Install PHP from https://www.php.net/downloads
- **Windows**: Add PHP to your system PATH
- **Mac**: Use Homebrew: `brew install php`

## 🛡️ Security Notes

- This setup is for **development/local use only**
- The server is accessible to anyone on your local network
- Don't use this setup for production or public networks
- Consider using authentication for sensitive data

## ⚙️ Advanced Configuration

### Custom Port
Edit `server.php` and change the `$port` variable:
```php
$port = 8080; // Change to your preferred port
```

### Custom IP Binding
The server binds to `0.0.0.0` (all interfaces) by default. You can restrict this by editing the `$host` variable in `server.php`.

## 🌟 Features for Mobile

- ✅ Responsive design works on mobile browsers
- ✅ QR code scanning opens cabinet details
- ✅ Touch-friendly interface
- ✅ Fast loading on mobile networks
- ✅ All features available on mobile

## 📞 Support

If you encounter issues:
1. Check the troubleshooting section above
2. Ensure PHP is properly installed
3. Verify network connectivity
4. Check firewall settings

## 🎉 Success!

Once everything is working:
- Your Cabinet Information System is accessible from any device on your network
- QR codes can be scanned from mobile devices
- You can manage cabinets from your phone or tablet
- Perfect for field use and inventory management!