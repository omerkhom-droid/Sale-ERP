<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | quotations
    |--------------------------------------------------------------------------
    | جدول رأس عرض السعر.
    |
    | نفس فكرة فاتورة البيع، لكن:
    | - لا يؤثر على المخزون
    | - لا ينشئ قيود محاسبية
    | - يمكن تحويله لاحقًا إلى فاتورة بيع
    */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {

            $table->id();

            /*
                رقم عرض السعر.
                مثال:
                QUO-20260802203000-1234
            */
            $table->string('quotation_no')->unique();

            /*
                العميل اختياري.
                يمكن إنشاء عرض سعر لعميل نقدي غير مسجل.
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
                بيانات العميل المحفوظة داخل عرض السعر.
                مهمة للطباعة حتى لو تغيرت بيانات العميل لاحقًا.
            */
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile', 50)->nullable();
            $table->string('customer_tax_number', 50)->nullable();
            $table->text('customer_address')->nullable();

            /*
                الفرع.
            */
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            /*
                مركز التكلفة.
            */
            $table->unsignedBigInteger('cost_center_id')->nullable()->index();

            /*
                المستودع اختياري في عرض السعر.
                لا يتم خصم مخزون، لكنه مفيد عند التحويل إلى فاتورة.
            */
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            /*
                تاريخ عرض السعر وتاريخ الصلاحية.
            */
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();

            /*
                إجماليات عرض السعر.
            */
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            /*
                حالة عرض السعر.
            */
            $table->enum('status', [
                'draft',
                'sent',
                'approved',
                'rejected',
                'converted',
                'cancelled',
            ])->default('draft');

            /*
                الربط مع فاتورة البيع بعد التحويل.
            */
            $table->foreignId('converted_sales_invoice_id')
                ->nullable()
                ->constrained('sales_invoices')
                ->nullOnDelete();

            $table->timestamp('converted_at')->nullable();

            $table->foreignId('converted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
                ملاحظات وشروط.
            */
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            /*
                المستخدم الذي أنشأ عرض السعر.
            */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
                بيانات الاعتماد.
            */
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('approved_by')
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
            $table->index('quotation_no');
            $table->index('quotation_date');
            $table->index('valid_until');
            $table->index('customer_id');
            $table->index('customer_type');
            $table->index('branch_id');
            $table->index('warehouse_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};