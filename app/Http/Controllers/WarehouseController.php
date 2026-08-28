<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;
use Yajra\DataTables\Facades\DataTables;

class WarehouseController extends Controller
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

    private function actorCanSeeAllCompanyBranches(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
            'company_owner',
            'company_admin',
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
            يشوف كل الفروع في النظام.
        */
        if ($this->actorCanSeeAllCompanies()) {
            return null;
        }

        /*
            company_owner / company_admin:
            يشوف فروع شركته فقط.
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
            يشوف فرعه فقط.
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

    private function applyBranchScope($query)
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('id', $branchIds);
    }

    private function assertBranchAllowed(?int $branchId): void
    {
        if (! $branchId) {
            abort(403, 'الفرع غير صحيح.');
        }

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            abort_unless(
                Branch::query()->whereKey($branchId)->exists(),
                403,
                'الفرع غير صحيح.'
            );

            return;
        }

        abort_unless(
            in_array((int) $branchId, $branchIds, true),
            403,
            'لا تملك صلاحية استخدام هذا الفرع.'
        );
    }

    private function assertWarehouseAllowed(Warehouse $warehouse): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array((int) $warehouse->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى هذا المستودع.'
        );
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('warehouses.view'), 403);

        $branches = $this->applyBranchScope(
            Branch::query()->where('is_active', true)
        )
            ->orderBy('branch_name')
            ->get();

        return view('warehouses.index', compact('branches'));
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('warehouses.view'), 403);

        $user = auth()->user();

        $warehouses = $this->applyWarehouseScope(
            Warehouse::query()->with('branch')
        )->latest();

        return DataTables::of($warehouses)
            ->addIndexColumn()

            ->addColumn('branch_name', function ($warehouse) {
                return e(
                    $warehouse->branch?->branch_name_ar
                    ?? $warehouse->branch?->branch_name
                    ?? $warehouse->branch?->name
                    ?? '-'
                );
            })

            ->addColumn('status', function ($warehouse) {
                return $warehouse->is_active
                    ? '<span class="badge bg-success">نشط</span>'
                    : '<span class="badge bg-danger">غير نشط</span>';
            })

            ->addColumn('edit', function ($warehouse) use ($user) {
                if (! $user || ! $user->can('warehouses.edit')) {
                    return '<span class="text-muted">-</span>';
                }

                if (! $this->warehouseInActorScope($warehouse)) {
                    return '<span class="text-muted">-</span>';
                }

                return '
                    <button type="button"
                            class="btn btn-sm btn-primary editBtn"
                            data-id="' . (int) $warehouse->id . '">
                        تعديل
                    </button>
                ';
            })

            ->addColumn('delete', function ($warehouse) use ($user) {
                if (! $user || ! $user->can('warehouses.delete')) {
                    return '<span class="text-muted">-</span>';
                }

                if (! $this->warehouseInActorScope($warehouse)) {
                    return '<span class="text-muted">-</span>';
                }

                return '
                    <button type="button"
                            class="btn btn-sm btn-danger deleteBtn"
                            data-id="' . (int) $warehouse->id . '">
                        حذف
                    </button>
                ';
            })

            ->rawColumns(['status', 'edit', 'delete'])
            ->make(true);
    }

    private function warehouseInActorScope(Warehouse $warehouse): bool
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return true;
        }

        return in_array((int) $warehouse->branch_id, $branchIds, true);
    }

    public function store(StoreWarehouseRequest $request)
    {
        abort_unless(auth()->user()?->can('warehouses.create'), 403);

        $data = $request->validated();

        $this->assertBranchAllowed((int) ($data['branch_id'] ?? 0));

        Warehouse::create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة المستودع بنجاح',
        ]);
    }

    public function edit(Warehouse $warehouse)
    {
        abort_unless(auth()->user()?->can('warehouses.edit'), 403);

        $this->assertWarehouseAllowed($warehouse);

        return response()->json($warehouse);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        abort_unless(auth()->user()?->can('warehouses.edit'), 403);

        $this->assertWarehouseAllowed($warehouse);

        $data = $request->validated();

        $this->assertBranchAllowed((int) ($data['branch_id'] ?? 0));

        $warehouse->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل المستودع بنجاح',
        ]);
    }

    public function destroy(Warehouse $warehouse)
    {
        abort_unless(auth()->user()?->can('warehouses.delete'), 403);

        $this->assertWarehouseAllowed($warehouse);

        try {
            $warehouse->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستودع بنجاح',
            ]);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المستودع لوجود عمليات أو فواتير أو أرصدة مرتبطة به.',
            ], 422);
        }
    }
}