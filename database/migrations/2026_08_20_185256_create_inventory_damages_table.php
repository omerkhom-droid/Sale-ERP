<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_damages', function (Blueprint $table) {
            $table->id();

            $table->string('damage_no')->unique();
            $table->date('damage_date');

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnUpdate();

            $table->string('status')->default('draft'); // draft, posted, cancelled

            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index(['damage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_damages');
    }
};