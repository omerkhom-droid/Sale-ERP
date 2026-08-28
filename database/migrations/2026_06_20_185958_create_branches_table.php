<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->string('branch_name');

            $table->decimal('preceatage', 8, 2)->default(0);

            $table->string('tax_registration_number', 15);
            $table->string('license_type', 50)->default('CRN');
            $table->string('license_number', 50);

            $table->string('country_code', 10);
            $table->string('state', 100);
            $table->string('city', 100);
            $table->string('neighborhood', 100);
            $table->string('street_name', 150);
            $table->string('additional_street_name', 150)->nullable();

            $table->string('building_number', 10);
            $table->string('secondary_number', 10);
            $table->string('postal_zone', 10);

            $table->string('phone', 50);
            $table->text('details')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('is_active');
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};