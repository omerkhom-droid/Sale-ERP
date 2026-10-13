<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workshop_vehicles', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
            $table->string('owner_name')->nullable();
            $table->string('owner_mobile', 50)->nullable();
        });
        Schema::table('workshop_orders', function (Blueprint $table) {
            $table->string('customer_type', 10)->default('cash');
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile', 50)->nullable();
        });
        DB::table('workshop_vehicles')->orderBy('id')->chunkById(200, function ($vehicles) {
            foreach ($vehicles as $vehicle) {
                $customer = DB::table('customers')->find($vehicle->customer_id);
                if ($customer) DB::table('workshop_vehicles')->where('id', $vehicle->id)->update(['owner_name' => $customer->customer_name, 'owner_mobile' => $customer->phone]);
            }
        });
        DB::table('workshop_orders')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                $vehicle = DB::table('workshop_vehicles')->find($order->vehicle_id);
                $quotation = $order->quotation_id ? DB::table('quotations')->find($order->quotation_id) : null;
                DB::table('workshop_orders')->where('id', $order->id)->update([
                    'customer_type' => $quotation->customer_type ?? 'cash',
                    'customer_name' => $quotation->customer_name ?? $vehicle->owner_name ?? null,
                    'customer_mobile' => $quotation->customer_mobile ?? $vehicle->owner_mobile ?? null,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Keeping customer_id nullable preserves vehicles with unregistered cash owners.
        Schema::table('workshop_orders', fn (Blueprint $table) => $table->dropColumn(['customer_type', 'customer_name', 'customer_mobile']));
        Schema::table('workshop_vehicles', fn (Blueprint $table) => $table->dropColumn(['owner_name', 'owner_mobile']));
    }
};
