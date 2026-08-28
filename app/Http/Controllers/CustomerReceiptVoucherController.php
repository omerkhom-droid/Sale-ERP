<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerReceiptVoucherRequest;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\CustomerReceiptVoucher;
use App\Models\SalesInvoice;
use App\Services\CustomerReceiptVoucherService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerReceiptVoucherController extends Controller
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

    private function applySalesInvoiceScope($query)
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        /*
            فواتير البيع تظهر حسب نطاق فروع المستخدم.
            branch_admin / user: فرعه فقط.
            company_admin / company_owner: فروع الشركة.
            master / system_admin: كل الفروع.
        */
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

    private function assertCustomerAllowed(?int $customerId): void
    {
        abort_unless($customerId, 403, 'العميل غير صحيح.');

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

        $exists = CostCenter::query()
            ->whereKey($costCenterId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'مركز التكلفة غير صحيح أو غير نشط.');
    }

    private function assertVoucherAccess(CustomerReceiptVoucher $voucher): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array((int) $voucher->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى سند قبض من فرع آخر.'
        );
    }

    private function getAllocationInvoiceIds(array $data)
    {
        return collect($data['allocations'] ?? [])
            ->map(function ($row) {
                return $row['sales_invoice_id']
                    ?? $row['invoice_id']
                    ?? null;
            })
            ->filter()
            ->unique()
            ->values();
    }

    private function assertInvoicesAllowed(array $data, int $customerId): ?int
    {
        $invoiceIds = $this->getAllocationInvoiceIds($data);

        if ($invoiceIds->isEmpty()) {
            return null;
        }

        $query = SalesInvoice::query()
            ->whereIn('id', $invoiceIds)
            ->where('customer_id', $customerId)
            ->where('status', 'posted')
            ->where('remaining_amount', '>', 0);

        /*
            هنا التعديل المهم:
            لا نمنع تعدد الفروع للإدمن.
            نطبق فقط نطاق المستخدم:
            - موظف الفرع يرى فواتير فرعه فقط.
            - مدير الشركة يرى فواتير فروع شركته.
            - master / system_admin يرى الكل.
        */
        $this->applySalesInvoiceScope($query);

        $invoices = $query->get([
            'id',
            'branch_id',
        ]);

        if ($invoices->count() !== $invoiceIds->count()) {
            abort(403, 'يوجد فاتورة غير مسموح استخدامها أو لا تتبع نفس العميل أو ليست ضمن نطاق فروعك.');
        }

        $branchIds = $invoices
            ->pluck('branch_id')
            ->filter()
            ->unique()
            ->values();

        /*
            إذا الفواتير من فرع واحد، نرجع هذا الفرع.
            إذا الفواتير من أكثر من فرع، نرجع null.
            في هذه الحالة يجب أن يكون فرع السند مختارًا من الفورم.
        */
        return $branchIds->count() === 1
            ? (int) $branchIds->first()
            : null;
    }

    private function prepareSecureVoucherData(array $data): array
    {
        $user = auth()->user();

        $customerId = (int) ($data['customer_id'] ?? 0);

        $this->assertCustomerAllowed($customerId);

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
            قد تكون من فرع واحد أو أكثر حسب صلاحية المستخدم.
        */
        $invoiceBranchId = $this->assertInvoicesAllowed(
            data: $data,
            customerId: $customerId
        );

        /*
            إذا لم يتم تحديد فرع للسند:
            - لو الفواتير كلها من فرع واحد، نستخدم نفس فرع الفواتير.
            - لو الفواتير من أكثر من فرع، لازم المستخدم يختار فرع السند.
        */
        if (! $branchId && $invoiceBranchId) {
            $branchId = (int) $invoiceBranchId;
            $data['branch_id'] = $branchId;
        }

        if (! $branchId) {
            abort(422, 'يجب اختيار فرع السند عند توزيع السند على فواتير من أكثر من فرع.');
        }

        /*
            فرع السند هو فرع تسجيل السند / الخزينة.
            وليس شرطًا أن يكون نفس فرع كل فاتورة إذا المستخدم إدمن.
        */
        $this->assertBranchAllowed($branchId);

        $data['branch_id'] = $branchId;
        $data['created_by'] = auth()->id();

        return $data;
    }

    private function activeCustomers()
    {
        /*
            العملاء عامّون داخل النسخة الحالية.
            فواتير العميل هي التي تُفلتر حسب فرع المستخدم عند اختيار العميل.
        */
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('id', 'desc')
            ->get();
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

    public function index()
    {
        abort_unless(auth()->user()?->can('customer_receipt_vouchers.view'), 403);

        return view('customer-receipt-vouchers.index');
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('customer_receipt_vouchers.view'), 403);

        $user = auth()->user();

        $query = CustomerReceiptVoucher::query()
            ->with([
                'customer',
                'branch',
            ]);

        $this->applyVoucherScope($query);

        $query->latest('id');

        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('voucher_date', function ($voucher) {
                return $voucher->voucher_date
                    ? Carbon::parse($voucher->voucher_date)->format('Y-m-d')
                    : '-';
            })

            ->addColumn('customer_name', function ($voucher) {
                return e(
                    $voucher->customer?->customer_name
                    ?? $voucher->customer?->name
                    ?? '-'
                );
            })

            ->addColumn('amount_display', function ($voucher) {
                return number_format((float) $voucher->amount, 2);
            })

            ->addColumn('allocated_amount_display', function ($voucher) {
                return number_format((float) $voucher->allocated_amount, 2);
            })

            ->addColumn('unallocated_amount_display', function ($voucher) {
                return number_format((float) $voucher->unallocated_amount, 2);
            })

            ->addColumn('status_badge', function ($voucher) {
                return match ($voucher->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'posted' => '<span class="badge bg-success">مرحلة</span>',
                    'cancelled' => '<span class="badge bg-danger">ملغاة</span>',
                    default => '<span class="badge bg-light text-dark">' . e($voucher->status) . '</span>',
                };
            })

            ->addColumn('actions', function ($voucher) use ($user) {
                $buttons = '<div class="d-flex justify-content-center gap-1 flex-wrap">';

                if ($user && $user->can('customer_receipt_vouchers.view')) {
                    $buttons .= '
                        <a href="' . route('customer-receipt-vouchers.show', $voucher->id) . '"
                           class="btn btn-sm btn-info">
                            عرض
                        </a>
                    ';
                }

                if ($user && $user->can('customer_receipt_vouchers.print')) {
                    $buttons .= '
                        <a href="' . route('customer-receipt-vouchers.print', $voucher->id) . '"
                           target="_blank"
                           class="btn btn-sm btn-dark">
                            طباعة
                        </a>
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
        abort_unless(auth()->user()?->can('customer_receipt_vouchers.create'), 403);

        $customers = $this->activeCustomers();
        $branches = $this->activeBranches();
        $costCenters = $this->activeCostCenters();

        return view('customer-receipt-vouchers.create', compact(
            'customers',
            'branches',
            'costCenters'
        ));
    }

    public function store(
        StoreCustomerReceiptVoucherRequest $request,
        CustomerReceiptVoucherService $service
    ) {
        abort_unless(auth()->user()?->can('customer_receipt_vouchers.create'), 403);

        try {
            $data = $this->prepareSecureVoucherData($request->validated());

            $voucher = $service->store($data);

            return redirect()
                ->route('customer-receipt-vouchers.show', $voucher->id)
                ->with('success', 'تم حفظ سند القبض بنجاح.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(CustomerReceiptVoucher $customerReceiptVoucher)
    {
        abort_unless(auth()->user()?->can('customer_receipt_vouchers.view'), 403);

        $this->assertVoucherAccess($customerReceiptVoucher);

        $customerReceiptVoucher->load([
            'customer',
            'branch',
            'allocations.salesInvoice',
            'creator',
            'postedBy',
            'cancelledBy',
        ]);

        return view('customer-receipt-vouchers.show', compact('customerReceiptVoucher'));
    }

    public function post(
        CustomerReceiptVoucher $customerReceiptVoucher,
        CustomerReceiptVoucherService $service
    ) {
        abort_unless(auth()->user()?->can('customer_receipt_vouchers.post'), 403);

        $this->assertVoucherAccess($customerReceiptVoucher);

        if ($customerReceiptVoucher->status !== 'draft') {
            return back()->with('error', 'لا يمكن ترحيل سند غير مسودة.');
        }

        try {
            $service->post($customerReceiptVoucher);

            return redirect()
                ->route('customer-receipt-vouchers.show', $customerReceiptVoucher->id)
                ->with('success', 'تم ترحيل سند القبض بنجاح.');

        } catch (Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    public function cancel(
        Request $request,
        CustomerReceiptVoucher $customerReceiptVoucher,
        CustomerReceiptVoucherService $service
    ) {
        abort_unless(auth()->user()?->can('customer_receipt_vouchers.cancel'), 403);

        $this->assertVoucherAccess($customerReceiptVoucher);

        if ($customerReceiptVoucher->status === 'cancelled') {
            return back()->with('error', 'السند ملغى مسبقًا.');
        }

        $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->cancel(
                $customerReceiptVoucher,
                $request->cancel_reason
            );

            return redirect()
                ->route('customer-receipt-vouchers.show', $customerReceiptVoucher->id)
                ->with('success', 'تم إلغاء سند القبض بنجاح.');

        } catch (Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    public function print(CustomerReceiptVoucher $customerReceiptVoucher)
    {
        abort_unless(auth()->user()?->can('customer_receipt_vouchers.print'), 403);

        $this->assertVoucherAccess($customerReceiptVoucher);

        $customerReceiptVoucher->load([
            'customer',
            'branch',
            'allocations.salesInvoice',
        ]);

        return view('customer-receipt-vouchers.print', compact('customerReceiptVoucher'));
    }

    public function customerInvoices(Customer $customer)
    {
        abort_unless(auth()->user()?->can('customer_receipt_vouchers.create'), 403);

        $this->assertCustomerAllowed($customer->id);

        /*
            عند اختيار العميل:
            - موظف الفرع يرى فواتير فرعه فقط.
            - مدير الشركة يرى فواتير كل فروع شركته.
            - master / system_admin يرى كل الفواتير.
        */
        $query = SalesInvoice::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'posted')
            ->where('remaining_amount', '>', 0);

        $this->applySalesInvoiceScope($query);

        $invoices = $query
            ->orderBy('invoice_date')
            ->get()
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

        return response()->json([
            'status' => 'success',
            'data' => $invoices,
        ]);
    }
}