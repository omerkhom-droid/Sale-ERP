<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_return_id')
                ->constrained('sales_returns')
                ->cascadeOnDelete();

            $table->foreignId('sales_invoice_item_id')
                ->constrained('sales_invoice_items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('product_unit_id')
                ->constrained('product_units')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                Snapshot من بيانات الفاتورة الأصلية.
            */
            $table->string('product_name')->nullable();
            $table->string('product_sku')->nullable();
            $table->string('unit_name')->nullable();

            /*
                الكمية المرتجعة.
            */
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('base_quantity', 15, 3)->default(0);

            /*
                نفس أسعار البيع والتكلفة من فاتورة البيع الأصلية.
            */
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);

            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);

            $table->decimal('vat_rate', 5, 2)->default(15);
            $table->decimal('vat_amount', 15, 2)->default(0);

            $table->decimal('line_total', 15, 2)->default(0);

            $table->timestamps();

            $table->index('sales_return_id');
            $table->index('sales_invoice_item_id');
            $table->index('product_id');
            $table->index('product_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
    }
};