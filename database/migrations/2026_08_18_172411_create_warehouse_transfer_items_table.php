<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transfer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_transfer_id')
                ->constrained('warehouse_transfers')
                ->cascadeOnDelete();

            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate();
            $table->foreignId('product_unit_id')->constrained('product_units')->cascadeOnUpdate();

            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'product_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_items');
    }
};