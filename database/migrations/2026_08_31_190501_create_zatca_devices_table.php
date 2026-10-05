<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zatca_devices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->string('device_name');
            $table->string('egs_serial_number');

            $table->enum('environment', [
                'developer',
                'simulation',
                'production',
            ])->default('simulation');

            $table->string('common_name');
            $table->string('organization_identifier');
            $table->string('organization_unit_name')->nullable();
            $table->string('organization_name');
            $table->string('country_name', 2)->default('SA');

            $table->string('invoice_type', 10)->default('1100');
            $table->string('registered_address');
            $table->string('business_category');

            $table->longText('private_key')->nullable();
            $table->longText('csr')->nullable();

            $table->longText('compliance_certificate')->nullable();
            $table->text('compliance_secret')->nullable();
            $table->string('compliance_request_id')->nullable();

            $table->longText('production_certificate')->nullable();
            $table->text('production_secret')->nullable();
            $table->string('production_request_id')->nullable();

            $table->enum('status', [
                'new',
                'csr_generated',
                'compliance',
                'production',
                'revoked',
            ])->default('new');

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zatca_devices');
    }
};
