<?php

namespace App\Services;

use App\Models\GeneralReceiptVoucher;
use App\Models\JournalEntry;
use Exception;
use Illuminate\Support\Facades\DB;

class GeneralReceiptVoucherService
{
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {}

    public function store(array $data): GeneralReceiptVoucher
    {
        return DB::transaction(function () use ($data) {

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw new Exception('مبلغ سند القبض يجب أن يكون أكبر من صفر.');
            }

            if ((int) $data['cash_bank_account_id'] === (int) $data['opposite_account_id']) {
                throw new Exception('لا يمكن أن يكون حساب القبض والحساب المقابل نفس الحساب.');
            }

            $voucher = GeneralReceiptVoucher::create([
                'voucher_no' => $data['voucher_no'] ?? $this->generateVoucherNo(),

                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,

                'voucher_date' => $data['voucher_date'],
                'payment_method' => $data['payment_method'] ?? 'cash',

                'cash_bank_account_id' => $data['cash_bank_account_id'],
                'opposite_account_id' => $data['opposite_account_id'],

                'amount' => $amount,

                'payer_name' => $data['payer_name'] ?? null,
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

    public function post(GeneralReceiptVoucher $voucher): GeneralReceiptVoucher
    {
        return DB::transaction(function () use ($voucher) {

            $voucher = GeneralReceiptVoucher::lockForUpdate()
                ->findOrFail($voucher->id);

            if ($voucher->status === 'posted') {
                throw new Exception('سند القبض العام مرحل مسبقاً.');
            }

            if ($voucher->status === 'cancelled') {
                throw new Exception('لا يمكن ترحيل سند قبض عام ملغى.');
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

    public function cancel(GeneralReceiptVoucher $voucher, ?string $reason = null): GeneralReceiptVoucher
    {
        return DB::transaction(function () use ($voucher, $reason) {

            $voucher = GeneralReceiptVoucher::lockForUpdate()
                ->findOrFail($voucher->id);

            if ($voucher->status === 'cancelled') {
                throw new Exception('سند القبض العام ملغى مسبقاً.');
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

            $journalEntries = JournalEntry::where('reference_type', GeneralReceiptVoucher::class)
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

    private function createJournalEntry(GeneralReceiptVoucher $voucher): void
    {
        $this->journalEntryService->create([
            'entry_date' => $voucher->voucher_date,
            'document_type' => 'general_receipt_voucher',
            'document_number' => $voucher->voucher_no,

            'reference_type' => GeneralReceiptVoucher::class,
            'reference_id' => $voucher->id,

            'description' => 'قيد سند قبض عام رقم ' . $voucher->voucher_no,
            'created_by' => auth()->id(),

            'lines' => [
                [
                    'account_id' => $voucher->cash_bank_account_id,
                    'debit' => $voucher->amount,
                    'credit' => 0,
                    'description' => 'قبض عام' . ($voucher->payer_name ? ' من: ' . $voucher->payer_name : ''),
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                ],
                [
                    'account_id' => $voucher->opposite_account_id,
                    'debit' => 0,
                    'credit' => $voucher->amount,
                    'description' => 'الحساب المقابل لسند قبض عام رقم ' . $voucher->voucher_no,
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                ],
            ],
        ]);
    }

    private function generateVoucherNo(): string
    {
        return 'GRV-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}