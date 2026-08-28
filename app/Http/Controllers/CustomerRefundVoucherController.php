<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRefundVoucherRequest;
use App\Models\Branch;
use App\Models\CustomerRefundVoucher;
use App\Models\SalesReturn;
use App\Services\CustomerRefundVoucherService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerRefundVoucherController extends Controller
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

    private function applySalesReturnScope($query)
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        /*
            مردودات المبيعات حسب branch_id.
            branch_admin / user: فرعه فقط.
            company_owner / company_admin: فروع الشركة.
            master / system_admin: كل الفروع.
        */
        return $query->whereIn('branch_id', $branchIds);
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

    private function assertSalesReturnAccess(SalesReturn $salesReturn): void
    {
        $salesReturn->loadMissing([
            'salesInvoice',
            'customer',
        ]);

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        $returnBranchId = (int) (
            $salesReturn->branch_id
            ?? $salesReturn->salesInvoice?->branch_id
            ?? 0
        );

        abort_unless(
            $returnBranchId && in_array($returnBranchId, $branchIds, true),
            403,
            'لا تملك صلاحية استخدام مردود مبيعات من فرع آخر.'
        );
    }

    private function assertVoucherAccess(CustomerRefundVoucher $voucher): void
    {
        $voucher->loadMissing([
            'salesReturn.salesInvoice',
            'branch',
        ]);

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        $voucherBranchId = (int) (
            $voucher->branch_id
            ?? $voucher->salesReturn?->branch_id
            ?? $voucher->salesReturn?->salesInvoice?->branch_id
            ?? 0
        );

        abort_unless(
            $voucherBranchId && in_array($voucherBranchId, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى سند صرف عميل من فرع آخر.'
        );
    }

    private function availableRefundAmount(SalesReturn $salesReturn): float
    {
        $availableAmount = round(
            (float) $salesReturn->refundable_amount - (float) ($salesReturn->refunded_amount ?? 0),
            2
        );

        return max($availableAmount, 0);
    }

    private function prepareSecureVoucherData(array $data): array
    {
        $salesReturnId = (int) ($data['sales_return_id'] ?? 0);

        abort_unless($salesReturnId, 403, 'مردود المبيعات غير صحيح.');

        $salesReturn = SalesReturn::query()
            ->with([
                'salesInvoice',
                'customer',
            ])
            ->whereKey($salesReturnId)
            ->firstOrFail();

        $this->assertSalesReturnAccess($salesReturn);

        if ($salesReturn->status !== 'posted') {
            abort(422, 'لا يمكن إنشاء سند صرف إلا من مردود مبيعات مرحل.');
        }

        $availableAmount = $this->availableRefundAmount($salesReturn);

        if ($availableAmount <= 0) {
            abort(422, 'لا يوجد مبلغ متاح للصرف لهذا المردود.');
        }

        if (! empty($data['amount']) && (float) $data['amount'] > $availableAmount) {
            abort(422, 'مبلغ السند أكبر من المبلغ المتاح للصرف.');
        }

        /*
            لا نعتمد الفرع أو العميل من الفورم.
            سند صرف العميل يأخذ بياناته من مردود المبيعات.
        */
        $branchId = (int) (
            $salesReturn->branch_id
            ?? $salesReturn->salesInvoice?->branch_id
            ?? 0
        );

        $this->assertBranchAllowed($branchId);

        $data['sales_return_id'] = $salesReturn->id;
        $data['branch_id'] = $branchId;
        $data['customer_id'] = $salesReturn->customer_id
            ?? $salesReturn->salesInvoice?->customer_id
            ?? null;

        $data['created_by'] = auth()->id();

        return $data;
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('customer_refund_vouchers.view'), 403);

        return view('customer-refund-vouchers.index');
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('customer_refund_vouchers.view'), 403);

        $user = auth()->user();

        $query = CustomerRefundVoucher::query()
            ->with([
                'salesReturn',
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

            ->addColumn('sales_return_no', function ($voucher) {
                return e($voucher->salesReturn?->return_no ?? '-');
            })

            ->addColumn('customer_name', function ($voucher) {
                return e(
                    $voucher->customer?->customer_name
                    ?? $voucher->customer?->name
                    ?? $voucher->customer?->fullname
                    ?? '-'
                );
            })

            ->addColumn('branch_name', function ($voucher) {
                return e(
                    $voucher->branch?->branch_name
                    ?? $voucher->branch?->branch_name_ar
                    ?? '-'
                );
            })

            ->addColumn('amount_display', function ($voucher) {
                return number_format((float) $voucher->amount, 2);
            })

            ->addColumn('payment_method_display', function ($voucher) {
                return match ($voucher->payment_method) {
                    'cash' => 'نقدي',
                    'card' => 'شبكة',
                    'bank_transfer' => 'تحويل بنكي',
                    'other' => 'أخرى',
                    default => e($voucher->payment_method),
                };
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

                if ($user && $user->can('customer_refund_vouchers.view')) {
                    $buttons .= '
                        <a href="' . route('customer-refund-vouchers.show', $voucher->id) . '"
                           class="btn btn-sm btn-info">
                            عرض
                        </a>
                    ';
                }

                if ($user && $user->can('customer_refund_vouchers.print')) {
                    $buttons .= '
                        <a href="' . route('customer-refund-vouchers.print', $voucher->id) . '"
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
        abort_unless(auth()->user()?->can('customer_refund_vouchers.create'), 403);

        /*
            نعرض فقط مردودات المبيعات المرحلة
            التي لديها مبلغ متاح للصرف
            وضمن فرع المستخدم أو الفروع المسموحة له.
        */
        $salesReturns = SalesReturn::query()
            ->with([
                'customer',
                'salesInvoice',
            ])
            ->where('status', 'posted')
            ->whereRaw('(refundable_amount - COALESCE(refunded_amount, 0)) > 0');

        $this->applySalesReturnScope($salesReturns);

        $salesReturns = $salesReturns
            ->latest('id')
            ->get();

        return view('customer-refund-vouchers.create', compact('salesReturns'));
    }

    public function store(
        StoreCustomerRefundVoucherRequest $request,
        CustomerRefundVoucherService $service
    ) {
        abort_unless(auth()->user()?->can('customer_refund_vouchers.create'), 403);

        try {
            $data = $this->prepareSecureVoucherData($request->validated());

            $voucher = $service->store($data);

            return redirect()
                ->route('customer-refund-vouchers.show', $voucher->id)
                ->with('success', 'تم حفظ سند صرف العميل بنجاح.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(CustomerRefundVoucher $customerRefundVoucher)
    {
        abort_unless(auth()->user()?->can('customer_refund_vouchers.view'), 403);

        $this->assertVoucherAccess($customerRefundVoucher);

        $customerRefundVoucher->load([
            'salesReturn.salesInvoice',
            'customer',
            'branch',
            'creator',
            'postedBy',
            'cancelledBy',
        ]);

        return view('customer-refund-vouchers.show', compact('customerRefundVoucher'));
    }

    public function post(
        CustomerRefundVoucher $customerRefundVoucher,
        CustomerRefundVoucherService $service
    ) {
        abort_unless(auth()->user()?->can('customer_refund_vouchers.post'), 403);

        $this->assertVoucherAccess($customerRefundVoucher);

        if ($customerRefundVoucher->status !== 'draft') {
            return back()->with('error', 'لا يمكن ترحيل سند غير مسودة.');
        }

        try {
            $service->post($customerRefundVoucher);

            return redirect()
                ->route('customer-refund-vouchers.show', $customerRefundVoucher->id)
                ->with('success', 'تم ترحيل سند صرف العميل بنجاح.');

        } catch (Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    public function cancel(
        Request $request,
        CustomerRefundVoucher $customerRefundVoucher,
        CustomerRefundVoucherService $service
    ) {
        abort_unless(auth()->user()?->can('customer_refund_vouchers.cancel'), 403);

        $this->assertVoucherAccess($customerRefundVoucher);

        if ($customerRefundVoucher->status === 'cancelled') {
            return back()->with('error', 'السند ملغى مسبقًا.');
        }

        $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->cancel($customerRefundVoucher, $request->cancel_reason);

            return redirect()
                ->route('customer-refund-vouchers.show', $customerRefundVoucher->id)
                ->with('success', 'تم إلغاء سند صرف العميل بنجاح.');

        } catch (Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    public function print(CustomerRefundVoucher $customerRefundVoucher)
    {
        abort_unless(auth()->user()?->can('customer_refund_vouchers.print'), 403);

        $this->assertVoucherAccess($customerRefundVoucher);

        $customerRefundVoucher->load([
            'salesReturn.salesInvoice',
            'customer',
            'branch',
        ]);

        return view('customer-refund-vouchers.print', compact('customerRefundVoucher'));
    }

    public function salesReturnData(SalesReturn $salesReturn)
    {
        abort_unless(auth()->user()?->can('customer_refund_vouchers.create'), 403);

        $this->assertSalesReturnAccess($salesReturn);

        $salesReturn->loadMissing([
            'customer',
            'salesInvoice',
        ]);

        if ($salesReturn->status !== 'posted') {
            return response()->json([
                'status' => 'error',
                'message' => 'مردود المبيعات غير مرحل.',
            ], 422);
        }

        $availableAmount = $this->availableRefundAmount($salesReturn);

        if ($availableAmount <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يوجد مبلغ متاح للصرف لهذا المردود.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'return_no' => $salesReturn->return_no,
                'return_date' => $salesReturn->return_date
                    ? Carbon::parse($salesReturn->return_date)->format('Y-m-d')
                    : '-',

                'customer_name' => $salesReturn->customer?->customer_name
                    ?? $salesReturn->customer?->name
                    ?? $salesReturn->customer?->fullname
                    ?? $salesReturn->salesInvoice?->customer_name
                    ?? '-',

                'invoice_no' => $salesReturn->salesInvoice?->invoice_no ?? '-',

                'total_amount' => (float) $salesReturn->total_amount,
                'refundable_amount' => (float) $salesReturn->refundable_amount,
                'refunded_amount' => (float) ($salesReturn->refunded_amount ?? 0),
                'available_amount' => (float) $availableAmount,

                'branch_id' => $salesReturn->branch_id
                    ?? $salesReturn->salesInvoice?->branch_id,
            ],
        ]);
    }
}