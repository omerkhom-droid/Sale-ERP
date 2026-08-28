<?php

namespace App\Http\Controllers;
use App\Http\Requests\StorePurchaseInvoiceRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseInvoiceService;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PurchaseInvoiceController extends Controller
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

    private function assertPurchaseInvoiceAccess(PurchaseInvoice $purchaseInvoice): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array((int) $purchaseInvoice->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى فاتورة مشتريات من فرع آخر.'
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

    private function assertSupplierAllowed(?int $supplierId): void
    {
        abort_unless($supplierId, 403, 'المورد غير صحيح.');

        $exists = Supplier::query()
            ->whereKey($supplierId)
            ->where('is_active', true)
            ->exists();

        abort_unless($exists, 403, 'المورد غير صحيح أو غير نشط.');
    }

    private function assertPaymentAccountAllowed(?int $accountId): void
    {
        if (! $accountId) {
            return;
        }

        $exists = Account::query()
            ->whereKey($accountId)
            ->where('is_active', true)
            ->where('is_group', false)
            ->where('account_type', 'asset')
            ->exists();

        abort_unless($exists, 403, 'حساب الدفع غير صحيح أو غير نشط.');
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

    private function prepareSecurePurchaseInvoiceData(array $data): array
    {
        $user = auth()->user();

        /*
            branch_admin / user:
            لا نعتمد الفرع القادم من الفورم.
            نثبت الفرع من المستخدم.
        */
        if (! $this->actorCanSeeAllCompanyBranches()) {
            $data['branch_id'] = $user?->branch_id;
        }

        $branchId = (int) ($data['branch_id'] ?? 0);
        $warehouseId = (int) ($data['warehouse_id'] ?? 0);

        $this->assertBranchAllowed($branchId);

        $warehouse = $this->assertWarehouseAllowed($warehouseId, $branchId);

        $this->assertSupplierAllowed((int) ($data['supplier_id'] ?? 0));

        $this->assertPaymentAccountAllowed((int) ($data['payment_account_id'] ?? 0));

        $this->assertCostCenterAllowed((int) ($data['cost_center_id'] ?? 0));

        $this->assertProductsAndUnitsAllowed($data);

        /*
            الفرع النهائي يجب أن يكون نفس فرع المستودع.
        */
        $data['branch_id'] = $branchId ?: $warehouse->branch_id;

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

    private function activeWarehouses()
    {
        return $this->applyWarehouseScope(
                Warehouse::query(),
                true
            )
            ->orderBy('warehouse_name')
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

    private function activePaymentAccounts()
    {
        /*
            حسابات الدفع عامة داخل النسخة الحالية.
        */
        return Account::query()
            ->where('is_active', true)
            ->where('is_group', false)
            ->where('account_type', 'asset')
            ->orderBy('account_code')
            ->get();
    }

    private function activeProducts()
    {
        /*
            المنتجات عامة داخل النظام.
            المخزون هو الذي يتبع المستودع.
        */
        return Product::query()
            ->with(['units.unit'])
            ->where('is_active', true)
            ->orderBy('product_name_ar')
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
        abort_unless(auth()->user()?->can('purchase_invoices.view'), 403);

        return view('purchase-invoices.index');
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('purchase_invoices.view'), 403);

        $user = auth()->user();

        $invoices = PurchaseInvoice::query()
            ->with([
                'supplier',
                'warehouse',
            ]);

        $this->applyPurchaseInvoiceScope($invoices);

        $invoices->latest('id');

        return DataTables::of($invoices)
            ->addIndexColumn()

            ->editColumn('invoice_date', function ($row) {
                return $row->invoice_date
                    ? Carbon::parse($row->invoice_date)->format('Y-m-d')
                    : '-';
            })

            ->addColumn('supplier_name', function ($row) {
                return e($row->supplier?->supplier_name ?? '-');
            })

            ->addColumn('warehouse_name', function ($row) {
                return e($row->warehouse?->warehouse_name ?? '-');
            })

            ->addColumn('payment_status_badge', function ($row) {
                return match ($row->payment_status) {
                    'paid' => '<span class="badge bg-success">مدفوعة</span>',
                    'partial' => '<span class="badge bg-warning text-dark">مدفوعة جزئياً</span>',
                    'unpaid' => '<span class="badge bg-danger">غير مدفوعة</span>',
                    default => '<span class="badge bg-light text-dark">غير معروف</span>',
                };
            })

            ->addColumn('status_badge', function ($row) {
                return match ($row->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'posted' => '<span class="badge bg-success">مرحلة</span>',
                    'cancelled' => '<span class="badge bg-danger">ملغاة</span>',
                    default => '<span class="badge bg-light text-dark">غير معروف</span>',
                };
            })

            ->editColumn('total_amount', function ($row) {
                return number_format((float) $row->total_amount, 2);
            })

            ->editColumn('paid_amount', function ($row) {
                return number_format((float) $row->paid_amount, 2);
            })

            ->editColumn('remaining_amount', function ($row) {
                return number_format((float) $row->remaining_amount, 2);
            })

            ->addColumn('actions', function ($row) use ($user) {
                $buttons = '<div class="d-flex gap-1 justify-content-center text-center flex-wrap">';

                if ($user && $user->can('purchase_invoices.view')) {
                    $buttons .= '
                        <a href="' . route('purchase-invoices.show', $row->id) . '"
                           class="btn btn-sm btn-info">
                            عرض
                        </a>
                    ';
                }

                if ($user && $user->can('purchase_invoices.print')) {
                    $buttons .= '
                        <a href="' . route('purchase-invoices.print', $row->id) . '"
                           target="_blank"
                           class="btn btn-sm btn-dark">
                            طباعة
                        </a>
                    ';
                }

                if (
                    $user
                    && $user->can('purchase_invoices.post')
                    && $row->status === 'draft'
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
                    $user
                    && $user->can('purchase_invoices.cancel')
                    && $row->status !== 'cancelled'
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

                return $buttons === '<div class="d-flex gap-1 justify-content-center text-center flex-wrap"></div>'
                    ? '<span class="text-muted">-</span>'
                    : $buttons;
            })

            ->rawColumns([
                'payment_status_badge',
                'status_badge',
                'actions',
            ])

            ->make(true);
    }

    public function create()
    {
        abort_unless(auth()->user()?->can('purchase_invoices.create'), 403);

        $suppliers = $this->activeSuppliers();
        $branches = $this->activeBranches();
        $warehouses = $this->activeWarehouses();
        $paymentAccounts = $this->activePaymentAccounts();
        $costCenters = $this->activeCostCenters();

        return view('purchase-invoices.create', compact(
            'suppliers',
            'branches',
            'warehouses',
            'paymentAccounts',
            'costCenters'
        ));
    }

    public function store(
        StorePurchaseInvoiceRequest $request,
        PurchaseInvoiceService $service
    ) {
        abort_unless(auth()->user()?->can('purchase_invoices.create'), 403);

        $data = $this->prepareSecurePurchaseInvoiceData($request->validated());

        try {
            $invoice = $service->store($data);

            return response()->json([
                'status' => true,
                'message' => 'تم حفظ فاتورة المشتريات بنجاح',
                'invoice_id' => $invoice->id,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function post(
        PurchaseInvoice $purchaseInvoice,
        PurchaseInvoiceService $service
    ) {
        abort_unless(auth()->user()?->can('purchase_invoices.post'), 403);

        $this->assertPurchaseInvoiceAccess($purchaseInvoice);

        if ($purchaseInvoice->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن ترحيل فاتورة غير مسودة.',
            ], 422);
        }

        try {
            $service->post($purchaseInvoice);

            return response()->json([
                'status' => true,
                'message' => 'تم ترحيل فاتورة المشتريات بنجاح',
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
        PurchaseInvoice $purchaseInvoice,
        PurchaseInvoiceService $service
    ) {
        abort_unless(auth()->user()?->can('purchase_invoices.cancel'), 403);

        $this->assertPurchaseInvoiceAccess($purchaseInvoice);

        if ($purchaseInvoice->status === 'cancelled') {
            return response()->json([
                'status' => false,
                'message' => 'الفاتورة ملغاة مسبقًا.',
            ], 422);
        }

        $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->cancel(
                $purchaseInvoice,
                $request->cancel_reason
            );

            return response()->json([
                'status' => true,
                'message' => 'تم إلغاء فاتورة المشتريات بنجاح',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        abort_unless(auth()->user()?->can('purchase_invoices.view'), 403);

        $this->assertPurchaseInvoiceAccess($purchaseInvoice);

        $purchaseInvoice->load([
            'supplier',
            'warehouse',
            'paymentAccount',
            'items.product',
            'items.productUnit.unit',
        ]);

        return view('purchase-invoices.show', compact('purchaseInvoice'));
    }

    public function print(PurchaseInvoice $purchaseInvoice)
    {
        abort_unless(auth()->user()?->can('purchase_invoices.print'), 403);

        $this->assertPurchaseInvoiceAccess($purchaseInvoice);

        $purchaseInvoice->load([
            'supplier',
            'warehouse',
            'paymentAccount',
            'items.product',
            'items.productUnit.unit',
        ]);

        return view('purchase-invoices.print', compact('purchaseInvoice'));
    }

    public function productUnits(Product $product): JsonResponse
    {
        if (! $product->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'الصنف غير نشط.',
            ], 404);
        }

        $units = $product->units()
            ->with('unit')
            ->orderByDesc('is_default')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $units->map(function ($productUnit) {
                return [
                    'id' => $productUnit->id,
                    'unit_name' => $productUnit->unit?->unit_name
                        ?? $productUnit->unit?->name
                        ?? '-',
                    'factor' => (float) ($productUnit->factor ?? 1),
                    'purchase_price' => (float) ($productUnit->purchase_price ?? 0),
                    'is_default' => (int) ($productUnit->is_default ?? 0),
                ];
            })->values(),
        ]);
    }

    
}