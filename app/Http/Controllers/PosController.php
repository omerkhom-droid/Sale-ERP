<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\POS\PosShiftService;
use App\Services\POS\PosOrderService;
use App\Models\PosOrder;
use App\Models\PosSetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function __construct(
        private readonly PosShiftService $posShiftService
    ) {
    }

    private function actorUserType(): string
    {
        return auth()->user()?->user_type ?? 'user';
    }

    private function actorCanSeeAllCompanies(): bool
    {
        return in_array($this->actorUserType(), ['master', 'system_admin'], true);
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

        if ($this->actorCanSeeAllCompanyBranches()) {
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

    private function applyBranchScope($query, string $column = 'id')
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $branchIds);
    }

    private function applyWarehouseScope($query)
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

    private function assertWarehouseAllowed(int $warehouseId): void
    {
        $query = Warehouse::query()
            ->whereKey($warehouseId);

        $this->applyWarehouseScope($query);

        abort_unless(
            $query->exists(),
            403,
            'لا تملك صلاحية استخدام هذا المستودع.'
        );
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('pos.view'), 403);

        $branchesQuery = Branch::query()
            ->orderBy('branch_name');

        $this->applyBranchScope($branchesQuery, 'id');

        $branches = $branchesQuery->get();

        $warehousesQuery = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('warehouse_name');

        $this->applyWarehouseScope($warehousesQuery);

        $warehouses = $warehousesQuery->get();

        $openShift = $this->posShiftService->currentOpenShift();

        $defaultPosSetting = null;
        $currentPosSetting = null;

        if ($openShift) {
            $currentPosSetting = PosSetting::query()
                ->where('branch_id', $openShift->branch_id)
                ->first();

            $defaultPosSetting = $currentPosSetting;
        }

        if (! $openShift && $branches->isNotEmpty()) {
            $defaultBranchId = (int) request('branch_id', $branches->first()->id);

            if (! $branches->contains('id', $defaultBranchId)) {
                $defaultBranchId = (int) $branches->first()->id;
            }

            $defaultPosSetting = PosSetting::query()
                ->where('branch_id', $defaultBranchId)
                ->first();

            $currentPosSetting = $defaultPosSetting;
        }

        return view('pos.index', compact(
            'branches',
            'warehouses',
            'openShift',
            'defaultPosSetting',
            'currentPosSetting'
        ));
    }

    public function openShift(Request $request)
    {
        abort_unless(auth()->user()?->can('pos.open_shift'), 403);

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'opening_cash' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->assertWarehouseAllowed((int) $data['warehouse_id']);

        try {
            $shift = $this->posShiftService->openShift($data);

            return response()->json([
                'success' => true,
                'message' => 'تم فتح الوردية بنجاح.',
                'shift' => $shift,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function closeShift(Request $request, PosShift $posShift)
    {
        abort_unless(auth()->user()?->can('pos.close_shift'), 403);

        abort_unless(
            (int) $posShift->user_id === (int) auth()->id(),
            403,
            'لا يمكنك إغلاق وردية مستخدم آخر.'
        );

        $data = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->posShiftService->refreshShiftTotals($posShift);

            $shift = $this->posShiftService->closeShift($posShift, $data);

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق الوردية بنجاح.',
                'shift' => $shift,
                'report_url' => route('pos.shift-report', $shift->id),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function categories()
    {
        abort_unless(auth()->user()?->can('pos.view'), 403);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('category_name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'category_name' => $category->category_name,
                ];
            })
            ->values();

        return response()->json($categories);
    }

    public function products(Request $request)
    {
        abort_unless(auth()->user()?->can('pos.view'), 403);

        $query = Product::query()
            ->with([
                'category',
                'defaultUnit.unit',
                'images',
            ])
            ->where('is_active', true)
            ->where('show_in_pos', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->q);

            $query->where(function ($q) use ($search) {
                $q->where('product_name_ar', 'like', "%{$search}%")
                    ->orWhere('product_name_en', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('keywords', 'like', "%{$search}%");
            });
        }

        $products = $query
            ->orderBy('pos_sort_order')
            ->orderBy('product_name_ar')
            ->limit(80)
            ->get()
            ->map(function (Product $product) {
                $defaultUnit = $product->defaultUnit;
                
                $image = $product->images
                    ->sortByDesc('is_default')
                    ->first();

                $imageUrl = $image
                    ? asset('storage/' . $image->image)
                    : asset('images/no-image.png');

                return [
                    'id' => $product->id,
                    'name' => $product->product_name_ar ?? $product->product_name_en ?? $product->sku,
                    'sku' => $product->sku,
                    'category_id' => $product->category_id,
                    'unit_id' => $product->defaultUnit?->id,
                    'unit_name' => $product->defaultUnit?->unit?->unit_name,
                    'price' => (float) ($product->defaultUnit?->sale_price ?? 0),
                    'color' => $product->pos_button_color,
                    'image_url' => $imageUrl,
                ];
            });

        return response()->json($products);
    }

    public function checkout(Request $request, PosOrderService $posOrderService)
    {
        abort_unless(auth()->user()?->can('pos.checkout'), 403);

        $data = $request->validate([
            'order_type' => ['required', 'string', 'in:dine_in,takeaway,delivery'],
            'table_no' => ['nullable', 'string', 'max:50'],

            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_mobile' => ['nullable', 'string', 'max:50'],
            'customer_tax_number' => ['nullable', 'string', 'max:100'],
            'customer_address' => ['nullable', 'string', 'max:500'],

            'discount_amount' => ['nullable', 'numeric', 'min:0'],

            'payment_method' => ['required', 'string', 'in:cash,card,bank_transfer'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'reference_no' => ['nullable', 'string', 'max:100'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $posOrderService->checkout($data);

            $posSetting = PosSetting::query()
                ->where('branch_id', $order->branch_id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'تم إتمام الدفع وإنشاء الفاتورة بنجاح.',
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'sales_invoice_id' => $order->sales_invoice_id,
                'invoice_no' => $order->salesInvoice?->invoice_no,
                'total_amount' => $order->total_amount,
                'change_amount' => $order->change_amount,
                'auto_print_receipt' => (bool) ($posSetting?->auto_print_receipt ?? true),
                'receipt_copies' => max(1, min(5, (int) ($posSetting?->receipt_copies ?? 1))),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }


    private function assertPosOrderAccess(PosOrder $posOrder): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        abort_unless(
            in_array((int) $posOrder->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية عرض هذا الطلب.'
        );
    }

    public function receipt(PosOrder $posOrder)
    {
        abort_unless(auth()->user()?->can('pos.print_receipt'), 403);

        $this->assertPosOrderAccess($posOrder);

        $posOrder->load([
            'branch',
            'warehouse',
            'shift.user',
            'items.product',
            'items.productUnit.unit',
            'payments',
            'salesInvoice',
            'creator',
        ]);

        $posSetting = PosSetting::query()
            ->where('branch_id', $posOrder->branch_id)
            ->first();

        $receiptCopies = (int) request('copies', $posSetting?->receipt_copies ?? 1);
        $receiptCopies = max(1, min(5, $receiptCopies));

        $autoPrint = filter_var(
            request('auto', $posSetting?->auto_print_receipt ?? true),
            FILTER_VALIDATE_BOOLEAN
        );

        return view('pos.receipt', compact(
            'posOrder',
            'posSetting',
            'receiptCopies',
            'autoPrint'
        ));
    }

    public function shiftReport(PosShift $posShift)
    {
        abort_unless(auth()->user()?->can('pos.daily_report'), 403);

        $branchIds = $this->actorBranchIds();

        if (! is_null($branchIds)) {
            abort_unless(
                in_array((int) $posShift->branch_id, $branchIds, true),
                403,
                'لا تملك صلاحية عرض تقرير هذه الوردية.'
            );
        }

        $this->posShiftService->refreshShiftTotals($posShift);

        $posShift->load([
            'branch',
            'warehouse',
            'user',
            'orders' => function ($query) {
                $query->with([
                    'payments',
                    'salesInvoice',
                    'creator',
                ])->latest();
            },
        ]);

        return view('pos.shift-report', compact('posShift'));
    }

    public function shifts(Request $request)
    {
        abort_unless(auth()->user()?->can('pos.daily_report'), 403);

        $branchesQuery = Branch::query()
            ->orderBy('branch_name');

        $this->applyBranchScope($branchesQuery, 'id');

        $branches = $branchesQuery->get();

        $query = PosShift::query()
            ->with([
                'branch',
                'warehouse',
                'user',
            ])
            ->withCount([
                'orders as paid_orders_count' => function ($query) {
                    $query->where('status', 'paid');
                },
                'orders as cancelled_orders_count' => function ($query) {
                    $query->where('status', 'cancelled');
                },
            ])
            ->latest('opened_at');

        $branchIds = $this->actorBranchIds();

        if (! is_null($branchIds)) {
            if (empty($branchIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('branch_id', $branchIds);
            }
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->branch_id;

            if (! is_null($branchIds)) {
                abort_unless(
                    in_array($branchId, $branchIds, true),
                    403,
                    'لا تملك صلاحية عرض ورديات هذا الفرع.'
                );
            }

            $query->where('branch_id', $branchId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('opened_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('opened_at', '<=', $request->to_date);
        }

        $shifts = $query->paginate(20)->withQueryString();

        return view('pos.shifts', compact(
            'branches',
            'shifts'
        ));
    }

    public function cancelOrder(
        Request $request,
        PosOrder $posOrder,
        PosOrderService $posOrderService
    ) {
        abort_unless(auth()->user()?->can('pos.cancel_order'), 403);

        $this->assertPosOrderAccess($posOrder);

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $order = $posOrderService->cancelOrder(
                posOrder: $posOrder,
                reason: $data['cancel_reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الطلب بنجاح.',
                'order_id' => $order->id,
                'order_no' => $order->order_no,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function itemsReport(Request $request)
    {
        abort_unless(auth()->user()?->can('pos.daily_report'), 403);

        $branchesQuery = Branch::query()->orderBy('branch_name');
        $this->applyBranchScope($branchesQuery, 'id');
        $branches = $branchesQuery->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('category_name')
            ->get();

        $branchIds = $this->actorBranchIds();

        $query = DB::table('pos_order_items')
            ->join('pos_orders', 'pos_orders.id', '=', 'pos_order_items.pos_order_id')
            ->join('products', 'products.id', '=', 'pos_order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('product_units', 'product_units.id', '=', 'pos_order_items.product_unit_id')
            ->leftJoin('units', 'units.id', '=', 'product_units.unit_id')
            ->where('pos_orders.status', 'paid')
            ->select([
                'products.id as product_id',
                'products.sku',
                'products.product_name_ar',
                'products.product_name_en',
                'categories.category_name',
                'units.unit_name',

                DB::raw('SUM(pos_order_items.quantity) as total_quantity'),
                DB::raw('SUM(pos_order_items.quantity * pos_order_items.unit_price) as gross_sales'),
                DB::raw('SUM(pos_order_items.discount_amount) as total_discount'),
                DB::raw('SUM(pos_order_items.vat_amount) as total_vat'),
                DB::raw('SUM(pos_order_items.line_total) as total_sales'),
                DB::raw('COUNT(DISTINCT pos_orders.id) as orders_count'),
            ])
            ->groupBy([
                'products.id',
                'products.sku',
                'products.product_name_ar',
                'products.product_name_en',
                'categories.category_name',
                'units.unit_name',
            ]);

        if (! is_null($branchIds)) {
            if (empty($branchIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('pos_orders.branch_id', $branchIds);
            }
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->branch_id;

            if (! is_null($branchIds)) {
                abort_unless(
                    in_array($branchId, $branchIds, true),
                    403,
                    'لا تملك صلاحية عرض بيانات هذا الفرع.'
                );
            }

            $query->where('pos_orders.branch_id', $branchId);
        }

        if ($request->filled('category_id')) {
            $query->where('products.category_id', (int) $request->category_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('pos_orders.paid_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('pos_orders.paid_at', '<=', $request->to_date);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);

            $query->where(function ($q) use ($search) {
                $q->where('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.product_name_ar', 'like', "%{$search}%")
                    ->orWhere('products.product_name_en', 'like', "%{$search}%");
            });
        }

        $sort = $request->get('sort', 'total_sales');

        if (! in_array($sort, ['total_sales', 'total_quantity', 'orders_count'], true)) {
            $sort = 'total_sales';
        }

        $items = $query
            ->orderByDesc($sort)
            ->paginate(30)
            ->withQueryString();

        $totals = [
            'quantity' => (float) $items->sum('total_quantity'),
            'gross_sales' => (float) $items->sum('gross_sales'),
            'discount' => (float) $items->sum('total_discount'),
            'vat' => (float) $items->sum('total_vat'),
            'sales' => (float) $items->sum('total_sales'),
            'orders_count' => (int) $items->sum('orders_count'),
        ];

        return view('pos.reports.items', compact(
            'items',
            'totals',
            'branches',
            'categories',
            'sort'
        ));
    }


    public function categoriesReport(Request $request)
    {
        abort_unless(auth()->user()?->can('pos.daily_report'), 403);

        $branchesQuery = Branch::query()->orderBy('branch_name');
        $this->applyBranchScope($branchesQuery, 'id');
        $branches = $branchesQuery->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('category_name')
            ->get();

        $branchIds = $this->actorBranchIds();

        $query = DB::table('pos_order_items')
            ->join('pos_orders', 'pos_orders.id', '=', 'pos_order_items.pos_order_id')
            ->join('products', 'products.id', '=', 'pos_order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('pos_orders.status', 'paid')
            ->select([
                'products.category_id',
                DB::raw("COALESCE(categories.category_name, 'بدون تصنيف') as category_name"),

                DB::raw('COUNT(DISTINCT products.id) as products_count'),
                DB::raw('COUNT(DISTINCT pos_orders.id) as orders_count'),
                DB::raw('SUM(pos_order_items.quantity) as total_quantity'),
                DB::raw('SUM(pos_order_items.quantity * pos_order_items.unit_price) as gross_sales'),
                DB::raw('SUM(pos_order_items.discount_amount) as total_discount'),
                DB::raw('SUM(pos_order_items.vat_amount) as total_vat'),
                DB::raw('SUM(pos_order_items.line_total) as total_sales'),
            ])
            ->groupBy([
                'products.category_id',
                'categories.category_name',
            ]);

        if (! is_null($branchIds)) {
            if (empty($branchIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('pos_orders.branch_id', $branchIds);
            }
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->branch_id;

            if (! is_null($branchIds)) {
                abort_unless(
                    in_array($branchId, $branchIds, true),
                    403,
                    'لا تملك صلاحية عرض بيانات هذا الفرع.'
                );
            }

            $query->where('pos_orders.branch_id', $branchId);
        }

        if ($request->filled('category_id')) {
            $query->where('products.category_id', (int) $request->category_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('pos_orders.paid_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('pos_orders.paid_at', '<=', $request->to_date);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);

            $query->where(function ($q) use ($search) {
                $q->where('categories.category_name', 'like', "%{$search}%")
                    ->orWhere('products.product_name_ar', 'like', "%{$search}%")
                    ->orWhere('products.product_name_en', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        $sort = $request->get('sort', 'total_sales');

        if (! in_array($sort, ['total_sales', 'total_quantity', 'orders_count', 'products_count'], true)) {
            $sort = 'total_sales';
        }

        $rows = $query
            ->orderByDesc($sort)
            ->get();

        $totals = [
            'products_count' => (int) $rows->sum('products_count'),
            'orders_count' => (int) $rows->sum('orders_count'),
            'quantity' => (float) $rows->sum('total_quantity'),
            'gross_sales' => (float) $rows->sum('gross_sales'),
            'discount' => (float) $rows->sum('total_discount'),
            'vat' => (float) $rows->sum('total_vat'),
            'sales' => (float) $rows->sum('total_sales'),
        ];

        return view('pos.reports.categories', compact(
            'rows',
            'totals',
            'branches',
            'categories',
            'sort'
        ));
    }


    public function paymentsReport(Request $request)
    {
        abort_unless(auth()->user()?->can('pos.daily_report'), 403);

        $branchesQuery = Branch::query()->orderBy('branch_name');
        $this->applyBranchScope($branchesQuery, 'id');
        $branches = $branchesQuery->get();

        $branchIds = $this->actorBranchIds();

        $query = DB::table('pos_order_payments')
            ->join('pos_orders', 'pos_orders.id', '=', 'pos_order_payments.pos_order_id')
            ->leftJoin('branches', 'branches.id', '=', 'pos_orders.branch_id')
            ->where('pos_orders.status', 'paid')
            ->select([
                'pos_order_payments.payment_method',
                DB::raw('COUNT(DISTINCT pos_orders.id) as orders_count'),
                DB::raw('SUM(pos_order_payments.amount) as total_amount'),
                DB::raw('MIN(pos_orders.paid_at) as first_payment_at'),
                DB::raw('MAX(pos_orders.paid_at) as last_payment_at'),
            ])
            ->groupBy('pos_order_payments.payment_method');

        if (! is_null($branchIds)) {
            if (empty($branchIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('pos_orders.branch_id', $branchIds);
            }
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->branch_id;

            if (! is_null($branchIds)) {
                abort_unless(
                    in_array($branchId, $branchIds, true),
                    403,
                    'لا تملك صلاحية عرض بيانات هذا الفرع.'
                );
            }

            $query->where('pos_orders.branch_id', $branchId);
        }

        if ($request->filled('payment_method')) {
            $query->where('pos_order_payments.payment_method', $request->payment_method);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('pos_orders.paid_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('pos_orders.paid_at', '<=', $request->to_date);
        }

        $rows = $query
            ->orderByDesc('total_amount')
            ->get();

        $detailsQuery = DB::table('pos_order_payments')
            ->join('pos_orders', 'pos_orders.id', '=', 'pos_order_payments.pos_order_id')
            ->leftJoin('pos_shifts', 'pos_shifts.id', '=', 'pos_orders.pos_shift_id')
            ->leftJoin('branches', 'branches.id', '=', 'pos_orders.branch_id')
            ->leftJoin('users', 'users.id', '=', 'pos_orders.created_by')
            ->where('pos_orders.status', 'paid')
            ->select([
                'pos_orders.id as order_id',
                'pos_orders.order_no',
                'pos_orders.paid_at',
                'pos_orders.total_amount',
                'pos_orders.discount_amount',
                'pos_orders.vat_amount',
                'pos_order_payments.payment_method',
                'pos_order_payments.amount as payment_amount',
                'pos_order_payments.reference_no',
                'pos_shifts.shift_no',
                'branches.branch_name',
                'users.name as cashier_name',
            ]);

        if (! is_null($branchIds)) {
            if (empty($branchIds)) {
                $detailsQuery->whereRaw('1 = 0');
            } else {
                $detailsQuery->whereIn('pos_orders.branch_id', $branchIds);
            }
        }

        if ($request->filled('branch_id')) {
            $detailsQuery->where('pos_orders.branch_id', (int) $request->branch_id);
        }

        if ($request->filled('payment_method')) {
            $detailsQuery->where('pos_order_payments.payment_method', $request->payment_method);
        }

        if ($request->filled('from_date')) {
            $detailsQuery->whereDate('pos_orders.paid_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $detailsQuery->whereDate('pos_orders.paid_at', '<=', $request->to_date);
        }

        $payments = $detailsQuery
            ->orderByDesc('pos_orders.paid_at')
            ->paginate(30)
            ->withQueryString();

        $totals = [
            'orders_count' => (int) $rows->sum('orders_count'),
            'total_amount' => (float) $rows->sum('total_amount'),
            'cash' => (float) $rows->where('payment_method', 'cash')->sum('total_amount'),
            'card' => (float) $rows->where('payment_method', 'card')->sum('total_amount'),
            'bank_transfer' => (float) $rows->where('payment_method', 'bank_transfer')->sum('total_amount'),
        ];

        return view('pos.reports.payments', compact(
            'rows',
            'payments',
            'totals',
            'branches'
        ));
    }

    public function currentShiftOrders()
    {
        abort_unless(auth()->user()?->can('pos.view'), 403);

        $shift = $this->posShiftService->currentOpenShift();

        if (! $shift) {
            return response()->json([
                'success' => true,
                'orders' => [],
                'message' => 'لا توجد وردية مفتوحة.',
            ]);
        }

        $orders = PosOrder::query()
            ->with([
                'payments',
                'salesInvoice',
            ])
            ->where('pos_shift_id', $shift->id)
            ->latest()
            ->limit(30)
            ->get()
            ->map(function (PosOrder $order) use ($shift) {
                $payments = $order->payments
                    ->map(function ($payment) {
                        return [
                            'method' => $payment->payment_method,
                            'label' => match ($payment->payment_method) {
                                'cash' => 'نقدي',
                                'card' => 'شبكة',
                                'bank_transfer' => 'تحويل',
                                default => $payment->payment_method,
                            },
                            'amount' => (float) $payment->amount,
                        ];
                    })
                    ->values();

                return [
                    'id' => $order->id,
                    'order_no' => $order->order_no,
                    'invoice_no' => $order->salesInvoice?->invoice_no,
                    'status' => $order->status,
                    'status_label' => match ($order->status) {
                        'paid' => 'مدفوع',
                        'cancelled' => 'ملغي',
                        'draft' => 'مسودة',
                        default => $order->status,
                    },
                    'order_type_label' => match ($order->order_type) {
                        'dine_in' => 'محلي',
                        'delivery' => 'توصيل',
                        default => 'سفري',
                    },
                    'total_amount' => (float) $order->total_amount,
                    'paid_amount' => (float) $order->paid_amount,
                    'change_amount' => (float) $order->change_amount,
                    'paid_at' => optional($order->paid_at ?? $order->created_at)->format('Y-m-d H:i'),
                    'payments' => $payments,
                    'receipt_url' => route('pos.receipt', $order->id),
                    'cancel_url' => route('pos.orders.cancel', $order->id),
                    'can_cancel' => (
                        $order->status === 'paid'
                        && $shift->status === 'open'
                        && auth()->user()?->can('pos.cancel_order')
                    ),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }
}