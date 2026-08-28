<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
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
            && Schema::hasColumn('suppliers', 'company_id')
            && ! empty($user->company_id)
        ) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    private function assertSupplierAccess(Supplier $supplier): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($this->actorIsSuperAdmin()) {
            return;
        }

        if (
            Schema::hasColumn('suppliers', 'company_id')
            && ! empty($user->company_id)
            && (int) $supplier->company_id !== (int) $user->company_id
        ) {
            abort(403, 'لا تملك صلاحية الوصول إلى هذا المورد.');
        }
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('suppliers.view'), 403);

        return view('suppliers.index');
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('suppliers.view'), 403);

        $user = auth()->user();

        $suppliers = $this->applyCompanyScope(
                Supplier::query()
            )
            ->latest();

        return DataTables::of($suppliers)
            ->addIndexColumn()

            ->addColumn('supplier_type_text', function ($row) {
                return $row->supplier_type === 'company'
                    ? '<span class="badge bg-primary">شركة</span>'
                    : '<span class="badge bg-info">فرد</span>';
            })

            ->addColumn('status', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">نشط</span>'
                    : '<span class="badge bg-danger">غير نشط</span>';
            })

            ->addColumn('actions', function ($row) use ($user) {
                $buttons = '<div class="d-flex justify-content-center gap-1 flex-wrap">';

                if ($user && $user->can('suppliers.edit')) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-primary editBtn"
                                data-id="' . $row->id . '">
                            تعديل
                        </button>
                    ';
                }

                if ($user && $user->can('suppliers.delete')) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-danger deleteBtn"
                                data-id="' . $row->id . '">
                            حذف
                        </button>
                    ';
                }

                $buttons .= '</div>';

                return $buttons === '<div class="d-flex justify-content-center gap-1 flex-wrap"></div>'
                    ? '<span class="text-muted">-</span>'
                    : $buttons;
            })

            ->rawColumns([
                'supplier_type_text',
                'status',
                'actions',
            ])

            ->make(true);
    }

    public function store(StoreSupplierRequest $request)
    {
        abort_unless(auth()->user()?->can('suppliers.create'), 403);

        $data = $request->validated();

        if (
            Schema::hasColumn('suppliers', 'company_id')
            && ! empty(auth()->user()?->company_id)
        ) {
            $data['company_id'] = auth()->user()->company_id;
        }

        Supplier::create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة المورد بنجاح',
        ]);
    }

    public function edit(Supplier $supplier)
    {
        abort_unless(auth()->user()?->can('suppliers.edit'), 403);

        $this->assertSupplierAccess($supplier);

        return response()->json($supplier);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        abort_unless(auth()->user()?->can('suppliers.edit'), 403);

        $this->assertSupplierAccess($supplier);

        $data = $request->validated();

        unset($data['company_id']);

        $supplier->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل المورد بنجاح',
        ]);
    }

    public function destroy(Supplier $supplier)
    {
        abort_unless(auth()->user()?->can('suppliers.delete'), 403);

        $this->assertSupplierAccess($supplier);

        $supplier->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف المورد بنجاح',
        ]);
    }
}