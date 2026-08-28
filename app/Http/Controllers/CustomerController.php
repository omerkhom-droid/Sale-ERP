<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
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
            && Schema::hasColumn('customers', 'company_id')
            && ! empty($user->company_id)
        ) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    private function assertCustomerAccess(Customer $customer): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($this->actorIsSuperAdmin()) {
            return;
        }

        if (
            Schema::hasColumn('customers', 'company_id')
            && ! empty($user->company_id)
            && (int) $customer->company_id !== (int) $user->company_id
        ) {
            abort(403, 'لا تملك صلاحية الوصول إلى هذا العميل.');
        }
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('customers.view'), 403);

        return view('customers.index');
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('customers.view'), 403);

        $user = auth()->user();

        $customers = $this->applyCompanyScope(
                Customer::query()
            )
            ->latest();

        return DataTables::of($customers)
            ->addIndexColumn()

            ->addColumn('customer_type_text', function ($row) {
                return $row->customer_type === 'company'
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

                if ($user && $user->can('customers.edit')) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-primary editBtn"
                                data-id="' . $row->id . '">
                            تعديل
                        </button>
                    ';
                }

                if ($user && $user->can('customers.delete')) {
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
                'customer_type_text',
                'status',
                'actions',
            ])

            ->make(true);
    }

    public function create()
    {
        abort(404);
    }

    public function store(StoreCustomerRequest $request)
    {
        abort_unless(auth()->user()?->can('customers.create'), 403);

        $data = $request->validated();

        if (
            Schema::hasColumn('customers', 'company_id')
            && ! empty(auth()->user()?->company_id)
        ) {
            $data['company_id'] = auth()->user()->company_id;
        }

        Customer::create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة العميل بنجاح',
        ]);
    }

    public function edit(Customer $customer)
    {
        abort_unless(auth()->user()?->can('customers.edit'), 403);

        $this->assertCustomerAccess($customer);

        return response()->json($customer);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        abort_unless(auth()->user()?->can('customers.edit'), 403);

        $this->assertCustomerAccess($customer);

        $data = $request->validated();

        unset($data['company_id']);

        $customer->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل العميل بنجاح',
        ]);
    }

    public function destroy(Customer $customer)
    {
        abort_unless(auth()->user()?->can('customers.delete'), 403);

        $this->assertCustomerAccess($customer);

        $customer->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف العميل بنجاح',
        ]);
    }
}