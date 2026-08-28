<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pos_order_id')
                ->constrained('pos_orders')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('product_unit_id')
                ->constrained('product_units')
                ->restrictOnDelete();

            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('base_quantity', 15, 4)->default(1);

            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->decimal('vat_rate', 5, 2)->default(15);
            $table->decimal('vat_amount', 15, 2)->default(0);

            $table->decimal('line_total', 15, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['pos_order_id', 'product_id']);
            $table->index('product_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_order_items');
    }
};