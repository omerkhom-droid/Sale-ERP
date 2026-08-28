<?php

namespace App\Services\POS;

use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosOrderPayment;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Services\SalesInvoiceService;
use Exception;
use Illuminate\Support\Facades\DB;

class PosOrderService
{
    public function __construct(
        private readonly SalesInvoiceService $salesInvoiceService,
        private readonly PosShiftService $posShiftService
    ) {
    }

    public function checkout(array $data): PosOrder
    {
        return DB::transaction(function () use ($data) {

            $shift = $this->posShiftService->currentOpenShift();

            if (! $shift) {
                throw new Exception('لا توجد وردية مفتوحة.');
            }

            if ($shift->status !== 'open') {
                throw new Exception('الوردية الحالية مغلقة.');
            }

            $items = collect($data['items'] ?? [])
                ->filter(fn ($item) => (float) ($item['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw new Exception('يجب إضافة صنف واحد على الأقل.');
            }

            $paymentMethod = $data['payment_method'] ?? 'cash';

            if (! in_array($paymentMethod, ['cash', 'card', 'bank_transfer'], true)) {
                throw new Exception('طريقة الدفع غير صحيحة.');
            }

            $orderType = $data['order_type'] ?? 'takeaway';

            if (! in_array($orderType, ['dine_in', 'takeaway', 'delivery'], true)) {
                throw new Exception('نوع الطلب غير صحيح.');
            }

            $subtotal = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $productUnitId = (int) $item['product_unit_id'];
                $quantity = round((float) $item['quantity'], 4);

                $product = Product::query()
                    ->whereKey($productId)
                    ->where('is_active', true)
                    ->where('show_in_pos', true)
                    ->first();

                if (! $product) {
                    throw new Exception('يوجد صنف غير متاح في شاشة POS.');
                }

                $productUnit = ProductUnit::query()
                    ->with('unit')
                    ->whereKey($productUnitId)
                    ->where('product_id', $productId)
                    ->first();

                if (! $productUnit) {
                    throw new Exception('وحدة الصنف غير صحيحة.');
                }

                $unitPrice = round((float) ($item['unit_price'] ?? $productUnit->sale_price ?? 0), 2);

                if ($unitPrice < 0) {
                    throw new Exception('سعر البيع غير صحيح.');
                }

                $gross = round($quantity * $unitPrice, 2);
                $subtotal += $gross;

                $factor = (float) ($productUnit->factor ?? 1);

                if ($factor <= 0) {
                    $factor = 1;
                }

                $preparedItems[] = [
                    'product_id' => $productId,
                    'product_unit_id' => $productUnitId,
                    'quantity' => $quantity,
                    'base_quantity' => round($quantity * $factor, 4),
                    'unit_price' => $unitPrice,
                    'gross' => $gross,
                    'vat_rate' => 15,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $subtotal = round($subtotal, 2);
            $discountAmount = round((float) ($data['discount_amount'] ?? 0), 2);

            if ($discountAmount < 0) {
                $discountAmount = 0;
            }

            if ($discountAmount > $subtotal) {
                throw new Exception('الخصم لا يمكن أن يكون أكبر من إجمالي الطلب.');
            }

            $preparedItems = $this->distributeDiscount($preparedItems, $subtotal, $discountAmount);

            $netAmount = round($subtotal - $discountAmount, 2);
            $vatAmount = round($netAmount * 0.15, 2);
            $totalAmount = round($netAmount + $vatAmount, 2);

            $paidAmount = round((float) ($data['paid_amount'] ?? 0), 2);

            if ($paidAmount < $totalAmount) {
                throw new Exception('المبلغ المدفوع أقل من إجمالي الطلب.');
            }

            $changeAmount = round($paidAmount - $totalAmount, 2);

            $order = PosOrder::create([
                'order_no' => $this->generateOrderNo(),
                'pos_shift_id' => $shift->id,
                'branch_id' => $shift->branch_id,
                'warehouse_id' => $shift->warehouse_id,
                'customer_id' => $data['customer_id'] ?? null,
                'sales_invoice_id' => null,
                'created_by' => auth()->id(),
                'cancelled_by' => null,
                'order_type' => $orderType,
                'table_no' => $data['table_no'] ?? null,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_status' => 'unpaid',
                'paid_at' => null,
                'cancelled_at' => null,
                'notes' => $data['notes'] ?? null,
                'cancel_reason' => null,
            ]);

            foreach ($preparedItems as $item) {
                PosOrderItem::create([
                    'pos_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['product_unit_id'],
                    'quantity' => $item['quantity'],
                    'base_quantity' => $item['base_quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'vat_rate' => $item['vat_rate'],
                    'vat_amount' => $item['vat_amount'],
                    'line_total' => $item['line_total'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $paymentAmountForShift = $totalAmount;

            PosOrderPayment::create([
                'pos_order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'amount' => $paymentAmountForShift,
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => null,
            ]);

            $salesInvoice = $this->salesInvoiceService->store([
                'customer_id' => $data['customer_id'] ?? null,
                'customer_type' => 'cash',
                'customer_name' => $data['customer_name'] ?? 'عميل نقدي',
                'customer_mobile' => $data['customer_mobile'] ?? null,
                'customer_tax_number' => $data['customer_tax_number'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,

                /*
                |--------------------------------------------------------------------------
                | POS بيع مدفوع بالكامل
                |--------------------------------------------------------------------------
                */
                'payment_type' => 'cash',
                'payment_method' => $paymentMethod,
                'paid_amount' => $totalAmount,

                'branch_id' => $shift->branch_id,
                'warehouse_id' => $shift->warehouse_id,
                'cost_center_id' => null,
                'invoice_date' => now()->toDateString(),

                'items' => collect($preparedItems)->map(function ($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'product_unit_id' => $item['product_unit_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_amount' => $item['discount_amount'],
                        'vat_rate' => $item['vat_rate'],
                    ];
                })->toArray(),

                'notes' => 'فاتورة من شاشة POS',
                'save_action' => 'post',
            ]);

            $order->update([
                'sales_invoice_id' => $salesInvoice->id,
                'status' => 'paid',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->posShiftService->refreshShiftTotals($shift);

            return $order->fresh([
                'shift',
                'items.product',
                'items.productUnit.unit',
                'payments',
                'salesInvoice',
            ]);
        });
    }

    private function distributeDiscount(array $items, float $subtotal, float $discountAmount): array
    {
        if ($discountAmount <= 0 || $subtotal <= 0) {
            return collect($items)->map(function ($item) {
                $net = round($item['gross'], 2);
                $vat = round($net * 0.15, 2);

                $item['discount_amount'] = 0;
                $item['vat_amount'] = $vat;
                $item['line_total'] = round($net + $vat, 2);

                return $item;
            })->toArray();
        }

        $remainingDiscount = $discountAmount;
        $lastIndex = count($items) - 1;

        foreach ($items as $index => $item) {
            if ($index === $lastIndex) {
                $lineDiscount = round($remainingDiscount, 2);
            } else {
                $ratio = $item['gross'] / $subtotal;
                $lineDiscount = round($discountAmount * $ratio, 2);
                $remainingDiscount = round($remainingDiscount - $lineDiscount, 2);
            }

            if ($lineDiscount > $item['gross']) {
                $lineDiscount = $item['gross'];
            }

            $net = round($item['gross'] - $lineDiscount, 2);
            $vat = round($net * 0.15, 2);

            $items[$index]['discount_amount'] = $lineDiscount;
            $items[$index]['vat_amount'] = $vat;
            $items[$index]['line_total'] = round($net + $vat, 2);
        }

        return $items;
    }


    public function cancelOrder(PosOrder $posOrder, string $reason): PosOrder
    {
        return DB::transaction(function () use ($posOrder, $reason) {

            $reason = trim($reason);

            if ($reason === '') {
                throw new Exception('سبب الإلغاء مطلوب.');
            }

            $order = PosOrder::query()
                ->with([
                    'shift',
                    'salesInvoice',
                    'items.product',
                    'items.productUnit.unit',
                    'payments',
                ])
                ->whereKey($posOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === 'cancelled') {
                throw new Exception('هذا الطلب ملغي مسبقًا.');
            }

            if (! $order->shift) {
                throw new Exception('الطلب غير مرتبط بورديّة.');
            }

            if ($order->shift->status !== 'open') {
                throw new Exception('لا يمكن إلغاء طلب داخل وردية مغلقة. استخدم مردود مبيعات بدلًا من الإلغاء.');
            }

            /*
            |--------------------------------------------------------------------------
            | إذا كان الطلب مرتبطًا بفاتورة مبيعات
            |--------------------------------------------------------------------------
            | SalesInvoiceService هو المسؤول عن:
            | - عكس المخزون
            | - عكس القيود
            | - تحديث حالة الفاتورة
            */
            if ($order->salesInvoice && $order->salesInvoice->status !== 'cancelled') {
                $this->salesInvoiceService->cancel(
                    invoice: $order->salesInvoice,
                    reason: 'إلغاء طلب POS رقم ' . $order->order_no . ' - ' . $reason
                );
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            $this->posShiftService->refreshShiftTotals($order->shift);

            return $order->fresh([
                'shift',
                'salesInvoice',
                'items.product',
                'items.productUnit.unit',
                'payments',
            ]);
        });
    }

    private function generateOrderNo(): string
    {
        return 'POS-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}