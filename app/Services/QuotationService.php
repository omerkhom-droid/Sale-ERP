<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Exception;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    | حفظ عرض سعر.
    |
    | عرض السعر لا يؤثر على المخزون ولا الحسابات.
    */
    public function store(array $data): Quotation
    {
        return DB::transaction(function () use ($data) {

            $customer = null;

            if (! empty($data['customer_id'])) {
                $customer = Customer::findOrFail($data['customer_id']);
            }

            $customerData = $this->prepareCustomerData($data, $customer);

            $items = collect($data['items'] ?? [])
                ->filter(fn ($item) => (float) ($item['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw new Exception('يجب إضافة صنف واحد على الأقل.');
            }

            $calculatedItems = $items->map(function ($item) {
                return $this->calculateItem($item);
            });

            $subtotal = round($calculatedItems->sum('net_amount'), 2);
            $discountAmount = round($calculatedItems->sum('discount_amount'), 2);
            $vatAmount = round($calculatedItems->sum('vat_amount'), 2);
            $totalAmount = round($subtotal + $vatAmount, 2);

            $status = ($data['save_action'] ?? 'draft') === 'sent'
                ? 'sent'
                : 'draft';

            $quotation = Quotation::create([
                'quotation_no' => $data['quotation_no'] ?? $this->generateQuotationNo(),

                'customer_id' => $customer?->id,
                'customer_type' => $data['customer_type'] ?? 'cash',

                'customer_name' => $customerData['customer_name'],
                'customer_mobile' => $customerData['customer_mobile'],
                'customer_tax_number' => $customerData['customer_tax_number'],
                'customer_address' => $customerData['customer_address'],

                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,

                'quotation_date' => $data['quotation_date'],
                'valid_until' => $data['valid_until'] ?? null,

                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,

                'status' => $status,

                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,

                'created_by' => auth()->id(),
            ]);

            foreach ($calculatedItems as $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,

                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],

                    'product_unit_id' => $item['product_unit_id'],
                    'unit_name' => $item['unit_name'],

                    'quantity' => $item['quantity'],
                    'base_quantity' => $item['base_quantity'],

                    'unit_price' => $item['unit_price'],

                    'discount_amount' => $item['discount_amount'],
                    'net_amount' => $item['net_amount'],

                    'vat_rate' => $item['vat_rate'],
                    'vat_amount' => $item['vat_amount'],

                    'line_total' => $item['line_total'],
                ]);
            }

            return $quotation->fresh([
                'customer',
                'branch',
                'costCenter',
                'warehouse',
                'items.product',
                'items.productUnit.unit',
            ]);
        });
    }

    public function update(Quotation $quotation, array $data): Quotation
    {
        return DB::transaction(function () use ($quotation, $data) {

            $quotation = Quotation::with('items')
                ->lockForUpdate()
                ->findOrFail($quotation->id);

            if (in_array($quotation->status, ['converted', 'cancelled'], true)) {
                throw new Exception('لا يمكن تعديل عرض سعر محول إلى فاتورة أو ملغي.');
            }

            $customer = null;

            if (! empty($data['customer_id'])) {
                $customer = Customer::findOrFail($data['customer_id']);
            }

            $customerData = $this->prepareCustomerData($data, $customer);

            $items = collect($data['items'] ?? [])
                ->filter(fn ($item) => (float) ($item['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw new Exception('يجب إضافة صنف واحد على الأقل.');
            }

            $calculatedItems = $items->map(function ($item) {
                return $this->calculateItem($item);
            });

            $subtotal = round($calculatedItems->sum('net_amount'), 2);
            $discountAmount = round($calculatedItems->sum('discount_amount'), 2);
            $vatAmount = round($calculatedItems->sum('vat_amount'), 2);
            $totalAmount = round($subtotal + $vatAmount, 2);

            /*
                عند تعديل عرض معتمد، نعيد حالته حسب زر الحفظ؛
                لأن البيانات تغيرت ولا يصح يبقى معتمد بنفس الاعتماد القديم.
            */
            $status = ($data['save_action'] ?? 'draft') === 'sent'
                ? 'sent'
                : 'draft';

            $quotation->update([
                'quotation_no' => $data['quotation_no'] ?: $quotation->quotation_no,

                'customer_id' => $customer?->id,
                'customer_type' => $data['customer_type'] ?? 'cash',

                'customer_name' => $customerData['customer_name'],
                'customer_mobile' => $customerData['customer_mobile'],
                'customer_tax_number' => $customerData['customer_tax_number'],
                'customer_address' => $customerData['customer_address'],

                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,

                'quotation_date' => $data['quotation_date'],
                'valid_until' => $data['valid_until'] ?? null,

                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,

                'status' => $status,

                'approved_at' => null,
                'approved_by' => null,

                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            $quotation->items()->delete();

            foreach ($calculatedItems as $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,

                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],

                    'product_unit_id' => $item['product_unit_id'],
                    'unit_name' => $item['unit_name'],

                    'quantity' => $item['quantity'],
                    'base_quantity' => $item['base_quantity'],

                    'unit_price' => $item['unit_price'],

                    'discount_amount' => $item['discount_amount'],
                    'net_amount' => $item['net_amount'],

                    'vat_rate' => $item['vat_rate'],
                    'vat_amount' => $item['vat_amount'],

                    'line_total' => $item['line_total'],
                ]);
            }

            return $quotation->fresh([
                'customer',
                'branch',
                'costCenter',
                'warehouse',
                'items.product',
                'items.productUnit.unit',
            ]);
        });
    }
    private function prepareCustomerData(array $data, ?Customer $customer): array
    {
        return [
            'customer_name' => $data['customer_name']
                ?? $this->modelValue($customer, ['customer_name', 'name', 'fullname'])
                ?? 'عميل نقدي',

            'customer_mobile' => $data['customer_mobile']
                ?? $this->modelValue($customer, ['mobile', 'phone']),

            'customer_tax_number' => $data['customer_tax_number']
                ?? $this->modelValue($customer, ['tax_registration_number', 'tax_number', 'vat_number']),

            'customer_address' => $data['customer_address']
                ?? $this->modelValue($customer, ['address', 'full_address']),
        ];
    }

    private function calculateItem(array $item): array
    {
        $productId = (int) $item['product_id'];
        $productUnitId = (int) $item['product_unit_id'];

        $product = Product::findOrFail($productId);
        $productUnit = ProductUnit::with('unit')->findOrFail($productUnitId);

        $quantity = (float) $item['quantity'];
        $unitPrice = (float) $item['unit_price'];
        $discountAmount = (float) ($item['discount_amount'] ?? 0);
        $vatRate = (float) ($item['vat_rate'] ?? 15);

        $baseQuantity = $this->getBaseQuantity(
            productUnit: $productUnit,
            quantity: $quantity
        );

        $grossAmount = round($quantity * $unitPrice, 2);

        if ($discountAmount > $grossAmount) {
            throw new Exception('خصم السطر لا يمكن أن يكون أكبر من قيمة السطر.');
        }

        $netAmount = round($grossAmount - $discountAmount, 2);

        $vatAmount = round($netAmount * ($vatRate / 100), 2);

        $lineTotal = round($netAmount + $vatAmount, 2);

        return [
            'product_id' => $productId,
            'product_name' => $this->modelValue($product, ['product_name_ar', 'product_name', 'name']) ?? '',
            'product_sku' => $this->modelValue($product, ['sku', 'product_code']) ?? '',

            'product_unit_id' => $productUnitId,
            'unit_name' => $productUnit->unit?->unit_name
                ?? $productUnit->unit?->name
                ?? '',

            'quantity' => $quantity,
            'base_quantity' => $baseQuantity,

            'unit_price' => $unitPrice,

            'discount_amount' => $discountAmount,
            'net_amount' => $netAmount,

            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,

            'line_total' => $lineTotal,
        ];
    }

    private function getBaseQuantity(ProductUnit $productUnit, float $quantity): float
    {
        $factor = (float) (
            $productUnit->getAttribute('conversion_factor')
            ?? $productUnit->getAttribute('factor')
            ?? 1
        );

        if ($factor <= 0) {
            $factor = 1;
        }

        return round($quantity * $factor, 3);
    }

    private function modelValue($model, array $attributes): mixed
    {
        if (! $model) {
            return null;
        }

        foreach ($attributes as $attribute) {
            if (! empty($model->{$attribute})) {
                return $model->{$attribute};
            }
        }

        return null;
    }

    private function generateQuotationNo(): string
    {
        do {
            $number = 'QUO-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
        } while (Quotation::where('quotation_no', $number)->exists());

        return $number;
    }
}