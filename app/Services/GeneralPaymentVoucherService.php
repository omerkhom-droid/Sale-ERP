<?php

namespace App\Services;

use App\Models\GeneralPaymentVoucher;
use App\Models\JournalEntry;
use Exception;
use Illuminate\Support\Facades\DB;

class GeneralPaymentVoucherService
{
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {}

    public function store(array $data): GeneralPaymentVoucher
    {
        return DB::transaction(function () use ($data) {

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw new Exception('مبلغ سند الصرف يجب أن يكون أكبر من صفر.');
            }

            if ((int) $data['cash_bank_account_id'] === (int) $data['opposite_account_id']) {
                throw new Exception('لا يمكن أن يكون حساب الصرف والحساب المقابل نفس الحساب.');
            }

            $voucher = GeneralPaymentVoucher::create([
                'voucher_no' => $data['voucher_no'] ?? $this->generateVoucherNo(),

                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,

                'voucher_date' => $data['voucher_date'],
                'payment_method' => $data['payment_method'] ?? 'cash',

                'cash_bank_account_id' => $data['cash_bank_account_id'],
                'opposite_account_id' => $data['opposite_account_id'],

                'amount' => $amount,

                'payee_name' => $data['payee_name'] ?? null,
                'notes' => $data['notes'] ?? null,

                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            if (($data['save_action'] ?? 'draft') === 'post') {
                return $this->post($voucher);
            }

            return $voucher->fresh([
                'branch',
                'costCenter',
                'cashBankAccount',
                'oppositeAccount',
            ]);
        });
    }

    public function post(GeneralPaymentVoucher $voucher): GeneralPaymentVoucher
    {
        return DB::transaction(function () use ($voucher) {

            $voucher = GeneralPaymentVoucher::lockForUpdate()
                ->findOrFail($voucher->id);

            if ($voucher->status === 'posted') {
                throw new Exception('سند الصرف العام مرحل مسبقاً.');
            }

            if ($voucher->status === 'cancelled') {
                throw new Exception('لا يمكن ترحيل سند صرف عام ملغى.');
            }

            $this->createJournalEntry($voucher);

            $voucher->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            return $voucher->fresh([
                'branch',
                'costCenter',
                'cashBankAccount',
                'oppositeAccount',
            ]);
        });
    }

    public function cancel(GeneralPaymentVoucher $voucher, ?string $reason = null): GeneralPaymentVoucher
    {
        return DB::transaction(function () use ($voucher, $reason) {

            $voucher = GeneralPaymentVoucher::lockForUpdate()
                ->findOrFail($voucher->id);

            if ($voucher->status === 'cancelled') {
                throw new Exception('سند الصرف العام ملغى مسبقاً.');
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

            $journalEntries = JournalEntry::where('reference_type', GeneralPaymentVoucher::class)
                ->where('reference_id', $voucher->id)
                ->get();

            foreach ($journalEntries as $entry) {
                $this->journalEntryService->reverse($entry);
            }

            $voucher->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancel_reason' => $reason,
            ]);

            return $voucher->fresh([
                'branch',
                'costCenter',
                'cashBankAccount',
                'oppositeAccount',
            ]);
        });
    }

    private function createJournalEntry(GeneralPaymentVoucher $voucher): void
    {
        $this->journalEntryService->create([
            'entry_date' => $voucher->voucher_date,
            'document_type' => 'general_payment_voucher',
            'document_number' => $voucher->voucher_no,

            'reference_type' => GeneralPaymentVoucher::class,
            'reference_id' => $voucher->id,

            'description' => 'قيد سند صرف عام رقم ' . $voucher->voucher_no,
            'created_by' => auth()->id(),

            'lines' => [
                [
                    'account_id' => $voucher->opposite_account_id,
                    'debit' => $voucher->amount,
                    'credit' => 0,
                    'description' => 'الحساب المقابل لسند صرف عام رقم ' . $voucher->voucher_no,
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                ],
                [
                    'account_id' => $voucher->cash_bank_account_id,
                    'debit' => 0,
                    'credit' => $voucher->amount,
                    'description' => 'صرف عام' . ($voucher->payee_name ? ' إلى: ' . $voucher->payee_name : ''),
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                ],
            ],
        ]);
    }

    private function generateVoucherNo(): string
    {
        return 'GPV-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}