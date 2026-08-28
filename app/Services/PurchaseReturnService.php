<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\InventoryTransaction;
use App\Models\JournalEntry;
use App\Models\ProductStock;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Services\InventoryGuardService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReturnService
{
    /*
    |--------------------------------------------------------------------------
    | constructor
    |--------------------------------------------------------------------------
    | نحقن JournalEntryService لأن مردود المشتريات يحتاج قيد محاسبي.
    |
    | بدل ما نكتب كود القيود هنا من الصفر، نستخدم نفس السيرفس العام
    | الذي استخدمناه في فواتير المشتريات وسندات الصرف.
    */
    public function __construct(
        protected JournalEntryService $journalEntryService
    ) {}


    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    | هذه الدالة تحفظ مردود المشتريات.
    |
    | إذا save_action = draft:
    |   تحفظ المستند فقط بدون تأثير على المخزون أو الحسابات.
    |
    | إذا save_action = post:
    |   تحفظ المستند ثم تستدعي post() لترحيله.
    */
    public function store(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {

            /*
                نجلب الفاتورة الأصلية مع أصنافها.
                المردود لازم يكون مبني على فاتورة مشتريات موجودة.
            */
            $invoice = PurchaseInvoice::with('items')
                ->where('id', $data['purchase_invoice_id'])
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                throw ValidationException::withMessages([
                    'purchase_invoice_id' => 'فاتورة المشتريات غير موجودة.',
                ]);
            }

            /*
                لا نسمح بمردود على فاتورة غير مرحلة.
                لأن المسودة لم تدخل المخزون ولا الحسابات أصلاً.
            */
            if ($invoice->status !== 'posted') {
                throw ValidationException::withMessages([
                    'purchase_invoice_id' => 'لا يمكن عمل مردود إلا لفاتورة مشتريات مرحلة.',
                ]);
            }

            /*
                لا نسمح بمردود على فاتورة ملغاة.
            */
            if ($invoice->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'purchase_invoice_id' => 'لا يمكن عمل مردود على فاتورة ملغاة.',
                ]);
            }

            /*
                ننظف الأصناف:
                نأخذ فقط السطور التي فيها كمية أكبر من صفر.
            */
            $items = collect($data['items'])
                ->filter(fn ($row) => (float) ($row['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'يجب إدخال صنف واحد على الأقل في مردود المشتريات.',
                ]);
            }

            /*
                نتحقق أن الكميات المطلوبة للإرجاع لا تتجاوز الكمية المتاحة.
                مثال:
                اشتريت 20
                رجعت سابقاً 5
                المتاح للإرجاع الآن = 15
            */
            $this->validateReturnQuantities($invoice, $items);

            /*
                نحسب الأصناف بناءً على بيانات سطر الفاتورة الأصلي.
                لا نسمح للمستخدم يغير السعر أو الضريبة في المردود.
                لأن المردود يجب أن يرجع بنفس تكلفة الفاتورة الأصلية.
            */
            $calculatedItems = $items->map(function ($row) use ($invoice) {
                return $this->calculateReturnItem($invoice, $row);
            });

            /*
                subtotal:
                قيمة المردود قبل الضريبة.

                vat_amount:
                ضريبة المردود.

                total_amount:
                إجمالي المردود شامل الضريبة.
            */
            $subtotal = round($calculatedItems->sum('net_before_vat'), 2);
            $vatAmount = round($calculatedItems->sum('vat_amount'), 2);
            $totalAmount = round($calculatedItems->sum('line_total'), 2);

            /*
                نحفظ رأس المردود كمسودة دائماً.
                لو المستخدم اختار حفظ وترحيل، سنرحله بعد حفظ الأصناف.
            */
            $purchaseReturn = PurchaseReturn::create([
                'return_no' => $data['return_no'] ?? $this->generateReturnNo(),
                'purchase_invoice_id' => $invoice->id,
                'supplier_id' => $invoice->supplier_id,
                'branch_id' => $invoice->branch_id,
                'cost_center_id' => $invoice->cost_center_id,
                'warehouse_id' => $invoice->warehouse_id,
                'return_date' => $data['return_date'],
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            /*
                نحفظ تفاصيل الأصناف المرتجعة.
            */
            foreach ($calculatedItems as $item) {
                $purchaseReturn->items()->create([
                    'purchase_invoice_item_id' => $item['purchase_invoice_item_id'],
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['product_unit_id'],
                    'quantity' => $item['quantity'],
                    'base_quantity' => $item['base_quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'discount_amount' => $item['discount_amount'],
                    'vat_rate' => $item['vat_rate'],
                    'vat_amount' => $item['vat_amount'],
                    'line_total' => $item['line_total'],
                ]);
            }

            /*
                لو المستخدم ضغط حفظ وترحيل:
                نستدعي post()
                وهنا سيبدأ التأثير الحقيقي:
                - نقص المخزون
                - قيد محاسبي
                - تحديث الفاتورة الأصلية
            */
            if (($data['save_action'] ?? 'draft') === 'post') {
                $this->post($purchaseReturn);
            }

            return $purchaseReturn->fresh([
                'invoice',
                'supplier',
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
    | ترحيل مردود المشتريات.
    |
    | عند الترحيل يحدث الآتي:
    | 1. التأكد أن المستند مسودة
    | 2. التأكد أن الكميات لا تتجاوز المتاح
    | 3. إنقاص المخزون
    | 4. إنشاء حركة مخزون purchase_return
    | 5. إنشاء قيد محاسبي
    | 6. تحديث الفاتورة الأصلية
    | 7. تغيير حالة المردود إلى posted
    */
    public function post(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        return DB::transaction(function () use ($purchaseReturn) {

            $purchaseReturn->refresh();

            if ($purchaseReturn->status === 'posted') {
                throw ValidationException::withMessages([
                    'purchase_return' => 'مردود المشتريات مرحل بالفعل.',
                ]);
            }

            if ($purchaseReturn->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'purchase_return' => 'لا يمكن ترحيل مردود مشتريات ملغى.',
                ]);
            }

            /*
                نحمل العلاقات المطلوبة:
                invoice = الفاتورة الأصلية
                items   = أصناف المردود
            */
            $purchaseReturn->load(['invoice', 'items']);

            $invoice = $purchaseReturn->invoice;

            if (!$invoice || $invoice->status !== 'posted') {
                throw ValidationException::withMessages([
                    'purchase_invoice_id' => 'الفاتورة الأصلية غير صالحة للترحيل.',
                ]);
            }

            /*
                نتحقق مرة ثانية عند الترحيل.
                السبب:
                ممكن يكون المستخدم حفظ مسودة، ثم حصلت مردودات أخرى قبل أن يرحلها.
            */
            $this->validateReturnQuantities(
                $invoice,
                $purchaseReturn->items->map(function ($item) {
                    return [
                        'purchase_invoice_item_id' => $item->purchase_invoice_item_id,
                        'quantity' => $item->quantity,
                    ];
                })
            );

            app(InventoryGuardService::class)->assertDateAfterLastPostedInventoryCount(
                warehouseId: (int) $purchaseReturn->warehouse_id,
                documentDate: $purchaseReturn->return_date,
                documentName: 'مردود المشتريات'
            );
            
            /*
                كل صنف في المردود ينقص من المخزون.
            */
            foreach ($purchaseReturn->items as $item) {
                $this->decreaseStock($purchaseReturn, $item);
            }

            /*
                إنشاء القيد المحاسبي الخاص بالمردود.
            */
            $this->createJournalEntry($purchaseReturn);

            /*
                تحديث حالة الفاتورة الأصلية:
                returned_amount
                return_status
                remaining_amount
                payment_status
            */
            /*
            |--------------------------------------------------------------------------
            | أولاً: نغير حالة المردود إلى posted
            |--------------------------------------------------------------------------
            | مهم جداً:
            | دالة updateInvoiceReturnStatus تحسب فقط المردودات التي status = posted.
            | لذلك لازم نرحل المردود أولاً، ثم نعيد حساب الفاتورة الأصلية.
            */
            $purchaseReturn->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | ثانياً: نعيد حساب الفاتورة الأصلية
            |--------------------------------------------------------------------------
            | الآن المردود الحالي أصبح posted،
            | لذلك سيتم حسابه ضمن returned_amount.
            */
            $this->updateInvoiceReturnStatus($invoice);

            return $purchaseReturn->fresh([
                'invoice',
                'supplier',
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
    | إلغاء مردود المشتريات.
    |
    | إذا كان Draft:
    |   نغير الحالة إلى cancelled فقط.
    |
    | إذا كان Posted:
    |   نرجع المخزون
    |   نعكس القيد
    |   نعيد حساب حالة الفاتورة الأصلية
    */
    public function cancel(PurchaseReturn $purchaseReturn, ?string $reason = null): PurchaseReturn
    {
        return DB::transaction(function () use ($purchaseReturn, $reason) {

            $purchaseReturn->refresh();

            if ($purchaseReturn->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'purchase_return' => 'مردود المشتريات ملغى بالفعل.',
                ]);
            }

            /*
                إذا كان مسودة، لم يؤثر على المخزون أو الحسابات.
                لذلك نلغي الحالة فقط.
            */
            if ($purchaseReturn->status === 'draft') {
                $purchaseReturn->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancel_reason' => $reason,
                ]);

                return $purchaseReturn->fresh();
            }

            /*
                لو كان مرحل، لازم نعكس أثره.
            */
            $purchaseReturn->load(['invoice', 'items']);

            foreach ($purchaseReturn->items as $item) {
                $this->increaseStockAfterCancel($purchaseReturn, $item);
            }

            /*
                نبحث عن القيد المحاسبي الذي تم إنشاؤه عند ترحيل المردود.
            */
            $journalEntry = JournalEntry::where('reference_type', PurchaseReturn::class)
                ->where('reference_id', $purchaseReturn->id)
                ->where('status', 'posted')
                ->latest()
                ->first();

            /*
                إذا وجدنا القيد، ننشئ قيد عكسي.
            */
            if ($journalEntry) {
                $this->journalEntryService->reverse(
                    $journalEntry,
                    'عكس قيد إلغاء مردود مشتريات رقم ' . $purchaseReturn->return_no
                );
            }

            /*
                نغير حالة المردود إلى ملغى.
            */
            $purchaseReturn->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancel_reason' => $reason,
            ]);

            /*
                نعيد حساب الفاتورة الأصلية بعد إلغاء المردود.
            */
            if ($purchaseReturn->invoice) {
                $this->updateInvoiceReturnStatus($purchaseReturn->invoice);
            }

            return $purchaseReturn->fresh([
                'invoice',
                'supplier',
                'warehouse',
                'items.product',
                'items.productUnit.unit',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | validateReturnQuantities
    |--------------------------------------------------------------------------
    | هذه الدالة تمنع إرجاع كمية أكبر من المتاح.
    |
    | مثال:
    | الكمية الأصلية في الفاتورة = 20
    | مردودات مرحلة سابقة = 8
    | المتاح للإرجاع = 12
    |
    | إذا حاول المستخدم يرجع 15، نرفض.
    */
    private function validateReturnQuantities(PurchaseInvoice $invoice, $items): void
    {
        /*
            نجمع الكميات حسب سطر الفاتورة.
            لأن المستخدم قد يرسل نفس السطر أكثر من مرة.
        */
        $groupedItems = collect($items)
            ->groupBy('purchase_invoice_item_id')
            ->map(function ($rows) {
                return round($rows->sum(fn ($row) => (float) $row['quantity']), 3);
            });

        foreach ($groupedItems as $invoiceItemId => $requestedQuantity) {

            /*
                نجلب سطر الفاتورة الأصلي ونتأكد أنه تابع لنفس الفاتورة.
            */
            $invoiceItem = PurchaseInvoiceItem::where('id', $invoiceItemId)
                ->where('purchase_invoice_id', $invoice->id)
                ->first();

            if (!$invoiceItem) {
                throw ValidationException::withMessages([
                    'items' => 'يوجد صنف لا يتبع فاتورة المشتريات المختارة.',
                ]);
            }

            /*
                نحسب الكميات التي تم إرجاعها سابقاً في مردودات مرحلة فقط.
                المسودات لا نحسبها لأنها لم تؤثر بعد.
            */
            $alreadyReturnedQuantity = $invoiceItem->returnItems()
                ->whereHas('purchaseReturn', function ($query) {
                    $query->where('status', 'posted');
                })
                ->sum('quantity');

            $availableQuantity = round(
                (float) $invoiceItem->quantity - (float) $alreadyReturnedQuantity,
                3
            );

            if ($requestedQuantity > $availableQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'الكمية المرتجعة للصنف أكبر من الكمية المتاحة للإرجاع. المتاح: ' . $availableQuantity,
                ]);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | calculateReturnItem
    |--------------------------------------------------------------------------
    | هذه الدالة تحسب سطر المردود بناءً على سطر الفاتورة الأصلي.
    |
    | لا نعتمد على سعر قادم من الفورم.
    | السبب:
    | مردود المشتريات يجب أن يرجع بنفس تكلفة الشراء الأصلية.
    */
    private function calculateReturnItem(PurchaseInvoice $invoice, array $row): array
    {
        $invoiceItem = PurchaseInvoiceItem::where('id', $row['purchase_invoice_item_id'])
            ->where('purchase_invoice_id', $invoice->id)
            ->first();

        if (!$invoiceItem) {
            throw ValidationException::withMessages([
                'items' => 'سطر الفاتورة غير صحيح.',
            ]);
        }

        $quantity = round((float) $row['quantity'], 3);

        /*
            نحسب نسبة التحويل من كمية الفاتورة إلى كمية الأساس.
            مثال:
            سطر الفاتورة:
            quantity = 2 كرتون
            base_quantity = 20 حبة
            إذن كل كرتون = 10 حبات
        */
        $baseFactor = 1;

        if ((float) $invoiceItem->quantity > 0) {
            $baseFactor = (float) $invoiceItem->base_quantity / (float) $invoiceItem->quantity;
        }

        $baseQuantity = round($quantity * $baseFactor, 3);

        /*
            نحسب الخصم النسبي للمردود.
            مثال:
            اشتريت 10 قطع، خصم السطر 20 ريال.
            رجعت 5 قطع.
            خصم المردود = 10 ريال.
        */
        $discountPerUnit = 0;

        if ((float) $invoiceItem->quantity > 0) {
            $discountPerUnit = (float) $invoiceItem->discount_amount / (float) $invoiceItem->quantity;
        }

        $discountAmount = round($discountPerUnit * $quantity, 2);

        /*
            قيمة السطر قبل الضريبة:
            الكمية × تكلفة الوحدة - الخصم النسبي
        */
        $netBeforeVat = round(
            ($quantity * (float) $invoiceItem->unit_cost) - $discountAmount,
            2
        );

        $vatRate = (float) $invoiceItem->vat_rate;

        $vatAmount = round($netBeforeVat * $vatRate / 100, 2);

        $lineTotal = round($netBeforeVat + $vatAmount, 2);

        return [
            'purchase_invoice_item_id' => $invoiceItem->id,
            'product_id' => $invoiceItem->product_id,
            'product_unit_id' => $invoiceItem->product_unit_id,
            'quantity' => $quantity,
            'base_quantity' => $baseQuantity,
            'unit_cost' => $invoiceItem->unit_cost,
            'discount_amount' => $discountAmount,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'line_total' => $lineTotal,

            /*
                هذا الحقل لا نحفظه في الجدول.
                نستخدمه فقط لحساب subtotal والقيد.
            */
            'net_before_vat' => $netBeforeVat,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | decreaseStock
    |--------------------------------------------------------------------------
    | عند ترحيل مردود المشتريات:
    | البضاعة تخرج من مخزوننا وترجع للمورد.
    |
    | لذلك:
    | quantity في product_stocks ينقص.
    */
    private function decreaseStock(PurchaseReturn $purchaseReturn, $item): void
    {
        $stock = ProductStock::where('product_id', $item->product_id)
            ->where('warehouse_id', $purchaseReturn->warehouse_id)
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            throw ValidationException::withMessages([
                'stock' => 'لا يوجد رصيد مخزون لهذا الصنف.',
            ]);
        }

        $balanceBefore = (float) $stock->quantity;
        $baseQuantity = (float) $item->base_quantity;

        if ($balanceBefore < $baseQuantity) {
            throw ValidationException::withMessages([
                'stock' => 'لا يمكن ترحيل المردود لأن رصيد المخزون أقل من الكمية المرتجعة.',
            ]);
        }

        $balanceAfter = round($balanceBefore - $baseQuantity, 3);

        $stock->update([
            'quantity' => $balanceAfter,
        ]);

        /*
            تكلفة السطر بدون ضريبة.
            لأن المخزون لا يدخل فيه VAT إذا كانت الضريبة قابلة للاسترداد.
        */
        $netBeforeVat = round(
            ((float) $item->quantity * (float) $item->unit_cost) - (float) $item->discount_amount,
            2
        );

        /*
            نسجل حركة مخزون.
            الكمية سالبة لأن البضاعة خرجت من المخزون.
        */
        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo(),
            'product_id' => $item->product_id,
            'warehouse_id' => $purchaseReturn->warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'purchase_return',
            'quantity' => -$baseQuantity,
            'unit_cost' => $baseQuantity > 0 ? round($netBeforeVat / $baseQuantity, 2) : 0,
            'total_cost' => -$netBeforeVat,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => PurchaseReturn::class,
            'reference_id' => $purchaseReturn->id,
            'notes' => 'ترحيل مردود مشتريات رقم ' . $purchaseReturn->return_no,
            'created_by' => auth()->id(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | increaseStockAfterCancel
    |--------------------------------------------------------------------------
    | عند إلغاء مردود مشتريات مرحل:
    | نرجع الكمية للمخزون لأنها كانت خرجت عند الترحيل.
    */
    private function increaseStockAfterCancel(PurchaseReturn $purchaseReturn, $item): void
    {
        $stock = ProductStock::where('product_id', $item->product_id)
            ->where('warehouse_id', $purchaseReturn->warehouse_id)
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            throw ValidationException::withMessages([
                'stock' => 'لا يوجد رصيد مخزون لهذا الصنف.',
            ]);
        }

        $balanceBefore = (float) $stock->quantity;
        $baseQuantity = (float) $item->base_quantity;
        $balanceAfter = round($balanceBefore + $baseQuantity, 3);

        $stock->update([
            'quantity' => $balanceAfter,
        ]);

        $netBeforeVat = round(
            ((float) $item->quantity * (float) $item->unit_cost) - (float) $item->discount_amount,
            2
        );

        /*
            نسجل حركة عكسية.
            استخدمنا stock_adjustment لأن هذا ليس شراء جديد،
            بل عكس لإلغاء مردود مشتريات.
        */
        InventoryTransaction::create([
            'transaction_no' => $this->generateCancelTransactionNo(),
            'product_id' => $item->product_id,
            'warehouse_id' => $purchaseReturn->warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'stock_adjustment',
            'quantity' => $baseQuantity,
            'unit_cost' => $baseQuantity > 0 ? round($netBeforeVat / $baseQuantity, 2) : 0,
            'total_cost' => $netBeforeVat,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => PurchaseReturn::class,
            'reference_id' => $purchaseReturn->id,
            'notes' => 'إلغاء مردود مشتريات رقم ' . $purchaseReturn->return_no,
            'created_by' => auth()->id(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | createJournalEntry
    |--------------------------------------------------------------------------
    | القيد المحاسبي لمردود المشتريات.
    |
    | عند الشراء الأصلي كان القيد:
    | من حـ / المخزون
    | من حـ / ضريبة المدخلات
    |     إلى حـ / الموردين
    |
    | عند مردود المشتريات يكون العكس الجزئي:
    | من حـ / الموردين
    |     إلى حـ / المخزون
    |     إلى حـ / ضريبة المدخلات
    */
    private function createJournalEntry(PurchaseReturn $purchaseReturn): void
    {
        $accountsPayableId = AccountSetting::getAccountId('accounts_payable');
        $inventoryAccountId = AccountSetting::getAccountId('inventory_account');
        $vatInputAccountId = AccountSetting::getAccountId('vat_input_account');

        $lines = [];

        /*
            الموردين مدين:
            لأن المبلغ المستحق للمورد يقل.
        */
        $lines[] = [
            'account_id' => $accountsPayableId,
            'supplier_id' => $purchaseReturn->supplier_id,
            'branch_id' => $purchaseReturn->branch_id,
            'cost_center_id' => $purchaseReturn->cost_center_id,
            'description' => 'مردود مشتريات رقم ' . $purchaseReturn->return_no,
            'debit' => $purchaseReturn->total_amount,
            'credit' => 0,
        ];

        /*
            المخزون دائن:
            لأن المخزون نقص.
        */
        $lines[] = [
            'account_id' => $inventoryAccountId,
            'supplier_id' => $purchaseReturn->supplier_id,
            'branch_id' => $purchaseReturn->branch_id,
            'cost_center_id' => $purchaseReturn->cost_center_id,
            'description' => 'نقص مخزون بسبب مردود مشتريات رقم ' . $purchaseReturn->return_no,
            'debit' => 0,
            'credit' => $purchaseReturn->subtotal,
        ];

        /*
            ضريبة المدخلات دائن:
            لأن الضريبة القابلة للاسترداد تقل مع مردود المشتريات.
        */
        if ((float) $purchaseReturn->vat_amount > 0) {
            $lines[] = [
                'account_id' => $vatInputAccountId,
                'supplier_id' => $purchaseReturn->supplier_id,
                'branch_id' => $purchaseReturn->branch_id,
                'cost_center_id' => $purchaseReturn->cost_center_id,
                'description' => 'عكس ضريبة مدخلات لمردود مشتريات رقم ' . $purchaseReturn->return_no,
                'debit' => 0,
                'credit' => $purchaseReturn->vat_amount,
            ];
        }

        $this->journalEntryService->create([
            'entry_date' => $purchaseReturn->return_date,
            'reference_type' => PurchaseReturn::class,
            'reference_id' => $purchaseReturn->id,
            'description' => 'قيد مردود مشتريات رقم ' . $purchaseReturn->return_no,
            'lines' => $lines,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | updateInvoiceReturnStatus
    |--------------------------------------------------------------------------
    | بعد ترحيل أو إلغاء مردود مشتريات، لازم نعيد حساب حالة الفاتورة الأصلية.
    |
    | نحسب:
    | returned_amount
    | return_status
    | remaining_amount
    | payment_status
    */
    private function updateInvoiceReturnStatus(PurchaseInvoice $invoice): void
    {
        $invoice->refresh();

        /*
            نجمع كل المردودات المرحلة فقط.
            المسودات والملغاة لا تؤثر.
        */
        $returnedAmount = PurchaseReturn::where('purchase_invoice_id', $invoice->id)
            ->where('status', 'posted')
            ->sum('total_amount');

        $returnedAmount = round((float) $returnedAmount, 2);

        /*
            صافي المبلغ المستحق بعد المردودات:
            إجمالي الفاتورة - إجمالي المردودات
        */
        $netPayable = round((float) $invoice->total_amount - $returnedAmount, 2);

        if ($netPayable < 0) {
            $netPayable = 0;
        }

        /*
            المتبقي الجديد:
            صافي المستحق - المدفوع.
        */
        $paidAmount = round((float) $invoice->paid_amount, 2);

        $remainingAmount = round($netPayable - $paidAmount, 2);

        if ($remainingAmount < 0) {
            $remainingAmount = 0;
        }

        /*
            حالة الدفع.
        */
        $paymentStatus = 'unpaid';

        if ($paidAmount > 0 && $remainingAmount > 0) {
            $paymentStatus = 'partial';
        }

        if ($remainingAmount == 0) {
            $paymentStatus = 'paid';
        }

        /*
            حالة المردود.
        */
        $returnStatus = 'none';

        if ($returnedAmount > 0 && $returnedAmount < (float) $invoice->total_amount) {
            $returnStatus = 'partial';
        }

        if ($returnedAmount >= (float) $invoice->total_amount) {
            $returnStatus = 'full';
        }

        $invoice->update([
            'returned_amount' => $returnedAmount,
            'return_status' => $returnStatus,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $paymentStatus,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | generateReturnNo
    |--------------------------------------------------------------------------
    | توليد رقم تلقائي لمردود المشتريات.
    */
    private function generateReturnNo(): string
    {
        return 'PR-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }


    /*
    |--------------------------------------------------------------------------
    | generateTransactionNo
    |--------------------------------------------------------------------------
    | توليد رقم حركة مخزون للمردود.
    */
    private function generateTransactionNo(): string
    {
        return 'PR-TRX-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }


    /*
    |--------------------------------------------------------------------------
    | generateCancelTransactionNo
    |--------------------------------------------------------------------------
    | توليد رقم حركة مخزون عند إلغاء المردود.
    */
    private function generateCancelTransactionNo(): string
    {
        return 'PR-CAN-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}