<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table) {
            $table->string('priority', 20)->default('normal');
            $table->dateTime('promised_at')->nullable();

            $table->foreignId('received_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->unsignedTinyInteger('fuel_percent')->nullable();
            $table->unsignedTinyInteger('key_count')->nullable();
            $table->text('condition_notes')->nullable();
            $table->text('belongings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by');

            $table->dropColumn([
                'priority',
                'promised_at',
                'fuel_percent',
                'key_count',
                'condition_notes',
                'belongings',
            ]);
        });
    }
};