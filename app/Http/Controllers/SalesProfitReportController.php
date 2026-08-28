<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturnItem;
use Illuminate\Http\Request;

class SalesProfitReportController extends Controller
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
            التقرير يفلتر الربحية حسب فروع الفواتير، وليس حسب فرع العميل.
        */
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('customer_name')
            ->get();
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()?->can('sales_profit_reports.view'), 403);

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'customer_id' => ['nullable'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? null;
        $customerId = $data['customer_id'] ?? null;
        $branchId = $data['branch_id'] ?? null;

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
        | بنود فواتير البيع المرحلة
        |--------------------------------------------------------------------------
        */
        $salesItemsQuery = SalesInvoiceItem::query()
            ->with([
                'salesInvoice.customer',
                'salesInvoice.branch',
                'product',
            ])
            ->whereHas('salesInvoice', function ($query) use ($dateFrom, $dateTo, $customerId, $branchId) {
                $query->where('status', 'posted');

                $this->applySalesInvoiceScope($query);

                if ($dateFrom) {
                    $query->whereDate('invoice_date', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $query->whereDate('invoice_date', '<=', $dateTo);
                }

                if ($customerId === 'cash') {
                    /*
                        في النظام الجديد فواتير النقدي بدون customer_id.
                    */
                    $query->whereNull('customer_id');
                } elseif ($customerId) {
                    $query->where('customer_id', $customerId);
                }

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            });

        /*
        |--------------------------------------------------------------------------
        | بنود مردودات المبيعات المرحلة
        |--------------------------------------------------------------------------
        */
        $returnItemsQuery = SalesReturnItem::query()
            ->with([
                'salesReturn.customer',
                'salesReturn.branch',
                'product',
            ])
            ->whereHas('salesReturn', function ($query) use ($dateFrom, $dateTo, $customerId, $branchId) {
                $query->where('status', 'posted');

                $this->applySalesReturnScope($query);

                if ($dateFrom) {
                    $query->whereDate('return_date', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $query->whereDate('return_date', '<=', $dateTo);
                }

                if ($customerId === 'cash') {
                    /*
                        في النظام الجديد مردودات النقدي بدون customer_id.
                    */
                    $query->whereNull('customer_id');
                } elseif ($customerId) {
                    $query->where('customer_id', $customerId);
                }

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            });

        /*
        |--------------------------------------------------------------------------
        | الإجماليات
        |--------------------------------------------------------------------------
        */
        $totalSalesWithoutVat = (clone $salesItemsQuery)->sum('net_amount');
        $totalSalesCost = (clone $salesItemsQuery)->sum('total_cost');

        $totalReturnsWithoutVat = (clone $returnItemsQuery)->sum('net_amount');
        $totalReturnsCost = (clone $returnItemsQuery)->sum('total_cost');

        $netSalesWithoutVat = $totalSalesWithoutVat - $totalReturnsWithoutVat;
        $netCost = $totalSalesCost - $totalReturnsCost;

        $grossProfit = $netSalesWithoutVat - $netCost;

        $profitPercent = $netSalesWithoutVat > 0
            ? ($grossProfit / $netSalesWithoutVat) * 100
            : 0;

        /*
        |-----------------------------------------------    ---------------------------
        | تفاصيل الأصناف المباعة
        |--------------------------------------------------------------------------
        */
        $items = (clone $salesItemsQuery)
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $customers = $this->customers();
        $branches = $this->branches();

        return view('sales-profit-reports.index', compact(
            'items',
            'customers',
            'branches',
            'dateFrom',
            'dateTo',
            'customerId',
            'branchId',
            'totalSalesWithoutVat',
            'totalSalesCost',
            'totalReturnsWithoutVat',
            'totalReturnsCost',
            'netSalesWithoutVat',
            'netCost',
            'grossProfit',
            'profitPercent'
        ));
    }
}