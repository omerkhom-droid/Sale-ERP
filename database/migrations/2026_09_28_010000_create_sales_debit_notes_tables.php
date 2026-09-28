<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_no', 100)->unique();
            $table->uuid('submission_token')->unique();
            $table->foreignId('sales_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('note_date')->index();
            $table->text('reason');
            $table->string('status', 20)->default('draft')->index();
            foreach (['subtotal', 'vat_amount', 'total_amount', 'total_cost'] as $column) {
                $table->decimal($column, 15, 2)->default(0);
            }
            foreach (['created_by', 'posted_by', 'cancelled_by'] as $column) {
                $table->foreignId($column)->nullable()->constrained('users')->nullOnDelete();
            }
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('sales_debit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_debit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_invoice_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_unit_id')->constrained()->restrictOnDelete();
            $table->string('product_name')->nullable();
            $table->string('unit_name')->nullable();
            $table->string('adjustment_type', 20); // quantity or price
            $table->boolean('affects_stock')->default(false);
            $table->decimal('quantity', 15, 3);
            $table->decimal('base_quantity', 15, 3)->default(0);
            foreach (['unit_price', 'net_amount', 'vat_amount', 'line_total', 'unit_cost', 'total_cost'] as $column) {
                $table->decimal($column, 15, 2)->default(0);
            }
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->timestamps();
            $table->unique(['sales_debit_note_id', 'sales_invoice_item_id'], 'sdn_original_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_debit_note_items');
        Schema::dropIfExists('sales_debit_notes');
    }
};
