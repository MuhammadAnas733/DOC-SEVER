# IIS Deployment Guide for Records (DocSaver)

This project has been prepared for hosting on IIS. Follow these steps to make it live.

## 1. Prerequisites (Verified on your system)
- **IIS Installed**: Ensure "Internet Information Services" is enabled in Windows Features.
- **PHP Path**: `C:\Program Files\php\php.exe`
- **MySQL Path**: `C:\Program Files\mysql\MySQL Server 8.0\bin\mysql.exe`
- **URL Rewrite Module**: Ensure the "URL Rewrite" module for IIS is installed.

## 2. Directory Permissions (Done)
I have already set the necessary permissions for the `storage` folder:
- `IIS_IUSRS` has **Modify** access to `c:\inetpub\wwwroot\records\storage`.

## 3. IIS Configuration Steps (CRITICAL)

1. **Open IIS Manager**:
   - Right-click **Sites** -> **Add Website**.
   - **Site name**: `records`
   - **Physical path**: `c:\inetpub\wwwroot\records\public` (**MUST point to the public folder!**)
   - **Port**: `8081` (or your choice).

2. **Configure PHP (If not done)**:
   - Select your site -> **Handler Mappings**.
   - Add a "Module Mapping":
     * **Request path**: `*.php`
     * **Module**: `FastCgiModule`
     * **Executable**: `C:\Program Files\php\php-cgi.exe`
     * **Name**: `PHP_via_FastCGI`

3. **Check .NET / URL Rewrite**:
   - Ensure the "URL Rewrite" icon appears in your site features. If not, you must install it from [IIS.net](https://www.iis.net/downloads/microsoft/url-rewrite).

## 4. Database Configuration (Final Step)
I have updated `c:\inetpub\wwwroot\records\src\Config.php`. 
You **MUST** update the password here:
```php
const DB_USER = 'root';
const DB_PASS = 'YOUR_MYSQL_PASSWORD'; // Set your actual password here!
```

## 5. Test the Deployment
Visit: `http://localhost:8081/index.php`
Or if you set a custom IP: `http://192.168.91.116:8081/`
