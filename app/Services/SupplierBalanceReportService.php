<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\Supplier;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierBalanceReportService
{
    public function getReport(
        ?string $dateTo = null,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        $dateTo = $dateTo ?: now()->toDateString();

        $payableAccountId = $this->accountId('accounts_payable');

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
            ->whereNotNull('jel.supplier_id')
            ->where('jel.account_id', $payableAccountId)
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
                'jel.supplier_id',
                DB::raw('SUM(jel.debit) as total_debit'),
                DB::raw('SUM(jel.credit) as total_credit'),
                DB::raw('SUM(jel.credit - jel.debit) as balance'),
            ])
            ->groupBy('jel.supplier_id')
            ->get();

        $supplierIds = $balances->pluck('supplier_id')->filter()->values();

        $suppliers = Supplier::whereIn('id', $supplierIds)
            ->get()
            ->keyBy('id');

        $rows = $balances->map(function ($row) use ($suppliers) {
            $supplier = $suppliers->get($row->supplier_id);

            $balance = round((float) $row->balance, 2);

            return [
                'supplier_id' => $row->supplier_id,
                'supplier' => $supplier,

                'supplier_name' => $supplier?->supplier_name
                    ?? $supplier?->name
                    ?? 'مورد رقم ' . $row->supplier_id,

                'mobile' => $supplier?->mobile ?? $supplier?->phone ?? '-',

                'total_debit' => round((float) $row->total_debit, 2),
                'total_credit' => round((float) $row->total_credit, 2),
                'balance' => $balance,

                /*
                    الموردين طبيعتهم دائن:
                    موجب = علينا للمورد
                    سالب = للمورد رصيد مدين / دفعة مقدمة له
                */
                'balance_type' => $balance > 0
                    ? 'credit'
                    : ($balance < 0 ? 'debit' : 'zero'),
            ];
        })
        ->sortBy('supplier_name')
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