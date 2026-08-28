<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\JournalEntry;
use App\Models\ManualJournalEntry;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManualJournalEntryService
{
    public function store(array $data): ManualJournalEntry
    {
        return DB::transaction(function () use ($data) {

            $manualEntry = ManualJournalEntry::create([
                'manual_no' => $data['manual_no'] ?? $this->generateManualNo(),
                'manual_date' => $data['manual_date'],
                'branch_id' => $data['branch_id'] ?? null,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['lines'] as $line) {
                $debit = round((float) ($line['debit'] ?? 0), 2);
                $credit = round((float) ($line['credit'] ?? 0), 2);

                if ($debit <= 0 && $credit <= 0) {
                    continue;
                }

                if ($debit > 0 && $credit > 0) {
                    throw new Exception('لا يمكن أن يكون السطر مدين ودائن في نفس الوقت.');
                }

                $lineType = $line['line_type'];

                if ($lineType === 'account' && empty($line['account_id'])) {
                    throw new Exception('يوجد سطر حساب بدون اختيار الحساب.');
                }

                if ($lineType === 'customer' && empty($line['customer_id'])) {
                    throw new Exception('يوجد سطر عميل بدون اختيار العميل.');
                }

                if ($lineType === 'supplier' && empty($line['supplier_id'])) {
                    throw new Exception('يوجد سطر مورد بدون اختيار المورد.');
                }

                $manualEntry->lines()->create([
                    'branch_id' => $line['branch_id'] ?? $data['branch_id'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,

                    'line_type' => $lineType,

                    'account_id' => $lineType === 'account'
                        ? ($line['account_id'] ?? null)
                        : null,

                    'customer_id' => $lineType === 'customer'
                        ? ($line['customer_id'] ?? null)
                        : null,

                    'supplier_id' => $lineType === 'supplier'
                        ? ($line['supplier_id'] ?? null)
                        : null,

                    'debit' => $debit,
                    'credit' => $credit,
                    'description' => $line['description'] ?? null,
                ]);
            }

            if ($manualEntry->lines()->count() === 0) {
                throw new Exception('يجب إدخال سطر واحد على الأقل.');
            }

            return $manualEntry;
        });
    }


    public function post(ManualJournalEntry $manualEntry): ManualJournalEntry
    {
        return DB::transaction(function () use ($manualEntry) {

            $manualEntry->load('lines');

            if ($manualEntry->status !== 'draft') {
                throw new Exception('لا يمكن ترحيل هذا القيد لأنه ليس مسودة.');
            }

            if ($manualEntry->lines->count() < 2) {
                throw new Exception('القيد اليدوي يجب أن يحتوي على سطرين على الأقل للترحيل.');
            }

            $journalLines = [];

            $accountsReceivable = $this->accountId('accounts_receivable');
            $accountsPayable = $this->accountId('accounts_payable');

            foreach ($manualEntry->lines as $line) {
                $debit = round((float) $line->debit, 2);
                $credit = round((float) $line->credit, 2);

                if ($debit <= 0 && $credit <= 0) {
                    continue;
                }

                if ($debit > 0 && $credit > 0) {
                    throw new Exception('لا يمكن أن يكون السطر مدين ودائن في نفس الوقت.');
                }

                $branchId = $line->branch_id ?? $manualEntry->branch_id;
                $costCenterId = $line->cost_center_id;

                if ($line->line_type === 'account') {
                    if (!$line->account_id) {
                        throw new Exception('يوجد سطر حساب بدون اختيار الحساب.');
                    }

                    $journalLines[] = [
                        'account_id' => $line->account_id,
                        'debit' => $debit,
                        'credit' => $credit,
                        'description' => $line->description ?: 'قيد يومية يدوي',
                        'branch_id' => $branchId,
                        'cost_center_id' => $costCenterId,
                    ];
                }

                if ($line->line_type === 'customer') {
                    if (!$line->customer_id) {
                        throw new Exception('يوجد سطر عميل بدون اختيار العميل.');
                    }

                    $journalLines[] = [
                        'account_id' => $accountsReceivable,
                        'customer_id' => $line->customer_id,
                        'debit' => $debit,
                        'credit' => $credit,
                        'description' => $line->description ?: 'قيد يومية يدوي لعميل',
                        'branch_id' => $branchId,
                        'cost_center_id' => $costCenterId,
                    ];
                }

                if ($line->line_type === 'supplier') {
                    if (!$line->supplier_id) {
                        throw new Exception('يوجد سطر مورد بدون اختيار المورد.');
                    }

                    $journalLines[] = [
                        'account_id' => $accountsPayable,
                        'supplier_id' => $line->supplier_id,
                        'debit' => $debit,
                        'credit' => $credit,
                        'description' => $line->description ?: 'قيد يومية يدوي لمورد',
                        'branch_id' => $branchId,
                        'cost_center_id' => $costCenterId,
                    ];
                }
            }

            $totalDebit = round(collect($journalLines)->sum('debit'), 2);
            $totalCredit = round(collect($journalLines)->sum('credit'), 2);

            if ($totalDebit <= 0 && $totalCredit <= 0) {
                throw new Exception('لا توجد مبالغ صالحة للترحيل.');
            }

            if ($totalDebit !== $totalCredit) {
                throw new Exception('القيد غير متوازن. إجمالي المدين يجب أن يساوي إجمالي الدائن.');
            }

            app(JournalEntryService::class)->create([
                'entry_date' => $manualEntry->manual_date,
                'document_type' => 'manual_journal_entry',
                'document_number' => $manualEntry->manual_no,

                'reference_type' => ManualJournalEntry::class,
                'reference_id' => $manualEntry->id,

                'description' => 'قيد يومية يدوي رقم ' . $manualEntry->manual_no,
                'created_by' => auth()->id(),

                'lines' => $journalLines,
            ]);

            $manualEntry->update([
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            return $manualEntry;
        });
    }


    public function cancel(ManualJournalEntry $manualEntry, ?string $reason = null): ManualJournalEntry
    {
        return DB::transaction(function () use ($manualEntry, $reason) {

            if ($manualEntry->status !== 'posted') {
                throw new Exception('لا يمكن إلغاء قيد غير مرحل.');
            }

            $journalEntries = JournalEntry::where('reference_type', ManualJournalEntry::class)
                ->where('reference_id', $manualEntry->id)
                ->get();

            foreach ($journalEntries as $entry) {
                app(JournalEntryService::class)->reverse($entry);
            }

            $manualEntry->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $manualEntry;
        });
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


    private function generateManualNo(): string
    {
        $prefix = 'MJV-' . now()->format('Ymd') . '-';

        $lastId = (int) ManualJournalEntry::max('id') + 1;

        return $prefix . str_pad($lastId, 5, '0', STR_PAD_LEFT);
    }
}