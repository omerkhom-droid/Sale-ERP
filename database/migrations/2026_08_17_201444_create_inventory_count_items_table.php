<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_count_id')
                ->constrained('inventory_counts')
                ->cascadeOnDelete();

            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate();
            $table->foreignId('product_unit_id')->constrained('product_units')->cascadeOnUpdate();

            $table->decimal('system_quantity', 15, 3)->default(0);
            $table->decimal('actual_quantity', 15, 3)->nullable();
            $table->decimal('variance_quantity', 15, 3)->default(0);

            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_variance_cost', 15, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['inventory_count_id', 'product_id', 'product_unit_id'], 'inventory_count_item_unique');
            $table->index(['product_id', 'product_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');
    }
};