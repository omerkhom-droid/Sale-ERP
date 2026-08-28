<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\InventoryTransaction;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseInvoiceService
{
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {
    }

    public function store(array $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($data) {

            $supplier = Supplier::findOrFail($data['supplier_id']);

            $items = collect($data['items'] ?? [])
                ->filter(fn ($item) => (float) ($item['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw new Exception('يجب إضافة صنف واحد على الأقل.');
            }

            $calculatedItems = $items->map(function ($item) use ($data) {
                return $this->calculateItem(
                    item: $item,
                    warehouseId: (int) $data['warehouse_id']
                );
            });

            $subtotal = round($calculatedItems->sum('net_amount'), 2);
            $discountAmount = round($calculatedItems->sum('discount_amount'), 2);
            $vatAmount = round($calculatedItems->sum('vat_amount'), 2);
            $totalAmount = round($subtotal + $vatAmount, 2);

            $paymentType = $data['payment_type'] ?? 'credit';

            $paidAmount = $this->calculatePaidAmount(
                paymentType: $paymentType,
                totalAmount: $totalAmount,
                inputPaidAmount: (float) ($data['paid_amount'] ?? 0)
            );

            $remainingAmount = round($totalAmount - $paidAmount, 2);

            if ($remainingAmount < 0) {
                throw new Exception('المبلغ المدفوع لا يمكن أن يكون أكبر من إجمالي الفاتورة.');
            }

            $paymentStatus = $this->getPaymentStatus(
                paidAmount: $paidAmount,
                remainingAmount: $remainingAmount
            );

            $invoiceData = [
                'invoice_no' => $data['invoice_no'] ?? $this->generateInvoiceNo(),
                'supplier_id' => $supplier->id,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'invoice_date' => $data['invoice_date'],
                'payment_type' => $paymentType,
                'payment_method' => $paidAmount > 0 ? ($data['payment_method'] ?? 'cash') : null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'returned_amount' => 0,
                'status' => 'draft',
                'payment_status' => $paymentStatus,
                'return_status' => 'none',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ];

            if (Schema::hasColumn('purchase_invoices', 'supplier_invoice_no')) {
                $invoiceData['supplier_invoice_no'] = $data['supplier_invoice_no'] ?? null;
            }

            if (Schema::hasColumn('purchase_invoices', 'due_date')) {
                $invoiceData['due_date'] = $data['due_date'] ?? null;
            }

            $invoice = PurchaseInvoice::create($invoiceData);

            foreach ($calculatedItems as $item) {
                $itemData = [
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'product_unit_id' => $item['product_unit_id'],
                    'unit_name' => $item['unit_name'],
                    'quantity' => $item['quantity'],
                    'base_quantity' => $item['base_quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'discount_amount' => $item['discount_amount'],
                    'net_amount' => $item['net_amount'],
                    'vat_rate' => $item['vat_rate'],
                    'vat_amount' => $item['vat_amount'],
                    'line_total' => $item['line_total'],
                ];

                if (Schema::hasColumn('purchase_invoice_items', 'unit_price')) {
                    $itemData['unit_price'] = $item['unit_cost'];
                }

                if (Schema::hasColumn('purchase_invoice_items', 'unit_code')) {
                    $itemData['unit_code'] = $item['unit_code'];
                }

                PurchaseInvoiceItem::create($itemData);
            }

            if (($data['save_action'] ?? 'draft') === 'post') {
                return $this->post($invoice);
            }

            return $invoice->fresh([
                'supplier',
                'branch',
                'warehouse',
                'items.product',
                'items.productUnit.unit',
            ]);
        });
    }

    public function post(PurchaseInvoice $invoice): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice) {

            $invoice = PurchaseInvoice::with([
                    'items.product',
                    'items.productUnit.unit',
                ])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($invoice->status === 'posted') {
                throw new Exception('فاتورة المشتريات مرحلة مسبقاً.');
            }

            if ($invoice->status === 'cancelled') {
                throw new Exception('لا يمكن ترحيل فاتورة مشتريات ملغاة.');
            }

            foreach ($invoice->items as $item) {
                $this->increaseStock($invoice, $item);
            }

            $this->createJournalEntry($invoice);

            $invoice->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            return $invoice->fresh([
                'supplier',
                'branch',
                'warehouse',
                'items.product',
                'items.productUnit.unit',
            ]);
        });
    }

    public function cancel(PurchaseInvoice $invoice, ?string $reason = null): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $reason) {

            $invoice = PurchaseInvoice::with([
                    'items.product',
                    'items.productUnit.unit',
                ])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($invoice->status === 'cancelled') {
                throw new Exception('فاتورة المشتريات ملغاة مسبقاً.');
            }

            if ($invoice->status === 'draft') {
                $invoice->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancel_reason' => $reason,
                ]);

                return $invoice->fresh();
            }

            foreach ($invoice->items as $item) {
                $this->decreaseStockAfterCancel($invoice, $item);
            }

            $journalEntries = JournalEntry::where('reference_type', PurchaseInvoice::class)
                ->where('reference_id', $invoice->id)
                ->get();

            foreach ($journalEntries as $entry) {
                $this->journalEntryService->reverse($entry);
            }

            $invoice->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancel_reason' => $reason,
            ]);

            return $invoice->fresh([
                'supplier',
                'branch',
                'warehouse',
                'items.product',
                'items.productUnit.unit',
            ]);
        });
    }

    private function calculateItem(array $item, int $warehouseId): array
    {
        $productId = (int) $item['product_id'];
        $productUnitId = (int) $item['product_unit_id'];

        $product = Product::findOrFail($productId);
        $productUnit = ProductUnit::with('unit')->findOrFail($productUnitId);

        $quantity = (float) $item['quantity'];

        $unitCost = (float) (
            $item['unit_cost']
            ?? $item['unit_price']
            ?? $item['price']
            ?? 0
        );

        if ($unitCost < 0) {
            throw new Exception('تكلفة الصنف لا يمكن أن تكون أقل من صفر.');
        }

        $discountAmount = (float) ($item['discount_amount'] ?? 0);
        $vatRate = (float) ($item['vat_rate'] ?? 15);

        $baseQuantity = $this->getBaseQuantity($productUnit, $quantity);

        $grossAmount = round($quantity * $unitCost, 2);

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
            'unit_name' => $productUnit->unit?->unit_name ?? $productUnit->unit?->name ?? '',
            'unit_code' => $productUnit->unit?->unit_code ?? null,
            'quantity' => $quantity,
            'base_quantity' => $baseQuantity,
            'unit_cost' => $unitCost,
            'discount_amount' => $discountAmount,
            'net_amount' => $netAmount,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'line_total' => $lineTotal,
        ];
    }

    private function increaseStock(PurchaseInvoice $invoice, PurchaseInvoiceItem $item): void
    {
        $stock = ProductStock::where('product_id', $item->product_id)
            ->where('warehouse_id', $invoice->warehouse_id)
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            $stockData = [
                'product_id' => $item->product_id,
                'warehouse_id' => $invoice->warehouse_id,
                'quantity' => 0,
            ];

            if (Schema::hasColumn('product_stocks', 'average_cost')) {
                $stockData['average_cost'] = 0;
            }

            $stock = ProductStock::create($stockData);
        }

        $balanceBefore = (float) $stock->quantity;
        $oldAverageCost = (float) ($stock->average_cost ?? 0);

        $incomingQuantity = (float) $item->base_quantity;
        $incomingUnitCost = (float) $item->unit_cost;

        $balanceAfter = round($balanceBefore + $incomingQuantity, 3);

        $newAverageCost = $oldAverageCost;

        if ($balanceAfter > 0) {
            $oldTotalCost = $balanceBefore * $oldAverageCost;
            $newTotalCost = $incomingQuantity * $incomingUnitCost;
            $newAverageCost = round(($oldTotalCost + $newTotalCost) / $balanceAfter, 4);
        }

        $updateData = [
            'quantity' => $balanceAfter,
        ];

        if (Schema::hasColumn('product_stocks', 'average_cost')) {
            $updateData['average_cost'] = $newAverageCost;
        }

        $stock->update($updateData);

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo(),
            'product_id' => $item->product_id,
            'warehouse_id' => $invoice->warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'purchase',
            'quantity' => $incomingQuantity,
            'unit_cost' => $incomingUnitCost,
            'total_cost' => round($incomingQuantity * $incomingUnitCost, 2),
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $invoice->id,
            'notes' => 'فاتورة مشتريات رقم ' . $invoice->invoice_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }

    private function decreaseStockAfterCancel(PurchaseInvoice $invoice, PurchaseInvoiceItem $item): void
    {
        $stock = ProductStock::where('product_id', $item->product_id)
            ->where('warehouse_id', $invoice->warehouse_id)
            ->lockForUpdate()
            ->firstOrFail();

        $balanceBefore = (float) $stock->quantity;
        $outQuantity = (float) $item->base_quantity;

        if ($balanceBefore < $outQuantity) {
            throw new Exception('لا يمكن إلغاء فاتورة المشتريات لأن مخزون الصنف غير كافٍ بعد عمليات لاحقة.');
        }

        $balanceAfter = round($balanceBefore - $outQuantity, 3);

        $stock->update([
            'quantity' => $balanceAfter,
        ]);

        InventoryTransaction::create([
            'transaction_no' => $this->generateCancelTransactionNo(),
            'product_id' => $item->product_id,
            'warehouse_id' => $invoice->warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'stock_adjustment',
            'quantity' => -1 * $outQuantity,
            'unit_cost' => (float) $item->unit_cost,
            'total_cost' => -1 * round($outQuantity * (float) $item->unit_cost, 2),
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $invoice->id,
            'notes' => 'إلغاء فاتورة مشتريات رقم ' . $invoice->invoice_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }

    private function createJournalEntry(PurchaseInvoice $invoice): void
    {
        $inventoryAccount = $this->accountId('inventory_account');
        $vatInputAccount = $this->accountId('vat_input_account');
        $accountsPayable = $this->accountId('accounts_payable');
        $cashOrBankAccount = $this->paymentAccountId($invoice->payment_method);

        $paidAmount = (float) $invoice->paid_amount;
        $remainingAmount = (float) $invoice->remaining_amount;

        $lines = [
            [
                'account_id' => $inventoryAccount,
                'debit' => (float) $invoice->subtotal,
                'credit' => 0,
                'description' => 'مخزون من فاتورة مشتريات رقم ' . $invoice->invoice_no,
                'supplier_id' => $invoice->supplier_id,
                'branch_id' => $invoice->branch_id,
                'cost_center_id' => $invoice->cost_center_id,
            ],
        ];

        if ((float) $invoice->vat_amount > 0) {
            $lines[] = [
                'account_id' => $vatInputAccount,
                'debit' => (float) $invoice->vat_amount,
                'credit' => 0,
                'description' => 'ضريبة مدخلات فاتورة مشتريات رقم ' . $invoice->invoice_no,
                'supplier_id' => $invoice->supplier_id,
                'branch_id' => $invoice->branch_id,
                'cost_center_id' => $invoice->cost_center_id,
            ];
        }

        if ($remainingAmount > 0) {
            $lines[] = [
                'account_id' => $accountsPayable,
                'debit' => 0,
                'credit' => $remainingAmount,
                'description' => 'مستحق للمورد من فاتورة مشتريات رقم ' . $invoice->invoice_no,
                'supplier_id' => $invoice->supplier_id,
                'branch_id' => $invoice->branch_id,
                'cost_center_id' => $invoice->cost_center_id,
            ];
        }

        if ($paidAmount > 0) {
            $lines[] = [
                'account_id' => $cashOrBankAccount,
                'debit' => 0,
                'credit' => $paidAmount,
                'description' => 'سداد مباشر لفاتورة مشتريات رقم ' . $invoice->invoice_no,
                'supplier_id' => $invoice->supplier_id,
                'branch_id' => $invoice->branch_id,
                'cost_center_id' => $invoice->cost_center_id,
            ];
        }

        $this->journalEntryService->create([
            'entry_date' => $invoice->invoice_date,
            'document_type' => 'purchase_invoice',
            'document_number' => $invoice->invoice_no,
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $invoice->id,
            'description' => 'قيد فاتورة مشتريات رقم ' . $invoice->invoice_no,
            'created_by' => auth()->id(),
            'lines' => $lines,
        ]);
    }

    private function paymentAccountId(?string $paymentMethod): int
    {
        return match ($paymentMethod) {
            'bank_transfer', 'card' => $this->accountId('bank_account'),
            default => $this->accountId('cash_account'),
        };
    }

    private function accountId(string $key): int
    {
        $query = AccountSetting::query();

        if (Schema::hasColumn('account_settings', 'setting_key')) {
            $query->where('setting_key', $key);
        } elseif (Schema::hasColumn('account_settings', 'account_key')) {
            $query->where('account_key', $key);
        } elseif (Schema::hasColumn('account_settings', 'key')) {
            $query->where('key', $key);
        } else {
            throw new Exception('لا يوجد عمود مفتاح في جدول account_settings.');
        }

        $setting = $query->first();

        if (!$setting || !$setting->account_id) {
            throw new Exception('يرجى ضبط الحساب المحاسبي: ' . $key);
        }

        return (int) $setting->account_id;
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

    private function calculatePaidAmount(string $paymentType, float $totalAmount, float $inputPaidAmount): float
    {
        if ($paymentType === 'cash') {
            return $totalAmount;
        }

        if ($paymentType === 'credit') {
            return 0;
        }

        if ($paymentType === 'partial') {
            if ($inputPaidAmount <= 0) {
                throw new Exception('في الفاتورة الجزئية يجب إدخال مبلغ مدفوع.');
            }

            if ($inputPaidAmount >= $totalAmount) {
                throw new Exception('في الفاتورة الجزئية يجب أن يكون المدفوع أقل من إجمالي الفاتورة.');
            }

            return round($inputPaidAmount, 2);
        }

        return 0;
    }

    private function getPaymentStatus(float $paidAmount, float $remainingAmount): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($remainingAmount <= 0) {
            return 'paid';
        }

        return 'partial';
    }

    private function modelValue($model, array $keys): ?string
    {
        if (!$model) {
            return null;
        }

        foreach ($keys as $key) {
            $value = $model->getAttribute($key);

            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private function generateInvoiceNo(): string
    {
        return 'PI-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }

    private function generateTransactionNo(): string
    {
        return 'PUR-TRX-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }

    private function generateCancelTransactionNo(): string
    {
        return 'PUR-CAN-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}
