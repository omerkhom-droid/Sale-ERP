<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();

            $table->string('transfer_no')->unique();
            $table->date('transfer_date');

            $table->foreignId('from_warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnUpdate();

            $table->foreignId('to_warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnUpdate();

            $table->string('status')->default('draft'); // draft, posted, cancelled
            $table->text('notes')->nullable();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['from_warehouse_id', 'to_warehouse_id']);
            $table->index(['transfer_date']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};