<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Throwable;

class DashboardController extends Controller
{
    public function index(): View
    {
        if ($this->canAccessAdminDashboard()) {
            return view('dashboard', array_merge(
                ['dashboardMode' => 'admin'],
                $this->adminData()
            ));
        }

        return view('dashboard', array_merge(
            ['dashboardMode' => 'shortcuts'],
            $this->employeeData()
        ));
    }

    private function actorUserType(): string
    {
        return auth()->user()?->user_type ?? 'user';
    }

    private function canAccessAdminDashboard(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
            'company_owner',
            'company_admin',
            'branch_admin',
        ], true);
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

    private function applyDashboardScope($query, string $table)
    {
        if (! Schema::hasTable($table)) {
            return $query->whereRaw('1 = 0');
        }

        if (! Schema::hasColumn($table, 'branch_id')) {
            return $query;
        }

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($table . '.branch_id', $branchIds);
    }

    private function adminData(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $todaySales = $this->sumDocumentAmount('sales_invoices', $today, $today);
        $monthSales = $this->sumDocumentAmount('sales_invoices', $startOfMonth, Carbon::today());
        $monthPurchases = $this->sumDocumentAmount('purchase_invoices', $startOfMonth, Carbon::today());
        $monthReturns = $this->sumDocumentAmount('sales_returns', $startOfMonth, Carbon::today());

        return [
            'todaySales' => $todaySales,
            'monthSales' => $monthSales,
            'monthPurchases' => $monthPurchases,
            'monthReturns' => $monthReturns,
            'netMonthSales' => $monthSales - $monthReturns,

            'salesInvoicesCount' => $this->countRows('sales_invoices'),
            'purchaseInvoicesCount' => $this->countRows('purchase_invoices'),
            'customersCount' => $this->countRows('customers'),
            'suppliersCount' => $this->countRows('suppliers'),
            'productsCount' => $this->countRows('products'),
            'warehousesCount' => $this->countRows('warehouses'),

            'draftSalesInvoicesCount' => $this->countByStatus('sales_invoices', 'draft'),
            'postedSalesInvoicesCount' => $this->countByStatus('sales_invoices', 'posted'),
            'cancelledSalesInvoicesCount' => $this->countByStatus('sales_invoices', 'cancelled'),

            'draftPurchaseInvoicesCount' => $this->countByStatus('purchase_invoices', 'draft'),
            'postedPurchaseInvoicesCount' => $this->countByStatus('purchase_invoices', 'posted'),
            'cancelledPurchaseInvoicesCount' => $this->countByStatus('purchase_invoices', 'cancelled'),

            'lowStockCount' => $this->lowStockCount(),

            'salesChartLabels' => $this->lastSevenDaysLabels(),
            'salesChartValues' => $this->lastSevenDaysTotals('sales_invoices'),
            'purchaseChartValues' => $this->lastSevenDaysTotals('purchase_invoices'),

            'monthlyChartLabels' => $this->lastSixMonthsLabels(),
            'monthlySalesValues' => $this->lastSixMonthsTotals('sales_invoices'),
            'monthlyPurchasesValues' => $this->lastSixMonthsTotals('purchase_invoices'),

            'salesStatusChart' => $this->statusChartData('sales_invoices'),
            'purchaseStatusChart' => $this->statusChartData('purchase_invoices'),

            'topBranchesBySales' => $this->topBranchesBySales(),
            'topCustomersBySales' => $this->topCustomersBySales(),
            'topProductsBySales' => $this->topProductsBySales(),

            'recentSalesInvoices' => $this->latestDocuments('sales_invoices', 'sales-invoices.show'),
            'recentPurchaseInvoices' => $this->latestDocuments('purchase_invoices', 'purchase-invoices.show'),
            'recentCustomerReceipts' => $this->latestDocuments('customer_receipt_vouchers', 'customer-receipt-vouchers.show'),
            'recentSupplierPayments' => $this->latestDocuments('supplier_payment_vouchers', 'supplier-payment-vouchers.show'),

            'managementCards' => $this->managementCards(),
        ];
    }

    private function employeeData(): array
    {
        $user = auth()->user();

        $shortcuts = collect([
            [
                'title' => 'فاتورة بيع',
                'description' => 'إنشاء فاتورة بيع جديدة',
                'route' => 'sales-invoices.create',
                'permission' => 'sales_invoices.create',
                'icon' => 'bi-receipt',
            ],
            [
                'title' => 'عرض سعر',
                'description' => 'إنشاء عرض سعر جديد',
                'route' => 'quotations.create',
                'permission' => 'quotations.create',
                'icon' => 'bi-file-earmark-text',
            ],
            [
                'title' => 'مرتجع بيع',
                'description' => 'إنشاء مرتجع مبيعات',
                'route' => 'sales-returns.create',
                'permission' => 'sales_returns.create',
                'icon' => 'bi-arrow-counterclockwise',
            ],
            [
                'title' => 'سند قبض عميل',
                'description' => 'تسجيل قبض من عميل',
                'route' => 'customer-receipt-vouchers.create',
                'permission' => 'customer_receipt_vouchers.create',
                'icon' => 'bi-cash-coin',
            ],
            [
                'title' => 'سند صرف مورد',
                'description' => 'تسجيل صرف لمورد',
                'route' => 'supplier-payment-vouchers.create',
                'permission' => 'supplier_payment_vouchers.create',
                'icon' => 'bi-wallet2',
            ],
            [
                'title' => 'حركة المخزون',
                'description' => 'عرض تقرير حركة المخزون',
                'route' => 'inventory-movements.index',
                'permission' => 'inventory_movement_reports.view',
                'icon' => 'bi-box-seam',
            ],
        ])->filter(function ($shortcut) use ($user) {
            return Route::has($shortcut['route'])
                && $this->safeCan($user, $shortcut['permission']);
        })->values();

        return [
            'employeeMessage' => 'اختصارات تشغيلية فقط بدون عرض بيانات مالية حساسة.',
            'shortcuts' => $shortcuts,
        ];
    }

    private function managementCards(): array
    {
        return collect([
            [
                'title' => 'إدارة المبيعات',
                'description' => 'فواتير البيع، المرتجعات، عروض الأسعار وسندات القبض.',
                'icon' => 'bi-graph-up-arrow',
                'route' => 'sales-invoices.index',
                'permission' => 'sales_invoices.view',
                'value' => $this->countRows('sales_invoices'),
                'label' => 'فاتورة بيع',
            ],
            [
                'title' => 'إدارة المشتريات',
                'description' => 'فواتير الشراء، المرتجعات وسندات صرف الموردين.',
                'icon' => 'bi-cart-check',
                'route' => 'purchase-invoices.index',
                'permission' => 'purchase_invoices.view',
                'value' => $this->countRows('purchase_invoices'),
                'label' => 'فاتورة شراء',
            ],
            [
                'title' => 'إدارة المخزون',
                'description' => 'الأصناف، المستودعات، الأرصدة وحركة المخزون.',
                'icon' => 'bi-box-seam',
                'route' => 'products.index',
                'permission' => 'products.view',
                'value' => $this->lowStockCount(),
                'label' => 'صنف منخفض',
            ],
            [
                'title' => 'إدارة الحسابات',
                'description' => 'القيود اليومية، الأرصدة الافتتاحية والتقارير المالية.',
                'icon' => 'bi-journal-text',
                'route' => 'manual-journal-entries.index',
                'permission' => 'manual_journal_entries.view',
                'value' => $this->countRows('manual_journal_entries'),
                'label' => 'قيد يومية',
            ],
            [
                'title' => 'إدارة العملاء',
                'description' => 'بيانات العملاء، كشوف الحساب وأرصدة العملاء.',
                'icon' => 'bi-people',
                'route' => 'customers.index',
                'permission' => 'customers.view',
                'value' => $this->countRows('customers'),
                'label' => 'عميل',
            ],
            [
                'title' => 'إدارة الموردين',
                'description' => 'بيانات الموردين، كشوف الحساب وأرصدة الموردين.',
                'icon' => 'bi-truck',
                'route' => 'suppliers.index',
                'permission' => 'suppliers.view',
                'value' => $this->countRows('suppliers'),
                'label' => 'مورد',
            ],
            [
                'title' => 'التقارير',
                'description' => 'تقارير المبيعات، الأرباح، المخزون والتقارير المالية.',
                'icon' => 'bi-bar-chart',
                'route' => 'sales-reports.index',
                'permission' => 'sales_reports.view',
                'value' => 8,
                'label' => 'تقرير رئيسي',
            ],
            [
                'title' => 'الإعدادات',
                'description' => 'الشركات، الفروع، المستخدمون، الصلاحيات والنسخ الاحتياطي.',
                'icon' => 'bi-gear',
                'route' => 'branches.index',
                'permission' => 'branches.view',
                'value' => $this->countRows('branches'),
                'label' => 'فرع',
            ],
        ])->filter(function ($card) {
            return Route::has($card['route'])
                && $this->safeCan(auth()->user(), $card['permission']);
        })->values()->toArray();
    }

    private function safeCan($user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        try {
            $exists = Permission::query()
                ->where('name', $permission)
                ->where('guard_name', 'web')
                ->exists();

            if (! $exists) {
                return false;
            }

            return $user->can($permission);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function sumDocumentAmount(string $table, Carbon $from, Carbon $to): float
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $amountColumn = $this->firstExistingColumn($table, [
            'grand_total',
            'total_with_vat',
            'net_total',
            'total_amount',
            'amount',
            'paid_amount',
            'total',
        ]);

        if (! $amountColumn) {
            return 0;
        }

        $dateColumn = $this->firstExistingColumn($table, [
            'invoice_date',
            'voucher_date',
            'return_date',
            'document_date',
            'date',
            'created_at',
        ]);

        $query = DB::table($table);

        $this->applyDashboardScope($query, $table);

        if ($dateColumn) {
            $query->whereDate($dateColumn, '>=', $from->toDateString())
                ->whereDate($dateColumn, '<=', $to->toDateString());
        }

        if (Schema::hasColumn($table, 'status')) {
            $query->whereIn('status', ['posted', 'approved']);
        }

        return (float) $query->sum($amountColumn);
    }

    private function countRows(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        $this->applyDashboardScope($query, $table);

        return (int) $query->count();
    }

    private function countByStatus(string $table, string $status): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status')) {
            return 0;
        }

        $query = DB::table($table)->where('status', $status);

        $this->applyDashboardScope($query, $table);

        return (int) $query->count();
    }

    private function lastSevenDaysLabels(): array
    {
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $labels[] = Carbon::today()->subDays($i)->format('m-d');
        }

        return $labels;
    }

    private function lastSevenDaysTotals(string $table): array
    {
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $values[] = $this->sumDocumentAmount($table, $day, $day);
        }

        return $values;
    }

    private function lastSixMonthsLabels(): array
    {
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $labels[] = Carbon::now()->subMonths($i)->format('Y-m');
        }

        return $labels;
    }

    private function lastSixMonthsTotals(string $table): array
    {
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);

            $values[] = $this->sumDocumentAmount(
                $table,
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth()
            );
        }

        return $values;
    }

    private function statusChartData(string $table): array
    {
        return [
            'draft' => $this->countByStatus($table, 'draft'),
            'posted' => $this->countByStatus($table, 'posted'),
            'cancelled' => $this->countByStatus($table, 'cancelled'),
        ];
    }

    private function lowStockCount(): int
    {
        if (! Schema::hasTable('products')) {
            return 0;
        }

        $quantityColumn = $this->firstExistingColumn('products', [
            'quantity',
            'current_quantity',
            'stock',
            'available_quantity',
        ]);

        if (! $quantityColumn || ! Schema::hasColumn('products', 'minimum_quantity')) {
            return 0;
        }

        return (int) DB::table('products')
            ->whereColumn($quantityColumn, '<=', 'minimum_quantity')
            ->count();
    }

    private function topBranchesBySales(int $limit = 5): array
    {
        if (
            ! Schema::hasTable('sales_invoices')
            || ! Schema::hasTable('branches')
            || ! Schema::hasColumn('sales_invoices', 'branch_id')
        ) {
            return [];
        }

        $amountColumn = $this->firstExistingColumn('sales_invoices', [
            'grand_total',
            'total_with_vat',
            'net_total',
            'total_amount',
            'total',
        ]);

        if (! $amountColumn) {
            return [];
        }

        $branchNameColumn = $this->firstExistingColumn('branches', [
            'branch_name_ar',
            'branch_name',
            'name',
        ]) ?? 'id';

        $query = DB::table('sales_invoices')
            ->join('branches', 'branches.id', '=', 'sales_invoices.branch_id')
            ->selectRaw('branches.' . $branchNameColumn . ' as name')
            ->selectRaw('SUM(sales_invoices.' . $amountColumn . ') as total')
            ->where('sales_invoices.status', 'posted')
            ->groupBy('branches.' . $branchNameColumn)
            ->orderByDesc('total')
            ->limit($limit);

        $this->applyDashboardScope($query, 'sales_invoices');

        return $query->get()
            ->map(fn ($row) => [
                'name' => $row->name ?? '-',
                'total' => (float) $row->total,
            ])
            ->toArray();
    }

    private function topCustomersBySales(int $limit = 5): array
    {
        if (
            ! Schema::hasTable('sales_invoices')
            || ! Schema::hasTable('customers')
            || ! Schema::hasColumn('sales_invoices', 'customer_id')
        ) {
            return [];
        }

        $amountColumn = $this->firstExistingColumn('sales_invoices', [
            'grand_total',
            'total_with_vat',
            'net_total',
            'total_amount',
            'total',
        ]);

        if (! $amountColumn) {
            return [];
        }

        $customerNameColumn = $this->firstExistingColumn('customers', [
            'customer_name',
            'name',
        ]) ?? 'id';

        $query = DB::table('sales_invoices')
            ->join('customers', 'customers.id', '=', 'sales_invoices.customer_id')
            ->selectRaw('customers.' . $customerNameColumn . ' as name')
            ->selectRaw('SUM(sales_invoices.' . $amountColumn . ') as total')
            ->where('sales_invoices.status', 'posted')
            ->groupBy('customers.' . $customerNameColumn)
            ->orderByDesc('total')
            ->limit($limit);

        $this->applyDashboardScope($query, 'sales_invoices');

        return $query->get()
            ->map(fn ($row) => [
                'name' => $row->name ?? '-',
                'total' => (float) $row->total,
            ])
            ->toArray();
    }

    private function topProductsBySales(int $limit = 5): array
    {
        if (
            ! Schema::hasTable('sales_invoice_items')
            || ! Schema::hasTable('sales_invoices')
            || ! Schema::hasTable('products')
            || ! Schema::hasColumn('sales_invoice_items', 'product_id')
            || ! Schema::hasColumn('sales_invoice_items', 'sales_invoice_id')
        ) {
            return [];
        }

        $amountColumn = $this->firstExistingColumn('sales_invoice_items', [
            'net_amount',
            'total_amount',
            'line_total',
            'subtotal',
            'total',
        ]);

        if (! $amountColumn) {
            return [];
        }

        $productNameColumn = $this->firstExistingColumn('products', [
            'product_name_ar',
            'product_name',
            'name',
        ]) ?? 'id';

        $query = DB::table('sales_invoice_items')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
            ->selectRaw('products.' . $productNameColumn . ' as name')
            ->selectRaw('SUM(sales_invoice_items.' . $amountColumn . ') as total')
            ->where('sales_invoices.status', 'posted')
            ->groupBy('products.' . $productNameColumn)
            ->orderByDesc('total')
            ->limit($limit);

        $this->applyDashboardScope($query, 'sales_invoices');

        return $query->get()
            ->map(fn ($row) => [
                'name' => $row->name ?? '-',
                'total' => (float) $row->total,
            ])
            ->toArray();
    }

    private function latestDocuments(string $table, string $routeName, int $limit = 5): array
    {
        if (! Schema::hasTable($table) || ! Route::has($routeName)) {
            return [];
        }

        $numberColumn = $this->firstExistingColumn($table, [
            'invoice_number',
            'invoice_no',
            'voucher_number',
            'voucher_no',
            'return_number',
            'return_no',
            'document_number',
            'number',
            'code',
            'reference_no',
        ]);

        $dateColumn = $this->firstExistingColumn($table, [
            'invoice_date',
            'voucher_date',
            'return_date',
            'document_date',
            'date',
            'created_at',
        ]);

        $amountColumn = $this->firstExistingColumn($table, [
            'grand_total',
            'total_with_vat',
            'net_total',
            'total_amount',
            'amount',
            'paid_amount',
            'total',
        ]);

        $statusColumn = Schema::hasColumn($table, 'status') ? 'status' : null;

        $query = DB::table($table)->select('id');

        $this->applyDashboardScope($query, $table);

        if ($numberColumn) {
            $query->addSelect($numberColumn . ' as document_number');
        }

        if ($dateColumn) {
            $query->addSelect($dateColumn . ' as document_date');
        }

        if ($amountColumn) {
            $query->addSelect($amountColumn . ' as document_amount');
        }

        if ($statusColumn) {
            $query->addSelect($statusColumn . ' as document_status');
        }

        if ($dateColumn) {
            $query->orderByDesc($dateColumn);
        } else {
            $query->orderByDesc('id');
        }

        return $query
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($routeName) {
                return [
                    'id' => $row->id,
                    'number' => $row->document_number ?? ('#' . $row->id),
                    'date' => $this->formatDashboardDate($row->document_date ?? null),
                    'amount' => (float) ($row->document_amount ?? 0),
                    'status' => $row->document_status ?? null,
                    'route' => $routeName,
                ];
            })
            ->toArray();
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function formatDashboardDate($date): string
    {
        if (! $date) {
            return '-';
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (Throwable $e) {
            return (string) $date;
        }
    }
}