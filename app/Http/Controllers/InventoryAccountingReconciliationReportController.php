<?php

namespace App\Http\Controllers;

use App\Models\AccountSetting;
use App\Models\Branch;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryAccountingReconciliationReportController extends Controller
{
    private function actorUserType(): string
    {
        return auth()->user()?->user_type ?? 'user';
    }

    private function actorCanSeeAllCompanies(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
        ], true);
    }

    private function actorBranchIds(): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($this->actorCanSeeAllCompanies()) {
            return null;
        }

        if (in_array($this->actorUserType(), ['company_owner', 'company_admin'], true)) {
            if (! $user->company_id) {
                return [];
            }

            return Branch::query()
                ->where('company_id', $user->company_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        if (! $user->branch_id) {
            return [];
        }

        return [(int) $user->branch_id];
    }

    private function applyBranchScope($query, string $column = 'id')
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $branchIds);
    }

    private function activeBranches()
    {
        $query = Branch::query()
            ->orderBy('branch_name');

        $this->applyBranchScope($query, 'id');

        return $query->get();
    }

    private function assertBranchAllowed(?int $branchId): void
    {
        if (! $branchId) {
            return;
        }

        $query = Branch::query()
            ->whereKey($branchId);

        $this->applyBranchScope($query, 'id');

        abort_unless(
            $query->exists(),
            403,
            'لا تملك صلاحية عرض بيانات هذا الفرع.'
        );
    }

    public function index(Request $request)
    {
        $branchId = $request->filled('branch_id')
            ? (int) $request->branch_id
            : null;

        $this->assertBranchAllowed($branchId);

        $branches = $this->activeBranches();

        try {
            $inventoryAccountId = $this->accountId('inventory_account');
        } catch (Exception $e) {
            return view('reports.inventory-accounting-reconciliation.index', [
                'branches' => $branches,
                'branchId' => $branchId,
                'inventoryValue' => 0,
                'ledgerBalance' => 0,
                'difference' => 0,
                'rows' => collect(),
                'configError' => $e->getMessage(),
            ]);
        }

        $inventoryValue = $this->inventoryValue($branchId);
        $ledgerBalance = $this->ledgerBalance($inventoryAccountId, $branchId);
        $difference = round($inventoryValue - $ledgerBalance, 2);

        $rows = $this->branchRows($inventoryAccountId, $branchId);

        return view('reports.inventory-accounting-reconciliation.index', compact(
            'branches',
            'branchId',
            'inventoryValue',
            'ledgerBalance',
            'difference',
            'rows'
        ))->with('configError', null);
    }

    private function inventoryValue(?int $branchId = null): float
    {
        $query = DB::table('product_stocks')
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'product_stocks.warehouse_id')
            ->where('products.is_active', true)
            ->where('warehouses.is_active', true);

        $branchIds = $this->actorBranchIds();

        if (! is_null($branchIds)) {
            if (empty($branchIds)) {
                return 0;
            }

            $query->whereIn('warehouses.branch_id', $branchIds);
        }

        if ($branchId) {
            $query->where('warehouses.branch_id', $branchId);
        }

        return round((float) $query->sum(
            DB::raw('product_stocks.quantity * product_stocks.average_cost')
        ), 2);
    }

    private function ledgerBalance(int $inventoryAccountId, ?int $branchId = null): float
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $inventoryAccountId)
            ->where('journal_entries.status', 'posted');

        $branchIds = $this->actorBranchIds();

        if (! is_null($branchIds)) {
            if (empty($branchIds)) {
                return 0;
            }

            $query->whereIn('journal_entry_lines.branch_id', $branchIds);
        }

        if ($branchId) {
            $query->where('journal_entry_lines.branch_id', $branchId);
        }

        return round((float) $query->sum(
            DB::raw('journal_entry_lines.debit - journal_entry_lines.credit')
        ), 2);
    }

    private function branchRows(int $inventoryAccountId, ?int $selectedBranchId = null)
    {
        $branchesQuery = Branch::query()
            ->orderBy('branch_name');

        $this->applyBranchScope($branchesQuery, 'id');

        if ($selectedBranchId) {
            $branchesQuery->whereKey($selectedBranchId);
        }

        return $branchesQuery
            ->get()
            ->map(function (Branch $branch) use ($inventoryAccountId) {
                $inventoryValue = $this->inventoryValue((int) $branch->id);
                $ledgerBalance = $this->ledgerBalance($inventoryAccountId, (int) $branch->id);
                $difference = round($inventoryValue - $ledgerBalance, 2);

                return [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->branch_name,
                    'inventory_value' => $inventoryValue,
                    'ledger_balance' => $ledgerBalance,
                    'difference' => $difference,
                    'status' => abs($difference) <= 0.01 ? 'matched' : 'different',
                ];
            });
    }

    private function accountId(string $key): int
    {
        $setting = AccountSetting::query()
            ->where('setting_key', $key)
            ->first();

        if (! $setting || ! $setting->account_id) {
            throw new Exception('يرجى ضبط الحساب المحاسبي: ' . $key);
        }

        return (int) $setting->account_id;
    }
}