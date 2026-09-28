<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SalesDebitNotePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['view', 'create', 'post', 'cancel', 'print'] as $action) {
            Permission::findOrCreate('sales_debit_notes.' . $action, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        // Assign through the application's role administration, not to every user.
    }
}
