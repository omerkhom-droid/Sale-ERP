<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Quotation;
use App\Models\Warehouse;
use App\Services\QuotationService;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use App\Services\SalesInvoiceService;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
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

    private function actorCanAccessAllBranches(): bool
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

    private function applyQuotationScope($query)
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

    private function assertQuotationAccess(Quotation $quotation): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array((int) $quotation->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى عرض السعر هذا.'
        );
    }

    private function assertBranchAllowed(?int $branchId): void
    {
        if (! $branchId) {
            abort(403, 'الفرع غير صحيح.');
        }

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            abort_unless(
                Branch::whereKey($branchId)->exists(),
                403,
                'الفرع غير صحيح.'
            );

            return;
        }

        abort_unless(
            in_array((int) $branchId, $branchIds, true),
            403,
            'لا تملك صلاحية استخدام هذا الفرع.'
        );
    }

    private function assertWarehouseAllowed(?int $warehouseId, ?int $branchId = null): ?Warehouse
    {
        if (! $warehouseId) {
            return null;
        }

        $warehouse = Warehouse::query()
            ->whereKey($warehouseId)
            ->first();

        abort_unless($warehouse, 403, 'المستودع غير صحيح.');

        $this->assertBranchAllowed((int) $warehouse->branch_id);

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

        abort_unless(
            Customer::whereKey($customerId)->exists(),
            403,
            'العميل المحدد غير صحيح.'
        );
    }

    private function assertCostCenterAllowed(?int $costCenterId): void
    {
        if (! $costCenterId) {
            return;
        }

        abort_unless(
            CostCenter::whereKey($costCenterId)->exists(),
            403,
            'مركز التكلفة المحدد غير صحيح.'
        );
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
                ->count();

            if ($allowedCount !== $productIds->count()) {
                abort(403, 'لا تملك صلاحية استخدام أحد الأصناف المحددة.');
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

    private function prepareSecureQuotationData(array $data): array
    {
        $user = auth()->user();

        if (! $this->actorCanAccessAllBranches()) {
            $data['branch_id'] = $user->branch_id;
        }

        $branchId = (int) ($data['branch_id'] ?? 0);
        $warehouseId = ! empty($data['warehouse_id'])
            ? (int) $data['warehouse_id']
            : null;

        $this->assertBranchAllowed($branchId);

        $warehouse = $this->assertWarehouseAllowed($warehouseId, $branchId);

        if ($warehouse && empty($data['branch_id'])) {
            $data['branch_id'] = $warehouse->branch_id;
        }

        $this->assertCustomerAllowed((int) ($data['customer_id'] ?? 0));

        $this->assertCostCenterAllowed((int) ($data['cost_center_id'] ?? 0));

        $this->assertProductsAndUnitsAllowed($data);

        return $data;
    }

    private function activeCustomers()
    {
        return Customer::query()
            ->when(
                $this->columnExistsOnModel(Customer::class, 'is_active'),
                fn ($query) => $query->where('is_active', 1)
            )
            ->orderBy('id', 'desc')
            ->get();
    }

    private function activeBranches()
    {
        $query = Branch::query();

        if ($this->columnExistsOnModel(Branch::class, 'is_active')) {
            $query->where('is_active', 1);
        }

        $branchIds = $this->actorBranchIds();

        if (! is_null($branchIds)) {
            if (empty($branchIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $branchIds);
            }
        }

        return $query->orderBy('branch_name')->get();
    }

    private function activeWarehouses()
    {
        $query = Warehouse::query();

        if ($this->columnExistsOnModel(Warehouse::class, 'is_active')) {
            $query->where('is_active', 1);
        }

        $branchIds = $this->actorBranchIds();

        if (! is_null($branchIds)) {
            if (empty($branchIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('branch_id', $branchIds);
            }
        }

        return $query->orderBy('warehouse_name')->get();
    }

    private function activeProducts()
    {
        return Product::query()
            ->when(
                $this->columnExistsOnModel(Product::class, 'is_active'),
                fn ($query) => $query->where('is_active', 1)
            )
            ->orderBy('id', 'desc')
            ->get();
    }

    private function activeCostCenters()
    {
        return CostCenter::query()
            ->when(
                $this->columnExistsOnModel(CostCenter::class, 'is_active'),
                fn ($query) => $query->where('is_active', 1)
            )
            ->orderBy('code')
            ->get();
    }

    private function columnExistsOnModel(string $modelClass, string $column): bool
    {
        /*
            وضعناها هنا فقط لتجنب كسر الصفحة إذا كان بعض الجداول
            لا يحتوي is_active.
            لا تستخدم لأي توافق قديم أو صلاحيات.
        */
        return \Illuminate\Support\Facades\Schema::hasColumn((new $modelClass)->getTable(), $column);
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('quotations.view'), 403);

        return view('quotations.index');
    }

    public function fetch(Request $request)
    {
        abort_unless(auth()->user()?->can('quotations.view'), 403);

        $user = auth()->user();

        $query = Quotation::query()
            ->with([
                'customer',
                'branch',
                'warehouse',
            ]);

        $this->applyQuotationScope($query);

        if ($request->filled('status')) {
            abort_unless(
                in_array($request->status, [
                    'draft',
                    'sent',
                    'approved',
                    'rejected',
                    'converted',
                    'cancelled',
                ], true),
                422,
                'حالة عرض السعر غير صحيحة.'
            );

            $query->where('status', $request->status);
        }

        $query->latest('id');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('quotation_date', function ($quotation) {
                return $quotation->quotation_date
                    ? $quotation->quotation_date->format('Y-m-d')
                    : '-';
            })

            ->editColumn('valid_until', function ($quotation) {
                return $quotation->valid_until
                    ? $quotation->valid_until->format('Y-m-d')
                    : '-';
            })
            ->addColumn('customer_display', function ($quotation) {
                return e($quotation->customer_name ?: 'عميل نقدي');
            })

            ->addColumn('total_amount_display', function ($quotation) {
                return number_format((float) $quotation->total_amount, 2);
            })

            ->addColumn('status_badge', function ($quotation) {
                return match ($quotation->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'sent' => '<span class="badge bg-info">مرسل</span>',
                    'approved' => '<span class="badge bg-success">معتمد</span>',
                    'rejected' => '<span class="badge bg-danger">مرفوض</span>',
                    'converted' => '<span class="badge bg-primary">محول لفاتورة</span>',
                    'cancelled' => '<span class="badge bg-dark">ملغي</span>',
                    default => '<span class="badge bg-light text-dark">' . e($quotation->status) . '</span>',
                };
            })

            ->addColumn('actions', function ($quotation) use ($user) {
                $buttons = '<div class="d-flex gap-1 justify-content-center text-center flex-wrap">';

                if ($user && $user->can('quotations.view')) {
                    $buttons .= '
                        <a href="' . route('quotations.show', $quotation->id) . '"
                           class="btn btn-sm btn-info">
                            عرض
                        </a>
                    ';
                }

                if (
                    $user
                    && $user->can('quotations.edit')
                    && ! in_array($quotation->status, ['converted', 'cancelled'], true)
                ) {
                    $buttons .= '
                        <a href="' . route('quotations.edit', $quotation->id) . '"
                           class="btn btn-sm btn-warning">
                            تعديل
                        </a>
                    ';
                }

                if ($user && $user->can('quotations.print')) {
                    $buttons .= '
                        <a href="' . route('quotations.print', $quotation->id) . '"
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
                'actions',
            ])

            ->make(true);
    }

    public function create()
    {
        abort_unless(auth()->user()?->can('quotations.create'), 403);

        $customers = $this->activeCustomers();
        $branches = $this->activeBranches();
        $warehouses = $this->activeWarehouses();
        $costCenters = $this->activeCostCenters();

        return view('quotations.create', compact(
            'customers',
            'branches',
            'warehouses',
            'costCenters'
        ));
    }

    public function store(
        StoreQuotationRequest $request,
        QuotationService $service
    ) {
        abort_unless(auth()->user()?->can('quotations.create'), 403);

        $data = $this->prepareSecureQuotationData($request->validated());

        try {
            $quotation = $service->store($data);

            return redirect()
                ->route('quotations.show', $quotation->id)
                ->with('success', 'تم حفظ عرض السعر بنجاح.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(Quotation $quotation)
    {
        abort_unless(auth()->user()?->can('quotations.view'), 403);

        $this->assertQuotationAccess($quotation);

        $quotation->load([
            'customer',
            'branch',
            'costCenter',
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'creator',
            'convertedSalesInvoice',
        ]);

        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        abort_unless(auth()->user()?->can('quotations.edit'), 403);

        $this->assertQuotationAccess($quotation);

        if (in_array($quotation->status, ['converted', 'cancelled'], true)) {
            return redirect()
                ->route('quotations.show', $quotation->id)
                ->with('error', 'لا يمكن تعديل عرض سعر محول إلى فاتورة أو ملغي.');
        }

        $quotation->load([
            'items.product',
            'items.productUnit.unit',
        ]);

        $customers = $this->activeCustomers();
        $branches = $this->activeBranches();
        $warehouses = $this->activeWarehouses();
        $products = $this->activeProducts();
        $costCenters = $this->activeCostCenters();

        $allowedProductIds = $products->pluck('id')->filter()->values();

        $productUnits = ProductUnit::with([
                'product',
                'unit',
            ])
            ->whereIn('product_id', $allowedProductIds)
            ->get();

        return view('quotations.edit', compact(
            'quotation',
            'customers',
            'branches',
            'warehouses',
            'products',
            'productUnits',
            'costCenters'
        ));
    }

    public function update(
        UpdateQuotationRequest $request,
        Quotation $quotation,
        QuotationService $service
    ) {
        abort_unless(auth()->user()?->can('quotations.edit'), 403);

        $this->assertQuotationAccess($quotation);

        if (in_array($quotation->status, ['converted', 'cancelled'], true)) {
            return redirect()
                ->route('quotations.show', $quotation->id)
                ->with('error', 'لا يمكن تعديل عرض سعر محول إلى فاتورة أو ملغي.');
        }

        $data = $this->prepareSecureQuotationData($request->validated());

        try {
            $quotation = $service->update($quotation, $data);

            return redirect()
                ->route('quotations.show', $quotation->id)
                ->with('success', 'تم تعديل عرض السعر بنجاح.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
    public function print(Quotation $quotation)
    {
        abort_unless(auth()->user()?->can('quotations.print'), 403);

        $this->assertQuotationAccess($quotation);

        $quotation->load([
            'customer',
            'branch',
            'costCenter',
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'creator',
            'convertedSalesInvoice',
        ]);

        return view('quotations.print', compact('quotation'));
    }

    public function cancel(Request $request, Quotation $quotation)
    {
        abort_unless(auth()->user()?->can('quotations.cancel'), 403);

        $this->assertQuotationAccess($quotation);

        if ($quotation->status === 'converted') {
            return back()->with('error', 'لا يمكن إلغاء عرض سعر محول إلى فاتورة.');
        }

        if ($quotation->status === 'cancelled') {
            return back()->with('error', 'عرض السعر ملغي مسبقًا.');
        }

        $data = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $quotation->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
            'cancel_reason' => $data['cancel_reason'] ?? null,
        ]);

        return back()->with('success', 'تم إلغاء عرض السعر بنجاح.');
    }


    public function productUnits(Product $product)
    {
        abort_unless(auth()->user()?->can('quotations.create'), 403);

        $units = ProductUnit::with('unit')
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($productUnit) {
                return [
                    'id' => $productUnit->id,
                    'unit_name' => $productUnit->unit?->unit_name
                        ?? $productUnit->unit?->name
                        ?? 'وحدة',

                    'sale_price' => (float) (
                        $productUnit->sale_price
                        ?? $productUnit->selling_price
                        ?? $productUnit->price
                        ?? 0
                    ),

                    'minimum_sale_price' => (float) (
                        $productUnit->minimum_sale_price
                        ?? 0
                    ),

                    'is_default' => (int) (
                        $productUnit->is_default
                        ?? 0
                    ),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $units,
        ]);
    }

    public function customerData(Customer $customer)
    {
        abort_unless(auth()->user()?->can('quotations.create'), 403);

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

    public function convertToInvoice(Quotation $quotation, SalesInvoiceService $salesInvoiceService)
    {
        abort_unless(auth()->user()?->can('quotations.convert'), 403);

        $this->assertQuotationAccess($quotation);

        if ($quotation->status === 'converted') {
            return back()->with('error', 'عرض السعر محول إلى فاتورة مسبقًا.');
        }

        if ($quotation->status === 'cancelled') {
            return back()->with('error', 'لا يمكن تحويل عرض سعر ملغي إلى فاتورة.');
        }

        if ($quotation->status === 'rejected') {
            return back()->with('error', 'لا يمكن تحويل عرض سعر مرفوض إلى فاتورة.');
        }

        if (! $quotation->warehouse_id) {
            return back()->with('error', 'لا يمكن التحويل إلى فاتورة بدون تحديد مستودع في عرض السعر.');
        }

        $quotation->load([
            'items',
        ]);

        if ($quotation->items->isEmpty()) {
            return back()->with('error', 'لا يمكن تحويل عرض سعر بدون أصناف.');
        }

        try {
            $invoice = DB::transaction(function () use ($quotation, $salesInvoiceService) {

                $invoiceData = [
                    'customer_id' => $quotation->customer_id,
                    'customer_type' => $quotation->customer_type,

                    'customer_name' => $quotation->customer_name,
                    'customer_mobile' => $quotation->customer_mobile,
                    'customer_tax_number' => $quotation->customer_tax_number,
                    'customer_address' => $quotation->customer_address,

                    /*
                        نحولها كفاتورة مسودة آجل بالكامل حتى لا نسجل مدفوعات تلقائية.
                        يمكن تعديل طريقة السداد لاحقًا حسب شاشة الفاتورة عندك.
                    */
                    'payment_type' => 'credit',
                    'payment_method' => null,
                    'paid_amount' => 0,

                    'branch_id' => $quotation->branch_id,
                    'cost_center_id' => $quotation->cost_center_id,
                    'warehouse_id' => $quotation->warehouse_id,

                    'invoice_date' => now()->toDateString(),

                    'notes' => $quotation->notes,

                    'save_action' => 'draft',

                    'items' => $quotation->items->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'product_unit_id' => $item->product_unit_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'discount_amount' => $item->discount_amount,
                            'vat_rate' => $item->vat_rate,
                        ];
                    })->toArray(),
                ];

                $invoice = $salesInvoiceService->store($invoiceData);

                $quotation->update([
                    'status' => 'converted',
                    'converted_sales_invoice_id' => $invoice->id,
                    'converted_at' => now(),
                    'converted_by' => auth()->id(),
                ]);

                return $invoice;
            });

            return redirect()
                ->route('sales-invoices.show', $invoice->id)
                ->with('success', 'تم تحويل عرض السعر إلى فاتورة بيع بنجاح.');

        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }


    public function approve(Quotation $quotation)
    {
        abort_unless(auth()->user()?->can('quotations.approve'), 403);

        $this->assertQuotationAccess($quotation);

        if (in_array($quotation->status, ['converted', 'cancelled'], true)) {
            return back()->with('error', 'لا يمكن اعتماد عرض سعر محول أو ملغي.');
        }

        if ($quotation->status === 'approved') {
            return back()->with('error', 'عرض السعر معتمد مسبقًا.');
        }

        $quotation->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'تم اعتماد عرض السعر بنجاح.');
    }

    public function reject(Request $request, Quotation $quotation)
    {
        abort_unless(auth()->user()?->can('quotations.reject'), 403);

        $this->assertQuotationAccess($quotation);

        if (in_array($quotation->status, ['converted', 'cancelled'], true)) {
            return back()->with('error', 'لا يمكن رفض عرض سعر محول أو ملغي.');
        }

        if ($quotation->status === 'rejected') {
            return back()->with('error', 'عرض السعر مرفوض مسبقًا.');
        }

        $quotation->update([
            'status' => 'rejected',
        ]);

        return back()->with('success', 'تم رفض عرض السعر بنجاح.');
    }
}