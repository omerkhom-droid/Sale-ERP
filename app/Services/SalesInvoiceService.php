<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\JournalEntry;

use Exception;
use Illuminate\Support\Facades\DB;

class SalesInvoiceService
{
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    | حفظ فاتورة بيع.
    |
    | draft = مسودة فقط، لا تؤثر على المخزون أو الحسابات.
    | post  = حفظ وترحيل مباشرة.
    */
    public function store(array $data): SalesInvoice
    {
        return DB::transaction(function () use ($data) {

            /*
                العميل قد يكون موجودًا أو فارغًا في حالة عميل نقدي.
            */
            $customer = null;

            if (!empty($data['customer_id'])) {
                $customer = Customer::findOrFail($data['customer_id']);
            }

            /*
                تجهيز بيانات العميل المطبوعة داخل الفاتورة.
            */
            $customerData = $this->prepareCustomerData($data, $customer);

            /*
                فلترة الأصناف الفارغة.
            */
            $items = collect($data['items'])
                ->filter(fn ($item) => (float) ($item['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw new Exception('يجب إضافة صنف واحد على الأقل.');
            }

            /*
                حساب أصناف الفاتورة.
            */
            $calculatedItems = $items->map(function ($item) use ($data) {
                return $this->calculateItem(
                    item: $item,
                    warehouseId: (int) $data['warehouse_id']
                );
            });

            /*
                إجماليات الفاتورة.
            */
            $subtotal = round($calculatedItems->sum('net_amount'), 2);
            $discountAmount = round($calculatedItems->sum('discount_amount'), 2);
            $vatAmount = round($calculatedItems->sum('vat_amount'), 2);
            $totalAmount = round($subtotal + $vatAmount, 2);

            /*
                حساب المدفوع والمتبقي حسب نوع الدفع.
            */
            $paymentType = $data['payment_type'];

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

            /*
                إنشاء رأس الفاتورة.
            */
            $invoice = SalesInvoice::create([
                // 'invoice_no' => $data['invoice_no'] ?? app(DocumentNumberService::class)->generate('sales_invoice'),
                'invoice_no' => $data['invoice_no'] ?? $this->generateInvoiceNo(),

                'customer_id' => $customer?->id,
                'customer_type' => $data['customer_type'] ?? 'cash',

                'customer_name' => $customerData['customer_name'],
                'customer_mobile' => $customerData['customer_mobile'],
                'customer_tax_number' => $customerData['customer_tax_number'],
                'customer_address' => $customerData['customer_address'],

                'payment_type' => $paymentType,
                'payment_method' => $paidAmount > 0
                    ? ($data['payment_method'] ?? 'cash')
                    : null,

                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'invoice_date' => $data['invoice_date'],

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
            ]);

            /*
                حفظ أصناف الفاتورة.
            */
            foreach ($calculatedItems as $item) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,

                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],

                    'product_unit_id' => $item['product_unit_id'],
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

            /*
                حفظ وترحيل مباشرة.
            */
            if (($data['save_action'] ?? 'draft') === 'post') {
                return $this->post($invoice);
            }

            return $invoice->fresh([
                'customer',
                'branch',
                'costCenter',
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
    | ترحيل فاتورة البيع.
    |
    | عند الترحيل:
    | - نتحقق من توفر الكمية
    | - نخصم المخزون
    | - نسجل حركة مخزون
    | - ننشئ قيد المبيعات والضريبة
    | - ننشئ قيد تكلفة البضاعة المباعة
    */
    public function post(SalesInvoice $invoice): SalesInvoice
    {
        return DB::transaction(function () use ($invoice) {

            $invoice = SalesInvoice::with([
                    'items.product',
                    'items.productUnit.unit',
                    'costCenter',
                ])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($invoice->status === 'posted') {
                throw new Exception('الفاتورة مرحلة مسبقاً.');
            }

            if ($invoice->status === 'cancelled') {
                throw new Exception('لا يمكن ترحيل فاتورة ملغاة.');
            }

            /*
                التأكد من توفر كل الأصناف قبل خصم أي كمية.
            */
            foreach ($invoice->items as $item) {
                $this->validateStockAvailable(
                    productId: (int) $item->product_id,
                    warehouseId: (int) $invoice->warehouse_id,
                    requiredQuantity: (float) $item->base_quantity
                );
            }

            /*
                خصم المخزون.
            */
            foreach ($invoice->items as $item) {
                $this->decreaseStock(
                    invoice: $invoice,
                    item: $item
                );
            }

            /*
                إنشاء القيود.
            */
            $this->createJournalEntries($invoice);

            /*
                تحديث حالة الفاتورة.
            */
            $invoice->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            return $invoice->fresh([
                'customer',
                'branch',
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
    | إلغاء فاتورة البيع.
    |
    | إذا كانت draft:
    | نغير الحالة فقط.
    |
    | إذا كانت posted:
    | نرجع المخزون ونعكس القيود.
    */
    public function cancel(SalesInvoice $invoice, ?string $reason = null): SalesInvoice
    {
        return DB::transaction(function () use ($invoice, $reason) {

            $invoice = SalesInvoice::with([
                    'items.product',
                    'items.productUnit.unit',
                ])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($invoice->status === 'cancelled') {
                throw new Exception('الفاتورة ملغاة مسبقاً.');
            }

            if ($invoice->status === 'draft') {
                $invoice->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancel_reason' => $reason,
                ]);

                return $invoice->fresh([
                    'customer',
                    'branch',
                    'warehouse',
                    'items.product',
                    'items.productUnit.unit',
                ]);
            }

            /*
                إرجاع المخزون إذا كانت الفاتورة مرحلة.
            */
            foreach ($invoice->items as $item) {
                $this->increaseStockAfterCancel(
                    invoice: $invoice,
                    item: $item
                );
            }

            /*
                عكس القيود.
            */
            /*
            |--------------------------------------------------------------------------
            | عكس القيود المحاسبية الخاصة بفاتورة البيع
            |--------------------------------------------------------------------------
            | JournalEntryService::reverse() عندك يستقبل JournalEntry Model
            | وليس reference_type / reference_id.
            */
            $journalEntries = JournalEntry::where('reference_type', SalesInvoice::class)
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
                'customer',
                'branch',
                'warehouse',
                'items.product',
                'items.productUnit.unit',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | prepareCustomerData
    |--------------------------------------------------------------------------
    | تجهيز بيانات العميل المطبوعة داخل الفاتورة.
    |
    | إذا اختار المستخدم عميل مسجل:
    | نأخذ البيانات من جدول customers.
    |
    | إذا كتب بيانات يدوية:
    | اليدوي له أولوية.
    */
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


    /*
    |--------------------------------------------------------------------------
    | calculateItem
    |--------------------------------------------------------------------------
    | حساب سطر فاتورة البيع.
    */
    private function calculateItem(array $item, int $warehouseId): array
    {
        $productId = (int) $item['product_id'];
        $productUnitId = (int) $item['product_unit_id'];

        $product = Product::findOrFail($productId);
        $productUnit = ProductUnit::with('unit')->findOrFail($productUnitId);

        $quantity = (float) $item['quantity'];
        $unitPrice = (float) $item['unit_price'];
        $discountAmount = (float) ($item['discount_amount'] ?? 0);
        $vatRate = (float) ($item['vat_rate'] ?? 15);

        /*
            تحويل الكمية إلى الوحدة الأساسية.
        */
        $baseQuantity = $this->getBaseQuantity(
            productUnit: $productUnit,
            quantity: $quantity
        );

        /*
            إجمالي السطر قبل الخصم والضريبة.
        */
        $grossAmount = round($quantity * $unitPrice, 2);

        if ($discountAmount > $grossAmount) {
            throw new Exception('خصم السطر لا يمكن أن يكون أكبر من قيمة السطر.');
        }

        /*
            الصافي قبل الضريبة.
        */
        $netAmount = round($grossAmount - $discountAmount, 2);

        /*
            الضريبة.
        */
        $vatAmount = round($netAmount * ($vatRate / 100), 2);

        /*
            الإجمالي شامل الضريبة.
        */
        $lineTotal = round($netAmount + $vatAmount, 2);

        /*
            تكلفة الصنف من متوسط تكلفة المخزون.
        */
        $stock = ProductStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        $unitCost = (float) ($stock?->average_cost ?? 0);

        $totalCost = round($baseQuantity * $unitCost, 2);

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

            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,

            'discount_amount' => $discountAmount,
            'net_amount' => $netAmount,

            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,

            'line_total' => $lineTotal,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | getBaseQuantity
    |--------------------------------------------------------------------------
    | تحويل الكمية للوحدة الأساسية حسب معامل التحويل.
    */
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


    /*
    |--------------------------------------------------------------------------
    | calculatePaidAmount
    |--------------------------------------------------------------------------
    | تحديد المدفوع حسب نوع الدفع.
    */
    private function calculatePaidAmount(
        string $paymentType,
        float $totalAmount,
        float $inputPaidAmount
    ): float {

        if ($paymentType === 'cash') {
            return $totalAmount;
        }

        if ($paymentType === 'credit') {
            return 0;
        }

        /*
            partial
        */
        if ($inputPaidAmount <= 0) {
            throw new Exception('في الفاتورة الجزئية يجب إدخال مبلغ مدفوع.');
        }

        if ($inputPaidAmount >= $totalAmount) {
            throw new Exception('في الفاتورة الجزئية يجب أن يكون المدفوع أقل من إجمالي الفاتورة.');
        }

        return round($inputPaidAmount, 2);
    }


    /*
    |--------------------------------------------------------------------------
    | validateStockAvailable
    |--------------------------------------------------------------------------
    | التحقق من توفر الكمية قبل الترحيل.
    */
    private function validateStockAvailable(
        int $productId,
        int $warehouseId,
        float $requiredQuantity
    ): void {

        $stock = ProductStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        $availableQuantity = (float) ($stock?->quantity ?? 0);

        if ($availableQuantity < $requiredQuantity) {
            throw new Exception(
                'الكمية غير متوفرة في المخزون. المتاح: '
                . number_format($availableQuantity, 3)
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | decreaseStock
    |--------------------------------------------------------------------------
    | خصم المخزون عند ترحيل فاتورة البيع.
    */
    private function decreaseStock(SalesInvoice $invoice, SalesInvoiceItem $item): void
    {
        $stock = ProductStock::where('product_id', $item->product_id)
            ->where('warehouse_id', $invoice->warehouse_id)
            ->lockForUpdate()
            ->firstOrFail();

        $balanceBefore = (float) $stock->quantity;
        $balanceAfter = round($balanceBefore - (float) $item->base_quantity, 3);

        if ($balanceAfter < 0) {
            throw new Exception('الرصيد لا يكفي لإتمام عملية البيع.');
        }

        /*
            عند البيع:
            الكمية تنقص.
            average_cost يبقى كما هو.
        */
        $stock->update([
            'quantity' => $balanceAfter,
        ]);

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo(),
            'product_id' => $item->product_id,
            'warehouse_id' => $invoice->warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'sale',
            'quantity' => -1 * (float) $item->base_quantity,
            'unit_cost' => (float) $item->unit_cost,
            'total_cost' => -1 * (float) $item->total_cost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => SalesInvoice::class,
            'reference_id' => $invoice->id,
            'notes' => 'فاتورة بيع رقم ' . $invoice->invoice_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | increaseStockAfterCancel
    |--------------------------------------------------------------------------
    | إرجاع المخزون عند إلغاء فاتورة بيع مرحلة.
    */
    private function increaseStockAfterCancel(SalesInvoice $invoice, SalesInvoiceItem $item): void
    {
        $stock = ProductStock::where('product_id', $item->product_id)
            ->where('warehouse_id', $invoice->warehouse_id)
            ->lockForUpdate()
            ->firstOrFail();

        $balanceBefore = (float) $stock->quantity;
        $balanceAfter = round($balanceBefore + (float) $item->base_quantity, 3);

        $stock->update([
            'quantity' => $balanceAfter,
        ]);

        InventoryTransaction::create([
            'transaction_no' => $this->generateCancelTransactionNo(),
            'product_id' => $item->product_id,
            'warehouse_id' => $invoice->warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'sale_return',
            'quantity' => (float) $item->base_quantity,
            'unit_cost' => (float) $item->unit_cost,
            'total_cost' => (float) $item->total_cost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => SalesInvoice::class,
            'reference_id' => $invoice->id,
            'notes' => 'إلغاء فاتورة بيع رقم ' . $invoice->invoice_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | createJournalEntries
    |--------------------------------------------------------------------------
    | إنشاء قيود فاتورة البيع.
    */
    private function createJournalEntries(SalesInvoice $invoice): void
    {
        $accountsReceivable = $this->accountId('accounts_receivable');
        $salesAccount = $this->accountId('sales_account');
        $vatOutputAccount = $this->accountId('vat_output_account');
        $cashOrBankAccount = $this->paymentAccountId($invoice->payment_method);

        $costOfGoodsSold = $this->accountId('cost_of_goods_sold');
        $inventoryAccount = $this->accountId('inventory_account');

        $paidAmount = (float) $invoice->paid_amount;
        $remainingAmount = (float) $invoice->remaining_amount;

        $lines = [];

        /*
            الجزء الآجل على العميل.
        */
        if ($remainingAmount > 0) {
            $lines[] = [
                'account_id' => $accountsReceivable,
                'debit' => $remainingAmount,
                'credit' => 0,
                'description' => 'ذمة عميل - فاتورة بيع رقم ' . $invoice->invoice_no,
                'customer_id' => $invoice->customer_id,
                'branch_id' => $invoice->branch_id,
                'cost_center_id' => $invoice->cost_center_id,
            ];
        }

        /*
            الجزء المدفوع نقداً / شبكة / تحويل.
        */
        if ($paidAmount > 0) {
            $lines[] = [
                'account_id' => $cashOrBankAccount,
                'debit' => $paidAmount,
                'credit' => 0,
                'description' => 'تحصيل مباشر - فاتورة بيع رقم ' . $invoice->invoice_no,
                'customer_id' => $invoice->customer_id,
                'branch_id' => $invoice->branch_id,
                'cost_center_id' => $invoice->cost_center_id,
            ];
        }

        /*
            المبيعات دائن.
        */
        $lines[] = [
            'account_id' => $salesAccount,
            'debit' => 0,
            'credit' => (float) $invoice->subtotal,
            'description' => 'مبيعات فاتورة رقم ' . $invoice->invoice_no,
            'customer_id' => $invoice->customer_id,
            'branch_id' => $invoice->branch_id,
            'cost_center_id' => $invoice->cost_center_id,
        ];

        /*
            ضريبة المخرجات دائن.
        */
        if ((float) $invoice->vat_amount > 0) {
            $lines[] = [
                'account_id' => $vatOutputAccount,
                'debit' => 0,
                'credit' => (float) $invoice->vat_amount,
                'description' => 'ضريبة مخرجات فاتورة بيع رقم ' . $invoice->invoice_no,
                'customer_id' => $invoice->customer_id,
                'branch_id' => $invoice->branch_id,
                'cost_center_id' => $invoice->cost_center_id,
            ];
        }

        /*
            قيد المبيعات والتحصيل.
        */
        $this->journalEntryService->create([
            'entry_date' => $invoice->invoice_date,
            'document_type' => 'sales_invoice',
            'document_number' => $invoice->invoice_no,
            'reference_type' => SalesInvoice::class,
            'reference_id' => $invoice->id,
            'description' => 'قيد فاتورة بيع رقم ' . $invoice->invoice_no,
            'created_by' => auth()->id(),
            'lines' => $lines,
        ]);

        /*
            قيد تكلفة البضاعة المباعة.
        */
        $totalCost = round($invoice->items->sum('total_cost'), 2);

        if ($totalCost <= 0) {
            return;
        }

        $this->journalEntryService->create([
            'entry_date' => $invoice->invoice_date,
            'document_type' => 'sales_invoice_cost',
            'document_number' => $invoice->invoice_no,
            'reference_type' => SalesInvoice::class,
            'reference_id' => $invoice->id,
            'description' => 'قيد تكلفة بضاعة مباعة لفاتورة رقم ' . $invoice->invoice_no,
            'created_by' => auth()->id(),
            'lines' => [
                [
                    'account_id' => $costOfGoodsSold,
                    'debit' => $totalCost,
                    'credit' => 0,
                    'description' => 'تكلفة البضاعة المباعة - فاتورة ' . $invoice->invoice_no,
                    'customer_id' => $invoice->customer_id,
                    'branch_id' => $invoice->branch_id,
                    'cost_center_id' => $invoice->cost_center_id,
                ],
                [
                    'account_id' => $inventoryAccount,
                    'debit' => 0,
                    'credit' => $totalCost,
                    'description' => 'خروج مخزون بسبب فاتورة بيع ' . $invoice->invoice_no,
                    'customer_id' => $invoice->customer_id,
                    'branch_id' => $invoice->branch_id,
                    'cost_center_id' => $invoice->cost_center_id,
                ],
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | paymentAccountId
    |--------------------------------------------------------------------------
    | تحديد حساب التحصيل حسب طريقة الدفع.
    */
    private function paymentAccountId(?string $paymentMethod): int
    {
        return match ($paymentMethod) {
            'bank_transfer', 'card' => $this->accountId('bank_account'),
            default => $this->accountId('cash_account'),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | accountId
    |--------------------------------------------------------------------------
    | جلب الحساب من account_settings.
    */
    private function accountId(string $key): int
    {
        $setting = AccountSetting::where('setting_key', $key)->first();

        if (!$setting || !$setting->account_id) {
            throw new Exception('يرجى ضبط الحساب المحاسبي: ' . $key);
        }

        return (int) $setting->account_id;
    }


    /*
    |--------------------------------------------------------------------------
    | getPaymentStatus
    |--------------------------------------------------------------------------
    | تحديد حالة السداد.
    */
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


    /*
    |--------------------------------------------------------------------------
    | modelValue
    |--------------------------------------------------------------------------
    | قراءة قيمة من موديل بعدة أسماء محتملة.
    */
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


    /*
    |--------------------------------------------------------------------------
    | generateInvoiceNo
    |--------------------------------------------------------------------------
    */
    private function generateInvoiceNo(): string
    {
        return 'SI-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }


    /*
    |--------------------------------------------------------------------------
    | generateTransactionNo
    |--------------------------------------------------------------------------
    */
    private function generateTransactionNo(): string
    {
        return 'SALE-TRX-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }


    /*
    |--------------------------------------------------------------------------
    | generateCancelTransactionNo
    |--------------------------------------------------------------------------
    */
    private function generateCancelTransactionNo(): string
    {
        return 'SALE-CAN-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}