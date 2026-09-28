<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workshop_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_order_id')->constrained('workshop_orders')->restrictOnDelete();
            $table->string('category', 30);
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['workshop_order_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_attachments');
    }
};
