# Alternative Setup Method Using phpMyAdmin

Since the MySQL command line is having connection issues, let's use phpMyAdmin instead:

## Step 1: Open phpMyAdmin
1. Go to: **http://localhost/phpmyadmin**
2. You should see the phpMyAdmin interface

## Step 2: Create Database
1. Click on **"New"** in the left sidebar (or the "Databases" tab at top)
2. In the "Create database" field, type: `hospital_docs`
3. Leave the collation as default (or choose `utf8mb4_general_ci`)
4. Click **"Create"**

## Step 3: Import Schema
1. Click on the `hospital_docs` database in the left sidebar to select it
2. Click the **"Import"** tab at the top
3. Click **"Choose File"** and select: `c:\docsaver\sql\schema.sql`
4. Scroll down and click **"Go"** or **"Import"**
5. You should see a success message showing 3 tables created

## Step 4: Verify
You should now see 3 tables in the left sidebar:
- `patients`
- `upload_history`
- `users`

## Step 5: Test the Application
1. Go back to: **http://localhost:8080**
2. Refresh the page
3. The "server communication error" should be gone!
4. Try registering a test patient

---

## Troubleshooting MySQL Connection

If MySQL shows as "running" in XAMPP but you still get connection errors, try:

1. **Stop and Restart MySQL** in XAMPP Control Panel
2. Check the MySQL error log:
   - In XAMPP Control Panel, click "Logs" next to MySQL
   - Look for errors related to port 3306 or ibdata

3. **Check if port 3306 is blocked** by another service
   - Open Command Prompt as Administrator
   - Run: `netstat -ano | findstr :3306`
   - If another process is using it, you may need to stop that service

4. **Alternative: Use phpMyAdmin** (which we're doing above) - it connects through Apache using PHP, bypassing the MySQL port issue
