<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierPaymentVoucherRequest;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierPaymentVoucher;
use App\Services\SupplierPaymentVoucherService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SupplierPaymentVoucherController extends Controller
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

        if ($this->actorCanSeeAllCompanies()) {
            return null;
        }

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

    private function applyPurchaseInvoiceScope($query)
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

    private function assertSupplierAllowed(?int $supplierId): void
    {
        abort_unless($supplierId, 403, 'المورد غير صحيح.');

        $exists = Supplier::query()
            ->whereKey($supplierId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'المورد غير صحيح أو غير نشط.');
    }

    private function paymentAccountIds()
    {
        return AccountSetting::query()
            ->whereIn('setting_key', [
                'cash_account',
                'bank_account',
            ])
            ->whereNotNull('account_id')
            ->pluck('account_id');
    }

    private function assertPaymentAccountAllowed(?int $accountId): void
    {
        abort_unless($accountId, 403, 'حساب الدفع غير صحيح.');

        $paymentAccountIds = $this->paymentAccountIds();

        $exists = Account::query()
            ->whereKey($accountId)
            ->whereIn('id', $paymentAccountIds)
            ->where('is_active', true)
            ->where('is_group', false)
            ->exists();

        abort_unless($exists, 403, 'حساب الدفع غير صحيح أو غير مفعّل في إعدادات الحسابات.');
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

    private function assertVoucherAccess(SupplierPaymentVoucher $voucher): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array((int) $voucher->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى سند من فرع آخر.'
        );
    }

    private function allocationInvoiceIds(array $data)
    {
        return collect($data['allocations'] ?? [])
            ->map(function ($row) {
                return $row['purchase_invoice_id']
                    ?? $row['invoice_id']
                    ?? null;
            })
            ->filter()
            ->unique()
            ->values();
    }

    private function assertInvoicesAllowed(array $data, int $supplierId): ?int
    {
        $invoiceIds = $this->allocationInvoiceIds($data);

        if ($invoiceIds->isEmpty()) {
            return null;
        }

        $query = PurchaseInvoice::query()
            ->whereIn('id', $invoiceIds)
            ->where('supplier_id', $supplierId)
            ->where('status', 'posted')
            ->where('remaining_amount', '>', 0);

        /*
            هنا التعديل المهم:
            لا نمنع تعدد الفروع للإدمن.
            نطبق فقط نطاق المستخدم:
            - branch_admin / user: فواتير فرعه فقط.
            - company_owner / company_admin: فواتير فروع شركته.
            - master / system_admin: كل الفواتير.
        */
        $this->applyPurchaseInvoiceScope($query);

        $invoices = $query->get([
            'id',
            'branch_id',
        ]);

        if ($invoices->count() !== $invoiceIds->count()) {
            abort(403, 'يوجد فاتورة غير مسموح استخدامها أو لا تتبع نفس المورد أو ليست ضمن نطاق فروعك.');
        }

        $branchIds = $invoices
            ->pluck('branch_id')
            ->filter()
            ->unique()
            ->values();

        /*
            إذا الفواتير من فرع واحد، نرجع فرع الفاتورة.
            إذا الفواتير من أكثر من فرع، نرجع null.
            وقتها يجب اختيار فرع السند من الفورم.
        */
        return $branchIds->count() === 1
            ? (int) $branchIds->first()
            : null;
    }

    private function prepareSecureVoucherData(array $data): array
    {
        $user = auth()->user();

        $supplierId = (int) ($data['supplier_id'] ?? 0);
        $paymentAccountId = (int) ($data['payment_account_id'] ?? 0);

        $this->assertSupplierAllowed($supplierId);
        $this->assertPaymentAccountAllowed($paymentAccountId);
        $this->assertCostCenterAllowed((int) ($data['cost_center_id'] ?? 0));

        $branchId = (int) ($data['branch_id'] ?? 0);

        /*
            branch_admin / user:
            لا نعتمد الفرع القادم من الفورم.
            نثبت الفرع من المستخدم.
        */
        if (! $this->actorCanSeeAllCompanyBranches()) {
            $branchId = (int) ($user?->branch_id ?? 0);
            $data['branch_id'] = $branchId;
        }

        /*
            التحقق من الفواتير الموزع عليها السند.
            يمكن أن تكون من أكثر من فرع فقط لمن يملك نطاق فروع أوسع.
        */
        $invoiceBranchId = $this->assertInvoicesAllowed($data, $supplierId);

        /*
            إذا لم يتم تحديد فرع للسند:
            - لو كل الفواتير من فرع واحد، نستخدم فرع الفواتير.
            - لو الفواتير من أكثر من فرع، يجب اختيار فرع السند.
        */
        if (! $branchId && $invoiceBranchId) {
            $branchId = (int) $invoiceBranchId;
            $data['branch_id'] = $branchId;
        }

        if (! $branchId) {
            abort(422, 'يجب اختيار فرع السند عند توزيع السند على فواتير من أكثر من فرع.');
        }

        /*
            فرع السند = فرع تسجيل السند / الخزينة / البنك.
            وليس شرطًا أن يكون نفس فرع كل فاتورة إذا المستخدم إدمن.
        */
        $this->assertBranchAllowed($branchId);

        $data['branch_id'] = $branchId;
        $data['created_by'] = auth()->id();

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

    private function activeSuppliers()
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('supplier_name')
            ->get();
    }

    private function activePaymentAccounts()
    {
        $paymentAccountIds = $this->paymentAccountIds();

        return Account::query()
            ->whereIn('id', $paymentAccountIds)
            ->where('is_active', true)
            ->where('is_group', false)
            ->orderBy('account_code')
            ->get();
    }

    private function activeCostCenters()
    {
        return CostCenter::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('supplier_payment_vouchers.view'), 403);

        return view('supplier-payment-vouchers.index');
    }

    public function fetch(Request $request)
    {
        abort_unless(auth()->user()?->can('supplier_payment_vouchers.view'), 403);

        $user = auth()->user();

        $vouchers = SupplierPaymentVoucher::query()
            ->with([
                'supplier',
                'paymentAccount',
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

            ->editColumn('voucher_date', function ($row) {
                return $row->voucher_date
                    ? Carbon::parse($row->voucher_date)->format('Y-m-d')
                    : '-';
            })

            ->addColumn('supplier_name', function ($row) {
                return e($row->supplier?->supplier_name ?? '-');
            })

            ->addColumn('payment_account_name', function ($row) {
                if (! $row->paymentAccount) {
                    return '-';
                }

                return e(
                    $row->paymentAccount->account_code
                    . ' - '
                    . $row->paymentAccount->account_name_ar
                );
            })

            ->addColumn('status_badge', function ($row) {
                return match ($row->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'posted' => '<span class="badge bg-success">مرحل</span>',
                    'cancelled' => '<span class="badge bg-danger">ملغى</span>',
                    default => '<span class="badge bg-light text-dark">غير معروف</span>',
                };
            })

            ->editColumn('amount', function ($row) {
                return number_format((float) $row->amount, 2);
            })

            ->addColumn('actions', function ($row) use ($user) {
                $buttons = '<div class="d-flex justify-content-center gap-1 flex-wrap">';

                if ($user && $user->can('supplier_payment_vouchers.view')) {
                    $buttons .= '
                        <a href="' . route('supplier-payment-vouchers.show', $row->id) . '"
                           class="btn btn-sm btn-info">
                            عرض
                        </a>
                    ';
                }

                if ($user && $user->can('supplier_payment_vouchers.print')) {
                    $buttons .= '
                        <a href="' . route('supplier-payment-vouchers.print', $row->id) . '"
                           target="_blank"
                           class="btn btn-sm btn-dark">
                            طباعة
                        </a>
                    ';
                }

                if (
                    $row->status === 'draft'
                    && $user
                    && $user->can('supplier_payment_vouchers.post')
                ) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-success postBtn"
                                data-id="' . (int) $row->id . '">
                            ترحيل
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
        abort_unless(auth()->user()?->can('supplier_payment_vouchers.create'), 403);

        $suppliers = $this->activeSuppliers();
        $branches = $this->activeBranches();
        $paymentAccounts = $this->activePaymentAccounts();
        $costCenters = $this->activeCostCenters();

        return view('supplier-payment-vouchers.create', compact(
            'suppliers',
            'branches',
            'paymentAccounts',
            'costCenters'
        ));
    }

    public function openInvoices(Supplier $supplier)
    {
        abort_unless(auth()->user()?->can('supplier_payment_vouchers.create'), 403);

        $this->assertSupplierAllowed($supplier->id);

        /*
            عند اختيار المورد:
            - branch_admin / user: تظهر فواتير فرعه فقط.
            - company_owner / company_admin: تظهر فواتير فروع شركته.
            - master / system_admin: تظهر كل الفواتير.
        */
        $invoices = PurchaseInvoice::query()
            ->where('supplier_id', $supplier->id)
            ->where('status', 'posted')
            ->where('remaining_amount', '>', 0);

        $this->applyPurchaseInvoiceScope($invoices);

        $invoices = $invoices
            ->orderBy('invoice_date')
            ->get([
                'id',
                'invoice_no',
                'invoice_date',
                'total_amount',
                'paid_amount',
                'remaining_amount',
                'branch_id',
            ])
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'invoice_date' => $invoice->invoice_date
                        ? Carbon::parse($invoice->invoice_date)->format('Y-m-d')
                        : '-',
                    'total_amount' => (float) $invoice->total_amount,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'remaining_amount' => (float) $invoice->remaining_amount,
                    'branch_id' => $invoice->branch_id,
                ];
            })
            ->values();

        return response()->json($invoices);
    }

    public function store(
        StoreSupplierPaymentVoucherRequest $request,
        SupplierPaymentVoucherService $service
    ) {
        abort_unless(auth()->user()?->can('supplier_payment_vouchers.create'), 403);

        try {
            $data = $this->prepareSecureVoucherData($request->validated());

            $voucher = $service->store($data);

            return response()->json([
                'status' => true,
                'message' => 'تم حفظ سند الصرف بنجاح',
                'voucher_id' => $voucher->id,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(SupplierPaymentVoucher $supplierPaymentVoucher)
    {
        abort_unless(auth()->user()?->can('supplier_payment_vouchers.view'), 403);

        $this->assertVoucherAccess($supplierPaymentVoucher);

        $supplierPaymentVoucher->load([
            'supplier',
            'paymentAccount',
            'allocations.purchaseInvoice',
        ]);

        return view('supplier-payment-vouchers.show', compact('supplierPaymentVoucher'));
    }

    public function print(SupplierPaymentVoucher $supplierPaymentVoucher)
    {
        abort_unless(auth()->user()?->can('supplier_payment_vouchers.print'), 403);

        $this->assertVoucherAccess($supplierPaymentVoucher);

        $supplierPaymentVoucher->load([
            'supplier',
            'paymentAccount',
            'allocations.purchaseInvoice',
        ]);

        return view('supplier-payment-vouchers.print', compact('supplierPaymentVoucher'));
    }
    

    public function post(
        SupplierPaymentVoucher $supplierPaymentVoucher,
        SupplierPaymentVoucherService $service
    ) {
        abort_unless(auth()->user()?->can('supplier_payment_vouchers.post'), 403);

        $this->assertVoucherAccess($supplierPaymentVoucher);

        if ($supplierPaymentVoucher->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن ترحيل سند غير مسودة.',
            ], 422);
        }

        try {
            $service->post($supplierPaymentVoucher);

            return response()->json([
                'status' => true,
                'message' => 'تم ترحيل سند الصرف بنجاح',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(
        Request $request,
        SupplierPaymentVoucher $supplierPaymentVoucher,
        SupplierPaymentVoucherService $service
    ) {
        abort_unless(auth()->user()?->can('supplier_payment_vouchers.cancel'), 403);

        $this->assertVoucherAccess($supplierPaymentVoucher);

        if ($supplierPaymentVoucher->status === 'cancelled') {
            return response()->json([
                'status' => false,
                'message' => 'السند ملغى مسبقًا.',
            ], 422);
        }

        $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->cancel(
                $supplierPaymentVoucher,
                $request->cancel_reason
            );

            return response()->json([
                'status' => true,
                'message' => 'تم إلغاء سند الصرف بنجاح',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}