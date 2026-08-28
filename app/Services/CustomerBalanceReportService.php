<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\Customer;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerBalanceReportService
{
    public function getReport(
        ?string $dateTo = null,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        $dateTo = $dateTo ?: now()->toDateString();

        $receivableAccountId = $this->accountId('accounts_receivable');

        $journalLineTable = 'journal_entry_lines';
        $journalEntryTable = 'journal_entries';

        $journalEntryForeignKey = Schema::hasColumn($journalLineTable, 'journal_entry_id')
            ? 'journal_entry_id'
            : 'journal_id';

        $entryDateColumn = Schema::hasColumn($journalEntryTable, 'entry_date')
            ? 'entry_date'
            : 'date';

        $hasBranchColumn = Schema::hasColumn($journalLineTable, 'branch_id');
        $hasCostCenterColumn = Schema::hasColumn($journalLineTable, 'cost_center_id');

        $query = DB::table($journalLineTable . ' as jel')
            ->join($journalEntryTable . ' as je', 'jel.' . $journalEntryForeignKey, '=', 'je.id')
            ->whereNotNull('jel.customer_id')
            ->where('jel.account_id', $receivableAccountId)
            ->whereDate('je.' . $entryDateColumn, '<=', $dateTo);

        if (Schema::hasColumn($journalEntryTable, 'status')) {
            $query->where(function ($q) {
                $q->whereNull('je.status')
                    ->orWhereNotIn('je.status', ['cancelled', 'void']);
            });
        }

        if ($branchId && $hasBranchColumn) {
            $query->where('jel.branch_id', $branchId);
        }

        if ($costCenterId && $hasCostCenterColumn) {
            $query->where('jel.cost_center_id', $costCenterId);
        }

        $balances = $query
            ->select([
                'jel.customer_id',
                DB::raw('SUM(jel.debit) as total_debit'),
                DB::raw('SUM(jel.credit) as total_credit'),
                DB::raw('SUM(jel.debit - jel.credit) as balance'),
            ])
            ->groupBy('jel.customer_id')
            ->get();

        $customerIds = $balances->pluck('customer_id')->filter()->values();

        $customers = Customer::whereIn('id', $customerIds)
            ->get()
            ->keyBy('id');

        $rows = $balances->map(function ($row) use ($customers) {
            $customer = $customers->get($row->customer_id);

            $balance = round((float) $row->balance, 2);

            return [
                'customer_id' => $row->customer_id,
                'customer' => $customer,

                'customer_name' => $customer?->customer_name
                    ?? $customer?->name
                    ?? 'عميل رقم ' . $row->customer_id,

                'mobile' => $customer?->mobile ?? $customer?->phone ?? '-',

                'total_debit' => round((float) $row->total_debit, 2),
                'total_credit' => round((float) $row->total_credit, 2),
                'balance' => $balance,

                /*
                    العملاء طبيعتهم مدين:
                    موجب = العميل عليه مبلغ
                    سالب = للعميل رصيد دائن / دفعة مقدمة
                */
                'balance_type' => $balance > 0
                    ? 'debit'
                    : ($balance < 0 ? 'credit' : 'zero'),
            ];
        })
        ->sortBy('customer_name')
        ->values();

        return [
            'date_to' => $dateTo,
            'branch_id' => $branchId,
            'cost_center_id' => $costCenterId,

            'rows' => $rows,

            'total_debit' => round($rows->sum('total_debit'), 2),
            'total_credit' => round($rows->sum('total_credit'), 2),
            'total_balance' => round($rows->sum('balance'), 2),
        ];
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
}