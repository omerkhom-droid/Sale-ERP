<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_order_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pos_order_id')
                ->constrained('pos_orders')
                ->cascadeOnDelete();

            $table->enum('payment_method', ['cash', 'card', 'bank_transfer']);
            $table->decimal('amount', 15, 2)->default(0);

            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['pos_order_id', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_order_payments');
    }
};