<?php

namespace App\Services;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    public function create(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {

            $lines = $data['lines'] ?? [];

            if (count($lines) < 2) {
                throw ValidationException::withMessages([
                    'lines' => 'القيد المحاسبي يجب أن يحتوي على طرفين على الأقل.',
                ]);
            }

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $debit = round((float) ($line['debit'] ?? 0), 2);
                $credit = round((float) ($line['credit'] ?? 0), 2);

                if ($debit > 0 && $credit > 0) {
                    throw ValidationException::withMessages([
                        'lines' => 'لا يمكن أن يكون نفس السطر مدين ودائن في نفس الوقت.',
                    ]);
                }

                if ($debit <= 0 && $credit <= 0) {
                    throw ValidationException::withMessages([
                        'lines' => 'كل سطر في القيد يجب أن يحتوي على مبلغ مدين أو دائن.',
                    ]);
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            $totalDebit = round($totalDebit, 2);
            $totalCredit = round($totalCredit, 2);

            if ($totalDebit !== $totalCredit) {
                throw ValidationException::withMessages([
                    'lines' => 'القيد غير متوازن. إجمالي المدين يجب أن يساوي إجمالي الدائن.',
                ]);
            }

            $entry = JournalEntry::create([
                'entry_no' => $data['entry_no'] ?? $this->generateEntryNo(),
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? null,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'customer_id' => $line['customer_id'] ?? null,
                    'supplier_id' => $line['supplier_id'] ?? null,
                    'branch_id' => $line['branch_id'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'debit' => round((float) ($line['debit'] ?? 0), 2),
                    'credit' => round((float) ($line['credit'] ?? 0), 2),
                ]);
            }

            return $entry->load('lines.account');
        });
    }


    public function reverse(JournalEntry $entry, ?string $reason = null): JournalEntry
    {
        return DB::transaction(function () use ($entry, $reason) {

            if ($entry->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'entry' => 'هذا القيد ملغى بالفعل.',
                ]);
            }

            $entry->load('lines');

            $reverseLines = [];

            foreach ($entry->lines as $line) {
                $reverseLines[] = [
                    'account_id' => $line->account_id,

                    'customer_id' => $line->customer_id,
                    'supplier_id' => $line->supplier_id,
                    'branch_id' => $line->branch_id,
                    'cost_center_id' => $line->cost_center_id,

                    'description' => 'عكس قيد رقم ' . $entry->entry_no,

                    'debit' => $line->credit,
                    'credit' => $line->debit,
                ];
            }

            $reverseEntry = $this->create([
                'entry_date' => now()->toDateString(),
                'reference_type' => JournalEntry::class,
                'reference_id' => $entry->id,
                'description' => $reason ?? 'عكس قيد رقم ' . $entry->entry_no,
                'lines' => $reverseLines,
            ]);

            $entry->update([
                'status' => 'cancelled',
            ]);

            return $reverseEntry;
        });
    }

    private function generateEntryNo(): string
    {
        return 'JV-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}