<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\OpeningBalance;
use App\Models\Supplier;
use App\Services\OpeningBalanceService;
use Exception;
use Illuminate\Http\Request;

class OpeningBalanceController extends Controller
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

    private function applyOpeningBalanceScope($query)
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

    private function assertOpeningBalanceAccess(OpeningBalance $openingBalance): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array((int) $openingBalance->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى مستند أرصدة افتتاحية من فرع آخر.'
        );
    }

    private function assertBranchAllowed(?int $branchId): void
    {
        abort_unless($branchId, 403, 'الفرع غير صحيح.');

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
            'لا تملك صلاحية استخدام هذا الفرع.'
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

        $exists = CostCenter::query()
            ->whereKey($costCenterId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'مركز التكلفة غير صحيح أو غير نشط.');
    }

    private function assertAccountAllowed(?int $accountId): void
    {
        if (! $accountId) {
            abort(422, 'يجب اختيار الحساب في سطر الحساب.');
        }

        $exists = Account::query()
            ->whereKey($accountId)
            ->where('is_active', true)
            ->where('is_group', false)
            ->exists();

        abort_unless($exists, 403, 'الحساب غير صحيح أو غير نشط أو حساب تجميعي.');
    }

    private function assertCustomerAllowed(?int $customerId): void
    {
        if (! $customerId) {
            abort(422, 'يجب اختيار العميل في سطر العميل.');
        }

        $exists = Customer::query()
            ->whereKey($customerId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'العميل غير صحيح أو غير نشط.');
    }

    private function assertSupplierAllowed(?int $supplierId): void
    {
        if (! $supplierId) {
            abort(422, 'يجب اختيار المورد في سطر المورد.');
        }

        $exists = Supplier::query()
            ->whereKey($supplierId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'المورد غير صحيح أو غير نشط.');
    }

    private function assertLineAmounts(array $line): void
    {
        $debit = (float) ($line['debit'] ?? 0);
        $credit = (float) ($line['credit'] ?? 0);

        if ($debit <= 0 && $credit <= 0) {
            abort(422, 'كل سطر يجب أن يحتوي على مبلغ مدين أو دائن.');
        }

        if ($debit > 0 && $credit > 0) {
            abort(422, 'لا يمكن أن يكون السطر مدين ودائن في نفس الوقت.');
        }
    }

    private function prepareSecureOpeningBalanceData(array $data): array
    {
        $user = auth()->user();

        abort_unless($user, 403);

        /*
            branch_admin / user:
            لا نعتمد الفرع القادم من الفورم.
            نثبت فرع المستند من المستخدم.
        */
        if (! $this->actorCanSeeAllCompanyBranches()) {
            $data['branch_id'] = $user->branch_id;
        }

        $defaultBranchId = (int) ($data['branch_id'] ?? 0);

        /*
            فرع رأس المستند مطلوب.
            للإدمن يكون الفرع المختار هو فرع المستند الرئيسي.
            أما السطور فيمكن أن يكون لها نفس الفرع أو فرع آخر ضمن نطاقه.
        */
        $this->assertBranchAllowed($defaultBranchId);

        $data['branch_id'] = $defaultBranchId;
        $data['created_by'] = auth()->id();

        foreach ($data['lines'] as $index => &$line) {
            $lineType = $line['line_type'] ?? null;

            /*
                branch_admin / user:
                كل السطور تكون على فرعه فقط.

                company_owner / company_admin / master / system_admin:
                السطر يستخدم فرعه إن وجد، وإلا يستخدم فرع رأس المستند.
            */
            $lineBranchId = (int) ($line['branch_id'] ?? 0);

            if (! $this->actorCanSeeAllCompanyBranches()) {
                $lineBranchId = (int) $user->branch_id;
            }

            if (! $lineBranchId) {
                $lineBranchId = $defaultBranchId;
            }

            abort_unless(
                $lineBranchId,
                422,
                'يجب اختيار الفرع في السطر أو في رأس المستند.'
            );

            $this->assertBranchAllowed($lineBranchId);

            $line['branch_id'] = $lineBranchId;

            $this->assertCostCenterAllowed((int) ($line['cost_center_id'] ?? 0));

            $this->assertLineAmounts($line);

            if ($lineType === 'account') {
                $this->assertAccountAllowed((int) ($line['account_id'] ?? 0));

                $line['customer_id'] = null;
                $line['supplier_id'] = null;
            } elseif ($lineType === 'customer') {
                $this->assertCustomerAllowed((int) ($line['customer_id'] ?? 0));

                $line['account_id'] = null;
                $line['supplier_id'] = null;
            } elseif ($lineType === 'supplier') {
                $this->assertSupplierAllowed((int) ($line['supplier_id'] ?? 0));

                $line['account_id'] = null;
                $line['customer_id'] = null;
            } else {
                abort(422, 'نوع السطر غير صحيح.');
            }
        }

        unset($line);

        return $data;
    }

    private function activeBranches()
    {
        return $this->applyBranchScope(
                Branch::query()->where('is_active', true)
            )
            ->orderBy('branch_name')
            ->get();
    }

    private function activeCostCenters()
    {
        /*
            مراكز التكلفة عامة داخل النسخة الحالية.
        */
        return CostCenter::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    private function activeAccounts()
    {
        /*
            الحسابات عامة داخل النسخة الحالية.
            لا نربط الحساب بصلاحية فرع.
        */
        return Account::query()
            ->where('is_active', true)
            ->where('is_group', false)
            ->orderBy('account_code')
            ->get();
    }

    private function activeCustomers()
    {
        /*
            العملاء عامّون داخل النسخة الحالية.
        */
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('customer_name')
            ->get();
    }

    private function activeSuppliers()
    {
        /*
            الموردون عامّون داخل النسخة الحالية.
        */
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('supplier_name')
            ->get();
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('opening_balances.view'), 403);

        $openingBalances = OpeningBalance::query()
            ->with([
                'branch',
                'creator',
            ])
            ->withCount('lines');

        $this->applyOpeningBalanceScope($openingBalances);

        $openingBalances = $openingBalances
            ->latest('id')
            ->paginate(20);

        return view('opening-balances.index', compact('openingBalances'));
    }

    public function create()
    {
        abort_unless(auth()->user()?->can('opening_balances.create'), 403);

        $branches = $this->activeBranches();
        $costCenters = $this->activeCostCenters();
        $accounts = $this->activeAccounts();
        $customers = $this->activeCustomers();
        $suppliers = $this->activeSuppliers();

        return view('opening-balances.create', compact(
            'branches',
            'costCenters',
            'accounts',
            'customers',
            'suppliers'
        ));
    }

    public function store(Request $request, OpeningBalanceService $service)
    {
        abort_unless(auth()->user()?->can('opening_balances.create'), 403);

        $data = $request->validate([
            'opening_date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],

            'lines.*.branch_id' => ['nullable', 'integer'],
            'lines.*.cost_center_id' => ['nullable', 'integer'],

            'lines.*.line_type' => ['required', 'in:account,customer,supplier'],

            'lines.*.account_id' => ['nullable', 'integer'],
            'lines.*.customer_id' => ['nullable', 'integer'],
            'lines.*.supplier_id' => ['nullable', 'integer'],

            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],

            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $data = $this->prepareSecureOpeningBalanceData($data);

        try {
            $openingBalance = $service->store($data);

            return redirect()
                ->route('opening-balances.show', $openingBalance)
                ->with('success', 'تم حفظ الأرصدة الافتتاحية كمسودة بنجاح.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(OpeningBalance $openingBalance)
    {
        abort_unless(auth()->user()?->can('opening_balances.view'), 403);

        $this->assertOpeningBalanceAccess($openingBalance);

        $openingBalance->load([
            'branch',
            'lines.branch',
            'lines.costCenter',
            'lines.account',
            'lines.customer',
            'lines.supplier',
            'creator',
            'poster',
            'canceller',
        ]);

        return view('opening-balances.show', compact('openingBalance'));
    }

    public function post(OpeningBalance $openingBalance, OpeningBalanceService $service)
    {
        abort_unless(auth()->user()?->can('opening_balances.post'), 403);

        $this->assertOpeningBalanceAccess($openingBalance);

        if ($openingBalance->status !== 'draft') {
            return back()->with('error', 'لا يمكن ترحيل مستند غير مسودة.');
        }

        try {
            $service->post($openingBalance);

            return redirect()
                ->route('opening-balances.show', $openingBalance)
                ->with('success', 'تم ترحيل الأرصدة الافتتاحية وإنشاء القيد المحاسبي بنجاح.');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(
        Request $request,
        OpeningBalance $openingBalance,
        OpeningBalanceService $service
    ) {
        abort_unless(auth()->user()?->can('opening_balances.cancel'), 403);

        $this->assertOpeningBalanceAccess($openingBalance);

        if ($openingBalance->status === 'cancelled') {
            return back()->with('error', 'المستند ملغي مسبقًا.');
        }

        $data = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $service->cancel(
                $openingBalance,
                $data['cancellation_reason'] ?? null
            );

            return redirect()
                ->route('opening-balances.show', $openingBalance)
                ->with('success', 'تم إلغاء مستند الأرصدة الافتتاحية وعكس القيد المحاسبي بنجاح.');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}