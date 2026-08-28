<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Assets
            ['code' => '1000', 'name_ar' => 'الأصول', 'name_en' => 'Assets', 'type' => 'asset', 'normal' => 'debit', 'parent' => null, 'level' => 1, 'group' => true],
            ['code' => '1100', 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1000', 'level' => 2, 'group' => true],
            ['code' => '1110', 'name_ar' => 'الصندوق', 'name_en' => 'Cash', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1100', 'level' => 3, 'group' => false],
            ['code' => '1120', 'name_ar' => 'البنك', 'name_en' => 'Bank', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1100', 'level' => 3, 'group' => false],
            ['code' => '1130', 'name_ar' => 'العملاء - الذمم المدينة', 'name_en' => 'Accounts Receivable', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1100', 'level' => 3, 'group' => false],
            ['code' => '1140', 'name_ar' => 'المخزون', 'name_en' => 'Inventory', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1100', 'level' => 3, 'group' => false],
            ['code' => '1150', 'name_ar' => 'ضريبة القيمة المضافة - مدخلات', 'name_en' => 'VAT Input', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1100', 'level' => 3, 'group' => false],

            ['code' => '1200', 'name_ar' => 'الأصول الثابتة', 'name_en' => 'Fixed Assets', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1000', 'level' => 2, 'group' => true],
            ['code' => '1210', 'name_ar' => 'الأثاث والمعدات', 'name_en' => 'Furniture and Equipment', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1200', 'level' => 3, 'group' => false],

            // Liabilities
            ['code' => '2000', 'name_ar' => 'الخصوم', 'name_en' => 'Liabilities', 'type' => 'liability', 'normal' => 'credit', 'parent' => null, 'level' => 1, 'group' => true],
            ['code' => '2100', 'name_ar' => 'الخصوم المتداولة', 'name_en' => 'Current Liabilities', 'type' => 'liability', 'normal' => 'credit', 'parent' => '2000', 'level' => 2, 'group' => true],
            ['code' => '2110', 'name_ar' => 'الموردون - الذمم الدائنة', 'name_en' => 'Accounts Payable', 'type' => 'liability', 'normal' => 'credit', 'parent' => '2100', 'level' => 3, 'group' => false],
            ['code' => '2120', 'name_ar' => 'ضريبة القيمة المضافة - مخرجات', 'name_en' => 'VAT Output', 'type' => 'liability', 'normal' => 'credit', 'parent' => '2100', 'level' => 3, 'group' => false],
            ['code' => '2130', 'name_ar' => 'مصروفات مستحقة', 'name_en' => 'Accrued Expenses', 'type' => 'liability', 'normal' => 'credit', 'parent' => '2100', 'level' => 3, 'group' => false],

            // Equity
            ['code' => '3000', 'name_ar' => 'حقوق الملكية', 'name_en' => 'Equity', 'type' => 'equity', 'normal' => 'credit', 'parent' => null, 'level' => 1, 'group' => true],
            ['code' => '3100', 'name_ar' => 'رأس المال', 'name_en' => 'Capital', 'type' => 'equity', 'normal' => 'credit', 'parent' => '3000', 'level' => 2, 'group' => false],
            ['code' => '3110', 'name_ar' => 'الأرصدة الافتتاحية', 'name_en' => 'Opening Balance Equity', 'type' => 'equity', 'normal' => 'credit', 'parent' => '3000', 'level' => 2, 'group' => false],

            // Revenue
            ['code' => '4000', 'name_ar' => 'الإيرادات', 'name_en' => 'Revenue', 'type' => 'revenue', 'normal' => 'credit', 'parent' => null, 'level' => 1, 'group' => true],
            ['code' => '4100', 'name_ar' => 'المبيعات', 'name_en' => 'Sales', 'type' => 'revenue', 'normal' => 'credit', 'parent' => '4000', 'level' => 2, 'group' => false],
            ['code' => '4110', 'name_ar' => 'مردودات المبيعات', 'name_en' => 'Sales Returns', 'type' => 'revenue', 'normal' => 'debit', 'parent' => '4000', 'level' => 2, 'group' => false],

            // Expenses
            ['code' => '5000', 'name_ar' => 'المصروفات', 'name_en' => 'Expenses', 'type' => 'expense', 'normal' => 'debit', 'parent' => null, 'level' => 1, 'group' => true],
            ['code' => '5100', 'name_ar' => 'تكلفة البضاعة المباعة', 'name_en' => 'Cost of Goods Sold', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5000', 'level' => 2, 'group' => false],
            ['code' => '5200', 'name_ar' => 'مصروفات التشغيل', 'name_en' => 'Operating Expenses', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5000', 'level' => 2, 'group' => true],
            ['code' => '5210', 'name_ar' => 'الرواتب والأجور', 'name_en' => 'Salaries and Wages', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5200', 'level' => 3, 'group' => false],
            ['code' => '5220', 'name_ar' => 'الإيجارات', 'name_en' => 'Rent Expense', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5200', 'level' => 3, 'group' => false],
            ['code' => '5230', 'name_ar' => 'الكهرباء والمياه', 'name_en' => 'Utilities Expense', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5200', 'level' => 3, 'group' => false],
            ['code' => '5240', 'name_ar' => 'الاتصالات والإنترنت', 'name_en' => 'Telecom and Internet', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5200', 'level' => 3, 'group' => false],
            ['code' => '5250', 'name_ar' => 'مصروفات بنكية', 'name_en' => 'Bank Charges', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5200', 'level' => 3, 'group' => false],
            ['code' => '5290', 'name_ar' => 'مصروفات أخرى', 'name_en' => 'Other Expenses', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5200', 'level' => 3, 'group' => false],
            ['code' => '5291', 'name_ar' => 'خسائر تلف المخزون', 'name_en' => 'inventory damage', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5200', 'level' => 3, 'group' => false],
        ];

        foreach ($accounts as $row) {
            $parentId = null;

            if ($row['parent']) {
                $parentId = Account::where('account_code', $row['parent'])->value('id');
            }

            Account::updateOrCreate(
                ['account_code' => $row['code']],
                [
                    'parent_id' => $parentId,
                    'level' => $row['level'],
                    'account_name_ar' => $row['name_ar'],
                    'account_name_en' => $row['name_en'],
                    'account_type' => $row['type'],
                    'normal_balance' => $row['normal'],
                    'is_group' => $row['group'],
                    'is_active' => true,
                ]
            );
        }

        if (Schema::hasTable('account_settings')) {
            $this->linkAccountSettings();
        }
    }

    private function linkAccountSettings(): void
    {
        $settings = [
            'inventory_account' => [
                'code' => '1140',
                'description' => 'حساب المخزون',
            ],
            'accounts_payable' => [
                'code' => '2110',
                'description' => 'حساب الموردين',
            ],
            'accounts_receivable' => [
                'code' => '1130',
                'description' => 'حساب العملاء',
            ],
            'sales_account' => [
                'code' => '4100',
                'description' => 'حساب المبيعات',
            ],
            'cost_of_goods_sold' => [
                'code' => '5100',
                'description' => 'حساب تكلفة البضاعة المباعة',
            ],
            'vat_input_account' => [
                'code' => '1150',
                'description' => 'حساب ضريبة المدخلات',
            ],
            'vat_output_account' => [
                'code' => '2120',
                'description' => 'حساب ضريبة المخرجات',
            ],
            'cash_account' => [
                'code' => '1110',
                'description' => 'حساب الصندوق',
            ],
            'bank_account' => [
                'code' => '1120',
                'description' => 'حساب البنك',
            ],
            'opening_balance_equity' => [
                'code' => '3110',
                'description' => 'حساب الأرصدة الافتتاحية',
            ],
        ];

        foreach ($settings as $key => $item) {
            $accountId = Account::where('account_code', $item['code'])->value('id');

            AccountSetting::updateOrCreate(
                ['setting_key' => $key],
                [
                    'account_id' => $accountId,
                    'description' => $item['description'],
                ]
            );
        }
    }
}