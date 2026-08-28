<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOpeningStockBalanceRequest;
use App\Models\Branch;
use App\Models\OpeningStockBalance;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\OpeningStockBalanceService;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;

class OpeningStockBalanceController extends Controller
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
            يرى كل فروع شركته.
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

    private function applyWarehouseScope($query, bool $onlyActive = false)
    {
        if ($onlyActive) {
            $query->where('is_active', true);
        }

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    private function assertProductAllowed(?int $productId): Product
    {
        abort_unless($productId, 403, 'الصنف غير صحيح.');

        $product = Product::query()
            ->whereKey($productId)
            ->where('is_active', true)
            ->first();

        abort_unless($product, 403, 'الصنف غير صحيح أو غير نشط.');

        return $product;
    }

    private function assertWarehouseAllowed(?int $warehouseId): Warehouse
    {
        abort_unless($warehouseId, 403, 'المستودع غير صحيح.');

        $query = Warehouse::query()
            ->with('branch')
            ->whereKey($warehouseId);

        $this->applyWarehouseScope($query, true);

        $warehouse = $query->first();

        abort_unless($warehouse, 403, 'لا تملك صلاحية استخدام هذا المستودع أو أن المستودع غير نشط.');

        return $warehouse;
    }

    private function assertProductUnitAllowed(array $data): void
    {
        if (
            empty($data['product_id'])
            || empty($data['product_unit_id'])
        ) {
            return;
        }

        $exists = DB::table('product_units')
            ->where('id', $data['product_unit_id'])
            ->where('product_id', $data['product_id'])
            ->exists();

        abort_unless($exists, 403, 'الوحدة المحددة لا تتبع هذا الصنف.');
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('opening_stock.view'), 403);

        /*
            المنتجات عامة داخل النظام.
            لا نفلترها حسب الشركة أو الفرع.
        */
        $productsCount = Product::query()
            ->where('is_active', true)
            ->count();

        /*
            المستودعات فقط حسب نطاق المستخدم.
        */
        $warehouses = $this->applyWarehouseScope(
                Warehouse::query()->with('branch'),
                true
            )
            ->orderBy('warehouse_name')
            ->get();

        return view('opening-stock.index', compact(
            'productsCount',
            'warehouses'
        ));
        
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('opening_stock.view'), 403);

        $balances = OpeningStockBalance::query()
            ->with([
                'product',
                'warehouse',
                'productUnit.unit',
            ])
            ->whereHas('warehouse', function ($query) {
                $this->applyWarehouseScope($query);
            })
            ->latest();

        return DataTables::of($balances)
            ->addIndexColumn()

            ->addColumn('product_name', function ($row) {
                return e(
                    $row->product?->product_name_ar
                    ?? $row->product?->product_name
                    ?? $row->product?->name
                    ?? '-'
                );
            })

            ->addColumn('warehouse_name', function ($row) {
                return e($row->warehouse?->warehouse_name ?? '-');
            })

            ->addColumn('unit_name', function ($row) {
                return e(
                    $row->productUnit?->unit?->unit_name_ar
                    ?? $row->productUnit?->unit?->unit_name
                    ?? $row->productUnit?->unit?->name
                    ?? '-'
                );
            })

            ->editColumn('quantity', function ($row) {
                return number_format((float) $row->quantity, 3);
            })

            ->editColumn('base_quantity', function ($row) {
                return number_format((float) $row->base_quantity, 3);
            })

            ->editColumn('unit_cost', function ($row) {
                return number_format((float) $row->unit_cost, 2);
            })

            ->editColumn('total_cost', function ($row) {
                return number_format((float) $row->total_cost, 2);
            })

            ->make(true);
    }

    public function store(
        StoreOpeningStockBalanceRequest $request,
        OpeningStockBalanceService $service
    ) {
        abort_unless(auth()->user()?->can('opening_stock.create'), 403);

        $data = $request->validated();

        /*
            المنتج عام، فقط نتأكد أنه موجود ونشط.
        */
        $this->assertProductAllowed((int) ($data['product_id'] ?? 0));

        /*
            المستودع هو الذي يحدد نطاق الفرع.
        */
        $warehouse = $this->assertWarehouseAllowed((int) ($data['warehouse_id'] ?? 0));

        /*
            التأكد أن الوحدة المختارة تتبع نفس المنتج.
        */
        $this->assertProductUnitAllowed($data);

        /*
            الرصيد الافتتاحي يتبع المستودع.
            والفرع يؤخذ من المستودع وليس من المستخدم.
        */
        $data['branch_id'] = $warehouse->branch_id;
        $data['created_by'] = auth()->id();

        /*
            إذا كان جدول opening_stock_balances عندك يحتوي company_id
            وخانة company_id موجودة في fillable داخل الموديل،
            فعّل السطر التالي.
        */
        // $data['company_id'] = $warehouse->branch?->company_id;

        $service->store($data);

        return response()->json([
            'status' => true,
            'message' => 'تم حفظ الرصيد الافتتاحي بنجاح',
        ]);
    }


    public function productUnits(Product $product): JsonResponse
    {
        if (! $product->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'الصنف غير نشط.',
            ], 404);
        }

        $units = $product->units()
            ->with('unit')
            ->orderByDesc('is_default')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $units->map(function ($productUnit) {
                return [
                    'id' => $productUnit->id,
                    'unit_name' => $productUnit->unit?->unit_name
                        ?? $productUnit->unit?->name
                        ?? '-',
                    'factor' => (float) ($productUnit->factor ?? 1),
                    'purchase_price' => (float) ($productUnit->purchase_price ?? 0),
                    'is_default' => (int) ($productUnit->is_default ?? 0),
                ];
            })->values(),
        ]);
    }
}