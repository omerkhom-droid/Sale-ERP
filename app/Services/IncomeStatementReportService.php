<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IncomeStatementReportService
{
    public function getReport(
        ?string $dateFrom = null,
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
            ->when($dateFrom, function ($query) use ($dateFrom, $entryDateColumn) {
                $query->whereDate('je.' . $entryDateColumn, '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo, $entryDateColumn) {
                $query->whereDate('je.' . $entryDateColumn, '<=', $dateTo);
            });

        if ($accountTypeColumn) {
            $query->whereIn('a.' . $accountTypeColumn, ['revenue', 'income', 'expense', 'expenses']);
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

        $revenueRows = collect();
        $expenseRows = collect();

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

            /*
                الإيرادات طبيعتها دائن:
                الرصيد = دائن - مدين

                المصروفات طبيعتها مدين:
                الرصيد = مدين - دائن
            */
            if (in_array($accountType, ['revenue', 'income'], true)) {
                $amount = round($totalCredit - $totalDebit, 2);

                $revenueRows->push([
                    'account_id' => (int) $accountId,
                    'account' => $account,
                    'account_code' => $accountCode,
                    'account_name' => $accountName,
                    'account_type' => $accountType,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'amount' => $amount,
                ]);
            }

            if (in_array($accountType, ['expense', 'expenses'], true)) {
                $amount = round($totalDebit - $totalCredit, 2);

                $expenseRows->push([
                    'account_id' => (int) $accountId,
                    'account' => $account,
                    'account_code' => $accountCode,
                    'account_name' => $accountName,
                    'account_type' => $accountType,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'amount' => $amount,
                ]);
            }
        }

        $revenueRows = $revenueRows
            ->filter(fn ($row) => round((float) $row['amount'], 2) != 0)
            ->sortBy('account_code')
            ->values();

        $expenseRows = $expenseRows
            ->filter(fn ($row) => round((float) $row['amount'], 2) != 0)
            ->sortBy('account_code')
            ->values();

        $totalRevenue = round($revenueRows->sum('amount'), 2);
        $totalExpenses = round($expenseRows->sum('amount'), 2);
        $netIncome = round($totalRevenue - $totalExpenses, 2);

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'branch_id' => $branchId,
            'cost_center_id' => $costCenterId,

            'revenues' => $revenueRows,
            'expenses' => $expenseRows,

            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
        ];
    }
}