<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->foreignId('default_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->foreignId('default_customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->decimal('tax_rate', 5, 2)->default(15.00);

            $table->boolean('auto_print_receipt')->default(true);
            $table->unsignedTinyInteger('receipt_copies')->default(1);
            $table->boolean('show_product_images')->default(true);

            $table->string('receipt_title')->nullable();
            $table->text('receipt_footer')->nullable();

            $table->timestamps();

            $table->unique('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_settings');
    }
};