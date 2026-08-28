<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();

            $table->string('return_no')->unique();

            $table->foreignId('sales_invoice_id')
                ->constrained('sales_invoices')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();
            $table->unsignedBigInteger('cost_center_id')->nullable()->index();
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('return_date');

            /*
                الإجماليات
            */
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            /*
                تكلفة البضاعة المرتجعة
            */
            $table->decimal('total_cost', 15, 2)->default(0);

            /*
                applied_amount:
                المبلغ الذي تم استخدامه لتخفيض المتبقي على الفاتورة.

                refundable_amount:
                مبلغ يصبح مستحقًا للعميل إذا كانت الفاتورة مدفوعة أكثر من اللازم.
            */
            $table->decimal('applied_amount', 15, 2)->default(0);
            $table->decimal('refundable_amount', 15, 2)->default(0);
            $table->decimal('refunded_amount', 15, 2)->default(0);

            $table->enum('status', [
                'draft',
                'posted',
                'cancelled',
            ])->default('draft');

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

            $table->index('sales_invoice_id');
            $table->index('customer_id');
            $table->index('warehouse_id');
            $table->index('return_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};