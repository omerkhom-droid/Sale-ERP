<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SystemSetupSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | الشركة الرئيسية
        |--------------------------------------------------------------------------
        */
        $company = Company::query()->updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name_ar' => 'الشركة الرئيسية',
                'name_en' => 'Main Company',
                'email' => 'info@wazin.test',
                'phone' => '0500000000',
                'tax_number' => '300000000000003',
                'commercial_registration' => '1010000000',
                'city' => 'الرياض',
                'address' => 'الرياض - المملكة العربية السعودية',
                'logo' => null,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | الفرع الرئيسي
        |--------------------------------------------------------------------------
        */
        $branch = Branch::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'branch_name' => 'الفرع الرئيسي',
            ],
            [
                'preceatage' => 0,

                'tax_registration_number' => '300000000000003',
                'license_type' => 'CRN',
                'license_number' => '1010000000',

                'country_code' => 'SA',
                'state' => 'الرياض',
                'city' => 'الرياض',
                'neighborhood' => 'الرياض',
                'street_name' => 'طريق الملك فهد',
                'additional_street_name' => null,
                'building_number' => '1234',
                'secondary_number' => '1234',
                'postal_zone' => '12345',

                'phone' => '0500000000',
                'details' => 'الفرع الرئيسي',
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | الصلاحيات النهائية
        |--------------------------------------------------------------------------
        */
        $permissions = [
            'dashboard.view',

            /*
            |--------------------------------------------------------------------------
            | النظام والدعم الفني
            |--------------------------------------------------------------------------
            */
            'backups.view',
            'backups.create',
            'backups.download',
            'backups.delete',

            'licenses.view',
            'licenses.update',

            /*
            |--------------------------------------------------------------------------
            | الشركات والفروع والإعدادات
            |--------------------------------------------------------------------------
            */
            'companies.view',
            'companies.create',
            'companies.edit',
            'companies.delete',

            'company_settings.view',
            'company_settings.edit',
            'company_settings.update',

            'branches.view',
            'branches.create',
            'branches.edit',
            'branches.delete',

            /*
            |--------------------------------------------------------------------------
            | المستخدمون والأدوار
            |--------------------------------------------------------------------------
            */
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            /*
            |--------------------------------------------------------------------------
            | البيانات الأساسية
            |--------------------------------------------------------------------------
            */
            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',

            'units.view',
            'units.create',
            'units.edit',
            'units.delete',

            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            'brands.view',
            'brands.create',
            'brands.edit',
            'brands.delete',

            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',

            /*
            |--------------------------------------------------------------------------
            | المخزون
            |--------------------------------------------------------------------------
            */
            'opening_stock.view',
            'opening_stock.create',
            'opening_stock.edit_draft',
            'opening_stock.post',
            'opening_stock.cancel',
            'opening_stock.delete_draft',

            'inventory_movements.view',

            /*
            |--------------------------------------------------------------------------
            | عروض الأسعار
            |--------------------------------------------------------------------------
            */
            'quotations.view',
            'quotations.create',
            'quotations.edit',
            'quotations.cancel',
            'quotations.print',
            'quotations.convert',
            'quotations.approve',
            'quotations.reject',

            /*
            |--------------------------------------------------------------------------
            | المبيعات
            |--------------------------------------------------------------------------
            */
            'sales_invoices.view',
            'sales_invoices.create',
            'sales_invoices.edit_draft',
            'sales_invoices.post',
            'sales_invoices.cancel',
            'sales_invoices.print',
            'sales_invoices.delete_draft',

            'sales_returns.view',
            'sales_returns.create',
            'sales_returns.edit_draft',
            'sales_returns.post',
            'sales_returns.cancel',
            'sales_returns.print',
            'sales_returns.delete_draft',

            'customer_receipt_vouchers.view',
            'customer_receipt_vouchers.create',
            'customer_receipt_vouchers.edit_draft',
            'customer_receipt_vouchers.post',
            'customer_receipt_vouchers.cancel',
            'customer_receipt_vouchers.print',
            'customer_receipt_vouchers.delete_draft',

            'customer_refund_vouchers.view',
            'customer_refund_vouchers.create',
            'customer_refund_vouchers.edit_draft',
            'customer_refund_vouchers.post',
            'customer_refund_vouchers.cancel',
            'customer_refund_vouchers.print',
            'customer_refund_vouchers.delete_draft',

            /*
            |--------------------------------------------------------------------------
            | المشتريات
            |--------------------------------------------------------------------------
            */
            'purchase_invoices.view',
            'purchase_invoices.create',
            'purchase_invoices.edit_draft',
            'purchase_invoices.post',
            'purchase_invoices.cancel',
            'purchase_invoices.print',
            'purchase_invoices.delete_draft',

            'purchase_returns.view',
            'purchase_returns.create',
            'purchase_returns.edit_draft',
            'purchase_returns.post',
            'purchase_returns.cancel',
            'purchase_returns.print',
            'purchase_returns.delete_draft',

            'supplier_payment_vouchers.view',
            'supplier_payment_vouchers.create',
            'supplier_payment_vouchers.edit_draft',
            'supplier_payment_vouchers.post',
            'supplier_payment_vouchers.cancel',
            'supplier_payment_vouchers.print',
            'supplier_payment_vouchers.delete_draft',

            /*
            |--------------------------------------------------------------------------
            | المحاسبة
            |--------------------------------------------------------------------------
            */
            'accounts.view',
            'accounts.create',
            'accounts.edit',
            'accounts.delete',

            'account_settings.view',
            'account_settings.update',

            'cost_centers.view',
            'cost_centers.create',
            'cost_centers.edit',
            'cost_centers.delete',

            'opening_balances.view',
            'opening_balances.create',
            'opening_balances.edit_draft',
            'opening_balances.post',
            'opening_balances.cancel',
            'opening_balances.print',
            'opening_balances.delete_draft',

            'manual_journal_entries.view',
            'manual_journal_entries.create',
            'manual_journal_entries.edit_draft',
            'manual_journal_entries.post',
            'manual_journal_entries.cancel',
            'manual_journal_entries.print',
            'manual_journal_entries.delete_draft',

            'general_receipt_vouchers.view',
            'general_receipt_vouchers.create',
            'general_receipt_vouchers.edit_draft',
            'general_receipt_vouchers.post',
            'general_receipt_vouchers.cancel',
            'general_receipt_vouchers.print',
            'general_receipt_vouchers.delete_draft',

            'general_payment_vouchers.view',
            'general_payment_vouchers.create',
            'general_payment_vouchers.edit_draft',
            'general_payment_vouchers.post',
            'general_payment_vouchers.cancel',
            'general_payment_vouchers.print',
            'general_payment_vouchers.delete_draft',

            /*
            |--------------------------------------------------------------------------
            | التقارير
            |--------------------------------------------------------------------------
            */
            'sales_reports.view',
            'sales_reports.print',
            'sales_reports.export',

            'sales_profit_reports.view',
            'sales_profit_reports.print',
            'sales_profit_reports.export',

            'inventory_movement_reports.view',
            'inventory_movement_reports.print',
            'inventory_movement_reports.export',

            'customer_statements.view',
            'customer_statements.print',
            'customer_statements.export',

            'supplier_statements.view',
            'supplier_statements.print',
            'supplier_statements.export',

            'customer_balance_reports.view',
            'customer_balance_reports.print',
            'customer_balance_reports.export',

            'supplier_balance_reports.view',
            'supplier_balance_reports.print',
            'supplier_balance_reports.export',

            'account_ledger_reports.view',
            'account_ledger_reports.print',
            'account_ledger_reports.export',

            'trial_balance_reports.view',
            'trial_balance_reports.print',
            'trial_balance_reports.export',

            'income_statement_reports.view',
            'income_statement_reports.print',
            'income_statement_reports.export',

            'balance_sheet_reports.view',
            'balance_sheet_reports.print',
            'balance_sheet_reports.export',

            'cash_flow_reports.view',
            'cash_flow_reports.print',
            'cash_flow_reports.export',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | الأدوار الأساسية
        |--------------------------------------------------------------------------
        */
        $masterRole = Role::query()->firstOrCreate([
            'name' => 'Master',
            'guard_name' => 'web',
        ]);

        $systemAdminRole = Role::query()->firstOrCreate([
            'name' => 'System Admin',
            'guard_name' => 'web',
        ]);

        $companyOwnerRole = Role::query()->firstOrCreate([
            'name' => 'Company Owner',
            'guard_name' => 'web',
        ]);

        $companyAdminRole = Role::query()->firstOrCreate([
            'name' => 'Company Admin',
            'guard_name' => 'web',
        ]);

        $branchAdminRole = Role::query()->firstOrCreate([
            'name' => 'Branch Admin',
            'guard_name' => 'web',
        ]);

        $employeeRole = Role::query()->firstOrCreate([
            'name' => 'Employee',
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | توزيع الصلاحيات
        |--------------------------------------------------------------------------
        */

        $masterRole->syncPermissions($permissions);

        $systemAdminRole->syncPermissions($permissions);

        $companyOwnerRole->syncPermissions([
            'dashboard.view',

            'company_settings.view',
            'company_settings.edit',
            'company_settings.update',

            'branches.view',
            'branches.create',
            'branches.edit',
            'branches.delete',

            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',

            'units.view',
            'units.create',
            'units.edit',
            'units.delete',

            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            'brands.view',
            'brands.create',
            'brands.edit',
            'brands.delete',

            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',

            'opening_stock.view',
            'opening_stock.create',
            'opening_stock.edit_draft',
            'opening_stock.post',
            'opening_stock.cancel',
            'opening_stock.delete_draft',

            'inventory_movements.view',

            'quotations.view',
            'quotations.create',
            'quotations.edit',
            'quotations.cancel',
            'quotations.print',
            'quotations.convert',
            'quotations.approve',
            'quotations.reject',

            'sales_invoices.view',
            'sales_invoices.create',
            'sales_invoices.edit_draft',
            'sales_invoices.post',
            'sales_invoices.cancel',
            'sales_invoices.print',
            'sales_invoices.delete_draft',

            'sales_returns.view',
            'sales_returns.create',
            'sales_returns.edit_draft',
            'sales_returns.post',
            'sales_returns.cancel',
            'sales_returns.print',
            'sales_returns.delete_draft',

            'customer_receipt_vouchers.view',
            'customer_receipt_vouchers.create',
            'customer_receipt_vouchers.edit_draft',
            'customer_receipt_vouchers.post',
            'customer_receipt_vouchers.cancel',
            'customer_receipt_vouchers.print',
            'customer_receipt_vouchers.delete_draft',

            'customer_refund_vouchers.view',
            'customer_refund_vouchers.create',
            'customer_refund_vouchers.edit_draft',
            'customer_refund_vouchers.post',
            'customer_refund_vouchers.cancel',
            'customer_refund_vouchers.print',
            'customer_refund_vouchers.delete_draft',

            'purchase_invoices.view',
            'purchase_invoices.create',
            'purchase_invoices.edit_draft',
            'purchase_invoices.post',
            'purchase_invoices.cancel',
            'purchase_invoices.print',
            'purchase_invoices.delete_draft',

            'purchase_returns.view',
            'purchase_returns.create',
            'purchase_returns.edit_draft',
            'purchase_returns.post',
            'purchase_returns.cancel',
            'purchase_returns.print',
            'purchase_returns.delete_draft',

            'supplier_payment_vouchers.view',
            'supplier_payment_vouchers.create',
            'supplier_payment_vouchers.edit_draft',
            'supplier_payment_vouchers.post',
            'supplier_payment_vouchers.cancel',
            'supplier_payment_vouchers.print',
            'supplier_payment_vouchers.delete_draft',

            'accounts.view',
            'accounts.create',
            'accounts.edit',
            'accounts.delete',

            'account_settings.view',
            'account_settings.update',

            'cost_centers.view',
            'cost_centers.create',
            'cost_centers.edit',
            'cost_centers.delete',

            'opening_balances.view',
            'opening_balances.create',
            'opening_balances.edit_draft',
            'opening_balances.post',
            'opening_balances.cancel',
            'opening_balances.print',
            'opening_balances.delete_draft',

            'manual_journal_entries.view',
            'manual_journal_entries.create',
            'manual_journal_entries.edit_draft',
            'manual_journal_entries.post',
            'manual_journal_entries.cancel',
            'manual_journal_entries.print',
            'manual_journal_entries.delete_draft',

            'general_receipt_vouchers.view',
            'general_receipt_vouchers.create',
            'general_receipt_vouchers.edit_draft',
            'general_receipt_vouchers.post',
            'general_receipt_vouchers.cancel',
            'general_receipt_vouchers.print',
            'general_receipt_vouchers.delete_draft',

            'general_payment_vouchers.view',
            'general_payment_vouchers.create',
            'general_payment_vouchers.edit_draft',
            'general_payment_vouchers.post',
            'general_payment_vouchers.cancel',
            'general_payment_vouchers.print',
            'general_payment_vouchers.delete_draft',

            'sales_reports.view',
            'sales_reports.print',
            'sales_reports.export',

            'sales_profit_reports.view',
            'sales_profit_reports.print',
            'sales_profit_reports.export',

            'inventory_movement_reports.view',
            'inventory_movement_reports.print',
            'inventory_movement_reports.export',

            'customer_statements.view',
            'customer_statements.print',
            'customer_statements.export',

            'supplier_statements.view',
            'supplier_statements.print',
            'supplier_statements.export',

            'customer_balance_reports.view',
            'customer_balance_reports.print',
            'customer_balance_reports.export',

            'supplier_balance_reports.view',
            'supplier_balance_reports.print',
            'supplier_balance_reports.export',

            'account_ledger_reports.view',
            'account_ledger_reports.print',
            'account_ledger_reports.export',

            'trial_balance_reports.view',
            'trial_balance_reports.print',
            'trial_balance_reports.export',

            'income_statement_reports.view',
            'income_statement_reports.print',
            'income_statement_reports.export',

            'balance_sheet_reports.view',
            'balance_sheet_reports.print',
            'balance_sheet_reports.export',

            'cash_flow_reports.view',
            'cash_flow_reports.print',
            'cash_flow_reports.export',

            'backups.view',
            'backups.create',
            'backups.download',
        ]);

        $companyAdminRole->syncPermissions([
            'dashboard.view',

            'company_settings.view',
            'company_settings.edit',
            'company_settings.update',

            'branches.view',
            'branches.create',
            'branches.edit',

            'users.view',
            'users.create',
            'users.edit',

            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',

            'units.view',
            'units.create',
            'units.edit',

            'categories.view',
            'categories.create',
            'categories.edit',

            'brands.view',
            'brands.create',
            'brands.edit',

            'products.view',
            'products.create',
            'products.edit',

            'customers.view',
            'customers.create',
            'customers.edit',

            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',

            'opening_stock.view',
            'opening_stock.create',
            'opening_stock.post',
            'opening_stock.cancel',

            'inventory_movements.view',

            'quotations.view',
            'quotations.create',
            'quotations.edit',
            'quotations.cancel',
            'quotations.print',
            'quotations.convert',
            'quotations.approve',
            'quotations.reject',

            'sales_invoices.view',
            'sales_invoices.create',
            'sales_invoices.edit_draft',
            'sales_invoices.post',
            'sales_invoices.cancel',
            'sales_invoices.print',

            'sales_returns.view',
            'sales_returns.create',
            'sales_returns.post',
            'sales_returns.cancel',
            'sales_returns.print',

            'customer_receipt_vouchers.view',
            'customer_receipt_vouchers.create',
            'customer_receipt_vouchers.post',
            'customer_receipt_vouchers.cancel',
            'customer_receipt_vouchers.print',

            'customer_refund_vouchers.view',
            'customer_refund_vouchers.create',
            'customer_refund_vouchers.post',
            'customer_refund_vouchers.cancel',
            'customer_refund_vouchers.print',

            'purchase_invoices.view',
            'purchase_invoices.create',
            'purchase_invoices.edit_draft',
            'purchase_invoices.post',
            'purchase_invoices.cancel',
            'purchase_invoices.print',

            'purchase_returns.view',
            'purchase_returns.create',
            'purchase_returns.post',
            'purchase_returns.cancel',
            'purchase_returns.print',

            'supplier_payment_vouchers.view',
            'supplier_payment_vouchers.create',
            'supplier_payment_vouchers.post',
            'supplier_payment_vouchers.cancel',
            'supplier_payment_vouchers.print',

            'accounts.view',
            'accounts.create',
            'accounts.edit',

            'account_settings.view',
            'account_settings.update',

            'cost_centers.view',
            'cost_centers.create',
            'cost_centers.edit',

            'opening_balances.view',
            'opening_balances.create',
            'opening_balances.post',
            'opening_balances.cancel',
            'opening_balances.print',

            'manual_journal_entries.view',
            'manual_journal_entries.create',
            'manual_journal_entries.post',
            'manual_journal_entries.cancel',
            'manual_journal_entries.print',

            'general_receipt_vouchers.view',
            'general_receipt_vouchers.create',
            'general_receipt_vouchers.post',
            'general_receipt_vouchers.cancel',
            'general_receipt_vouchers.print',

            'general_payment_vouchers.view',
            'general_payment_vouchers.create',
            'general_payment_vouchers.post',
            'general_payment_vouchers.cancel',
            'general_payment_vouchers.print',

            'sales_reports.view',
            'sales_reports.print',
            'sales_reports.export',

            'sales_profit_reports.view',
            'sales_profit_reports.print',
            'sales_profit_reports.export',

            'inventory_movement_reports.view',
            'inventory_movement_reports.print',
            'inventory_movement_reports.export',

            'customer_statements.view',
            'customer_statements.print',
            'customer_statements.export',

            'supplier_statements.view',
            'supplier_statements.print',
            'supplier_statements.export',

            'customer_balance_reports.view',
            'customer_balance_reports.print',
            'customer_balance_reports.export',

            'supplier_balance_reports.view',
            'supplier_balance_reports.print',
            'supplier_balance_reports.export',

            'account_ledger_reports.view',
            'account_ledger_reports.print',
            'account_ledger_reports.export',

            'trial_balance_reports.view',
            'trial_balance_reports.print',
            'trial_balance_reports.export',

            'income_statement_reports.view',
            'income_statement_reports.print',
            'income_statement_reports.export',

            'balance_sheet_reports.view',
            'balance_sheet_reports.print',
            'balance_sheet_reports.export',

            'cash_flow_reports.view',
            'cash_flow_reports.print',
            'cash_flow_reports.export',

            'backups.view',
            'backups.create',
            'backups.download',
        ]);

        $branchAdminRole->syncPermissions([
            'dashboard.view',

            'users.view',
            'users.create',
            'users.edit',

            'warehouses.view',

            'units.view',
            'categories.view',
            'brands.view',
            'products.view',

            'customers.view',
            'customers.create',
            'customers.edit',

            'suppliers.view',

            'opening_stock.view',
            'opening_stock.create',
            'opening_stock.post',

            'inventory_movements.view',

            'quotations.view',
            'quotations.create',
            'quotations.edit',
            'quotations.cancel',
            'quotations.print',
            'quotations.convert',

            'sales_invoices.view',
            'sales_invoices.create',
            'sales_invoices.edit_draft',
            'sales_invoices.post',
            'sales_invoices.cancel',
            'sales_invoices.print',

            'sales_returns.view',
            'sales_returns.create',
            'sales_returns.post',
            'sales_returns.cancel',
            'sales_returns.print',

            'customer_receipt_vouchers.view',
            'customer_receipt_vouchers.create',
            'customer_receipt_vouchers.post',
            'customer_receipt_vouchers.cancel',
            'customer_receipt_vouchers.print',

            'customer_refund_vouchers.view',
            'customer_refund_vouchers.create',
            'customer_refund_vouchers.post',
            'customer_refund_vouchers.cancel',
            'customer_refund_vouchers.print',

            'purchase_invoices.view',
            'purchase_invoices.create',
            'purchase_invoices.post',
            'purchase_invoices.cancel',
            'purchase_invoices.print',

            'purchase_returns.view',
            'purchase_returns.create',
            'purchase_returns.post',
            'purchase_returns.cancel',
            'purchase_returns.print',

            'supplier_payment_vouchers.view',
            'supplier_payment_vouchers.create',
            'supplier_payment_vouchers.post',
            'supplier_payment_vouchers.cancel',
            'supplier_payment_vouchers.print',

            'sales_reports.view',
            'sales_reports.print',

            'sales_profit_reports.view',
            'sales_profit_reports.print',

            'inventory_movement_reports.view',
            'inventory_movement_reports.print',

            'customer_statements.view',
            'customer_statements.print',

            'supplier_statements.view',
            'supplier_statements.print',

            'customer_balance_reports.view',
            'supplier_balance_reports.view',
        ]);

        $employeeRole->syncPermissions([
            'dashboard.view',

            'products.view',
            'customers.view',
            'customers.create',

            'quotations.view',
            'quotations.create',
            'quotations.print',

            'sales_invoices.view',
            'sales_invoices.create',
            'sales_invoices.print',

            'customer_receipt_vouchers.view',
            'customer_receipt_vouchers.create',
            'customer_receipt_vouchers.print',

            'customer_statements.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | مستخدم Master للنظام والدعم الفني
        |--------------------------------------------------------------------------
        */
        $masterUser = User::query()->firstOrCreate(
            ['email' => 'admin@wazin.test'],
            [
                'name' => 'مدير النظام',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'company_id' => null,
                'branch_id' => null,
                'user_type' => 'master',
                'level' => 100,
                'is_active' => true,
            ]
        );

        $masterUser->forceFill([
            'name' => 'مدير النظام',
            'company_id' => null,
            'branch_id' => null,
            'user_type' => 'master',
            'level' => 100,
            'is_active' => true,
        ])->save();

        $masterUser->syncRoles(['Master']);

        /*
        |--------------------------------------------------------------------------
        | مستخدم مالك الشركة التجريبي
        |--------------------------------------------------------------------------
        */
        $companyOwnerUser = User::query()->firstOrCreate(
            ['email' => 'owner@wazin.test'],
            [
                'name' => 'مالك الشركة',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'user_type' => 'company_owner',
                'level' => 70,
                'is_active' => true,
            ]
        );

        $companyOwnerUser->forceFill([
            'name' => 'مالك الشركة',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'user_type' => 'company_owner',
            'level' => 70,
            'is_active' => true,
        ])->save();

        $companyOwnerUser->syncRoles(['Company Owner']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}