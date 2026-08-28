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
        Schema::create('manual_journal_entries', function (Blueprint $table) {
            $table->id();

            $table->string('manual_no')->unique();

            $table->date('manual_date');

            /*
                خلي branch_id بدون constrained
                نفس طريقتنا السابقة لأن جدول branches عندك قد يختلف.
            */
            $table->unsignedBigInteger('branch_id')
                ->nullable()
                ->index();

            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft');

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->string('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index(['manual_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_journal_entries');
    }
};