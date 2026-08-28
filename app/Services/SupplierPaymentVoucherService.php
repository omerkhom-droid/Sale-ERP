<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\SupplierPaymentVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierPaymentVoucherService
{
    public function __construct(
        protected JournalEntryService $journalEntryService
    ) {}

    public function store(array $data): SupplierPaymentVoucher
    {
        return DB::transaction(function () use ($data) {

            $amount = round((float) $data['amount'], 2);

            $allocations = collect($data['allocations'])
                ->filter(fn ($row) => (float) ($row['amount'] ?? 0) > 0)
                ->values();

            if ($allocations->isEmpty()) {
                throw ValidationException::withMessages([
                    'allocations' => 'يجب توزيع مبلغ السند على فاتورة واحدة على الأقل.',
                ]);
            }

            $allocatedAmount = round(
                $allocations->sum(fn ($row) => (float) $row['amount']),
                2
            );

            if ($allocatedAmount != $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'إجمالي التوزيع يجب أن يساوي مبلغ سند الصرف.',
                ]);
            }

            $voucher = SupplierPaymentVoucher::create([
                'voucher_no' => $data['voucher_no'] ?? $this->generateVoucherNo(),
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'voucher_date' => $data['voucher_date'],
                'payment_account_id' => $data['payment_account_id'],
                'amount' => $amount,
                'allocated_amount' => $allocatedAmount,
                'unallocated_amount' => 0,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($allocations as $allocation) {
                $voucher->allocations()->create([
                    'purchase_invoice_id' => $allocation['purchase_invoice_id'],
                    'amount' => round((float) $allocation['amount'], 2),
                ]);
            }

            if (($data['save_action'] ?? 'draft') === 'post') {
                $this->post($voucher);
            }

            return $voucher->load([
                'supplier',
                'paymentAccount',
                'allocations.purchaseInvoice',
            ]);
        });
    }

    public function post(SupplierPaymentVoucher $voucher): SupplierPaymentVoucher
    {
        return DB::transaction(function () use ($voucher) {

            $voucher->refresh();

            if ($voucher->status === 'posted') {
                throw ValidationException::withMessages([
                    'voucher' => 'سند الصرف مرحل بالفعل.',
                ]);
            }

            if ($voucher->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'voucher' => 'لا يمكن ترحيل سند صرف ملغى.',
                ]);
            }

            $voucher->load('allocations');

            foreach ($voucher->allocations as $allocation) {

                $invoice = PurchaseInvoice::where('id', $allocation->purchase_invoice_id)
                    ->where('supplier_id', $voucher->supplier_id)
                    ->where('status', 'posted')
                    ->lockForUpdate()
                    ->first();

                if (!$invoice) {
                    throw ValidationException::withMessages([
                        'invoice' => 'يوجد فاتورة غير صحيحة أو غير مرحلة ضمن التوزيع.',
                    ]);
                }

                $allocationAmount = round((float) $allocation->amount, 2);

                if ($allocationAmount > (float) $invoice->remaining_amount) {
                    throw ValidationException::withMessages([
                        'allocation' => 'مبلغ السداد أكبر من المتبقي في الفاتورة رقم ' . $invoice->invoice_no,
                    ]);
                }

                $paidAmount = round((float) $invoice->paid_amount + $allocationAmount, 2);
                $remainingAmount = round((float) $invoice->total_amount - $paidAmount, 2);

                $paymentStatus = 'unpaid';

                if ($paidAmount > 0 && $remainingAmount > 0) {
                    $paymentStatus = 'partial';
                }

                if ($paidAmount > 0 && $remainingAmount == 0) {
                    $paymentStatus = 'paid';
                }

                $invoice->update([
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'payment_status' => $paymentStatus,
                ]);
            }

            $this->createJournalEntry($voucher);

            $voucher->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            return $voucher->fresh([
                'supplier',
                'paymentAccount',
                'allocations.purchaseInvoice',
            ]);
        });
    }

    public function cancel(SupplierPaymentVoucher $voucher, ?string $reason = null): SupplierPaymentVoucher
    {
        return DB::transaction(function () use ($voucher, $reason) {

            $voucher->refresh();

            if ($voucher->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'voucher' => 'سند الصرف ملغى بالفعل.',
                ]);
            }

            if ($voucher->status === 'draft') {
                $voucher->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancel_reason' => $reason,
                ]);

                return $voucher->fresh();
            }

            $voucher->load('allocations');

            foreach ($voucher->allocations as $allocation) {

                $invoice = PurchaseInvoice::where('id', $allocation->purchase_invoice_id)
                    ->where('supplier_id', $voucher->supplier_id)
                    ->where('status', 'posted')
                    ->lockForUpdate()
                    ->first();

                if (!$invoice) {
                    throw ValidationException::withMessages([
                        'invoice' => 'لا يمكن إلغاء السند لأن إحدى الفواتير غير متاحة أو غير مرحلة.',
                    ]);
                }

                $allocationAmount = round((float) $allocation->amount, 2);

                $paidAmount = round((float) $invoice->paid_amount - $allocationAmount, 2);

                if ($paidAmount < 0) {
                    $paidAmount = 0;
                }

                $remainingAmount = round((float) $invoice->total_amount - $paidAmount, 2);

                $paymentStatus = 'unpaid';

                if ($paidAmount > 0 && $remainingAmount > 0) {
                    $paymentStatus = 'partial';
                }

                if ($paidAmount > 0 && $remainingAmount == 0) {
                    $paymentStatus = 'paid';
                }

                $invoice->update([
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'payment_status' => $paymentStatus,
                ]);
            }

            $journalEntry = JournalEntry::where('reference_type', SupplierPaymentVoucher::class)
                ->where('reference_id', $voucher->id)
                ->where('status', 'posted')
                ->latest()
                ->first();

            if ($journalEntry) {
                $this->journalEntryService->reverse(
                    $journalEntry,
                    'عكس قيد إلغاء سند صرف رقم ' . $voucher->voucher_no
                );
            }

            $voucher->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancel_reason' => $reason,
            ]);

            return $voucher->fresh([
                'supplier',
                'paymentAccount',
                'allocations.purchaseInvoice',
            ]);
        });
    }

    private function createJournalEntry(SupplierPaymentVoucher $voucher): void
    {
        $accountsPayableId = AccountSetting::getAccountId('accounts_payable');

        $this->journalEntryService->create([
            'entry_date' => $voucher->voucher_date,
            'reference_type' => SupplierPaymentVoucher::class,
            'reference_id' => $voucher->id,
            'description' => 'قيد سند صرف مورد رقم ' . $voucher->voucher_no,
            'lines' => [
                [
                    'account_id' => $accountsPayableId,
                    'supplier_id' => $voucher->supplier_id,
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                    'description' => 'سداد مورد بسند رقم ' . $voucher->voucher_no,
                    'debit' => $voucher->amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $voucher->payment_account_id,
                    'supplier_id' => $voucher->supplier_id,
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                    'description' => 'دفع من حساب سند رقم ' . $voucher->voucher_no,
                    'debit' => 0,
                    'credit' => $voucher->amount,
                ],
            ],
        ]);
    }

    private function generateVoucherNo(): string
    {
        return 'PV-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}