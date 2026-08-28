<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryBalanceReportController extends Controller
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

    private function applyWarehouseScope($query, string $warehouseTable = 'warehouses')
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($warehouseTable . '.branch_id', $branchIds);
    }

    private function activeWarehouses()
    {
        $query = Warehouse::query()
            ->where('is_active', true);

        $this->applyWarehouseScope($query);

        return $query
            ->orderBy('warehouse_name')
            ->get();
    }

    private function assertWarehouseAllowed(?int $warehouseId): void
    {
        if (! $warehouseId) {
            return;
        }

        $query = Warehouse::query()
            ->whereKey($warehouseId)
            ->where('is_active', true);

        $this->applyWarehouseScope($query);

        abort_unless(
            $query->exists(),
            403,
            'لا تملك صلاحية استخدام هذا المستودع أو أن المستودع غير نشط.'
        );
    }

    public function index(Request $request)
    {
        $warehouseId = $request->filled('warehouse_id')
            ? (int) $request->warehouse_id
            : null;

        $productId = $request->filled('product_id')
            ? (int) $request->product_id
            : null;

        $showZero = $request->boolean('show_zero');

        $this->assertWarehouseAllowed($warehouseId);

        if ($productId) {
            abort_unless(
                Product::query()->whereKey($productId)->where('is_active', true)->exists(),
                403,
                'الصنف غير صحيح أو غير نشط.'
            );
        }

        $warehouses = $this->activeWarehouses();

        $selectedProduct = null;

        if ($productId) {
            $selectedProduct = Product::query()
                ->select(['id', 'sku', 'product_name_ar', 'product_name_en'])
                ->find($productId);
        }

        $baseQuery = DB::table('product_stocks')
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'product_stocks.warehouse_id')
            ->select([
                'product_stocks.product_id',
                'product_stocks.warehouse_id',
                'product_stocks.quantity',
                'product_stocks.average_cost',
                'products.sku',
                'products.product_name_ar',
                'products.product_name_en',
                'warehouses.warehouse_code',
                'warehouses.warehouse_name',
                DB::raw('(product_stocks.quantity * product_stocks.average_cost) as stock_value'),
            ])
            ->where('products.is_active', true)
            ->where('warehouses.is_active', true);

        $this->applyWarehouseScope($baseQuery);

        if ($warehouseId) {
            $baseQuery->where('product_stocks.warehouse_id', $warehouseId);
        }

        if ($productId) {
            $baseQuery->where('product_stocks.product_id', $productId);
        }

        if (! $showZero) {
            $baseQuery->where('product_stocks.quantity', '!=', 0);
        }

        $totalValue = (clone $baseQuery)->sum(DB::raw('product_stocks.quantity * product_stocks.average_cost'));

        $stocks = $baseQuery
            ->orderBy('warehouses.warehouse_name')
            ->orderBy('products.product_name_ar')
            ->paginate(50)
            ->withQueryString();

        return view('reports.inventory-balances.index', compact(
            'stocks',
            'warehouses',
            'warehouseId',
            'productId',
            'selectedProduct',
            'showZero',
            'totalValue'
        ));
    }
}