<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryMovementReportController extends Controller
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

        /*
            master / system_admin:
            يرى كل الفروع.
        */
        if ($this->actorCanSeeAllCompanies()) {
            return null;
        }

        /*
            company_owner / company_admin:
            يرى فروع شركته فقط.
        */
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

        /*
            branch_admin / user:
            يرى فرعه فقط.
        */
        if (! $user->branch_id) {
            return [];
        }

        return [(int) $user->branch_id];
    }

    private function applyWarehouseScope($query)
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    private function applyInventoryTransactionScope($query)
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        /*
            حركة المخزون مرتبطة بالمستودع.
            لذلك التصفية تكون حسب فرع المستودع.
        */
        return $query->whereHas('warehouse', function ($warehouseQuery) use ($branchIds) {
            $warehouseQuery->whereIn('branch_id', $branchIds);
        });
    }

    private function assertProductAllowed(?int $productId): void
    {
        abort_unless($productId, 422, 'الصنف غير صحيح.');

        $exists = Product::query()
            ->whereKey($productId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'الصنف غير صحيح أو غير نشط.');
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
            'المستودع غير صحيح أو غير نشط أو ليس ضمن فروعك.'
        );
    }

    private function activeProducts()
    {
        /*
            الأصناف عامة داخل النسخة الحالية.
            حركة المخزون نفسها تتقيد بالمستودعات المسموحة للمستخدم.
        */
        return Product::query()
            ->where('is_active', true)
            ->orderBy('product_name_ar')
            ->get();
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

    public function index(Request $request)
    {
        abort_unless(auth()->user()?->can('inventory_movement_reports.view'), 403);

        $warehouses = $this->activeWarehouses();

        $productId = $request->product_id;
        $warehouseId = $request->warehouse_id;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        $transactions = collect();
        $selectedProduct = null;
        $selectedWarehouse = null;

        /*
            لا نعرض التقرير إلا إذا اختار المستخدم صنف.
        */
        if ($productId) {
            $data = $request->validate([
                'product_id' => [
                    'required',
                    'integer',
                    Rule::exists('products', 'id'),
                ],
                'warehouse_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('warehouses', 'id'),
                ],
                'from_date' => ['nullable', 'date'],
                'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            ]);

            $productId = (int) $data['product_id'];
            $warehouseId = isset($data['warehouse_id']) && $data['warehouse_id'] !== ''
                ? (int) $data['warehouse_id']
                : null;

            $fromDate = $data['from_date'] ?? null;
            $toDate = $data['to_date'] ?? null;

            $this->assertProductAllowed($productId);
            $this->assertWarehouseAllowed($warehouseId);

            $selectedProduct = Product::query()->findOrFail($productId);

            if ($warehouseId) {
                $selectedWarehouse = Warehouse::query()->findOrFail($warehouseId);
            }

            $query = InventoryTransaction::query()
                ->with([
                    'product',
                    'warehouse',
                    'productUnit.unit',
                    'user',
                ])
                ->where('product_id', $productId);

            /*
                تطبيق نطاق الفروع:
                - موظف الفرع: حركات مستودعات فرعه فقط.
                - مدير الشركة: حركات مستودعات فروع شركته.
                - master/system_admin: الكل.
            */
            $this->applyInventoryTransactionScope($query);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $transactions = $query
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();
        }

        return view('inventory-movements.index', compact(
            'warehouses',
            'transactions',
            'selectedProduct',
            'selectedWarehouse',
            'productId',
            'warehouseId',
            'fromDate',
            'toDate'
        ));
    }
}