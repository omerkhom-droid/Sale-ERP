<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('zatca_icv')
                ->nullable()
                ->after('invoice_classification');

            $table->string('zatca_previous_invoice_hash', 255)
                ->nullable()
                ->after('zatca_icv');

            $table->uuid('zatca_uuid')
                ->nullable()
                ->unique()
                ->after('zatca_previous_invoice_hash');

            $table->string('zatca_invoice_hash', 255)
                ->nullable()
                ->after('zatca_uuid');

            $table->string('zatca_status', 30)
                ->nullable()
                ->index()
                ->after('zatca_invoice_hash');

            $table->unsignedSmallInteger('zatca_http_status')
                ->nullable()
                ->after('zatca_status');

            $table->unsignedInteger('zatca_attempts')
                ->default(0)
                ->after('zatca_http_status');

            $table->longText('zatca_qr')
                ->nullable()
                ->after('zatca_attempts');

            $table->longText('zatca_signed_xml')
                ->nullable()
                ->after('zatca_qr');

            $table->longText('zatca_cleared_xml')
                ->nullable()
                ->after('zatca_signed_xml');

            $table->longText('zatca_response')
                ->nullable()
                ->after('zatca_cleared_xml');

            $table->text('zatca_error')
                ->nullable()
                ->after('zatca_response');

            $table->timestamp('zatca_sent_at')
                ->nullable()
                ->after('zatca_error');
        });

        Schema::table('zatca_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_counter')
                ->default(0)
                ->after('status');

            $table->string('previous_invoice_hash', 255)
                ->default(
                    'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ=='
                )
                ->after('invoice_counter');

            $table->boolean('auto_submit')
                ->default(false)
                ->after('previous_invoice_hash');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique([
                'zatca_uuid',
            ]);

            $table->dropIndex([
                'zatca_status',
            ]);

            $table->dropColumn([
                'zatca_icv',
                'zatca_previous_invoice_hash',
                'zatca_uuid',
                'zatca_invoice_hash',
                'zatca_status',
                'zatca_http_status',
                'zatca_attempts',
                'zatca_qr',
                'zatca_signed_xml',
                'zatca_cleared_xml',
                'zatca_response',
                'zatca_error',
                'zatca_sent_at',
            ]);
        });

        Schema::table('zatca_devices', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_counter',
                'previous_invoice_hash',
            ]);
        });
    }
};