<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class WorkshopPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['view', 'create', 'manage'] as $action) {
            Permission::findOrCreate('workshop.'.$action, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
