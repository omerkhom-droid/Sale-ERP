<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_balance_lines', function (Blueprint $table) {
            $table->id();

            /*
                في مرحلة التطوير نستخدم unsignedBigInteger بدون foreign key
                لتجنب مشاكل ترتيب المايجريشن أو اختلاف المفاتيح.
            */
            $table->unsignedBigInteger('opening_balance_id')->index();

            /*
                الفرع ومركز التكلفة على مستوى السطر
                وليس فقط على رأس المستند.
            */
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('cost_center_id')->nullable()->index();

            $table->enum('line_type', ['account', 'customer', 'supplier']);

            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();

            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);

            $table->string('description')->nullable();

            $table->timestamps();

            $table->index(['line_type', 'account_id']);
            $table->index(['line_type', 'customer_id']);
            $table->index(['line_type', 'supplier_id']);
            $table->index(['branch_id', 'cost_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balance_lines');
    }
};