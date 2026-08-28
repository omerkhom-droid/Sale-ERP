<?php

namespace Database\Seeders;

use App\Models\DocumentSequence;
use Illuminate\Database\Seeder;

class DocumentSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $sequences = [
            // المنتجات والأطراف
            ['document_type' => 'product', 'prefix' => 'PRD'],
            ['document_type' => 'customer', 'prefix' => 'CUS'],
            ['document_type' => 'supplier', 'prefix' => 'SUP'],

            // عروض وفواتير المبيعات
            ['document_type' => 'quotation', 'prefix' => 'QUO'],
            ['document_type' => 'sales_invoice', 'prefix' => 'SI'],
            ['document_type' => 'sales_return', 'prefix' => 'SR'],

            // المشتريات
            ['document_type' => 'purchase_invoice', 'prefix' => 'PI'],
            ['document_type' => 'purchase_return', 'prefix' => 'PR'],

            // سندات العملاء
            ['document_type' => 'customer_receipt', 'prefix' => 'CR'],
            ['document_type' => 'customer_refund', 'prefix' => 'RF'],

            // سندات الموردين
            ['document_type' => 'supplier_payment', 'prefix' => 'SP'],

            // السندات العامة
            ['document_type' => 'general_receipt', 'prefix' => 'GR'],
            ['document_type' => 'general_payment', 'prefix' => 'GP'],

            // القيود اليومية
            ['document_type' => 'journal_entry', 'prefix' => 'JE'],
        ];

        foreach ($sequences as $sequence) {
            DocumentSequence::updateOrCreate(
                [
                    'document_type' => $sequence['document_type'],
                ],
                [
                    'prefix' => $sequence['prefix'],
                    'padding' => 6,
                    'is_active' => true,
                ]
            );
        }
    }
}