<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CostCenter;
use App\Services\SupplierBalanceReportService;
use Illuminate\Http\Request;

class SupplierBalanceReportController extends Controller
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

    private function assertCostCenterAllowed(?int $costCenterId): void
    {
        if (! $costCenterId) {
            return;
        }

        /*
            مراكز التكلفة عامة داخل النسخة الحالية.
            التصفية الفعلية تتم داخل التقرير حسب cost_center_id.
        */
        $exists = CostCenter::query()
            ->whereKey($costCenterId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'مركز التكلفة غير صحيح أو غير نشط.');
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
        return CostCenter::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function index(Request $request, SupplierBalanceReportService $service)
    {
        abort_unless(auth()->user()?->can('supplier_balance_reports.view'), 403);

        $data = $request->validate([
            'date_to' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
        ]);

        $dateTo = $data['date_to'] ?? now()->toDateString();

        $branchId = $data['branch_id'] ?? null;
        $costCenterId = $data['cost_center_id'] ?? null;

        $branchId = $branchId !== null && $branchId !== ''
            ? (int) $branchId
            : null;

        $costCenterId = $costCenterId !== null && $costCenterId !== ''
            ? (int) $costCenterId
            : null;

        /*
            branch_admin / user:
            لا نعتمد الفرع القادم من الفلتر.
            نثبت التقرير على فرع المستخدم.
        */
        if (! $this->actorCanSeeAllCompanyBranches()) {
            $userBranchId = auth()->user()?->branch_id;

            if (! $userBranchId) {
                abort(403, 'لا يوجد فرع مرتبط بالمستخدم الحالي.');
            }

            $branchId = (int) $userBranchId;
        }

        /*
            company_owner / company_admin:
            إذا اختار فرع، يجب أن يكون من فروع شركته.
            إذا لم يختر فرع، يرسل null للخدمة ويعرض كل فروع الشركة حسب منطق الخدمة.

            master / system_admin:
            إذا لم يختر فرع، يرسل null ويعرض الكل.
        */
        $this->assertBranchAllowed($branchId);
        $this->assertCostCenterAllowed($costCenterId);

        $report = $service->getReport(
            dateTo: $dateTo,
            branchId: $branchId,
            costCenterId: $costCenterId
        );

        $branches = $this->branches();
        $costCenters = $this->costCenters();

        return view('supplier-balance-reports.index', compact(
            'report',
            'dateTo',
            'branches',
            'costCenters',
            'branchId',
            'costCenterId'
        ));
    }
}