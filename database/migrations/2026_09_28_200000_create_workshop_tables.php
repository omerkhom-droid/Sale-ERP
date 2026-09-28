<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workshop_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('plate_number', 30);
            $table->string('vin', 50)->nullable();
            $table->string('make', 100);
            $table->string('model', 100);
            $table->unsignedSmallInteger('model_year')->nullable();
            $table->string('color', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'plate_number']);
            $table->index(['company_id', 'vin']);
        });

        Schema::create('workshop_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('workshop_vehicles')->restrictOnDelete();
            $table->string('order_number', 40)->unique();
            $table->string('status', 40)->default('received');
            $table->unsignedInteger('odometer')->nullable();
            $table->text('customer_complaint');
            $table->text('diagnosis')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->unique()->constrained('quotations')->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->unique()->constrained('sales_invoices')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'branch_id', 'status']);
        });

        Schema::create('workshop_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_order_id')->constrained('workshop_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_unit_id')->constrained()->restrictOnDelete();
            $table->string('description');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 15, 2);
            $table->string('fulfillment_status', 30)->default('planned');
            $table->timestamps();
        });

        Schema::create('workshop_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_order_id')->constrained('workshop_orders')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_order_events');
        Schema::dropIfExists('workshop_order_items');
        Schema::dropIfExists('workshop_orders');
        Schema::dropIfExists('workshop_vehicles');
    }
};
