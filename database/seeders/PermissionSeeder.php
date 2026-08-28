<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',

            'companies.view',
            'companies.create',
            'companies.edit',
            'companies.delete',
            
            'branches.view',
            'branches.create',
            'branches.edit',
            'branches.delete',

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

            'opening_stock.view',
            'opening_stock.create',
            'opening_stock.edit_draft',
            'opening_stock.post',
            'opening_stock.cancel',
            'opening_stock.delete_draft',

            'inventory_counts.view',
            'inventory_counts.create',
            'inventory_counts.edit',
            'inventory_counts.post',
            'inventory_counts.cancel',
            'inventory_counts.print',
            
            'inventory_damages.view',
            'inventory_damages.create',
            'inventory_damages.post',
            'inventory_damages.cancel',
            'inventory_damages.print',

            'warehouse_transfers.view',
            'warehouse_transfers.create',
            'warehouse_transfers.post',
            'warehouse_transfers.cancel',
            'warehouse_transfers.print',

            'inventory_movements.view',
            'inventory_balance_report.view',
            'inventory_accounting_reconciliation_report.view',

            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',

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

            'pos.view',
            'pos.open_shift',
            'pos.close_shift',
            'pos.checkout',
            'pos.cancel_order',
            'pos.discount',
            'pos.print_receipt',
            'pos.daily_report',
            'pos.settings',
            
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

            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $manager = Role::firstOrCreate([
            'name' => 'Manager',
            'guard_name' => 'web',
        ]);

        $accountant = Role::firstOrCreate([
            'name' => 'Accountant',
            'guard_name' => 'web',
        ]);

        $salesUser = Role::firstOrCreate([
            'name' => 'Sales User',
            'guard_name' => 'web',
        ]);

        $purchaseUser = Role::firstOrCreate([
            'name' => 'Purchase User',
            'guard_name' => 'web',
        ]);

        $warehouseUser = Role::firstOrCreate([
            'name' => 'Warehouse User',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions($permissions);

        $manager->syncPermissions([
            'dashboard.view',

            'branches.view',
            'warehouses.view',

            'units.view',
            'categories.view',
            'brands.view',
            'products.view',
            'inventory_movements.view',

            'customers.view',
            'customers.create',
            'customers.edit',

            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',

            'sales_invoices.view',
            'sales_invoices.create',
            'sales_invoices.post',
            'sales_invoices.print',

            'sales_returns.view',
            'sales_returns.create',
            'sales_returns.post',
            'sales_returns.print',

            'customer_receipt_vouchers.view',
            'customer_receipt_vouchers.create',
            'customer_receipt_vouchers.post',
            'customer_receipt_vouchers.print',

            'purchase_invoices.view',
            'purchase_invoices.create',
            'purchase_invoices.post',
            'purchase_invoices.print',

            'purchase_returns.view',
            'purchase_returns.create',
            'purchase_returns.post',
            'purchase_returns.print',

            'supplier_payment_vouchers.view',
            'supplier_payment_vouchers.create',
            'supplier_payment_vouchers.post',
            'supplier_payment_vouchers.print',

            'sales_reports.view',
            'sales_profit_reports.view',
            'inventory_movement_reports.view',

            'customer_statements.view',
            'supplier_statements.view',
            'customer_balance_reports.view',
            'supplier_balance_reports.view',
        ]);

        $accountant->syncPermissions([
            'dashboard.view',

            'customers.view',
            'suppliers.view',

            'accounts.view',
            'account_settings.view',
            'cost_centers.view',

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

            'customer_receipt_vouchers.view',
            'customer_receipt_vouchers.post',
            'customer_receipt_vouchers.print',

            'customer_refund_vouchers.view',
            'customer_refund_vouchers.post',
            'customer_refund_vouchers.print',

            'supplier_payment_vouchers.view',
            'supplier_payment_vouchers.post',
            'supplier_payment_vouchers.print',

            'customer_statements.view',
            'supplier_statements.view',
            'customer_balance_reports.view',
            'supplier_balance_reports.view',

            'account_ledger_reports.view',
            'trial_balance_reports.view',
            'income_statement_reports.view',
            'balance_sheet_reports.view',
            'cash_flow_reports.view',
        ]);

        $salesUser->syncPermissions([
            'dashboard.view',

            'customers.view',
            'customers.create',

            'products.view',

            'sales_invoices.view',
            'sales_invoices.create',
            'sales_invoices.print',

            'sales_returns.view',

            'customer_receipt_vouchers.view',
            'customer_receipt_vouchers.create',
            'customer_receipt_vouchers.print',

            'customer_statements.view',
        ]);

        $purchaseUser->syncPermissions([
            'dashboard.view',

            'suppliers.view',
            'suppliers.create',

            'products.view',

            'purchase_invoices.view',
            'purchase_invoices.create',
            'purchase_invoices.print',

            'purchase_returns.view',

            'supplier_payment_vouchers.view',
            'supplier_payment_vouchers.create',
            'supplier_payment_vouchers.print',

            'supplier_statements.view',
        ]);

        $warehouseUser->syncPermissions([
            'dashboard.view',

            'warehouses.view',
            'products.view',

            'opening_stock.view',
            'inventory_movements.view',
        ]);

        $firstUser = User::query()->orderBy('id')->first();

        if ($firstUser) {
            $firstUser->forceFill([
                'is_active' => true,
                'is_super_admin' => true,
                'can_access_all_branches' => true,
            ])->save();

            $firstUser->syncRoles([$superAdmin]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}