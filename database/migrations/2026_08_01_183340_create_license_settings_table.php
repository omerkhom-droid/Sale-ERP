<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_settings', function (Blueprint $table) {
            $table->id();

            $table->string('client_name')->nullable();
            $table->string('license_key')->nullable()->unique();

            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();

            $table->enum('status', [
                'trial',
                'active',
                'expired',
                'suspended',
            ])->default('trial');

            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('max_branches')->nullable();

            $table->timestamp('last_check_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_settings');
    }
};