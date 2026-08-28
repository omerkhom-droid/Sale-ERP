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
        Schema::create('manual_journal_entry_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('manual_journal_entry_id')
                ->constrained('manual_journal_entries')
                ->cascadeOnDelete();

            /*
                نفس فكرة الأرصدة الافتتاحية:
                الفرع ومركز التكلفة على مستوى السطر.
            */
            $table->unsignedBigInteger('branch_id')
                ->nullable()
                ->index();

            $table->unsignedBigInteger('cost_center_id')
                ->nullable()
                ->index();

            $table->enum('line_type', ['account', 'customer', 'supplier'])
                ->default('account');

            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);

            $table->string('description')->nullable();

            $table->timestamps();

            $table->index('line_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_journal_entry_lines');
    }
};