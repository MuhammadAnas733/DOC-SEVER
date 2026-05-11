# DocSaver - Secure Hospital Record System

DocSaver is a secure, consolidated medical record management system designed for **Advanced International Hospital, Islamabad**. It allows medical staff to register patients, upload medical reports, and automatically consolidate them into a single Master PDF for each patient.

## 🚀 Key Features
- **Consolidated Records**: Automatically appends new reports (PDF, Images, Word) to a central Master PDF.
- **Secure Access**: Integrated authentication system with role-based access.
- **Hospital Branding**: Custom logo and professional interface for AIH.
- **IIS Optimized**: Configured specifically for Internet Information Services (IIS) on Windows.

## 📁 Project Structure
```text
records/
├── docs/               # Detailed setup and deployment guides
├── public/             # Web root (IIS should point here)
│   ├── assets/         # CSS, JS, and Images (AIH Logo)
│   ├── api.php         # Main backend API
│   ├── index.php       # Dashboard (Authenticated)
│   └── login.php       # Secure Login Page
├── sessions/           # Local PHP session storage (Secure)
├── sql/                # Database schema
├── src/                # Core logic (Auth, Database, PDF Management)
├── storage/            # Patient Master PDFs and individual uploads
└── vendor/             # Composer dependencies (FPDF, FPDI)
```

## �️ Installation & Setup

### 1. Requirements
- **IIS** with PHP 8.0+ enabled.
- **MySQL 8.0** (XAMPP or Standalone).
- **URL Rewrite Module** for IIS.

### 2. Database Configuration
1. Create a database named `hospital_docs`.
2. Import the schema from `sql/schema.sql`.
3. Update `src/Config.php` with your database credentials:
```php
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'hospital_docs';
const DB_USER = 'root';
const DB_PASS = 'your_password';
```

### 3. IIS Website Configuration
- Set the **Physical Path** of your IIS site to the `public/` folder.
- Ensure the `sessions/` and `storage/` folders have **Modify** permissions for the `IIS_IUSRS` user.

## � Authentication
Default credentials (change immediately after first login):
- **Username**: `admin`
- **Password**: `password`

## 📚 Documentation
For detailed troubleshooting and specialized setup, please refer to the `docs/` directory:
- [IIS Deployment Guide](docs/IIS_DEPLOYMENT_GUIDE.md)
- [MySQL Fix Guide](docs/MYSQL_FIX_GUIDE.md)
- [phpMyAdmin Setup](docs/SETUP_DATABASE_PHPMYADMIN.md)

---
© 2026 Advanced International Hospital, Islamabad.
