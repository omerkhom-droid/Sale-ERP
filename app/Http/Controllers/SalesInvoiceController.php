<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesInvoiceRequest;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SalesInvoice;
use App\Models\Warehouse;
use App\Services\SalesInvoiceService;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SalesInvoiceController extends Controller
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
            يرى كل فروع شركته.
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

    private function applyWarehouseScope($query, bool $onlyActive = false)
    {
        if ($onlyActive) {
            $query->where('is_active', true);
        }

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    private function applyInvoiceScope($query)
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

    private function assertInvoiceAccess(SalesInvoice $salesInvoice): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array((int) $salesInvoice->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى فاتورة من فرع آخر.'
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
    }

    private function assertWarehouseAllowed(?int $warehouseId, ?int $branchId = null): Warehouse
    {
        abort_unless($warehouseId, 403, 'المستودع غير صحيح.');

        $query = Warehouse::query()
            ->whereKey($warehouseId)
            ->where('is_active', true);

        $this->applyWarehouseScope($query);

        $warehouse = $query->first();

        abort_unless($warehouse, 403, 'لا تملك صلاحية استخدام هذا المستودع أو أن المستودع غير نشط.');

        if ($branchId && (int) $warehouse->branch_id !== (int) $branchId) {
            abort(403, 'المستودع المحدد لا يتبع الفرع المختار.');
        }

        return $warehouse;
    }

    private function assertCustomerAllowed(?int $customerId): void
    {
        if (! $customerId) {
            return;
        }

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

    private function assertProductsAndUnitsAllowed(array $data): void
    {
        $items = collect($data['items'] ?? []);

        if ($items->isEmpty()) {
            return;
        }

        $productIds = $items
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isNotEmpty()) {
            $allowedCount = Product::query()
                ->whereIn('id', $productIds)
                ->where('is_active', true)
                ->count();

            if ($allowedCount !== $productIds->count()) {
                abort(403, 'أحد الأصناف المحددة غير صحيح أو غير نشط.');
            }
        }

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $productUnitId = $item['product_unit_id'] ?? null;

            if (! $productId || ! $productUnitId) {
                continue;
            }

            $exists = ProductUnit::query()
                ->where('id', $productUnitId)
                ->where('product_id', $productId)
                ->exists();

            abort_unless($exists, 403, 'إحدى الوحدات لا تتبع الصنف المحدد.');
        }
    }

    private function prepareSecureInvoiceData(array $data): array
    {
        $user = auth()->user();

        /*
            branch_admin / user:
            لا نأخذ الفرع من الفورم.
            نثبت الفرع من المستخدم.
        */
        if (! $this->actorCanSeeAllCompanyBranches()) {
            $data['branch_id'] = $user?->branch_id;
        }

        $branchId = (int) ($data['branch_id'] ?? 0);
        $warehouseId = (int) ($data['warehouse_id'] ?? 0);

        $this->assertBranchAllowed($branchId);

        $warehouse = $this->assertWarehouseAllowed($warehouseId, $branchId);

        $this->assertCustomerAllowed((int) ($data['customer_id'] ?? 0));

        $this->assertCostCenterAllowed((int) ($data['cost_center_id'] ?? 0));

        $this->assertProductsAndUnitsAllowed($data);

        /*
            الفرع النهائي يؤخذ من الفرع المختار،
            مع التأكد أن المستودع يتبع نفس الفرع.
        */
        $data['branch_id'] = $branchId ?: $warehouse->branch_id;

        return $data;
    }

    private function activeCustomers()
    {
        /*
            العملاء عامّون داخل النسخة الحالية.
            لا نربطهم بالشركة هنا.
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

    private function activeWarehouses()
    {
        return $this->applyWarehouseScope(
                Warehouse::query(),
                true
            )
            ->orderBy('warehouse_name')
            ->get();
    }

    private function activeProducts()
    {
        /*
            المنتجات عامة داخل النظام.
            المخزون هو الذي يتبع المستودع.
        */
        return Product::query()
            ->where('is_active', true)
            ->orderBy('id', 'desc')
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
        abort_unless(auth()->user()?->can('sales_invoices.view'), 403);

        return view('sales-invoices.index');
    }

    public function fetch(Request $request)
    {
        abort_unless(auth()->user()?->can('sales_invoices.view'), 403);

        $user = auth()->user();

        $query = SalesInvoice::query()
            ->with([
                'customer',
                'branch',
                'warehouse',
            ]);

        $this->applyInvoiceScope($query);

        if ($request->filled('status')) {
            abort_unless(
                in_array($request->status, ['draft', 'posted', 'cancelled'], true),
                422,
                'حالة الفاتورة غير صحيحة.'
            );

            $query->where('status', $request->status);
        }

        $query->latest('id');

        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('invoice_date', function ($invoice) {
                return $invoice->invoice_date
                    ? $invoice->invoice_date->format('Y-m-d')
                    : '-';
            })

            ->addColumn('customer_display', function ($invoice) {
                return e($invoice->customer_name ?: 'عميل نقدي');
            })

            ->addColumn('total_amount_display', function ($invoice) {
                return number_format((float) $invoice->total_amount, 2);
            })

            ->addColumn('remaining_amount_display', function ($invoice) {
                return number_format((float) $invoice->remaining_amount, 2);
            })

            ->addColumn('status_badge', function ($invoice) {
                return match ($invoice->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'posted' => '<span class="badge bg-success">مرحلة</span>',
                    'cancelled' => '<span class="badge bg-danger">ملغاة</span>',
                    default => '<span class="badge bg-light text-dark">' . e($invoice->status) . '</span>',
                };
            })

            ->addColumn('payment_status_badge', function ($invoice) {
                return match ($invoice->payment_status) {
                    'unpaid' => '<span class="badge bg-danger">غير مدفوعة</span>',
                    'partial' => '<span class="badge bg-warning text-dark">جزئي</span>',
                    'paid' => '<span class="badge bg-success">مدفوعة</span>',
                    default => '<span class="badge bg-light text-dark">' . e($invoice->payment_status) . '</span>',
                };
            })

            ->addColumn('actions', function ($invoice) use ($user) {
                $buttons = '<div class="d-flex gap-1 justify-content-center text-center flex-wrap">';

                if ($user && $user->can('sales_invoices.view')) {
                    $buttons .= '
                        <a href="' . route('sales-invoices.show', $invoice->id) . '"
                           class="btn btn-sm btn-info">
                            عرض
                        </a>
                    ';
                }

                if ($user && $user->can('sales_invoices.print')) {
                    $buttons .= '
                        <a href="' . route('sales-invoices.print', $invoice->id) . '"
                           target="_blank"
                           class="btn btn-sm btn-dark">
                            طباعة
                        </a>
                    ';
                }

                $buttons .= '</div>';

                return $buttons === '<div class="d-flex gap-1 justify-content-center text-center flex-wrap"></div>'
                    ? '<span class="text-muted">-</span>'
                    : $buttons;
            })

            ->rawColumns([
                'status_badge',
                'payment_status_badge',
                'actions',
            ])

            ->make(true);
    }

    public function create()
    {
        abort_unless(auth()->user()?->can('sales_invoices.create'), 403);

        $customers = $this->activeCustomers();
        $branches = $this->activeBranches();
        $warehouses = $this->activeWarehouses();
        $costCenters = $this->activeCostCenters();


        return view('sales-invoices.create', compact(
            'customers',
            'branches',
            'warehouses',
            'costCenters'
        ));
    }

    public function store(
        StoreSalesInvoiceRequest $request,
        SalesInvoiceService $service
    ) {
        abort_unless(auth()->user()?->can('sales_invoices.create'), 403);

        $data = $this->prepareSecureInvoiceData($request->validated());

        try {
            $invoice = $service->store($data);

            return redirect()
                ->route('sales-invoices.show', $invoice->id)
                ->with('success', 'تم حفظ فاتورة البيع بنجاح.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(SalesInvoice $salesInvoice)
    {
        abort_unless(auth()->user()?->can('sales_invoices.view'), 403);

        $this->assertInvoiceAccess($salesInvoice);

        $salesInvoice->load([
            'customer',
            'branch',
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'creator',
            'postedBy',
            'cancelledBy',
        ]);

        return view('sales-invoices.show', compact('salesInvoice'));
    }

    public function post(
        SalesInvoice $salesInvoice,
        SalesInvoiceService $service
    ) {
        abort_unless(auth()->user()?->can('sales_invoices.post'), 403);

        $this->assertInvoiceAccess($salesInvoice);

        if ($salesInvoice->status !== 'draft') {
            return back()->with('error', 'لا يمكن ترحيل فاتورة غير مسودة.');
        }

        try {
            $service->post($salesInvoice);

            return redirect()
                ->route('sales-invoices.show', $salesInvoice->id)
                ->with('success', 'تم ترحيل فاتورة البيع بنجاح.');

        } catch (Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    public function cancel(
        Request $request,
        SalesInvoice $salesInvoice,
        SalesInvoiceService $service
    ) {
        abort_unless(auth()->user()?->can('sales_invoices.cancel'), 403);

        $this->assertInvoiceAccess($salesInvoice);

        if ($salesInvoice->status === 'cancelled') {
            return back()->with('error', 'الفاتورة ملغاة مسبقًا.');
        }

        $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->cancel(
                invoice: $salesInvoice,
                reason: $request->cancel_reason
            );

            return redirect()
                ->route('sales-invoices.show', $salesInvoice->id)
                ->with('success', 'تم إلغاء فاتورة البيع بنجاح.');

        } catch (Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    public function print(SalesInvoice $salesInvoice)
    {
        abort_unless(auth()->user()?->can('sales_invoices.print'), 403);

        $this->assertInvoiceAccess($salesInvoice);

        $salesInvoice->load([
            'customer',
            'branch',
            'warehouse',
            'items.product',
            'items.productUnit.unit',
        ]);

        return view('sales-invoices.print', compact('salesInvoice'));
    }

    public function productUnits(Product $product)
    {
        abort_unless(auth()->user()?->can('sales_invoices.create'), 403);

        $exists = Product::query()
            ->whereKey($product->id)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'الصنف غير صحيح أو غير نشط.');

        $units = ProductUnit::query()
            ->with('unit')
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($productUnit) {
                return [
                    'id' => $productUnit->id,

                    'unit_name' => $productUnit->unit?->unit_name_ar
                        ?? $productUnit->unit?->unit_name
                        ?? $productUnit->unit?->name
                        ?? 'وحدة',

                    'conversion_factor' => $productUnit->factor
                        ?? $productUnit->conversion_factor
                        ?? 1,

                    'sale_price' => (float) ($productUnit->sale_price ?? 0),

                    'minimum_sale_price' => (float) ($productUnit->minimum_sale_price ?? 0),

                    'is_default' => (int) ($productUnit->is_default ?? 0),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $units,
        ]);
    }

    public function customerData(Customer $customer)
    {
        abort_unless(auth()->user()?->can('sales_invoices.create'), 403);

        $this->assertCustomerAllowed($customer->id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'customer_name' => $customer->customer_name
                    ?? $customer->name
                    ?? $customer->fullname
                    ?? '',

                'customer_mobile' => $customer->mobile
                    ?? $customer->phone
                    ?? '',

                'customer_tax_number' => $customer->tax_registration_number
                    ?? $customer->tax_number
                    ?? $customer->vat_number
                    ?? '',

                'customer_address' => $customer->address
                    ?? $customer->full_address
                    ?? '',
            ],
        ]);
    }
}