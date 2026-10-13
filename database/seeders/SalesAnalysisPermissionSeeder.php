<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SalesAnalysisPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::findOrCreate('sales_analysis.assign', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        // Assign to the intended roles through the application's permissions screen.
    }
}
