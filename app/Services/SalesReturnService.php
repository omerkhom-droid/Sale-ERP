<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\InventoryTransaction;
use App\Models\JournalEntry;
use App\Models\ProductStock;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Services\InventoryGuardService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesReturnService
{
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {}


    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    | حفظ مردود المبيعات كمسودة أو حفظ وترحيل.
    */
    public function store(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {

            $invoice = SalesInvoice::with([
                    'items.returnItems.salesReturn',
                    'items.product',
                    'items.productUnit.unit',
                ])
                ->lockForUpdate()
                ->findOrFail($data['sales_invoice_id']);

            if ($invoice->status !== 'posted') {
                throw new Exception('لا يمكن عمل مردود إلا على فاتورة بيع مرحلة.');
            }

            $preparedItems = $this->prepareItems($invoice, $data['items'] ?? []);

            if (count($preparedItems) === 0) {
                throw new Exception('يجب إدخال كمية مردود لصنف واحد على الأقل.');
            }

            $subtotal = round(collect($preparedItems)->sum('net_amount'), 2);
            $discountAmount = round(collect($preparedItems)->sum('discount_amount'), 2);
            $vatAmount = round(collect($preparedItems)->sum('vat_amount'), 2);
            $totalAmount = round(collect($preparedItems)->sum('line_total'), 2);
            $totalCost = round(collect($preparedItems)->sum('total_cost'), 2);

            /*
                applied_amount:
                الجزء الذي يخفض المتبقي على الفاتورة.

                refundable_amount:
                الجزء الزائد إذا كانت الفاتورة مدفوعة أو المتبقي أقل من قيمة المردود.
            */
            $currentRemaining = (float) $invoice->remaining_amount;

            $appliedAmount = min($totalAmount, $currentRemaining);
            $refundableAmount = round($totalAmount - $appliedAmount, 2);

            $salesReturn = SalesReturn::create([
                'return_no' => $data['return_no'] ?? $this->generateReturnNo(),

                'sales_invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'branch_id' => $invoice->branch_id,
                'cost_center_id' => $invoice->cost_center_id,
                'warehouse_id' => $invoice->warehouse_id,

                'return_date' => $data['return_date'],

                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'total_cost' => $totalCost,

                'applied_amount' => $appliedAmount,
                'refundable_amount' => $refundableAmount,

                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($preparedItems as $item) {
                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,

                    'sales_invoice_item_id' => $item['sales_invoice_item_id'],
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['product_unit_id'],

                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'unit_name' => $item['unit_name'],

                    'quantity' => $item['quantity'],
                    'base_quantity' => $item['base_quantity'],

                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $item['total_cost'],

                    'discount_amount' => $item['discount_amount'],
                    'net_amount' => $item['net_amount'],

                    'vat_rate' => $item['vat_rate'],
                    'vat_amount' => $item['vat_amount'],
                    'line_total' => $item['line_total'],
                ]);
            }

            if (($data['save_action'] ?? 'draft') === 'post') {
                $this->post($salesReturn);
            }

            return $salesReturn->fresh([
                'salesInvoice',
                'customer',
                'warehouse',
                'items.product',
                'items.productUnit.unit',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | post
    |--------------------------------------------------------------------------
    | ترحيل مردود المبيعات:
    | - زيادة المخزون
    | - تخفيض مديونية العميل
    | - إنشاء قيود عكس البيع والتكلفة
    */

    public function post(SalesReturn $salesReturn): SalesReturn
    {
        return DB::transaction(function () use ($salesReturn) {

            $salesReturn = SalesReturn::with([
                    'salesInvoice',
                    'items',
                ])
                ->lockForUpdate()
                ->findOrFail($salesReturn->id);

            if ($salesReturn->status === 'posted') {
                throw new Exception('مردود المبيعات مرحل مسبقاً.');
            }

            if ($salesReturn->status === 'cancelled') {
                throw new Exception('لا يمكن ترحيل مردود مبيعات ملغى.');
            }

            $invoice = SalesInvoice::lockForUpdate()
                ->findOrFail($salesReturn->sales_invoice_id);

            if ($invoice->status !== 'posted') {
                throw new Exception('الفاتورة الأصلية غير مرحلة.');
            }
            app(SalesDocumentBalanceService::class)->refresh($invoice);
            $applied = min((float) $salesReturn->total_amount, (float) $invoice->remaining_amount);
            $salesReturn->update([
                'applied_amount' => round($applied, 2),
                'refundable_amount' => round((float) $salesReturn->total_amount - $applied, 2),
            ]);

            app(InventoryGuardService::class)->assertDateAfterLastPostedInventoryCount(
                warehouseId: (int) $salesReturn->warehouse_id,
                documentDate: $salesReturn->return_date,
                documentName: 'مردود المبيعات'
            );


            /*
                زيادة المخزون.
            */
            foreach ($salesReturn->items as $item) {
                $this->increaseStock($salesReturn, $item);
            }

            /*
                تحديث الفاتورة الأصلية.
            */
            $this->applyReturnToInvoice($invoice, $salesReturn);

            /*
                القيود المحاسبية.
            */
            $this->createJournalEntries($salesReturn);

            $salesReturn->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            app(\App\Services\AuditLogService::class)->log(
                action: 'post',
                module: 'Sales Return',
                description: 'تم ترحيل مردود مبيعات رقم ' . $salesReturn->return_no,
                model: $salesReturn,
                newValues: [
                    'return_no' => $salesReturn->return_no,
                    'sales_invoice_id' => $salesReturn->sales_invoice_id,
                    'customer_id' => $salesReturn->customer_id,
                    'branch_id' => $salesReturn->branch_id,
                    'warehouse_id' => $salesReturn->warehouse_id,
                    'return_date' => $salesReturn->return_date,
                    'total_amount' => $salesReturn->total_amount,
                    'total_cost' => $salesReturn->total_cost,
                    'applied_amount' => $salesReturn->applied_amount,
                    'refundable_amount' => $salesReturn->refundable_amount,
                    'status' => $salesReturn->status,
                ],
                branchId: $salesReturn->branch_id
            );

            return $salesReturn->fresh([
                'salesInvoice',
                'customer',
                'warehouse',
                'items.product',
                'items.productUnit.unit',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | cancel
    |--------------------------------------------------------------------------
    | إلغاء مردود المبيعات:
    | - إنقاص المخزون مرة أخرى
    | - إعادة أثر الفاتورة
    | - عكس القيود
    */
    public function cancel(SalesReturn $salesReturn, ?string $reason = null): SalesReturn
    {
        return DB::transaction(function () use ($salesReturn, $reason) {

            $salesReturn = SalesReturn::with([
                    'salesInvoice',
                    'items',
                ])
                ->lockForUpdate()
                ->findOrFail($salesReturn->id);

            if ($salesReturn->status === 'cancelled') {
                throw new Exception('مردود المبيعات ملغى مسبقاً.');
            }

            if ($salesReturn->status === 'draft') {
                $salesReturn->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancel_reason' => $reason,
                ]);

                app(\App\Services\AuditLogService::class)->log(
                    action: 'cancel',
                    module: 'Sales Return',
                    description: 'تم إلغاء مردود مبيعات مسودة رقم ' . $salesReturn->return_no,
                    model: $salesReturn,
                    oldValues: [
                        'status' => 'draft',
                        'return_no' => $salesReturn->return_no,
                        'sales_invoice_id' => $salesReturn->sales_invoice_id,
                        'customer_id' => $salesReturn->customer_id,
                        'total_amount' => $salesReturn->total_amount,
                    ],
                    newValues: [
                        'status' => 'cancelled',
                        'cancel_reason' => $reason,
                        'cancelled_at' => now(),
                    ],
                    branchId: $salesReturn->branch_id
                );
                
                return $salesReturn->fresh();
            }

            $invoice = SalesInvoice::lockForUpdate()
                ->findOrFail($salesReturn->sales_invoice_id);

            /*
                إنقاص المخزون الذي تمت زيادته بسبب المردود.
            */
            foreach ($salesReturn->items as $item) {
                $this->decreaseStockAfterCancel($salesReturn, $item);
            }

            /*
                إعادة أثر المردود من الفاتورة الأصلية.
            */
            $this->removeReturnFromInvoice($invoice, $salesReturn);

            /*
                عكس القيود المحاسبية الخاصة بالمردود.
                JournalEntryService::reverse() عندك يستقبل JournalEntry Model.
            */
            $journalEntries = JournalEntry::where('reference_type', SalesReturn::class)
                ->where('reference_id', $salesReturn->id)
                ->get();

            foreach ($journalEntries as $entry) {
                $this->journalEntryService->reverse($entry);
            }

            $salesReturn->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancel_reason' => $reason,
            ]);

            app(\App\Services\AuditLogService::class)->log(
                action: 'cancel',
                module: 'Sales Return',
                description: 'تم إلغاء مردود مبيعات مرحل رقم ' . $salesReturn->return_no,
                model: $salesReturn,
                oldValues: [
                    'status' => 'posted',
                    'return_no' => $salesReturn->return_no,
                    'sales_invoice_id' => $salesReturn->sales_invoice_id,
                    'customer_id' => $salesReturn->customer_id,
                    'warehouse_id' => $salesReturn->warehouse_id,
                    'total_amount' => $salesReturn->total_amount,
                    'total_cost' => $salesReturn->total_cost,
                ],
                newValues: [
                    'status' => 'cancelled',
                    'cancel_reason' => $reason,
                    'cancelled_at' => now(),
                ],
                branchId: $salesReturn->branch_id
            );

            return $salesReturn->fresh([
                'salesInvoice',
                'customer',
                'warehouse',
                'items.product',
                'items.productUnit.unit',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | prepareItems
    |--------------------------------------------------------------------------
    | تجهيز بنود المردود من بنود فاتورة البيع الأصلية.
    */
    private function prepareItems(SalesInvoice $invoice, array $items): array
    {
        $preparedItems = [];

        foreach ($items as $row) {

            $returnQty = round((float) ($row['quantity'] ?? 0), 3);

            if ($returnQty <= 0) {
                continue;
            }

            $invoiceItem = SalesInvoiceItem::with([
                    'returnItems.salesReturn',
                    'product',
                    'productUnit.unit',
                ])
                ->findOrFail($row['sales_invoice_item_id']);

            if ((int) $invoiceItem->sales_invoice_id !== (int) $invoice->id) {
                throw new Exception('يوجد بند لا يخص فاتورة البيع المحددة.');
            }

            /*
                الكمية المتاحة للإرجاع.
            */
            $previousReturnedQty = $invoiceItem->returnItems
                ->filter(function ($returnItem) {
                    return $returnItem->salesReturn
                        && $returnItem->salesReturn->status === 'posted';
                })
                ->sum('quantity');

            $availableQty = round(
                (float) $invoiceItem->quantity - (float) $previousReturnedQty,
                3
            );

            if ($returnQty > $availableQty) {
                throw new Exception(
                    'كمية المردود أكبر من المتاح للصنف: ' .
                    ($invoiceItem->product_name ?? '-') .
                    ' المتاح: ' . $availableQty
                );
            }

            /*
                نسبة المردود من البند الأصلي.
            */
            $ratio = $returnQty / (float) $invoiceItem->quantity;

            $baseQuantity = round((float) $invoiceItem->base_quantity * $ratio, 3);

            $discountAmount = round((float) $invoiceItem->discount_amount * $ratio, 2);
            $netAmount = round((float) $invoiceItem->net_amount * $ratio, 2);
            $vatAmount = round((float) $invoiceItem->vat_amount * $ratio, 2);
            $lineTotal = round((float) $invoiceItem->line_total * $ratio, 2);

            $totalCost = round((float) $invoiceItem->total_cost * $ratio, 2);

            $preparedItems[] = [
                'sales_invoice_item_id' => $invoiceItem->id,
                'product_id' => $invoiceItem->product_id,
                'product_unit_id' => $invoiceItem->product_unit_id,

                'product_name' => $invoiceItem->product_name
                    ?? $invoiceItem->product?->product_name_ar
                    ?? $invoiceItem->product?->product_name
                    ?? '-',

                'product_sku' => $invoiceItem->product_sku
                    ?? $invoiceItem->product?->sku
                    ?? $invoiceItem->product?->product_code
                    ?? null,

                'unit_name' => $invoiceItem->unit_name
                    ?? $invoiceItem->productUnit?->unit?->unit_name
                    ?? $invoiceItem->productUnit?->unit?->name
                    ?? '-',

                'quantity' => $returnQty,
                'base_quantity' => $baseQuantity,

                'unit_price' => (float) $invoiceItem->unit_price,
                'unit_cost' => (float) $invoiceItem->unit_cost,
                'total_cost' => $totalCost,

                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,

                'vat_rate' => (float) $invoiceItem->vat_rate,
                'vat_amount' => $vatAmount,
                'line_total' => $lineTotal,
            ];
        }

        return $preparedItems;
    }

    /*
    |--------------------------------------------------------------------------
    | increaseStock
    |--------------------------------------------------------------------------
    | زيادة المخزون عند ترحيل مردود المبيعات.
    */
    private function increaseStock(SalesReturn $salesReturn, SalesReturnItem $item): void
    {
        $stock = ProductStock::where('product_id', $item->product_id)
            ->where('warehouse_id', $salesReturn->warehouse_id)
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            $stock = ProductStock::create([
                'product_id' => $item->product_id,
                'warehouse_id' => $salesReturn->warehouse_id,
                'quantity' => 0,
                'average_cost' => 0,
            ]);
        }

        $oldQuantity = (float) $stock->quantity;
        $oldAverageCost = (float) ($stock->average_cost ?? 0);

        $returnQuantity = (float) $item->base_quantity;
        $returnCost = (float) $item->unit_cost;

        $balanceBefore = $oldQuantity;
        $balanceAfter = round($oldQuantity + $returnQuantity, 3);

        /*
            إعادة حساب متوسط التكلفة عند رجوع الصنف للمخزون.
        */
        $newAverageCost = $oldAverageCost;

        if ($balanceAfter > 0 && Schema::hasColumn('product_stocks', 'average_cost')) {
            $oldTotalCost = $oldQuantity * $oldAverageCost;
            $returnTotalCost = $returnQuantity * $returnCost;

            $newAverageCost = round(
                ($oldTotalCost + $returnTotalCost) / $balanceAfter,
                4
            );
        }

        $updateData = [
            'quantity' => $balanceAfter,
        ];

        if (Schema::hasColumn('product_stocks', 'average_cost')) {
            $updateData['average_cost'] = $newAverageCost;
        }

        $stock->update($updateData);

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo('SR-IN'),
            'product_id' => $item->product_id,
            'warehouse_id' => $salesReturn->warehouse_id,
            'product_unit_id' => $item->product_unit_id,

            /*
                نستخدم stock_adjustment لأنه موجود عندك ومقبول.
                والتمييز سيكون من reference_type و notes.
            */
            'transaction_type' => 'stock_adjustment',

            'quantity' => $returnQuantity,
            'unit_cost' => $returnCost,
            'total_cost' => $item->total_cost,

            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,

            'reference_type' => SalesReturn::class,
            'reference_id' => $salesReturn->id,

            'notes' => 'مردود مبيعات رقم ' . $salesReturn->return_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | decreaseStockAfterCancel
    |--------------------------------------------------------------------------
    | إنقاص المخزون عند إلغاء مردود المبيعات.
    */
    private function decreaseStockAfterCancel(SalesReturn $salesReturn, SalesReturnItem $item): void
    {
        $stock = ProductStock::where('product_id', $item->product_id)
            ->where('warehouse_id', $salesReturn->warehouse_id)
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            throw new Exception('لا يوجد رصيد مخزون للصنف عند إلغاء المردود.');
        }

        $balanceBefore = (float) $stock->quantity;
        $quantity = (float) $item->base_quantity;

        if ($balanceBefore < $quantity) {
            throw new Exception('لا يمكن إلغاء المردود لأن رصيد المخزون الحالي أقل من كمية المردود.');
        }

        $balanceAfter = round($balanceBefore - $quantity, 3);

        $stock->update([
            'quantity' => $balanceAfter,
        ]);

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo('SR-CAN'),
            'product_id' => $item->product_id,
            'warehouse_id' => $salesReturn->warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'stock_adjustment',

            'quantity' => -1 * $quantity,
            'unit_cost' => $item->unit_cost,
            'total_cost' => -1 * (float) $item->total_cost,

            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,

            'reference_type' => SalesReturn::class,
            'reference_id' => $salesReturn->id,

            'notes' => 'إلغاء مردود مبيعات رقم ' . $salesReturn->return_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | applyReturnToInvoice
    |--------------------------------------------------------------------------
    | تطبيق أثر المردود على فاتورة البيع الأصلية.
    */
    private function applyReturnToInvoice(SalesInvoice $invoice, SalesReturn $salesReturn): void
    {
        $newReturnedAmount = round(
            (float) $invoice->returned_amount + (float) $salesReturn->total_amount,
            2
        );

        $netInvoiceAmount = round(
            app(SalesDocumentBalanceService::class)->netAmount($invoice, $salesReturn->id) - (float) $salesReturn->applied_amount,
            2
        );

        if ($netInvoiceAmount < 0) {
            $netInvoiceAmount = 0;
        }

        $paidAmount = (float) $invoice->paid_amount;

        $newRemaining = round($netInvoiceAmount - $paidAmount, 2);

        if ($newRemaining < 0) {
            $newRemaining = 0;
        }

        $invoice->update([
            'returned_amount' => $newReturnedAmount,
            'remaining_amount' => $newRemaining,
            'payment_status' => $this->paymentStatus($paidAmount, $netInvoiceAmount),
            'return_status' => $this->returnStatus($newReturnedAmount, (float) $invoice->total_amount),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | removeReturnFromInvoice
    |--------------------------------------------------------------------------
    | إزالة أثر المردود من فاتورة البيع عند إلغاء المردود.
    */
    private function removeReturnFromInvoice(SalesInvoice $invoice, SalesReturn $salesReturn): void
    {
        $newReturnedAmount = round(
            (float) $invoice->returned_amount - (float) $salesReturn->total_amount,
            2
        );

        if ($newReturnedAmount < 0) {
            $newReturnedAmount = 0;
        }

        $netInvoiceAmount = round(
            app(SalesDocumentBalanceService::class)->netAmount($invoice, $salesReturn->id),
            2
        );

        if ($netInvoiceAmount < 0) {
            $netInvoiceAmount = 0;
        }

        $paidAmount = (float) $invoice->paid_amount;

        $newRemaining = round($netInvoiceAmount - $paidAmount, 2);

        if ($newRemaining < 0) {
            $newRemaining = 0;
        }

        $invoice->update([
            'returned_amount' => $newReturnedAmount,
            'remaining_amount' => $newRemaining,
            'payment_status' => $this->paymentStatus($paidAmount, $netInvoiceAmount),
            'return_status' => $this->returnStatus($newReturnedAmount, (float) $invoice->total_amount),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | createJournalEntries
    |--------------------------------------------------------------------------
    | قيود مردود المبيعات:
    |
    | 1) عكس الإيراد والضريبة:
    | من حـ / المبيعات
    | من حـ / ضريبة المخرجات
    |     إلى حـ / العملاء
    |
    | 2) عكس تكلفة البضاعة:
    | من حـ / المخزون
    |     إلى حـ / تكلفة البضاعة المباعة
    */
    private function createJournalEntries(SalesReturn $salesReturn): void
    {
        $receivableAccount = $this->accountId('accounts_receivable');
        $salesAccount = $this->accountId('sales_account');
        $vatOutputAccount = $this->accountId('vat_output_account');
        $inventoryAccount = $this->accountId('inventory_account');
        $cogsAccount = $this->accountId('cost_of_goods_sold');

        /*
            قيد عكس الإيراد والضريبة.
        */
        $lines = [];

        $lines[] = [
            'account_id' => $salesAccount,
            'debit' => $salesReturn->subtotal,
            'credit' => 0,
            'description' => 'مردود مبيعات',
            'customer_id' => $salesReturn->customer_id,
            'branch_id' => $salesReturn->branch_id,
            'cost_center_id' => $salesReturn->cost_center_id,
        ];

        if ((float) $salesReturn->vat_amount > 0) {
            $lines[] = [
                'account_id' => $vatOutputAccount,
                'debit' => $salesReturn->vat_amount,
                'credit' => 0,
                'description' => 'عكس ضريبة مخرجات مردود المبيعات',
                'customer_id' => $salesReturn->customer_id,
                'branch_id' => $salesReturn->branch_id,
                'cost_center_id' => $salesReturn->cost_center_id,
            ];
        }

        $lines[] = [
            'account_id' => $receivableAccount,
            'debit' => 0,
            'credit' => $salesReturn->total_amount,
            'description' => 'تخفيض مديونية العميل بسبب مردود مبيعات',
            'customer_id' => $salesReturn->customer_id,
            'branch_id' => $salesReturn->branch_id,
            'cost_center_id' => $salesReturn->cost_center_id,
        ];

        $this->journalEntryService->create([
            'entry_date' => $salesReturn->return_date,
            'document_type' => 'sales_return',
            'document_number' => $salesReturn->return_no,

            'reference_type' => SalesReturn::class,
            'reference_id' => $salesReturn->id,

            'description' => 'مردود مبيعات رقم ' . $salesReturn->return_no,

            'lines' => $lines,
        ]);

        /*
            قيد عكس تكلفة البضاعة المباعة.
        */
        if ((float) $salesReturn->total_cost > 0) {
            $this->journalEntryService->create([
                'entry_date' => $salesReturn->return_date,
                'document_type' => 'sales_return_cost',
                'document_number' => $salesReturn->return_no,

                'reference_type' => SalesReturn::class,
                'reference_id' => $salesReturn->id,

                'description' => 'عكس تكلفة مردود مبيعات رقم ' . $salesReturn->return_no,

                'lines' => [
                    [
                        'account_id' => $inventoryAccount,
                        'debit' => $salesReturn->total_cost,
                        'credit' => 0,
                        'description' => 'إرجاع البضاعة للمخزون',
                        'customer_id' => $salesReturn->customer_id,
                        'branch_id' => $salesReturn->branch_id,
                        'cost_center_id' => $salesReturn->cost_center_id,
                    ],
                    [
                        'account_id' => $cogsAccount,
                        'debit' => 0,
                        'credit' => $salesReturn->total_cost,
                        'description' => 'عكس تكلفة البضاعة المباعة',
                        'customer_id' => $salesReturn->customer_id,
                        'branch_id' => $salesReturn->branch_id,
                        'cost_center_id' => $salesReturn->cost_center_id,
                    ],
                ],
            ]);
        }
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


    private function paymentStatus(float $paidAmount, float $netInvoiceAmount): string
    {
        if ($netInvoiceAmount <= 0) {
            return 'paid';
        }

        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $netInvoiceAmount) {
            return 'paid';
        }

        return 'partial';
    }


    private function returnStatus(float $returnedAmount, float $invoiceTotal): string
    {
        if ($returnedAmount <= 0) {
            return 'none';
        }

        if ($returnedAmount >= $invoiceTotal) {
            return 'full';
        }

        return 'partial';
    }


    private function generateReturnNo(): string
    {
        return 'SR-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }


    private function generateTransactionNo(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}