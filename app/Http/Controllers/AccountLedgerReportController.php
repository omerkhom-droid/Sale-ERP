<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Services\AccountLedgerReportService;
use Illuminate\Http\Request;

class AccountLedgerReportController extends Controller
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

    private function assertAccountAllowed(?int $accountId): void
    {
        if (! $accountId) {
            return;
        }

        /*
            الحسابات عامة داخل النسخة الحالية.
            كشف الحساب نفسه يتم تقييده بالفرع عن طريق branch_id المرسل للخدمة.
        */
        $exists = Account::query()
            ->whereKey($accountId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 422, 'الحساب غير صحيح أو غير نشط.');
    }

    private function accounts()
    {
        return Account::query()
            ->where('is_active', true)
            ->orderBy('account_code')
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
        return CostCenter::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function index(Request $request, AccountLedgerReportService $service)
    {
        abort_unless(auth()->user()?->can('account_ledger_reports.view'), 403);

        $data = $request->validate([
            'account_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
        ]);

        $accountId = $data['account_id'] ?? null;
        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? now()->toDateString();
        $branchId = $data['branch_id'] ?? null;
        $costCenterId = $data['cost_center_id'] ?? null;

        $accountId = $accountId !== null && $accountId !== ''
            ? (int) $accountId
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

            if (! $userBranchId && $accountId) {
                abort(403, 'لا يوجد فرع مرتبط بالمستخدم الحالي.');
            }

            $branchId = $userBranchId ? (int) $userBranchId : null;
        }

        /*
            company_owner / company_admin:
            إذا اختار فرع، يجب أن يكون من فروع شركته.
            إذا لم يختر فرع، يرسل null للخدمة ويعرض كل فروع الشركة حسب منطق الخدمة.

            master / system_admin:
            إذا لم يختر فرع، يرسل null ويعرض الكل.
        */
        $this->assertAccountAllowed($accountId);
        $this->assertBranchAllowed($branchId);
        $this->assertCostCenterAllowed($costCenterId);

        $accounts = $this->accounts();
        $branches = $this->branches();
        $costCenters = $this->costCenters();

        $report = null;

        if ($accountId) {
            $report = $service->getReport(
                accountId: $accountId,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                branchId: $branchId,
                costCenterId: $costCenterId
            );
        }

        return view('account-ledger-reports.index', compact(
            'accounts',
            'branches',
            'costCenters',
            'accountId',
            'dateFrom',
            'dateTo',
            'branchId',
            'costCenterId',
            'report'
        ));
    }
}