<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('zatca_compliance_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zatca_device_id')->unique();
            $table->string('fingerprint', 64);
            $table->json('supplier');
            $table->json('tests');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zatca_compliance_runs');
    }
};
