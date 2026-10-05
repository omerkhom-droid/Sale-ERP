<?php

namespace App\Services\Zatca;

use App\Models\Company;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class ZatcaSalesInvoiceDataService
{
    public function build(
        SalesInvoice $invoice,
        int $icv,
        string $previousInvoiceHash
    ): array {
        $invoice->loadMissing([
            'customer',
            'branch',
            'items',
        ]);

        if ($invoice->status !== 'posted') {
            throw new RuntimeException(
                'لا يمكن إرسال فاتورة غير مرحلة إلى ZATCA.'
            );
        }

        if (! $invoice->branch) {
            throw new RuntimeException(
                'الفاتورة غير مرتبطة بفرع.'
            );
        }

        if ($invoice->items->isEmpty()) {
            throw new RuntimeException(
                'الفاتورة لا تحتوي على أصناف.'
            );
        }

        $branch = $invoice->branch;

        $company = Company::query()
            ->findOrFail($branch->company_id);

        $isSimplified =
            $invoice->invoice_classification !== 'standard';

        $issueDate = Carbon::parse(
            $invoice->invoice_date
        );

        $issueTime = Carbon::parse(
            $invoice->posted_at
                ?: $invoice->created_at
        );

        $lines = $invoice->items
            ->values()
            ->map(function ($item, int $index): array {
                $quantity = (float) $item->quantity;
                $unitPrice = (float) $item->unit_price;
                $discount = (float) $item->discount_amount;
                $netAmount = (float) $item->net_amount;
                $vatRate = (float) $item->vat_rate;
                $vatAmount = (float) $item->vat_amount;
                $lineTotal = (float) $item->line_total;

                return [
                    'id' => (string) ($index + 1),

                    'name' =>
                        $item->product_name
                        ?: $item->product_sku
                        ?: 'Product',

                    'quantity' => $quantity,

                    'unit' => $this->unitCode(
                        $item->unit_name
                    ),

                    'unit_price' => $unitPrice,
                    'discount_amount' => $discount,
                    'line_extension_amount' => $netAmount,

                    'tax_category' =>
                        $vatRate > 0 ? 'S' : 'Z',

                    'tax_percent' => $vatRate,
                    'tax_amount' => $vatAmount,
                    'tax_inclusive_amount' => $lineTotal,
                ];
            })
            ->all();

        $lineExtensionTotal = round(
            collect($lines)->sum(
                'line_extension_amount'
            ),
            2
        );

        $taxTotal = round(
            (float) $invoice->vat_amount,
            2
        );

        $taxInclusiveTotal = round(
            (float) $invoice->total_amount,
            2
        );

        $taxExclusiveTotal = round(
            $taxInclusiveTotal - $taxTotal,
            2
        );

        return [
            'invoice_number' =>
                (string) $invoice->invoice_no,

            'uuid' => (string) Str::uuid(),

            'issue_date' =>
                $issueDate->format('Y-m-d'),

            'issue_time' =>
                $issueTime->copy()
                    ->utc()
                    ->format('H:i:s'),

            'supply_date' =>
                $issueDate->format('Y-m-d'),

            'invoice_type_code' => '388',

            'invoice_type_name' =>
                $isSimplified
                    ? '0200000'
                    : '0100000',

            'currency' => 'SAR',
            'icv' => $icv,

            'previous_invoice_hash' =>
                $previousInvoiceHash,

            'supplier' => [
                'name' =>
                    $company->name_ar
                    ?: $company->name_en,

                'vat_number' =>
                    $branch->tax_registration_number
                    ?: $company->tax_number,

                'registration_number' =>
                    $company->commercial_registration,

                'registration_scheme' => 'CRN',

                'street' =>
                    $branch->street_name,

                'building_number' =>
                    $branch->building_number,

                'district' =>
                    $branch->neighborhood,

                'city' =>
                    $branch->city,

                'postal_code' =>
                    $branch->postal_zone,

                'country_code' =>
                    $branch->country_code ?: 'SA',
            ],

            'customer' => $this->customer(
                $invoice,
                $isSimplified
            ),

            'lines' => $lines,

            'line_extension_total' =>
                $lineExtensionTotal,

            'tax_exclusive_total' =>
                $taxExclusiveTotal,

            'tax_total' =>
                $taxTotal,

            'tax_inclusive_total' =>
                $taxInclusiveTotal,

            'payable_total' =>
                $taxInclusiveTotal,

            'qr' => '',
        ];
    }

    private function customer(
        SalesInvoice $invoice,
        bool $isSimplified
    ): array {
        if ($isSimplified) {
            return [
                'name' =>
                    $invoice->customer_name
                    ?: 'Cash Customer',

                'vat_number' => null,
            ];
        }

        return [
            'name' =>
                $invoice->customer_name,

            'vat_number' =>
                $invoice->customer_tax_number,

            'registration_number' =>
                $invoice->customer_commercial_register,

            'registration_scheme' => 'CRN',

            'street' =>
                $invoice->customer_street_name,

            'building_number' =>
                $invoice->customer_building_number,

            'district' =>
                $invoice->customer_district,

            'city' =>
                $invoice->customer_city,

            'postal_code' =>
                $invoice->customer_postal_code,

            'country_code' =>
                $invoice->customer_country_code ?: 'SA',
        ];
    }

    private function unitCode(
        ?string $unitName
    ): string {
        $unit = mb_strtolower(
            trim((string) $unitName)
        );

        return match ($unit) {
            'حبة',
            'قطعة',
            'pcs',
            'piece' => 'PCE',

            'كيلو',
            'كيلوجرام',
            'kg' => 'KGM',

            'لتر',
            'liter',
            'litre' => 'LTR',

            'ساعة',
            'hour' => 'HUR',

            'خدمة',
            'service' => 'E48',

            default => 'PCE',
        };
    }
}