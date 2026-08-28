<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\ManualJournalEntry;
use App\Models\Supplier;
use App\Services\ManualJournalEntryService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ManualJournalEntryController extends Controller
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

    private function applyManualJournalScope($query)
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

    private function assertManualJournalAccess(ManualJournalEntry $manualJournalEntry): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array((int) $manualJournalEntry->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى قيد من فرع آخر.'
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

    private function assertJournalIsBalanced(array $lines): void
    {
        $totalDebit = collect($lines)->sum(function ($line) {
            return (float) ($line['debit'] ?? 0);
        });

        $totalCredit = collect($lines)->sum(function ($line) {
            return (float) ($line['credit'] ?? 0);
        });

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            abort(422, 'القيد غير متوازن. يجب أن يساوي إجمالي المدين إجمالي الدائن.');
        }
    }

    private function prepareSecureManualJournalData(array $data): array
    {
        $user = auth()->user();

        abort_unless($user, 403);

        /*
            branch_admin / user:
            لا نعتمد الفرع القادم من الفورم.
            نثبت فرع رأس القيد من المستخدم.
        */
        if (! $this->actorCanSeeAllCompanyBranches()) {
            $data['branch_id'] = $user->branch_id;
        }

        $defaultBranchId = (int) ($data['branch_id'] ?? 0);

        /*
            فرع رأس القيد مطلوب.
            للإدمن يكون الفرع المختار هو فرع القيد الرئيسي.
            والسطور يمكن أن تكون على نفس الفرع أو فروع أخرى ضمن نطاقه.
        */
        $this->assertBranchAllowed($defaultBranchId);

        $data['branch_id'] = $defaultBranchId;
        $data['created_by'] = auth()->id();

        foreach ($data['lines'] as &$line) {
            $lineType = $line['line_type'] ?? null;

            /*
                branch_admin / user:
                كل السطور تكون على فرعه فقط.

                company_owner / company_admin / master / system_admin:
                السطر يستخدم فرعه إن وجد، وإلا يستخدم فرع رأس القيد.
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
                'يجب اختيار الفرع في السطر أو في رأس القيد.'
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

        $this->assertJournalIsBalanced($data['lines']);

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
        abort_unless(auth()->user()?->can('manual_journal_entries.view'), 403);

        $manualJournalEntries = ManualJournalEntry::query()
            ->with([
                'branch',
                'creator',
            ])
            ->withCount('lines');

        $this->applyManualJournalScope($manualJournalEntries);

        $manualJournalEntries = $manualJournalEntries
            ->latest('id')
            ->paginate(20);

        return view('manual-journal-entries.index', compact('manualJournalEntries'));
    }

    public function fetch(Request $request)
    {
        abort_unless(auth()->user()?->can('manual_journal_entries.view'), 403);

        if (! $request->ajax()) {
            abort(404);
        }

        $user = auth()->user();

        $query = ManualJournalEntry::query()
            ->with([
                'branch',
                'creator',
            ])
            ->withCount('lines')
            ->withSum('lines as total_debit_sum', 'debit')
            ->withSum('lines as total_credit_sum', 'credit');

        $this->applyManualJournalScope($query);

        if ($request->filled('status')) {
            abort_unless(
                in_array($request->status, ['draft', 'posted', 'cancelled'], true),
                422,
                'حالة القيد غير صحيحة.'
            );

            $query->where('status', $request->status);
        }

        $query->latest('id');

        return DataTables::of($query)
            ->addIndexColumn()

            ->addColumn('manual_no', function ($row) {
                return e($row->manual_no ?? '-');
            })

            ->addColumn('manual_date', function ($row) {
                return $row->manual_date
                    ? Carbon::parse($row->manual_date)->format('Y-m-d')
                    : '-';
            })

            ->addColumn('branch_name', function ($row) {
                return e(
                    $row->branch?->branch_name
                    ?? $row->branch?->branch_name_ar
                    ?? '-'
                );
            })

            ->addColumn('notes', function ($row) {
                return e($row->notes ?? '-');
            })

            ->addColumn('total_debit', function ($row) {
                return number_format((float) ($row->total_debit_sum ?? 0), 2);
            })

            ->addColumn('total_credit', function ($row) {
                return number_format((float) ($row->total_credit_sum ?? 0), 2);
            })

            ->addColumn('status_badge', function ($row) {
                return match ($row->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'posted' => '<span class="badge bg-success">مرحل</span>',
                    'cancelled' => '<span class="badge bg-danger">ملغي</span>',
                    default => '<span class="badge bg-light text-dark">' . e($row->status) . '</span>',
                };
            })

            ->addColumn('actions', function ($row) use ($user) {
                $buttons = '<div class="d-flex justify-content-center gap-1 flex-wrap">';

                if ($user && $user->can('manual_journal_entries.view')) {
                    $buttons .= '
                        <a href="' . route('manual-journal-entries.show', $row) . '"
                           class="btn btn-sm btn-info">
                            عرض
                        </a>
                    ';
                }

                if (
                    $row->status === 'draft'
                    && $user
                    && $user->can('manual_journal_entries.post')
                ) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-success postBtn"
                                data-id="' . (int) $row->id . '">
                            ترحيل
                        </button>
                    ';
                }

                if (
                    $row->status === 'posted'
                    && $user
                    && $user->can('manual_journal_entries.cancel')
                ) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-danger cancelBtn"
                                data-id="' . (int) $row->id . '">
                            إلغاء
                        </button>
                    ';
                }

                $buttons .= '</div>';

                return $buttons === '<div class="d-flex justify-content-center gap-1 flex-wrap"></div>'
                    ? '<span class="text-muted">-</span>'
                    : $buttons;
            })

            ->rawColumns([
                'status_badge',
                'actions',
            ])

            ->make(true);
    }

    public function create()
    {
        abort_unless(auth()->user()?->can('manual_journal_entries.create'), 403);

        $branches = $this->activeBranches();
        $costCenters = $this->activeCostCenters();
        $accounts = $this->activeAccounts();
        $customers = $this->activeCustomers();
        $suppliers = $this->activeSuppliers();

        return view('manual-journal-entries.create', compact(
            'branches',
            'costCenters',
            'accounts',
            'customers',
            'suppliers'
        ));
    }

    public function store(Request $request, ManualJournalEntryService $service)
    {
        abort_unless(auth()->user()?->can('manual_journal_entries.create'), 403);

        $data = $request->validate([
            'manual_date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:2'],

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

        $data = $this->prepareSecureManualJournalData($data);

        try {
            $manualJournalEntry = $service->store($data);

            return redirect()
                ->route('manual-journal-entries.show', $manualJournalEntry)
                ->with('success', 'تم حفظ قيد اليومية كمسودة بنجاح.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(ManualJournalEntry $manualJournalEntry)
    {
        abort_unless(auth()->user()?->can('manual_journal_entries.view'), 403);

        $this->assertManualJournalAccess($manualJournalEntry);

        $manualJournalEntry->load([
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

        return view('manual-journal-entries.show', compact('manualJournalEntry'));
    }

    public function print(ManualJournalEntry $manualJournalEntry)
    {
        abort_unless(auth()->user()?->can('manual_journal_entries.print'), 403);

        $this->assertManualJournalAccess($manualJournalEntry);

        $manualJournalEntry->load([
            'branch.company',
            'lines.branch',
            'lines.costCenter',
            'lines.account',
            'lines.customer',
            'lines.supplier',
            'creator',
            'poster',
            'canceller',
        ]);

        return view('manual-journal-entries.print', compact('manualJournalEntry'));
    }
    
    public function post(
        ManualJournalEntry $manualJournalEntry,
        ManualJournalEntryService $service
    ) {
        abort_unless(auth()->user()?->can('manual_journal_entries.post'), 403);

        $this->assertManualJournalAccess($manualJournalEntry);

        if ($manualJournalEntry->status !== 'draft') {
            return back()->with('error', 'لا يمكن ترحيل قيد غير مسودة.');
        }

        try {
            $service->post($manualJournalEntry);

            return redirect()
                ->route('manual-journal-entries.show', $manualJournalEntry)
                ->with('success', 'تم ترحيل قيد اليومية وإنشاء القيد المحاسبي بنجاح.');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(
        Request $request,
        ManualJournalEntry $manualJournalEntry,
        ManualJournalEntryService $service
    ) {
        abort_unless(auth()->user()?->can('manual_journal_entries.cancel'), 403);

        $this->assertManualJournalAccess($manualJournalEntry);

        if ($manualJournalEntry->status === 'cancelled') {
            return back()->with('error', 'القيد ملغي مسبقًا.');
        }

        $data = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $service->cancel(
                $manualJournalEntry,
                $data['cancellation_reason'] ?? null
            );

            return redirect()
                ->route('manual-journal-entries.show', $manualJournalEntry)
                ->with('success', 'تم إلغاء قيد اليومية وعكس القيد المحاسبي بنجاح.');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}