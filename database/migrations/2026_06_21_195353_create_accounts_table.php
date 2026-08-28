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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1);

            $table->string('account_code', 50)->unique();
            $table->string('account_name_ar');
            $table->string('account_name_en')->nullable();

            $table->enum('account_type', [
                'asset',
                'liability',
                'equity',
                'revenue',
                'expense'
            ]);

            $table->enum('normal_balance', [
                'debit',
                'credit'
            ]);
            $table->boolean('is_group')->default(false);
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
