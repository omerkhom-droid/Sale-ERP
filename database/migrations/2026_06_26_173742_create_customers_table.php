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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('customer_code', 100)->unique();
            $table->string('customer_name');
            $table->enum('customer_type', ['individual', 'company'])->default('company');

            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('tax_number', 15)->nullable();
            $table->string('commercial_register', 20)->nullable();

            $table->string('country_code', 10)->default('SA');
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('street_name')->nullable();
            $table->string('building_number', 10)->nullable();
            $table->string('additional_number', 10)->nullable();
            $table->string('postal_code', 10)->nullable();

            $table->text('address')->nullable();

            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->enum('balance_type', ['debit', 'credit'])->default('debit');

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
