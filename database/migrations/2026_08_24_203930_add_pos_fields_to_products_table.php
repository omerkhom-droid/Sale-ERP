<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('show_in_pos')->default(false)->after('track_inventory');
            $table->unsignedInteger('pos_sort_order')->default(0)->after('show_in_pos');
            $table->string('pos_button_color')->nullable()->after('pos_sort_order');

            $table->index('show_in_pos');
            $table->index('pos_sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['show_in_pos']);
            $table->dropIndex(['pos_sort_order']);

            $table->dropColumn([
                'show_in_pos',
                'pos_sort_order',
                'pos_button_color',
            ]);
        });
    }
};