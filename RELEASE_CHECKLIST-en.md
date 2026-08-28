# English

## 1. Release Information

```text
System Name: Wazin ERP
Version: v1.0.0
Release Type: Production / Commercial Release
Environment: Provider-managed hosting
```

---

## 2. Environment Check

Before approving the release, verify:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL is set to the real system URL
Database credentials are correct
Mail settings are correct if used
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

---

## 3. Installation Commands Check

Run and verify that all commands complete successfully:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan permission:cache-reset
php artisan optimize:clear
php artisan optimize
```

---

## 4. Login Check

Verify login with:

```text
Master:
admin@wazin.test
12345678

Company Owner:
owner@wazin.test
12345678
```

Default passwords must be changed after the first login.

---

## 5. Base Data Check

Verify that the following records exist:

```text
Main company
Main branch
Master user
Company Owner user
Default roles
Permissions
Chart of accounts
Account settings
Document sequences
License settings
```

---

## 6. Roles and Permissions Check

Verify that the following roles exist:

```text
Master
System Admin
Company Owner
Company Admin
Branch Admin
Employee
```

Verify that:

```text
Master can access everything
System Admin can access system support tools
Company Owner can manage the company
Company Admin can manage company operations
Branch Admin is limited to branch scope
Employee is limited by assigned permissions
```

---

## 7. Companies and Branches Check

Test:

```text
View companies
Create company
Edit company
Deactivate company
View branches
Create branch
Edit branch
Prevent Branch Admin and Employee from accessing companies and branches
```

---

## 8. Users Check

Test:

```text
Create user
Edit user
Deactivate user
Assign user to branch
Assign role to user
Prevent managing users with higher level
Prevent Branch Admin from managing users outside their branch
```

---

## 9. Master Data Check

Test the following pages:

```text
Warehouses
Units
Categories
Brands
Products
Customers
Suppliers
Cost centers
Chart of accounts
Account settings
```

For each page, verify:

```text
View
Create
Edit
Delete if available
Permissions
Messages
```

---

## 10. Sales Cycle Check

Run a complete sales cycle:

```text
Create quotation
Approve quotation
Convert quotation to sales invoice
Post sales invoice
Print sales invoice
Create customer receipt voucher
Post customer receipt voucher
Print customer receipt voucher
```

Then verify:

```text
Customer statement
Customer balances
Sales report
Sales profit report
General ledger
Trial balance
Inventory movement
```

---

## 11. Sales Returns Check

Run:

```text
Create sales return
Post sales return
Print sales return
Create customer refund voucher if needed
Post refund voucher
Print refund voucher
```

Verify:

```text
Stock quantity increased
Customer balance updated
Reports updated
Journal entry exists
```

---

## 12. Purchase Cycle Check

Run:

```text
Create purchase invoice
Post purchase invoice
Print purchase invoice
Create supplier payment voucher
Post supplier payment voucher
Print supplier payment voucher
```

Then verify:

```text
Supplier balance
Supplier statement
Supplier balances
Inventory movement
General ledger
Trial balance
```

---

## 13. Purchase Returns Check

Run:

```text
Create purchase return
Post purchase return
Print purchase return
```

Verify:

```text
Stock quantity decreased
Supplier balance updated
Reports updated
Journal entry exists
```

---

## 14. Journal and General Vouchers Check

Test:

```text
Manual journal entry
Post journal entry
Print journal entry
General receipt voucher
Post general receipt voucher
Print general receipt voucher
General payment voucher
Post general payment voucher
Print general payment voucher
```

Then check:

```text
General ledger
Trial balance
Income statement
Cash flow statement
```

---

## 15. Reports Check

Open and test:

```text
Sales report
Sales profit report
Inventory movement report
Customer statement
Supplier statement
Customer balances
Supplier balances
General ledger
Trial balance
Income statement
Balance sheet
Cash flow statement
```

For each report, verify:

```text
Filters
Permissions
Result accuracy
Printing
Export if available
No data leakage outside user scope
```

---

## 16. Printing Check

Verify printing for:

```text
Quotation
Sales invoice
Sales return
Customer receipt voucher
Customer refund voucher
Purchase invoice
Purchase return
Supplier payment voucher
General receipt voucher
General payment voucher
Manual journal entry
```

Verify that prints show:

```text
Company name
Company logo
Branch information
VAT number
Address
Document number
Document status
Signatures
```

---

## 17. License Check

Test:

```text
View license page
Update license information
License start date
License expiry date
Allowed users count
Allowed branches count
License status
Block users after license expiry
Allow Master/System Admin access for support
```

---

## 18. Backup Check

Test:

```text
Create backup
Download backup
Delete backup
Prevent unauthorized access
```

---

## 19. Security Check

Verify:

```text
APP_DEBUG=false
.env is not committed to GitHub
Domain does not point to project root
Domain points to public directory
Default passwords changed
File permissions are correct
Admin pages are protected
No buttons are visible without permission
```

---

## 20. Performance Check

Verify:

```text
Pages load quickly
DataTables are responsive
Reports do not timeout
Printing is not too slow
Cache is enabled
Optimize commands run correctly
```

---

## 21. Logs Check

Review:

```text
storage/logs/laravel.log
```

Make sure there are no recent errors after testing.

---

## 22. Release Approval Commands

After successful testing, run:

```bash
php artisan permission:cache-reset
php artisan optimize:clear
php artisan optimize
git status
git add .
git commit -m "Prepare Wazin ERP v1.0.0 release"
git tag v1.0.0
git push
git push origin v1.0.0
```

---

## 23. Test Result

```text
Release Status: Ready / Not Ready
Tester Name:
Test Date:
Notes:
```

---

# Version

```text
Product: Wazin ERP
Checklist Version: 1.0.0
Environment: Production Hosting
```