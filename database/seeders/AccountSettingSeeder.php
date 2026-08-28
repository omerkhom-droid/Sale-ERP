<?php

namespace Database\Seeders;

use App\Models\AccountSetting;
use Illuminate\Database\Seeder;

class AccountSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'setting_key' => 'inventory_account',
                'description' => 'حساب المخزون',
            ],
            [
                'setting_key' => 'accounts_payable',
                'description' => 'حساب الموردين',
            ],
            [
                'setting_key' => 'accounts_receivable',
                'description' => 'حساب العملاء',
            ],
            [
                'setting_key' => 'sales_account',
                'description' => 'حساب المبيعات',
            ],
            [
                'setting_key' => 'cost_of_goods_sold',
                'description' => 'حساب تكلفة البضاعة المباعة',
            ],
            [
                'setting_key' => 'inventory_damage_expense_account',
                'description' => 'خسائر تلف المخزون ',
            ],
            [
                'setting_key' => 'vat_input_account',
                'description' => 'حساب ضريبة المدخلات',
            ],
            [
                'setting_key' => 'vat_output_account',
                'description' => 'حساب ضريبة المخرجات',
            ],
            [
                'setting_key' => 'cash_account',
                'description' => 'حساب الصندوق',
            ],
            [
                'setting_key' => 'bank_account',
                'description' => 'حساب البنك',
            ],
            [
                'setting_key' => 'opening_balance_equity',
                'description' => 'حساب رأس المال / الأرصدة الافتتاحية',
            ],
        ];

        foreach ($settings as $setting) {
            AccountSetting::firstOrCreate(
                ['setting_key' => $setting['setting_key']],
                ['description' => $setting['description']]
            );
        }
    }
}