
# English

## 1. System Overview

Wazin ERP is a business management system for sales, purchases, inventory, accounting, vouchers, journal entries, reports, users, roles, permissions, companies, and branches.

This version is intended to run on hosting managed by the service provider, with direct technical support. It is not intended as a source-code delivery package for the final client.

---

## 2. Server Requirements

Before installation, make sure the server has:

```text
PHP 8.2 or higher
MySQL or MariaDB
Composer
Node.js
npm
Apache or Nginx
SSL Certificate
```

Required PHP extensions:

```text
pdo_mysql
mbstring
openssl
tokenizer
xml
ctype
json
fileinfo
zip
curl
gd
intl
```

---

## 3. Upload Project Files

Upload the project files outside `public_html` when possible.

Example:

```text
/home/username/wazin-erp
```

The domain must point to the Laravel `public` directory:

```text
/home/username/wazin-erp/public
```

Correct example:

```text
domain.com  →  /public
```

Incorrect example:

```text
domain.com  →  /wazin-erp
```

---

## 4. Create Database

Create a new MySQL database from the hosting control panel.

Example:

```text
Database Name: wazin_erp
Database User: wazin_user
Database Password: strong_password_here
```

The database user must have full privileges on the database.

---

## 5. Configure Environment File

Copy:

```text
.env.example
```

to:

```text
.env
```

Then update the main values:

```env
APP_NAME="Wazin ERP"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

APP_LOCALE=ar
APP_FALLBACK_LOCALE=ar
APP_FAKER_LOCALE=ar_SA

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wazin_erp
DB_USERNAME=wazin_user
DB_PASSWORD=strong_password_here

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=smtp.your-domain.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

---

## 6. Install Laravel Dependencies

Run the following command inside the project directory:

```bash
composer install --no-dev --optimize-autoloader
```

---

## 7. Build Frontend Assets

Run:

```bash
npm install
npm run build
```

If the hosting provider does not support Node.js, run these commands locally, then upload the generated build files to the server.

---

## 8. Generate Application Key

Run:

```bash
php artisan key:generate
```

---

## 9. Run Migrations and Seeders

For the first installation only, run:

```bash
php artisan migrate --seed
```

This will create:

```text
Main company
Main branch
Master user
Company owner user
Roles and permissions
Chart of accounts
Account settings
Document sequences
Default operational data
License settings
```

---

## 10. Create Storage Link

Run:

```bash
php artisan storage:link
```

---

## 11. Clear and Optimize Cache

After installation, run:

```bash
php artisan permission:cache-reset
php artisan optimize:clear
php artisan optimize
```

---

## 12. Default Login Credentials

### Master Account

```text
Email: admin@wazin.test
Password: 12345678
```

### Company Owner Account

```text
Email: owner@wazin.test
Password: 12345678
```

Default passwords must be changed immediately after the first login.

---

## 13. First Login Checklist

After the first login, review and update:

```text
Company information
Branch information
VAT number
Commercial registration
National address
Company logo
Users
Roles and permissions
License settings
Account settings
```

---

## 14. Commands After Each Update

After uploading a new system update, run:

```bash
php artisan migrate
php artisan permission:cache-reset
php artisan optimize:clear
php artisan optimize
```

If frontend files were changed, run:

```bash
npm install
npm run build
```

---

## 15. Backup

Before any update, take a backup of:

```text
Database
.env file
storage directory
Current project files
```

A backup can also be created from inside the system if the backup permission is enabled.

---

## 16. Security Notes

The following rules must be applied:

```text
APP_DEBUG=false
Do not upload .env to GitHub
Point the domain to the public directory only
Change default passwords
Use SSL
Do not give the client access to the source code
Enable regular backups
Review user permissions
```

---

## 17. Useful Commands

List routes:

```bash
php artisan route:list
```

Clear cache:

```bash
php artisan optimize:clear
```

Reset Spatie permission cache:

```bash
php artisan permission:cache-reset
```

Run seeders only:

```bash
php artisan db:seed
```

Rebuild the database from scratch, for testing environment only:

```bash
php artisan migrate:fresh --seed
```

Warning: this command deletes all existing data.

---

## 18. Operational Notes

This version is designed to run on hosting managed by the service provider.

The client accesses the system through the domain only. Project files and database access are not delivered to the client unless agreed otherwise.

Technical support, updates, and backups are handled by the service provider.

---

## 19. Technical Support

For any installation or runtime issue, send the following information to technical support:

```text
System URL
Screenshot of the error
Time of the error
Affected username
Steps that caused the error
laravel.log file if available
```

The log file is usually located at:

```text
storage/logs/laravel.log
```

---

# Version

```text
Product: Wazin ERP
Installation Guide Version: 1.0.0
Environment: Production Hosting
```