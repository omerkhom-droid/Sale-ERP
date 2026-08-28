<?php

namespace App\Services\POS;

use App\Models\PosShift;
use Exception;
use Illuminate\Support\Facades\DB;

class PosShiftService
{
    public function currentOpenShift(?int $userId = null): ?PosShift
    {
        $userId = $userId ?: auth()->id();

        return PosShift::query()
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function openShift(array $data): PosShift
    {
        return DB::transaction(function () use ($data) {

            $userId = (int) auth()->id();

            $existingShift = $this->currentOpenShift($userId);

            if ($existingShift) {
                throw new Exception('يوجد وردية مفتوحة بالفعل لهذا المستخدم.');
            }

            $openingCash = round((float) ($data['opening_cash'] ?? 0), 2);

            if ($openingCash < 0) {
                throw new Exception('رصيد بداية الصندوق لا يمكن أن يكون أقل من صفر.');
            }

            return PosShift::create([
                'shift_no' => $this->generateShiftNo(),
                'branch_id' => (int) $data['branch_id'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'user_id' => $userId,
                'opened_at' => now(),

                'opening_cash' => $openingCash,
                'expected_cash' => $openingCash,
                'actual_cash' => 0,
                'cash_difference' => 0,

                'total_sales' => 0,
                'total_cash' => 0,
                'total_card' => 0,
                'total_bank_transfer' => 0,
                'total_discount' => 0,
                'total_vat' => 0,
                'total_cancelled' => 0,

                'status' => 'open',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function closeShift(PosShift $shift, array $data): PosShift
    {
        return DB::transaction(function () use ($shift, $data) {

            $shift = PosShift::query()
                ->with(['orders.payments'])
                ->whereKey($shift->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($shift->status !== 'open') {
                throw new Exception('هذه الوردية مغلقة بالفعل.');
            }

            /*
            |--------------------------------------------------------------------------
            | منع إغلاق وردية فيها طلبات غير مكتملة
            |--------------------------------------------------------------------------
            */
            $draftOrdersCount = $shift->orders()
                ->where('status', 'draft')
                ->count();

            if ($draftOrdersCount > 0) {
                throw new Exception('لا يمكن إغلاق الوردية لوجود طلبات غير مكتملة.');
            }

            /*
            |--------------------------------------------------------------------------
            | حساب نهائي للإجماليات قبل الإغلاق
            |--------------------------------------------------------------------------
            */
            $totals = $this->calculateShiftTotals($shift);

            $actualCash = round((float) ($data['actual_cash'] ?? 0), 2);

            if ($actualCash < 0) {
                throw new Exception('النقد الفعلي لا يمكن أن يكون أقل من صفر.');
            }

            $cashDifference = round($actualCash - $totals['expected_cash'], 2);

            $shift->update([
                'total_sales' => $totals['total_sales'],
                'total_cash' => $totals['total_cash'],
                'total_card' => $totals['total_card'],
                'total_bank_transfer' => $totals['total_bank_transfer'],
                'total_discount' => $totals['total_discount'],
                'total_vat' => $totals['total_vat'],
                'total_cancelled' => $totals['total_cancelled'],

                'expected_cash' => $totals['expected_cash'],
                'actual_cash' => $actualCash,
                'cash_difference' => $cashDifference,

                'closed_at' => now(),
                'status' => 'closed',
                'notes' => $data['notes'] ?? $shift->notes,
            ]);

            return $shift->fresh([
                'branch',
                'warehouse',
                'user',
                'orders.payments',
            ]);
        });
    }

    public function refreshShiftTotals(PosShift $shift): void
    {
        $shift = PosShift::query()
            ->with(['orders.payments'])
            ->whereKey($shift->id)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | لا نعيد حساب وردية مغلقة
        |--------------------------------------------------------------------------
        | بعد الإغلاق، تقرير الوردية يعتبر نهائي.
        */
        if ($shift->status === 'closed') {
            return;
        }

        $totals = $this->calculateShiftTotals($shift);

        $shift->update([
            'total_sales' => $totals['total_sales'],
            'total_cash' => $totals['total_cash'],
            'total_card' => $totals['total_card'],
            'total_bank_transfer' => $totals['total_bank_transfer'],
            'total_discount' => $totals['total_discount'],
            'total_vat' => $totals['total_vat'],
            'total_cancelled' => $totals['total_cancelled'],
            'expected_cash' => $totals['expected_cash'],
        ]);
    }

    private function calculateShiftTotals(PosShift $shift): array
    {
        $paidOrders = $shift->orders
            ->where('status', 'paid');

        $cancelledOrders = $shift->orders
            ->where('status', 'cancelled');

        $totalSales = round((float) $paidOrders->sum('total_amount'), 2);
        $totalDiscount = round((float) $paidOrders->sum('discount_amount'), 2);
        $totalVat = round((float) $paidOrders->sum('vat_amount'), 2);
        $totalCancelled = round((float) $cancelledOrders->sum('total_amount'), 2);

        $totalCash = 0;
        $totalCard = 0;
        $totalBankTransfer = 0;

        foreach ($paidOrders as $order) {
            foreach ($order->payments as $payment) {
                $amount = (float) $payment->amount;

                if ($payment->payment_method === 'cash') {
                    $totalCash += $amount;
                }

                if ($payment->payment_method === 'card') {
                    $totalCard += $amount;
                }

                if ($payment->payment_method === 'bank_transfer') {
                    $totalBankTransfer += $amount;
                }
            }
        }

        $totalCash = round($totalCash, 2);
        $totalCard = round($totalCard, 2);
        $totalBankTransfer = round($totalBankTransfer, 2);

        return [
            'total_sales' => $totalSales,
            'total_cash' => $totalCash,
            'total_card' => $totalCard,
            'total_bank_transfer' => $totalBankTransfer,
            'total_discount' => $totalDiscount,
            'total_vat' => $totalVat,
            'total_cancelled' => $totalCancelled,
            'expected_cash' => round((float) $shift->opening_cash + $totalCash, 2),
        ];
    }

    private function generateShiftNo(): string
    {
        return 'SHIFT-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}