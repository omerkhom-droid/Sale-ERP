<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_refund_vouchers', function (Blueprint $table) {
            $table->id();

            $table->string('voucher_no')->unique();

            /*
                مردود المبيعات الذي سيتم صرف المبلغ بناء عليه.
            */
            $table->foreignId('sales_return_id')
                ->constrained('sales_returns')
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
            $table->date('refund_date');

            $table->enum('payment_method', [
                'cash',
                'card',
                'bank_transfer',
                'other',
            ])->default('cash');

            $table->decimal('amount', 15, 2)->default(0);

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

            $table->index('sales_return_id');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('refund_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_refund_vouchers');
    }
};