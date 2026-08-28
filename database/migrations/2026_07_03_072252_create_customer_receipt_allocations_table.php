<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_receipt_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_receipt_voucher_id')
                ->constrained('customer_receipt_vouchers')
                ->cascadeOnDelete();

            $table->foreignId('sales_invoice_id')
                ->constrained('sales_invoices')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('amount', 15, 2)->default(0);

            $table->timestamps();

            $table->index('sales_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_receipt_allocations');
    }
};