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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_no', 100)->unique();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->foreignId('product_unit_id')
                ->constrained('product_units')
                ->restrictOnDelete();

            $table->enum('transaction_type', [
                'purchase',
                'sale',
                'purchase_return',
                'sale_return',
                'opening_balance',
                'stock_adjustment',
                'transfer_in',
                'transfer_out',
                'damage',
                'inventory_count',
            ]);

            $table->decimal('quantity', 12, 3);

            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);

            $table->decimal('balance_before', 12, 3)->default(0);
            $table->decimal('balance_after', 12, 3)->default(0);

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
