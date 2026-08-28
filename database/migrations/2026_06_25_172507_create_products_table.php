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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete();

            $table->string('sku', 100)->unique();

            $table->string('product_name_ar');
            $table->string('product_name_en')->nullable();
            
            $table->string('short_name',100)->nullable();

            $table->text('keywords')->nullable();
            $table->enum('product_type',['inventory', 'service'])->default('inventory');

            $table->text('description')->nullable();

            $table->decimal('minimum_quantity', 12, 3)->default(0);

            $table->boolean('track_inventory')->default(true);
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
        Schema::dropIfExists('products');
    }
};
