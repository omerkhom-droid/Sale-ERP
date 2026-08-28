<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Services\CustomerStatementService;
use Illuminate\Http\Request;

class CustomerStatementController extends Controller
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
            return;
        }

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            abort_unless(
                Branch::query()
                    ->whereKey($branchId)
                    ->where('is_active', true)
                    ->exists(),
                403,
                'الفرع غير صحيح أو غير نشط.'
            );

            return;
        }

        abort_unless(
            in_array((int) $branchId, $branchIds, true),
            403,
            'لا تملك صلاحية عرض بيانات هذا الفرع.'
        );

        abort_unless(
            Branch::query()
                ->whereKey($branchId)
                ->where('is_active', true)
                ->exists(),
            403,
            'الفرع غير صحيح أو غير نشط.'
        );
    }

    private function assertCustomerAllowed(?int $customerId): void
    {
        if (! $customerId) {
            return;
        }

        /*
            العملاء عامّون داخل النسخة الحالية.
            كشف الحساب نفسه يتم تقييده بالفرع عن طريق branch_id المرسل للخدمة.
        */
        $exists = Customer::query()
            ->whereKey($customerId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'العميل غير صحيح أو غير نشط.');
    }

    private function assertCostCenterAllowed(?int $costCenterId): void
    {
        if (! $costCenterId) {
            return;
        }

        /*
            مراكز التكلفة عامة داخل النسخة الحالية.
        */
        $exists = CostCenter::query()
            ->whereKey($costCenterId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'مركز التكلفة غير صحيح أو غير نشط.');
    }

    private function customers()
    {
        /*
            العملاء عامّون داخل النسخة الحالية.
            لا نفلتر العملاء بالفرع؛ نفلتر حركة الكشف بالفرع.
        */
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('customer_name')
            ->get();
    }

    private function branches()
    {
        return $this->applyBranchScope(
                Branch::query()->where('is_active', true)
            )
            ->orderBy('branch_name')
            ->get();
    }

    private function costCenters()
    {
        /*
            مراكز التكلفة عامة داخل النسخة الحالية.
        */
        return CostCenter::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function index(Request $request, CustomerStatementService $service)
    {
        abort_unless(auth()->user()?->can('customer_statements.view'), 403);

        $data = $request->validate([
            'customer_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
        ]);

        $customerId = $data['customer_id'] ?? null;
        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? now()->toDateString();
        $branchId = $data['branch_id'] ?? null;
        $costCenterId = $data['cost_center_id'] ?? null;

        $customerId = $customerId !== null && $customerId !== ''
            ? (int) $customerId
            : null;

        $branchId = $branchId !== null && $branchId !== ''
            ? (int) $branchId
            : null;

        $costCenterId = $costCenterId !== null && $costCenterId !== ''
            ? (int) $costCenterId
            : null;

        /*
            branch_admin / user:
            لا نعتمد الفرع القادم من الفلتر.
            نثبت الكشف على فرع المستخدم.
        */
        if (! $this->actorCanSeeAllCompanyBranches()) {
            $userBranchId = auth()->user()?->branch_id;

            if (! $userBranchId && $customerId) {
                abort(403, 'لا يوجد فرع مرتبط بالمستخدم الحالي.');
            }

            $branchId = $userBranchId ? (int) $userBranchId : null;
        }

        /*
            company_owner / company_admin:
            إذا اختار فرع، يجب أن يكون من فروع شركته.
            إذا لم يختر فرع، يعرض كل فروع الشركة حسب منطق الخدمة/النظام الحالي.
        */
        $this->assertBranchAllowed($branchId);
        $this->assertCustomerAllowed($customerId);
        $this->assertCostCenterAllowed($costCenterId);

        $customers = $this->customers();
        $branches = $this->branches();
        $costCenters = $this->costCenters();

        $statement = null;

        if ($customerId) {
            $statement = $service->build(
                customerId: $customerId,
                fromDate: $dateFrom,
                toDate: $dateTo,
                branchId: $branchId,
                costCenterId: $costCenterId
            );
        }

        return view('customer-statements.index', compact(
            'customers',
            'branches',
            'costCenters',
            'customerId',
            'dateFrom',
            'dateTo',
            'branchId',
            'costCenterId',
            'statement'
        ));
    }
}