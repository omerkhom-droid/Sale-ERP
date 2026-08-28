<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | purchase_return_items
    |--------------------------------------------------------------------------
    | هذا جدول أصناف مردود المشتريات.
    |
    | كل سطر يمثل صنف تم إرجاعه للمورد.
    |
    | مهم جداً:
    | نربط السطر بـ purchase_invoice_item_id
    | حتى نعرف أن هذا المردود راجع من أي سطر في الفاتورة الأصلية.
    */
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_return_id')
                ->constrained('purchase_returns')
                ->cascadeOnDelete();

            /*
                سطر الفاتورة الأصلي.
                منه نعرف:
                - الصنف
                - الوحدة
                - الكمية الأصلية
                - التكلفة
                - الضريبة
            */
            $table->foreignId('purchase_invoice_item_id')
                ->constrained('purchase_invoice_items')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('product_unit_id')
                ->constrained('product_units')
                ->restrictOnDelete();

            /*
                quantity:
                الكمية حسب الوحدة المختارة في الفاتورة.
                مثال: 5 كرتون.

                base_quantity:
                الكمية بوحدة الأساس.
                مثال: 5 كرتون = 50 حبة.
            */
            $table->decimal('quantity', 15, 3);
            $table->decimal('base_quantity', 15, 3);

            /*
                unit_cost:
                تكلفة الوحدة من الفاتورة الأصلية.

                discount_amount:
                نصيب هذا المردود من خصم السطر الأصلي إن وجد.

                vat_rate:
                نسبة الضريبة من السطر الأصلي.

                vat_amount:
                ضريبة المردود.

                line_total:
                إجمالي السطر شامل الضريبة.
            */
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};