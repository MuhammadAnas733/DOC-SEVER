# AIH Records Backup System Guide

This document explains the backup system implemented for the AIH Medical Records project.

## 1. Overview
The backup system provides three layers of data protection:
- **Manual Backups**: Triggered instantly from the management UI.
- **Automated Daily Backups**: Set up via Windows Task Scheduler.
- **Full Storage Coverage**: Backs up both the MySQL database and all uploaded medical records (PDFs/Images).

---

## 2. Manual Backup
To trigger a manual backup:
1. Log in as an **Admin**.
2. Go to the **Dashboard**.
3. Click **System Backups** in the left sidebar.
4. Click **Create Manual Backup**.
5. Once complete, the backup will appear in the list. You can **Download** it to your local computer for safe keeping or **Delete** old versions.

---

## 3. Automated (Daily) Backup
The system includes a script for automated backups.

### Setup Instructions (Windows Server):
1. Open **PowerShell** as Administrator.
2. Navigate to your project folder:
   ```powershell
   cd C:\inetpub\wwwroot\records\scripts
   ```
3. Run the setup script:
   ```powershell
   .\setup_daily_backup.ps1
   ```
4. This will create a task in **Windows Task Scheduler** named `AIH_Daily_Backup` which runs every night at **2:00 AM**.

**Note**: Ensure `php.exe` is in your system's PATH. If not, edit `scripts/setup_daily_backup.ps1` to provide the full path to your PHP executable.

---

## 4. Technical Details
- **Storage Location**: All backups are stored in `C:\inetpub\wwwroot\records\backups\`.
- **Format**: Backups are saved as `.zip` files named `backup_YYYY-MM-DD_HH-MM-SS.zip`.
- **Internal Structure**:
  - `database.sql`: A full dump of the `hospital_docs` database.
  - `storage.zip`: A compressed copy of the `storage/` directory containing all medical records.

---

## 5. Security & Maintenance
- **Access**: Only users with `admin` role can access the backup management page and API.
- **Storage Management**: Backups can grow large over time. It is recommended to download backups to an external drive or cloud storage periodically and delete older files from the server's `backups/` folder.
- **Logs**: Automated backup results are logged in `backups/auto_backup_log.txt`.

---

## 6. How to Restore
In case of data loss:
1. Unzip the backup file.
2. **Database**: Import `database.sql` into MySQL using a tool like phpMyAdmin or command line:
   ```bash
   mysql -u root -p hospital_docs < database.sql
   ```
3. **Files**: Unzip `storage.zip` and place its contents back into the `storage/` directory of your project.
