# MySQL Workbench Configuration for Network Access

# Cabinet Information System - Complete Database Setup

## Step-by-Step Guide for MySQL Workbench Users

### 🆕 **Latest Updates (v2.0)**
- **🔐 Password Reset System**: Complete forgot password functionality with token-based security
- **📊 Enhanced Database Schema**: Added `password_reset_tokens` table for secure authentication
- **🔒 Security Features**: Time-limited, single-use reset tokens with automatic cleanup
- **📧 Email Integration**: Automated password reset emails with secure token generation

### 1. Configure MySQL Server for Network Access

#### Method 1: Using MySQL Workbench (Recommended)

1. **Open MySQL Workbench**
2. **Connect to your Local instance MySQL80**
3. **Go to Server → Options File**
4. **Navigate to "Networking" tab**
5. **Find "bind-address" setting**
6. **Change from `127.0.0.1` to `0.0.0.0`**
7. **Apply changes and restart MySQL server**

#### Method 2: Manual Configuration File Edit

Your MySQL config file is likely at:

```
C:\ProgramData\MySQL\MySQL Server 8.0\my.ini
```

Edit this file and find:

```ini
[mysqld]
bind-address = 127.0.0.1
```

Change it to:

```ini
[mysqld]
bind-address = 0.0.0.0
```

### 2. Complete Database Setup

#### **Step 2a: Import Main Database Schema**

1. **Open MySQL Workbench**
2. **Connect to your MySQL server**
3. **Create the database:**

```sql
CREATE DATABASE IF NOT EXISTS cabinet_info_system;
USE cabinet_info_system;
```

4. **Import the main schema:**

```sql
-- Run the contents of cabinet_info_system.sql
-- This creates all main tables: users, cabinets, items, categories, etc.
```

#### **Step 2b: Add Password Reset Table**

1. **Create the password reset tokens table:**

```sql
-- Password reset functionality table
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Create index for better performance
CREATE INDEX idx_token ON password_reset_tokens(token);
CREATE INDEX idx_user_id ON password_reset_tokens(user_id);
CREATE INDEX idx_expires_at ON password_reset_tokens(expires_at);
```

#### **Step 2c: Grant Network Access to Database User**

1. **Open a new SQL tab in Workbench**
2. **Run these commands:**

```sql
-- Create or update user for network access
CREATE USER 'root'@'%' IDENTIFIED BY 'Mico2025!';

-- Grant all privileges on your database
GRANT ALL PRIVILEGES ON cabinet_info_system.* TO 'root'@'%';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify the user was created
SELECT host, user FROM mysql.user WHERE user = 'root';
```

### 3. Restart MySQL Service

#### Using Services (Windows):

1. **Press Win + R, type `services.msc`**
2. **Find "MySQL80" service**
3. **Right-click → Restart**

#### Using MySQL Workbench:

1. **Go to Server → Startup/Shutdown**
2. **Click "Stop Server"**
3. **Click "Start Server"**

### 4. Test the Configuration

#### Check if MySQL is listening on network interface:

Open Command Prompt and run:

```cmd
netstat -an | findstr 3306
```

You should see:

```
TCP    0.0.0.0:3306    0.0.0.0:0    LISTENING
```

(Not just 127.0.0.1:3306)

### 5. Verify Connection

Run your diagnostic script:

```
http://192.168.100.24:8080/database-test.php
```

This will test both localhost and network connections.

### 6. Password Reset System Configuration

#### **Email Configuration for Password Reset**

1. **Update email settings in `includes/email_config_user.json`:**

```json
{
  "email_service": "gmail",
  "smtp_host": "smtp.gmail.com",
  "smtp_port": 587,
  "smtp_username": "your-email@gmail.com",
  "smtp_password": "your-app-password",
  "from_email": "your-email@gmail.com",
  "from_name": "Cabinet Inventory System"
}
```

2. **Test password reset functionality:**

- Visit: `http://192.168.100.24:8080/admin/login.php`
- Click "Forgot Password?" link
- Enter a registered email address
- Check email for reset instructions

#### **Password Reset Security Features**

- **Token Expiration**: Reset tokens expire after 1 hour
- **One-time Use**: Tokens are invalidated after successful use
- **Secure Generation**: Cryptographically secure token generation
- **Automatic Cleanup**: Expired tokens are automatically removed

---

## Security Notes

⚠️ **Important Security Considerations:**

1. **This setup allows connections from any IP on your network**
2. **Only use this for development/local network access**
3. **Never expose this setup to the public internet**
4. **Consider creating a separate user with limited privileges:**

```sql
-- Create a limited user for mobile access
CREATE USER 'mobile_user'@'192.168.100.%' IDENTIFIED BY 'your_secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON cabinet_info_system.* TO 'mobile_user'@'192.168.100.%';
FLUSH PRIVILEGES;
```

---

## Troubleshooting

### If you get "Access denied" errors:

1. Make sure the user has network access permissions
2. Check if Windows Firewall is blocking port 3306
3. Verify the bind-address is set to 0.0.0.0

### If you get "Connection refused" errors:

1. Check if MySQL service is running
2. Verify port 3306 is open
3. Ensure bind-address is configured correctly

### Quick Test Commands:

```sql
-- Show current users and their allowed hosts
SELECT host, user, authentication_string FROM mysql.user;

-- Show current database grants for root
SHOW GRANTS FOR 'root'@'%';

-- Test if you can connect from network
-- (Run this from another computer on your network)
mysql -h 192.168.100.24 -u root -p cabinet_info_system
```

### Password Reset System Troubleshooting:

#### **If password reset emails are not being sent:**

1. **Check email configuration:**
   - Verify `includes/email_config_user.json` settings
   - Ensure Gmail app password is correct
   - Test email service with a simple email

2. **Check database connection:**
   - Verify `password_reset_tokens` table exists
   - Check if tokens are being created
   - Ensure user email exists in database

3. **Check logs:**
   - Review `logs/email_activity.log`
   - Check `logs/email_log.txt`
   - Look for PHP error logs

#### **If password reset tokens are not working:**

```sql
-- Check if password reset table exists
SHOW TABLES LIKE 'password_reset_tokens';

-- Check table structure
DESCRIBE password_reset_tokens;

-- View recent reset attempts
SELECT * FROM password_reset_tokens ORDER BY created_at DESC LIMIT 10;

-- Clean up expired tokens
DELETE FROM password_reset_tokens WHERE expires_at < NOW();
```

#### **Test password reset flow:**

1. **Request password reset** from login page
2. **Check database** for new token creation
3. **Verify email** is sent successfully
4. **Test token** by clicking reset link
5. **Verify password** is updated correctly
