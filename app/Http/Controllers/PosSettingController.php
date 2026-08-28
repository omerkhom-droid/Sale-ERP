<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\PosSetting;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class PosSettingController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->can('pos.settings'), 403);

        $branchesQuery = Branch::query()->orderBy('branch_name');
        $this->applyBranchScope($branchesQuery, 'id');

        $branches = $branchesQuery->get();

        if ($branches->isEmpty()) {
            return view('pos.settings.index', [
                'branches' => $branches,
                'selectedBranch' => null,
                'setting' => new PosSetting(),
                'warehouses' => collect(),
                'customers' => collect(),
            ]);
        }

        $selectedBranchId = (int) ($request->branch_id ?: $branches->first()->id);

        if (! $branches->contains('id', $selectedBranchId)) {
            abort(403, 'لا تملك صلاحية الوصول لهذا الفرع.');
        }

        $selectedBranch = $branches->firstWhere('id', $selectedBranchId);

        $setting = PosSetting::query()
            ->where('branch_id', $selectedBranchId)
            ->first();

        if (! $setting) {
            $setting = new PosSetting([
                'branch_id' => $selectedBranchId,
                'tax_rate' => 15,
                'auto_print_receipt' => true,
                'receipt_copies' => 1,
                'show_product_images' => true,
            ]);
        }

        $warehouses = Warehouse::query()
            ->where('branch_id', $selectedBranchId)
            ->where('is_active', true)
            ->orderBy('warehouse_name')
            ->get();

        $customers = Customer::query()
            ->orderBy('customer_name')
            ->limit(300)
            ->get();

        return view('pos.settings.index', compact(
            'branches',
            'selectedBranch',
            'setting',
            'warehouses',
            'customers'
        ));
    }

    public function save(Request $request)
    {
        abort_unless(auth()->user()?->can('pos.settings'), 403);

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'default_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'default_customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'auto_print_receipt' => ['nullable', 'boolean'],
            'receipt_copies' => ['required', 'integer', 'min:1', 'max:5'],
            'show_product_images' => ['nullable', 'boolean'],
            'receipt_title' => ['nullable', 'string', 'max:255'],
            'receipt_footer' => ['nullable', 'string', 'max:1000'],
        ]);

        $branchId = (int) $data['branch_id'];

        $this->assertBranchAccess($branchId);

        if (! empty($data['default_warehouse_id'])) {
            $warehouseAllowed = Warehouse::query()
                ->whereKey((int) $data['default_warehouse_id'])
                ->where('branch_id', $branchId)
                ->exists();

            if (! $warehouseAllowed) {
                return back()
                    ->withErrors(['default_warehouse_id' => 'المستودع الافتراضي لا يتبع الفرع المحدد.'])
                    ->withInput();
            }
        }

        PosSetting::updateOrCreate(
            ['branch_id' => $branchId],
            [
                'default_warehouse_id' => $data['default_warehouse_id'] ?? null,
                'default_customer_id' => $data['default_customer_id'] ?? null,
                'tax_rate' => round((float) $data['tax_rate'], 2),
                'auto_print_receipt' => (bool) ($data['auto_print_receipt'] ?? false),
                'receipt_copies' => (int) $data['receipt_copies'],
                'show_product_images' => (bool) ($data['show_product_images'] ?? false),
                'receipt_title' => $data['receipt_title'] ?? null,
                'receipt_footer' => $data['receipt_footer'] ?? null,
            ]
        );

        return redirect()
            ->route('pos.settings.index', ['branch_id' => $branchId])
            ->with('success', 'تم حفظ إعدادات POS بنجاح.');
    }

    private function actorBranchIds(): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if (method_exists($user, 'canSeeAllCompanyBranches') && $user->canSeeAllCompanyBranches()) {
            return null;
        }

        if (! empty($user->branch_id)) {
            return [(int) $user->branch_id];
        }

        return [];
    }

    private function applyBranchScope($query, string $column = 'id'): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        if (empty($branchIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn($column, $branchIds);
    }

    private function assertBranchAccess(int $branchId): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array($branchId, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول لهذا الفرع.'
        );
    }
}