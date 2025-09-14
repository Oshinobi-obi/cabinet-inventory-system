# MySQL Workbench Configuration for Network Access
# Cabinet Information System - Mobile Setup

## Step-by-Step Guide for MySQL Workbench Users

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

### 2. Grant Network Access to Database User

#### Using MySQL Workbench:
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