<?php

namespace Database\Seeders;

use App\Models\LicenseSetting;
use Illuminate\Database\Seeder;

class LicenseSettingSeeder extends Seeder
{
    public function run(): void
    {
        LicenseSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'client_name' => 'عميل تجريبي',
                'license_key' => 'TRIAL-' . now()->format('YmdHis'),
                'starts_at' => now()->toDateString(),
                'expires_at' => now()->addDays(15)->toDateString(),
                'status' => 'trial',
                'max_users' => 5,
                'max_branches' => 1,
                'notes' => 'ترخيص تجريبي لمدة 15 يوم.',
            ]
        );
    }
}