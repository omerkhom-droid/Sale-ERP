<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | purchase_returns
    |--------------------------------------------------------------------------
    | هذا جدول رأس مستند مردود المشتريات.
    |
    | يعني يحفظ البيانات العامة للمستند:
    | - رقم المردود
    | - الفاتورة الأصلية
    | - المورد
    | - المستودع
    | - التاريخ
    | - الإجماليات
    | - الحالة
    */
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();

            $table->string('return_no')->unique();

            /*
                ربط المردود بفاتورة المشتريات الأصلية.
                لأن المردود يجب أن يكون مبني على فاتورة مشتريات موجودة ومرحلة.
            */
            $table->foreignId('purchase_invoice_id')
                ->constrained('purchase_invoices')
                ->restrictOnDelete();

            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('cost_center_id')->nullable()->index();
            
            /*
                نخزن المورد والمستودع أيضاً لتسهيل البحث والتقارير.
                وهما أساساً مأخوذان من الفاتورة الأصلية.
            */
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->date('return_date');

            /*
                subtotal:
                قيمة الأصناف قبل الضريبة.

                vat_amount:
                ضريبة المردود.

                total_amount:
                إجمالي المردود شامل الضريبة.
            */
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            /*
                draft:
                مسودة بدون أثر.

                posted:
                مرحل، يؤثر على المخزون والمحاسبة.

                cancelled:
                ملغى، تم عكس أثره.
            */
            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('posted_at')->nullable();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('cancel_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};