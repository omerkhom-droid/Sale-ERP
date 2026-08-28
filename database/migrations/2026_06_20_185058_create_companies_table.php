<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('code')->nullable()->unique();

            $table->string('name_ar');
            $table->string('name_en')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('tax_number')->nullable();
            $table->string('commercial_registration')->nullable();

            $table->string('city')->nullable();
            $table->string('address')->nullable();

            $table->string('logo')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};