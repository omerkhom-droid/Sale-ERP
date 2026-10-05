<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->enum('invoice_classification', [
                'simplified',
                'standard',
            ])->default('simplified')->after('invoice_no')->index();

            $table->string('customer_email')->nullable()
                ->after('customer_mobile');

            $table->string('customer_commercial_register', 50)->nullable()
                ->after('customer_tax_number');

            $table->string('customer_country_code', 2)->nullable()
                ->after('customer_commercial_register');

            $table->string('customer_state', 127)->nullable()
                ->after('customer_country_code');

            $table->string('customer_city', 127)->nullable()
                ->after('customer_state');

            $table->string('customer_district', 127)->nullable()
                ->after('customer_city');

            $table->string('customer_street_name', 127)->nullable()
                ->after('customer_district');

            $table->string('customer_building_number', 4)->nullable()
                ->after('customer_street_name');

            $table->string('customer_additional_number', 4)->nullable()
                ->after('customer_building_number');

            $table->string('customer_postal_code', 5)->nullable()
                ->after('customer_additional_number');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex([
                'invoice_classification',
            ]);

            $table->dropColumn([
                'invoice_classification',
                'customer_email',
                'customer_commercial_register',
                'customer_country_code',
                'customer_state',
                'customer_city',
                'customer_district',
                'customer_street_name',
                'customer_building_number',
                'customer_additional_number',
                'customer_postal_code',
            ]);
        });
    }
};