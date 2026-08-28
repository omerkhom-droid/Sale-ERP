<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\JournalEntry;
use App\Models\OpeningBalance;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OpeningBalanceService
{
    public function store(array $data): OpeningBalance
    {
        return DB::transaction(function () use ($data) {

            $openingBalance = OpeningBalance::create([
                'opening_no' => $this->generateOpeningNo(),
                'opening_date' => $data['opening_date'],
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

                $openingBalance->lines()->create([
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

            if ($openingBalance->lines()->count() === 0) {
                throw new Exception('يجب إدخال سطر واحد على الأقل.');
            }

            return $openingBalance;
        });
    }


    public function post(OpeningBalance $openingBalance): OpeningBalance
    {
        return DB::transaction(function () use ($openingBalance) {

            $openingBalance->load('lines');

            if ($openingBalance->status !== 'draft') {
                throw new Exception('لا يمكن ترحيل هذا المستند لأنه ليس مسودة.');
            }

            $journalLines = [];

            $accountsReceivable = $this->accountId('accounts_receivable');
            $accountsPayable = $this->accountId('accounts_payable');
            $openingBalanceEquity = $this->accountId('opening_balance_equity');

            foreach ($openingBalance->lines as $line) {
                $debit = round((float) $line->debit, 2);
                $credit = round((float) $line->credit, 2);

                if ($debit <= 0 && $credit <= 0) {
                    continue;
                }

                $branchId = $line->branch_id ?? $openingBalance->branch_id;
                $costCenterId = $line->cost_center_id;

                if ($line->line_type === 'account') {
                    if (!$line->account_id) {
                        throw new Exception('يوجد سطر حساب بدون اختيار الحساب.');
                    }

                    $journalLines[] = [
                        'account_id' => $line->account_id,
                        'debit' => $debit,
                        'credit' => $credit,
                        'description' => $line->description ?: 'رصيد افتتاحي لحساب',
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
                        'description' => $line->description ?: 'رصيد افتتاحي لعميل',
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
                        'description' => $line->description ?: 'رصيد افتتاحي لمورد',
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

            $difference = round($totalDebit - $totalCredit, 2);

            if ($difference > 0) {
                $journalLines[] = [
                    'account_id' => $openingBalanceEquity,
                    'debit' => 0,
                    'credit' => $difference,
                    'description' => 'مقابل الأرصدة الافتتاحية',
                    'branch_id' => $openingBalance->branch_id,
                    'cost_center_id' => null,
                ];
            } elseif ($difference < 0) {
                $journalLines[] = [
                    'account_id' => $openingBalanceEquity,
                    'debit' => abs($difference),
                    'credit' => 0,
                    'description' => 'مقابل الأرصدة الافتتاحية',
                    'branch_id' => $openingBalance->branch_id,
                    'cost_center_id' => null,
                ];
            }

            app(JournalEntryService::class)->create([
                'entry_date' => $openingBalance->opening_date,
                'document_type' => 'opening_balance',
                'document_number' => $openingBalance->opening_no,

                'reference_type' => OpeningBalance::class,
                'reference_id' => $openingBalance->id,

                'description' => 'قيد الأرصدة الافتتاحية رقم ' . $openingBalance->opening_no,
                'created_by' => auth()->id(),

                'lines' => $journalLines,
            ]);

            $openingBalance->update([
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            return $openingBalance;
        });
    }


    public function cancel(OpeningBalance $openingBalance, ?string $reason = null): OpeningBalance
    {
        return DB::transaction(function () use ($openingBalance, $reason) {

            if ($openingBalance->status !== 'posted') {
                throw new Exception('لا يمكن إلغاء مستند غير مرحل.');
            }

            $journalEntries = JournalEntry::where('reference_type', OpeningBalance::class)
                ->where('reference_id', $openingBalance->id)
                ->get();

            foreach ($journalEntries as $entry) {
                app(JournalEntryService::class)->reverse($entry);
            }

            $openingBalance->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $openingBalance;
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


    private function generateOpeningNo(): string
    {
        $prefix = 'OBAL-' . now()->format('Ymd') . '-';

        $lastId = (int) OpeningBalance::max('id') + 1;

        return $prefix . str_pad($lastId, 5, '0', STR_PAD_LEFT);
    }
}