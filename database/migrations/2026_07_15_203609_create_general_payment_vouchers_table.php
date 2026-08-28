<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('general_payment_vouchers', function (Blueprint $table) {
            $table->id();

            $table->string('voucher_no')->unique();

            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('cost_center_id')->nullable()->index();

            $table->date('voucher_date');

            $table->string('payment_method', 50)->default('cash');

            // الحساب الخارج منه المال: صندوق / بنك
            $table->unsignedBigInteger('cash_bank_account_id')->index();

            // الحساب المقابل: مصروف / أصل / أي حساب عام
            $table->unsignedBigInteger('opposite_account_id')->index();

            $table->decimal('amount', 15, 2);

            $table->string('payee_name')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->string('cancel_reason')->nullable();

            $table->timestamps();

            $table->index(['voucher_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_payment_vouchers');
    }
};