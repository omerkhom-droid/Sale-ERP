<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | quotation_items
    |--------------------------------------------------------------------------
    | أصناف عرض السعر.
    */
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {

            $table->id();

            /*
                عرض السعر التابع له السطر.
            */
            $table->foreignId('quotation_id')
                ->constrained('quotations')
                ->cascadeOnDelete();

            /*
                الصنف.
            */
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            /*
                حفظ اسم وكود الصنف وقت عرض السعر.
            */
            $table->string('product_name')->nullable();
            $table->string('product_sku')->nullable();

            /*
                الوحدة.
            */
            $table->foreignId('product_unit_id')
                ->constrained('product_units')
                ->restrictOnDelete();

            /*
                اسم الوحدة وقت عرض السعر.
            */
            $table->string('unit_name')->nullable();

            /*
                الكمية بالوحدة المختارة.
            */
            $table->decimal('quantity', 15, 3)->default(0);

            /*
                الكمية الأساسية بعد التحويل.
            */
            $table->decimal('base_quantity', 15, 3)->default(0);

            /*
                سعر البيع.
            */
            $table->decimal('unit_price', 15, 2)->default(0);

            /*
                الخصم على السطر.
            */
            $table->decimal('discount_amount', 15, 2)->default(0);

            /*
                صافي السطر قبل الضريبة.
            */
            $table->decimal('net_amount', 15, 2)->default(0);

            /*
                نسبة الضريبة.
            */
            $table->decimal('vat_rate', 5, 2)->default(0);

            /*
                مبلغ الضريبة.
            */
            $table->decimal('vat_amount', 15, 2)->default(0);

            /*
                إجمالي السطر شامل الضريبة.
            */
            $table->decimal('line_total', 15, 2)->default(0);

            $table->timestamps();

            $table->index('quotation_id');
            $table->index('product_id');
            $table->index('product_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};