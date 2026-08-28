<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | sales_invoices
    |--------------------------------------------------------------------------
    | جدول رأس فاتورة البيع.
    |
    | مثل المشتريات:
    | draft     = مسودة
    | posted    = مرحلة
    | cancelled = ملغاة
    |
    | ويدعم:
    | - عميل مسجل
    | - عميل نقدي ببيانات يدوية
    */
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {

            $table->id();

            /*
                رقم فاتورة البيع.
                مثال:
                SI-20260702123000-1234
            */
            $table->string('invoice_no')->unique();

            /*
                العميل اختياري.
                إذا الفاتورة نقدية لعميل غير مسجل يكون customer_id = null.
            */
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            /*
                نوع العميل:
                cash   = نقدي
                credit = آجل
            */
            $table->enum('customer_type', [
                'cash',
                'credit',
            ])->default('cash');

            /*
                بيانات العميل المحفوظة داخل الفاتورة.
                تستخدم في الطباعة حتى لو العميل غير مسجل.
            */
            $table->string('customer_name')->nullable();

            $table->string('customer_mobile', 50)->nullable();

            $table->string('customer_tax_number', 50)->nullable();

            $table->text('customer_address')->nullable();

            /*
                نوع الدفع:
                cash    = نقدي بالكامل
                credit  = آجل بالكامل
                partial = جزء نقدي وجزء آجل
            */
            $table->enum('payment_type', [
                'cash',
                'credit',
                'partial',
            ])->default('cash');

            /*
                طريقة الدفع للمبلغ المدفوع.
            */
            $table->enum('payment_method', [
                'cash',
                'card',
                'bank_transfer',
                'other',
            ])->nullable();

            /*
                الفرع.
            */
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();
            $table->unsignedBigInteger('cost_center_id')->nullable()->index();
            
            /*
                المستودع الذي تخرج منه البضاعة.
            */
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            /*
                تاريخ الفاتورة.
            */
            $table->date('invoice_date');

            /*
                إجماليات الفاتورة.
            */
            $table->decimal('subtotal', 15, 2)->default(0);

            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->decimal('vat_amount', 15, 2)->default(0);

            $table->decimal('total_amount', 15, 2)->default(0);

            /*
                المدفوع.
            */
            $table->decimal('paid_amount', 15, 2)->default(0);

            /*
                المتبقي على العميل.
            */
            $table->decimal('remaining_amount', 15, 2)->default(0);

            /*
                لاحقًا عند مردودات البيع.
            */
            $table->decimal('returned_amount', 15, 2)->default(0);

            /*
                حالة الفاتورة.
            */
            $table->enum('status', [
                'draft',
                'posted',
                'cancelled',
            ])->default('draft');

            /*
                حالة السداد.
            */
            $table->enum('payment_status', [
                'unpaid',
                'partial',
                'paid',
            ])->default('unpaid');

            /*
                حالة المردود.
            */
            $table->enum('return_status', [
                'none',
                'partial',
                'full',
            ])->default('none');

            $table->text('notes')->nullable();

            /*
                المستخدم الذي أنشأ الفاتورة.
            */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
                بيانات الترحيل.
            */
            $table->timestamp('posted_at')->nullable();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
                بيانات الإلغاء.
            */
            $table->timestamp('cancelled_at')->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('cancel_reason')->nullable();

            $table->timestamps();

            /*
                فهارس للتقارير والبحث.
            */
            $table->index('invoice_date');
            $table->index('customer_id');
            $table->index('customer_type');
            $table->index('payment_type');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};