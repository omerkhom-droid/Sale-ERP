<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesReportController extends Controller
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

    private function applySalesInvoiceScope($query)
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

    private function applySalesReturnScope($query)
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

    private function assertCustomerAllowed($customerId): void
    {
        if (! $customerId || $customerId === 'cash') {
            return;
        }

        $exists = Customer::query()
            ->whereKey((int) $customerId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'العميل غير صحيح أو غير نشط.');
    }

    private function branches()
    {
        return $this->applyBranchScope(
                Branch::query()->where('is_active', true)
            )
            ->orderBy('branch_name')
            ->get();
    }

    private function customers()
    {
        /*
            العملاء عامّون داخل النسخة الحالية.
            التقرير يفلتر الفواتير حسب الفرع، وليس العملاء.
        */
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('customer_name')
            ->get();
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()?->can('sales_reports.view'), 403);

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'customer_id' => ['nullable'],
            'branch_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['draft', 'posted', 'cancelled'])],
        ]);

        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? null;
        $customerId = $data['customer_id'] ?? null;
        $branchId = $data['branch_id'] ?? null;
        $status = $data['status'] ?? null;

        if ($customerId === '') {
            $customerId = null;
        }

        if ($customerId !== null && $customerId !== 'cash') {
            abort_unless(is_numeric($customerId), 422, 'العميل غير صحيح.');
            $customerId = (int) $customerId;
        }

        if ($branchId !== null && $branchId !== '') {
            $branchId = (int) $branchId;
            $this->assertBranchAllowed($branchId);
        } else {
            $branchId = null;
        }

        $this->assertCustomerAllowed($customerId);

        /*
        |--------------------------------------------------------------------------
        | فواتير البيع
        |--------------------------------------------------------------------------
        */
        $invoicesQuery = SalesInvoice::query()
            ->with([
                'customer',
                'branch',
                'warehouse',
            ]);

        $this->applySalesInvoiceScope($invoicesQuery);

        if ($dateFrom) {
            $invoicesQuery->whereDate('invoice_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $invoicesQuery->whereDate('invoice_date', '<=', $dateTo);
        }

        if ($customerId === 'cash') {
            /*
                في النظام الجديد نعتمد أن فواتير النقدي بدون customer_id.
                إذا أضفت customer_type في المايقريشن لاحقًا نضيفه هنا صراحة.
            */
            $invoicesQuery->whereNull('customer_id');
        } elseif ($customerId) {
            $invoicesQuery->where('customer_id', $customerId);
        }

        if ($branchId) {
            $invoicesQuery->where('branch_id', $branchId);
        }

        if ($status) {
            $invoicesQuery->where('status', $status);
        }

        $invoices = (clone $invoicesQuery)
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | ملخص الفواتير المرحلة فقط
        |--------------------------------------------------------------------------
        */
        $postedInvoicesQuery = (clone $invoicesQuery)
            ->where('status', 'posted');

        $totalSubtotal = (clone $postedInvoicesQuery)->sum('subtotal');
        $totalDiscount = (clone $postedInvoicesQuery)->sum('discount_amount');
        $totalVat = (clone $postedInvoicesQuery)->sum('vat_amount');
        $totalSales = (clone $postedInvoicesQuery)->sum('total_amount');
        $totalPaid = (clone $postedInvoicesQuery)->sum('paid_amount');
        $totalRemaining = (clone $postedInvoicesQuery)->sum('remaining_amount');

        /*
        |--------------------------------------------------------------------------
        | مردودات المبيعات المرحلة
        |--------------------------------------------------------------------------
        */
        $returnsQuery = SalesReturn::query()
            ->where('status', 'posted');

        $this->applySalesReturnScope($returnsQuery);

        if ($dateFrom) {
            $returnsQuery->whereDate('return_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $returnsQuery->whereDate('return_date', '<=', $dateTo);
        }

        if ($customerId === 'cash') {
            $returnsQuery->whereNull('customer_id');
        } elseif ($customerId) {
            $returnsQuery->where('customer_id', $customerId);
        }

        if ($branchId) {
            $returnsQuery->where('branch_id', $branchId);
        }

        $totalReturns = (clone $returnsQuery)->sum('total_amount');

        $netSales = $totalSales - $totalReturns;

        $customers = $this->customers();
        $branches = $this->branches();

        return view('sales-reports.index', compact(
            'invoices',
            'customers',
            'branches',
            'dateFrom',
            'dateTo',
            'customerId',
            'branchId',
            'status',
            'totalSubtotal',
            'totalDiscount',
            'totalVat',
            'totalSales',
            'totalPaid',
            'totalRemaining',
            'totalReturns',
            'netSales'
        ));
    }
}