<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BalanceSheetReportService
{
    public function getReport(
        ?string $dateTo = null,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        $dateTo = $dateTo ?: now()->toDateString();

        $journalLineTable = 'journal_entry_lines';
        $journalEntryTable = 'journal_entries';

        $journalEntryForeignKey = Schema::hasColumn($journalLineTable, 'journal_entry_id')
            ? 'journal_entry_id'
            : 'journal_id';

        $entryDateColumn = Schema::hasColumn($journalEntryTable, 'entry_date')
            ? 'entry_date'
            : 'date';

        $accountTypeColumn = Schema::hasColumn('accounts', 'account_type')
            ? 'account_type'
            : (Schema::hasColumn('accounts', 'type') ? 'type' : null);

        $hasBranchColumn = Schema::hasColumn($journalLineTable, 'branch_id');
        $hasCostCenterColumn = Schema::hasColumn($journalLineTable, 'cost_center_id');

        $query = DB::table($journalLineTable . ' as jel')
            ->join($journalEntryTable . ' as je', 'jel.' . $journalEntryForeignKey, '=', 'je.id')
            ->join('accounts as a', 'jel.account_id', '=', 'a.id')
            ->whereNotNull('jel.account_id')
            ->whereDate('je.' . $entryDateColumn, '<=', $dateTo);

        if ($accountTypeColumn) {
            $query->whereIn('a.' . $accountTypeColumn, [
                'asset',
                'assets',
                'liability',
                'liabilities',
                'equity',
                'revenue',
                'income',
                'expense',
                'expenses',
            ]);
        }

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
                'jel.account_id',
                DB::raw('COALESCE(SUM(jel.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(jel.credit), 0) as total_credit'),
            ])
            ->groupBy('jel.account_id')
            ->get()
            ->keyBy('account_id');

        $accountIds = $balances->keys()->filter()->values();

        $accounts = Account::whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        $assetRows = collect();
        $liabilityRows = collect();
        $equityRows = collect();

        $totalRevenue = 0;
        $totalExpenses = 0;

        foreach ($balances as $accountId => $row) {
            $account = $accounts->get($accountId);

            if (!$account) {
                continue;
            }

            $accountType = $account->account_type
                ?? $account->type
                ?? null;

            $accountCode = $account->account_code
                ?? $account->code
                ?? '';

            $accountName = $account->account_name_ar
                ?? $account->account_name
                ?? $account->name
                ?? ('حساب رقم ' . $accountId);

            $totalDebit = round((float) $row->total_debit, 2);
            $totalCredit = round((float) $row->total_credit, 2);

            if (in_array($accountType, ['asset', 'assets'], true)) {
                $balance = round($totalDebit - $totalCredit, 2);

                $assetRows->push($this->makeRow(
                    accountId: (int) $accountId,
                    account: $account,
                    accountCode: $accountCode,
                    accountName: $accountName,
                    accountType: $accountType,
                    totalDebit: $totalDebit,
                    totalCredit: $totalCredit,
                    balance: $balance
                ));
            }

            if (in_array($accountType, ['liability', 'liabilities'], true)) {
                $balance = round($totalCredit - $totalDebit, 2);

                $liabilityRows->push($this->makeRow(
                    accountId: (int) $accountId,
                    account: $account,
                    accountCode: $accountCode,
                    accountName: $accountName,
                    accountType: $accountType,
                    totalDebit: $totalDebit,
                    totalCredit: $totalCredit,
                    balance: $balance
                ));
            }

            if ($accountType === 'equity') {
                $balance = round($totalCredit - $totalDebit, 2);

                $equityRows->push($this->makeRow(
                    accountId: (int) $accountId,
                    account: $account,
                    accountCode: $accountCode,
                    accountName: $accountName,
                    accountType: $accountType,
                    totalDebit: $totalDebit,
                    totalCredit: $totalCredit,
                    balance: $balance
                ));
            }

            /*
                صافي الربح غير المرحل:
                الإيرادات طبيعتها دائن = دائن - مدين
                المصروفات طبيعتها مدين = مدين - دائن

                نضيفه ضمن حقوق الملكية حتى تظهر الميزانية متوازنة قبل عمل قيد الإقفال.
            */
            if (in_array($accountType, ['revenue', 'income'], true)) {
                $totalRevenue += round($totalCredit - $totalDebit, 2);
            }

            if (in_array($accountType, ['expense', 'expenses'], true)) {
                $totalExpenses += round($totalDebit - $totalCredit, 2);
            }
        }

        $assetRows = $assetRows
            ->filter(fn ($row) => round((float) $row['balance'], 2) != 0)
            ->sortBy('account_code')
            ->values();

        $liabilityRows = $liabilityRows
            ->filter(fn ($row) => round((float) $row['balance'], 2) != 0)
            ->sortBy('account_code')
            ->values();

        $equityRows = $equityRows
            ->filter(fn ($row) => round((float) $row['balance'], 2) != 0)
            ->sortBy('account_code')
            ->values();

        $netIncome = round($totalRevenue - $totalExpenses, 2);

        if ($netIncome != 0) {
            $equityRows->push([
                'account_id' => null,
                'account' => null,
                'account_code' => '',
                'account_name' => $netIncome > 0
                    ? 'صافي ربح الفترة غير المرحل'
                    : 'صافي خسارة الفترة غير المرحلة',
                'account_type' => 'equity',
                'total_debit' => 0,
                'total_credit' => 0,
                'balance' => $netIncome,
                'is_net_income' => true,
            ]);
        }

        $totalAssets = round($assetRows->sum('balance'), 2);
        $totalLiabilities = round($liabilityRows->sum('balance'), 2);
        $totalEquity = round($equityRows->sum('balance'), 2);

        $totalLiabilitiesAndEquity = round($totalLiabilities + $totalEquity, 2);

        $difference = round($totalAssets - $totalLiabilitiesAndEquity, 2);

        return [
            'date_to' => $dateTo,
            'branch_id' => $branchId,
            'cost_center_id' => $costCenterId,

            'assets' => $assetRows,
            'liabilities' => $liabilityRows,
            'equity' => $equityRows,

            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,

            'total_revenue' => round($totalRevenue, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_income' => $netIncome,

            'difference' => $difference,
        ];
    }


    private function makeRow(
        int $accountId,
        $account,
        string $accountCode,
        string $accountName,
        ?string $accountType,
        float $totalDebit,
        float $totalCredit,
        float $balance
    ): array {
        return [
            'account_id' => $accountId,
            'account' => $account,
            'account_code' => $accountCode,
            'account_name' => $accountName,
            'account_type' => $accountType,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'balance' => $balance,
            'is_net_income' => false,
        ];
    }
}