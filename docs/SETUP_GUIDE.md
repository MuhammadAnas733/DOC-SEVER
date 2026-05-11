# Quick Setup Guide for DocSaver

## Step 1: Start XAMPP MySQL

1. Open **XAMPP Control Panel** (usually at `C:\xampp\xampp-control.exe`)
2. Click **Start** next to **MySQL**
3. Wait until the MySQL status shows as **Running** (green background)

## Step 2: Create the Database

Once MySQL is running, open a terminal in the `c:\docsaver` directory and run:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS hospital_docs;"
C:\xampp\mysql\bin\mysql.exe -u root hospital_docs < sql/schema.sql
```

Or you can use phpMyAdmin:
1. Go to http://localhost/phpmyadmin
2. Click "New" to create a database
3. Name it `hospital_docs`
4. Go to "Import" tab and upload `sql/schema.sql`

## Step 3: Access the Application

The PHP development server is already running at:

**http://localhost:8080**

Just open this URL in your browser!

## Testing the System

### Register a Patient:
1. Fill in the "Register New Patient" form:
   - MRN: `HOSP-001`
   - Full Name: `John Doe`
   - Date of Birth: (any date)
   - Gender: `Male`
2. Click "Register & Create Master PDF"

### Append a Medical Record:
1. In the "Append Medical Record" section:
   - Enter the MRN: `HOSP-001`
   - Upload a PDF or image file
2. Click "Append to Master File"

### View Master PDF:
- The patient will appear in the "Recent Patients" table
- Click "View Master PDF" to see the consolidated record

## Troubleshooting

**Error: "Database connection failed"**
- Make sure MySQL is running in XAMPP Control Panel
- Check that the database `hospital_docs` exists
- Verify credentials in `src/Config.php` (default: user=root, password=empty)

**Error: "Could not write to file"**
- Make sure the `storage/pdfs/` directory exists and is writable
- On Windows, right-click → Properties → Security → Edit → Grant full control

**PDF not displaying**
- Check browser console for errors
- Verify that the master PDF exists in `storage/pdfs/`
- Some browsers may block inline PDFs - try downloading instead
