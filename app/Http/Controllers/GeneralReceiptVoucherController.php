<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\GeneralReceiptVoucher;
use App\Services\GeneralReceiptVoucherService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class GeneralReceiptVoucherController extends Controller
{
    public function __construct(
        private GeneralReceiptVoucherService $service
    ) {}

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

    private function applyVoucherScope($query)
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

    private function assertVoucherAccess(GeneralReceiptVoucher $voucher): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array((int) $voucher->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى سند قبض عام من فرع آخر.'
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

    private function assertAccountAllowed(?int $accountId, string $message): void
    {
        abort_unless($accountId, 403, $message);

        $exists = Account::query()
            ->whereKey($accountId)
            ->where('is_active', true)
            ->where('is_group', false)
            ->exists();

        abort_unless($exists, 403, $message);
    }

    private function prepareSecureVoucherData(array $data): array
    {
        $user = auth()->user();

        abort_unless($user, 403);

        if (($data['save_action'] ?? 'draft') === 'post') {
            abort_unless(
                $user->can('general_receipt_vouchers.post'),
                403,
                'لا تملك صلاحية الحفظ مع الترحيل.'
            );
        }

        /*
            branch_admin / user:
            لا نعتمد الفرع القادم من الفورم.
            نثبت فرع السند من المستخدم.
        */
        if (! $this->actorCanSeeAllCompanyBranches()) {
            $data['branch_id'] = $user->branch_id;
        }

        $branchId = (int) ($data['branch_id'] ?? 0);

        /*
            فرع السند مطلوب.
            للإدمن: يختار الفرع من الفورم.
            للموظف: يثبت على فرعه.
        */
        $this->assertBranchAllowed($branchId);

        $data['branch_id'] = $branchId;
        $data['created_by'] = auth()->id();

        $this->assertCostCenterAllowed((int) ($data['cost_center_id'] ?? 0));

        $this->assertAccountAllowed(
            (int) ($data['cash_bank_account_id'] ?? 0),
            'حساب النقدية أو البنك غير صحيح أو غير نشط أو حساب تجميعي.'
        );

        $this->assertAccountAllowed(
            (int) ($data['opposite_account_id'] ?? 0),
            'الحساب المقابل غير صحيح أو غير نشط أو حساب تجميعي.'
        );

        if (
            (int) ($data['cash_bank_account_id'] ?? 0)
            === (int) ($data['opposite_account_id'] ?? 0)
        ) {
            abort(422, 'لا يمكن أن يكون حساب القبض هو نفس الحساب المقابل.');
        }

        return $data;
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

    private function accounts()
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

    public function index()
    {
        abort_unless(auth()->user()?->can('general_receipt_vouchers.view'), 403);

        return view('general-receipt-vouchers.index');
    }

    public function fetch(Request $request)
    {
        abort_unless(auth()->user()?->can('general_receipt_vouchers.view'), 403);

        $user = auth()->user();

        $vouchers = GeneralReceiptVoucher::query()
            ->with([
                'branch',
                'costCenter',
                'cashBankAccount',
                'oppositeAccount',
                'creator',
            ]);

        $this->applyVoucherScope($vouchers);

        if ($request->filled('status')) {
            abort_unless(
                in_array($request->status, ['draft', 'posted', 'cancelled'], true),
                422,
                'حالة السند غير صحيحة.'
            );

            $vouchers->where('status', $request->status);
        }

        $vouchers->latest('id');

        return DataTables::of($vouchers)
            ->addIndexColumn()

            ->addColumn('voucher_no', function ($voucher) {
                $html = '<strong>' . e($voucher->voucher_no) . '</strong>';

                if ($voucher->payer_name) {
                    $html .= '<br><small class="text-muted">من: ' . e($voucher->payer_name) . '</small>';
                }

                return $html;
            })

            ->addColumn('voucher_date', function ($voucher) {
                return $voucher->voucher_date
                    ? Carbon::parse($voucher->voucher_date)->format('Y-m-d')
                    : '-';
            })

            ->addColumn('branch_name', function ($voucher) {
                return e(
                    $voucher->branch?->branch_name
                    ?? $voucher->branch?->branch_name_ar
                    ?? $voucher->branch?->name
                    ?? '-'
                );
            })

            ->addColumn('cost_center_name', function ($voucher) {
                if (! $voucher->costCenter) {
                    return '-';
                }

                return e(
                    ($voucher->costCenter->code ? $voucher->costCenter->code . ' - ' : '')
                    . ($voucher->costCenter->name ?? $voucher->costCenter->cost_center_name ?? '-')
                );
            })

            ->addColumn('cash_bank_account', function ($voucher) {
                $account = $voucher->cashBankAccount;

                if (! $account) {
                    return '-';
                }

                $code = $account->account_code ?? $account->code ?? '';
                $name = $account->account_name_ar
                    ?? $account->account_name
                    ?? $account->name
                    ?? '';

                return e(trim($code . ' - ' . $name, ' -'));
            })

            ->addColumn('opposite_account', function ($voucher) {
                $account = $voucher->oppositeAccount;

                if (! $account) {
                    return '-';
                }

                $code = $account->account_code ?? $account->code ?? '';
                $name = $account->account_name_ar
                    ?? $account->account_name
                    ?? $account->name
                    ?? '';

                return e(trim($code . ' - ' . $name, ' -'));
            })

            ->addColumn('amount', function ($voucher) {
                return number_format((float) $voucher->amount, 2);
            })

            ->addColumn('status_badge', function ($voucher) {
                return match ($voucher->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'posted' => '<span class="badge bg-success">مرحل</span>',
                    'cancelled' => '<span class="badge bg-danger">ملغى</span>',
                    default => '<span class="badge bg-light text-dark">' . e($voucher->status) . '</span>',
                };
            })

            ->addColumn('actions', function ($voucher) use ($user) {
                $buttons = '<div class="d-flex gap-1 flex-wrap">';

                if ($user && $user->can('general_receipt_vouchers.view')) {
                    $buttons .= '
                        <a href="' . route('general-receipt-vouchers.show', $voucher) . '"
                           class="btn btn-sm btn-info">
                            عرض
                        </a>
                    ';
                }

                if ($user && $user->can('general_receipt_vouchers.print')) {
                    $buttons .= '
                        <a href="' . route('general-receipt-vouchers.print', $voucher) . '"
                           target="_blank"
                           class="btn btn-sm btn-dark">
                            طباعة
                        </a>
                    ';
                }

                if (
                    $voucher->status === 'draft'
                    && $user
                    && $user->can('general_receipt_vouchers.post')
                ) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-success postBtn"
                                data-id="' . (int) $voucher->id . '">
                            ترحيل
                        </button>
                    ';
                }

                if (
                    $voucher->status !== 'cancelled'
                    && $user
                    && $user->can('general_receipt_vouchers.cancel')
                ) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-danger cancelBtn"
                                data-id="' . (int) $voucher->id . '">
                            إلغاء
                        </button>
                    ';
                }

                $buttons .= '</div>';

                return $buttons === '<div class="d-flex gap-1 flex-wrap"></div>'
                    ? '<span class="text-muted">-</span>'
                    : $buttons;
            })

            ->rawColumns([
                'voucher_no',
                'status_badge',
                'actions',
            ])

            ->make(true);
    }

    public function create()
    {
        abort_unless(auth()->user()?->can('general_receipt_vouchers.create'), 403);

        $branches = $this->branches();
        $costCenters = $this->costCenters();
        $accounts = $this->accounts();

        return view('general-receipt-vouchers.create', compact(
            'branches',
            'costCenters',
            'accounts'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('general_receipt_vouchers.create'), 403);

        $data = $request->validate([
            'voucher_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('general_receipt_vouchers', 'voucher_no'),
            ],
            'branch_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'voucher_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'cash_bank_account_id' => ['required', 'integer'],
            'opposite_account_id' => ['required', 'integer', 'different:cash_bank_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'save_action' => ['nullable', 'in:draft,post'],
        ]);

        $data = $this->prepareSecureVoucherData($data);

        try {
            $voucher = $this->service->store($data);

            return redirect()
                ->route('general-receipt-vouchers.show', $voucher)
                ->with('success', 'تم حفظ سند القبض العام بنجاح.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(GeneralReceiptVoucher $generalReceiptVoucher)
    {
        abort_unless(auth()->user()?->can('general_receipt_vouchers.view'), 403);

        $this->assertVoucherAccess($generalReceiptVoucher);

        $generalReceiptVoucher->load([
            'branch',
            'costCenter',
            'cashBankAccount',
            'oppositeAccount',
            'creator',
            'poster',
            'canceller',
        ]);

        return view('general-receipt-vouchers.show', [
            'voucher' => $generalReceiptVoucher,
        ]);
    }

public function print(GeneralReceiptVoucher $generalReceiptVoucher)
{
    abort_unless(auth()->user()?->can('general_receipt_vouchers.print'), 403);

    if (method_exists($this, 'assertVoucherAccess')) {
        $this->assertVoucherAccess($generalReceiptVoucher);
    }

    $printBranch = Branch::with('company')->find($generalReceiptVoucher->branch_id);
    $printCompany = $printBranch?->company ?? auth()->user()?->company;

    $costCenter = CostCenter::query()->find($generalReceiptVoucher->cost_center_id);

    $cashBankAccount = Account::query()->find($generalReceiptVoucher->cash_bank_account_id);
    $oppositeAccount = Account::query()->find($generalReceiptVoucher->opposite_account_id);

    $creator = User::query()->find($generalReceiptVoucher->created_by);
    $poster = User::query()->find($generalReceiptVoucher->posted_by);
    $canceller = User::query()->find($generalReceiptVoucher->cancelled_by);

    return view('general-receipt-vouchers.print', compact(
        'generalReceiptVoucher',
        'printBranch',
        'printCompany',
        'costCenter',
        'cashBankAccount',
        'oppositeAccount',
        'creator',
        'poster',
        'canceller'
    ));
}

    public function post(GeneralReceiptVoucher $generalReceiptVoucher)
    {
        abort_unless(auth()->user()?->can('general_receipt_vouchers.post'), 403);

        $this->assertVoucherAccess($generalReceiptVoucher);

        if ($generalReceiptVoucher->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن ترحيل سند غير مسودة.',
            ], 422);
        }

        try {
            $this->service->post($generalReceiptVoucher);

            return response()->json([
                'status' => true,
                'message' => 'تم ترحيل سند القبض العام بنجاح.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, GeneralReceiptVoucher $generalReceiptVoucher)
    {
        abort_unless(auth()->user()?->can('general_receipt_vouchers.cancel'), 403);

        $this->assertVoucherAccess($generalReceiptVoucher);

        if ($generalReceiptVoucher->status === 'cancelled') {
            return response()->json([
                'status' => false,
                'message' => 'السند ملغى مسبقًا.',
            ], 422);
        }

        $data = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->service->cancel($generalReceiptVoucher, $data['cancel_reason'] ?? null);

            return response()->json([
                'status' => true,
                'message' => 'تم إلغاء سند القبض العام بنجاح.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}