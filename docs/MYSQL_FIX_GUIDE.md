# XAMPP MySQL Connection Fix Guide

## Current Problem
MySQL shows as "running" in XAMPP Control Panel but won't accept connections from PHP.

## Solution 1: Restart MySQL Service (Try This First)

1. Open **XAMPP Control Panel**
2. Click **"Stop"** next to MySQL
3. Wait 10 seconds
4. Click **"Start"** next to MySQL
5. Check if it shows "Running" in green
6. Go to **http://localhost:8080/test.php** and check if "Section 4: Database Connection" shows ✅

## Solution 2: Check MySQL Logs

1. In XAMPP Control Panel, click **"Logs"** button next to MySQL
2. Look for errors related to:
   - Port 3306 already in use
   - innodb_system data file errors
   - Permission denied errors

### Common Error Fixes:

**Error: "Port 3306 is already in use"**
- Another MySQL service is running
- Fix: Open Task Manager → Services → Stop any "MySQL" or "MariaDB" services
- Or Change MySQL port (see Solution 3 below)

**Error: "innodb_system data file 'ibdata1' must be writable"**
- File permissions issue
- Fix: Right-click `C:\xampp\mysql\data\ibdata1` → Properties → Uncheck "Read-only"

## Solution 3: Change MySQL Port (if port 3306 is blocked)

### Step 1: Edit MySQL Config
1. Open `C:\xampp\mysql\bin\my.ini` in a text editor
2. Find the line: `port=3306`
3. Change it to: `port=3307` (or any other port)
4. Save the file

### Step 2: Restart MySQL
- Stop and Start MySQL in XAMPP Control Panel

### Step 3: Update Application Config
Edit `c:\docsaver\src\Config.php`:
```php
const DB_HOST = '127.0.0.1:3307'; // Use the new port
```

## Solution 4: Use Portable MySQL (Alternative)

If XAMPP MySQL won't work at all, you can:
1. Keep using **phpMyAdmin** for database management (it works!)
2. Use a different local MySQL server like:
   - **mysql.exe** standalone
   - **Docker MySQL container**
   - **WampServer** (alternative to XAMPP)

##Solution 5: Test Using mysqli Instead of PDO

Try this test script to isolate the issue:
```php
<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "hospital_docs", 3306);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
} else {
    echo "Connected successfully!";
}
?>
```

## Current Status Check

Run this command to check if MySQL is actually listening on port 3306:
```powershell
netstat -an | findstr ":3306"
```

If you see output like:
```
TCP    0.0.0.0:3306    0.0.0.0:0    LISTENING
```
Then MySQL IS running and listening.

If you see nothing, MySQL isn't bound to port 3306.

---

## Quick Workaround: Direct MySQL Connect Script

I can create a version that uses `mysqli` instead of PDO if the issue persists!
