<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class BrandController extends Controller
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
            && Schema::hasColumn('brands', 'company_id')
            && ! empty($user->company_id)
        ) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    private function assertBrandAccess(Brand $brand): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($this->actorIsSuperAdmin()) {
            return;
        }

        if (
            Schema::hasColumn('brands', 'company_id')
            && ! empty($user->company_id)
            && (int) $brand->company_id !== (int) $user->company_id
        ) {
            abort(403, 'لا تملك صلاحية الوصول إلى هذه العلامة التجارية.');
        }
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('brands.view'), 403);

        return view('brand.index');
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('brands.view'), 403);

        $user = auth()->user();

        $brands = $this->applyCompanyScope(
                Brand::query()
            )
            ->latest();

        return DataTables::of($brands)
            ->addIndexColumn()

            ->addColumn('status', function ($brand) {
                return $brand->is_active
                    ? '<span class="badge bg-success">نشط</span>'
                    : '<span class="badge bg-danger">غير نشط</span>';
            })

            ->addColumn('edit', function ($brand) use ($user) {
                if (! $user || ! $user->can('brands.edit')) {
                    return '<span class="text-muted">-</span>';
                }

                return '
                    <button type="button"
                            class="btn btn-sm btn-primary editBtn"
                            data-id="' . $brand->id . '">
                        تعديل
                    </button>
                ';
            })

            ->addColumn('delete', function ($brand) use ($user) {
                if (! $user || ! $user->can('brands.delete')) {
                    return '<span class="text-muted">-</span>';
                }

                return '
                    <button type="button"
                            class="btn btn-sm btn-danger deleteBtn"
                            data-id="' . $brand->id . '">
                        حذف
                    </button>
                ';
            })

            ->rawColumns(['status', 'edit', 'delete'])

            ->make(true);
    }

    public function store(StoreBrandRequest $request)
    {
        abort_unless(auth()->user()?->can('brands.create'), 403);

        $data = $request->validated();

        if (
            Schema::hasColumn('brands', 'company_id')
            && ! empty(auth()->user()?->company_id)
        ) {
            $data['company_id'] = auth()->user()->company_id;
        }

        Brand::create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة العلامة التجارية بنجاح',
        ]);
    }

    public function edit(Brand $brand)
    {
        abort_unless(auth()->user()?->can('brands.edit'), 403);

        $this->assertBrandAccess($brand);

        return response()->json($brand);
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        abort_unless(auth()->user()?->can('brands.edit'), 403);

        $this->assertBrandAccess($brand);

        $data = $request->validated();

        unset($data['company_id']);

        $brand->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل العلامة التجارية بنجاح',
        ]);
    }

    public function destroy(Brand $brand)
    {
        abort_unless(auth()->user()?->can('brands.delete'), 403);

        $this->assertBrandAccess($brand);

        $brand->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف العلامة التجارية بنجاح',
        ]);
    }
}