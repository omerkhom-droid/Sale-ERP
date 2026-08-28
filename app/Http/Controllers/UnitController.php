<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    private function actorIsSuperAdmin(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return (bool) ($user->is_super_admin ?? false)
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || $user->hasRole('Super Admin');
    }

    private function applyCompanyScope($query)
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (
            ! $this->actorIsSuperAdmin()
            && Schema::hasColumn('units', 'company_id')
            && ! empty($user->company_id)
        ) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    private function assertUnitAccess(Unit $unit): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($this->actorIsSuperAdmin()) {
            return;
        }

        if (
            Schema::hasColumn('units', 'company_id')
            && ! empty($user->company_id)
            && (int) $unit->company_id !== (int) $user->company_id
        ) {
            abort(403, 'لا تملك صلاحية الوصول إلى هذه الوحدة.');
        }
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('units.view'), 403);

        return view('units.index');
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('units.view'), 403);

        $user = auth()->user();

        $units = $this->applyCompanyScope(
                Unit::query()
            )
            ->latest();

        return DataTables::of($units)
            ->addIndexColumn()

            ->addColumn('status', function ($unit) {
                return $unit->is_active
                    ? '<span class="badge bg-success">نشط</span>'
                    : '<span class="badge bg-danger">غير نشط</span>';
            })

            ->addColumn('edit', function ($unit) use ($user) {
                if (! $user || ! $user->can('units.edit')) {
                    return '<span class="text-muted">-</span>';
                }

                return '
                    <button type="button"
                            class="btn btn-sm btn-primary editBtn"
                            data-id="' . $unit->id . '">
                        تعديل
                    </button>
                ';
            })

            ->addColumn('delete', function ($unit) use ($user) {
                if (! $user || ! $user->can('units.delete')) {
                    return '<span class="text-muted">-</span>';
                }

                return '
                    <button type="button"
                            class="btn btn-sm btn-danger deleteBtn"
                            data-id="' . $unit->id . '">
                        حذف
                    </button>
                ';
            })

            ->rawColumns(['status', 'edit', 'delete'])

            ->make(true);
    }

    public function store(StoreUnitRequest $request)
    {
        abort_unless(auth()->user()?->can('units.create'), 403);

        $data = $request->validated();

        if (
            Schema::hasColumn('units', 'company_id')
            && ! empty(auth()->user()?->company_id)
        ) {
            $data['company_id'] = auth()->user()->company_id;
        }

        Unit::create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة الوحدة بنجاح',
        ]);
    }

    public function edit(Unit $unit)
    {
        abort_unless(auth()->user()?->can('units.edit'), 403);

        $this->assertUnitAccess($unit);

        return response()->json($unit);
    }

    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        abort_unless(auth()->user()?->can('units.edit'), 403);

        $this->assertUnitAccess($unit);

        $data = $request->validated();

        unset($data['company_id']);

        $unit->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل الوحدة بنجاح',
        ]);
    }

    public function destroy(Unit $unit)
    {
        abort_unless(auth()->user()?->can('units.delete'), 403);

        $this->assertUnitAccess($unit);

        $unit->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الوحدة بنجاح',
        ]);
    }
}