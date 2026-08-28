<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrialBalanceReportService
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

        $hasBranchColumn = Schema::hasColumn($journalLineTable, 'branch_id');
        $hasCostCenterColumn = Schema::hasColumn($journalLineTable, 'cost_center_id');

        /*
        |--------------------------------------------------------------------------
        | أرصدة افتتاحية قبل تاريخ البداية
        |--------------------------------------------------------------------------
        */
        $openingBalances = collect();

        if ($dateFrom) {
            $openingQuery = $this->baseQuery(
                journalLineTable: $journalLineTable,
                journalEntryTable: $journalEntryTable,
                journalEntryForeignKey: $journalEntryForeignKey,
                hasBranchColumn: $hasBranchColumn,
                hasCostCenterColumn: $hasCostCenterColumn,
                branchId: $branchId,
                costCenterId: $costCenterId
            );

            $openingBalances = $openingQuery
                ->whereDate('je.' . $entryDateColumn, '<', $dateFrom)
                ->select([
                    'jel.account_id',
                    DB::raw('COALESCE(SUM(jel.debit - jel.credit), 0) as opening_balance'),
                ])
                ->groupBy('jel.account_id')
                ->get()
                ->keyBy('account_id');
        }

        /*
        |--------------------------------------------------------------------------
        | حركات الفترة
        |--------------------------------------------------------------------------
        */
        $periodQuery = $this->baseQuery(
            journalLineTable: $journalLineTable,
            journalEntryTable: $journalEntryTable,
            journalEntryForeignKey: $journalEntryForeignKey,
            hasBranchColumn: $hasBranchColumn,
            hasCostCenterColumn: $hasCostCenterColumn,
            branchId: $branchId,
            costCenterId: $costCenterId
        );

        $periodBalances = $periodQuery
            ->when($dateFrom, function ($query) use ($dateFrom, $entryDateColumn) {
                $query->whereDate('je.' . $entryDateColumn, '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo, $entryDateColumn) {
                $query->whereDate('je.' . $entryDateColumn, '<=', $dateTo);
            })
            ->select([
                'jel.account_id',
                DB::raw('COALESCE(SUM(jel.debit), 0) as period_debit'),
                DB::raw('COALESCE(SUM(jel.credit), 0) as period_credit'),
                DB::raw('COALESCE(SUM(jel.debit - jel.credit), 0) as period_balance'),
            ])
            ->groupBy('jel.account_id')
            ->get()
            ->keyBy('account_id');

        $accountIds = $openingBalances
            ->keys()
            ->merge($periodBalances->keys())
            ->filter()
            ->unique()
            ->values();

        $accounts = Account::whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        $rows = $accountIds->map(function ($accountId) use ($accounts, $openingBalances, $periodBalances) {
            $account = $accounts->get($accountId);

            $openingBalance = round((float) optional($openingBalances->get($accountId))->opening_balance, 2);

            $periodDebit = round((float) optional($periodBalances->get($accountId))->period_debit, 2);
            $periodCredit = round((float) optional($periodBalances->get($accountId))->period_credit, 2);

            $closingBalance = round($openingBalance + $periodDebit - $periodCredit, 2);

            $accountCode = $account->account_code
                ?? $account->code
                ?? '';

            $accountName = $account->account_name_ar
                ?? $account->account_name
                ?? $account->name
                ?? ('حساب رقم ' . $accountId);

            return [
                'account_id' => $accountId,
                'account' => $account,

                'account_code' => $accountCode,
                'account_name' => $accountName,
                'account_type' => $account->account_type ?? $account->type ?? '-',

                'opening_balance' => $openingBalance,
                'opening_debit' => $openingBalance > 0 ? abs($openingBalance) : 0,
                'opening_credit' => $openingBalance < 0 ? abs($openingBalance) : 0,

                'period_debit' => $periodDebit,
                'period_credit' => $periodCredit,

                'closing_balance' => $closingBalance,
                'closing_debit' => $closingBalance > 0 ? abs($closingBalance) : 0,
                'closing_credit' => $closingBalance < 0 ? abs($closingBalance) : 0,
            ];
        })
        ->filter(function ($row) {
            return round((float) $row['opening_balance'], 2) != 0
                || round((float) $row['period_debit'], 2) != 0
                || round((float) $row['period_credit'], 2) != 0
                || round((float) $row['closing_balance'], 2) != 0;
        })
        ->sortBy('account_code')
        ->values();

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'branch_id' => $branchId,
            'cost_center_id' => $costCenterId,

            'rows' => $rows,

            'total_opening_debit' => round($rows->sum('opening_debit'), 2),
            'total_opening_credit' => round($rows->sum('opening_credit'), 2),

            'total_period_debit' => round($rows->sum('period_debit'), 2),
            'total_period_credit' => round($rows->sum('period_credit'), 2),

            'total_closing_debit' => round($rows->sum('closing_debit'), 2),
            'total_closing_credit' => round($rows->sum('closing_credit'), 2),
        ];
    }


    private function baseQuery(
        string $journalLineTable,
        string $journalEntryTable,
        string $journalEntryForeignKey,
        bool $hasBranchColumn,
        bool $hasCostCenterColumn,
        ?int $branchId,
        ?int $costCenterId
    ) {
        $query = DB::table($journalLineTable . ' as jel')
            ->join($journalEntryTable . ' as je', 'jel.' . $journalEntryForeignKey, '=', 'je.id')
            ->whereNotNull('jel.account_id');

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

        return $query;
    }
}