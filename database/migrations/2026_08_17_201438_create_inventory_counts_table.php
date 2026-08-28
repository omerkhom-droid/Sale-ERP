<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->string('count_no')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnUpdate();
            $table->date('count_date');
            $table->string('scope_type')->default('all'); // all, category, brand
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('status')->default('draft'); // draft, posted, cancelled
            $table->text('notes')->nullable();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index(['count_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_counts');
    }
};